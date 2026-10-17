<?php 

    $servidor ='localhost';
    $banco = 'banco_ecociclo';
    $usuario = 'root';
    $senha = '';

    try{
        $pdo = new PDO(
            "mysql:host=$servidor;dbname=$banco;charset=utf8mb4",
            $usuario,
            $senha,
            [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=> false,
            ]

        );
    } catch (PDOException $erro){
        die("Erro ao conectar, tente novamente!");
    }