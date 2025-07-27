<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

// Advanced Tournament Wizard - Complete Setup Process
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$tournament_data = $_SESSION['tournament_wizard_advanced'] ?? [];

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

switch($step) {
    case 1:
        // Tournament Information
        if ($_POST) {
            $tournament_data['name'] = $_POST['tournament_name'];
            $tournament_data['year'] = $_POST['tournament_year'];
            $tournament_data['description'] = $_POST['description'];
            $_SESSION['tournament_wizard_advanced'] = $tournament_data;
            header('Location: ?step=2');
            exit;
        }
        break;

    case 2:
        // Tournament Configuration
        if ($_POST) {
            $tournament_data['num_groups'] = $_POST['num_groups'];
            $tournament_data['teams_per_group'] = $_POST['teams_per_group'];
            $tournament_data['final_phase'] = $_POST['final_phase'];
            $_SESSION['tournament_wizard_advanced'] = $tournament_data;
            header('Location: ?step=3');
            exit;
        }
        break;

    case 3:
        // Team Management
        if ($_POST) {
            if (isset($_POST['teams_data'])) {
                $tournament_data['teams'] = json_decode($_POST['teams_data'], true);
                $_SESSION['tournament_wizard_advanced'] = $tournament_data;
                header('Location: ?step=4');
                exit;
            }
        }
        break;

    case 4:
        // Player Management
        if ($_POST) {
            if (isset($_POST['players_data'])) {
                $tournament_data['players'] = json_decode($_POST['players_data'], true);
                $_SESSION['tournament_wizard_advanced'] = $tournament_data;
                header('Location: ?step=5');
                exit;
            }
        }
        break;

    case 5:
        // Review & Confirm
        if ($_POST && $_POST['action'] === 'confirm') {
            header('Location: ?step=6');
            exit;
        }
        break;

    case 6:
        // Tournament Creation
        try {
            // Create tournament using TournamentManager
            $tournament_id = $tournamentManager->createTournament(
                $tournament_data['name'],
                $tournament_data['year'],
                $tournament_data['description'],
                $tournament_data['num_groups'],
                $tournament_data['teams_per_group'],
                $tournament_data['final_phase']
            );

            // Add teams if provided
            if (!empty($tournament_data['teams'])) {
                $groups = [];
                $stmt = $pdo->prepare("SELECT * FROM grupos WHERE tournament_id = ? ORDER BY nome");
                $stmt->execute([$tournament_id]);
                $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $team_ids = [];
                foreach ($tournament_data['teams'] as $team_index => $team) {
                    if (!empty($team['name']) && isset($team['group_index'])) {
                        $group_id = $groups[$team['group_index']]['id'];

                        // Process logo if provided
                        $logo_data = null;
                        if (!empty($team['logo'])) {
                            // Remove data URL prefix if present
                            $logo_base64 = preg_replace('/^data:image\/[^;]+;base64,/', '', $team['logo']);
                            $logo_data = base64_decode($logo_base64);
                        }

                        $stmt = $pdo->prepare("
                            INSERT INTO times (nome, logo, grupo_id, tournament_id, pts, vitorias, empates, derrotas, gm, gc, sg, token)
                            VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, ?)
                        ");
                        $token = bin2hex(random_bytes(16));
                        $stmt->execute([$team['name'], $logo_data, $group_id, $tournament_id, $token]);
                        
                        $team_id = $pdo->lastInsertId();
                        $team_ids[$team_index] = $team_id;
                    }
                }

                // Add players if provided
                if (!empty($tournament_data['players'])) {
                    foreach ($tournament_data['players'] as $player) {
                        if (!empty($player['name']) && isset($player['team_index']) && isset($team_ids[$player['team_index']])) {
                            $team_id = $team_ids[$player['team_index']];
                            
                            // Process player image if provided
                            $image_data = null;
                            if (!empty($player['image'])) {
                                $image_base64 = preg_replace('/^data:image\/[^;]+;base64,/', '', $player['image']);
                                $image_data = base64_decode($image_base64);
                            }

                            $stmt = $pdo->prepare("
                                INSERT INTO jogadores (nome, posicao, numero, time_id, gols, assistencias, cartoes_amarelos, cartoes_vermelhos, token, imagem)
                                VALUES (?, ?, ?, ?, 0, 0, 0, 0, ?, ?)
                            ");
                            $player_token = bin2hex(random_bytes(16));
                            $stmt->execute([
                                $player['name'],
                                $player['position'] ?? '',
                                $player['number'] ?? null,
                                $team_id,
                                $player_token,
                                $image_data
                            ]);
                        }
                    }
                }
            }

            unset($_SESSION['tournament_wizard_advanced']);

            $_SESSION['success'] = "Torneio criado com sucesso com times e jogadores!";
            header('Location: tournament_list.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Erro ao criar torneio: " . $e->getMessage();
        }
        break;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistente Avançado de Criação de Torneio - Etapa <?= $step ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../../../public/css/tournament_wizard.css">
    <style>
        /* Estilos específicos para o wizard avançado */
        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 30px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
            padding: 10px 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .step-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: scale(1.1);
        }
        
        .step-item.completed {
            background: #27ae60;
            color: white;
        }
        
        .team-card, .player-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-preview, .player-image-preview {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .file-input-wrapper:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .players-section {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        
        .player-form {
            display: grid;
            grid-template-columns: 1fr 1fr 100px 1fr auto;
            gap: 10px;
            align-items: center;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .player-form {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            
            .step-indicator {
                flex-wrap: wrap;
            }
            
            .step-item {
                margin: 5px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1><i class="fas fa-trophy"></i> Assistente Avançado de Criação de Torneio</h1>
            <div class="step-indicator">
                <div class="step-item <?= $step >= 1 ? ($step == 1 ? 'active' : 'completed') : '' ?>">
                    <i class="fas fa-info-circle"></i> Informações
                </div>
                <div class="step-item <?= $step >= 2 ? ($step == 2 ? 'active' : 'completed') : '' ?>">
                    <i class="fas fa-cog"></i> Configuração
                </div>
                <div class="step-item <?= $step >= 3 ? ($step == 3 ? 'active' : 'completed') : '' ?>">
                    <i class="fas fa-users"></i> Times
                </div>
                <div class="step-item <?= $step >= 4 ? ($step == 4 ? 'active' : 'completed') : '' ?>">
                    <i class="fas fa-user"></i> Jogadores
                </div>
                <div class="step-item <?= $step >= 5 ? ($step == 5 ? 'active' : 'completed') : '' ?>">
                    <i class="fas fa-check"></i> Revisar
                </div>
                <div class="step-item <?= $step >= 6 ? 'active' : '' ?>">
                    <i class="fas fa-rocket"></i> Criar
                </div>
            </div>
        </div>

        <div class="step-content">
            <?php switch($step):
                case 1: ?>
                    <h2>Informações do Torneio</h2>
                    <form method="POST" class="wizard-form">
                        <div class="form-group">
                            <label for="tournament_name">Nome do Torneio</label>
                            <input type="text" id="tournament_name" name="tournament_name" 
                                   value="<?= htmlspecialchars($tournament_data['name'] ?? '') ?>" 
                                   placeholder="Ex: Copa das Panelas 2024" required>
                        </div>

                        <div class="form-group">
                            <label for="tournament_year">Ano</label>
                            <input type="number" id="tournament_year" name="tournament_year" 
                                   value="<?= $tournament_data['year'] ?? date('Y') ?>" 
                                   min="2020" max="2030" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Descrição</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Descrição opcional do torneio"><?= htmlspecialchars($tournament_data['description'] ?? '') ?></textarea>
                        </div>

                        <div class="button-group">
                            <a href="tournament_list.php" class="btn-back">Cancelar</a>
                            <button type="submit" class="btn-next">Próxima Etapa</button>
                        </div>
                    </form>
                    <?php break;

                case 2: ?>
                    <h2>Configuração do Torneio</h2>
                    <form method="POST" class="wizard-form">
                        <div class="form-group">
                            <label for="num_groups">Número de Grupos</label>
                            <select id="num_groups" name="num_groups" required>
                                <option value="1" <?= ($tournament_data['num_groups'] ?? '') == '1' ? 'selected' : '' ?>>1 Grupo</option>
                                <option value="2" <?= ($tournament_data['num_groups'] ?? '') == '2' ? 'selected' : '' ?>>2 Grupos</option>
                                <option value="4" <?= ($tournament_data['num_groups'] ?? '') == '4' ? 'selected' : '' ?>>4 Grupos</option>
                                <option value="6" <?= ($tournament_data['num_groups'] ?? '') == '6' ? 'selected' : '' ?>>6 Grupos</option>
                                <option value="8" <?= ($tournament_data['num_groups'] ?? '') == '8' ? 'selected' : '' ?>>8 Grupos</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="teams_per_group">Times por Grupo</label>
                            <select id="teams_per_group" name="teams_per_group" required>
                                <option value="3" <?= ($tournament_data['teams_per_group'] ?? '') == '3' ? 'selected' : '' ?>>3 Times</option>
                                <option value="4" <?= ($tournament_data['teams_per_group'] ?? '') == '4' ? 'selected' : '' ?>>4 Times</option>
                                <option value="5" <?= ($tournament_data['teams_per_group'] ?? '') == '5' ? 'selected' : '' ?>>5 Times</option>
                                <option value="6" <?= ($tournament_data['teams_per_group'] ?? '') == '6' ? 'selected' : '' ?>>6 Times</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="final_phase">Fase Final</label>
                            <select id="final_phase" name="final_phase" required>
                                <option value="final" <?= ($tournament_data['final_phase'] ?? '') == 'final' ? 'selected' : '' ?>>Final Direta</option>
                                <option value="semifinais" <?= ($tournament_data['final_phase'] ?? '') == 'semifinais' ? 'selected' : '' ?>>Semifinais</option>
                                <option value="quartas" <?= ($tournament_data['final_phase'] ?? '') == 'quartas' ? 'selected' : '' ?>>Quartas de Final</option>
                                <option value="oitavas" <?= ($tournament_data['final_phase'] ?? '') == 'oitavas' ? 'selected' : '' ?>>Oitavas de Final</option>
                            </select>
                        </div>

                        <div class="tournament-summary">
                            <h3>Resumo do Torneio</h3>
                            <p><strong>Total de times:</strong> <span id="total-teams">4</span></p>
                            <p><strong>Jogos na fase de grupos:</strong> <span id="group-matches">6</span></p>
                        </div>

                        <div class="button-group">
                            <a href="?step=1" class="btn-back">Anterior</a>
                            <button type="submit" class="btn-next">Próxima Etapa</button>
                        </div>
                    </form>
                    <?php break;

                case 3: ?>
                    <h2>Gerenciamento de Times</h2>
                    <div class="team-management">
                        <div class="setup-info">
                            <p>Configure <strong><?= $tournament_data['num_groups'] ?? 1 ?> grupos</strong> com <strong><?= $tournament_data['teams_per_group'] ?? 4 ?> times cada</strong></p>
                            <p>Total de times necessários: <strong id="total-teams-needed"><?= ($tournament_data['num_groups'] ?? 1) * ($tournament_data['teams_per_group'] ?? 4) ?></strong></p>
                        </div>

                        <div class="teams-container" id="teams-container">
                            <!-- Times serão gerados pelo JavaScript -->
                        </div>

                        <div class="team-actions">
                            <button type="button" class="btn btn-secondary" onclick="fillRandomTeams()">
                                <i class="fas fa-random"></i> Preencher com Nomes Aleatórios
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearAllTeams()">
                                <i class="fas fa-trash"></i> Limpar Todos
                            </button>
                            <button type="button" class="btn btn-primary" onclick="proceedWithTeams()">
                                <i class="fas fa-arrow-right"></i> Continuar para Jogadores
                            </button>
                            <button type="button" class="btn btn-success" onclick="skipPlayers()">
                                <i class="fas fa-forward"></i> Pular Jogadores
                            </button>
                        </div>

                        <div class="button-group">
                            <a href="?step=2" class="btn-back">Anterior</a>
                        </div>
                    </div>

                    <!-- Hidden form for team data submission -->
                    <form id="teams-form" method="POST" style="display: none;">
                        <input type="hidden" name="teams_data" id="teams-data-input">
                    </form>
                    <?php break;

                case 4: ?>
                    <h2>Gerenciamento de Jogadores</h2>
                    <div class="player-management">
                        <div class="setup-info">
                            <p>Adicione jogadores aos times criados (opcional)</p>
                            <p>Você pode adicionar quantos jogadores quiser para cada time</p>
                        </div>

                        <div class="teams-with-players" id="teams-with-players">
                            <!-- Times com jogadores serão gerados pelo JavaScript -->
                        </div>

                        <div class="player-actions">
                            <button type="button" class="btn btn-secondary" onclick="addPlayersToAllTeams()">
                                <i class="fas fa-users"></i> Adicionar Jogadores Básicos
                            </button>
                            <button type="button" class="btn btn-primary" onclick="proceedWithPlayers()">
                                <i class="fas fa-arrow-right"></i> Continuar
                            </button>
                        </div>

                        <div class="button-group">
                            <a href="?step=3" class="btn-back">Anterior</a>
                        </div>
                    </div>

                    <!-- Hidden form for player data submission -->
                    <form id="players-form" method="POST" style="display: none;">
                        <input type="hidden" name="players_data" id="players-data-input">
                    </form>
                    <?php break;

                case 5: ?>
                    <h2>Revisar e Confirmar</h2>
                    <div class="review-container">
                        <div class="review-section">
                            <h3>Informações do Torneio</h3>
                            <div class="review-item">
                                <label>Nome:</label>
                                <span><?= htmlspecialchars($tournament_data['name'] ?? '') ?></span>
                            </div>
                            <div class="review-item">
                                <label>Ano:</label>
                                <span><?= htmlspecialchars($tournament_data['year'] ?? '') ?></span>
                            </div>
                            <div class="review-item">
                                <label>Descrição:</label>
                                <span><?= htmlspecialchars($tournament_data['description'] ?? 'Nenhuma') ?></span>
                            </div>
                        </div>

                        <div class="review-section">
                            <h3>Configuração</h3>
                            <div class="review-item">
                                <label>Grupos:</label>
                                <span><?= $tournament_data['num_groups'] ?? 1 ?></span>
                            </div>
                            <div class="review-item">
                                <label>Times por Grupo:</label>
                                <span><?= $tournament_data['teams_per_group'] ?? 4 ?></span>
                            </div>
                            <div class="review-item">
                                <label>Fase Final:</label>
                                <span><?= ucfirst($tournament_data['final_phase'] ?? 'final') ?></span>
                            </div>
                        </div>

                        <?php if (!empty($tournament_data['teams'])): ?>
                            <div class="review-section">
                                <h3>Times Configurados</h3>
                                <div class="teams-review">
                                    <?php
                                    $teams_by_group = [];
                                    foreach ($tournament_data['teams'] as $team) {
                                        if (!empty($team['name'])) {
                                            $group_index = $team['group_index'] ?? 0;
                                            $teams_by_group[$group_index][] = $team;
                                        }
                                    }

                                    foreach ($teams_by_group as $group_index => $teams): ?>
                                        <div class="group-review">
                                            <h4>Grupo <?= chr(65 + $group_index) ?></h4>
                                            <ul>
                                                <?php foreach ($teams as $team): ?>
                                                    <li><?= htmlspecialchars($team['name']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($tournament_data['players'])): ?>
                            <div class="review-section">
                                <h3>Jogadores Configurados</h3>
                                <p>Total de jogadores: <strong><?= count($tournament_data['players']) ?></strong></p>
                            </div>
                        <?php endif; ?>

                        <div class="confirmation">
                            <div class="warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p><strong>Importante:</strong> Criar este torneio irá arquivar o torneio ativo atual.</p>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="confirm">
                                <div class="button-group">
                                    <a href="?step=4" class="btn-back">Anterior</a>
                                    <button type="submit" class="btn-create">
                                        <i class="fas fa-rocket"></i> Criar Torneio
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php break;

                case 6: ?>
                    <div class="creation-result">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <h3>Erro na Criação</h3>
                                <p><?= htmlspecialchars($_SESSION['error']) ?></p>
                                <a href="?step=5" class="btn btn-primary">Tentar Novamente</a>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php else: ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i>
                                <h3>Torneio Criado com Sucesso!</h3>
                                <p>Seu torneio foi criado e está pronto para uso.</p>
                                <script>
                                    setTimeout(function() {
                                        window.location.href = 'tournament_list.php';
                                    }, 2000);
                                </script>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php break;
            endswitch; ?>
        </div>
    </div>

    <script>
        // Calcular totais automaticamente
        function updateTournamentSummary() {
            const numGroups = parseInt(document.getElementById('num_groups')?.value || 1);
            const teamsPerGroup = parseInt(document.getElementById('teams_per_group')?.value || 4);
            
            const totalTeams = numGroups * teamsPerGroup;
            const matchesPerGroup = (teamsPerGroup * (teamsPerGroup - 1)) / 2;
            const totalGroupMatches = numGroups * matchesPerGroup;
            
            if (document.getElementById('total-teams')) {
                document.getElementById('total-teams').textContent = totalTeams;
            }
            if (document.getElementById('group-matches')) {
                document.getElementById('group-matches').textContent = totalGroupMatches;
            }
        }

        // Atualizar quando os selects mudarem
        document.addEventListener('DOMContentLoaded', function() {
            updateTournamentSummary();

            const selects = document.querySelectorAll('#num_groups, #teams_per_group');
            selects.forEach(select => {
                select.addEventListener('change', updateTournamentSummary);
            });

            // Inicializar times se estivermos na etapa 3
            if (document.getElementById('teams-container')) {
                initializeTeams();
            }

            // Inicializar jogadores se estivermos na etapa 4
            if (document.getElementById('teams-with-players')) {
                initializePlayers();
            }
        });

        // Gerenciamento de Times
        let teamsData = [];
        let playersData = [];

        function initializeTeams() {
            const numGroups = <?= $tournament_data['num_groups'] ?? 1 ?>;
            const teamsPerGroup = <?= $tournament_data['teams_per_group'] ?? 4 ?>;
            const container = document.getElementById('teams-container');

            container.innerHTML = '';
            teamsData = [];

            for (let groupIndex = 0; groupIndex < numGroups; groupIndex++) {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'group-section';
                groupDiv.innerHTML = `
                    <h3>Grupo ${String.fromCharCode(65 + groupIndex)}</h3>
                    <div class="teams-in-group" id="group-${groupIndex}">
                    </div>
                `;
                container.appendChild(groupDiv);

                for (let teamIndex = 0; teamIndex < teamsPerGroup; teamIndex++) {
                    const globalTeamIndex = groupIndex * teamsPerGroup + teamIndex;
                    createTeamCard(groupIndex, globalTeamIndex);
                }
            }
        }

        function createTeamCard(groupIndex, teamIndex) {
            const groupContainer = document.getElementById(`group-${groupIndex}`);
            const teamCard = document.createElement('div');
            teamCard.className = 'team-card';
            teamCard.innerHTML = `
                <div class="team-form">
                    <div class="team-basic-info">
                        <input type="text" placeholder="Nome do Time"
                               id="team-name-${teamIndex}"
                               onchange="updateTeamData(${teamIndex}, 'name', this.value)">

                        <div class="logo-upload">
                            <div class="file-input-wrapper">
                                <input type="file" accept="image/*"
                                       id="team-logo-${teamIndex}"
                                       onchange="handleLogoUpload(${teamIndex}, this)">
                                <div class="upload-text">
                                    <i class="fas fa-upload"></i>
                                    <span>Logo do Time</span>
                                </div>
                            </div>
                            <img id="logo-preview-${teamIndex}" class="logo-preview" style="display: none;">
                        </div>
                    </div>
                </div>
            `;
            groupContainer.appendChild(teamCard);

            // Inicializar dados do time
            teamsData[teamIndex] = {
                name: '',
                group_index: groupIndex,
                logo: ''
            };
        }

        function updateTeamData(teamIndex, field, value) {
            if (!teamsData[teamIndex]) {
                teamsData[teamIndex] = { group_index: Math.floor(teamIndex / <?= $tournament_data['teams_per_group'] ?? 4 ?>) };
            }
            teamsData[teamIndex][field] = value;
        }

        function handleLogoUpload(teamIndex, input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(`logo-preview-${teamIndex}`);
                    preview.src = e.target.result;
                    preview.style.display = 'block';

                    updateTeamData(teamIndex, 'logo', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }

        function fillRandomTeams() {
            const teamNames = [
                'Águias FC', 'Leões United', 'Tigres SC', 'Panteras FC', 'Lobos FC',
                'Dragões SC', 'Falcões United', 'Tubarões FC', 'Cobras SC', 'Ursos FC',
                'Raios FC', 'Trovões SC', 'Relâmpagos United', 'Tempestade FC', 'Furacão SC',
                'Estrelas FC', 'Cometas SC', 'Meteoros United', 'Galáxia FC', 'Cosmos SC'
            ];

            teamsData.forEach((team, index) => {
                if (index < teamNames.length) {
                    const nameInput = document.getElementById(`team-name-${index}`);
                    nameInput.value = teamNames[index];
                    updateTeamData(index, 'name', teamNames[index]);
                }
            });
        }

        function clearAllTeams() {
            teamsData.forEach((team, index) => {
                const nameInput = document.getElementById(`team-name-${index}`);
                const logoPreview = document.getElementById(`logo-preview-${index}`);
                const logoInput = document.getElementById(`team-logo-${index}`);

                nameInput.value = '';
                logoPreview.style.display = 'none';
                logoInput.value = '';

                updateTeamData(index, 'name', '');
                updateTeamData(index, 'logo', '');
            });
        }

        function proceedWithTeams() {
            // Filtrar apenas times com nome
            const validTeams = teamsData.filter(team => team.name && team.name.trim() !== '');

            if (validTeams.length === 0) {
                alert('Adicione pelo menos um time para continuar.');
                return;
            }

            document.getElementById('teams-data-input').value = JSON.stringify(teamsData);
            document.getElementById('teams-form').submit();
        }

        function skipPlayers() {
            // Ir direto para revisão
            document.getElementById('teams-data-input').value = JSON.stringify(teamsData);

            // Criar form temporário para pular jogadores
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?step=5';

            const teamsInput = document.createElement('input');
            teamsInput.type = 'hidden';
            teamsInput.name = 'teams_data';
            teamsInput.value = JSON.stringify(teamsData);

            const playersInput = document.createElement('input');
            playersInput.type = 'hidden';
            playersInput.name = 'players_data';
            playersInput.value = JSON.stringify([]);

            form.appendChild(teamsInput);
            form.appendChild(playersInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Gerenciamento de Jogadores
        function initializePlayers() {
            const teams = <?= json_encode($tournament_data['teams'] ?? []) ?>;
            const container = document.getElementById('teams-with-players');

            container.innerHTML = '';
            playersData = [];

            teams.forEach((team, teamIndex) => {
                if (team.name) {
                    createTeamPlayersSection(team, teamIndex);
                }
            });
        }

        function createTeamPlayersSection(team, teamIndex) {
            const container = document.getElementById('teams-with-players');
            const teamSection = document.createElement('div');
            teamSection.className = 'team-players-section';
            teamSection.innerHTML = `
                <div class="team-header">
                    <h3>${team.name}</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addPlayerToTeam(${teamIndex})">
                        <i class="fas fa-plus"></i> Adicionar Jogador
                    </button>
                </div>
                <div class="players-list" id="players-team-${teamIndex}">
                </div>
            `;
            container.appendChild(teamSection);
        }

        function addPlayerToTeam(teamIndex) {
            const playersList = document.getElementById(`players-team-${teamIndex}`);
            const playerIndex = playersData.length;

            const playerCard = document.createElement('div');
            playerCard.className = 'player-card';
            playerCard.innerHTML = `
                <div class="player-form">
                    <input type="text" placeholder="Nome do Jogador"
                           onchange="updatePlayerData(${playerIndex}, 'name', this.value)">
                    <input type="text" placeholder="Posição"
                           onchange="updatePlayerData(${playerIndex}, 'position', this.value)">
                    <input type="number" placeholder="Nº" min="1" max="99"
                           onchange="updatePlayerData(${playerIndex}, 'number', this.value)">
                    <div class="player-image-upload">
                        <input type="file" accept="image/*"
                               onchange="handlePlayerImageUpload(${playerIndex}, this)">
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removePlayer(${playerIndex})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            playersList.appendChild(playerCard);

            playersData[playerIndex] = {
                name: '',
                position: '',
                number: '',
                team_index: teamIndex,
                image: ''
            };
        }

        function updatePlayerData(playerIndex, field, value) {
            if (!playersData[playerIndex]) {
                playersData[playerIndex] = {};
            }
            playersData[playerIndex][field] = value;
        }

        function handlePlayerImageUpload(playerIndex, input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    updatePlayerData(playerIndex, 'image', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }

        function removePlayer(playerIndex) {
            playersData[playerIndex] = null;
            // Remover visualmente
            event.target.closest('.player-card').remove();
        }

        function addPlayersToAllTeams() {
            const teams = <?= json_encode($tournament_data['teams'] ?? []) ?>;
            teams.forEach((team, teamIndex) => {
                if (team.name) {
                    // Adicionar 3 jogadores básicos para cada time
                    for (let i = 0; i < 3; i++) {
                        addPlayerToTeam(teamIndex);
                    }
                }
            });
        }

        function proceedWithPlayers() {
            // Filtrar jogadores válidos
            const validPlayers = playersData.filter(player => player && player.name && player.name.trim() !== '');

            document.getElementById('players-data-input').value = JSON.stringify(validPlayers);
            document.getElementById('players-form').submit();
        }
    </script>
</body>
</html>
