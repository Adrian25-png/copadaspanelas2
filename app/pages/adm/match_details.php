<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();
$match_id = $_GET['id'] ?? null;

if (!$match_id) {
    $_SESSION['error'] = "ID do jogo não fornecido";
    header('Location: match_reports.php');
    exit;
}

// Buscar dados completos do jogo
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               t1.nome as team1_name, t1.logo as team1_logo,
               t2.nome as team2_name, t2.logo as team2_logo,
               g.nome as group_name,
               tour.name as tournament_name,
               tour.id as tournament_id
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN grupos g ON m.group_id = g.id
        LEFT JOIN tournaments tour ON m.tournament_id = tour.id
        WHERE m.id = ?
    ");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        $_SESSION['error'] = "Jogo não encontrado";
        header('Location: match_reports.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar dados do jogo: " . $e->getMessage();
    header('Location: match_reports.php');
    exit;
}

// Buscar estatísticas dos jogadores (se existirem)
try {
    $stmt = $pdo->prepare("
        SELECT ps.*, j.nome as player_name, t.nome as team_name
        FROM player_stats ps
        LEFT JOIN jogadores j ON ps.player_id = j.id
        LEFT JOIN times t ON j.time_id = t.id
        WHERE ps.match_id = ?
        ORDER BY t.nome, j.nome
    ");
    $stmt->execute([$match_id]);
    $player_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $player_stats = [];
}

// Função para formatar data
function formatDate($date) {
    if (!$date) return 'Não definida';
    return date('d/m/Y H:i', strtotime($date));
}

// Função para status em português
function getStatusText($status) {
    switch ($status) {
        case 'agendado': return 'Agendado';
        case 'em_andamento': return 'Em Andamento';
        case 'finalizado': return 'Finalizado';
        case 'cancelado': return 'Cancelado';
        case 'adiado': return 'Adiado';
        default: return ucfirst($status);
    }
}

// Função para cor do status
function getStatusColor($status) {
    switch ($status) {
        case 'agendado': return '#f39c12';
        case 'em_andamento': return '#e74c3c';
        case 'finalizado': return '#27ae60';
        case 'cancelado': return '#95a5a6';
        case 'adiado': return '#e67e22';
        default: return '#3498db';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Jogo - <?= htmlspecialchars($match['team1_name']) ?> vs <?= htmlspecialchars($match['team2_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #f39c12;
        }

        .match-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .teams-display {
            display: flex;
            align-items: center;
            gap: 30px;
            flex: 1;
            justify-content: center;
        }

        .team {
            text-align: center;
            flex: 1;
            max-width: 200px;
        }

        .team-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #f39c12;
        }

        .team-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .team-score {
            font-size: 3rem;
            font-weight: bold;
            color: #f39c12;
        }

        .vs {
            font-size: 2rem;
            font-weight: bold;
            color: #ecf0f1;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .match-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .info-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #f39c12;
        }

        .stats-section {
            margin-top: 30px;
        }

        .stats-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #f39c12;
            text-align: center;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .stats-table th,
        .stats-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-table th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: bold;
            color: #f39c12;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .empty-stats {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .teams-display {
                flex-direction: column;
                gap: 20px;
            }

            .vs {
                order: 2;
            }

            .match-header {
                flex-direction: column;
            }

            .header h1 {
                font-size: 2rem;
            }

            .team-score {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-futbol"></i> Detalhes do Jogo</h1>
            <p><?= htmlspecialchars($match['tournament_name']) ?></p>
        </div>

        <div class="match-card">
            <div class="match-header">
                <div class="teams-display">
                    <div class="team">
                        <div class="team-logo">
                            <?php if ($match['team1_logo']): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($match['team1_logo']) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <i class="fas fa-shield-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                        <div class="team-score"><?= $match['team1_goals'] ?? '-' ?></div>
                    </div>

                    <div class="vs">VS</div>

                    <div class="team">
                        <div class="team-logo">
                            <?php if ($match['team2_logo']): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($match['team2_logo']) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <i class="fas fa-shield-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                        <div class="team-score"><?= $match['team2_goals'] ?? '-' ?></div>
                    </div>
                </div>

                <div class="status-badge" style="background-color: <?= getStatusColor($match['status']) ?>;">
                    <?= getStatusText($match['status']) ?>
                </div>
            </div>

            <div class="match-info">
                <div class="info-item">
                    <div class="info-label">Data e Hora</div>
                    <div class="info-value"><?= formatDate($match['match_date']) ?></div>
                </div>

                <?php if ($match['group_name']): ?>
                <div class="info-item">
                    <div class="info-label">Grupo</div>
                    <div class="info-value"><?= htmlspecialchars($match['group_name']) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($match['phase']): ?>
                <div class="info-item">
                    <div class="info-label">Fase</div>
                    <div class="info-value"><?= htmlspecialchars($match['phase']) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($match['round']): ?>
                <div class="info-item">
                    <div class="info-label">Rodada</div>
                    <div class="info-value"><?= $match['round'] ?></div>
                </div>
                <?php endif; ?>

                <?php if ($match['location']): ?>
                <div class="info-item">
                    <div class="info-label">Local</div>
                    <div class="info-value"><?= htmlspecialchars($match['location']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($player_stats)): ?>
            <div class="stats-section">
                <h3 class="stats-title"><i class="fas fa-chart-bar"></i> Estatísticas dos Jogadores</h3>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Jogador</th>
                            <th>Time</th>
                            <th>Gols</th>
                            <th>Assistências</th>
                            <th>Cartões Amarelos</th>
                            <th>Cartões Vermelhos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($player_stats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['player_name']) ?></td>
                            <td><?= htmlspecialchars($stat['team_name']) ?></td>
                            <td><?= $stat['goals'] ?? 0 ?></td>
                            <td><?= $stat['assists'] ?? 0 ?></td>
                            <td><?= $stat['yellow_cards'] ?? 0 ?></td>
                            <td><?= $stat['red_cards'] ?? 0 ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-stats">
                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Nenhuma estatística de jogador registrada para este jogo.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <?php if ($match['status'] !== 'finalizado'): ?>
            <a href="edit_match.php?tournament_id=<?= $match['tournament_id'] ?>&match_id=<?= $match['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar Jogo
            </a>
            <?php endif; ?>

            <a href="match_manager.php?tournament_id=<?= $match['tournament_id'] ?>" class="btn btn-secondary">
                <i class="fas fa-futbol"></i> Gerenciar Jogos
            </a>

            <a href="match_reports.php" class="btn btn-success">
                <i class="fas fa-list"></i> Relatórios de Jogos
            </a>
        </div>
    </div>
</body>
</html>
