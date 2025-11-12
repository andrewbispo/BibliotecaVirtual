<?php
require_once 'conexao.php';

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');

    // Validação sequencial
    if ($nome === "" || $email === "" || $senha === "" || $confirmar === "" || $cpf === "") {
        $mensagem = "<div class='alert alert-danger'>Preencha todos os campos.</div>";
    } elseif (!preg_match('/^\d{11}$/', $cpf)) {
        $mensagem = "<div class='alert alert-danger'>CPF inválido. Deve conter exatamente 11 dígitos numéricos.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "<div class='alert alert-danger'>E-mail inválido.</div>";
    } elseif ($senha !== $confirmar) {
        $mensagem = "<div class='alert alert-danger'>As senhas não conferem.</div>";
    } else {
        // Verificação de duplicidade
        $sql_check = "SELECT email, cpf FROM usuario WHERE email = ? OR cpf = ?";
        $stmt_check = $conexao->prepare($sql_check);

        if ($stmt_check === false) {
            $mensagem = "<div class='alert alert-danger'>Erro (Prepare Verificação): {$conexao->error}</div>";
        } else {
            $stmt_check->bind_param("ss", $email, $cpf);
            $stmt_check->execute();
            $resultado_check = $stmt_check->get_result();
            $stmt_check->close();

            if ($resultado_check->num_rows > 0) {
                $row = $resultado_check->fetch_assoc();
                if ($row['email'] === $email) {
                    $mensagem = "<div class='alert alert-warning'>E-mail já cadastrado.</div>";
                } else {
                    $mensagem = "<div class='alert alert-warning'>CPF já cadastrado.</div>";
                }
            } else {
                // Inserção com transação
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $conexao->begin_transaction();

                try {
                    $sql_insert = "INSERT INTO usuario (nome, email, senha, cpf, ativo) VALUES (?, ?, ?, ?, 1)";
                    $stmt_insert = $conexao->prepare($sql_insert);

                    if ($stmt_insert === false) {
                        throw new Exception("Erro (Prepare Inserção): {$conexao->error}");
                    }

                    $stmt_insert->bind_param("ssss", $nome, $email, $hash, $cpf);

                    if (!$stmt_insert->execute()) {
                        throw new Exception("Erro ao cadastrar: {$stmt_insert->error}");
                    }

                    $conexao->commit();
                    $mensagem = "<div class='alert alert-success'>Usuário cadastrado com sucesso! <a href='login.php'>Fazer login</a></div>";
                    $stmt_insert->close();
                } catch (Exception $e) {
                    $conexao->rollback();
                    if ($conexao->errno === 1062) {
                        if (strpos($conexao->error, 'cpf') !== false) {
                            $mensagem = "<div class='alert alert-warning'>CPF já cadastrado.</div>";
                        } elseif (strpos($conexao->error, 'email') !== false) {
                            $mensagem = "<div class='alert alert-warning'>E-mail já cadastrado.</div>";
                        } else {
                            $mensagem = "<div class='alert alert-danger'>Erro de duplicidade: {$conexao->error}</div>";
                        }
                    } else {
                        $mensagem = "<div class='alert alert-danger'>{$e->getMessage()}</div>";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Criar Conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src\style.css">
    <link rel="icon" href="src\book.svg" type="image">
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h4>Criar sua Conta</h4>
                    </div>
                    <div class="card-body">
                        <?= $mensagem ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Nome completo</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CPF</label>
                                <input type="cpf" name="cpf" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" name="senha" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Senha</label>
                                <input type="password" name="confirmar" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-sage w-100">Cadastrar</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="login.php" class="text-decoration-none">Já tem uma conta? Faça login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>