<?php
// CRUD_Agendamentos/listar.php - Listagem e busca de agendamentos (RN06, RN15)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Agendamento.php';
require_once __DIR__ . '/../Models/Participante.php';

$perfil = $_SESSION['usuario_perfil'];

$agendamentoModel  = new Agendamento($pdo);
$participanteModel = new Participante($pdo);

$busca = trim($_GET['busca'] ?? '');

// Participante só enxerga os próprios agendamentos (RN16).
// ADMIN e COLETOR enxergam todos (participante_id = 0 significa "sem filtro").
$filtro_participante = 0;
if ($perfil === 'PARTICIPANTE') {
    $meu = $participanteModel->buscarPorUsuarioId((int)$_SESSION['usuario_id']);
    $filtro_participante = $meu ? (int)$meu['id'] : -1;
}

if ($filtro_participante === -1) {
    // Usuário PARTICIPANTE sem cadastro de participante: lista vazia
    $agendamentos = [];
} else {
    $agendamentos = $agendamentoModel->listar($busca, $filtro_participante);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[1100px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <div class="mb-[15px]">
        <a href="../index.php" class="font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar ao Painel Principal</a>
    </div>

    <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <h2 class="m-0 text-2xl font-bold text-green-800"><?= ($perfil === 'PARTICIPANTE') ? 'Meus Agendamentos' : 'Agendamentos de Coleta' ?></h2>
        <a href="criar.php" class="rounded bg-green-800 px-4 py-[10px] text-center font-bold text-white no-underline hover:bg-green-900">+ Solicitar Coleta</a>
    </div>

    <form method="GET" action="listar.php" class="mb-5 flex flex-col gap-[10px] sm:flex-row">
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Pesquisar por participante, material, status ou bairro..." class="min-w-0 flex-1 rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
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
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Participante</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Data</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Horário</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Material</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Endereço / Bairro</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Status</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($agendamentos)): ?>
                <tr>
                    <td colspan="8" class="border border-gray-200 p-5 text-center text-gray-500">Nenhum agendamento encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($agendamentos as $a): ?>
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 p-3 text-sm"><?= (int)$a['id'] ?></td>
                        <td class="border border-gray-200 p-3 text-sm"><strong><?= htmlspecialchars($a['nome_participante']) ?></strong></td>
                        <td class="border border-gray-200 p-3 text-sm"><?= date('d/m/Y', strtotime($a['data_agendada'])) ?></td>
                        <td class="border border-gray-200 p-3 text-sm"><?= htmlspecialchars($a['faixa_horario']) ?></td>
                        <td class="border border-gray-200 p-3 text-sm"><?= htmlspecialchars($a['nome_material']) ?></td>
                        <td class="border border-gray-200 p-3 text-sm">
                            <?= htmlspecialchars($a['endereco']) ?><br>
                            <small class="text-gray-600"><?= htmlspecialchars($a['bairro']) ?></small>
                        </td>
                        <td class="border border-gray-200 p-3 text-sm">
                            <span class="inline-block rounded-full bg-green-100 px-2 py-1 text-[11px] font-bold text-green-900">
                                <?= htmlspecialchars($a['status']) ?>
                            </span>
                        </td>
                        <td class="border border-gray-200 p-3 text-sm">
                            <?php if ($a['status'] === 'Solicitada' || $a['status'] === 'Agendada'): ?>
                                <a href="editar.php?id=<?= (int)$a['id'] ?>" class="rounded bg-green-700 px-3 py-1.5 text-xs font-bold text-white no-underline hover:bg-green-900">Editar / Status</a>
                            <?php else: ?>
                                <a href="editar.php?id=<?= (int)$a['id'] ?>" class="rounded bg-gray-600 px-3 py-1.5 text-xs font-bold text-white no-underline hover:bg-gray-700">Ver</a>
                            <?php endif; ?>
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