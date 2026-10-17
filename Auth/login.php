<?php
// Auth/login.php - Tela de login e validação de credenciais (RN02, RN03, RN16)
session_start();

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';

// Se já estiver logado, não precisa ver a tela de login
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

$mensagem_erro = '';
$email = '';

// Só processa quando o formulário for enviado (POST).
// Quando a página é aberta normalmente (GET), o PHP pula este bloco e mostra o formulário.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $mensagem_erro = "Preencha o e-mail e a senha.";
    } else {
        $usuarioModel = new Usuario($pdo);
        $usuario = $usuarioModel->buscarPorEmail($email);

        // Compara a senha digitada com o hash salvo no banco (coluna senha_hash)
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Gera um novo ID de sessão (evita fixação de sessão)
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            header("Location: ../index.php");
            exit();
        } else {
            // RN02: mensagem única, sem dizer se o erro foi no e-mail ou na senha
            $mensagem_erro = "Autenticação não realizada. Verifique os dados informados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EcoCiclo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-5 font-sans text-gray-800">

<div class="mx-auto my-20 max-w-[380px] rounded-lg bg-white p-[30px] shadow-md">
    <h2 class="mb-6 mt-0 text-center text-2xl font-bold text-green-800">EcoCiclo</h2>

    <?php if ($mensagem_erro !== ''): ?>
        <div class="mb-5 rounded border border-red-200 bg-red-100 px-[15px] py-3 text-sm text-red-900"><?= htmlspecialchars($mensagem_erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-[15px] flex flex-col">
            <label for="email" class="mb-1 text-[13px] font-bold text-gray-600">E-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <div class="mb-[15px] flex flex-col">
            <label for="senha" class="mb-1 text-[13px] font-bold text-gray-600">Senha</label>
            <input type="password" id="senha" name="senha" required class="rounded border border-gray-300 p-[10px] text-sm focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>

        <button type="submit" class="mt-[10px] w-full rounded bg-green-800 p-3 text-[15px] font-bold text-white hover:bg-green-900">Entrar</button>
    </form>
</div>

</body>
</html>