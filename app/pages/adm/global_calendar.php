<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Filtros
$tournament_filter = $_GET['tournament'] ?? '';
$month_filter = $_GET['month'] ?? date('Y-m');
$status_filter = $_GET['status'] ?? '';

// Construir query
$where_conditions = ["1=1"];
$params = [];

if ($tournament_filter) {
    $where_conditions[] = "m.tournament_id = ?";
    $params[] = $tournament_filter;
}

if ($month_filter) {
    $where_conditions[] = "DATE_FORMAT(m.match_date, '%Y-%m') = ?";
    $params[] = $month_filter;
}

if ($status_filter) {
    $where_conditions[] = "m.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Obter jogos
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               t1.nome as team1_name, 
               t2.nome as team2_name,
               g.nome as group_name,
               tour.name as tournament_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN grupos g ON m.group_id = g.id
        LEFT JOIN tournaments tour ON m.tournament_id = tour.id
        WHERE $where_clause
        ORDER BY m.match_date ASC, m.created_at ASC
    ");
    $stmt->execute($params);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $matches = [];
    $_SESSION['error'] = "Erro ao carregar jogos: " . $e->getMessage();
}

// Obter torneios para filtro
try {
    $stmt = $pdo->query("SELECT id, name FROM tournaments ORDER BY name");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tournaments = [];
}

// Agrupar jogos por data
$matches_by_date = [];
foreach ($matches as $match) {
    if ($match['match_date']) {
        $date = date('Y-m-d', strtotime($match['match_date']));
        $matches_by_date[$date][] = $match;
    } else {
        $matches_by_date['sem_data'][] = $match;
    }
}

// Estatísticas
$total_matches = count($matches);
$finished_matches = count(array_filter($matches, fn($m) => $m['status'] === 'finalizado'));
$scheduled_matches = count(array_filter($matches, fn($m) => $m['status'] === 'agendado'));
$ongoing_matches = count(array_filter($matches, fn($m) => $m['status'] === 'em_andamento'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Global - Copa das Panelas</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .calendar-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .date-header {
            font-size: 1.2rem;
            font-weight: bold;
            color: #f39c12;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(243, 156, 18, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .match-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 15px;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .match-item:hover {
            background: rgba(0, 0, 0, 0.3);
            transform: translateX(5px);
        }
        
        .match-time {
            font-weight: bold;
            color: #3498db;
            font-size: 0.9rem;
        }
        
        .match-teams {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .team-name {
            font-weight: bold;
        }
        
        .vs-text {
            color: #f39c12;
            font-weight: bold;
        }
        
        .match-score {
            font-size: 1.1rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .match-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-finalizado { background: #27ae60; }
        .status-agendado { background: #f39c12; }
        .status-em_andamento { background: #3498db; }
        .status-cancelado { background: #e74c3c; }
        
        .match-info {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
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
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .match-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-calendar-alt"></i> Calendário Global</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Todos os jogos do sistema</p>
            </div>
            <a href="dashboard_simple.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
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
                        <label class="filter-label">Mês</label>
                        <input type="month" name="month" class="filter-input" value="<?= htmlspecialchars($month_filter) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-input">
                            <option value="">Todos os status</option>
                            <option value="agendado" <?= $status_filter === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                            <option value="em_andamento" <?= $status_filter === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="finalizado" <?= $status_filter === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            <option value="cancelado" <?= $status_filter === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_matches ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $finished_matches ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $scheduled_matches ?></div>
                <div class="stat-label">Agendados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $ongoing_matches ?></div>
                <div class="stat-label">Em Andamento</div>
            </div>
        </div>
        
        <!-- Calendário de Jogos -->
        <?php if (!empty($matches_by_date)): ?>
            <?php foreach ($matches_by_date as $date => $date_matches): ?>
                <div class="calendar-section">
                    <div class="date-header">
                        <i class="fas fa-calendar-day"></i>
                        <?php if ($date === 'sem_data'): ?>
                            Jogos sem Data Definida
                        <?php else: ?>
                            <?= date('d/m/Y - l', strtotime($date)) ?>
                        <?php endif; ?>
                        <span style="font-size: 0.9rem; opacity: 0.8;">(<?= count($date_matches) ?> jogos)</span>
                    </div>
                    
                    <?php foreach ($date_matches as $match): ?>
                        <div class="match-item">
                            <div class="match-time">
                                <?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : 'A definir' ?>
                            </div>
                            
                            <div class="match-teams">
                                <span class="team-name"><?= htmlspecialchars($match['team1_name']) ?></span>
                                <?php if ($match['status'] === 'finalizado' && $match['team1_goals'] !== null): ?>
                                    <span class="match-score"><?= $match['team1_goals'] ?> x <?= $match['team2_goals'] ?></span>
                                <?php else: ?>
                                    <span class="vs-text">VS</span>
                                <?php endif; ?>
                                <span class="team-name"><?= htmlspecialchars($match['team2_name']) ?></span>
                            </div>
                            
                            <div>
                                <span class="match-status status-<?= $match['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $match['status'])) ?>
                                </span>
                                <div class="match-info">
                                    <?= htmlspecialchars($match['tournament_name']) ?>
                                    <?php if ($match['group_name']): ?>
                                        - <?= htmlspecialchars($match['group_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Nenhum Jogo Encontrado</h3>
                <p>Não há jogos que correspondam aos filtros selecionados.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.calendar-section, .stat-card');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
