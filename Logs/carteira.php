<?php
// Logs/carteira.php - Saldo e histórico de EcoPontos (RN11, RN16)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Participante.php';
require_once __DIR__ . '/../Models/Carteira.php';

$participanteModel = new Participante($pdo);
$carteiraModel     = new Carteira($pdo);

// A carteira é sempre a do usuário logado (o ID nunca vem da URL)
$participante = $participanteModel->buscarPorUsuarioId((int)$_SESSION['usuario_id']);

$saldo = 0;
$extrato = [];

if ($participante) {
    $saldo = (int)$participante['saldo_ecopontos'];
    $extrato = $carteiraModel->listarExtrato((int)$participante['id']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteira de EcoPontos - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[900px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <div class="mb-[15px]">
        <a href="../index.php" class="font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar ao Painel Principal</a>
    </div>

    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Carteira de EcoPontos</h2>

    <?php if (!$participante): ?>
        <div class="rounded border border-gray-300 bg-gray-200 px-[15px] py-3 text-sm text-gray-700">Sua conta não possui carteira de EcoPontos. A carteira existe apenas para participantes.</div>
    <?php else: ?>
        <div class="my-5 mb-[30px] flex flex-col justify-between gap-5 rounded-lg bg-gradient-to-br from-green-800 to-green-900 p-6 text-white sm:flex-row sm:items-center sm:p-[25px]">
            <div>
                <div class="mb-1 text-sm uppercase tracking-wider opacity-90">Saldo disponível</div>
                <div class="text-4xl font-bold"><?= number_format($saldo, 0, ',', '.') ?> <small class="text-lg">pts</small></div>
            </div>
            <a href="troca_cupom.php" class="rounded bg-amber-400 px-5 py-3 font-bold text-gray-800 no-underline hover:bg-amber-500">Trocar por cupons &rarr;</a>
        </div>

        <h3 class="mb-4 mt-0 text-xl font-bold text-green-800">Histórico de movimentações</h3>

        <div class="overflow-x-auto">
        <table class="mt-[15px] w-full border-collapse">
            <thead>
                <tr>
                    <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Data / Hora</th>
                    <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Tipo</th>
                    <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Descrição</th>
                    <th class="border border-gray-200 bg-green-50 p-3 text-right text-sm text-green-800">Pontos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($extrato)): ?>
                    <tr><td colspan="4" class="border border-gray-200 p-6 text-center text-gray-500">Nenhuma movimentação até o momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($extrato as $m): ?>
                        <?php $credito = ($m['tipo'] === 'CREDITO'); ?>
                        <tr class="even:bg-gray-50">
                            <td class="border border-gray-200 p-3 text-sm"><?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?></td>
                            <td class="border border-gray-200 p-3 text-sm">
                                <span class="inline-block rounded-full px-2 py-1 text-[11px] font-bold <?= $credito ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' ?>">
                                    <?= $credito ? 'CRÉDITO' : 'DÉBITO' ?>
                                </span>
                            </td>
                            <td class="border border-gray-200 p-3 text-sm"><?= htmlspecialchars($m['descricao']) ?></td>
                            <td class="border border-gray-200 p-3 text-right text-sm font-bold <?= $credito ? 'text-green-800' : 'text-red-700' ?>">
                                <?= $credito ? '+' : '-' ?><?= number_format((int)$m['quantidade'], 0, ',', '.') ?> pts
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>