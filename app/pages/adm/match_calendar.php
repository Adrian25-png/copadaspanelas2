<?php
/**
 * Calendário Visual de Jogos
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../classes/MatchManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;

if (!$tournament_id) {
    $_SESSION['error'] = "ID do torneio não especificado";
    header('Location: tournament_list.php');
    exit;
}

$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    $_SESSION['error'] = "Torneio não encontrado";
    header('Location: tournament_list.php');
    exit;
}

$matchManager = new MatchManager($pdo, $tournament_id);

// Obter mês e ano atual ou especificado
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Validar mês e ano
$month = max(1, min(12, (int)$month));
$year = max(2020, min(2030, (int)$year));

// Obter jogos do mês
$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$stmt = $pdo->prepare("
    SELECT m.*, 
           t1.nome as team1_name, t2.nome as team2_name,
           g.nome as group_name,
           DATE(m.match_date) as match_date_only,
           TIME(m.match_date) as match_time_only
    FROM matches m
    LEFT JOIN times t1 ON m.team1_id = t1.id
    LEFT JOIN times t2 ON m.team2_id = t2.id
    LEFT JOIN grupos g ON m.group_id = g.id
    WHERE m.tournament_id = ? 
    AND m.match_date IS NOT NULL
    AND DATE(m.match_date) BETWEEN ? AND ?
    ORDER BY m.match_date
");
$stmt->execute([$tournament_id, $start_date, $end_date]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar jogos por data
$matches_by_date = [];
foreach ($matches as $match) {
    $date = $match['match_date_only'];
    $matches_by_date[$date][] = $match;
}

// Gerar calendário
$first_day = date('w', strtotime($start_date)); // Dia da semana do primeiro dia
$days_in_month = date('t', strtotime($start_date));

// Nomes dos meses
$month_names = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// URLs para navegação
$prev_month = $month == 1 ? 12 : $month - 1;
$prev_year = $month == 1 ? $year - 1 : $year;
$next_month = $month == 12 ? 1 : $month + 1;
$next_year = $month == 12 ? $year + 1 : $year;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário de Jogos - <?= htmlspecialchars($tournament['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
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
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
        }
        
        .nav-btn {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .month-year {
            font-size: 1.8rem;
            font-weight: bold;
            color: #f39c12;
        }
        
        .calendar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            overflow: hidden;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        
        .calendar-header {
            background: rgba(52, 152, 219, 0.3);
            padding: 15px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .calendar-day {
            background: rgba(255, 255, 255, 0.1);
            min-height: 120px;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            transition: all 0.3s ease;
        }
        
        .calendar-day:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .calendar-day.other-month {
            opacity: 0.3;
        }
        
        .calendar-day.today {
            background: rgba(241, 196, 15, 0.3);
            border: 2px solid #f1c40f;
        }
        
        .calendar-day.has-matches {
            background: rgba(46, 204, 113, 0.3);
            border: 2px solid #2ecc71;
        }
        
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .day-match {
            background: rgba(52, 152, 219, 0.8);
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.7rem;
            margin-bottom: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .day-match:hover {
            background: rgba(52, 152, 219, 1);
            transform: scale(1.05);
        }
        
        .match-time {
            font-weight: bold;
            color: #f39c12;
        }
        
        .match-teams {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .match-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            border-radius: 15px;
            padding: 30px;
            z-index: 1000;
            min-width: 400px;
            border: 2px solid #3498db;
            display: none;
        }
        
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .popup-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .legend-today { background: rgba(241, 196, 15, 0.5); }
        .legend-matches { background: rgba(46, 204, 113, 0.5); }
        .legend-normal { background: rgba(255, 255, 255, 0.1); }
        
        @media (max-width: 768px) {
            .calendar-grid {
                font-size: 0.8rem;
            }
            
            .calendar-day {
                min-height: 80px;
                padding: 4px;
            }
            
            .day-match {
                font-size: 0.6rem;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .calendar-nav {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-calendar"></i> Calendário de Jogos</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <a href="match_schedule.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <div class="calendar-nav">
            <a href="?tournament_id=<?= $tournament_id ?>&month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="nav-btn">
                <i class="fas fa-chevron-left"></i> <?= $month_names[$prev_month] ?>
            </a>
            
            <div class="month-year">
                <?= $month_names[$month] ?> <?= $year ?>
            </div>
            
            <a href="?tournament_id=<?= $tournament_id ?>&month=<?= $next_month ?>&year=<?= $next_year ?>" class="nav-btn">
                <?= $month_names[$next_month] ?> <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        
        <div class="calendar">
            <div class="calendar-grid">
                <!-- Cabeçalho dos dias da semana -->
                <div class="calendar-header">Dom</div>
                <div class="calendar-header">Seg</div>
                <div class="calendar-header">Ter</div>
                <div class="calendar-header">Qua</div>
                <div class="calendar-header">Qui</div>
                <div class="calendar-header">Sex</div>
                <div class="calendar-header">Sáb</div>
                
                <!-- Dias vazios antes do primeiro dia do mês -->
                <?php for ($i = 0; $i < $first_day; $i++): ?>
                    <div class="calendar-day other-month"></div>
                <?php endfor; ?>
                
                <!-- Dias do mês -->
                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                    <?php
                    $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $is_today = $current_date === date('Y-m-d');
                    $has_matches = isset($matches_by_date[$current_date]);
                    $day_matches = $matches_by_date[$current_date] ?? [];
                    
                    $classes = ['calendar-day'];
                    if ($is_today) $classes[] = 'today';
                    if ($has_matches) $classes[] = 'has-matches';
                    ?>
                    
                    <div class="<?= implode(' ', $classes) ?>">
                        <div class="day-number"><?= $day ?></div>
                        
                        <?php foreach ($day_matches as $match): ?>
                            <div class="day-match" onclick="showMatchDetails(<?= htmlspecialchars(json_encode($match)) ?>)">
                                <div class="match-time"><?= date('H:i', strtotime($match['match_time_only'])) ?></div>
                                <div class="match-teams">
                                    <?= htmlspecialchars(substr($match['team1_name'], 0, 8)) ?> vs <?= htmlspecialchars(substr($match['team2_name'], 0, 8)) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
                
                <!-- Dias vazios após o último dia do mês -->
                <?php
                $remaining_cells = 42 - ($first_day + $days_in_month);
                for ($i = 0; $i < $remaining_cells; $i++):
                ?>
                    <div class="calendar-day other-month"></div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color legend-today"></div>
                <span>Hoje</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-matches"></div>
                <span>Dias com jogos</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-normal"></div>
                <span>Dias normais</span>
            </div>
        </div>
    </div>
    
    <!-- Popup para detalhes do jogo -->
    <div class="popup-overlay" onclick="closeMatchDetails()"></div>
    <div class="match-popup" id="matchPopup">
        <button class="popup-close" onclick="closeMatchDetails()">&times;</button>
        <div id="matchDetails"></div>
    </div>
    
    <script>
        function showMatchDetails(match) {
            const popup = document.getElementById('matchPopup');
            const overlay = document.querySelector('.popup-overlay');
            const details = document.getElementById('matchDetails');
            
            const matchDate = new Date(match.match_date);
            const formattedDate = matchDate.toLocaleDateString('pt-BR');
            const formattedTime = matchDate.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            
            details.innerHTML = `
                <h3 style="color: #3498db; margin-bottom: 20px;">
                    <i class="fas fa-futbol"></i> Detalhes do Jogo
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center; margin-bottom: 20px;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.3rem; font-weight: bold;">${match.team1_name}</div>
                        ${match.group_name ? `<div style="opacity: 0.7;">${match.group_name}</div>` : ''}
                    </div>
                    
                    <div style="text-align: center; font-size: 1.5rem; font-weight: bold; color: #f39c12;">
                        ${match.status === 'finalizado' ? `${match.team1_goals} - ${match.team2_goals}` : 'VS'}
                    </div>
                    
                    <div style="text-align: center;">
                        <div style="font-size: 1.3rem; font-weight: bold;">${match.team2_name}</div>
                        ${match.group_name ? `<div style="opacity: 0.7;">${match.group_name}</div>` : ''}
                    </div>
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px;">
                    <div style="margin-bottom: 10px;">
                        <i class="fas fa-calendar"></i> <strong>Data:</strong> ${formattedDate}
                    </div>
                    <div style="margin-bottom: 10px;">
                        <i class="fas fa-clock"></i> <strong>Horário:</strong> ${formattedTime}
                    </div>
                    <div style="margin-bottom: 10px;">
                        <i class="fas fa-layer-group"></i> <strong>Fase:</strong> ${match.phase.charAt(0).toUpperCase() + match.phase.slice(1)}
                    </div>
                    <div>
                        <i class="fas fa-info-circle"></i> <strong>Status:</strong> ${match.status.charAt(0).toUpperCase() + match.status.slice(1)}
                    </div>
                </div>
            `;
            
            overlay.style.display = 'block';
            popup.style.display = 'block';
        }
        
        function closeMatchDetails() {
            document.querySelector('.popup-overlay').style.display = 'none';
            document.getElementById('matchPopup').style.display = 'none';
        }
        
        // Fechar popup com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMatchDetails();
            }
        });
    </script>
</body>
</html>
