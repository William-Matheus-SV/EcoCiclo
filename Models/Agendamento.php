<?php

class Agendamento
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra uma nova solicitação de coleta (RN06, RN17)
     * O status inicial é sempre 'Solicitada'
     */
    public function criar(
        int $participanteId,
        int $materialId,
        string $endereco,
        string $bairro,
        string $dataAgendada,
        string $faixaHorario,
        string $observacoes
    ): bool {
        $sql = "INSERT INTO agendamento
                    (participante_id, material_id, endereco, bairro, data_agendada, faixa_horario, status, observacoes)
                VALUES
                    (:participante_id, :material_id, :endereco, :bairro, :data_agendada, :faixa_horario, 'Solicitada', :observacoes)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':participante_id' => $participanteId,
            ':material_id'     => $materialId,
            ':endereco'        => $endereco,
            ':bairro'          => $bairro,
            ':data_agendada'   => $dataAgendada,
            ':faixa_horario'   => $faixaHorario,
            ':observacoes'     => $observacoes
        ]);
    }

    /**
     * Lista agendamentos.
     * - $busca: termo opcional (nome, material, status ou bairro)
     * - $participanteId: se maior que 0, mostra só os agendamentos desse participante
     */
    public function listar(string $busca = '', int $participanteId = 0): array
    {
        // "WHERE 1=1" é sempre verdadeiro: serve só para podermos acrescentar "AND ..." depois
        $sql = "SELECT a.*, u.nome AS nome_participante, m.nome AS nome_material
                FROM agendamento a
                JOIN participante p ON a.participante_id = p.id
                JOIN usuario u ON p.usuario_id = u.id
                JOIN material m ON a.material_id = m.id
                WHERE 1=1";

        $parametros = [];

        if ($participanteId > 0) {
            $sql .= " AND a.participante_id = :participante_id";
            $parametros[':participante_id'] = $participanteId;
        }

        if ($busca !== '') {
            // Cada :nome só pode aparecer uma vez (EMULATE_PREPARES desligado)
            $sql .= " AND (u.nome LIKE :busca1 OR m.nome LIKE :busca2 OR a.status LIKE :busca3 OR a.bairro LIKE :busca4)";
            $termo = '%' . $busca . '%';
            $parametros[':busca1'] = $termo;
            $parametros[':busca2'] = $termo;
            $parametros[':busca3'] = $termo;
            $parametros[':busca4'] = $termo;
        }

        $sql .= " ORDER BY a.data_agendada DESC, a.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um agendamento pelo ID, já com nome do participante e do material
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT a.*, u.nome AS nome_participante, m.nome AS nome_material
                FROM agendamento a
                JOIN participante p ON a.participante_id = p.id
                JOIN usuario u ON p.usuario_id = u.id
                JOIN material m ON a.material_id = m.id
                WHERE a.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Atualiza os dados e o status de um agendamento.
     * Só altera se ele ainda estiver 'Solicitada' ou 'Agendada':
     * agendamentos Concluídos ou Cancelados não podem mais ser mexidos.
     */
    public function atualizar(
        int $id,
        int $materialId,
        string $endereco,
        string $bairro,
        string $dataAgendada,
        string $faixaHorario,
        string $status,
        string $observacoes
    ): bool {
        $sql = "UPDATE agendamento
                SET material_id = :material_id,
                    endereco = :endereco,
                    bairro = :bairro,
                    data_agendada = :data_agendada,
                    faixa_horario = :faixa_horario,
                    status = :status,
                    observacoes = :observacoes
                WHERE id = :id AND status IN ('Solicitada', 'Agendada')";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':material_id'   => $materialId,
            ':endereco'      => $endereco,
            ':bairro'        => $bairro,
            ':data_agendada' => $dataAgendada,
            ':faixa_horario' => $faixaHorario,
            ':status'        => $status,
            ':observacoes'   => $observacoes,
            ':id'            => $id
        ]);
    }
}