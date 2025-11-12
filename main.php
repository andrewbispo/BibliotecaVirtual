<?php
// =======================================================
// LÓGICA PHP/SQL PARA O CATÁLOGO - VERSÃO RESPONSIVA
// =======================================================

// 1. Coleta de parâmetros de busca (filtro)
$busca = $_GET['busca'] ?? '';
$genero = $_GET['genero'] ?? '';

// Consulta principal com JOIN para pegar estoque
$sql = "SELECT DISTINCT 
            a.id, a.titulo, a.autor, a.genero, a.dataPublicacao, 
            a.descricao, a.foto_capa, a.quantidadeAcervo, a.tombo,
            COALESCE(e.quantidadeEstoque, a.quantidadeAcervo) as estoque_disponivel
        FROM acervo a
        LEFT JOIN estoque e ON a.id = e.idLivro
        WHERE 1=1";

$parametros = [];
$tipos = '';

// Adiciona filtro por título ou autor
if (!empty($busca)) {
    $sql .= " AND (a.titulo LIKE ? OR a.autor LIKE ?)";
    $busca_param = "%" . $busca . "%";
    $parametros[] = $busca_param;
    $parametros[] = $busca_param;
    $tipos .= 'ss';
}

// Adiciona filtro por gênero
if (!empty($genero)) {
    $sql .= " AND a.genero = ?";
    $parametros[] = $genero;
    $tipos .= 's';
}

// Adiciona ordenação
$sql .= " ORDER BY a.titulo ASC";

// Preparação e execução da consulta
$livros = [];

