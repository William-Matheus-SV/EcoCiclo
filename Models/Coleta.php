<?php

class Coleta
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Agendamentos que ainda podem receber a confirmação da coleta
     */
    public function listarPendentes(): array
    {
        $sql = "SELECT a.id, a.data_agendada, a.faixa_horario,
                       u.nome AS nome_participante, m.nome AS nome_material
                FROM agendamento a
                JOIN participante p ON a.participante_id = p.id
                JOIN usuario u ON p.usuario_id = u.id
                JOIN material m ON a.material_id = m.id
                WHERE a.status IN ('Solicitada', 'Agendada')
                ORDER BY a.data_agendada ASC, a.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Últimas 10 coletas confirmadas (com participante, material e responsável)
     */
    public function listarRecentes(): array
    {
        $sql = "SELECT c.*, u.nome AS nome_participante, m.nome AS nome_material,
                       r.nome AS nome_responsavel
                FROM coleta c
                JOIN agendamento a ON c.agendamento_id = a.id
                JOIN participante p ON a.participante_id = p.id
                JOIN usuario u ON p.usuario_id = u.id
                JOIN material m ON a.material_id = m.id
                JOIN usuario r ON c.responsavel_id = r.id
                ORDER BY c.data_efetiva DESC, c.id DESC
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Confirma a coleta: registra o peso, gera os EcoPontos UMA única vez,
     * grava no extrato, atualiza o saldo e conclui o agendamento.
     * Tudo dentro de uma transação: ou acontece tudo, ou não acontece nada.
     *
     * Devolve um array: ['sucesso' => true/false, 'mensagem' => '...']
     */
    public function confirmar(int $agendamentoId, int $responsavelId, float $pesoKg, string $observacoes): array
    {
        try {
            $this->pdo->beginTransaction();

            // FOR UPDATE trava a linha do agendamento até o fim da transação:
            // se dois cliques chegarem juntos, o segundo espera o primeiro terminar.
            $stmt = $this->pdo->prepare(
                "SELECT participante_id, status FROM agendamento WHERE id = :id FOR UPDATE"
            );
            $stmt->execute([':id' => $agendamentoId]);
            $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$agendamento) {
                $this->pdo->rollBack();
                return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado.'];
            }

            // RN10: só confirma se ainda não foi concluído nem cancelado
            if ($agendamento['status'] !== 'Solicitada' && $agendamento['status'] !== 'Agendada') {
                $this->pdo->rollBack();
                return ['sucesso' => false, 'mensagem' => 'Esta coleta já foi concluída ou cancelada. Nenhum ponto foi creditado.'];
            }

            // RN09: 10 EcoPontos por kg. Trabalhamos em centésimos de kg (número inteiro)
            // para evitar erros de arredondamento de números decimais.
            $pesoKg = round($pesoKg, 2);
            $centesimos = (int) round($pesoKg * 100);
            $pontos = (int) round($centesimos / 10);

            if ($pontos < 1) {
                $this->pdo->rollBack();
                return ['sucesso' => false, 'mensagem' => 'O peso informado é muito baixo para gerar EcoPontos.'];
            }

            // RN08: registra a coleta (data_efetiva usa o valor padrão: agora)
            $stmt = $this->pdo->prepare(
                "INSERT INTO coleta (agendamento_id, responsavel_id, peso_kg, pontos_gerados, observacoes)
                 VALUES (:agendamento_id, :responsavel_id, :peso_kg, :pontos, :observacoes)"
            );
            $stmt->execute([
                ':agendamento_id' => $agendamentoId,
                ':responsavel_id' => $responsavelId,
                ':peso_kg'        => $pesoKg,
                ':pontos'         => $pontos,
                ':observacoes'    => $observacoes
            ]);
            $coletaId = (int) $this->pdo->lastInsertId();

            // RN09 e RN17: crédito no extrato, ligado à coleta que o originou
            $descricao = 'Crédito por coleta concluída (' . number_format($pesoKg, 2, '.', '') . ' kg)';
            $stmt = $this->pdo->prepare(
                "INSERT INTO movimentacao_ecopontos (participante_id, tipo, quantidade, descricao, coleta_id)
                 VALUES (:participante_id, 'CREDITO', :quantidade, :descricao, :coleta_id)"
            );
            $stmt->execute([
                ':participante_id' => $agendamento['participante_id'],
                ':quantidade'      => $pontos,
                ':descricao'       => $descricao,
                ':coleta_id'       => $coletaId
            ]);

            // Atualiza o saldo do participante
            $stmt = $this->pdo->prepare(
                "UPDATE participante SET saldo_ecopontos = saldo_ecopontos + :pontos WHERE id = :id"
            );
            $stmt->execute([
                ':pontos' => $pontos,
                ':id'     => $agendamento['participante_id']
            ]);

            // Conclui o agendamento
            $stmt = $this->pdo->prepare("UPDATE agendamento SET status = 'Concluida' WHERE id = :id");
            $stmt->execute([':id' => $agendamentoId]);

            $this->pdo->commit();

            return ['sucesso' => true, 'mensagem' => 'Coleta confirmada.'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // RN18: mensagem simples, sem expor erro do banco
            return ['sucesso' => false, 'mensagem' => 'Erro ao registrar a coleta. Tente novamente.'];
        }
    }
}