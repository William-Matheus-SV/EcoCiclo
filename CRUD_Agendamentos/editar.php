<?php
// CRUD_Agendamentos/editar.php - Alteração de dados, status e cancelamento (RN06, RN07, RN18)
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

$agendamentoModel  = new Agendamento($pdo);
$participanteModel = new Participante($pdo);
$materialModel     = new Material($pdo);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: listar.php");
    exit();
}

$agendamento = $agendamentoModel->buscarPorId($id);

if (!$agendamento) {
    header("Location: listar.php");
    exit();
}

// Participante só mexe nos próprios agendamentos
if ($perfil === 'PARTICIPANTE') {
    $meu = $participanteModel->buscarPorUsuarioId((int)$_SESSION['usuario_id']);
    if (!$meu || (int)$meu['id'] !== (int)$agendamento['participante_id']) {
        header("Location: listar.php");
        exit();
    }
}

$materiais = $materialModel->listar();

// Só dá para editar enquanto estiver Solicitada ou Agendada
$editavel = ($agendamento['status'] === 'Solicitada' || $agendamento['status'] === 'Agendada');

// Para onde cada status pode ir. "Concluida" não aparece aqui de propósito:
// a conclusão só acontece na tela de confirmação da coleta (peso + EcoPontos).
$transicoes = [
    'Solicitada' => ['Solicitada', 'Agendada', 'Cancelada'],
    'Agendada'   => ['Agendada', 'Cancelada']
];

$opcoes_status = [];
if ($editavel) {
    foreach ($transicoes[$agendamento['status']] as $s) {
        // Participante pode cancelar, mas quem agenda é ADMIN/COLETOR
        if ($perfil === 'PARTICIPANTE' && $s === 'Agendada' && $agendamento['status'] !== 'Agendada') {
            continue;
        }
        $opcoes_status[] = $s;
    }
}

$mensagem_sucesso = isset($_GET['ok']) ? "Agendamento atualizado com sucesso!" : '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $editavel) {
    $material_id   = (int)($_POST['material_id'] ?? 0);
    $endereco      = trim($_POST['endereco'] ?? '');
    $bairro        = trim($_POST['bairro'] ?? '');
    $data_agendada = $_POST['data_agendada'] ?? '';
    $faixa_horario = $_POST['faixa_horario'] ?? '';
    $status        = $_POST['status'] ?? '';
    $observacoes   = trim($_POST['observacoes'] ?? '');

    $hoje = date('Y-m-d');

    if ($endereco === '' || $bairro === '' || $data_agendada === '' || $faixa_horario === '' || $material_id <= 0) {
        $mensagem_erro = "Preencha todos os campos obrigatórios (*).";
    } elseif (strtotime($data_agendada) === false) {
        $mensagem_erro = "Informe uma data válida.";
    } elseif ($data_agendada !== $agendamento['data_agendada'] && $data_agendada < $hoje) {
        // RN07: não aceita data passada nova (a data antiga pode permanecer)
        $mensagem_erro = "A data não pode ser anterior a hoje.";
    } elseif (!in_array($faixa_horario, $faixasHorario)) {
        $mensagem_erro = "Escolha uma faixa de horário válida.";
    } elseif (!in_array($status, $opcoes_status)) {
        $mensagem_erro = "Mudança de status não permitida.";
    } else {
        try {
            $agendamentoModel->atualizar($id, $material_id, $endereco, $bairro, $data_agendada, $faixa_horario, $status, $observacoes);

            // Redireciona para a própria página: evita reenviar o formulário ao atualizar (F5)
            header("Location: editar.php?id=" . $id . "&ok=1");
            exit();
        } catch (Exception $e) {
            // RN18: mensagem simples, sem expor erro do banco
            $mensagem_erro = "Ocorreu um erro ao salvar as alterações. Tente novamente.";
        }
    }

    // Se chegou aqui com erro, mantém o que a pessoa digitou
    if ($mensagem_erro !== '') {
        $agendamento['material_id']   = $material_id;
        $agendamento['endereco']      = $endereco;
        $agendamento['bairro']        = $bairro;
        $agendamento['data_agendada'] = $data_agendada;
        $agendamento['faixa_horario'] = $faixa_horario;
        $agendamento['observacoes']   = $observacoes;
    }
}

