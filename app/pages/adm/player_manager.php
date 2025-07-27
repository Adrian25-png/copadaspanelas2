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
            max-width: 1400px;
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
        
        .add-player-form {
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
            grid-template-columns: 2fr 1fr 100px 1fr auto;
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
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        
        .teams-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }
        
        .team-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .team-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #f39c12;
            text-align: center;
        }
        
        .player-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .player-number {
            background: #3498db;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .player-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .player-info {
            flex: 1;
        }
        
        .player-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .player-position {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .player-stats {
            font-size: 0.8rem;
            opacity: 0.6;
            margin-top: 5px;
        }
        
        .player-actions {
            display: flex;
            gap: 8px;
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
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .team-filter {
            margin-bottom: 20px;
        }

        .image-upload-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        .current-image-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .current-image-preview img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .image-info {
            flex: 1;
        }

        .image-info h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            color: #3498db;
        }

        .image-info p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .teams-container {
                grid-template-columns: 1fr;
            }
            
            .player-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .player-actions {
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
                <h1><i class="fas fa-user"></i> Gerenciar Jogadores</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
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
        
        <!-- Filtro de Time -->
        <?php if (!$team_id): ?>
            <div class="team-filter">
                <label for="team-filter">Filtrar por time:</label>
                <select id="team-filter" onchange="filterByTeam()">
                    <option value="">Todos os times</option>
                    <?php foreach ($times as $time): ?>
                        <option value="<?= $time['id'] ?>"><?= htmlspecialchars($time['grupo_nome']) ?> - <?= htmlspecialchars($time['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <!-- Formulário para Adicionar Jogador -->
        <div class="add-player-form">
            <h2><i class="fas fa-user-plus"></i> Adicionar Novo Jogador</h2>
            
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
                        <button type="submit" class="btn btn-success">
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
            <?php foreach ($times as $time): ?>
                <div class="team-card" data-team-id="<?= $time['id'] ?>">
                    <div class="team-title">
                        <?= htmlspecialchars($time['grupo_nome']) ?> - <?= htmlspecialchars($time['nome']) ?>
                    </div>
                    
                    <?php if (!empty($jogadores_por_time[$time['id']])): ?>
                        <?php foreach ($jogadores_por_time[$time['id']] as $jogador): ?>
                            <div class="player-item">
                                <div class="player-number">
                                    <?= $jogador['numero'] ?: '?' ?>
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
                                            class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePlayer(<?= $jogador['id'] ?>, '<?= htmlspecialchars($jogador['nome']) ?>')"
                                            class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; opacity: 0.7; font-style: italic;">
                            Nenhum jogador cadastrado neste time
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modal para Editar Jogador -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Editar Jogador</h3>

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
    
    <!-- Modal para Confirmar Exclusão -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão</h3>
            
            <p>Tem certeza que deseja excluir o jogador <strong id="delete_player_name"></strong>?</p>
            <p style="color: #e74c3c;">Esta ação não pode ser desfeita.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="delete_player">
                <input type="hidden" name="jogador_id" id="delete_jogador_id">
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Excluir
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
            document.getElementById('delete_jogador_id').value = id;
            document.getElementById('delete_player_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        function filterByTeam() {
            const teamId = document.getElementById('team-filter').value;
            if (teamId) {
                window.location.href = `player_manager.php?tournament_id=<?= $tournament_id ?>&team_id=${teamId}`;
            } else {
                window.location.href = `player_manager.php?tournament_id=<?= $tournament_id ?>`;
            }
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
