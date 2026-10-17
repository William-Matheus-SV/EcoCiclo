<?php

class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT id, nome, email, senha_hash, perfil FROM usuario WHERE email = :email";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT id, nome, email, perfil FROM usuario WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function criar(
        string $nome,
        string $email,
        string $senha,
        string $perfil = 'PARTICIPANTE',
    ): int|bool {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuario (nome, email, senha_hash, perfil)
                VALUES (:nome, :email, :senha_hash, :perfil)";

        $stmt = $this->pdo->prepare($sql);

        $sucesso = $stmt->execute([
            ':nome'       => $nome,
            ':email'      => $email,
            ':senha_hash' => $senhaHash,
            ':perfil'     => $perfil
        ]);

        if ($sucesso) {
            return (int) $this->pdo->lastInsertId();
        }

        return false;
    }
}