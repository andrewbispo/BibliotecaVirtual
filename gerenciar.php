<?php
// CRUCIAL: Ativa o buffer de saída para garantir que o header funcione
ob_start();
session_start();
require 'conexao.php';

// Cadastrar novo livro
if (isset($_POST['cadastrar'])) {
    $titulo = $_POST['titulo'];
    $tombo = $_POST['tombo'];
    $editora = $_POST['editora'];
    $num_paginas = $_POST['num_paginas'];
    $autor = $_POST['autor'];
    $genero = $_POST['genero'];
    $dataPublicacao = $_POST['dataPublicacao'];
    $quantidadeAcervo = $_POST['quantidadeAcervo'];
    $foto_capa = $_POST['foto_capa'] ?? '';

    // Validação do lado do servidor para campos obrigatórios
    if (empty($titulo) || empty($tombo) || empty($editora) || empty($autor) || empty($num_paginas)) {
        $_SESSION['alerta_tipo'] = 'danger';
        $_SESSION['alerta_mensagem'] = 'Erro: Os campos Título, Tombo, Editora, Número de Páginas e Autor são obrigatórios!';
    } else {
        // Verifica se a conexão está OK antes de preparar
        if ($conexao->connect_error) {
            $_SESSION['alerta_tipo'] = 'danger';
            $_SESSION['alerta_mensagem'] = 'Erro de conexão com o banco de dados: ' . $conexao->connect_error;
        } else {
            $stmt = $conexao->prepare("INSERT INTO acervo (titulo, tombo, editora, num_paginas, autor, genero, dataPublicacao, quantidadeAcervo, foto_capa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt === false) {
                $_SESSION['alerta_tipo'] = 'danger';
                $_SESSION['alerta_mensagem'] = 'Erro ao preparar a consulta de INSERT: ' . $conexao->error;
            } else {
                $stmt->bind_param("sssisssis", $titulo, $tombo, $editora, $num_paginas, $autor, $genero, $dataPublicacao, $quantidadeAcervo, $foto_capa);

                // ✅ ESTA É A MENSAGEM DE SUCESSO
                if ($stmt->execute()) {
                    $_SESSION['alerta_tipo'] = 'success';
                    $_SESSION['alerta_mensagem'] = 'Livro ' . htmlspecialchars($titulo) . ' cadastrado com sucesso!';
                } else {
                    $_SESSION['alerta_tipo'] = 'danger';
                    $_SESSION['alerta_mensagem'] = 'Erro ao executar o INSERT: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ...
// Excluir livro
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $tituloExcluido = 'Livro Desconhecido'; // Valor padrão em caso de falha na busca

    // 1. Buscar o título antes de excluir
    $stmt_busca = $conexao->prepare("SELECT titulo, tombo FROM acervo WHERE id = ?");
    $stmt_busca->bind_param("i", $id);
    $stmt_busca->execute();
    $result_busca = $stmt_busca->get_result();

    if ($row = $result_busca->fetch_assoc()) {
        $tituloExcluido = htmlspecialchars($row['titulo'] . ' (Tombo: ' . $row['tombo'] . ')');
    }
    $stmt_busca->close();

    // 2. Excluir o registro
    $stmt = $conexao->prepare("DELETE FROM acervo WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['alerta_tipo'] = 'warning';
        // 3. Usar o título na mensagem de alerta
        $_SESSION['alerta_mensagem'] = 'Livro ' . $tituloExcluido . ' excluído com sucesso.';
    } else {
        $_SESSION['alerta_tipo'] = 'danger';
        $_SESSION['alerta_mensagem'] = 'Erro ao excluir o livro: ' . $stmt->error;
    }

    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// ...

// Atualizar livro
if (isset($_POST['atualizar'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $tombo = $_POST['tombo'];
    $editora = $_POST['editora'];
    $num_paginas = $_POST['num_paginas'];
    $autor = $_POST['autor'];
    $genero = $_POST['genero'];
    $dataPublicacao = $_POST['dataPublicacao'];
    $quantidadeAcervo = $_POST['quantidadeAcervo'];
    $foto_capa = $_POST['foto_capa'] ?? '';

    // Validação do lado do servidor para campos obrigatórios
    if (empty($titulo) || empty($tombo) || empty($editora) || empty($autor) || empty($num_paginas)) {
        $_SESSION['alerta_tipo'] = 'danger';
        $_SESSION['alerta_mensagem'] = 'Erro: Preencha todos os campos obrigatórios para atualização!';
    } else {
        $stmt = $conexao->prepare("UPDATE acervo SET titulo=?, tombo=?, editora=?, num_paginas=?, autor=?, genero=?, dataPublicacao=?, quantidadeAcervo=?, foto_capa=? WHERE id=?");

        if ($stmt === false) {
            $_SESSION['alerta_tipo'] = 'danger';
            $_SESSION['alerta_mensagem'] = 'Erro ao preparar a consulta de UPDATE: ' . $conexao->error;
        } else {
            $stmt->bind_param("sssisssisi", $titulo, $tombo, $editora, $num_paginas, $autor, $genero, $dataPublicacao, $quantidadeAcervo, $foto_capa, $id);

            if ($stmt->execute()) {
                $_SESSION['alerta_tipo'] = 'success';
                $_SESSION['alerta_mensagem'] = 'Livro **' . htmlspecialchars($titulo) . '** atualizado com sucesso!';
            } else {
                $_SESSION['alerta_tipo'] = 'danger';
                $_SESSION['alerta_mensagem'] = 'Erro ao executar o UPDATE: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . (isset($id) ? "?editar=" . $id : ""));
    exit;
}

// Parâmetros de busca
$busca = $_GET['busca'] ?? '';
$genero = $_GET['genero'] ?? '';
$ordem = $_GET['ordem'] ?? 'titulo'; // Padrão é ordenar por título

// Construir a query de busca (Esta seção não precisou de alteração, pois SELECT * inclui todos os campos)
$sql = "SELECT * FROM acervo WHERE 1=1";
$parametros = [];
$tipos = '';

// Adiciona filtro por título, autor ou número do tombo
if (!empty($busca)) {
    $sql .= " AND (titulo LIKE ? OR autor LIKE ? OR tombo LIKE ?)";
    $busca_param = "%" . $busca . "%";
    $parametros[] = $busca_param;
    $parametros[] = $busca_param;
    $parametros[] = $busca_param;
    $tipos .= 'sss';
}

// Adiciona filtro por gênero
if (!empty($genero)) {
    $sql .= " AND genero = ?";
    $parametros[] = $genero;
    $tipos .= 's';
}

// Adiciona ordenação
$ordem_coluna = ($ordem === 'tombo') ? 'tombo' : 'titulo';
$sql .= " ORDER BY $ordem_coluna ASC";

// Executa a consulta
if ($stmt = $conexao->prepare($sql)) {
    if (!empty($parametros)) {
        $bind_params = array_merge([$tipos], $parametros);
        $refs = [];
        foreach ($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // Se a preparação falhar, executa a consulta simples
    $result = $conexao->query("SELECT * FROM acervo ORDER BY titulo ASC");
}

// Buscar livro para edição
$livroEdit = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $conexao->prepare("SELECT * FROM acervo WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $livroEdit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Gerenciador de Acervo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="src\style.css" rel="stylesheet">
    <link rel="icon" href="src\book.svg" type="image">
    <style>
        /* Corrige footer da página gerenciar.php */
        .livros-page footer {
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            background: linear-gradient(135deg, var(--color-primary), var(--color-brown-dark)) !important;
        }
    </style>
</head>
<header>
    <?php include "header.php";
    ?>
</header>

<body class="bg-light">
    <?php

    // Verificação de duplicatas (código de debug)
    $sql_debug = "SELECT titulo, autor, COUNT(*) as count 
              FROM acervo 
              GROUP BY titulo, autor 
              HAVING COUNT(*) > 1";

    if ($stmt_debug = $conexao->prepare($sql_debug)) {
        $stmt_debug->execute();
        $result_debug = $stmt_debug->get_result();

        if ($result_debug->num_rows > 0) {
            // Bloco HTML inicial usando Heredoc para clareza
            echo <<<HTML
            <div class="container mt-3">
                <div class="alert alert-warning">
                    <strong>Detectados registros duplicados:</strong>
                </div>
                <ul>
        HTML;

            // Loop de resultados
            while ($row = $result_debug->fetch_assoc()) {
                // Interpolação de string para saída do <li>
                $titulo = htmlspecialchars($row['titulo']);
                $autor = htmlspecialchars($row['autor']);
                $count = $row['count'];

                echo "<li>Título: $titulo - Autor: $autor ($count vezes)</li>\n";
            }

            // Bloco HTML final
            echo <<<HTML
                </ul>
            </div>
        HTML;
        }

        $stmt_debug->close();
    }

    ?>

    <?php if (isset($_SESSION['alerta_mensagem'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['alerta_tipo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['alerta_mensagem']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php
        // Limpa as variáveis de sessão para que o alerta não apareça novamente
        unset($_SESSION['alerta_mensagem']);
        unset($_SESSION['alerta_tipo']);
        ?>
    <?php endif; ?>
    <div class="container pt-5">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">
                        <?php echo $livroEdit ? "Editar Livro" : "Cadastrar Livro"; ?>
                    </h5>
                </div>
                <div class="card-body mb-4"
                    style=" background-color: white; border: none; border-bottom-right-radius: 20px; border-bottom-left-radius: 20px;">
                    <form method="POST">
                        <?php if ($livroEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $livroEdit['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" name="titulo" class="form-control" required
                                value="<?php echo $livroEdit['titulo'] ?? ''; ?>" placeholder="Ex: O pequeno príncipe">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Número do Tombo</label>
                            <input type="text" name="tombo" class="form-control" required
                                value="<?php echo $livroEdit['tombo'] ?? ''; ?>" placeholder="Ex: 20240">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Editora</label>
                            <input type="text" name="editora" class="form-control" required
                                value="<?php echo $livroEdit['editora'] ?? ''; ?>"
                                placeholder="Ex: Companhia das Letras">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Número de Páginas</label>
                            <input type="number" name="num_paginas" class="form-control" required
                                value="<?php echo $livroEdit['num_paginas'] ?? ''; ?>" min="1" placeholder="Ex: 256">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Autor</label>
                            <input type="text" name="autor" class="form-control" required
                                value="<?php echo $livroEdit['autor'] ?? ''; ?>" placeholder="Ex: J.K. Rowling">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gênero</label>
                            <select name="genero" class="form-select" aria-label="Gênero">
                                <option value="" <?php echo empty($livroEdit['genero']) ? 'selected' : ''; ?>>
                                    Escolha uma opção</option>
                                <option value="Fantasia" <?php echo ($livroEdit['genero'] ?? '') === 'Fantasia' ? 'selected' : ''; ?>>Fantasia</option>
                                <option value="Romance" <?php echo ($livroEdit['genero'] ?? '') === 'Romance' ? 'selected' : ''; ?>>Romance</option>
                                <option value="Suspense" <?php echo ($livroEdit['genero'] ?? '') === 'Suspense' ? 'selected' : ''; ?>>Suspense</option>
                                <option value="Aventura" <?php echo ($livroEdit['genero'] ?? '') === 'Aventura' ? 'selected' : ''; ?>>Aventura</option>
                                <option value="Ficção" <?php echo ($livroEdit['genero'] ?? '') === 'Ficção' ? 'selected' : ''; ?>>Ficção</option>
                                <option value="Poesias" <?php echo ($livroEdit['genero'] ?? '') === 'Poesias' ? 'selected' : ''; ?>>Poesias</option>
                                <option value="Novela" <?php echo ($livroEdit['genero'] ?? '') === 'Novela' ? 'selected' : ''; ?>>Novela</option>
                                <option value="Crônica" <?php echo ($livroEdit['genero'] ?? '') === 'Crônica' ? 'selected' : ''; ?>>Crônica</option>
                                <option value="Contos" <?php echo ($livroEdit['genero'] ?? '') === 'Contos' ? 'selected' : ''; ?>>Contos</option>
                                <option value="Aventura" <?php echo ($livroEdit['genero'] ?? '') === 'Aventura' ? 'selected' : ''; ?>>Aventura</option>
                                <option value="Ficção Científica" <?php echo ($livroEdit['genero'] ?? '') === 'Ficção Científica' ? 'selected' : ''; ?>>Ficção Científica</option>
                            </select>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">Data de Publicação</label>
                            <input type="date" name="dataPublicacao" class="form-control"
                                value="<?php echo $livroEdit['dataPublicacao'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL da Foto de Capa</label>
                            <input type="url" name="foto_capa" class="form-control"
                                value="<?php echo $livroEdit['foto_capa'] ?? ''; ?>"
                                placeholder="Ex: https://exemplo.com/capa.jpg">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantidade no Acervo</label>
                            <input type="number" name="quantidadeAcervo" class="form-control" min="0"
                                value="<?php echo $livroEdit['quantidadeAcervo'] ?? 0; ?>">
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="<?php echo $livroEdit ? 'atualizar' : 'cadastrar'; ?>"
                                class="btn btn-success">
                                <?php echo $livroEdit ? 'Atualizar' : 'Cadastrar'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card-header text-white">
                    <div class=" d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 text-white">Lista de Livros</h5>
                        <div class="d-flex align-items-center gap-2 w-100">
                            <form class="d-flex gap-2 flex-grow-1" method="GET" action="">
                                <input type="text" name="busca" class="form-control form-control-sm"
                                    placeholder="Buscar por título, autor ou tombo..."
                                    value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                                <select name="genero" class="form-select form-select-sm filter-select"
                                    style="width: auto; min-width: 140px;">
                                    <option value="">Gêneros</option>
                                    <?php
                                    $generosDisponiveis = ["Fantasia", "Romance", "Suspense", "Ficção", "Poesias", "Novela", "Crônica", "Contos", "Aventura", "Ficção Científica"];
                                    foreach ($generosDisponiveis as $g):
                                        $selected = (isset($_GET['genero']) && $_GET['genero'] === $g) ? 'selected' : '';
                                        echo "<option value=\"$g\" $selected>$g</option>";
                                    endforeach;
                                    ?>
                                </select>
                                <select name="ordem" class="form-select form-select filter-select"
                                    style="width: auto; min-width: 160px; color: black;">
                                    <option value="titulo" <?php echo (!isset($_GET['ordem']) || $_GET['ordem'] === 'titulo') ? 'selected' : ''; ?>>Ordem Alfabética</option>
                                    <option value="tombo" <?php echo (isset($_GET['ordem']) && $_GET['ordem'] === 'tombo') ? 'selected' : ''; ?>>Nº do Tombo</option>
                                </select>
                                <button type="submit" class="btn btn-dark btn-sm"
                                    style="color: black !;">Buscar</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0"
                        style=" background-color: white; border: none; border-bottom-right-radius: 20px; border-bottom-left-radius: 20px;">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-1">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 5%">ID</th>
                                        <th style="width: 10%">Nº Tombo</th>
                                        <th style="width: 15%">Título</th>
                                        <th style="width: 15%">Autor</th>
                                        <th style="width: 10%">Editora</th>
                                        <th style="width: 8%">Páginas</th>
                                        <th style="width: 10%">Gênero</th>
                                        <th style="width: 10%">Publicação</th>
                                        <th style="width: 7%">Qtd</th>
                                        <th style="width: 10%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Configuração da paginação
                                    $livrosPorPagina = 25;
                                    $paginaAtual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
                                    $offset = ($paginaAtual - 1) * $livrosPorPagina;

                                    // Filtro e ordenação
                                    $busca = $_GET['busca'] ?? '';
                                    $genero = $_GET['genero'] ?? '';
                                    $ordem = $_GET['ordem'] ?? 'titulo';

                                    $filtro = [];
                                    if ($busca) {
                                        $buscaEsc = $conexao->real_escape_string($busca);
                                        $filtro[] = "(titulo LIKE '%$buscaEsc%' OR autor LIKE '%$buscaEsc%' OR tombo LIKE '%$buscaEsc%')";
                                    }
                                    if ($genero) {
                                        $generoEsc = $conexao->real_escape_string($genero);
                                        $filtro[] = "genero = '$generoEsc'";
                                    }

                                    $where = count($filtro) ? "WHERE " . implode(" AND ", $filtro) : "";

                                    // Total de livros
                                    $totalLivros = $conexao->query("SELECT COUNT(*) AS total FROM acervo $where")->fetch_assoc()['total'];
                                    $totalPaginas = ceil($totalLivros / $livrosPorPagina);

                                    // Consulta principal com limite e offset
                                    $sql = "SELECT * FROM acervo $where ORDER BY $ordem LIMIT $livrosPorPagina OFFSET $offset";
                                    $result = $conexao->query($sql);

                                    while ($row = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['tombo']); ?></td>
                                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($row['autor']); ?></td>
                                            <td><?php echo htmlspecialchars($row['editora']); ?></td>
                                            <td><?php echo htmlspecialchars($row['num_paginas']); ?></td>
                                            <td><?php echo htmlspecialchars($row['genero']); ?></td>
                                            <td><?php echo htmlspecialchars($row['dataPublicacao']); ?></td>
                                            <td><?php echo htmlspecialchars($row['quantidadeAcervo']); ?></td>
                                            <td>
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="?editar=<?php echo $row['id']; ?>"
                                                        class="btn-warning btn btn-sm">Editar</a>
                                                    <a href="?excluir=<?php echo $row['id']; ?>"
                                                        class="btn-danger btn btn-sm"
                                                        onclick="return confirm('Deseja excluir este livro?');">Excluir</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label="Navegação de páginas">
                            <ul class="pagination justify-content-center my-3">
                                <li class="page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" style="color: black; background-color: white;"
                                        href="?pagina=<?= $paginaAtual - 1 ?>&busca=<?= urlencode($busca) ?>&genero=<?= urlencode($genero) ?>&ordem=<?= urlencode($ordem) ?>">Anterior</a>
                                </li>

                                <?php
                                // Limitar para mostrar no máximo 10 botões
                                $inicio = max(1, $paginaAtual - 5);
                                $fim = min($totalPaginas, $inicio + 9);

                                for ($i = $inicio; $i <= $fim; $i++):
                                    ?>
                                    <li class="page-item <?= $i == $paginaAtual ? 'active' : '' ?>">
                                        <a class="page-link " style=" color: black; background-color: white;"
                                            href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>&genero=<?= urlencode($genero) ?>&ordem=<?= urlencode($ordem) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                                    <a class="page-link " style=" color: black; background-color: white;"
                                        href="?pagina=<?= $paginaAtual + 1 ?>&busca=<?= urlencode($busca) ?>&genero=<?= urlencode($genero) ?>&ordem=<?= urlencode($ordem) ?>">Próximo</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

<?php
$conexao->close();
?>