<?php
// CRUD_Participantes/listar.php - Listagem e busca de participantes (RN05, RN15)
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

// Termo digitado na busca (vem pela URL: listar.php?busca=ana)
$busca = trim($_GET['busca'] ?? '');

// Se $busca estiver vazia, o model devolve todos; senão, só os que combinam
$participantes = $participanteModel->listar($busca);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[1000px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <div class="mb-[15px]">
        <a href="../index.php" class="font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar ao Painel Principal</a>
    </div>

    <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <h2 class="m-0 text-2xl font-bold text-green-800">Participantes Cadastrados</h2>
        <a href="cadastro.php" class="rounded bg-green-800 px-4 py-[10px] text-center font-bold text-white no-underline hover:bg-green-900">+ Novo Participante</a>
    </div>

    <!-- Busca por termo (RN15) -->
    <form method="GET" action="listar.php" class="mb-5 flex flex-col gap-[10px] sm:flex-row">
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Pesquisar por nome, e-mail ou bairro..." class="min-w-0 flex-1 rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        <button type="submit" class="rounded bg-green-800 px-[18px] py-[10px] font-bold text-white hover:bg-green-900">Buscar</button>
        <?php if ($busca !== ''): ?>
            <a href="listar.php" class="self-center text-[13px] text-red-700 no-underline hover:text-red-900">Limpar Filtro</a>
        <?php endif; ?>
    </form>

    <div class="overflow-x-auto">
    <table class="mt-[10px] w-full border-collapse">
        <thead>
            <tr>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">ID</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Nome</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">E-mail</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Telefone</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Bairro</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($participantes)): ?>
                <tr>
                    <td colspan="6" class="border border-gray-200 p-5 text-center text-gray-500">Nenhum participante encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($participantes as $p): ?>
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 p-3 text-left text-sm"><?= (int)$p['id'] ?></td>
                        <td class="border border-gray-200 p-3 text-left text-sm"><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                        <td class="border border-gray-200 p-3 text-left text-sm"><?= htmlspecialchars($p['email']) ?></td>
                        <td class="border border-gray-200 p-3 text-left text-sm"><?= htmlspecialchars($p['telefone']) ?></td>
                        <td class="border border-gray-200 p-3 text-left text-sm"><?= htmlspecialchars($p['bairro']) ?></td>
                        <td class="border border-gray-200 p-3 text-left text-sm">
                            <a href="editar.php?id=<?= (int)$p['id'] ?>" class="rounded bg-green-700 px-3 py-1.5 text-xs font-bold text-white no-underline hover:bg-green-900">Detalhes / Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

</body>
</html>