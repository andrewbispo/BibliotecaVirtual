<?php
session_start();
require 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = "";
$idLivro = $_GET['idLivro'] ?? $_GET['id'] ?? null;

// Buscar informações do livro com estoque REAL disponível
$livro = null;
if ($idLivro) {
    $stmt = $conexao->prepare("
        SELECT a.*, 
               COALESCE(e.quantidadeEstoque, a.quantidadeAcervo) as estoque_total,
               (SELECT COUNT(*) FROM pedido 
                WHERE idLivro = a.id 
                AND statusRetirada IN ('Pendente', 'Retirado')) as pedidos_ativos
        FROM acervo a
        LEFT JOIN estoque e ON a.id = e.idLivro
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $idLivro);
    $stmt->execute();
    $result = $stmt->get_result();
    $livro = $result->fetch_assoc();

    if ($livro) {
        // Calcula o estoque REAL disponível
        $livro['estoque_disponivel'] = max(0, $livro['estoque_total'] - $livro['pedidos_ativos']);
    }

    $stmt->close();
}

// Processar o aluguel
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirmar_aluguel'])) {
    $dataRetirada = $_POST['dataRetirada'] ?? '';
    $id_usuario = $_SESSION['usuario_id'];

    // Validações
    $dataAtual = date('Y-m-d');
    $dataMinima = date('Y-m-d', strtotime('+1 day'));

    if (empty($dataRetirada)) {
        $mensagem = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>Selecione uma data de retirada.</div>";
    } elseif ($dataRetirada < $dataMinima) {
        $mensagem = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>A data de retirada deve ser pelo menos 1 dia após hoje.</div>";
    } elseif (!$livro || $livro['estoque_disponivel'] <= 0) {
        $mensagem = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>Livro indisponível no momento.</div>";
    } else {
        // Verifica novamente o estoque antes de inserir (segurança extra)
        $stmt = $conexao->prepare("
            SELECT 
                COALESCE(e.quantidadeEstoque, a.quantidadeAcervo) as estoque_total,
                (SELECT COUNT(*) FROM pedido 
                 WHERE idLivro = a.id 
                 AND statusRetirada IN ('Pendente', 'Retirado')) as pedidos_ativos
            FROM acervo a
            LEFT JOIN estoque e ON a.id = e.idLivro
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $idLivro);
        $stmt->execute();
        $verifica = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $estoque_real = max(0, $verifica['estoque_total'] - $verifica['pedidos_ativos']);

        if ($estoque_real <= 0) {
            $mensagem = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>Este livro acabou de ser reservado. Tente novamente mais tarde.</div>";
        } else {
            // Insere o pedido
            $conexao->begin_transaction();

            try {
                $stmt = $conexao->prepare("
                    INSERT INTO pedido (id_usuario, idLivro, dataRetirada, statusRetirada, dataPedido)
                    VALUES (?, ?, ?, 'Pendente', NOW())
                ");
                $stmt->bind_param("iis", $id_usuario, $idLivro, $dataRetirada);

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao criar pedido.");
                }
                $stmt->close();

                $conexao->commit();
                $_SESSION['alerta_tipo'] = 'success';
                $_SESSION['alerta_mensagem'] = 'Pedido de aluguel realizado com sucesso! Retire o livro na data agendada.';
                header("Location: pedidos.php");
                exit;

            } catch (Exception $e) {
                $conexao->rollback();
                $mensagem = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>Erro: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alugar Livro - Biblioteca Bethel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="src/style.css">
    <link rel="icon" href="src\book.svg" type="image/svg+xml">
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>

    <div class="container mt-5 mb-5">
        <?php if (!$livro): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-3"></i>
                <div>
                    <h4 class="alert-heading">Livro não encontrado</h4>
                    <p class="mb-0">O livro solicitado não existe ou foi removido do catálogo.</p>
                    <hr>
                    <a href="index.php" class="btn btn-primary mt-2">
                        <i class="bi bi-arrow-left me-2"></i>Voltar ao Catálogo
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Imagem do Livro -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <img src="<?= htmlspecialchars($livro['foto_capa'] ?? 'img/capa_padrao.jpg') ?>"
                            class="card-img-top" alt="Capa do livro"
                            style="height: 500px; object-fit: cover; border-radius: 0.5rem 0.5rem 0 0;">
                    </div>
                </div>

                <!-- Informações e Formulário -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white py-3">
                            <h4 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-book me-2"></i>
                                Alugar Livro
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <?= $mensagem ?>

                            <h5 class="card-title mb-3"><?= htmlspecialchars($livro['titulo']) ?></h5>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong><i class="bi bi-person me-2"></i>Autor:</strong>
                                        <?= htmlspecialchars($livro['autor']) ?></p>
                                    <p class="mb-2"><strong><i class="bi bi-building me-2"></i>Editora:</strong>
                                        <?= htmlspecialchars($livro['editora']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong><i class="bi bi-tag me-2"></i>Gênero:</strong>
                                        <?= htmlspecialchars($livro['genero'] ?? 'Não informado') ?></p>
                                    <p class="mb-2"><strong><i class="bi bi-hash me-2"></i>Tombo:</strong>
                                        <?= htmlspecialchars($livro['tombo']) ?></p>
                                </div>
                            </div>

                            <?php if ($livro['estoque_disponivel'] > 0): ?>
                                <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                    <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                    <div>
                                        <strong>Disponível!</strong><br>
                                        <?= $livro['estoque_disponivel'] ?>
                                        <?= $livro['estoque_disponivel'] == 1 ? 'exemplar disponível' : 'exemplares disponíveis' ?>
                                    </div>
                                </div>

                                <form method="POST">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">
                                            <i class="bi bi-calendar-event me-2"></i>Data de Retirada
                                        </label>
                                        <input type="date" name="dataRetirada" class="form-control form-control-lg"
                                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            A retirada deve ser agendada para pelo menos 1 dia após hoje.
                                        </small>
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" name="confirmar_aluguel"
                                            class="btn btn-success btn-lg flex-grow-1">
                                            <i class="bi bi-check-circle me-2"></i>Confirmar Aluguel
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                            <i class="bi bi-x-circle me-2"></i>Cancelar
                                        </a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                    <i class="bi bi-x-circle-fill me-3 fs-4"></i>
                                    <div>
                                        <strong>Indisponível</strong><br>
                                        Este livro não possui exemplares disponíveis no momento.
                                    </div>
                                </div>
                                <a href="index.php" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar ao Catálogo
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>