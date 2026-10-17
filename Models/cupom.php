<?php

class Cupom
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Catálogo: cupons dentro da validade, de comércios ativos (RN12)
     * Os esgotados continuam aparecendo (a tela mostra "Esgotado")
     */
    public function listarDisponiveis(): array
    {
        $sql = "SELECT c.id, c.titulo, c.descricao, c.custo_ecopontos, c.validade, c.quantidade_disponivel,
                       co.nome AS nome_comercio, co.bairro AS bairro_comercio
                FROM cupom c
                JOIN comercio co ON c.comercio_id = co.id
                WHERE co.situacao_cadastral = 'Ativo'
                  AND c.validade >= CURDATE()
                ORDER BY c.custo_ecopontos ASC, c.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Troca um cupom por EcoPontos (RN13, RN14, RN17).
     * Tudo dentro de uma transação: valida, debita o saldo, baixa o estoque,
     * registra a troca e grava o débito no extrato. Se algo falhar, nada é salvo.
     *
     * Devolve um array: ['sucesso' => true/false, 'mensagem' => '...', 'codigo' => '...']
     */
    public function trocar(int $participanteId, int $cupomId): array
    {
        try {
            $this->pdo->beginTransaction();

            // Trava a linha do cupom enquanto a troca acontece (evita vender além do estoque)
            $stmt = $this->pdo->prepare(
                "SELECT c.titulo, c.custo_ecopontos, c.validade, c.quantidade_disponivel, co.situacao_cadastral
                 FROM cupom c
                 JOIN comercio co ON c.comercio_id = co.id
                 WHERE c.id = :id
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $cupomId]);
            $cupom = $stmt->fetch(PDO::FETCH_ASSOC);

            // RN13: o cupom precisa existir, estar válido e disponível
            if (!$cupom || $cupom['situacao_cadastral'] !== 'Ativo' || $cupom['validade'] < date('Y-m-d')) {
                $this->pdo->rollBack();
                return ['sucesso' => false, 'mensagem' => 'Este cupom não está mais disponível.'];
            }

            if ((int)$cupom['quantidade_disponivel'] <= 0) {
                $this->pdo->rollBack();
                return ['sucesso' => false, 'mensagem' => 'Este cupom está esgotado.'];
            }

            $custo = (int)$cupom['custo_ecopontos'];

            // RN13: só debita se o saldo for suficiente. A própria condição do UPDATE
            // (saldo_ecopontos >= custo) garante isso mesmo com dois cliques ao mesmo tempo.
            $stmt = $this->pdo->prepare(
                "UPDATE participante
                 SET saldo_ecopontos = saldo_ecopontos - :custo1
                 WHERE id = :id AND saldo_ecopontos >= :custo2"
            );
            $stmt->execute([
                ':custo1' => $custo,
                ':custo2' => $custo,
                ':id'     => $participanteId
            ]);

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return ['sucesso' => false, 'mensagem' => 'Saldo insuficiente de EcoPontos para esta troca.'];
            }

            // Baixa uma unidade do estoque
            $stmt = $this->pdo->prepare(
                "UPDATE cupom SET quantidade_disponivel = quantidade_disponivel - 1 WHERE id = :id"
            );
            $stmt->execute([':id' => $cupomId]);

            // Código que o participante apresenta no comércio (ex.: ECO-3F9A1C2B)
            $codigo = 'ECO-' . strtoupper(bin2hex(random_bytes(4)));

            // RN17: a troca relaciona participante, cupom e pontos debitados
            $stmt = $this->pdo->prepare(
                "INSERT INTO troca_cupom (participante_id, cupom_id, pontos_debitados, codigo_resgatado)
                 VALUES (:participante_id, :cupom_id, :pontos, :codigo)"
            );
            $stmt->execute([
                ':participante_id' => $participanteId,
                ':cupom_id'        => $cupomId,
                ':pontos'          => $custo,
                ':codigo'          => $codigo
            ]);
            $trocaId = (int) $this->pdo->lastInsertId();

            // Débito no extrato, ligado à troca. O código fica na descrição
            // para o participante poder consultá-lo depois na carteira.
            $descricao = 'Troca pelo cupom: ' . $cupom['titulo'] . ' (código ' . $codigo . ')';
            $stmt = $this->pdo->prepare(
                "INSERT INTO movimentacao_ecopontos (participante_id, tipo, quantidade, descricao, troca_id)
                 VALUES (:participante_id, 'DEBITO', :quantidade, :descricao, :troca_id)"
            );
            $stmt->execute([
                ':participante_id' => $participanteId,
                ':quantidade'      => $custo,
                ':descricao'       => $descricao,
                ':troca_id'        => $trocaId
            ]);

            $this->pdo->commit();

            return ['sucesso' => true, 'mensagem' => 'Troca realizada.', 'codigo' => $codigo];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // RN14 e RN18: nada foi salvo, e a mensagem não expõe erro do banco
            return ['sucesso' => false, 'mensagem' => 'Não foi possível concluir a troca. Nenhum ponto foi debitado.'];
        }
    }
}