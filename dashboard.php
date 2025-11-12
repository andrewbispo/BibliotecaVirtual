<?php
session_start();
require 'conexao.php';

// Verifica se é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] != 1) {
    header("Location: index.php");
    exit;
}

// Estatísticas Gerais
$stats = [];

$sql = "
SELECT
    (SELECT COUNT(*) FROM acervo) AS total_livros,
    (SELECT COUNT(*) FROM usuario) AS total_usuarios,
    (SELECT COUNT(*) FROM pedido) AS total_pedidos,
    (SELECT COUNT(*) FROM pedido WHERE statusRetirada = 'Pendente') AS pedidos_pendentes,
    (SELECT COUNT(*) FROM pedido WHERE statusRetirada = 'Retirado') AS livros_circulacao
";
$result = $conexao->query($sql);
$stats = $result->fetch_assoc();
// Estoque total disponível
$result = $conexao->query("
    SELECT SUM(
        GREATEST(0, COALESCE(e.quantidadeEstoque, a.quantidadeAcervo) - 
        (SELECT COUNT(*) FROM pedido 
         WHERE idLivro = a.id 
         AND statusRetirada IN ('Pendente', 'Retirado')))
    ) as total
    FROM acervo a
    LEFT JOIN estoque e ON a.id = e.idLivro
");
$stats['estoque_disponivel'] = $result->fetch_assoc()['total'];

// Taxa de ocupação
$stats['taxa_ocupacao'] = $stats['total_livros'] > 0
    ? round(($stats['livros_circulacao'] / $stats['total_livros']) * 100, 2)
    : 0;

// Livros mais populares (top 5)
$livros_populares = [];
$result = $conexao->query("
    SELECT a.titulo, a.autor, COUNT(p.idPedido) as total_alugueis
    FROM acervo a
    INNER JOIN pedido p ON a.id = p.idLivro
    GROUP BY a.id, a.titulo, a.autor
    ORDER BY total_alugueis DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $livros_populares[] = $row;
}

// Usuários mais ativos (top 5)
$usuarios_ativos = [];
$result = $conexao->query("
    SELECT u.nome, COUNT(p.idPedido) as total_pedidos
    FROM usuario u
    INNER JOIN pedido p ON u.id_usuario = p.id_usuario
    GROUP BY u.id_usuario, u.nome
    ORDER BY total_pedidos DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $usuarios_ativos[] = $row;
}

// Pedidos recentes
$pedidos_recentes = [];
$result = $conexao->query("
    SELECT p.*, a.titulo, u.nome as nome_usuario
    FROM pedido p
    INNER JOIN acervo a ON p.idLivro = a.id
    INNER JOIN usuario u ON p.id_usuario = u.id_usuario
    ORDER BY p.dataPedido DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $pedidos_recentes[] = $row;
}

// Estatísticas por mês (últimos 6 meses)
$estatisticas_mensais = [];
$result = $conexao->query("
    SELECT 
        DATE_FORMAT(dataPedido, '%Y-%m') as mes,
        COUNT(*) as total_pedidos
    FROM pedido
    WHERE dataPedido >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(dataPedido, '%Y-%m')
    ORDER BY mes DESC
");
while ($row = $result->fetch_assoc()) {
    $estatisticas_mensais[] = $row;
}

// Livros nunca alugados
$result = $conexao->query("
    SELECT COUNT(*) as total
    FROM acervo a
    LEFT JOIN pedido p ON a.id = p.idLivro
    WHERE p.idPedido IS NULL
");
$stats['livros_nunca_alugados'] = $result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Administrativo - Biblioteca Bethel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="src/style.css">
    <link rel="icon" href="src\book.svg" type="image/svg+xml">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>

    <div class="container-fluid mt-4 mb-5">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-3">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard Administrativo
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row g-3 mb-4">
            <!-- Total de Livros -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total de Livros</h6>
                                <h2 class="mb-0 fw-bold"><?= number_format($stats['total_livros']) ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-book fs-1"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            <?= number_format($stats['livros_nunca_alugados']) ?> nunca alugados
                        </small>
                    </div>
                </div>
            </div>

            <!-- Total de Usuários -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total de Usuários</h6>
                                <h2 class="mb-0 fw-bold"><?= number_format($stats['total_usuarios']) ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-people fs-1"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-person-check me-1"></i>
                            Usuários cadastrados
                        </small>
                    </div>
                </div>
            </div>

            <!-- Livros em Circulação -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Em Circulação</h6>
                                <h2 class="mb-0 fw-bold"><?= number_format($stats['livros_circulacao']) ?></h2>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-arrow-repeat fs-1"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-graph-up me-1"></i>
                            Taxa de ocupação: <?= $stats['taxa_ocupacao'] ?>%
                        </small>
                    </div>
                </div>
            </div>

            <!-- Pedidos Pendentes -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Pedidos Pendentes</h6>
                                <h2 class="mb-0 fw-bold"><?= number_format($stats['pedidos_pendentes']) ?></h2>
                            </div>
                            <div class="text-danger">
                                <i class="bi bi-clock fs-1"></i>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Aguardando retirada
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row g-3 mb-4">
            <!-- Gráfico de Pedidos por Mês -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart me-2"></i>
                            Pedidos nos Últimos 6 Meses
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartPedidosMensais" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Rápidas -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-pie-chart me-2"></i>
                            Estatísticas Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Total de Pedidos</span>
                                <span class="badge bg-primary rounded-pill"><?= $stats['total_pedidos'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Estoque Disponível</span>
                                <span class="badge bg-success rounded-pill"><?= $stats['estoque_disponivel'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Taxa de Ocupação</span>
                                <span class="badge bg-warning rounded-pill"><?= $stats['taxa_ocupacao'] ?>%</span>
                            </li>
                        </ul>

                        <div class="mt-4">
                            <h6 class="mb-3">Ações Rápidas</h6>
                            <div class="d-grid gap-2">
                                <a href="gerenciar.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i>Adicionar Livro
                                </a>
                                <a href="pedidos.php" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-list-check me-1"></i>Ver Todos os Pedidos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabelas -->
        <div class="row g-3 mb-4">
            <!-- Livros Mais Populares -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-star me-2"></i>
                            Top 5 Livros Mais Alugados
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Título</th>
                                        <th>Autor</th>
                                        <th class="text-end pe-3">Aluguéis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($livros_populares)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                Nenhum dado disponível
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($livros_populares as $index => $livro): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold"><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($livro['titulo']) ?></td>
                                                <td class="text-muted"><?= htmlspecialchars($livro['autor']) ?></td>
                                                <td class="text-end pe-3">
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?= $livro['total_alugueis'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuários Mais Ativos -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-trophy me-2"></i>
                            Top 5 Usuários Mais Ativos
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Nome</th>
                                        <th class="text-end pe-3">Pedidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios_ativos)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                Nenhum dado disponível
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios_ativos as $index => $usuario): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold"><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                                <td class="text-end pe-3">
                                                    <span class="badge bg-success rounded-pill">
                                                        <?= $usuario['total_pedidos'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pedidos Recentes -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Pedidos Recentes
                        </h5>
                        <a href="pedidos.php" class="btn btn-sm btn-outline-primary">
                            Ver todos <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>Usuário</th>
                                        <th>Livro</th>
                                        <th>Data Pedido</th>
                                        <th>Data Retirada</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pedidos_recentes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                Nenhum pedido encontrado
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pedidos_recentes as $pedido): ?>
                                            <tr>
                                                <td class="ps-3">#<?= $pedido['idPedido'] ?></td>
                                                <td><?= htmlspecialchars($pedido['nome_usuario']) ?></td>
                                                <td><?= htmlspecialchars($pedido['titulo']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($pedido['dataPedido'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($pedido['dataRetirada'])) ?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $badges = [
                                                        'Pendente' => 'warning',
                                                        'Retirado' => 'primary',
                                                        'Devolvido' => 'success',
                                                        'Cancelado' => 'danger'
                                                    ];
                                                    $cor = $badges[$pedido['statusRetirada']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $cor ?>">
                                                        <?= $pedido['statusRetirada'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script do Gráfico -->
    <script>
        // Dados do PHP para JavaScript
        const estatisticasMensais = <?= json_encode(array_reverse($estatisticas_mensais)) ?>;

        // Preparar dados para o gráfico
        const labels = estatisticasMensais.map(item => {
            const [ano, mes] = item.mes.split('-');
            const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return `${meses[parseInt(mes) - 1]}/${ano}`;
        });

        const data = estatisticasMensais.map(item => item.total_pedidos);

        // Criar gráfico
        const ctx = document.getElementById('chartPedidosMensais');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pedidos',
                    data: data,
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

    </script>
</body>

</html>

<?php
$conexao->close();
?>