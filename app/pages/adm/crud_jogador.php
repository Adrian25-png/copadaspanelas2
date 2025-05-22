<?php
// Ajuste caminho do require para conexão PDO
require_once __DIR__ . '/../../config/conexao.php';
$pdo = conectar();
session_start();

// Verifica se o usuário está autenticado e é admin
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Ajuste caminho do include session_check.php
require_once __DIR__ . '/../../actions/cadastro_adm/session_check.php';

$isAdmin = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Função para gerar token CSRF
function gerarTokenCSRF() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Tratar exclusão do jogador
if (isset($_POST['delete_token'])) {
    // Verificação do token CSRF
    if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token inválido");
    }

    $token = $_POST['delete_token'];

    // Preparar e executar busca do id pelo token usando PDO
    $stmt = $pdo->prepare("SELECT id FROM jogadores WHERE token = :token");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $id = $stmt->fetchColumn();

    if ($id) {
        $deleteStmt = $pdo->prepare("DELETE FROM jogadores WHERE id = :id");
        $deleteStmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($deleteStmt->execute()) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        die("Token inválido");
    }
}

$_SESSION['csrf_token'] = gerarTokenCSRF();

// Obter todos os times
$timesStmt = $pdo->query("SELECT id, nome FROM times");
$times = $timesStmt->fetchAll(PDO::FETCH_ASSOC);

// Obter jogadores por time selecionado
$selectedTimeId = $_POST['time_id'] ?? null;
$players = [];
if ($selectedTimeId) {
    $playersStmt = $pdo->prepare("SELECT id, nome, posicao, numero, gols, assistencias, cartoes_amarelos, cartoes_vermelhos, imagem, token FROM jogadores WHERE time_id = :time_id");
    $playersStmt->bindValue(':time_id', $selectedTimeId, PDO::PARAM_INT);
    $playersStmt->execute();
    $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRUD Jogadores</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../../public/css/cssfooter.css" />
    <link rel="stylesheet" href="../../../public/css/adm/cadastros_times_jogadores_adm/crud_jogador.css" />
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <script>
        function populatePlayersList(timeId) {
            document.getElementById('selectedTimeId').value = timeId;
            document.getElementById('playersForm').submit();
        }
        function redirectToAddPlayer() {
            window.location.href = 'formulario_jogador.php';
        }
    </script>
</head>
<body>
<?php require_once 'header_adm.php'; ?>
<h1>EDITAR JOGADORES</h1>
<div class="container">
    <div class="form-group">
        <button class="btn-add" onclick="redirectToAddPlayer()">+</button>
        <form id="playersForm" method="POST">
            <label for="timeSelect" class="mr-2">Selecione um Time:</label>
            <select class="form-control" id="timeSelect" name="time_id" onchange="populatePlayersList(this.value)">
                <option value="">Escolha um time</option>
                <?php foreach ($times as $time): ?>
                    <option value="<?php echo htmlspecialchars($time['id']); ?>" <?php echo ($selectedTimeId == $time['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($time['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <input type="hidden" id="selectedTimeId" name="selected_time_id" value="<?php echo htmlspecialchars($selectedTimeId); ?>" />
    <?php if ($selectedTimeId): ?>
        <?php if (count($players) > 0): ?>
            <?php foreach ($players as $player): ?>
                <div class="player-card">
                    <?php if ($player['imagem']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($player['imagem']); ?>" class="player-image" alt="Imagem do Jogador" />
                    <?php else: ?>
                        <img src="../../../public/images/default-player.png" class="player-image" alt="Imagem do Jogador" />
                    <?php endif; ?>
                    <div class="player-details">
                        <strong>Nome:</strong> <?php echo htmlspecialchars($player['nome']); ?><br />
                        <strong>Posição:</strong> <?php echo htmlspecialchars($player['posicao']); ?><br />
                        <strong>Número:</strong> <?php echo htmlspecialchars($player['numero']); ?><br />
                        <strong>Gols:</strong> <?php echo htmlspecialchars($player['gols']); ?><br />
                        <strong>Assistências:</strong> <?php echo htmlspecialchars($player['assistencias']); ?><br />
                        <strong>Cartões Amarelos:</strong> <?php echo htmlspecialchars($player['cartoes_amarelos']); ?><br />
                        <strong>Cartões Vermelhos:</strong> <?php echo htmlspecialchars($player['cartoes_vermelhos']); ?><br />
                    </div>
                    <div class="player-actions">
                        <a href="editar_jogador.php?token=<?php echo htmlspecialchars($player['token']); ?>" class="btn btn-primary">Editar</a>
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmDeleteModal" data-token="<?php echo htmlspecialchars($player['token']); ?>">Excluir</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Não há jogadores para o time selecionado.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir este jogador?
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST" action="">
                    <input type="hidden" name="delete_token" id="deleteToken" value="" />
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Bootstrap e jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $('#confirmDeleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var token = button.data('token');

        var modal = $(this);
        modal.find('#deleteToken').val(token);
        modal.find('#deleteForm').attr('action', '?delete_token=' + encodeURIComponent(token));
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>