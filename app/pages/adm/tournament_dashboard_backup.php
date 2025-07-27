<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$tournament_id = $_GET['id'] ?? null;
$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

// If no tournament ID provided, get current active tournament
if (!$tournament_id) {
    $current = $tournamentManager->getCurrentTournament();
    if ($current) {
        $tournament_id = $current['id'];
    } else {
        header('Location: tournament_wizard.php');
        exit;
    }
}

// Get tournament information
$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    header('Location: tournament_list.php');
    exit;
}

// Get tournament statistics
$stats = $tournamentManager->getTournamentStats($tournament_id);

// Get recent activity
$recent_activity = $tournamentManager->getActivityLog($tournament_id, 5);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Torneio - <?= htmlspecialchars($tournament['name']) ?></title>
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="../../../public/css/tournament_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <?php require_once 'header_adm.php'; ?>
    
    <div class="dashboard-container">
        <!-- Tournament Header -->
        <div class="tournament-header">
            <div class="tournament-info">
                <h1><?= htmlspecialchars($tournament['name']) ?></h1>
                <p class="tournament-year"><?= $tournament['year'] ?></p>
                <span class="tournament-status status-<?= $tournament['status'] ?>">
                    <?= ($tournament['status'] === 'setup' ? 'Configuração' : ($tournament['status'] === 'active' ? 'Ativo' : ($tournament['status'] === 'completed' ? 'Concluído' : 'Arquivado'))) ?>
                </span>
            </div>
            
            <div class="tournament-actions">
                <?php if ($tournament['status'] === 'setup'): ?>
                    <button class="btn btn-primary" onclick="activateTournament()">
                        <i class="fas fa-play"></i> Ativar Torneio
                    </button>
                <?php endif; ?>

                <button class="btn btn-secondary" onclick="exportTournament()">
                    <i class="fas fa-download"></i> Exportar Dados
                </button>

                <button class="btn btn-danger" onclick="archiveTournament()">
                    <i class="fas fa-archive"></i> Arquivar
                </button>
            </div>
        </div>
        
        <!-- Progress Overview -->
        <div class="progress-overview">
            <h2>Progresso do Torneio</h2>
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $stats['completion_percentage'] ?>%"></div>
                </div>
                <span class="progress-text"><?= $stats['completion_percentage'] ?>% Complete</span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-layer-group"></i>
                    <div class="stat-info">
                        <span class="stat-number"><?= $stats['groups'] ?></span>
                        <span class="stat-label">Groups</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <span class="stat-number"><?= $stats['teams'] ?></span>
                        <span class="stat-label">Teams</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-futbol"></i>
                    <div class="stat-info">
                        <span class="stat-number"><?= $stats['matches_played'] ?>/<?= $stats['matches_total'] ?></span>
                        <span class="stat-label">Matches Played</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="add_teams_bulk.php?tournament=<?= $tournament_id ?>" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add Teams</h3>
                    <p>Bulk add teams to groups</p>
                </a>
                
                <a href="manage_fixtures.php?tournament=<?= $tournament_id ?>" class="action-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Manage Fixtures</h3>
                    <p>View and edit match schedules</p>
                </a>
                
                <a href="enter_results.php?tournament=<?= $tournament_id ?>" class="action-card">
                    <i class="fas fa-edit"></i>
                    <h3>Enter Results</h3>
                    <p>Input match scores quickly</p>
                </a>
                
                <a href="view_standings.php?tournament=<?= $tournament_id ?>" class="action-card">
                    <i class="fas fa-trophy"></i>
                    <h3>View Standings</h3>
                    <p>Check current group standings</p>
                </a>
                
                <a href="manage_finals.php?tournament=<?= $tournament_id ?>" class="action-card">
                    <i class="fas fa-medal"></i>
                    <h3>Finals Management</h3>
                    <p>Manage knockout stages</p>
                </a>
                
                <a href="tournament_settings.php?tournament=<?= $tournament_id ?>" class="action-card">
                    <i class="fas fa-cog"></i>
                    <h3>Settings</h3>
                    <p>Tournament configuration</p>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2>Recent Activity</h2>
            <div class="activity-list">
                <?php if (!empty($recent_activity)): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <i class="fas fa-<?= $activity['action'] == 'CREATED' ? 'plus' : ($activity['action'] == 'BACKUP' ? 'save' : 'edit') ?> text-<?= $activity['action'] == 'CREATED' ? 'success' : 'info' ?>"></i>
                            <div class="activity-content">
                                <p><strong><?= htmlspecialchars($activity['action']) ?>:</strong> <?= htmlspecialchars($activity['description']) ?></p>
                                <span class="activity-time"><?= date('M j, Y H:i', strtotime($activity['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <i class="fas fa-info-circle text-info"></i>
                        <div class="activity-content">
                            <p>No recent activity</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function activateTournament() {
            if (confirm('Are you sure you want to activate this tournament? This will generate all fixtures and make it live.')) {
                // AJAX call to activate tournament
                fetch('activate_tournament.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({tournament_id: <?= $tournament_id ?>})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function exportTournament() {
            window.open('export_tournament.php?id=<?= $tournament_id ?>', '_blank');
        }
        
        function archiveTournament() {
            if (confirm('Are you sure you want to archive this tournament? This action cannot be undone.')) {
                // Implementation for archiving
            }
        }
    </script>
</body>
</html>
