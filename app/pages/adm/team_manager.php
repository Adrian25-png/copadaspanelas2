<?php
/**
 * Gerenciador de Times
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['tournament_id'] ?? null;
if (!$tournament_id) {
    $_SESSION['error'] = "ID do torneio não especificado";
    header('Location: tournament_list.php');
    exit;
}

$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    $_SESSION['error'] = "Torneio não encontrado";
    header('Location: tournament_list.php');
    exit;
}

// Processar ações
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_team':
                    $grupo_id = $_POST['grupo_id'];
                    $nome = trim($_POST['nome']);

                    if (empty($nome)) {
                        throw new Exception("Nome do time é obrigatório");
                    }

                    // Verificar limite de times por grupo
                    $teams_per_group = $tournament['teams_per_group'] ?? 4; // Default 4 se não configurado

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE grupo_id = ?");
                    $stmt->execute([$grupo_id]);
                    $current_teams_count = $stmt->fetchColumn();

                    if ($current_teams_count >= $teams_per_group) {
                        throw new Exception("Este grupo já atingiu o limite máximo de $teams_per_group times. Não é possível adicionar mais times.");
                    }

                    // Verificar se já existe um time com o mesmo nome no torneio
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE nome = ? AND tournament_id = ?");
                    $stmt->execute([$nome, $tournament_id]);
                    $name_exists = $stmt->fetchColumn();

                    if ($name_exists > 0) {
                        throw new Exception("Já existe um time com o nome '$nome' neste torneio.");
                    }

                    // Processar logo se enviada
                    $logo_data = null;
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                        $logo_data = file_get_contents($_FILES['logo']['tmp_name']);
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO times (nome, logo, grupo_id, tournament_id, pts, vitorias, empates, derrotas, gm, gc, sg, token)
                        VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, ?)
                    ");
                    $token = bin2hex(random_bytes(16));
                    $stmt->execute([$nome, $logo_data, $grupo_id, $tournament_id, $token]);

                    $tournamentManager->logActivity($tournament_id, 'TIME_ADICIONADO', "Time '$nome' adicionado");
                    $_SESSION['success'] = "Time '$nome' adicionado com sucesso!";
                    break;
                    
                case 'edit_team':
                    $time_id = $_POST['time_id'];
                    $nome = trim($_POST['nome']);
                    
                    if (empty($nome)) {
                        throw new Exception("Nome do time é obrigatório");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE times SET nome = ? WHERE id = ? AND tournament_id = ?");
                    $stmt->execute([$nome, $time_id, $tournament_id]);
                    
                    $tournamentManager->logActivity($tournament_id, 'TIME_EDITADO', "Time ID $time_id editado");
                    $_SESSION['success'] = "Time atualizado com sucesso!";
                    break;
                    
                case 'delete_team':
                    $time_id = $_POST['time_id'];
                    
                    // Verificar se o time tem jogadores
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogadores WHERE time_id = ?");
                    $stmt->execute([$time_id]);
                    $player_count = $stmt->fetchColumn();
                    
                    if ($player_count > 0) {
                        throw new Exception("Não é possível excluir um time que possui jogadores. Remova os jogadores primeiro.");
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM times WHERE id = ? AND tournament_id = ?");
                    $stmt->execute([$time_id, $tournament_id]);
                    
                    $tournamentManager->logActivity($tournament_id, 'TIME_REMOVIDO', "Time ID $time_id removido");
                    $_SESSION['success'] = "Time removido com sucesso!";
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: team_manager.php?tournament_id=$tournament_id");
    exit;
}

// Obter grupos e times
$stmt = $pdo->prepare("
    SELECT g.id as grupo_id, g.nome as grupo_nome
    FROM grupos g
    WHERE g.tournament_id = ?
    ORDER BY g.nome
");
$stmt->execute([$tournament_id]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$times_por_grupo = [];
foreach ($grupos as $grupo) {
    $stmt = $pdo->prepare("
        SELECT t.*, COUNT(j.id) as total_jogadores
        FROM times t
        LEFT JOIN jogadores j ON t.id = j.time_id
        WHERE t.grupo_id = ?
        GROUP BY t.id
        ORDER BY t.nome
    ");
    $stmt->execute([$grupo['grupo_id']]);
    $times_por_grupo[$grupo['grupo_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Times - <?= htmlspecialchars($tournament['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            color: white;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .add-team-form {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .group-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .group-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #f39c12;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .group-status {
            font-size: 0.9rem;
            font-weight: normal;
            opacity: 0.8;
        }

        .group-card.group-full {
            border: 2px solid #27ae60;
            background: rgba(39, 174, 96, 0.1);
        }

        .group-card.group-partial {
            border: 2px solid #f39c12;
            background: rgba(243, 156, 18, 0.1);
        }

        .group-card.group-empty {
            border: 2px solid #3498db;
            background: rgba(52, 152, 219, 0.1);
        }
        
        .team-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .team-info {
            flex: 1;
        }
        
        .team-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .team-stats {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .team-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        
        .logo-preview {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #2ecc71;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .groups-container {
                grid-template-columns: 1fr;
            }
            
            .team-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .team-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users"></i> Gerenciar Times</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
                <div style="background: rgba(52, 152, 219, 0.2); padding: 10px; border-radius: 8px; margin-top: 10px; border: 1px solid #3498db;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Configuração:</strong> <?= count($grupos) ?> grupos com máximo de <?= $tournament['teams_per_group'] ?? 4 ?> times cada
                    <span style="margin-left: 15px;">
                        <i class="fas fa-calculator"></i>
                        Total máximo: <?= count($grupos) * ($tournament['teams_per_group'] ?? 4) ?> times
                    </span>
                </div>
            </div>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Formulário para Adicionar Time -->
        <div class="add-team-form">
            <h2><i class="fas fa-plus-circle"></i> Adicionar Novo Time</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_team">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome do Time</label>
                        <input type="text" id="nome" name="nome" placeholder="Ex: Águias FC" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="grupo_id">Grupo</label>
                        <select id="grupo_id" name="grupo_id" required>
                            <option value="">Selecione um grupo</option>
                            <?php
                            $teams_per_group = $tournament['teams_per_group'] ?? 4;
                            foreach ($grupos as $grupo):
                                $current_teams = count($times_por_grupo[$grupo['grupo_id']] ?? []);
                                $is_full = $current_teams >= $teams_per_group;
                                $status_text = $is_full ? " (CHEIO - $current_teams/$teams_per_group)" : " ($current_teams/$teams_per_group)";
                            ?>
                                <option value="<?= $grupo['grupo_id'] ?>" <?= $is_full ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($grupo['grupo_nome']) ?><?= $status_text ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #ccc; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Limite: <?= $teams_per_group ?> times por grupo
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="logo">Logo do Time (opcional)</label>
                    <input type="file" id="logo" name="logo" accept="image/*">
                </div>
            </form>
        </div>
        
        <!-- Lista de Times por Grupo -->
        <div class="groups-container">
            <?php
            $teams_per_group = $tournament['teams_per_group'] ?? 4;
            foreach ($grupos as $grupo):
                $current_teams = count($times_por_grupo[$grupo['grupo_id']] ?? []);
                $is_full = $current_teams >= $teams_per_group;
                $status_class = $is_full ? 'group-full' : ($current_teams > 0 ? 'group-partial' : 'group-empty');
            ?>
                <div class="group-card <?= $status_class ?>">
                    <div class="group-title">
                        <?= htmlspecialchars($grupo['grupo_nome']) ?>
                        <span class="group-status">
                            <?= $current_teams ?>/<?= $teams_per_group ?>
                            <?php if ($is_full): ?>
                                <i class="fas fa-check-circle" style="color: #27ae60; margin-left: 5px;"></i>
                            <?php elseif ($current_teams > 0): ?>
                                <i class="fas fa-clock" style="color: #f39c12; margin-left: 5px;"></i>
                            <?php else: ?>
                                <i class="fas fa-plus-circle" style="color: #3498db; margin-left: 5px;"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($times_por_grupo[$grupo['grupo_id']])): ?>
                        <?php foreach ($times_por_grupo[$grupo['grupo_id']] as $time): ?>
                            <div class="team-item">
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <?php if ($time['logo']): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($time['logo']) ?>" 
                                             class="logo-preview" alt="Logo">
                                    <?php endif; ?>
                                    
                                    <div class="team-info">
                                        <div class="team-name"><?= htmlspecialchars($time['nome']) ?></div>
                                        <div class="team-stats">
                                            <?= $time['total_jogadores'] ?> jogadores | 
                                            <?= $time['pts'] ?> pts | 
                                            <?= $time['vitorias'] ?>V <?= $time['empates'] ?>E <?= $time['derrotas'] ?>D
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="team-actions">
                                    <button onclick="editTeam(<?= $time['id'] ?>, '<?= htmlspecialchars($time['nome']) ?>')" 
                                            class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteTeam(<?= $time['id'] ?>, '<?= htmlspecialchars($time['nome']) ?>')" 
                                            class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="player_manager.php?tournament_id=<?= $tournament_id ?>&team_id=<?= $time['id'] ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-user"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; opacity: 0.7; font-style: italic;">
                            Nenhum time cadastrado neste grupo
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modal para Editar Time -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Editar Time</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="edit_team">
                <input type="hidden" name="time_id" id="edit_time_id">
                
                <div class="form-group">
                    <label for="edit_nome">Nome do Time</label>
                    <input type="text" id="edit_nome" name="nome" required>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    

    
    <script>
        function editTeam(id, name) {
            document.getElementById('edit_time_id').value = id;
            document.getElementById('edit_nome').value = name;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function deleteTeam(id, name) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_team">
                <input type="hidden" name="time_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Validação do formulário de adicionar time
        document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
            const grupoSelect = document.getElementById('grupo_id');
            const selectedOption = grupoSelect.options[grupoSelect.selectedIndex];

            if (selectedOption && selectedOption.disabled) {
                e.preventDefault();
                return false;
            }

            if (selectedOption && selectedOption.text.includes('CHEIO')) {
                e.preventDefault();
                return false;
            }
        });

        // Atualizar informações quando mudar o grupo selecionado
        document.getElementById('grupo_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const submitBtn = document.querySelector('button[type="submit"]');

            if (selectedOption && selectedOption.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-ban"></i> Grupo Cheio';
                submitBtn.style.background = '#95a5a6';
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar';
                submitBtn.style.background = '#27ae60';
            }
        });

        // Fechar modal clicando fora
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');

            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
