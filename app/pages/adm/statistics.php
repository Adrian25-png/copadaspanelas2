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
            min-height: 100vh;
            color: #E0E0E0;
            margin: 0;
            padding: 20px;
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

        .btn-standard {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 5px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            border: 2px solid #7B1FA2;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .stat-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: #7B1FA2;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #9E9E9E;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .stat-details {
            font-size: 0.95rem;
            color: #B0B0B0;
            line-height: 1.4;
        }
        
        .section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .section::before {
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
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #7B1FA2;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: #2A2A2A;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(123, 31, 162, 0.2);
        }

        .table th {
            background: #7B1FA2;
            font-weight: 600;
            color: white;
            font-size: 1rem;
        }

        .table td {
            color: #E0E0E0;
        }

        .table tr:hover {
            background: rgba(123, 31, 162, 0.1);
        }
        
        .activity-item {
            background: #2A2A2A;
            border: 1px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            border-color: #7B1FA2;
            transform: translateY(-2px);
        }

        .activity-type {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-tournament { background: #2196F3; color: white; }
        .type-team { background: #4CAF50; color: white; }
        .type-match { background: #FF9800; color: white; }

        .match-score {
            font-weight: 600;
            color: #4CAF50;
        }

        .alert {
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }

        .alert-error {
            background: #2A2A2A;
            border-left-color: #F44336;
            color: #F44336;
        }

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

    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Estatísticas do Sistema</h1>
                <p style="margin: 8px 0 0 0; color: #9E9E9E; font-size: 1.1rem;">Visão geral completa do sistema</p>
            </div>
            <div>
                <button onclick="window.location.reload()" class="btn-standard">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
                <a href="dashboard_simple.php" class="btn-standard">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Mensagens de Erro -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Estatísticas Principais -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon">
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
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $team_stats['total_teams'] ?? 0 ?></div>
                <div class="stat-label">Total de Times</div>
                <div class="stat-details">
                    Média de pontos: <?= number_format($team_stats['avg_points'] ?? 0, 1) ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
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
                <div class="stat-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="stat-number"><?= $match_stats['total_goals'] ?? 0 ?></div>
                <div class="stat-label">Total de Gols</div>
                <div class="stat-details">
                    Média: <?= $match_stats['finished_matches'] > 0 ? number_format(($match_stats['total_goals'] ?? 0) / $match_stats['finished_matches'], 1) : 0 ?> por jogo
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
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
            <div class="section fade-in">
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
            <div class="section fade-in">
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
            <div class="section fade-in">
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
        <div class="fade-in" style="text-align: center; margin-top: 50px; padding-top: 25px; border-top: 2px solid rgba(123, 31, 162, 0.3); color: #9E9E9E;">
            <p style="margin-bottom: 8px; font-weight: 500;">
                <i class="fas fa-clock" style="margin-right: 8px; color: #7B1FA2;"></i>
                Estatísticas atualizadas em <?= date('d/m/Y H:i:s') ?>
            </p>
            <p style="font-weight: 600; color: #E1BEE7;">
                <i class="fas fa-trophy" style="margin-right: 8px; color: #7B1FA2;"></i>
                Sistema Copa das Panelas - Gestão de Torneios
            </p>
        </div>
    </div>

    <script>
        // Animações Copa das Panelas
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Animação especial para cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        card.style.transform = 'scale(1)';
                    }, 200);
                }, 1000 + (index * 100));
            });

            // Animação para seções
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                setTimeout(() => {
                    section.style.borderLeftColor = '#4CAF50';
                    setTimeout(() => {
                        section.style.borderLeftColor = '#7B1FA2';
                    }, 300);
                }, 1500 + (index * 200));
            });

            // Contador animado para números
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach((number, index) => {
                const finalValue = parseInt(number.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 30);

                setTimeout(() => {
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            currentValue = finalValue;
                            clearInterval(timer);
                        }
                        number.textContent = currentValue;
                    }, 50);
                }, 1200 + (index * 100));
            });
        });

        // Auto-refresh a cada 5 minutos
        setTimeout(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>
