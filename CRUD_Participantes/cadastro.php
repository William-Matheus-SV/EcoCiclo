<?php
// CRUD_Participantes/cadastro.php - Cadastro de participante (RN05, RN16, RN18)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

// Só ADMIN e COLETOR gerenciam participantes (RN16: dados pessoais)
if ($_SESSION['usuario_perfil'] === 'PARTICIPANTE') {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Participante.php';

$mensagem_sucesso = '';
$mensagem_erro = '';

// Valores iniciais dos campos (vazios na primeira abertura)
$nome = '';
$email = '';
$telefone = '';
$endereco = '';
$bairro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $senha    = $_POST['senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $bairro   = trim($_POST['bairro'] ?? '');

    // Validações (RN05 e RN18): uma por vez, com mensagem clara
    if ($nome === '' || $email === '' || $senha === '' || $telefone === '' || $endereco === '' || $bairro === '') {
        $mensagem_erro = "Preencha todos os campos obrigatórios (*).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Informe um e-mail válido.";
    } elseif (strlen($senha) < 6) {
        $mensagem_erro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $usuarioModel      = new Usuario($pdo);
        $participanteModel = new Participante($pdo);

        // RN05: o e-mail é único
        if ($usuarioModel->buscarPorEmail($email)) {
            $mensagem_erro = "Este e-mail já está cadastrado.";
        } else {
            try {
                // Transação: ou salva usuário E participante, ou não salva nada
                $pdo->beginTransaction();

                // RN16: o model já grava a senha com password_hash
                $usuarioId = $usuarioModel->criar($nome, $email, $senha, 'PARTICIPANTE');

                if ($usuarioId) {
                    $participanteModel->criar($usuarioId, $telefone, $endereco, $bairro);
                    $pdo->commit();

                    $mensagem_sucesso = "Participante cadastrado com sucesso!";

                    // Limpa o formulário depois de salvar
                    $nome = '';
                    $email = '';
                    $telefone = '';
                    $endereco = '';
                    $bairro = '';
                } else {
                    $pdo->rollBack();
                    $mensagem_erro = "Não foi possível concluir o cadastro.";
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // RN18: mensagem simples, sem mostrar erro do banco
                $mensagem_erro = "Ocorreu um erro ao salvar o cadastro. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Participante - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[650px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <a href="listar.php" class="mb-5 inline-block font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar para a Lista de Participantes</a>
    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Cadastrar Novo Participante</h2>

    <?php if ($mensagem_sucesso !== ''): ?>
        <div class="mb-5 rounded border border-green-200 bg-green-100 px-[15px] py-3 text-sm text-green-900"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="cadastro.php">
        <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
            <div class="flex flex-col sm:col-span-2">
                <label for="nome" class="mb-1 text-[13px] font-bold text-gray-600">Nome Completo *</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
            </div>

            <div class="flex flex-col">
                <label for="email" class="mb-1 text-[13px] font-bold text-gray-600">E-mail *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
            </div>

            <div class="flex flex-col">
                <label for="senha" class="mb-1 text-[13px] font-bold text-gray-600">Senha de Acesso * (mín. 6 caracteres)</label>
                <input type="password" id="senha" name="senha" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="telefone" class="mb-1 text-[13px] font-bold text-gray-600">Telefone *</label>
                <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone) ?>" placeholder="(00) 00000-0000" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="endereco" class="mb-1 text-[13px] font-bold text-gray-600">Endereço (Rua, Número) *</label>
                <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($endereco) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="bairro" class="mb-1 text-[13px] font-bold text-gray-600">Bairro *</label>
                <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars($bairro) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
            </div>

            <button type="submit" class="mt-[10px] rounded bg-green-800 p-3 text-[15px] font-bold text-white hover:bg-green-900 sm:col-span-2">Salvar Cadastro</button>
        </div>
    </form>
</div>

</body>
</html>