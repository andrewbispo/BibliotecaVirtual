<?php
include 'conexao.php';

if (!isset($_GET['id'])) {
    die("Livro não especificado.");
}

$idLivro = intval($_GET['id']);

// Buscar informações do livro com estoque REAL disponível
$livro = null;
if ($idLivro) {
    $stmt = $conexao->prepare("
        SELECT a.*, 
               COALESCE(e.quantidadeEstoque, a.quantidadeAcervo) AS estoque_total,
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
        $livro['estoque_disponivel'] = max(0, $livro['estoque_total'] - $livro['pedidos_ativos']);
    }

    $stmt->close();
}

if (!$livro) {
    die("Livro não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($livro['titulo']); ?> - Biblioteca Bethel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="src/style.css" rel="stylesheet">
    <link rel="icon" href="src\book.svg" type="image/svg+xml">
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Início</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($livro['titulo']); ?>
                </li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm">
            <div class="row g-0">
                <!-- Imagem do livro -->
                <div class="col-lg-4 bg-light d-flex justify-content-center align-items-center p-4">
                    <div class="position-relative w-100" style="max-width: 400px;">
                        <img src="<?php echo htmlspecialchars($livro['foto_capa'] ?? 'img/capa_padrao.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($livro['titulo']); ?>" class="img-fluid rounded shadow"
                            style="width: 100%; object-fit: contain; aspect-ratio: 2/3;">

                        <!-- Badge de status na imagem -->
                        <span
                            class="position-absolute top-0 end-0 m-3 badge <?= $livro['estoque_disponivel'] > 0 ? 'bg-success' : 'bg-danger' ?> fs-6">
                            <i
                                class="bi <?= $livro['estoque_disponivel'] > 0 ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                            <?= $livro['estoque_disponivel'] > 0 ? 'Disponível' : 'Indisponível' ?>
                        </span>
                    </div>
                </div>

                <!-- Informações do livro -->
                <div class="col-lg-8">
                    <div class="card-body p-4 p-lg-5">
                        <h1 class="card-title fw-bold mb-4 display-6">
                            <?php echo htmlspecialchars($livro['titulo']); ?>
                        </h1>

                        <!-- Informações básicas em cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-person me-1"></i>Autor
                                    </small>
                                    <strong><?php echo htmlspecialchars($livro['autor']); ?></strong>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-building me-1"></i>Editora
                                    </small>
                                    <strong><?php echo htmlspecialchars($livro['editora']); ?></strong>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-tag me-1"></i>Gênero
                                    </small>
                                    <strong><?php echo htmlspecialchars($livro['genero'] ?? 'Não informado'); ?></strong>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-calendar3 me-1"></i>Ano de Publicação
                                    </small>
                                    <strong><?php echo htmlspecialchars($livro['dataPublicacao'] ?? 'Não informado'); ?></strong>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-hash me-1"></i>Tombo
                                    </small>
                                    <strong><?php echo htmlspecialchars($livro['tombo']); ?></strong>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div
                                    class="p-3 <?= $livro['estoque_disponivel'] > 0 ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-box-seam me-1"></i>Estoque Disponível
                                    </small>
                                    <strong
                                        class="<?= $livro['estoque_disponivel'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?php echo $livro['estoque_disponivel']; ?>
                                        <?= $livro['estoque_disponivel'] == 1 ? 'exemplar' : 'exemplares' ?>
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <?php if (!empty($livro['descricao'])): ?>
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="bi bi-info-circle me-2"></i>Sobre o livro
                                    </h5>
                                    <p class="card-text text-secondary lh-lg">
                                        <?php echo nl2br(htmlspecialchars($livro['descricao'])); ?>
                                    </p>
                                </div>
                        <?php endif; ?>

                        <!-- Botões de ação -->
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <?php if ($livro['estoque_disponivel'] > 0): ?>
                                    <?php if (isset($_SESSION['usuario_id'])): ?>
                                            <a href="alugar.php?idLivro=<?php echo $livro['id']; ?>"
                                                class="btn btn-success btn-lg flex-grow-1">
                                                <i class="bi bi-bookmark-check me-2"></i>Alugar este livro
                                            </a>
                                    <?php else: ?>
                                            <a href="login.php" class="btn btn-primary btn-lg flex-grow-1">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar para alugar
                                            </a>
                                    <?php endif; ?>
                            <?php else: ?>
                                    <button class="btn btn-secondary btn-lg flex-grow-1" disabled>
                                        <i class="bi bi-x-circle me-2"></i>Sem estoque disponível
                                    </button>
                            <?php endif; ?>

                            <a href="index.php" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>