<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Filtros
$tournament_filter = $_GET['tournament'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Construir query
$where_conditions = [];
$params = [];

if ($tournament_filter) {
    $where_conditions[] = "t.id = ?";
    $params[] = $tournament_filter;
}

if ($status_filter) {
    $where_conditions[] = "m.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(m.match_date) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obter jogos
$stmt = $pdo->prepare("
    SELECT m.*, 
           t1.nome as team1_name, t2.nome as team2_name,
           g.nome as group_name, tour.name as tournament_name,
           DATE(m.match_date) as match_date_only,
           TIME(m.match_date) as match_time_only
    FROM matches m
    LEFT JOIN times t1 ON m.team1_id = t1.id
    LEFT JOIN times t2 ON m.team2_id = t2.id
    LEFT JOIN grupos g ON m.group_id = g.id
    LEFT JOIN tournaments tour ON m.tournament_id = tour.id
    $where_clause
    ORDER BY m.match_date DESC, m.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter torneios para filtro
$stmt = $pdo->query("SELECT id, name FROM tournaments ORDER BY name");
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas gerais
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as finished,
        SUM(CASE WHEN status = 'agendado' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN match_date IS NOT NULL AND DATE(match_date) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM matches
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos os Jogos - Copa das Panelas</title>
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
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
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 1rem;
            color: #9E9E9E;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.5rem;
            color: #7B1FA2;
            margin-bottom: 15px;
        }

        .filters {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .filters::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 1rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-input {
            padding: 12px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 500;
        }

        .filter-input:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333;
            box-shadow: 0 0 10px rgba(123, 31, 162, 0.3);
        }

        .filter-input option {
            background: #2A2A2A;
            color: #E0E0E0;
        }

        .matches-list {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .matches-list::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .match-item {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .match-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #7B1FA2;
        }

        .match-item:hover {
            background: #333;
            border-color: #7B1FA2;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(123, 31, 162, 0.3);
        }

        .match-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto auto;
            gap: 25px;
            align-items: center;
            margin-bottom: 20px;
        }

        .team-info {
            text-align: center;
        }

        .team-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #E1BEE7;
        }

        .team-group {
            font-size: 0.9rem;
            color: #9E9E9E;
            font-weight: 500;
        }

        .match-score {
            font-size: 2rem;
            font-weight: 700;
            color: #E1BEE7;
            text-align: center;
            background: #1E1E1E;
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid #7B1FA2;
        }

        .vs-text {
            font-size: 1.3rem;
            color: #9E9E9E;
            font-weight: 600;
        }

        .match-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            font-size: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #E0E0E0;
        }

        .info-item i {
            color: #7B1FA2;
            width: 20px;
        }

        .match-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            border: 2px solid;
        }

        .status-agendado {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
            border-color: #FF9800;
        }
        .status-em_andamento {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            border-color: #2196F3;
        }
        .status-finalizado {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border-color: #4CAF50;
        }
        .status-cancelado {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border-color: #F44336;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #9E9E9E;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 25px;
            color: #7B1FA2;
            opacity: 0.6;
        }

        .empty-state h3 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: #E1BEE7;
        }

        .empty-state p {
            font-size: 1.1rem;
            line-height: 1.5;
        }

        /* Animações */
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
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .match-header {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-futbol"></i> Todos os Jogos</h1>
                <p style="margin: 8px 0; color: #9E9E9E; font-size: 1.1rem;">Visualize e gerencie todos os jogos do sistema</p>
            </div>
            <a href="dashboard_simple.php" class="btn-standard">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid fade-in" style="animation-delay: 0.2s;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-futbol"></i></div>
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?= $stats['finished'] ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number"><?= $stats['scheduled'] ?></div>
                <div class="stat-label">Agendados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-number"><?= $stats['today'] ?></div>
                <div class="stat-label">Hoje</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters fade-in" style="animation-delay: 0.3s;">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-trophy"></i> Torneio</label>
                        <select name="tournament" class="filter-input">
                            <option value="">Todos os torneios</option>
                            <?php foreach ($tournaments as $tournament): ?>
                                <option value="<?= $tournament['id'] ?>" <?= $tournament_filter == $tournament['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tournament['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-flag"></i> Status</label>
                        <select name="status" class="filter-input">
                            <option value="">Todos os status</option>
                            <option value="agendado" <?= $status_filter == 'agendado' ? 'selected' : '' ?>>Agendado</option>
                            <option value="em_andamento" <?= $status_filter == 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="finalizado" <?= $status_filter == 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            <option value="cancelado" <?= $status_filter == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-calendar"></i> Data</label>
                        <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($date_filter) ?>">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn-standard">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista de Jogos -->
        <div class="matches-list fade-in" style="animation-delay: 0.4s;">
            <?php if (!empty($matches)): ?>
                <?php foreach ($matches as $index => $match): ?>
                    <div class="match-item">
                        <div class="match-header">
                            <div class="team-info">
                                <div class="team-name"><?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?></div>
                                <?php if ($match['group_name']): ?>
                                    <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="match-score">
                                <?php if ($match['status'] === 'finalizado' && $match['team1_goals'] !== null): ?>
                                    <?= $match['team1_goals'] ?> - <?= $match['team2_goals'] ?>
                                <?php else: ?>
                                    <div class="vs-text">VS</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="team-info">
                                <div class="team-name"><?= htmlspecialchars($match['team2_name'] ?? 'Time 2') ?></div>
                                <?php if ($match['group_name']): ?>
                                    <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="match-status status-<?= $match['status'] ?>">
                                <?= ucfirst($match['status']) ?>
                            </div>
                            
                            <div>
                                <a href="edit_match.php?tournament_id=<?= $match['tournament_id'] ?>&match_id=<?= $match['id'] ?>"
                                   class="btn-standard" style="padding: 10px 18px; font-size: 0.9rem;">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                        </div>

                        <div class="match-info">
                            <div class="info-item">
                                <i class="fas fa-trophy"></i>
                                <span><?= htmlspecialchars($match['tournament_name']) ?></span>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-layer-group"></i>
                                <span><?= ucfirst($match['phase']) ?></span>
                            </div>

                            <?php if ($match['match_date']): ?>
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y', strtotime($match['match_date'])) ?></span>
                                </div>

                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= date('H:i', strtotime($match['match_date'])) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="info-item">
                                    <i class="fas fa-calendar-times"></i>
                                    <span>Sem data agendada</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-futbol"></i>
                    <h3>Nenhum Jogo Encontrado</h3>
                    <p>Não há jogos que correspondam aos filtros selecionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animações e interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Animações de entrada para cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, (index * 100) + 400);
            });

            // Animações de entrada para itens de jogos
            const matchItems = document.querySelectorAll('.match-item');
            matchItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-30px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, (index * 100) + 800);
            });

            // Efeitos hover adicionais
            matchItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.borderColor = '#E1BEE7';
                });

                item.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'transparent';
                });
            });
        });
    </script>
</body>
</html>
