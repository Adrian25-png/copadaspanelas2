<?php
/**
 * Agenda de Jogos - Sistema de Agendamento
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

// Processar agendamentos
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'schedule_single':
                $match_id = $_POST['match_id'];
                $match_date = $_POST['match_date'];
                $match_time = $_POST['match_time'];
                
                $matchManager->scheduleMatch($match_id, $match_date, $match_time);
                $tournamentManager->logActivity($tournament_id, 'JOGO_AGENDADO', "Jogo ID $match_id agendado para $match_date $match_time");
                $_SESSION['success'] = "Jogo agendado com sucesso!";
                break;
                
            case 'schedule_multiple':
                $schedules = $_POST['schedules'] ?? [];
                $updated_count = $matchManager->scheduleMultipleMatches($schedules);
                
                if ($updated_count > 0) {
                    $tournamentManager->logActivity($tournament_id, 'JOGOS_AGENDADOS', "$updated_count jogos agendados em lote");
                    $_SESSION['success'] = "$updated_count jogos agendados com sucesso!";
                } else {
                    $_SESSION['warning'] = "Nenhum jogo foi agendado.";
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: match_schedule.php?tournament_id=$tournament_id");
    exit;
}

// Obter dados
$unscheduled_matches = $matchManager->getUnscheduledMatches($tournament_id);
$calendar_matches = $matchManager->getMatchCalendar($tournament_id, 30);

// Agrupar jogos do calendário por data
$calendar_by_date = [];
foreach ($calendar_matches as $match) {
    $date = $match['match_date_only'];
    $calendar_by_date[$date][] = $match;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Jogos - <?= htmlspecialchars($tournament['name']) ?></title>
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
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #2ecc71;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .alert-warning {
            background: rgba(243, 156, 18, 0.2);
            border: 1px solid #f39c12;
            color: #f39c12;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f39c12;
        }
        
        .match-row {
            display: grid;
            grid-template-columns: 2fr auto 2fr 150px 100px auto;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .team-name {
            font-weight: 600;
            text-align: center;
        }
        
        .vs-divider {
            text-align: center;
            font-weight: bold;
            color: #3498db;
            font-size: 1.2rem;
        }
        
        .schedule-input {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .schedule-input input {
            padding: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
        }
        
        .schedule-input input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .schedule-input input[type="date"] {
            color-scheme: dark;
        }
        
        .schedule-input input[type="time"] {
            color-scheme: dark;
        }
        
        .match-info {
            font-size: 0.9rem;
            opacity: 0.7;
            text-align: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .calendar-day {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .calendar-date {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #3498db;
        }
        
        .calendar-match {
            display: grid;
            grid-template-columns: auto 2fr auto 2fr auto;
            gap: 15px;
            align-items: center;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .match-time {
            font-weight: bold;
            color: #f39c12;
            font-size: 1.1rem;
        }
        
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .match-row {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 10px;
            }
            
            .calendar-match {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-calendar-alt"></i> Agenda de Jogos</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="match_calendar.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                    <i class="fas fa-calendar"></i> Calendário Visual
                </a>
                <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($_SESSION['warning']) ?>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>
        
        <!-- Jogos sem Data -->
        <?php if (!empty($unscheduled_matches)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i> Jogos sem Data Agendada
                    <span style="font-size: 1rem; opacity: 0.7;">(<?= count($unscheduled_matches) ?> jogos)</span>
                </h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="schedule_multiple">
                    
                    <?php foreach ($unscheduled_matches as $match): ?>
                        <div class="match-row">
                            <div class="team-name">
                                <?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?>
                                <?php if ($match['group_name']): ?>
                                    <div class="match-info"><?= htmlspecialchars($match['group_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="vs-divider">VS</div>
                            
                            <div class="team-name">
                                <?= htmlspecialchars($match['team2_name'] ?? 'Time 2') ?>
                                <?php if ($match['group_name']): ?>
                                    <div class="match-info"><?= htmlspecialchars($match['group_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="schedule-input">
                                <input type="date" 
                                       name="schedules[<?= $match['id'] ?>][date]" 
                                       min="<?= date('Y-m-d') ?>"
                                       title="Data do jogo">
                            </div>
                            
                            <div class="schedule-input">
                                <input type="time" 
                                       name="schedules[<?= $match['id'] ?>][time]" 
                                       value="20:00"
                                       title="Horário do jogo">
                            </div>
                            
                            <div class="match-info">
                                <?= ucfirst($match['phase']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-calendar-plus"></i> Agendar Jogos Selecionados
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="clearAllDates()">
                            <i class="fas fa-eraser"></i> Limpar Datas
                        </button>
                        
                        <button type="button" class="btn btn-warning" onclick="fillWeekendDates()">
                            <i class="fas fa-magic"></i> Preencher Fins de Semana
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Calendário de Jogos -->
        <?php if (!empty($calendar_by_date)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-calendar-check"></i> Próximos Jogos Agendados
                </h2>
                
                <?php foreach ($calendar_by_date as $date => $matches): ?>
                    <div class="calendar-day">
                        <div class="calendar-date">
                            <i class="fas fa-calendar-day"></i>
                            <?= date('d/m/Y - l', strtotime($date)) ?>
                        </div>
                        
                        <?php foreach ($matches as $match): ?>
                            <div class="calendar-match">
                                <div class="match-time">
                                    <?= date('H:i', strtotime($match['match_time_only'])) ?>
                                </div>
                                
                                <div class="team-name">
                                    <?= htmlspecialchars($match['team1_name']) ?>
                                    <?php if ($match['group_name']): ?>
                                        <div class="match-info"><?= htmlspecialchars($match['group_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="vs-divider">VS</div>
                                
                                <div class="team-name">
                                    <?= htmlspecialchars($match['team2_name']) ?>
                                    <?php if ($match['group_name']): ?>
                                        <div class="match-info"><?= htmlspecialchars($match['group_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <span class="btn btn-primary" style="font-size: 0.8rem;">
                                        <?= ucfirst($match['phase']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="section">
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nenhum Jogo Agendado</h3>
                    <p>Agende os jogos acima para visualizar o calendário.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function clearAllDates() {
            if (confirm('Limpar todas as datas selecionadas?')) {
                document.querySelectorAll('input[type="date"]').forEach(input => input.value = '');
                document.querySelectorAll('input[type="time"]').forEach(input => input.value = '20:00');
            }
        }
        
        function fillWeekendDates() {
            if (confirm('Preencher automaticamente com datas de fins de semana?')) {
                const dateInputs = document.querySelectorAll('input[type="date"]');
                let currentDate = new Date();
                
                // Encontrar próximo sábado
                while (currentDate.getDay() !== 6) {
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                dateInputs.forEach((input, index) => {
                    if (!input.value) {
                        // Alternar entre sábado e domingo
                        if (index % 2 === 1) {
                            currentDate.setDate(currentDate.getDate() + 1); // Domingo
                        }
                        
                        const dateStr = currentDate.toISOString().split('T')[0];
                        input.value = dateStr;
                        
                        if (index % 2 === 1) {
                            // Próximo sábado
                            currentDate.setDate(currentDate.getDate() + 6);
                        }
                    }
                });
            }
        }
        
        // Auto-save quando data é alterada
        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Opcional: salvar automaticamente quando data é alterada
                    // Pode implementar AJAX aqui se desejar
                });
            });
        });
    </script>
</body>
</html>
