<?php
// CRUD_Agendamentos/criar.php - Solicitação de coleta (RN06, RN07, RN17, RN18)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/listas.php';
require_once __DIR__ . '/../Models/Agendamento.php';
require_once __DIR__ . '/../Models/Participante.php';
require_once __DIR__ . '/../Models/Material.php';

$perfil = $_SESSION['usuario_perfil'];

$participanteModel = new Participante($pdo);
$agendamentoModel  = new Agendamento($pdo);
$materialModel     = new Material($pdo);

$materiais = $materialModel->listar();

$mensagem_sucesso = '';
$mensagem_erro = '';

$participante_id = 0;
$participantes = [];
$endereco = '';
$bairro = '';

if ($perfil === 'PARTICIPANTE') {
    // O participante só pode pedir coleta para ele mesmo.
    // Já deixamos o endereço e o bairro do cadastro preenchidos (ele pode trocar).
    $meu = $participanteModel->buscarPorUsuarioId((int)$_SESSION['usuario_id']);
    if ($meu) {
        $participante_id = (int)$meu['id'];
        $endereco = $meu['endereco'];
        $bairro = $meu['bairro'];
    }
} else {
    // ADMIN e COLETOR escolhem o participante numa lista
    $participantes = $participanteModel->listar();
}

$material_id = 0;
$data_agendada = '';
$faixa_horario = '';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($perfil !== 'PARTICIPANTE') {
        $participante_id = (int)($_POST['participante_id'] ?? 0);
    }
    $material_id   = (int)($_POST['material_id'] ?? 0);
    $endereco      = trim($_POST['endereco'] ?? '');
    $bairro        = trim($_POST['bairro'] ?? '');
    $data_agendada = $_POST['data_agendada'] ?? '';
    $faixa_horario = $_POST['faixa_horario'] ?? '';
    $observacoes   = trim($_POST['observacoes'] ?? '');

    $hoje = date('Y-m-d');

    // RN07: sem participante, endereço, data e faixa de horário não salva
    if ($participante_id <= 0) {
        $mensagem_erro = "Participante não identificado. Verifique o cadastro.";
    } elseif ($endereco === '' || $bairro === '' || $data_agendada === '' || $faixa_horario === '' || $material_id <= 0) {
        $mensagem_erro = "Preencha todos os campos obrigatórios (*).";
    } elseif (strtotime($data_agendada) === false) {
        $mensagem_erro = "Informe uma data válida.";
    } elseif ($data_agendada < $hoje) {
        // RN07: datas passadas não são aceitas em novas solicitações
        $mensagem_erro = "A data desejada não pode ser anterior a hoje.";
    } elseif (!in_array($faixa_horario, $faixasHorario)) {
        $mensagem_erro = "Escolha uma faixa de horário válida.";
    } else {
        try {
            $agendamentoModel->criar($participante_id, $material_id, $endereco, $bairro, $data_agendada, $faixa_horario, $observacoes);
            $mensagem_sucesso = "Coleta solicitada com sucesso!";

            // Limpa só o que muda de um pedido para outro
            $material_id = 0;
            $data_agendada = '';
            $faixa_horario = '';
            $observacoes = '';
        } catch (Exception $e) {
            // RN18: mensagem simples, sem expor erro do banco
            $mensagem_erro = "Ocorreu um erro ao salvar a solicitação. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Coleta - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[600px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <a href="listar.php" class="mb-5 inline-block font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar para os Agendamentos</a>
    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Solicitar Coleta</h2>

    <?php if ($mensagem_sucesso !== ''): ?>
        <div class="mb-5 rounded border border-green-200 bg-green-100 px-[15px] py-3 text-sm text-green-900"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="criar.php">
        <?php if ($perfil !== 'PARTICIPANTE'): ?>
            <div class="mb-[15px] flex flex-col">
                <label for="participante_id" class="mb-1 text-[13px] font-bold text-gray-600">Participante *</label>
                <select id="participante_id" name="participante_id" required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($participantes as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $participante_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['bairro']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="mb-[15px] flex flex-col">
            <label for="material_id" class="mb-1 text-[13px] font-bold text-gray-600">Material Reciclável *</label>
            <select id="material_id" name="material_id" required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                <option value="">-- Selecione o material --</option>
                <?php foreach ($materiais as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= ((int)$m['id'] === $material_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="endereco" class="mb-1 text-[13px] font-bold text-gray-600">Endereço da Coleta *</label>
            <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($endereco) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="bairro" class="mb-1 text-[13px] font-bold text-gray-600">Bairro *</label>
            <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars($bairro) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="data_agendada" class="mb-1 text-[13px] font-bold text-gray-600">Data Desejada *</label>
            <input type="date" id="data_agendada" name="data_agendada" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($data_agendada) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="faixa_horario" class="mb-1 text-[13px] font-bold text-gray-600">Faixa de Horário *</label>
            <select id="faixa_horario" name="faixa_horario" required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                <option value="">-- Selecione --</option>
                <?php foreach ($faixasHorario as $faixa): ?>
                    <option value="<?= htmlspecialchars($faixa) ?>" <?= ($faixa === $faixa_horario) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($faixa) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="observacoes" class="mb-1 text-[13px] font-bold text-gray-600">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="3" placeholder="Ex: Deixar na portaria" class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700"><?= htmlspecialchars($observacoes) ?></textarea>
        </div>

        <button type="submit" class="mt-[10px] w-full rounded bg-green-800 p-3 text-[15px] font-bold text-white hover:bg-green-900">Solicitar Coleta</button>
    </form>
</div>

</body>
</html>