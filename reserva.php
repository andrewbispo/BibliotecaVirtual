<?php
/**
 * SISTEMA DE RESERVAS - EXEMPLO DE IMPLEMENTAÇÃO
 * 
 * Este arquivo é um EXEMPLO de como implementar o sistema de reservas
 * Funcionalidade: Quando um livro está indisponível, o usuário pode
 * reservá-lo e será notificado quando ficar disponível.
 */

session_start();
require 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$idLivro = $_GET['idLivro'] ?? null;
$mensagem = "";

// Buscar informações do livro
$livro = null;
if ($idLivro) {
    $stmt = $conexao->prepare("
        SELECT a.*, 
               COALESCE(e.quantidadeEstoque, a.quantidadeAcervo) as estoque_total,
               (SELECT COUNT(*) FROM pedido 
                WHERE idLivro = a.id 
                AND statusRetirada IN ('Pendente', 'Retirado')) as pedidos_ativos,
               (SELECT COUNT(*) FROM reservas
                WHERE idLivro = a.id
                AND statusReserva = 'Ativa') as reservas_ativas
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

// Verificar se já tem reserva ativa
$temReservaAtiva = false;
if ($livro) {
    $stmt = $conexao->prepare("
        SELECT idReserva 
        FROM reservas 
        WHERE id_usuario = ? 
        AND idLivro = ? 
        AND statusReserva = 'Ativa'
    ");
    $stmt->bind_param("ii", $id_usuario, $idLivro);
    $stmt->execute();
    $result = $stmt->get_result();
    $temReservaAtiva = $result->num_rows > 0;
    $stmt->close();
}

// Processar reserva
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['fazer_reserva'])) {
    if ($temReservaAtiva) {
        $mensagem = "<div class='alert alert-warning'><i class='bi bi-exclamation-triangle me-2'></i>Você já possui uma reserva ativa para este livro.</div>";
    } else {
        // Criar reserva
        $dataExpiracao = date('Y-m-d H:i:s', strtotime('+7 days')); // Expira em 7 dias

        $stmt = $conexao->prepare("
            INSERT INTO reservas (id_usuario, idLivro, dataReserva, statusReserva, dataExpiracao)
            VALUES (?, ?, NOW(), 'Ativa', ?)
        ");
        $stmt->bind_param("iis", $id_usuario, $idLivro, $dataExpiracao);

        if ($stmt->execute()) {
            $_SESSION['alerta_tipo'] = 'success';
            $_SESSION['alerta_mensagem'] = 'Reserva realizada com sucesso! Você será notificado quando o livro estiver disponível.';

            // Aqui você poderia enviar um email de confirmação
            // enviarEmailConfirmacaoReserva($id_usuario, $idLivro);

            header("Location: minhas_reservas.php");
            exit;
        } else {
            $mensagem = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>Erro ao criar reserva.</div>";
        }
        $stmt->close();
    }
}

// Cancelar reserva
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancelar_reserva'])) {
    $stmt = $conexao->prepare("
        UPDATE reservas 
        SET statusReserva = 'Cancelada'
        WHERE id_usuario = ? 
        AND idLivro = ? 
        AND statusReserva = 'Ativa'
    ");
    $stmt->bind_param("ii", $id_usuario, $idLivro);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['alerta_tipo'] = 'info';
        $_SESSION['alerta_mensagem'] = 'Reserva cancelada.';
        header("Location: index.php");
        exit;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservar Livro - Biblioteca Bethel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="src/style.css">
    <link rel="icon" href="src\book.svg" type="image/svg+xml">
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>

    <div class="container mt-5 mb-5">
        <?php if (!$livro): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Livro não encontrado.
                    <a href="index.php" class="btn btn-sm btn-primary ms-3">Voltar</a>
                </div>
        <?php else: ?>
                <div class="row g-4">
                    <!-- Imagem do Livro -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <img src="<?= htmlspecialchars($livro['foto_capa'] ?? 'img/capa_padrao.jpg') ?>"
                                class="card-img-top" alt="Capa do livro" style="height: 500px; object-fit: cover;">
                        </div>
                    </div>

                    <!-- Informações e Formulário -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-warning text-dark py-3">
                                <h4 class="mb-0">
                                    <i class="bi bi-bookmark-star me-2"></i>
                                    Reservar Livro
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <?= $mensagem ?>

                                <h5 class="card-title mb-3"><?= htmlspecialchars($livro['titulo']) ?></h5>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong><i class="bi bi-person me-2"></i>Autor:</strong>
                                            <?= htmlspecialchars($livro['autor']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <strong><i class="bi bi-building me-2"></i>Editora:</strong>
                                            <?= htmlspecialchars($livro['editora']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong><i class="bi bi-tag me-2"></i>Gênero:</strong>
                                            <?= htmlspecialchars($livro['genero'] ?? 'Não informado') ?>
                                        </p>
                                        <p class="mb-2">
                                            <strong><i class="bi bi-hash me-2"></i>Tombo:</strong>
                                            <?= htmlspecialchars($livro['tombo']) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Status do Estoque -->
                                <div class="alert alert-danger mb-4">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-x-circle-fill me-3 fs-4"></i>
                                        <div>
                                            <strong>Livro Indisponível</strong><br>
                                            <small>
                                                <?= $livro['estoque_disponivel'] ?> unidade(s) em estoque,
                                                <?= $livro['pedidos_ativos'] ?> pedido(s) ativo(s)
                                                <?php if ($livro['reservas_ativas'] > 0): ?>
                                                        , <?= $livro['reservas_ativas'] ?> reserva(s) na fila
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informações sobre a Reserva -->
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Como funciona a reserva?
                                        </h6>
                                        <ul class="mb-0 small">
                                            <li>Você entrará na fila de espera por este livro</li>
                                            <li>Quando o livro estiver disponível, você será notificado por email</li>
                                            <li>Você terá 7 dias para fazer o pedido após a notificação</li>
                                            <li>Após 7 dias, sua reserva expira automaticamente</li>
                                        </ul>
                                    </div>
                                </div>

                                <?php if ($temReservaAtiva): ?>
                                        <!-- Já tem reserva ativa -->
                                        <div class="alert alert-info mb-4">
                                            <i class="bi bi-bookmark-check-fill me-2"></i>
                                            <strong>Você já possui uma reserva ativa para este livro!</strong>
                                        </div>

                                        <form method="POST">
                                            <div class="d-flex gap-2">
                                                <button type="submit" name="cancelar_reserva" class="btn btn-danger"
                                                    onclick="return confirm('Deseja cancelar sua reserva?')">
                                                    <i class="bi bi-x-circle me-2"></i>Cancelar Reserva
                                                </button>
                                                <a href="minhas_reservas.php" class="btn btn-primary">
                                                    <i class="bi bi-list-ul me-2"></i>Ver Minhas Reservas
                                                </a>
                                                <a href="index.php" class="btn btn-outline-secondary">
                                                    <i class="bi bi-arrow-left me-2"></i>Voltar
                                                </a>
                                            </div>
                                        </form>
                                <?php else: ?>
                                        <!-- Fazer reserva -->
                                        <form method="POST">
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="submit" name="fazer_reserva" class="btn btn-warning btn-lg flex-grow-1">
                                                    <i class="bi bi-bookmark-plus me-2"></i>Fazer Reserva
                                                </button>
                                                <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                                </a>
                                            </div>
                                        </form>
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

<?php
/**
 * FUNÇÕES AUXILIARES PARA SISTEMA DE RESERVAS
 * 
 * Adicione estas funções em um arquivo separado (ex: funcoes_reservas.php)
 */

/**
 * Envia email de confirmação de reserva
 */
function enviarEmailConfirmacaoReserva($id_usuario, $idLivro)
{
    // TODO: Implementar envio de email
    // Usar PHPMailer ou API de email
    return true;
}

/**
 * Notifica usuário quando livro fica disponível
 * Esta função deve ser chamada quando um pedido é devolvido
 */
function notificarProximaReserva($idLivro)
{
    global $conexao;

    // Buscar próxima reserva ativa na fila
    $stmt = $conexao->prepare("
        SELECT r.*, u.email, u.nome, a.titulo
        FROM reservas r
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        INNER JOIN acervo a ON r.idLivro = a.id
        WHERE r.idLivro = ?
        AND r.statusReserva = 'Ativa'
        ORDER BY r.dataReserva ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $idLivro);
    $stmt->execute();
    $reserva = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($reserva) {
        // Enviar email de notificação
        $assunto = "Livro Disponível - " . $reserva['titulo'];
        $mensagem = "Olá " . $reserva['nome'] . ",\n\n";
        $mensagem .= "O livro '" . $reserva['titulo'] . "' que você reservou está disponível!\n";
        $mensagem .= "Você tem 7 dias para fazer o pedido.\n\n";
        $mensagem .= "Acesse: http://seusite.com/alugar.php?idLivro=" . $idLivro;

        // TODO: Enviar email real
        // mail($reserva['email'], $assunto, $mensagem);

        return true;
    }

    return false;
}

/**
 * Verifica e expira reservas antigas
 * Executar esta função periodicamente (cron job)
 */
function expirarReservasAntigas()
{
    global $conexao;

    $stmt = $conexao->prepare("
        UPDATE reservas
        SET statusReserva = 'Cancelada'
        WHERE statusReserva = 'Ativa'
        AND dataExpiracao < NOW()
    ");
    $stmt->execute();
    $reservasExpiradas = $stmt->affected_rows;
    $stmt->close();

    return $reservasExpiradas;
}

/**
 * Conta posição do usuário na fila de reserva
 */
function obterPosicaoFila($id_usuario, $idLivro)
{
    global $conexao;

    $stmt = $conexao->prepare("
        SELECT COUNT(*) + 1 as posicao
        FROM reservas
        WHERE idLivro = ?
        AND statusReserva = 'Ativa'
        AND dataReserva < (
            SELECT dataReserva 
            FROM reservas 
            WHERE id_usuario = ? 
            AND idLivro = ? 
            AND statusReserva = 'Ativa'
        )
    ");
    $stmt->bind_param("iii", $idLivro, $id_usuario, $idLivro);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result['posicao'] ?? 0;
}
?>