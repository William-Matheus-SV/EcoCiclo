<?php
// index.php - Página principal e navegação entre os módulos (RN03, RN04)
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Auth/login.php");
    exit();
}

$usuario_nome   = $_SESSION['usuario_nome'];
$usuario_perfil = $_SESSION['usuario_perfil'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCiclo - Painel Principal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 font-sans text-gray-800">

    <header class="flex flex-col justify-between gap-4 bg-green-800 px-6 py-4 text-white shadow sm:flex-row sm:items-center sm:px-[30px]">
        <h1 class="m-0 text-[22px] font-bold">EcoCiclo</h1>
        <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:gap-[15px]">
            <span>Usuário: <strong><?= htmlspecialchars($usuario_nome) ?></strong> (<?= htmlspecialchars($usuario_perfil) ?>)</span>
            <a href="Auth/logout.php" class="rounded bg-red-700 px-[14px] py-2 font-bold text-white no-underline hover:bg-red-800">Sair do Sistema</a>
        </div>
    </header>

    <div class="mx-auto my-10 max-w-[1000px] px-5">
        <div class="mb-[30px] rounded border-l-[5px] border-green-800 bg-green-50 p-5">
            <h2 class="mt-0 text-green-900">Bem-vindo(a), <?= htmlspecialchars($usuario_nome) ?>!</h2>
            <p>Escolha um módulo abaixo para solicitar coletas, acompanhar seus EcoPontos ou trocar por cupons de desconto no comércio local.</p>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-5">
            <a href="CRUD_Agendamentos/listar.php" class="rounded-lg border-t-4 border-green-800 bg-white px-5 py-[25px] text-center text-gray-800 no-underline shadow transition hover:-translate-y-1 hover:shadow-lg">
                <h3 class="my-[10px] text-lg text-green-800">Agendamentos</h3>
                <p class="m-0 text-[13px] text-gray-600">Solicitar e acompanhar coletas de materiais recicláveis</p>
            </a>

            <?php if ($usuario_perfil !== 'PARTICIPANTE'): ?>
                <a href="coleta.php" class="rounded-lg border-t-4 border-green-800 bg-white px-5 py-[25px] text-center text-gray-800 no-underline shadow transition hover:-translate-y-1 hover:shadow-lg">
                    <h3 class="my-[10px] text-lg text-green-800">Coletas</h3>
                    <p class="m-0 text-[13px] text-gray-600">Confirmar o peso coletado e creditar EcoPontos</p>
                </a>

                <a href="CRUD_Participantes/listar.php" class="rounded-lg border-t-4 border-green-800 bg-white px-5 py-[25px] text-center text-gray-800 no-underline shadow transition hover:-translate-y-1 hover:shadow-lg">
                    <h3 class="my-[10px] text-lg text-green-800">Participantes</h3>
                    <p class="m-0 text-[13px] text-gray-600">Cadastro, busca e edição de participantes</p>
                </a>
            <?php endif; ?>

            <a href="listarMateriais.php" class="rounded-lg border-t-4 border-green-800 bg-white px-5 py-[25px] text-center text-gray-800 no-underline shadow transition hover:-translate-y-1 hover:shadow-lg">
                <h3 class="my-[10px] text-lg text-green-800">Materiais</h3>
                <p class="m-0 text-[13px] text-gray-600">Veja quais materiais recicláveis são aceitos</p>
            </a>

            <a href="Logs/carteira.php" class="rounded-lg border-t-4 border-green-800 bg-white px-5 py-[25px] text-center text-gray-800 no-underline shadow transition hover:-translate-y-1 hover:shadow-lg">
                <h3 class="my-[10px] text-lg text-green-800">Carteira de EcoPontos</h3>
                <p class="m-0 text-[13px] text-gray-600">Consultar saldo e histórico de movimentações</p>
            </a>

            <a href="Logs/troca_cupom.php" class="rounded-lg border-t-4 border-green-800 bg-white px-5 py-[25px] text-center text-gray-800 no-underline shadow transition hover:-translate-y-1 hover:shadow-lg">
                <h3 class="my-[10px] text-lg text-green-800">Cupons</h3>
                <p class="m-0 text-[13px] text-gray-600">Catálogo de parceiros e troca de pontos por benefícios</p>
            </a>
        </div>
    </div>

    <footer class="mt-[50px] p-5 text-center text-xs text-gray-500">
        &copy; <?= date('Y') ?> EcoCiclo &mdash; Plataforma Socioambiental de Reciclagem
    </footer>

</body>
</html>