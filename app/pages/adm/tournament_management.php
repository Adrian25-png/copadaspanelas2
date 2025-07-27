<?php
/**
 * Gerenciamento Completo de Torneios
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

// Obter torneio ativo ou especificado
$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id) {
    $current_tournament = $tournamentManager->getCurrentTournament();
    if ($current_tournament) {
        $tournament_id = $current_tournament['id'];
    }
}

if (!$tournament_id) {
    $_SESSION['error'] = "Nenhum torneio ativo encontrado";
    header('Location: tournament_list.php');
    exit;
}

$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    $_SESSION['error'] = "Torneio não encontrado";
    header('Location: tournament_list.php');
    exit;
}

// Obter estatísticas do torneio
$stats = $tournamentManager->getTournamentStats($tournament_id);

// Obter grupos e times
$stmt = $pdo->prepare("
    SELECT g.id as grupo_id, g.nome as grupo_nome, 
           COUNT(t.id) as total_times
    FROM grupos g
    LEFT JOIN times t ON g.id = t.grupo_id
    WHERE g.tournament_id = ?
    GROUP BY g.id, g.nome
    ORDER BY g.nome
");
$stmt->execute([$tournament_id]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter times por grupo
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

// Obter logs recentes
$recent_activity = $tournamentManager->getActivityLog($tournament_id, 10);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Torneio - <?= htmlspecialchars($tournament['name']) ?></title>
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
        
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        
        .tournament-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .status-setup { background: #f39c12; }
        .status-active { background: #27ae60; }
        .status-completed { background: #3498db; }
        .status-archived { background: #95a5a6; }
        
        .management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .management-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .groups-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .group-card {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .group-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #f39c12;
        }
        
        .team-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .team-item:last-child {
            border-bottom: none;
        }
        
        .team-name {
            font-weight: 600;
        }
        
        .team-players {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .activity-log {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-action {
            font-weight: 600;
            color: #3498db;
        }
        
        .activity-time {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .management-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tournament_list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Voltar para Lista de Torneios
        </a>
        
        <div class="header">
            <div>
                <h1><i class="fas fa-cogs"></i> Gerenciar Torneio</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?> - <?= $tournament['year'] ?></p>
            </div>
            <span class="tournament-status status-<?= $tournament['status'] ?>">
                <?= ($tournament['status'] === 'setup' ? 'Configuração' : 
                    ($tournament['status'] === 'active' ? 'Ativo' : 
                    ($tournament['status'] === 'completed' ? 'Concluído' : 'Arquivado'))) ?>
            </span>
        </div>
        
        <!-- Estatísticas Rápidas -->
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-number"><?= $stats['total_groups'] ?? 0 ?></div>
                <div class="stat-label">Grupos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['total_teams'] ?? 0 ?></div>
                <div class="stat-label">Times</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['total_matches'] ?? 0 ?></div>
                <div class="stat-label">Jogos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['completed_matches'] ?? 0 ?></div>
                <div class="stat-label">Concluídos</div>
            </div>
        </div>
        
        <div class="management-grid">
            <!-- Gerenciamento de Times e Jogadores -->
            <div class="management-section">
                <h2 class="section-title">
                    <i class="fas fa-users"></i> Times e Jogadores
                </h2>
                
                <div class="action-buttons">
                    <a href="team_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-users"></i> Gerenciar Times
                    </a>
                    <a href="player_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-success">
                        <i class="fas fa-user"></i> Gerenciar Jogadores
                    </a>
                    <a href="bulk_team_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-warning">
                        <i class="fas fa-upload"></i> Importar Times
                    </a>
                </div>
            </div>
            
            <!-- Gerenciamento de Jogos -->
            <div class="management-section">
                <h2 class="section-title">
                    <i class="fas fa-futbol"></i> Jogos e Resultados
                </h2>
                
                <div class="action-buttons">
                    <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> Gerenciar Jogos
                    </a>
                    <a href="quick_results.php?tournament_id=<?= $tournament_id ?>" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Inserir Resultados
                    </a>
                    <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-trophy"></i> Ver Classificação
                    </a>
                </div>
            </div>
        </div>
        
        <div class="management-grid">
            <!-- Configurações do Torneio -->
            <div class="management-section">
                <h2 class="section-title">
                    <i class="fas fa-cog"></i> Configurações
                </h2>
                
                <div class="action-buttons">
                    <a href="tournament_settings.php?id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Torneio
                    </a>
                    <?php if ($tournament['status'] === 'setup'): ?>
                        <a href="tournament_actions.php?action=activate&id=<?= $tournament_id ?>" class="btn btn-success">
                            <i class="fas fa-play"></i> Ativar Torneio
                        </a>
                    <?php endif; ?>
                    <?php if ($tournament['status'] === 'active'): ?>
                        <a href="tournament_actions.php?action=archive&id=<?= $tournament_id ?>" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Arquivar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Relatórios e Exportação -->
            <div class="management-section">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </h2>
                
                <div class="action-buttons">
                    <a href="tournament_reports.php?id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Relatórios
                    </a>
                    <a href="export_tournament.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Exportar Dados
                    </a>
                    <a href="tournament_dashboard.php?id=<?= $tournament_id ?>" class="btn btn-success">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Visão Geral dos Grupos -->
        <?php if (!empty($grupos)): ?>
            <div class="management-section" style="margin-top: 30px;">
                <h2 class="section-title">
                    <i class="fas fa-layer-group"></i> Visão Geral dos Grupos
                </h2>
                
                <div class="groups-overview">
                    <?php foreach ($grupos as $grupo): ?>
                        <div class="group-card">
                            <div class="group-title"><?= htmlspecialchars($grupo['grupo_nome']) ?></div>
                            <?php if (!empty($times_por_grupo[$grupo['grupo_id']])): ?>
                                <?php foreach ($times_por_grupo[$grupo['grupo_id']] as $time): ?>
                                    <div class="team-item">
                                        <span class="team-name"><?= htmlspecialchars($time['nome']) ?></span>
                                        <span class="team-players"><?= $time['total_jogadores'] ?> jogadores</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="opacity: 0.7; font-style: italic;">Nenhum time cadastrado</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Log de Atividades -->
        <?php if (!empty($recent_activity)): ?>
            <div class="activity-log">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Atividades Recentes
                </h2>
                
                <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                    <div class="activity-item">
                        <div>
                            <span class="activity-action"><?= htmlspecialchars($activity['action']) ?></span>
                            <span> - <?= htmlspecialchars($activity['description']) ?></span>
                        </div>
                        <span class="activity-time"><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center; opacity: 0.7;">
            <p>Gerenciamento de Torneio - Copa das Panelas</p>
        </div>
    </div>
</body>
</html>
