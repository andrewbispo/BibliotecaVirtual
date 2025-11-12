<?php
session_start();
session_unset();
// Se o PHP estiver usando cookies de sessão, destrói o cookie também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destrói completamente a sessão
session_destroy();

// Evita cache do navegador (garante que a página não volte pelo botão “Voltar”)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Exibe o alerta e redireciona
echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <script>
        alert('Você saiu da conta com sucesso.');
        window.location.href = 'login.php';
    </script>
</head>
<body></body>
</html>";
exit;
