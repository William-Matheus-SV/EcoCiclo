<?php
// listarMateriais.php - Catálogo de materiais recicláveis aceitos (RN04)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Auth/login.php");
    exit();
}

require_once __DIR__ . '/Config/database.php';
require_once __DIR__ . '/Models/Material.php';

$materialModel = new Material($pdo);
$materiais = $materialModel->listar();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiais - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[800px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <div class="mb-[15px]">
        <a href="index.php" class="font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar ao Painel Principal</a>
    </div>

    <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <h2 class="m-0 text-2xl font-bold text-green-800">Materiais Recicláveis</h2>
        <a href="CRUD_Agendamentos/criar.php" class="rounded bg-green-800 px-4 py-[10px] text-center font-bold text-white no-underline hover:bg-green-900">+ Solicitar Coleta</a>
    </div>

    <p class="mb-5 text-sm text-gray-600">Estes são os materiais aceitos nas coletas. Cada quilograma coletado gera 10 EcoPontos.</p>

    <div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Material</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">O que pode ser entregue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($materiais)): ?>
                <tr><td colspan="2" class="border border-gray-200 p-5 text-center text-gray-500">Nenhum material cadastrado.</td></tr>
            <?php else: ?>
                <?php foreach ($materiais as $m): ?>
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 p-3 text-sm"><strong><?= htmlspecialchars($m['nome']) ?></strong></td>
                        <td class="border border-gray-200 p-3 text-sm"><?= htmlspecialchars($m['descricao']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

</body>
</html>