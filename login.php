<?php
session_start();
require 'conexao.php';

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = trim($_POST['email']);
  $senha = trim($_POST['senha']);

  // 1. ALTERAÇÃO AQUI: Incluindo 'nivel_acesso' na consulta
  $stmt = $conexao->prepare("SELECT id_usuario, nome, senha, nivel_acesso FROM usuario WHERE email=?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 1) {
    $user = $res->fetch_assoc();

    // Confere a senha (armazenada como hash no banco)
    if (password_verify($senha, $user['senha'])) {
      // Login válido → cria sessão
      $_SESSION['logado'] = true; // Boa prática: sempre defina um status de logado
      $_SESSION['usuario_id'] = $user['id_usuario'];
      $_SESSION['usuario_nome'] = $user['nome'];

      // 2. ALTERAÇÃO AQUI: Salvando o nível de acesso na sessão
      $_SESSION['nivel_acesso'] = $user['nivel_acesso'];

      header("Location: index.php");
      exit; // Adicionado exit para garantir que o redirecionamento funcione
    } else {
      $erro = "Senha incorreta!";
    }
  } else {
    $erro = "Usuário não encontrado ou inativo.";
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="src\style.css">
  <link rel="icon" href="src\book.svg" type="image/svg+xml">
</head>

<body class="bg-light">
  <?php include "header.php"; ?>

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h4>Login</h4>
          </div>
          <div class="card-body">
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>
            <form method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" id="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" name="senha" id="senha" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-success w-100">Entrar</button>
              <div class="text-center mt-3">
                <a href="cadastro.php" class="text-decoration-none">Cadastre-se aqui</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'footer.php'; ?>
</body>

</html>