<?php
// Garante que a sessão seja iniciada antes de usar $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center me-5" href="index.php">
                <i class="bi bi-book me-2"></i>
                Biblioteca Bethel
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Alternar navegação">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <?php if (isset($_SESSION['usuario_nome']) && !empty($_SESSION['usuario_nome'])): ?>
                        <!-- Informação do usuário -->
                        <li class="nav-item d-lg-flex align-items-center me-4">
                            <span class="text-light px-3 py-2 rounded bg-secondary bg-opacity-25">
                                <i class="bi bi-person-circle me-2"></i>
                                <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>
                            </span>
                        </li>

                        <!-- Meus Pedidos -->
                        <li class="nav-item">
                            <a href="pedidos.php" class="btn btn-info text-white">
                                <i class="bi bi-clipboard-check me-1"></i>
                                Meus Pedidos
                            </a>
                        </li>

                        <!-- Gerenciar Acervo (apenas admin) -->
                        <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 1): ?>
                            <li class="nav-item">
                                <a class="btn btn-warning" href="gerenciar.php">
                                    <i class="bi bi-gear-fill me-1"></i>
                                    Gerenciar Livros
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 1): ?>
                            <a href="dashboard.php" class="btn btn-dark">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        <?php endif; ?>

                        <!-- Sair -->
                        <li class="nav-item">
                            <a href="logout.php" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right me-1"></i>
                                Sair
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Links para usuários não logados -->
                        <li class="nav-item">
                            <a class="btn btn-outline-light" href="cadastro.php">
                                <i class="bi bi-person-plus me-1"></i>
                                Cadastrar-se
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="btn btn-success" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                Entrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- Adicionar Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">