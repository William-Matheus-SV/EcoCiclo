<?php

class Material
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos os materiais recicláveis cadastrados (RN04)
     */
    public function listar(): array
    {
        $sql = "SELECT id, nome, descricao FROM material ORDER BY nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}