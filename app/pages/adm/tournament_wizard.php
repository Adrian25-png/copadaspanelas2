<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

// Tournament Wizard - Unified Setup Process
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$tournament_data = $_SESSION['tournament_wizard'] ?? [];

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

switch($step) {
    case 1:
        // Tournament Information
        if ($_POST) {
            $tournament_data['name'] = $_POST['tournament_name'];
            $tournament_data['year'] = $_POST['tournament_year'];
            $tournament_data['description'] = $_POST['description'];
            $_SESSION['tournament_wizard'] = $tournament_data;
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
            $_SESSION['tournament_wizard'] = $tournament_data;
            header('Location: ?step=3');
            exit;
        }
        break;

    case 3:
        // Team Management
        if ($_POST) {
            if (isset($_POST['teams_data'])) {
                $tournament_data['teams'] = json_decode($_POST['teams_data'], true);
                $_SESSION['tournament_wizard'] = $tournament_data;
                header('Location: ?step=4');
                exit;
            }
        }
        break;

    case 4:
        // Review & Confirm
        if ($_POST && $_POST['action'] === 'confirm') {
            header('Location: ?step=5');
            exit;
        }
        break;

    case 5:
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

                foreach ($tournament_data['teams'] as $team) {
                    if (!empty($team['name']) && !empty($team['group_index'])) {
                        $group_id = $groups[$team['group_index']]['id'];

                        // Process logo if provided
                        $logo_data = null;
                        if (!empty($team['logo'])) {
                            $logo_data = base64_decode($team['logo']);
                        }

                        $stmt = $pdo->prepare("
                            INSERT INTO times (nome, logo, grupo_id, tournament_id, pts, vitorias, empates, derrotas, gm, gc, sg, token)
                            VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, ?)
                        ");
                        $token = bin2hex(random_bytes(16));
                        $stmt->execute([$team['name'], $logo_data, $group_id, $tournament_id, $token]);
                    }
                }
            }

            unset($_SESSION['tournament_wizard']);

            $_SESSION['success'] = "Torneio criado com sucesso!";
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
    <title>Assistente de Criação de Torneio - Etapa <?= $step ?></title>
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="../../../public/css/tournament_wizard.css">
</head>
<body>
    <?php require_once 'header_adm.php'; ?>
    
    <div class="wizard-container">
        <!-- Progress Bar -->
        <div class="progress-bar">
            <?php for($i = 1; $i <= 5; $i++): ?>
                <div class="step <?= $i <= $step ? 'active' : '' ?> <?= $i < $step ? 'completed' : '' ?>">
                    <span class="step-number"><?= $i ?></span>
                    <span class="step-title">
                        <?= ['', 'Informações', 'Configuração', 'Times', 'Revisão', 'Criar'][$i] ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>
        
        <!-- Step Content -->
        <div class="step-content">
            <?php switch($step): 
                case 1: ?>
                    <h2>Informações do Torneio</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="tournament_name">Nome do Torneio:</label>
                            <input type="text" id="tournament_name" name="tournament_name"
                                   value="<?= htmlspecialchars($tournament_data['name'] ?? '') ?>"
                                   placeholder="Ex: Copa das Panelas 2024" required>
                        </div>

                        <div class="form-group">
                            <label for="tournament_year">Ano:</label>
                            <input type="number" id="tournament_year" name="tournament_year"
                                   value="<?= $tournament_data['year'] ?? date('Y') ?>"
                                   min="2020" max="2030" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Descrição (opcional):</label>
                            <textarea id="description" name="description" rows="3"
                                      placeholder="Descreva o torneio..."><?= htmlspecialchars($tournament_data['description'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn-next">Próxima Etapa</button>
                    </form>
                    <?php break;
                    
                case 2: ?>
                    <h2>Configuração do Torneio</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="num_groups">Número de Grupos:</label>
                            <select id="num_groups" name="num_groups" required>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($tournament_data['num_groups'] ?? '') == $i ? 'selected' : '' ?>>
                                        <?= $i ?> Grupo<?= $i > 1 ? 's' : '' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="teams_per_group">Times por Grupo:</label>
                            <select id="teams_per_group" name="teams_per_group" required>
                                <?php for($i = 2; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($tournament_data['teams_per_group'] ?? '') == $i ? 'selected' : '' ?>>
                                        <?= $i ?> Times
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="final_phase">Fase Final:</label>
                            <select id="final_phase" name="final_phase" required>
                                <option value="oitavas" <?= ($tournament_data['final_phase'] ?? '') == 'oitavas' ? 'selected' : '' ?>>Oitavas de Final</option>
                                <option value="quartas" <?= ($tournament_data['final_phase'] ?? '') == 'quartas' ? 'selected' : '' ?>>Quartas de Final</option>
                                <option value="semifinais" <?= ($tournament_data['final_phase'] ?? '') == 'semifinais' ? 'selected' : '' ?>>Semifinais</option>
                                <option value="final" <?= ($tournament_data['final_phase'] ?? '') == 'final' ? 'selected' : '' ?>>Apenas Final</option>
                            </select>
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
                        <div class="management-options">
                            <div class="option-tabs">
                                <button type="button" class="tab-btn active" onclick="showTeamTab('manual')">
                                    <i class="fas fa-edit"></i> Entrada Manual
                                </button>
                                <button type="button" class="tab-btn" onclick="showTeamTab('skip')">
                                    <i class="fas fa-forward"></i> Pular (Adicionar Depois)
                                </button>
                            </div>

                            <!-- Manual Entry Tab -->
                            <div id="manual-team-tab" class="team-tab-content active">
                                <div class="teams-setup">
                                    <div class="setup-info">
                                        <p>Configure <strong><?= $tournament_data['num_groups'] ?? 1 ?> grupos</strong> com <strong><?= $tournament_data['teams_per_group'] ?? 4 ?> times cada</strong></p>
                                        <p>Total de times necessários: <strong><?= ($tournament_data['num_groups'] ?? 1) * ($tournament_data['teams_per_group'] ?? 4) ?></strong></p>
                                    </div>

                                    <div class="groups-container" id="groups-container">
                                        <!-- Groups will be generated by JavaScript -->
                                    </div>

                                    <div class="team-actions">
                                        <button type="button" class="btn btn-secondary" onclick="fillRandomTeams()">
                                            <i class="fas fa-random"></i> Preencher com Nomes Aleatórios
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="proceedWithTeams()">
                                            <i class="fas fa-arrow-right"></i> Continuar com Times
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Skip Tab -->
                            <div id="skip-team-tab" class="team-tab-content">
                                <div class="skip-option">
                                    <i class="fas fa-info-circle"></i>
                                    <h3>Pular Configuração de Times</h3>
                                    <p>Você pode adicionar times depois pelo painel do torneio. O torneio será criado com grupos vazios.</p>
                                    <button type="button" class="btn btn-primary" onclick="skipTeams()">
                                        <i class="fas fa-forward"></i> Pular e Continuar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="button-group">
                            <a href="?step=2" class="btn-back">Previous</a>
                        </div>
                    </div>

                    <!-- Hidden form for team data submission -->
                    <form id="teams-form" method="POST" style="display: none;">
                        <input type="hidden" name="teams_data" id="teams-data-input">
                    </form>
                    <?php break;

                case 4: ?>
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
                        </div>

                        <div class="confirmation">
                            <div class="warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p><strong>Importante:</strong> Criar este torneio irá arquivar o torneio ativo atual.</p>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="confirm">
                                <div class="button-group">
                                    <a href="?step=3" class="btn-back">Anterior</a>
                                    <button type="submit" class="btn-create">
                                        <i class="fas fa-plus-circle"></i> Criar Torneio
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php break;

                case 5: ?>
                    <div class="creation-status">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <h3>Erro ao Criar Torneio</h3>
                                <p><?= htmlspecialchars($_SESSION['error']) ?></p>
                                <a href="?step=4" class="btn btn-primary">Try Again</a>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php else: ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i>
                                <h3>Tournament Created Successfully!</h3>
                                <p>Redirecting to dashboard...</p>
                            </div>
                            <script>
                                setTimeout(function() {
                                    window.location.href = 'tournament_list.php';
                                }, 2000);
                            </script>
                        <?php endif; ?>
                    </div>
                    <?php break;

                default: ?>
                    <p>Invalid step. <a href="?step=1">Start over</a></p>
            <?php endswitch; ?>
        </div>
    </div>

    <script>
        // Tournament Wizard JavaScript
        const tournamentData = <?= json_encode($tournament_data) ?>;

        function showTeamTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.team-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-team-tab').classList.add('active');
            event.target.classList.add('active');
        }

        function generateGroupsInterface() {
            const container = document.getElementById('groups-container');
            if (!container) return;

            const numGroups = tournamentData.num_groups || 1;
            const teamsPerGroup = tournamentData.teams_per_group || 4;

            container.innerHTML = '';

            for (let g = 0; g < numGroups; g++) {
                const groupLetter = String.fromCharCode(65 + g); // A, B, C, etc.

                const groupDiv = document.createElement('div');
                groupDiv.className = 'group-setup';
                groupDiv.innerHTML = `
                    <h4>Grupo ${groupLetter}</h4>
                    <div class="group-teams">
                        ${Array.from({length: teamsPerGroup}, (_, i) => `
                            <div class="team-input">
                                <input type="text"
                                       placeholder="Team ${i + 1} name"
                                       data-group="${g}"
                                       data-team="${i}"
                                       class="team-name-input">
                            </div>
                        `).join('')}
                    </div>
                `;

                container.appendChild(groupDiv);
            }
        }

        function fillRandomTeams() {
            const teamNames = [
                'Flamengo', 'Corinthians', 'Palmeiras', 'São Paulo', 'Santos', 'Vasco',
                'Botafogo', 'Fluminense', 'Grêmio', 'Internacional', 'Cruzeiro', 'Atlético-MG',
                'Bahia', 'Sport', 'Ceará', 'Fortaleza', 'Goiás', 'Atlético-GO',
                'Coritiba', 'Athletico-PR', 'Chapecoense', 'Avaí', 'Figueirense', 'Joinville'
            ];

            const inputs = document.querySelectorAll('.team-name-input');
            inputs.forEach((input, index) => {
                if (index < teamNames.length) {
                    input.value = teamNames[index];
                } else {
                    input.value = `Team ${index + 1}`;
                }
            });
        }

        function proceedWithTeams() {
            const teams = [];
            const inputs = document.querySelectorAll('.team-name-input');

            inputs.forEach(input => {
                const name = input.value.trim();
                if (name) {
                    teams.push({
                        name: name,
                        group_index: parseInt(input.dataset.group)
                    });
                }
            });

            document.getElementById('teams-data-input').value = JSON.stringify(teams);
            document.getElementById('teams-form').submit();
        }

        function skipTeams() {
            document.getElementById('teams-data-input').value = JSON.stringify([]);
            document.getElementById('teams-form').submit();
        }

        // Initialize groups interface when page loads
        document.addEventListener('DOMContentLoaded', function() {
            generateGroupsInterface();
        });
    </script>
</body>
</html>
