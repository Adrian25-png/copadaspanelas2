<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

// Get all tournaments
$tournaments = $tournamentManager->getAllTournaments();
$current_tournament = $tournamentManager->getCurrentTournament();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Torneios - Copa das Panelas</title>
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="../../../public/css/tournament_list.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
</head>
<body>
    <!-- Cabeçalho padrão do admin -->
    <?php require_once 'header_adm.php'; ?>
    
    <main>
        <div class="tournament-list-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-trophy"></i> Gerenciamento de Torneios</h1>
                <p>Gerencie todos os torneios and create new ones</p>
                
                <div class="header-actions">
                    <a href="tournament_wizard.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Criar Novo Torneio
                    </a>
                    <a href="tournament_wizard_advanced.php" class="btn btn-success">
                        <i class="fas fa-magic"></i> Assistente Completo
                    </a>
                    
                    <?php if ($current_tournament): ?>
                        <a href="tournament_dashboard.php?id=<?= $current_tournament['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Painel Atual
                        </a>
                    <?php endif; ?>
                </div>
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
            
            <!-- Current Tournament Highlight -->
            <?php if ($current_tournament): ?>
                <div class="current-tournament">
                    <div class="current-badge">
                        <i class="fas fa-star"></i> Current Tournament
                    </div>
                    
                    <div class="tournament-card active">
                        <div class="tournament-header">
                            <div class="tournament-info">
                                <h3><?= htmlspecialchars($current_tournament['name']) ?></h3>
                                <p class="tournament-year"><?= $current_tournament['year'] ?></p>
                                <span class="tournament-status status-<?= $current_tournament['status'] ?>">
                                    <?= ($current_tournament['status'] === 'setup' ? 'Configuração' : ($current_tournament['status'] === 'active' ? 'Ativo' : ($current_tournament['status'] === 'completed' ? 'Concluído' : 'Arquivado'))) ?>
                                </span>
                            </div>
                            
                            <div class="tournament-stats">
                                <div class="stat">
                                    <span class="stat-number"><?= $current_tournament['team_count'] ?? 0 ?></span>
                                    <span class="stat-label">Times</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?= $current_tournament['group_count'] ?? 0 ?></span>
                                    <span class="stat-label">Grupos</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tournament-actions">
                            <a href="tournament_dashboard.php?id=<?= $current_tournament['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i> Painel
                            </a>
                            <a href="tournament_management.php?id=<?= $current_tournament['id'] ?>" class="btn btn-success">
                                <i class="fas fa-cogs"></i> Gerenciar
                            </a>
                            <a href="rodadas_adm.php" class="btn btn-secondary">
                                <i class="fas fa-futbol"></i> Jogos
                            </a>
                            <a href="tournament_standings.php?id=<?= $current_tournament['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-table"></i> Classificação
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Todos os Torneios -->
            <div class="tournaments-section">
                <h2>Todos os Torneios</h2>
                
                <?php if (empty($tournaments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <h3>Nenhum Torneio Ainda</h3>
                        <p>Crie seu primeiro torneio to get started</p>
                        <a href="tournament_wizard.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Criar Torneio
                        </a>
                    </div>
                <?php else: ?>
                    <div class="tournaments-grid">
                        <?php foreach ($tournaments as $tournament): ?>
                            <div class="tournament-card <?= $tournament['status'] === 'active' ? 'active' : '' ?>">
                                <div class="tournament-header">
                                    <div class="tournament-info">
                                        <h3><?= htmlspecialchars($tournament['name']) ?></h3>
                                        <p class="tournament-year"><?= $tournament['year'] ?></p>
                                        <span class="tournament-status status-<?= $tournament['status'] ?>">
                                            <?= ($tournament['status'] === 'setup' ? 'Configuração' : ($tournament['status'] === 'active' ? 'Ativo' : ($tournament['status'] === 'completed' ? 'Concluído' : 'Arquivado'))) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="tournament-stats">
                                        <div class="stat">
                                            <span class="stat-number"><?= $tournament['team_count'] ?? 0 ?></span>
                                            <span class="stat-label">Times</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?= $tournament['group_count'] ?? 0 ?></span>
                                            <span class="stat-label">Grupos</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tournament-meta">
                                    <p><i class="fas fa-calendar"></i> Criado: <?= date('M j, Y', strtotime($tournament['created_at'])) ?></p>
                                    <?php if ($tournament['description']): ?>
                                        <p><i class="fas fa-info-circle"></i> <?= htmlspecialchars($tournament['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="tournament-actions">
                                    <a href="tournament_management.php?id=<?= $tournament['id'] ?>" class="btn btn-success">
                                        <i class="fas fa-cogs"></i> Gerenciar
                                    </a>

                                    <?php if ($tournament['status'] === 'active'): ?>
                                        <a href="tournament_dashboard.php?id=<?= $tournament['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-tachometer-alt"></i> Painel
                                        </a>
                                    <?php elseif ($tournament['status'] === 'setup'): ?>
                                        <a href="tournament_dashboard.php?id=<?= $tournament['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-cog"></i> Configurar
                                        </a>
                                        <button class="btn btn-success" onclick="activateTournament(<?= $tournament['id'] ?>)">
                                            <i class="fas fa-play"></i> Ativar
                                        </button>
                                    <?php else: ?>
                                        <a href="tournament_dashboard.php?id=<?= $tournament['id'] ?>" class="btn btn-secondary">
                                            <i class="fas fa-eye"></i> Visualizar
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-secondary dropdown-toggle" onclick="toggleDropdown(<?= $tournament['id'] ?>)">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="dropdown-<?= $tournament['id'] ?>">
                                            <a href="export_tournament.php?id=<?= $tournament['id'] ?>" class="dropdown-item">
                                                <i class="fas fa-download"></i> Exportar
                                            </a>

                                            <?php if ($tournament['status'] !== 'active'): ?>
                                                <a href="#" onclick="activateTournament(<?= $tournament['id'] ?>)" class="dropdown-item">
                                                    <i class="fas fa-play"></i> Ativar
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($tournament['status'] === 'active'): ?>
                                                <a href="#" onclick="archiveTournament(<?= $tournament['id'] ?>)" class="dropdown-item">
                                                    <i class="fas fa-archive"></i> Arquivar
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($tournament['status'] !== 'active'): ?>
                                                <a href="#" onclick="duplicateTournament(<?= $tournament['id'] ?>)" class="dropdown-item">
                                                    <i class="fas fa-copy"></i> Duplicar
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($tournament['status'] === 'setup' || $tournament['status'] === 'archived'): ?>
                                                <div class="dropdown-divider"></div>
                                                <a href="#" onclick="deleteTournament(<?= $tournament['id'] ?>)" class="dropdown-item danger">
                                                    <i class="fas fa-trash"></i> Excluir
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php require_once '../footer.php'; ?>
    
    <script>
        function toggleDropdown(tournamentId) {
            const dropdown = document.getElementById('dropdown-' + tournamentId);
            dropdown.classList.toggle('show');
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== 'dropdown-' + tournamentId) {
                    menu.classList.remove('show');
                }
            });
        }
        
        function activateTournament(tournamentId) {
            if (confirm('Are you sure you want to activate this tournament? This will archive the current active tournament.')) {
                fetch('activate_tournament.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({tournament_id: tournamentId})
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
        
        function duplicateTournament(tournamentId) {
            if (confirm('Create a copy of this tournament?')) {
                window.location.href = 'tournament_wizard.php?duplicate=' + tournamentId;
            }
        }
        
        function deleteTournament(tournamentId) {
            // Redirecionar para página de confirmação
            window.location.href = 'tournament_actions.php?action=delete&id=' + tournamentId;
        }

        function archiveTournament(tournamentId) {
            if (confirm('Tem certeza que deseja arquivar este torneio?')) {
                window.location.href = 'tournament_actions.php?action=archive&id=' + tournamentId;
            }
        }

        function activateTournament(tournamentId) {
            if (confirm('Tem certeza que deseja ativar este torneio? O torneio atual será arquivado.')) {
                window.location.href = 'tournament_actions.php?action=activate&id=' + tournamentId;
            }
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
