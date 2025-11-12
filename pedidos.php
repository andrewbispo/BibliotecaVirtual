<?php
session_start();
require 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$nivel_acesso = $_SESSION['nivel_acesso'] ?? 0;

// Atualizar status do pedido (apenas admin)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['atualizar_status']) && $nivel_acesso == 1) {
    $idPedido = $_POST['idPedido'];
    $novoStatus = $_POST['statusRetirada'];
    $statusAnterior = $_POST['statusAnterior'];

    $conexao->begin_transaction();

    try {
        // Busca informações do pedido
        $stmt = $conexao->prepare("SELECT idLivro FROM pedido WHERE idPedido = ?");
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pedido) {
            throw new Exception("Pedido não encontrado.");
        }

        $idLivro = $pedido['idLivro'];

        // Atualiza o status
        $stmt = $conexao->prepare("UPDATE pedido SET statusRetirada = ? WHERE idPedido = ?");
        $stmt->bind_param("si", $novoStatus, $idPedido);
        $stmt->execute();
        $stmt->close();

        // Gerencia o estoque conforme o status
        // Não mexe no estoque quando status é Pendente
        // Retirado: decrementa (mas só quando vem de Pendente)
        if ($statusAnterior === 'Pendente' && $novoStatus === 'Retirado') {
            $stmt = $conexao->prepare("
                UPDATE estoque 
                SET quantidadeEstoque = GREATEST(0, quantidadeEstoque - 1)
                WHERE idLivro = ?
            ");
            $stmt->bind_param("i", $idLivro);
            $stmt->execute();
            $stmt->close();
        }

        // Devolvido: incrementa (mas só quando vem de Retirado)
        if ($statusAnterior === 'Retirado' && $novoStatus === 'Devolvido') {
            $stmt = $conexao->prepare("
                INSERT INTO estoque (idLivro, quantidadeEstoque) 
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE quantidadeEstoque = quantidadeEstoque + 1
            ");
            $stmt->bind_param("i", $idLivro);
            $stmt->execute();
            $stmt->close();
        }

        // Cancelado: devolve ao estoque se estava Retirado
        if ($statusAnterior === 'Retirado' && $novoStatus === 'Cancelado') {
            $stmt = $conexao->prepare("
                UPDATE estoque 
                SET quantidadeEstoque = quantidadeEstoque + 1 
                WHERE idLivro = ?
            ");
            $stmt->bind_param("i", $idLivro);
            $stmt->execute();
            $stmt->close();
        }

        $conexao->commit();
        $_SESSION['alerta_tipo'] = 'success';
        $_SESSION['alerta_mensagem'] = 'Status atualizado com sucesso!';

    } catch (Exception $e) {
        $conexao->rollback();
        $_SESSION['alerta_tipo'] = 'danger';
        $_SESSION['alerta_mensagem'] = 'Erro: ' . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Cancelar pedido (usuário comum, apenas se Pendente)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancelar_pedido'])) {
    $idPedido = $_POST['idPedido'];

    $stmt = $conexao->prepare("
        UPDATE pedido 
        SET statusRetirada = 'Cancelado' 
        WHERE idPedido = ? AND id_usuario = ? AND statusRetirada = 'Pendente'
    ");
    $stmt->bind_param("ii", $idPedido, $id_usuario);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['alerta_tipo'] = 'warning';
        $_SESSION['alerta_mensagem'] = 'Pedido cancelado com sucesso.';
    } else {
        $_SESSION['alerta_tipo'] = 'danger';
        $_SESSION['alerta_mensagem'] = 'Não foi possível cancelar o pedido.';
    }
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Filtros de pesquisa
$filtroStatus = $_GET['status'] ?? '';
$filtroBusca = $_GET['busca'] ?? '';

// Buscar pedidos
if ($nivel_acesso == 1) {
    // Admin vê todos os pedidos
    $where = "WHERE 1=1";

    if (!empty($filtroStatus)) {
        $where .= " AND p.statusRetirada = '" . $conexao->real_escape_string($filtroStatus) . "'";
    }

    if (!empty($filtroBusca)) {
        $busca = $conexao->real_escape_string($filtroBusca);
        $where .= " AND (a.titulo LIKE '%$busca%' OR u.nome LIKE '%$busca%' OR a.tombo LIKE '%$busca%')";
    }

    $sql = "
        SELECT p.*, a.titulo, a.autor, a.tombo, u.nome as nome_usuario
        FROM pedido p
        INNER JOIN acervo a ON p.idLivro = a.id
        INNER JOIN usuario u ON p.id_usuario = u.id_usuario
        $where
        ORDER BY 
            FIELD(p.statusRetirada, 'Pendente', 'Retirado', 'Devolvido', 'Cancelado'),
            p.dataPedido DESC
    ";
    $result = $conexao->query($sql);
} else {
    // Usuário comum vê apenas seus pedidos
    $where = "WHERE p.id_usuario = ?";

    if (!empty($filtroStatus)) {
        $where .= " AND p.statusRetirada = ?";
    }

    $sql = "
        SELECT p.*, a.titulo, a.autor, a.tombo
        FROM pedido p
        INNER JOIN acervo a ON p.idLivro = a.id
        $where
        ORDER BY p.dataPedido DESC
    ";

    $stmt = $conexao->prepare($sql);

    if (!empty($filtroStatus)) {
        $stmt->bind_param("is", $id_usuario, $filtroStatus);
    } else {
        $stmt->bind_param("i", $id_usuario);
    }

    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Pedidos - Biblioteca Bethel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="src/style.css">
    <link rel="icon" href="src\book.svg" type="image/svg+xml">
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>

    <div class="container mt-5 mb-5">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-clipboard-check me-2"></i>
                <?= $nivel_acesso == 1 ? 'Gerenciar Todos os Pedidos' : 'Meus Pedidos' ?>
            </h2>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao Catálogo
            </a>
        </div>

        <!-- Alertas -->
        <?php if (isset($_SESSION['alerta_mensagem'])): ?>
                <div class="alert alert-<?= $_SESSION['alerta_tipo'] ?> alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-<?= $_SESSION['alerta_tipo'] == 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-3 fs-4"></i>
                    <div>
                        <?= $_SESSION['alerta_mensagem'] ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
                unset($_SESSION['alerta_mensagem']);
                unset($_SESSION['alerta_tipo']);
                ?>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <?php if ($nivel_acesso == 1): ?>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="busca" class="form-control" 
                                       placeholder="Buscar por livro, usuário ou tombo..."
                                       value="<?= htmlspecialchars($filtroBusca) ?>">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-<?= $nivel_acesso == 1 ? '4' : '8' ?>">
                        <select name="status" class="form-select">
                            <option value="">Todos os status</option>
                            <option value="Pendente" <?= $filtroStatus === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Retirado" <?= $filtroStatus === 'Retirado' ? 'selected' : '' ?>>Retirado</option>
                            <option value="Devolvido" <?= $filtroStatus === 'Devolvido' ? 'selected' : '' ?>>Devolvido</option>
                            <option value="Cancelado" <?= $filtroStatus === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                    </div>
                    
                    <?php if (!empty($filtroStatus) || !empty($filtroBusca)): ?>
                        <div class="col-12">
                            <a href="pedidos.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i>Limpar filtros
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill me-3 fs-3"></i>
                    <div>
                        <h5 class="alert-heading">Nenhum pedido encontrado</h5>
                        <p class="mb-0">
                            <?php if (!empty($filtroStatus) || !empty($filtroBusca)): ?>
                                    Não há pedidos com os filtros selecionados.
                            <?php else: ?>
                                    Você ainda não realizou nenhum pedido.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
        <?php else: ?>
                <div class="row g-3">
                    <?php while ($pedido = $result->fetch_assoc()): ?>
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <!-- Informações do Pedido -->
                                            <div class="col-lg-7">
                                                <h5 class="card-title mb-2">
                                                    <?= htmlspecialchars($pedido['titulo']) ?>
                                                </h5>
                                        
                                                <div class="row g-2 small text-muted">
                                                    <?php if ($nivel_acesso == 1): ?>
                                                        <div class="col-md-6">
                                                            <i class="bi bi-person me-1"></i>
                                                            <strong>Usuário:</strong> <?= htmlspecialchars($pedido['nome_usuario']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                            
                                                    <div class="col-md-6">
                                                        <i class="bi bi-person-badge me-1"></i>
                                                        <strong>Autor:</strong> <?= htmlspecialchars($pedido['autor']) ?>
                                                    </div>
                                            
                                                    <div class="col-md-6">
                                                        <i class="bi bi-hash me-1"></i>
                                                        <strong>Tombo:</strong> <?= htmlspecialchars($pedido['tombo']) ?>
                                                    </div>
                                            
                                                    <div class="col-md-6">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        <strong>Pedido em:</strong> <?= date('d/m/Y H:i', strtotime($pedido['dataPedido'])) ?>
                                                    </div>
                                            
                                                    <div class="col-md-6">
                                                        <i class="bi bi-calendar-event me-1"></i>
                                                        <strong>Retirada:</strong> <?= date('d/m/Y', strtotime($pedido['dataRetirada'])) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status e Ações -->
                                            <div class="col-lg-5">
                                                <div class="text-lg-end">
                                                    <?php
                                                    $badges = [
                                                        'Pendente' => 'warning',
                                                        'Retirado' => 'primary',
                                                        'Devolvido' => 'success',
                                                        'Cancelado' => 'danger'
                                                    ];
                                                    $icons = [
                                                        'Pendente' => 'clock',
                                                        'Retirado' => 'book',
                                                        'Devolvido' => 'check-circle',
                                                        'Cancelado' => 'x-circle'
                                                    ];
                                                    $cor = $badges[$pedido['statusRetirada']] ?? 'secondary';
                                                    $icon = $icons[$pedido['statusRetirada']] ?? 'circle';
                                                    ?>
                                            
                                                    <span class="badge bg-<?= $cor ?> fs-6 mb-3">
                                                        <i class="bi bi-<?= $icon ?> me-1"></i>
                                                        <?= $pedido['statusRetirada'] ?>
                                                    </span>

                                                    <!-- Ações Admin -->
                                                    <?php if ($nivel_acesso == 1): ?>
                                                            <form method="POST" class="d-flex gap-2 justify-content-lg-end flex-wrap">
                                                                <input type="hidden" name="idPedido" value="<?= $pedido['idPedido'] ?>">
                                                                <input type="hidden" name="statusAnterior" value="<?= $pedido['statusRetirada'] ?>">
                                                    
                                                                <select name="statusRetirada" class="form-select form-select-sm" style="max-width: 150px;">
                                                                    <option value="Pendente" <?= $pedido['statusRetirada'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                                                    <option value="Retirado" <?= $pedido['statusRetirada'] === 'Retirado' ? 'selected' : '' ?>>Retirado</option>
                                                                    <option value="Devolvido" <?= $pedido['statusRetirada'] === 'Devolvido' ? 'selected' : '' ?>>Devolvido</option>
                                                                    <option value="Cancelado" <?= $pedido['statusRetirada'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                                                </select>
                                                    
                                                                <button type="submit" name="atualizar_status" class="btn btn-sm btn-primary">
                                                                    <i class="bi bi-check-lg"></i> Atualizar
                                                                </button>
                                                            </form>
                                                    <?php else: ?>
                                                            <!-- Ações Usuário -->
                                                            <?php if ($pedido['statusRetirada'] === 'Pendente'): ?>
                                                                    <form method="POST" class="d-inline"
                                                                        onsubmit="return confirm('Deseja realmente cancelar este pedido?');">
                                                                        <input type="hidden" name="idPedido" value="<?= $pedido['idPedido'] ?>">
                                                                        <button type="submit" name="cancelar_pedido" class="btn btn-sm btn-danger">
                                                                            <i class="bi bi-x-circle me-1"></i>Cancelar Pedido
                                                                        </button>
                                                                    </form>
                                                            <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php endwhile; ?>
                </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conexao->close();
?>