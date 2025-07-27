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
        }
        
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-primary { background: #3498db; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .filters {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-label {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .filter-input {
            padding: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .matches-list {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
        }
        
        .match-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .match-item:hover {
            background: rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }
        
        .match-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto auto;
            gap: 20px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .team-info {
            text-align: center;
        }
        
        .team-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .team-group {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .match-score {
            font-size: 1.8rem;
            font-weight: bold;
            color: #3498db;
            text-align: center;
        }
        
        .vs-text {
            font-size: 1.2rem;
            opacity: 0.7;
        }
        
        .match-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            font-size: 0.9rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .match-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-agendado { background: #f39c12; }
        .status-em_andamento { background: #3498db; }
        .status-finalizado { background: #27ae60; }
        .status-cancelado { background: #e74c3c; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .header {
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-futbol"></i> Todos os Jogos</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Visualize e gerencie todos os jogos do sistema</p>
            </div>
            <a href="dashboard_simple.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['finished'] ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['scheduled'] ?></div>
                <div class="stat-label">Agendados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['today'] ?></div>
                <div class="stat-label">Hoje</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Torneio</label>
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
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-input">
                            <option value="">Todos os status</option>
                            <option value="agendado" <?= $status_filter == 'agendado' ? 'selected' : '' ?>>Agendado</option>
                            <option value="em_andamento" <?= $status_filter == 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="finalizado" <?= $status_filter == 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            <option value="cancelado" <?= $status_filter == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Data</label>
                        <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($date_filter) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Lista de Jogos -->
        <div class="matches-list">
            <?php if (!empty($matches)): ?>
                <?php foreach ($matches as $match): ?>
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
                                   class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">
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
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.match-item, .stat-card');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>