// Atributo HTML para travar os campos quando não é mais editável
$travado = $editavel ? '' : 'disabled';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[600px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <a href="listar.php" class="mb-5 inline-block font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar para os Agendamentos</a>
    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Agendamento #<?= (int)$agendamento['id'] ?></h2>

    <?php if ($mensagem_sucesso !== ''): ?>
        <div class="mb-5 rounded border border-green-200 bg-green-100 px-[15px] py-3 text-sm text-green-900"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <?php if (!$editavel): ?>
        <div class="mb-5 rounded border border-gray-300 bg-gray-200 px-[15px] py-3 text-sm text-gray-700">
            Este agendamento está <strong><?= htmlspecialchars($agendamento['status']) ?></strong> e não pode mais ser alterado.
        </div>
    <?php endif; ?>

    <form method="POST" action="editar.php?id=<?= $id ?>">
        <div class="mb-[15px] flex flex-col">
            <label class="mb-1 text-[13px] font-bold text-gray-600">Participante</label>
            <input type="text" value="<?= htmlspecialchars($agendamento['nome_participante']) ?>" disabled class="cursor-not-allowed rounded border border-gray-300 bg-gray-200 p-[10px] text-sm">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="material_id" class="mb-1 text-[13px] font-bold text-gray-600">Material Reciclável *</label>
            <select id="material_id" name="material_id" <?= $travado ?> required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-200">
                <?php foreach ($materiais as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= ((int)$m['id'] === (int)$agendamento['material_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="endereco" class="mb-1 text-[13px] font-bold text-gray-600">Endereço da Coleta *</label>
            <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($agendamento['endereco']) ?>" <?= $travado ?> required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-200">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="bairro" class="mb-1 text-[13px] font-bold text-gray-600">Bairro *</label>
            <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars($agendamento['bairro']) ?>" <?= $travado ?> required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-200">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="data_agendada" class="mb-1 text-[13px] font-bold text-gray-600">Data *</label>
            <input type="date" id="data_agendada" name="data_agendada" value="<?= htmlspecialchars($agendamento['data_agendada']) ?>" <?= $travado ?> required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-200">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="faixa_horario" class="mb-1 text-[13px] font-bold text-gray-600">Faixa de Horário *</label>
            <select id="faixa_horario" name="faixa_horario" <?= $travado ?> required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-200">
                <?php foreach ($faixasHorario as $faixa): ?>
                    <option value="<?= htmlspecialchars($faixa) ?>" <?= ($faixa === $agendamento['faixa_horario']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($faixa) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="status" class="mb-1 text-[13px] font-bold text-gray-600">Status *</label>
            <?php if ($editavel): ?>
                <select id="status" name="status" required class="rounded border border-gray-300 bg-white p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                    <?php foreach ($opcoes_status as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= ($s === $agendamento['status']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="text" value="<?= htmlspecialchars($agendamento['status']) ?>" disabled class="cursor-not-allowed rounded border border-gray-300 bg-gray-200 p-[10px] text-sm">
            <?php endif; ?>
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="observacoes" class="mb-1 text-[13px] font-bold text-gray-600">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="3" <?= $travado ?> class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-200"><?= htmlspecialchars($agendamento['observacoes'] ?? '') ?></textarea>
        </div>

        <?php if ($editavel): ?>
            <button type="submit" class="mt-[10px] w-full rounded bg-green-800 p-3 text-[15px] font-bold text-white hover:bg-green-900">Salvar Alterações</button>
        <?php endif; ?>
    </form>
</div>

</body>
</html>