if (isset($conexao) && $stmt = $conexao->prepare($sql)) {
    if (!empty($parametros)) {
        $bind_params = array_merge([$tipos], $parametros);
        $refs = [];
        foreach ($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($livro = $resultado->fetch_assoc()) {
        $livros[] = $livro;
    }

    $stmt->close();
}
?>

<main class="container my-4 my-md-5">
    <!-- Hero Section Responsiva -->
    <section class="mb-4 mb-md-5 text-center">
        <h1 class="display-4 text-brown mb-3">Bem-vindo à Biblioteca Bethel</h1>
        <p class="lead px-3 px-md-0">Explore um universo de conhecimento com milhares de livros físicos de diversos
            gêneros</p>
    </section>

    <!-- Seção de Filtros Responsiva -->
    <section class="mb-4 mb-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <h2 class="h4 h3-md mb-3 mb-md-4">
                    <i class="bi bi-funnel me-2"></i>Explore o Catálogo
                </h2>
                <p class="text-muted mb-3 d-none d-md-block">Filtre por gênero, autor ou título para encontrar o livro
                    perfeito.</p>

                <form method="GET" action="">
                    <div class="row g-2 g-md-3">
                        <!-- Campo de Busca -->
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <input type="text" name="busca" class="form-control border-start-0"
                                    placeholder="Buscar por título ou autor..." value="<?= htmlspecialchars($busca) ?>"
                                    aria-label="Campo de busca">
                            </div>
                        </div>

                        <!-- Select de Gênero -->
                        <div class="col-12 col-md-4">
                            <select name="genero" class="form-select" aria-label="Filtrar por gênero">
                                <option value="">Todos os gêneros</option>
                                <?php
                                $generosDisponiveis = [
                                    "Fantasia",
                                    "Romance",
                                    "Suspense",
                                    "Ficção",
                                    "Poesias",
                                    "Novela",
                                    "Crônica",
                                    "Contos",
                                    "Aventura",
                                    "Ficção Científica"
                                ];
                                foreach ($generosDisponiveis as $g):
                                    ?>
                                    <option value="<?= htmlspecialchars($g) ?>" <?= ($genero === $g ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($g) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Botão de Pesquisa -->
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-primary w-100" aria-label="Pesquisar livros">
                                <i class="bi bi-search me-1 d-none d-md-inline"></i>
                                <span>Pesquisar</span>
                            </button>
                        </div>

                        <!-- Limpar Filtros (apenas quando há filtros ativos) -->
                        <?php if (!empty($busca) || !empty($genero)): ?>
                            <div class="col-12">
                                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Limpar filtros
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Seção de Resultados -->
    <section class="mt-4 mb-4 mb-md-5">
        <!-- Header dos Resultados -->
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-4">
            <h3 class="h5 h4-md mb-2 mb-md-0">
                <i class="bi bi-books me-2"></i>
                Livros Encontrados
                <span class="badge bg-primary ms-2"><?= count($livros) ?></span>
            </h3>

            <!-- Informações de Filtro Ativo -->
            <?php if (!empty($busca) || !empty($genero)): ?>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <small class="text-muted">Filtros ativos:</small>
                    <?php if (!empty($busca)): ?>
                        <span class="badge bg-secondary">
                            <i class="bi bi-search me-1"></i>
                            <?= htmlspecialchars(substr($busca, 0, 20)) ?>
                            <?= strlen($busca) > 20 ? '...' : '' ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($genero)): ?>
                        <span class="badge bg-secondary">
                            <i class="bi bi-tag me-1"></i>
                            <?= htmlspecialchars($genero) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grid de Livros ou Estado Vazio -->
        <?php if (empty($livros)): ?>
            <div class="empty-state py-5">
                <div class="text-center">
                    <i class="bi bi-book display-1 text-muted mb-3"></i>
                    <h3 class="mb-3">Nenhum livro encontrado</h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($busca) || !empty($genero)): ?>
                            Não encontramos livros com os critérios selecionados.<br>
                            Tente refinar sua pesquisa ou limpar os filtros.
                        <?php else: ?>
                            O catálogo está vazio no momento.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($busca) || !empty($genero)): ?>
                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-2"></i>Ver todos os livros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid Responsivo de Livros -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                <?php foreach ($livros as $livro): ?>
                    <div class="col">
                        <article class="card h-100 shadow-sm hover-lift">
                            <!-- Imagem do Livro -->
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($livro['foto_capa'] ?? 'img/capa_padrao.jpg') ?>"
                                    class="card-img-top" alt="Capa do livro: <?= htmlspecialchars($livro['titulo']) ?>"
                                    loading="lazy" style="height: 300px; object-fit: cover;">

                                <!-- Badge de Status -->
                                <span
                                    class="position-absolute top-0 end-0 m-2 badge <?= ($livro['estoque_disponivel'] ?? 0) > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <i
                                        class="bi <?= ($livro['estoque_disponivel'] ?? 0) > 0 ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                    <?= ($livro['estoque_disponivel'] ?? 0) > 0 ? 'Disponível' : 'Indisponível' ?>
                                </span>
                            </div>

                            <!-- Corpo do Card -->
                            <div class="card-body d-flex flex-column p-3">
                                <!-- Título -->
                                <h5 class="card-title mb-2"
                                    style="min-height: 2.4em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <?= htmlspecialchars($livro['titulo']) ?>
                                </h5>

                                <!-- Metadados -->
                                <div class="mb-2">
                                    <p class="card-text text-muted mb-1 small">
                                        <i class="bi bi-person me-1"></i>
                                        <strong>Autor:</strong>
                                        <span class="text-truncate d-inline-block"
                                            style="max-width: 180px; vertical-align: bottom;">
                                            <?= htmlspecialchars($livro['autor']) ?>
                                        </span>
                                    </p>

                                    <?php if (!empty($livro['genero'])): ?>
                                        <p class="card-text text-muted mb-1 small">
                                            <i class="bi bi-tag me-1"></i>
                                            <strong>Gênero:</strong> <?= htmlspecialchars($livro['genero']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <p class="card-text text-muted mb-2 small">
                                        <i class="bi bi-hash me-1"></i>
                                        <strong>Tombo:</strong> <?= htmlspecialchars($livro['tombo'] ?? 'N/A') ?>
                                    </p>
                                </div>

                                <!-- Descrição (oculta em mobile) -->
                                <p class="card-text small text-muted d-none d-md-block mb-3"
                                    style="min-height: 3em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <?= htmlspecialchars(substr($livro['descricao'] ?? 'Sem descrição disponível.', 0, 100)) ?>
                                    <?php if (strlen($livro['descricao'] ?? '') > 100)
                                        echo '...'; ?>
                                </p>

                                <!-- Espaçador flexível -->
                                <div class="mt-auto"></div>
                            </div>

                            <!-- Footer do Card -->
                            <div class="card-footer bg-white border-top-0 p-3 pt-0">
                                <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch">
                                    <!-- Botão Ver Detalhes -->
                                    <a href="detalhes_livro.php?id=<?= $livro['id'] ?>"
                                        class="btn btn-outline-primary btn-sm flex-grow-1"
                                        aria-label="Ver detalhes de <?= htmlspecialchars($livro['titulo']) ?>">
                                        <i class="bi bi-eye me-1"></i>
                                        <span class="d-none d-sm-inline">Ver </span>Detalhes
                                    </a>

                                    <!-- Botão Alugar (apenas se disponível e logado) -->
                                    <?php if (($livro['estoque_disponivel'] ?? 0) > 0): ?>
                                        <?php if (isset($_SESSION['usuario_id'])): ?>
                                            <a href="alugar.php?idLivro=<?= $livro['id'] ?>" class="btn btn-warning btn-sm flex-grow-1"
                                                aria-label="Alugar <?= htmlspecialchars($livro['titulo']) ?>">
                                                <i class="bi bi-bookmark-check me-1"></i>
                                                Alugar
                                            </a>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-success btn-sm flex-grow-1"
                                                aria-label="Fazer login para alugar">
                                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                                Login
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="mb-5">
        <h2 class="h3 mb-3">Destaques da Semana</h2>
        <ul class="list-group">
            <li class="list-group-item"><strong>Livro:</strong> "As Estrelas que Somos" por Lúcia Fernandes</li>
            <li class="list-group-item"><strong>Artigo:</strong> "Inteligência Artificial na Educação Moderna"</li>
            <li class="list-group-item"><strong>Audiobook:</strong> "Histórias que Encantam" narrado por João Silveira
            </li>
        </ul>
    </section>

</main>