<?php

class Participante
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Cadastra o participante ligado a um usuário (RN05, RN17)
     */
    public function criar(int $usuarioId, string $telefone, string $endereco, string $bairro): bool
    {
        $sql = "INSERT INTO participante (usuario_id, telefone, endereco, bairro)
                VALUES (:usuario_id, :telefone, :endereco, :bairro)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':telefone'   => $telefone,
            ':endereco'   => $endereco,
            ':bairro'     => $bairro
        ]);
    }

    /**
     * Lista participantes, com busca opcional por nome, e-mail ou bairro (RN15)
     */
    public function listar(string $busca = ''): array
    {
        $sql = "SELECT p.*, u.nome, u.email
                FROM participante p
                JOIN usuario u ON p.usuario_id = u.id";

        if ($busca !== '') {
            // Com EMULATE_PREPARES desligado, cada :nome só pode aparecer uma vez
            $sql .= " WHERE u.nome LIKE :busca1 OR u.email LIKE :busca2 OR p.bairro LIKE :busca3";
        }

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->pdo->prepare($sql);

        if ($busca !== '') {
            $termo = '%' . $busca . '%';
            $stmt->bindValue(':busca1', $termo);
            $stmt->bindValue(':busca2', $termo);
            $stmt->bindValue(':busca3', $termo);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca o participante a partir do ID do usuário logado
     */
    public function buscarPorUsuarioId(int $usuarioId): ?array
    {
        $sql = "SELECT p.*, u.nome, u.email
                FROM participante p
                JOIN usuario u ON p.usuario_id = u.id
                WHERE p.usuario_id = :usuario_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca um participante pelo ID dele (usado na tela de edição) (RN15)
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT p.*, u.nome, u.email
                FROM participante p
                JOIN usuario u ON p.usuario_id = u.id
                WHERE p.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Atualiza nome (tabela usuario) e contato/endereço (tabela participante)
     * Um único UPDATE com JOIN altera as duas tabelas ao mesmo tempo
     */
    public function atualizar(int $id, string $nome, string $telefone, string $endereco, string $bairro): bool
    {
        $sql = "UPDATE participante p
                JOIN usuario u ON u.id = p.usuario_id
                SET u.nome = :nome,
                    p.telefone = :telefone,
                    p.endereco = :endereco,
                    p.bairro = :bairro
                WHERE p.id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nome'     => $nome,
            ':telefone' => $telefone,
            ':endereco' => $endereco,
            ':bairro'   => $bairro,
            ':id'       => $id
        ]);
    }
}