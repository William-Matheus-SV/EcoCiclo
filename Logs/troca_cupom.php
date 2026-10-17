<?php
// Logs/troca_cupom.php - Catálogo de cupons e troca por EcoPontos (RN12, RN13, RN14, RN18)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Participante.php';
require_once __DIR__ . '/../Models/Cupom.php';

$participanteModel = new Participante($pdo);
$cupomModel        = new Cupom($pdo);

// Quem troca é sempre o participante do usuário logado
$participante = $participanteModel->buscarPorUsuarioId((int)$_SESSION['usuario_id']);
$saldo = $participante ? (int)$participante['saldo_ecopontos'] : 0;

// Mensagem guardada na sessão pela troca anterior (aparece uma vez e é apagada)
$mensagem_sucesso = '';
if (isset($_SESSION['mensagem_troca'])) {
    $mensagem_sucesso = $_SESSION['mensagem_troca'];
    unset($_SESSION['mensagem_troca']);
}
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cupom_id = (int)($_POST['cupom_id'] ?? 0);

    if (!$participante) {
        $mensagem_erro = "Somente participantes podem trocar cupons.";
    } elseif ($cupom_id <= 0) {
        $mensagem_erro = "Selecione um cupom.";
    } else {
        $resultado = $cupomModel->trocar((int)$participante['id'], $cupom_id);

        if ($resultado['sucesso']) {
            $_SESSION['mensagem_troca'] = "Cupom resgatado com sucesso! Seu código é " . $resultado['codigo']
                . ". Ele também fica registrado no seu extrato.";

            // Redireciona: recarregar a página não repete a troca
            header("Location: troca_cupom.php");
            exit();
        } else {
            $mensagem_erro = $resultado['mensagem'];
        }
    }
}

$cupons = $cupomModel->listarDisponiveis();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupons - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-5 max-w-[1000px] rounded-lg bg-white p-6 shadow-md sm:p-[30px]">
    <div class="mb-[15px]">
        <a href="carteira.php" class="font-bold text-green-800 no-underline hover:text-green-900">&larr; Voltar para a Carteira</a>
        &nbsp;|&nbsp;
        <a href="../index.php" class="font-bold text-green-800 no-underline hover:text-green-900">Painel Principal</a>
    </div>

    <h2 class="mb-6 mt-0 text-2xl font-bold text-green-800">Cupons de Desconto</h2>

    <?php if ($participante): ?>
        <div class="my-5 flex flex-col justify-between gap-3 rounded-md border border-green-200 bg-green-50 px-5 py-[15px] sm:flex-row sm:items-center">
            <span>Seu saldo atual:</span>
            <strong class="text-xl text-green-900"><?= number_format($saldo, 0, ',', '.') ?> EcoPontos</strong>
        </div>
    <?php else: ?>
        <div class="mb-5 mt-5 rounded border border-gray-300 bg-gray-200 px-[15px] py-3 text-sm text-gray-700">Você está vendo o catálogo. A troca de cupons é exclusiva para participantes.</div>
    <?php endif; ?>

    <?php if ($mensagem_sucesso !== ''): ?>
        <div class="mb-5 rounded border border-green-200 bg-green-100 px-[15px] py-3 text-sm text-green-900"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <?php if (empty($cupons)): ?>
        <p class="text-center text-gray-500">Nenhum cupom disponível no momento.</p>
    <?php else: ?>
        <div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-5">
            <?php foreach ($cupons as $c): ?>
                <?php
                $esgotado = ((int)$c['quantidade_disponivel'] <= 0);
                $sem_saldo = ($saldo < (int)$c['custo_ecopontos']);
                ?>
                <div class="flex flex-col justify-between rounded-lg border border-gray-200 bg-white p-5">
                    <div>
                        <div class="mb-1 text-xs uppercase tracking-wide text-gray-500"><?= htmlspecialchars($c['nome_comercio']) ?> &middot; <?= htmlspecialchars($c['bairro_comercio']) ?></div>
                        <h3 class="mb-[10px] mt-0 text-lg font-bold text-green-800"><?= htmlspecialchars($c['titulo']) ?></h3>
                        <p class="mb-[15px] mt-0 text-[13px] text-gray-600"><?= htmlspecialchars($c['descricao']) ?></p>
                    </div>

                    <div>
                        <div class="mb-[15px] text-[13px] leading-7">
                            <span class="text-base font-bold text-green-800"><?= number_format((int)$c['custo_ecopontos'], 0, ',', '.') ?> pts</span><br>
                            Válido até <?= date('d/m/Y', strtotime($c['validade'])) ?><br>
                            <?= $esgotado ? 'Esgotado' : ((int)$c['quantidade_disponivel'] . ' disponíveis') ?>
                        </div>

                        <?php if ($participante): ?>
                            <form method="POST" action="troca_cupom.php">
                                <input type="hidden" name="cupom_id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="w-full cursor-pointer rounded border-0 bg-green-800 p-[10px] font-bold text-white hover:bg-green-900 disabled:cursor-not-allowed disabled:bg-gray-300" <?= ($esgotado || $sem_saldo) ? 'disabled' : '' ?>>
                                    <?php
                                    if ($esgotado) {
                                        echo 'Esgotado';
                                    } elseif ($sem_saldo) {
                                        echo 'Saldo insuficiente';
                                    } else {
                                        echo 'Trocar por este cupom';
                                    }
                                    ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>