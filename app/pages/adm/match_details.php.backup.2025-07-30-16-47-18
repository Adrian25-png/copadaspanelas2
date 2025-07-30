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
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header-content .icon {
            font-size: 2.5rem;
            color: rgba(255,255,255,0.9);
        }

        .header-subtitle {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
        }

        .match-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 30px;
        }

        .teams-display {
            display: flex;
            align-items: center;
            gap: 40px;
            flex: 1;
            justify-content: center;
        }

        .team {
            text-align: center;
            flex: 1;
            max-width: 220px;
            transition: all 0.3s ease;
        }

        .team:hover {
            transform: translateY(-5px);
        }

        .team-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #6366f1;
            border: 3px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .team-logo:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.3);
        }

        .team-name {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #E0E0E0;
        }

        .team-score {
            font-size: 3.5rem;
            font-weight: 700;
            color: #10b981;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            padding: 15px 25px;
            border-radius: 15px;
            border: 2px solid rgba(16, 185, 129, 0.2);
            display: inline-block;
        }

        .vs {
            font-size: 2.5rem;
            font-weight: 700;
            color: #f59e0b;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            padding: 15px 20px;
            border-radius: 15px;
            border: 2px solid rgba(245, 158, 11, 0.2);
        }

        .status-badge {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .match-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .info-item {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.2));
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #d946ef);
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .info-label {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
            font-weight: 500;
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #6366f1;
        }

        .stats-section {
            margin-top: 40px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-title {
            font-size: 1.6rem;
            margin-bottom: 25px;
            color: #E0E0E0;
            text-align: center;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .stats-title i {
            color: #6366f1;
            font-size: 1.4rem;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .stats-table th,
        .stats-table td {
            padding: 18px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-table th {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(139, 92, 246, 0.3));
            font-weight: 600;
            color: #E0E0E0;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
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
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .empty-stats {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.6);
        }

        .empty-stats i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: rgba(99, 102, 241, 0.3);
        }

        .empty-stats h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #E0E0E0;
        }

        .empty-stats p {
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .header-content h1 {
                font-size: 2rem;
                flex-direction: column;
                gap: 10px;
            }

            .teams-display {
                flex-direction: column;
                gap: 25px;
            }

            .vs {
                order: 2;
                font-size: 2rem;
            }

            .match-header {
                flex-direction: column;
                gap: 20px;
            }

            .team-score {
                font-size: 2.5rem;
            }

            .team-logo {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }

            .match-info {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }

            .main-container {
                padding: 15px;
            }

            .match-card {
                padding: 25px;
            }
        }

        @media (max-width: 480px) {
            .header-content h1 {
                font-size: 1.8rem;
            }

            .team-score {
                font-size: 2rem;
            }

            .vs {
                font-size: 1.8rem;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <h1><i class="fas fa-futbol icon"></i> Detalhes do Jogo</h1>
                <div class="header-subtitle"><?= htmlspecialchars($match['tournament_name']) ?></div>
            </div>
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

        <div class="section">
            <h3><i class="fas fa-cogs"></i> Ações</h3>
            <div class="actions" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <?php if ($match['status'] !== 'finalizado'): ?>
                <a href="edit_match.php?tournament_id=<?= $match['tournament_id'] ?>&match_id=<?= $match['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar Jogo
                </a>
                <?php endif; ?>

                <a href="match_manager.php?tournament_id=<?= $match['tournament_id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-futbol"></i> Gerenciar Jogos
                </a>

                <a href="match_reports.php" class="btn btn-success">
                    <i class="fas fa-chart-line"></i> Relatórios
                </a>

                <a href="dashboard_simple.php" class="btn btn-warning">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animação do header
            const header = document.querySelector('.page-header');
            if (header) {
                header.style.opacity = '0';
                header.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    header.style.transition = 'all 0.8s ease';
                    header.style.opacity = '1';
                    header.style.transform = 'translateY(0)';
                }, 100);
            }

            // Animação do card principal
            const matchCard = document.querySelector('.match-card');
            if (matchCard) {
                matchCard.style.opacity = '0';
                matchCard.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    matchCard.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    matchCard.style.opacity = '1';
                    matchCard.style.transform = 'translateY(0)';
                }, 200);
            }

            // Animação dos times
            const teams = document.querySelectorAll('.team');
            teams.forEach((team, index) => {
                team.style.opacity = '0';
                team.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    team.style.transition = 'all 0.5s ease';
                    team.style.opacity = '1';
                    team.style.transform = 'scale(1)';
                }, 400 + (index * 200));
            });

            // Animação das seções
            const sections = document.querySelectorAll('.section, .stats-section');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, 600 + (index * 150));
            });

            // Animação dos info items
            const infoItems = document.querySelectorAll('.info-item');
            infoItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 800 + (index * 100));
            });
        });
    </script>
</body>
</html>
