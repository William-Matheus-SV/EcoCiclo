<?php

class Carteira
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Extrato do participante: créditos (coletas) e débitos (trocas de cupom) (RN11)
     * Do mais recente para o mais antigo
     */
    public function listarExtrato(int $participanteId): array
    {
        $sql = "SELECT tipo, quantidade, data_movimentacao, descricao
                FROM movimentacao_ecopontos
                WHERE participante_id = :participante_id
                ORDER BY data_movimentacao DESC, id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':participante_id' => $participanteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}