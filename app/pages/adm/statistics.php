<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Obter estatísticas gerais do sistema
try {
    // Estatísticas de torneios
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_tournaments,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_tournaments,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tournaments,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_tournaments
        FROM tournaments
    ");
    $tournament_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas de times
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_teams,
            COUNT(DISTINCT tournament_id) as tournaments_with_teams,
            AVG(COALESCE(pts, 0)) as avg_points,
            MAX(COALESCE(pts, 0)) as max_points
        FROM times
    ");
    $team_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas de jogos
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_matches,
            COUNT(CASE WHEN status = 'finalizado' THEN 1 END) as finished_matches,
            COUNT(CASE WHEN status = 'agendado' THEN 1 END) as scheduled_matches,
            COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as ongoing_matches,
            SUM(COALESCE(team1_goals, 0) + COALESCE(team2_goals, 0)) as total_goals
        FROM matches
    ");
    $match_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas de grupos
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_groups,
            COUNT(DISTINCT tournament_id) as tournaments_with_groups
        FROM grupos
    ");
    $group_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Top 10 times com mais pontos
    $stmt = $pdo->query("
        SELECT t.nome,
               COALESCE(t.pts, 0) as pts,
               (COALESCE(t.vitorias, 0) + COALESCE(t.empates, 0) + COALESCE(t.derrotas, 0)) as jogos,
               COALESCE(t.vitorias, 0) as vitorias,
               COALESCE(t.empates, 0) as empates,
               COALESCE(t.derrotas, 0) as derrotas,
               COALESCE(t.gm, 0) as gols_pro,
               COALESCE(t.gc, 0) as gols_contra,
               COALESCE(t.sg, 0) as saldo_gols,
               tour.name as tournament_name
        FROM times t
        LEFT JOIN tournaments tour ON t.tournament_id = tour.id
        WHERE COALESCE(t.pts, 0) > 0
        ORDER BY t.pts DESC, t.sg DESC, t.gm DESC
        LIMIT 10
    ");
    $top_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jogos com mais gols
    $stmt = $pdo->query("
        SELECT m.*, t1.nome as team1_name, t2.nome as team2_name, tour.name as tournament_name,
               (COALESCE(m.team1_goals, 0) + COALESCE(m.team2_goals, 0)) as total_goals
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN tournaments tour ON m.tournament_id = tour.id
        WHERE m.status = 'finalizado' AND (m.team1_goals IS NOT NULL AND m.team2_goals IS NOT NULL)
        ORDER BY total_goals DESC, m.match_date DESC
        LIMIT 10
    ");
    $high_scoring_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Atividade recente
    $recent_activity = [];

    // Obter torneios recentes
    $stmt = $pdo->query("
        SELECT 'tournament' as type, name as title, created_at, status as extra
        FROM tournaments
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $tournaments_recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter jogos recentes
    $stmt = $pdo->query("
        SELECT 'match' as type,
               CONCAT(t1.nome, ' vs ', t2.nome) as title,
               m.created_at,
               m.status as extra
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        ORDER BY m.created_at DESC
        LIMIT 10
    ");
    $matches_recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combinar e ordenar atividades
    $recent_activity = array_merge($tournaments_recent, $matches_recent);
    usort($recent_activity, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recent_activity = array_slice($recent_activity, 0, 15);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar estatísticas: " . $e->getMessage();
    $tournament_stats = $team_stats = $match_stats = $group_stats = [];
    $top_teams = $high_scoring_matches = $recent_activity = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas do Sistema - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .stat-details {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f39c12;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table th {
            background: rgba(0, 0, 0, 0.4);
            font-weight: bold;
            color: #f39c12;
        }
        
        .table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .activity-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .type-tournament { background: #3498db; }
        .type-team { background: #27ae60; }
        .type-match { background: #f39c12; }
        
        .match-score {
            font-weight: bold;
            color: #3498db;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Estatísticas do Sistema</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Visão geral completa do sistema</p>
            </div>
            <div>
                <button onclick="window.location.reload()" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Mensagens de Erro -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Estatísticas Principais -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #3498db;">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-number"><?= $tournament_stats['total_tournaments'] ?? 0 ?></div>
                <div class="stat-label">Total de Torneios</div>
                <div class="stat-details">
                    Ativos: <?= $tournament_stats['active_tournaments'] ?? 0 ?> | 
                    Finalizados: <?= $tournament_stats['completed_tournaments'] ?? 0 ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #27ae60;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $team_stats['total_teams'] ?? 0 ?></div>
                <div class="stat-label">Total de Times</div>
                <div class="stat-details">
                    Média de pontos: <?= number_format($team_stats['avg_points'] ?? 0, 1) ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #f39c12;">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-number"><?= $match_stats['total_matches'] ?? 0 ?></div>
                <div class="stat-label">Total de Jogos</div>
                <div class="stat-details">
                    Finalizados: <?= $match_stats['finished_matches'] ?? 0 ?> | 
                    Agendados: <?= $match_stats['scheduled_matches'] ?? 0 ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #e74c3c;">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="stat-number"><?= $match_stats['total_goals'] ?? 0 ?></div>
                <div class="stat-label">Total de Gols</div>
                <div class="stat-details">
                    Média: <?= $match_stats['finished_matches'] > 0 ? number_format(($match_stats['total_goals'] ?? 0) / $match_stats['finished_matches'], 1) : 0 ?> por jogo
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #9b59b6;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-number"><?= $group_stats['total_groups'] ?? 0 ?></div>
                <div class="stat-label">Total de Grupos</div>
                <div class="stat-details">
                    Em <?= $group_stats['tournaments_with_groups'] ?? 0 ?> torneios
                </div>
            </div>
        </div>
        
        <!-- Top 10 Times -->
        <?php if (!empty($top_teams)): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fas fa-medal"></i>
                    Top 10 Times com Mais Pontos
                </h3>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Time</th>
                            <th>Torneio</th>
                            <th>Pts</th>
                            <th>J</th>
                            <th>V</th>
                            <th>E</th>
                            <th>D</th>
                            <th>GP</th>
                            <th>GC</th>
                            <th>SG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_teams as $index => $team): ?>
                            <tr>
                                <td><?= $index + 1 ?>º</td>
                                <td style="font-weight: bold;"><?= htmlspecialchars($team['nome']) ?></td>
                                <td><?= htmlspecialchars($team['tournament_name']) ?></td>
                                <td style="font-weight: bold; color: #27ae60;"><?= $team['pts'] ?></td>
                                <td><?= $team['jogos'] ?></td>
                                <td><?= $team['vitorias'] ?></td>
                                <td><?= $team['empates'] ?></td>
                                <td><?= $team['derrotas'] ?></td>
                                <td><?= $team['gols_pro'] ?></td>
                                <td><?= $team['gols_contra'] ?></td>
                                <td style="color: <?= $team['saldo_gols'] > 0 ? '#27ae60' : ($team['saldo_gols'] < 0 ? '#e74c3c' : '#f39c12') ?>">
                                    <?= $team['saldo_gols'] > 0 ? '+' : '' ?><?= $team['saldo_gols'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Jogos com Mais Gols -->
        <?php if (!empty($high_scoring_matches)): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fas fa-fire"></i>
                    Jogos com Mais Gols
                </h3>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time 1</th>
                            <th>Placar</th>
                            <th>Time 2</th>
                            <th>Total</th>
                            <th>Torneio</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($high_scoring_matches as $match): ?>
                            <tr>
                                <td><?= htmlspecialchars($match['team1_name']) ?></td>
                                <td class="match-score"><?= $match['team1_goals'] ?> x <?= $match['team2_goals'] ?></td>
                                <td><?= htmlspecialchars($match['team2_name']) ?></td>
                                <td style="font-weight: bold; color: #f39c12;"><?= $match['total_goals'] ?> gols</td>
                                <td><?= htmlspecialchars($match['tournament_name']) ?></td>
                                <td><?= $match['match_date'] ? date('d/m/Y', strtotime($match['match_date'])) : 'N/A' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Atividade Recente -->
        <?php if (!empty($recent_activity)): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i>
                    Atividade Recente
                </h3>
                
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <div>
                            <span class="activity-type type-<?= $activity['type'] ?>">
                                <?= ucfirst($activity['type']) ?>
                            </span>
                            <span style="margin-left: 10px; font-weight: bold;">
                                <?= htmlspecialchars($activity['title']) ?>
                            </span>
                        </div>
                        <div style="opacity: 0.7;">
                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Rodapé -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2); opacity: 0.7;">
            <p>Estatísticas atualizadas em <?= date('d/m/Y H:i:s') ?></p>
            <p>Sistema Copa das Panelas - Gestão de Torneios</p>
        </div>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.stat-card, .section');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Auto-refresh a cada 5 minutos
        setTimeout(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>
