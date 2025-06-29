<?php
include '../../config/conexao.php';
$pdo = conectar();
session_start();

// Captura a mensagem da sessão (se houver)
$mensagem = $_SESSION['mensagem'] ?? '';
$mensagem_tipo = $_SESSION['mensagem_tipo'] ?? '';
// Limpa as mensagens após capturar
unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);

// Função para gerar token CSRF
function gerarToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Tratamento de exclusão de time
if (isset($_POST['delete_token'])) {
    if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token inválido");
    }

    $token = $_POST['delete_token'];

    $sql = "SELECT id FROM times WHERE token = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && isset($result['id'])) {
        $id = $result['id'];
    
        try {
            // Excluir os jogos onde este time participou
            $deleteJogosSql = "DELETE FROM jogos_fase_grupos WHERE timeA_id = ? OR timeB_id = ?";
            $stmtDeleteJogos = $pdo->prepare($deleteJogosSql);
            $stmtDeleteJogos->execute([$id, $id]);
    
            // Excluir o time da tabela times
            $deleteSql = "DELETE FROM times WHERE id = ?";
            $stmtDelete = $pdo->prepare($deleteSql);
            $stmtDelete->execute([$id]);
    
            $_SESSION['mensagem'] = "Time e seus jogos relacionados foram excluídos com sucesso!";
            $_SESSION['mensagem_tipo'] = "sucesso";
    
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = "Erro ao excluir time e/ou jogos: " . $e->getMessage();
            $_SESSION['mensagem_tipo'] = "erro";
        }
    } else {
        $_SESSION['mensagem'] = "Token inválido ou time não encontrado.";
        $_SESSION['mensagem_tipo'] = "erro";
    }

    // Redireciona após exclusão
    header("Location: listar_times.php");
    exit;
}

// Carrega os times para exibir na tela
$sql = "SELECT t.id, t.nome, t.logo, t.token, g.nome AS grupo_nome 
        FROM times t 
        JOIN grupos g ON t.grupo_id = g.id 
        ORDER BY g.nome, t.nome";
$stmt = $pdo->query($sql);

$times = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $times[$row['grupo_nome']][] = $row;
}

$csrf_token = gerarToken();
$_SESSION['csrf_token'] = $csrf_token;
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Times</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../public/css/adm/cadastros_times_jogadores_adm/listar_times.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php require_once 'header_adm.php' ?>

    <h1 class="text-center fade-in">LISTA DE TIMES</h1>

    <!-- Mensagem de sucesso/erro (exibida no topo após exclusão) -->
    <?php if (!empty($mensagem)) : ?>
        <div class="mensagem <?php echo $mensagem_tipo === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <div class="container fade-in">
        <div class="row">
        <?php foreach ($times as $grupo_nome => $timesGrupo): ?>
            <div class="col-md-3">
                <h3 id="nome_grupo"><?php echo htmlspecialchars($grupo_nome); ?></h3>
                <?php foreach ($timesGrupo as $time): ?>
                    <div class="time-card">
                        <?php if ($time['logo']): ?>
                            <img src="data:image/png;base64,<?php echo base64_encode($time['logo']); ?>" class="time-image" alt="Logo do Time">
                        <?php else: ?>
                            <img src="../../../public/images/default-team.png" class="time-image" alt="Logo do Time">
                        <?php endif; ?>
                        <div class="time-details">
                            <strong>Nome:</strong> <?php echo htmlspecialchars($time['nome']); ?><br>
                            <strong>Grupo:</strong> <?php echo htmlspecialchars($time['grupo_nome']); ?><br>
                        </div>
                        <div class="time-actions">
                            <a href="#" class="delete" data-toggle="modal" data-target="#confirmDeleteModal" data-token="<?php echo htmlspecialchars($time['token']); ?>">Excluir</a>
                            <a href="editar_time.php?token=<?php echo $time['token']; ?>" class="edit">Editar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
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
                    Tem certeza que deseja excluir este time?
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="delete_token" id="delete_token" value="">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $('#confirmDeleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var token = button.data('token');
            var modal = $(this);
            modal.find('#delete_token').val(token);
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
    </script>
    <?php include '../footer.php'; ?>
</body>
</html>