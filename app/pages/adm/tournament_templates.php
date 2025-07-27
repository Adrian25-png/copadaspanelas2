<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentTemplates.php';

$templates = TournamentTemplates::getTemplates();

// Handle template selection
if ($_POST && isset($_POST['template_id'])) {
    $template_id = $_POST['template_id'];
    $template = TournamentTemplates::getTemplate($template_id);
    
    if ($template) {
        // Store template data in session for wizard
        $_SESSION['tournament_wizard'] = [
            'name' => $template['name'] . ' ' . date('Y'),
            'year' => date('Y'),
            'description' => $template['description'],
            'num_groups' => $template['num_groups'],
            'teams_per_group' => $template['teams_per_group'],
            'final_phase' => $template['final_phase'],
            'teams' => $template['teams']
        ];
        
        header('Location: tournament_wizard.php?step=4');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Templates - Copa das Panelas</title>
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="../../../public/css/tournament_templates.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
</head>
<body>
    <!-- Cabeçalho padrão do admin -->
    <?php require_once 'header_adm.php'; ?>
    
    <main>
        <div class="templates-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-copy"></i> Tournament Templates</h1>
                <p>Choose a pre-configured tournament template to get started quickly</p>
                
                <div class="header-actions">
                    <a href="tournament_wizard.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Custom Tournament
                    </a>
                    <a href="tournament_list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            
            <!-- Templates Grid -->
            <div class="templates-grid">
                <?php foreach ($templates as $template_id => $template): ?>
                    <div class="template-card" data-template="<?= $template_id ?>">
                        <div class="template-header">
                            <div class="template-icon">
                                <?php
                                $icons = [
                                    'copa_mundo' => 'fa-globe',
                                    'brasileirao' => 'fa-flag',
                                    'champions_league' => 'fa-star',
                                    'copa_america' => 'fa-map',
                                    'estadual' => 'fa-map-marker-alt',
                                    'escolar' => 'fa-graduation-cap'
                                ];
                                $icon = $icons[$template_id] ?? 'fa-trophy';
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <h3><?= htmlspecialchars($template['name']) ?></h3>
                        </div>
                        
                        <div class="template-description">
                            <p><?= htmlspecialchars($template['description']) ?></p>
                        </div>
                        
                        <div class="template-stats">
                            <div class="stat">
                                <i class="fas fa-layer-group"></i>
                                <span><?= $template['num_groups'] ?> Groups</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-users"></i>
                                <span><?= $template['teams_per_group'] ?> Teams/Group</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-trophy"></i>
                                <span><?= ucfirst($template['final_phase']) ?></span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-futbol"></i>
                                <span><?= count($template['teams']) ?> Total Teams</span>
                            </div>
                        </div>
                        
                        <div class="template-preview">
                            <h4>Teams Preview:</h4>
                            <div class="teams-preview">
                                <?php 
                                $preview_teams = array_slice($template['teams'], 0, 8);
                                foreach ($preview_teams as $team): 
                                ?>
                                    <span class="team-tag"><?= htmlspecialchars($team['name']) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($template['teams']) > 8): ?>
                                    <span class="team-tag more">+<?= count($template['teams']) - 8 ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="template-actions">
                            <button class="btn btn-secondary" onclick="previewTemplate('<?= $template_id ?>')">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <button class="btn btn-primary" onclick="selectTemplate('<?= $template_id ?>')">
                                <i class="fas fa-check"></i> Use Template
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Custom Template Card -->
            <div class="template-card custom-template">
                <div class="template-header">
                    <div class="template-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3>Custom Tournament</h3>
                </div>
                
                <div class="template-description">
                    <p>Create a completely custom tournament with your own configuration and teams</p>
                </div>
                
                <div class="template-features">
                    <ul>
                        <li><i class="fas fa-check"></i> Custom number of groups</li>
                        <li><i class="fas fa-check"></i> Custom teams per group</li>
                        <li><i class="fas fa-check"></i> Choose final phase format</li>
                        <li><i class="fas fa-check"></i> Add your own teams</li>
                        <li><i class="fas fa-check"></i> Full customization</li>
                    </ul>
                </div>
                
                <div class="template-actions">
                    <a href="tournament_wizard.php" class="btn btn-primary btn-large">
                        <i class="fas fa-cog"></i> Create Custom Tournament
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Template Preview Modal -->
    <div id="preview-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="preview-title">Template Preview</h2>
                <button class="modal-close" onclick="closePreview()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body" id="preview-content">
                <!-- Preview content will be loaded here -->
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePreview()">Close</button>
                <button class="btn btn-primary" id="use-template-btn">Use This Template</button>
            </div>
        </div>
    </div>
    
    <!-- Hidden form for template selection -->
    <form id="template-form" method="POST" style="display: none;">
        <input type="hidden" name="template_id" id="template-id-input">
    </form>
    
    <!-- Footer -->
    <?php require_once '../footer.php'; ?>
    
    <script>
        const templates = <?= json_encode($templates) ?>;
        
        function selectTemplate(templateId) {
            if (confirm('Use this template to create a new tournament?')) {
                document.getElementById('template-id-input').value = templateId;
                document.getElementById('template-form').submit();
            }
        }
        
        function previewTemplate(templateId) {
            const template = templates[templateId];
            if (!template) return;
            
            document.getElementById('preview-title').textContent = template.name + ' - Preview';
            
            // Group teams by group
            const teamsByGroup = {};
            template.teams.forEach(team => {
                if (!teamsByGroup[team.group]) {
                    teamsByGroup[team.group] = [];
                }
                teamsByGroup[team.group].push(team.name);
            });
            
            let content = `
                <div class="preview-stats">
                    <div class="preview-stat">
                        <strong>Groups:</strong> ${template.num_groups}
                    </div>
                    <div class="preview-stat">
                        <strong>Teams per Group:</strong> ${template.teams_per_group}
                    </div>
                    <div class="preview-stat">
                        <strong>Final Phase:</strong> ${template.final_phase}
                    </div>
                    <div class="preview-stat">
                        <strong>Total Teams:</strong> ${template.teams.length}
                    </div>
                </div>
                
                <div class="preview-groups">
            `;
            
            Object.keys(teamsByGroup).forEach(groupIndex => {
                const groupLetter = String.fromCharCode(65 + parseInt(groupIndex));
                content += `
                    <div class="preview-group">
                        <h4>Group ${groupLetter}</h4>
                        <ul>
                            ${teamsByGroup[groupIndex].map(team => `<li>${team}</li>`).join('')}
                        </ul>
                    </div>
                `;
            });
            
            content += '</div>';
            
            document.getElementById('preview-content').innerHTML = content;
            document.getElementById('use-template-btn').onclick = () => selectTemplate(templateId);
            document.getElementById('preview-modal').style.display = 'flex';
        }
        
        function closePreview() {
            document.getElementById('preview-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('preview-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    </script>
</body>
</html>
