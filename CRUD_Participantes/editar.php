<?php
// CRUD_Participantes/editar.php - Detalhes e edição de participante (RN05, RN15, RN18)
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
require_once __DIR__ . '/../Models/Participante.php';

$participanteModel = new Participante($pdo);

// ID vem pela URL: editar.php?id=3
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: listar.php");
    exit();
}

$mensagem_sucesso = '';
$mensagem_erro = '';

// Salvar alterações (formulário enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $bairro   = trim($_POST['bairro'] ?? '');

    if ($nome === '' || $telefone === '' || $endereco === '' || $bairro === '') {
        $mensagem_erro = "Preencha todos os campos obrigatórios (*).";
    } else {
        try {
            $participanteModel->atualizar($id, $nome, $telefone, $endereco, $bairro);
            $mensagem_sucesso = "Dados atualizados com sucesso!";
        } catch (Exception $e) {
            // RN18: mensagem simples, sem expor erro do banco
            $mensagem_erro = "Ocorreu um erro ao salvar as alterações. Tente novamente.";
        }
    }
}

// Carrega os dados atuais (depois de salvar, já mostra os valores novos)
$participante = $participanteModel->buscarPorId($id);

if (!$participante) {
    header("Location: listar.php");
    exit();
}

// Se houve erro na validação, mantém o que a pessoa digitou no formulário
if ($mensagem_erro !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $participante['nome']     = $nome;
    $participante['telefone'] = $telefone;
    $participante['endereco'] = $endereco;
    $participante['bairro']   = $bairro;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Participante - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[650px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <a href="listar.php" class="mb-5 inline-block font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar para a Lista de Participantes</a>
    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Detalhes do Participante</h2>

    <?php if ($mensagem_sucesso !== ''): ?>
        <div class="mb-5 rounded border border-green-200 bg-green-100 px-[15px] py-3 text-sm text-green-900"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="editar.php?id=<?= $id ?>">
        <div class="mb-[15px] flex flex-col">
            <label for="nome" class="mb-1 text-[13px] font-bold text-gray-600">Nome Completo *</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($participante['nome']) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label class="mb-1 text-[13px] font-bold text-gray-600">E-mail (não pode ser alterado)</label>
            <input type="email" value="<?= htmlspecialchars($participante['email']) ?>" disabled class="cursor-not-allowed rounded border border-gray-300 bg-gray-200 p-[10px] text-sm">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="telefone" class="mb-1 text-[13px] font-bold text-gray-600">Telefone *</label>
            <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($participante['telefone']) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="endereco" class="mb-1 text-[13px] font-bold text-gray-600">Endereço *</label>
            <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($participante['endereco']) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="bairro" class="mb-1 text-[13px] font-bold text-gray-600">Bairro *</label>
            <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars($participante['bairro']) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label class="mb-1 text-[13px] font-bold text-gray-600">Saldo de EcoPontos</label>
            <input type="text" value="<?= (int)$participante['saldo_ecopontos'] ?> pts" disabled class="cursor-not-allowed rounded border border-gray-300 bg-gray-200 p-[10px] text-sm">
        </div>

        <button type="submit" class="w-full rounded bg-green-800 p-3 text-[15px] font-bold text-white hover:bg-green-900">Salvar Alterações</button>
    </form>
</div>

</body>
</html>