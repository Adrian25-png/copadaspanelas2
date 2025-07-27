<?php
session_start();
require_once '../../config/conexao.php';

$tournament_id = $_GET['tournament'] ?? null;
if (!$tournament_id) {
    header('Location: tournament_list.php');
    exit;
}

$pdo = conectar();

// Get tournament and groups
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM grupos WHERE tournament_id = ? ORDER BY nome");
$stmt->execute([$tournament_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process bulk team addition
if ($_POST && isset($_POST['bulk_add'])) {
    try {
        $pdo->beginTransaction();
        
        $teams_data = json_decode($_POST['teams_data'], true);
        $success_count = 0;
        $errors = [];
        
        foreach ($teams_data as $team) {
            // Validate team data
            if (empty($team['name']) || empty($team['group_id'])) {
                $errors[] = "Invalid team data: " . ($team['name'] ?? 'Unknown');
                continue;
            }
            
            // Check for duplicate names
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE nome = ? AND tournament_id = ?");
            $stmt->execute([$team['name'], $tournament_id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Team name already exists: " . $team['name'];
                continue;
            }
            
            // Process logo upload if provided
            $logo_data = null;
            if (!empty($team['logo_file'])) {
                // Handle base64 encoded image
                $logo_data = base64_decode($team['logo_file']);
            }
            
            // Insert team
            $stmt = $pdo->prepare("
                INSERT INTO times (nome, logo, grupo_id, tournament_id, pts, vitorias, empates, derrotas, gm, gc, sg, token) 
                VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, ?)
            ");
            $token = bin2hex(random_bytes(16));
            $stmt->execute([$team['name'], $logo_data, $team['group_id'], $tournament_id, $token]);
            $success_count++;
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Successfully added $success_count teams.";
        if (!empty($errors)) {
            $_SESSION['warnings'] = $errors;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error adding teams: " . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tournament=' . $tournament_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Team Manager - <?= htmlspecialchars($tournament['name']) ?></title>
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="../../../public/css/bulk_team_manager.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <?php require_once 'header_adm.php'; ?>
    
    <div class="bulk-manager-container">
        <div class="page-header">
            <h1>Bulk Team Manager</h1>
            <p>Tournament: <?= htmlspecialchars($tournament['name']) ?></p>
            <a href="tournament_dashboard.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Bulk Addition Methods -->
        <div class="addition-methods">
            <div class="method-tabs">
                <button class="tab-btn active" onclick="showTab('manual')">
                    <i class="fas fa-edit"></i> Manual Entry
                </button>
                <button class="tab-btn" onclick="showTab('csv')">
                    <i class="fas fa-file-csv"></i> CSV Import
                </button>
                <button class="tab-btn" onclick="showTab('template')">
                    <i class="fas fa-copy"></i> Use Template
                </button>
            </div>
            
            <!-- Manual Entry Tab -->
            <div id="manual-tab" class="tab-content active">
                <div class="manual-entry">
                    <div class="entry-controls">
                        <button class="btn btn-primary" onclick="addTeamRow()">
                            <i class="fas fa-plus"></i> Add Team
                        </button>
                        <button class="btn btn-secondary" onclick="clearAll()">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                        <span class="team-counter">Teams: <span id="team-count">0</span></span>
                    </div>
                    
                    <div class="teams-grid" id="teams-grid">
                        <!-- Team rows will be added dynamically -->
                    </div>
                    
                    <div class="bulk-actions">
                        <button class="btn btn-success btn-large" onclick="submitTeams()">
                            <i class="fas fa-save"></i> Add All Teams
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- CSV Import Tab -->
            <div id="csv-tab" class="tab-content">
                <div class="csv-import">
                    <div class="import-instructions">
                        <h3>CSV Import Instructions</h3>
                        <p>Upload a CSV file with the following columns:</p>
                        <ul>
                            <li><strong>team_name</strong> - Name of the team (required)</li>
                            <li><strong>group_name</strong> - Group name (e.g., "Grupo A")</li>
                            <li><strong>logo_url</strong> - URL to team logo (optional)</li>
                        </ul>
                    </div>
                    
                    <div class="file-upload">
                        <input type="file" id="csv-file" accept=".csv" onchange="processCsvFile(this)">
                        <label for="csv-file" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Choose CSV File
                        </label>
                    </div>
                    
                    <div id="csv-preview" class="csv-preview" style="display: none;">
                        <h3>Preview</h3>
                        <div id="csv-preview-content"></div>
                        <button class="btn btn-success" onclick="importCsvTeams()">
                            <i class="fas fa-download"></i> Import Teams
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Template Tab -->
            <div id="template-tab" class="tab-content">
                <div class="template-selection">
                    <h3>Quick Templates</h3>
                    <div class="template-grid">
                        <div class="template-card" onclick="loadTeamTemplate('brazilian')">
                            <i class="fas fa-flag"></i>
                            <h4>Brazilian Teams</h4>
                            <p>Popular Brazilian football teams</p>
                        </div>

                        <div class="template-card" onclick="loadTeamTemplate('european')">
                            <i class="fas fa-globe-europe"></i>
                            <h4>European Teams</h4>
                            <p>Major European football clubs</p>
                        </div>

                        <div class="template-card" onclick="loadTeamTemplate('generic')">
                            <i class="fas fa-users"></i>
                            <h4>Generic Teams</h4>
                            <p>Team A, Team B, etc.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden form for submission -->
    <form id="bulk-form" method="POST" style="display: none;">
        <input type="hidden" name="bulk_add" value="1">
        <input type="hidden" name="teams_data" id="teams-data-input">
    </form>
    
    <script>
        let teamCount = 0;
        let teamsData = [];
        
        const groups = <?= json_encode($groups) ?>;
        
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function addTeamRow() {
            teamCount++;
            const grid = document.getElementById('teams-grid');
            
            const teamRow = document.createElement('div');
            teamRow.className = 'team-row';
            teamRow.id = 'team-' + teamCount;
            
            teamRow.innerHTML = `
                <div class="team-input-group">
                    <label>Team Name:</label>
                    <input type="text" class="team-name" placeholder="Enter team name" required>
                </div>
                
                <div class="team-input-group">
                    <label>Group:</label>
                    <select class="team-group" required>
                        <option value="">Select Group</option>
                        ${groups.map(group => `<option value="${group.id}">${group.nome}</option>`).join('')}
                    </select>
                </div>
                
                <div class="team-input-group">
                    <label>Logo:</label>
                    <input type="file" class="team-logo" accept="image/*" onchange="previewLogo(this)">
                    <div class="logo-preview"></div>
                </div>
                
                <div class="team-actions">
                    <button type="button" class="btn btn-danger btn-small" onclick="removeTeamRow(${teamCount})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            grid.appendChild(teamRow);
            updateTeamCount();
        }
        
        function removeTeamRow(id) {
            const row = document.getElementById('team-' + id);
            if (row) {
                row.remove();
                updateTeamCount();
            }
        }
        
        function updateTeamCount() {
            const count = document.querySelectorAll('.team-row').length;
            document.getElementById('team-count').textContent = count;
        }
        
        function clearAll() {
            if (confirm('Are you sure you want to clear all teams?')) {
                document.getElementById('teams-grid').innerHTML = '';
                updateTeamCount();
            }
        }
        
        function submitTeams() {
            const teamRows = document.querySelectorAll('.team-row');
            const teams = [];
            
            teamRows.forEach(row => {
                const name = row.querySelector('.team-name').value.trim();
                const groupId = row.querySelector('.team-group').value;
                const logoFile = row.querySelector('.team-logo').files[0];
                
                if (name && groupId) {
                    const team = {
                        name: name,
                        group_id: groupId,
                        logo_file: null
                    };
                    
                    // Handle logo file if present
                    if (logoFile) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            team.logo_file = e.target.result.split(',')[1]; // Remove data:image/...;base64,
                        };
                        reader.readAsDataURL(logoFile);
                    }
                    
                    teams.push(team);
                }
            });
            
            if (teams.length === 0) {
                alert('Please add at least one team with valid data.');
                return;
            }
            
            // Submit the form
            document.getElementById('teams-data-input').value = JSON.stringify(teams);
            document.getElementById('bulk-form').submit();
        }
        
        function loadTeamTemplate(templateType) {
            const templates = {
                brazilian: [
                    'Flamengo', 'Corinthians', 'Palmeiras', 'São Paulo', 'Santos', 'Vasco',
                    'Botafogo', 'Fluminense', 'Grêmio', 'Internacional', 'Cruzeiro', 'Atlético-MG',
                    'Bahia', 'Sport', 'Ceará', 'Fortaleza', 'Goiás', 'Atlético-GO',
                    'Coritiba', 'Athletico-PR', 'Chapecoense', 'Avaí'
                ],
                european: [
                    'Real Madrid', 'Barcelona', 'Manchester United', 'Liverpool', 'Chelsea', 'Arsenal',
                    'Bayern Munich', 'Borussia Dortmund', 'Juventus', 'AC Milan', 'Inter Milan', 'PSG',
                    'Manchester City', 'Tottenham', 'Atletico Madrid', 'Valencia'
                ],
                generic: [
                    'Team Alpha', 'Team Beta', 'Team Gamma', 'Team Delta', 'Team Epsilon', 'Team Zeta',
                    'Team Eta', 'Team Theta', 'Team Iota', 'Team Kappa', 'Team Lambda', 'Team Mu'
                ]
            };

            const teamNames = templates[templateType] || [];
            const groupCount = groups.length;
            const teamsPerGroup = Math.ceil(teamNames.length / groupCount);

            // Clear existing teams
            clearAll();

            // Add teams distributed across groups
            teamNames.forEach((name, index) => {
                addTeamRow();
                const teamRows = document.querySelectorAll('.team-row');
                const currentRow = teamRows[teamRows.length - 1];

                currentRow.querySelector('.team-name').value = name;

                // Distribute teams evenly across groups
                const groupIndex = index % groupCount;
                currentRow.querySelector('.team-group').value = groups[groupIndex].id;
            });
        }

        function processCsvFile(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const csv = e.target.result;
                const lines = csv.split('\n');
                const headers = lines[0].split(',').map(h => h.trim().toLowerCase());

                const teams = [];
                for (let i = 1; i < lines.length; i++) {
                    const values = lines[i].split(',').map(v => v.trim());
                    if (values.length >= 2 && values[0]) {
                        const team = {
                            name: values[0],
                            group: values[1] || 'Grupo A'
                        };
                        teams.push(team);
                    }
                }

                displayCsvPreview(teams);
            };
            reader.readAsText(file);
        }

        function displayCsvPreview(teams) {
            const preview = document.getElementById('csv-preview');
            const content = document.getElementById('csv-preview-content');

            let html = '<table class="csv-table"><thead><tr><th>Team Name</th><th>Group</th></tr></thead><tbody>';
            teams.forEach(team => {
                html += `<tr><td>${team.name}</td><td>${team.group}</td></tr>`;
            });
            html += '</tbody></table>';

            content.innerHTML = html;
            preview.style.display = 'block';

            // Store teams data for import
            window.csvTeamsData = teams;
        }

        function importCsvTeams() {
            if (!window.csvTeamsData) return;

            clearAll();

            window.csvTeamsData.forEach(team => {
                addTeamRow();
                const teamRows = document.querySelectorAll('.team-row');
                const currentRow = teamRows[teamRows.length - 1];

                currentRow.querySelector('.team-name').value = team.name;

                // Find matching group
                const groupSelect = currentRow.querySelector('.team-group');
                for (let option of groupSelect.options) {
                    if (option.text.toLowerCase().includes(team.group.toLowerCase())) {
                        option.selected = true;
                        break;
                    }
                }
            });

            // Switch to manual tab
            showTab('manual');
        }

        function previewLogo(input) {
            const file = input.files[0];
            const preview = input.parentNode.querySelector('.logo-preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Logo preview" style="max-width: 50px; max-height: 50px; border-radius: 4px;">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }

        // Initialize with one team row
        addTeamRow();
    </script>
</body>
</html>
