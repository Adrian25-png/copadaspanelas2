<?php
/**
 * Gerenciador de Jogadores
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['tournament_id'] ?? null;
$team_id = $_GET['team_id'] ?? null;

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
                case 'add_player':
                    $time_id = $_POST['time_id'];
                    $nome = trim($_POST['nome']);
                    $posicao = trim($_POST['posicao']);
                    $numero = $_POST['numero'] ? (int)$_POST['numero'] : null;
                    
                    if (empty($nome)) {
                        throw new Exception("Nome do jogador é obrigatório");
                    }
                    
                    // Verificar se o número já existe no time
                    if ($numero) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogadores WHERE time_id = ? AND numero = ?");
                        $stmt->execute([$time_id, $numero]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception("Número $numero já está em uso neste time");
                        }
                    }
                    
                    // Processar imagem se enviada
                    $imagem_data = null;
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $imagem_data = file_get_contents($_FILES['imagem']['tmp_name']);
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO jogadores (nome, posicao, numero, time_id, gols, assistencias, cartoes_amarelos, cartoes_vermelhos, token, imagem)
                        VALUES (?, ?, ?, ?, 0, 0, 0, 0, ?, ?)
                    ");
                    $token = bin2hex(random_bytes(16));
                    $stmt->execute([$nome, $posicao, $numero, $time_id, $token, $imagem_data]);
                    
                    $tournamentManager->logActivity($tournament_id, 'JOGADOR_ADICIONADO', "Jogador '$nome' adicionado");
                    $_SESSION['success'] = "Jogador '$nome' adicionado com sucesso!";
                    break;
                    
                case 'edit_player':
                    $jogador_id = $_POST['jogador_id'];
                    $nome = trim($_POST['nome']);
                    $posicao = trim($_POST['posicao']);
                    $numero = $_POST['numero'] ? (int)$_POST['numero'] : null;

                    if (empty($nome)) {
                        throw new Exception("Nome do jogador é obrigatório");
                    }

                    // Verificar se o número já existe em outro jogador do mesmo time
                    if ($numero) {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM jogadores j
                            INNER JOIN times t ON j.time_id = t.id
                            WHERE j.time_id = (SELECT time_id FROM jogadores WHERE id = ?)
                            AND j.numero = ? AND j.id != ?
                        ");
                        $stmt->execute([$jogador_id, $numero, $jogador_id]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception("Número $numero já está em uso neste time");
                        }
                    }

                    // Processar nova imagem se enviada
                    $update_image = false;
                    $imagem_data = null;
                    if (isset($_FILES['edit_imagem']) && $_FILES['edit_imagem']['error'] === UPLOAD_ERR_OK) {
                        $imagem_data = file_get_contents($_FILES['edit_imagem']['tmp_name']);
                        $update_image = true;
                    }

                    if ($update_image) {
                        $stmt = $pdo->prepare("UPDATE jogadores SET nome = ?, posicao = ?, numero = ?, imagem = ? WHERE id = ?");
                        $stmt->execute([$nome, $posicao, $numero, $imagem_data, $jogador_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE jogadores SET nome = ?, posicao = ?, numero = ? WHERE id = ?");
                        $stmt->execute([$nome, $posicao, $numero, $jogador_id]);
                    }

                    $tournamentManager->logActivity($tournament_id, 'JOGADOR_EDITADO', "Jogador ID $jogador_id editado");
                    $_SESSION['success'] = "Jogador atualizado com sucesso!";
                    break;
                    
                case 'delete_player':
                    $jogador_id = $_POST['jogador_id'];
                    
                    $stmt = $pdo->prepare("DELETE FROM jogadores WHERE id = ?");
                    $stmt->execute([$jogador_id]);
                    
                    $tournamentManager->logActivity($tournament_id, 'JOGADOR_REMOVIDO', "Jogador ID $jogador_id removido");
                    $_SESSION['success'] = "Jogador removido com sucesso!";
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    $redirect_url = "player_manager.php?tournament_id=$tournament_id";
    if ($team_id) {
        $redirect_url .= "&team_id=$team_id";
    }
    header("Location: $redirect_url");
    exit;
}

// Obter times do torneio
$stmt = $pdo->prepare("
    SELECT t.id, t.nome, g.nome as grupo_nome
    FROM times t
    INNER JOIN grupos g ON t.grupo_id = g.id
    WHERE t.tournament_id = ?
    ORDER BY g.nome, t.nome
");
$stmt->execute([$tournament_id]);
$times = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter jogadores
$jogadores_por_time = [];
foreach ($times as $time) {
    $stmt = $pdo->prepare("
        SELECT * FROM jogadores 
        WHERE time_id = ? 
        ORDER BY numero IS NULL, numero, nome
    ");
    $stmt->execute([$time['id']]);
    $jogadores_por_time[$time['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Se um time específico foi selecionado, filtrar
if ($team_id) {
    $times = array_filter($times, function($time) use ($team_id) {
        return $time['id'] == $team_id;
    });
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Jogadores - <?= htmlspecialchars($tournament['name']) ?></title>
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
            max-width: 1400px;
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
            grid-template-columns: 2fr 1fr 100px 1fr auto;
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

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
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

        .teams-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(520px, 1fr));
            gap: 25px;
        }

        .team-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .team-card:hover {
            transform: translateY(-5px);
            background: #252525;
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .team-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #E1BEE7;
            text-align: center;
            padding-top: 5px;
        }

        .player-item {
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .player-item:hover {
            background: #333333;
            border-color: #E1BEE7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.2);
        }

        .player-number {
            background: #7B1FA2;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid #E1BEE7;
        }

        .player-number.no-number {
            background: #666;
            border-color: #999;
            font-size: 0.8rem;
        }

        .player-photo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #7B1FA2;
        }

        .player-info {
            flex: 1;
        }

        .player-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #E1BEE7;
        }

        .player-position {
            font-size: 0.9rem;
            color: #9E9E9E;
            margin-bottom: 3px;
        }

        .player-stats {
            font-size: 0.8rem;
            color: #757575;
            margin-top: 5px;
        }

        .player-actions {
            display: flex;
            gap: 10px;
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
            max-height: 90vh;
            overflow-y: auto;
        }

        .team-filter {
            margin-bottom: 25px;
        }

        .image-upload-section {
            background: rgba(123, 31, 162, 0.1);
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }

        .current-image-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .current-image-preview img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #7B1FA2;
        }

        .image-info {
            flex: 1;
        }

        .image-info h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            color: #E1BEE7;
        }

        .image-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #9E9E9E;
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
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .teams-container {
                grid-template-columns: 1fr;
            }

            .player-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .player-actions {
                width: 100%;
                justify-content: center;
                gap: 15px;
            }

            .btn-standard {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }

            .main-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .teams-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .player-actions {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-users"></i> Gerenciar Jogadores</h1>
                <p style="margin: 10px 0; color: #9E9E9E; font-size: 1.1rem;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Filtro de Time -->
        <?php if (!$team_id): ?>
            <div class="form-section fade-in" style="animation-delay: 0.2s;">
                <div class="section-title">
                    <i class="fas fa-filter"></i>
                    Filtrar por Time
                </div>
                <div class="form-group">
                    <label for="team-filter">Selecione um time para filtrar:</label>
                    <select id="team-filter" onchange="filterByTeam()">
                        <option value="">Todos os times</option>
                        <?php foreach ($times as $time): ?>
                            <option value="<?= $time['id'] ?>"><?= htmlspecialchars($time['grupo_nome']) ?> - <?= htmlspecialchars($time['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulário para Adicionar Jogador -->
        <div class="form-section fade-in" style="animation-delay: 0.4s;">
            <div class="section-title">
                <i class="fas fa-user-plus"></i>
                Adicionar Novo Jogador
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_player">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome do Jogador</label>
                        <input type="text" id="nome" name="nome" placeholder="Ex: João Silva" required>
                    </div>

                    <div class="form-group">
                        <label for="posicao">Posição</label>
                        <select id="posicao" name="posicao">
                            <option value="">Selecione</option>
                            <option value="Goleiro">Goleiro</option>
                            <option value="Defesa">Defesa</option>
                            <option value="Meio-campo">Meio-campo</option>
                            <option value="Atacante">Atacante</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="numero">Número</label>
                        <input type="number" id="numero" name="numero" min="1" max="99" placeholder="10">
                    </div>

                    <div class="form-group">
                        <label for="time_id">Time</label>
                        <select id="time_id" name="time_id" required>
                            <option value="">Selecione um time</option>
                            <?php foreach ($times as $time): ?>
                                <option value="<?= $time['id'] ?>" <?= $team_id == $time['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($time['grupo_nome']) ?> - <?= htmlspecialchars($time['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-standard btn-success">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagem">Foto do Jogador (opcional)</label>
                    <input type="file" id="imagem" name="imagem" accept="image/*">
                </div>
            </form>
        </div>

        <!-- Lista de Jogadores por Time -->
        <div class="teams-container">
            <?php foreach ($times as $index => $time): ?>
                <div class="team-card fade-in" data-team-id="<?= $time['id'] ?>" style="animation-delay: <?= ($index + 2) * 0.2 ?>s;">
                    <div class="team-title">
                        <?= htmlspecialchars($time['grupo_nome']) ?> - <?= htmlspecialchars($time['nome']) ?>
                    </div>

                    <?php if (!empty($jogadores_por_time[$time['id']])): ?>
                        <?php foreach ($jogadores_por_time[$time['id']] as $jogador): ?>
                            <div class="player-item">
                                <div class="player-number <?= !$jogador['numero'] ? 'no-number' : '' ?>">
                                    <?= $jogador['numero'] ?: 'S/N' ?>
                                </div>

                                <?php if ($jogador['imagem']): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($jogador['imagem']) ?>"
                                         class="player-photo" alt="Foto">
                                <?php endif; ?>

                                <div class="player-info">
                                    <div class="player-name"><?= htmlspecialchars($jogador['nome']) ?></div>
                                    <div class="player-position"><?= htmlspecialchars($jogador['posicao'] ?: 'Posição não definida') ?></div>
                                    <div class="player-stats">
                                        <?= $jogador['gols'] ?> gols | <?= $jogador['assistencias'] ?> assist. |
                                        <?= $jogador['cartoes_amarelos'] ?> CA | <?= $jogador['cartoes_vermelhos'] ?> CV
                                    </div>
                                </div>

                                <div class="player-actions">
                                    <button onclick="editPlayer(<?= $jogador['id'] ?>, '<?= htmlspecialchars($jogador['nome']) ?>', '<?= htmlspecialchars($jogador['posicao']) ?>', <?= $jogador['numero'] ?: 'null' ?>, <?= $jogador['imagem'] ? "'data:image/jpeg;base64," . base64_encode($jogador['imagem']) . "'" : 'null' ?>)"
                                            class="btn-standard btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePlayer(<?= $jogador['id'] ?>, '<?= htmlspecialchars($jogador['nome']) ?>')"
                                            class="btn-standard btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-plus"></i>
                            <h3>Nenhum jogador cadastrado</h3>
                            <p>Este time ainda não possui jogadores cadastrados.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para Editar Jogador -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #E1BEE7; margin-bottom: 25px;"><i class="fas fa-edit"></i> Editar Jogador</h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_player">
                <input type="hidden" name="jogador_id" id="edit_jogador_id">

                <div class="form-group">
                    <label for="edit_nome">Nome do Jogador</label>
                    <input type="text" id="edit_nome" name="nome" required>
                </div>

                <div class="form-group">
                    <label for="edit_posicao">Posição</label>
                    <select id="edit_posicao" name="posicao">
                        <option value="">Selecione</option>
                        <option value="Goleiro">Goleiro</option>
                        <option value="Defesa">Defesa</option>
                        <option value="Meio-campo">Meio-campo</option>
                        <option value="Atacante">Atacante</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_numero">Número</label>
                    <input type="number" id="edit_numero" name="numero" min="1" max="99">
                </div>

                <div class="form-group">
                    <label>Foto do Jogador</label>
                    <div class="image-upload-section">
                        <!-- Imagem atual -->
                        <div id="current_image_section" class="current-image-preview" style="display: none;">
                            <img id="current_player_image" alt="Foto atual">
                            <div class="image-info">
                                <h4>Foto Atual</h4>
                                <p>Selecione uma nova foto para substituir</p>
                            </div>
                        </div>

                        <!-- Upload de nova imagem -->
                        <div>
                            <label for="edit_imagem">Nova Foto (opcional)</label>
                            <input type="file" id="edit_imagem" name="edit_imagem" accept="image/*" onchange="previewEditImage(this)">
                        </div>

                        <!-- Preview da nova imagem -->
                        <div id="edit_image_preview" style="margin-top: 15px; display: none;">
                            <div class="current-image-preview">
                                <img id="edit_preview_img" alt="Nova foto">
                                <div class="image-info">
                                    <h4>Nova Foto</h4>
                                    <p>Esta será a nova foto do jogador</p>
                                </div>
                            </div>
                        </div>

                        <small style="opacity: 0.7; display: block; margin-top: 10px;">
                            Deixe em branco para manter a foto atual
                        </small>
                    </div>
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
        function editPlayer(id, name, position, number, currentImage) {
            document.getElementById('edit_jogador_id').value = id;
            document.getElementById('edit_nome').value = name;
            document.getElementById('edit_posicao').value = position;
            document.getElementById('edit_numero').value = number || '';

            // Limpar preview de nova imagem
            document.getElementById('edit_image_preview').style.display = 'none';
            document.getElementById('edit_imagem').value = '';

            // Mostrar imagem atual se existir
            const currentImageSection = document.getElementById('current_image_section');
            const currentPlayerImage = document.getElementById('current_player_image');

            if (currentImage && currentImage !== 'null') {
                currentPlayerImage.src = currentImage;
                currentImageSection.style.display = 'flex';
            } else {
                currentImageSection.style.display = 'none';
            }

            document.getElementById('editModal').style.display = 'block';
        }

        function previewEditImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit_preview_img').src = e.target.result;
                    document.getElementById('edit_image_preview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                document.getElementById('edit_image_preview').style.display = 'none';
            }
        }
        
        function deletePlayer(id, name) {
            if (confirm(`Tem certeza que deseja excluir o jogador "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_player">
                    <input type="hidden" name="jogador_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function filterByTeam() {
            const teamId = document.getElementById('team-filter').value;
            if (teamId) {
                window.location.href = `player_manager.php?tournament_id=<?= $tournament_id ?>&team_id=${teamId}`;
            } else {
                window.location.href = `player_manager.php?tournament_id=<?= $tournament_id ?>`;
            }
        }

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
