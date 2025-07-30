<?php
/**
 * Gerenciador de Times
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../includes/PermissionManager.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$permissionManager = getPermissionManager($pdo);

// Verificar permissão para gerenciar times
$permissionManager->requireAnyPermission(['create_team', 'edit_team', 'view_team']);

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(#281c3e, #0f051d);
            margin: 0;
            padding: 20px;
            color: #E0E0E0;
            min-height: 100vh;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            color: #7B1FA2;
        }

        .back-link {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .form-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #E1BEE7;
            padding-top: 5px;
        }

        .section-title i {
            color: #7B1FA2;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #E1BEE7;
            font-size: 1rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333333;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.2);
        }

        .form-group input::placeholder {
            color: #9E9E9E;
        }

        .form-group select option {
            background: #2A2A2A;
            color: #E0E0E0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .btn-standard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-primary {
            border-color: #7B1FA2;
            color: #E1BEE7;
        }

        .btn-success {
            border-color: #4CAF50;
            color: #66BB6A;
        }

        .btn-success:hover {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-danger {
            border-color: #F44336;
            color: #EF5350;
        }

        .btn-danger:hover {
            background: #F44336;
            border-color: #F44336;
        }

        .btn-warning {
            border-color: #FFC107;
            color: #FFD54F;
        }

        .btn-warning:hover {
            background: #FFC107;
            border-color: #FFC107;
            color: #1E1E1E;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-left-color: #4CAF50;
            color: #66BB6A;
        }

        .alert-success::before {
            background: linear-gradient(90deg, #4CAF50, #81C784);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-left-color: #F44336;
            color: #EF5350;
        }

        .alert-error::before {
            background: linear-gradient(90deg, #F44336, #EF5350);
        }

        .groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 25px;
        }

        .group-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .group-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .group-card:hover {
            transform: translateY(-5px);
            background: #252525;
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .group-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #E1BEE7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 5px;
        }

        .group-status {
            font-size: 0.9rem;
            font-weight: 500;
            color: #E0E0E0;
            opacity: 0.9;
        }

        .group-card.group-full {
            border-left-color: #4CAF50;
        }

        .group-card.group-full::before {
            background: linear-gradient(90deg, #4CAF50, #81C784);
        }

        .group-card.group-partial {
            border-left-color: #FFC107;
        }

        .group-card.group-partial::before {
            background: linear-gradient(90deg, #FFC107, #FFD54F);
        }

        .group-card.group-empty {
            border-left-color: #2196F3;
        }

        .group-card.group-empty::before {
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .team-item {
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .team-item:hover {
            background: #333333;
            border-color: #E1BEE7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.2);
        }

        .team-info {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .team-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #E1BEE7;
        }

        .team-stats {
            font-size: 0.9rem;
            color: #9E9E9E;
            margin-top: 5px;
        }

        .team-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .logo-preview {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #7B1FA2;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9E9E9E;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #7B1FA2;
            opacity: 0.6;
        }

        .empty-state h3 {
            color: #E1BEE7;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .empty-state p {
            font-size: 1rem;
            line-height: 1.5;
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
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
        }

        /* Animações */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 25px 20px;
            }

            .page-header h1 {
                font-size: 1.8rem;
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .groups-container {
                grid-template-columns: 1fr;
                gap: 20px;
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

            .btn-standard {
                width: 100%;
                justify-content: center;
            }

            .form-section {
                padding: 25px 20px;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 1.6rem;
            }

            .groups-container {
                grid-template-columns: 1fr;
            }

            .group-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-users"></i> Gerenciar Times</h1>
                <p style="margin: 5px 0; color: #E0E0E0; opacity: 0.9; font-size: 1.1rem;"><?= htmlspecialchars($tournament['name']) ?></p>
                <div style="background: rgba(33, 150, 243, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px; border: 2px solid #2196F3; color: #64B5F6;">
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
        <div class="form-section fade-in">
            <h3 class="section-title"><i class="fas fa-plus-circle"></i> Adicionar Novo Time</h3>

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
                        <small style="color: #9E9E9E; margin-top: 8px; display: block; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> Limite: <?= $teams_per_group ?> times por grupo
                        </small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-standard btn-success">
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
            foreach ($grupos as $index => $grupo):
                $current_teams = count($times_por_grupo[$grupo['grupo_id']] ?? []);
                $is_full = $current_teams >= $teams_per_group;
                $status_class = $is_full ? 'group-full' : ($current_teams > 0 ? 'group-partial' : 'group-empty');
            ?>
                <div class="group-card <?= $status_class ?> fade-in" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="group-title">
                        <?= htmlspecialchars($grupo['grupo_nome']) ?>
                        <span class="group-status">
                            <?= $current_teams ?>/<?= $teams_per_group ?>
                            <?php if ($is_full): ?>
                                <i class="fas fa-check-circle" style="color: #4CAF50; margin-left: 8px;"></i>
                            <?php elseif ($current_teams > 0): ?>
                                <i class="fas fa-clock" style="color: #FFC107; margin-left: 8px;"></i>
                            <?php else: ?>
                                <i class="fas fa-plus-circle" style="color: #2196F3; margin-left: 8px;"></i>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (!empty($times_por_grupo[$grupo['grupo_id']])): ?>
                        <?php foreach ($times_por_grupo[$grupo['grupo_id']] as $time): ?>
                            <div class="team-item">
                                <div class="team-info">
                                    <?php if ($time['logo']): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($time['logo']) ?>"
                                             class="logo-preview" alt="Logo">
                                    <?php endif; ?>

                                    <div>
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
                                            class="btn-standard btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteTeam(<?= $time['id'] ?>, '<?= htmlspecialchars($time['nome']) ?>')"
                                            class="btn-standard btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="player_manager.php?tournament_id=<?= $tournament_id ?>&team_id=<?= $time['id'] ?>"
                                       class="btn-standard btn-warning btn-sm">
                                        <i class="fas fa-user"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>Nenhum time cadastrado</h3>
                            <p>Este grupo ainda não possui times cadastrados.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modal para Editar Time -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #E1BEE7; margin-bottom: 25px;"><i class="fas fa-edit"></i> Editar Time</h3>

            <form method="POST">
                <input type="hidden" name="action" value="edit_team">
                <input type="hidden" name="time_id" id="edit_time_id">

                <div class="form-group">
                    <label for="edit_nome">Nome do Time</label>
                    <input type="text" id="edit_nome" name="nome" required>
                </div>

                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" onclick="closeModal()" class="btn-standard" style="border-color: #666; color: #ccc;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-standard btn-success">
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
            if (confirm(`Tem certeza que deseja excluir o time "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_team">
                    <input type="hidden" name="time_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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
                alert('Este grupo está cheio. Selecione outro grupo.');
                return false;
            }

            if (selectedOption && selectedOption.text.includes('CHEIO')) {
                e.preventDefault();
                alert('Este grupo está cheio. Selecione outro grupo.');
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
                submitBtn.style.background = '#666';
                submitBtn.style.borderColor = '#666';
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar';
                submitBtn.style.background = '#1E1E1E';
                submitBtn.style.borderColor = '#4CAF50';
            }
        });

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover dinâmicos aos form-sections
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach(section => {
                section.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 10px 25px rgba(123, 31, 162, 0.2)';
                });

                section.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
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
