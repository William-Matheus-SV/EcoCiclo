<?php
// coleta.php - Confirmação da coleta e geração de EcoPontos (RN08, RN09, RN10, RN18)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Auth/login.php");
    exit();
}

// Só ADMIN e COLETOR confirmam coletas
if ($_SESSION['usuario_perfil'] === 'PARTICIPANTE') {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/Config/database.php';
require_once __DIR__ . '/Models/Coleta.php';

$coletaModel = new Coleta($pdo);

// Depois de confirmar com sucesso, a página recarrega com ?ok=1
$mensagem_sucesso = isset($_GET['ok']) ? "Coleta confirmada e EcoPontos creditados com sucesso!" : '';
$mensagem_erro = '';

$agendamento_id = 0;
$peso_texto = '';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento_id = (int)($_POST['agendamento_id'] ?? 0);
    $peso_texto     = trim($_POST['peso_kg'] ?? '');
    $observacoes    = trim($_POST['observacoes'] ?? '');

    // Aceita vírgula ou ponto no peso (5,5 ou 5.5)
    $peso_numero = str_replace(',', '.', $peso_texto);

    if ($agendamento_id <= 0) {
        $mensagem_erro = "Selecione o agendamento da coleta.";
    } elseif (!is_numeric($peso_numero) || (float)$peso_numero <= 0) {
        // RN08: o peso precisa ser numérico e maior que zero
        $mensagem_erro = "Informe um peso numérico maior que zero.";
    } else {
        $resultado = $coletaModel->confirmar(
            $agendamento_id,
            (int)$_SESSION['usuario_id'],   // responsável pela confirmação (RN08)
            (float)$peso_numero,
            $observacoes
        );

        if ($resultado['sucesso']) {
            // Redireciona: se a pessoa recarregar a página (F5), o formulário NÃO é reenviado (RN10)
            header("Location: coleta.php?ok=1");
            exit();
        } else {
            $mensagem_erro = $resultado['mensagem'];
        }
    }
}

$pendentes = $coletaModel->listarPendentes();
$recentes  = $coletaModel->listarRecentes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Coletas - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[950px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <a href="index.php" class="mb-5 inline-block font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar ao Painel Principal</a>
    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Confirmação de Coletas</h2>

    <?php if ($mensagem_sucesso !== ''): ?>
        <div class="mb-5 rounded border border-green-200 bg-green-100 px-[15px] py-3 text-sm text-green-900"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="coleta.php" class="mb-[30px] grid grid-cols-1 gap-[15px] rounded-md border border-gray-200 bg-gray-50 p-5 sm:grid-cols-[2fr_1fr]">
        <div class="flex flex-col">
            <label for="agendamento_id" class="mb-1 text-[13px] font-bold text-gray-600">Agendamento *</label>
            <select id="agendamento_id" name="agendamento_id" required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                <option value="">-- Selecione o agendamento --</option>
                <?php foreach ($pendentes as $ag): ?>
                    <option value="<?= (int)$ag['id'] ?>" <?= ((int)$ag['id'] === $agendamento_id) ? 'selected' : '' ?>>
                        #<?= (int)$ag['id'] ?> - <?= htmlspecialchars($ag['nome_participante']) ?>
                        | <?= htmlspecialchars($ag['nome_material']) ?>
                        | <?= date('d/m/Y', strtotime($ag['data_agendada'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex flex-col">
            <label for="peso_kg" class="mb-1 text-[13px] font-bold text-gray-600">Peso coletado (kg) *</label>
            <input type="number" id="peso_kg" name="peso_kg" step="0.01" min="0.01" value="<?= htmlspecialchars($peso_texto) ?>" placeholder="Ex: 5.5" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="flex flex-col sm:col-span-2">
            <label for="observacoes" class="mb-1 text-[13px] font-bold text-gray-600">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="2" placeholder="Ex: Material bem separado" class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700"><?= htmlspecialchars($observacoes) ?></textarea>
        </div>

        <button type="submit" class="rounded bg-green-800 p-3 text-[15px] font-bold text-white hover:bg-green-900 sm:col-span-2">Confirmar Coleta e Creditar EcoPontos (10 pts por kg)</button>
    </form>

    <h3 class="mb-4 mt-0 text-xl font-bold text-green-800">Últimas Coletas Confirmadas</h3>
    <div class="overflow-x-auto">
    <table class="mt-[15px] w-full border-collapse">
        <thead>
            <tr>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Data / Hora</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Participante</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Material</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Peso (kg)</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">EcoPontos</th>
                <th class="border border-gray-200 bg-green-50 p-3 text-left text-sm text-green-800">Confirmada por</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentes)): ?>
                <tr><td colspan="6" class="border border-gray-200 p-5 text-center text-gray-500">Nenhuma coleta confirmada até o momento.</td></tr>
            <?php else: ?>
                <?php foreach ($recentes as $c): ?>
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 p-3 text-sm"><?= date('d/m/Y H:i', strtotime($c['data_efetiva'])) ?></td>
                        <td class="border border-gray-200 p-3 text-sm"><strong><?= htmlspecialchars($c['nome_participante']) ?></strong></td>
                        <td class="border border-gray-200 p-3 text-sm"><?= htmlspecialchars($c['nome_material']) ?></td>
                        <td class="border border-gray-200 p-3 text-sm"><?= number_format($c['peso_kg'], 2, ',', '.') ?> kg</td>
                        <td class="border border-gray-200 p-3 text-sm font-bold text-green-800">+<?= (int)$c['pontos_gerados'] ?> pts</td>
                        <td class="border border-gray-200 p-3 text-sm"><?= htmlspecialchars($c['nome_responsavel']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

</body>
</html>