<?php
/**
 * Agenda de Jogos do Torneio
 * Visualização e agendamento de jogos por data
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../classes/MatchManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['tournament_id'] ?? null;

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

// Processar agendamento em lote
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'schedule_matches') {
    try {
        $schedules = $_POST['schedules'] ?? [];
        $updated_count = $matchManager->scheduleMultipleMatches($schedules);
        
        $_SESSION['success'] = "$updated_count jogos agendados com sucesso!";
        $tournamentManager->logActivity($tournament_id, 'JOGOS_AGENDADOS', "$updated_count jogos agendados");
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao agendar jogos: " . $e->getMessage();
    }
    
    header("Location: match_schedule.php?tournament_id=$tournament_id");
    exit;
}

// Obter jogos agendados e não agendados
try {
    $scheduled_matches = $matchManager->getMatchCalendar($tournament_id, 60); // próximos 60 dias
    $unscheduled_matches = $matchManager->getUnscheduledMatches($tournament_id);
    
    // Agrupar jogos agendados por data
    $matches_by_date = [];
    foreach ($scheduled_matches as $match) {
        $date = date('Y-m-d', strtotime($match['match_date']));
        $matches_by_date[$date][] = $match;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar agenda: " . $e->getMessage();
    $scheduled_matches = [];
    $unscheduled_matches = [];
    $matches_by_date = [];
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
            margin: 0;
            padding: 20px;
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
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .tournament-info h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #f39c12;
        }
        
        .tournament-year {
            font-size: 1.2rem;
            opacity: 0.8;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #f39c12;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .calendar-section {
            margin-bottom: 25px;
        }
        
        .date-header {
            background: rgba(52, 152, 219, 0.3);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: bold;
            color: #3498db;
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
            grid-template-columns: 80px 1fr 120px;
            gap: 15px;
            align-items: center;
        }
        
        .match-time {
            font-weight: bold;
            color: #f39c12;
            text-align: center;
        }
        
        .match-teams {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .team-name {
            font-weight: 600;
        }
        
        .vs-text {
            color: #95a5a6;
            font-weight: bold;
        }
        
        .match-score {
            background: rgba(39, 174, 96, 0.3);
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .match-status {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            text-align: center;
        }
        
        .status-agendado { background: rgba(52, 152, 219, 0.3); color: #3498db; }
        .status-finalizado { background: rgba(39, 174, 96, 0.3); color: #27ae60; }
        .status-em_andamento { background: rgba(243, 156, 18, 0.3); color: #f39c12; }
        .status-cancelado { background: rgba(231, 76, 60, 0.3); color: #e74c3c; }
        
        .schedule-form {
            display: grid;
            grid-template-columns: 1fr 100px 100px 120px;
            gap: 10px;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .schedule-form input {
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .schedule-form input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: rgba(255, 255, 255, 0.2); color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .empty-state {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #95a5a6;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .tournament-info h1 {
                font-size: 2rem;
            }
            
            .match-item {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }
            
            .schedule-form {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="tournament-info">
                <h1><i class="fas fa-calendar-alt"></i> Agenda de Jogos</h1>
                <div class="tournament-year"><?= htmlspecialchars($tournament['name']) ?> - <?= $tournament['year'] ?></div>
            </div>
            <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar aos Jogos
            </a>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Jogos Agendados -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-calendar-check"></i>
                Jogos Agendados
                <span style="font-size: 1rem; opacity: 0.7;">(<?= count($scheduled_matches) ?> jogos)</span>
            </div>
            
            <?php if (empty($matches_by_date)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nenhum Jogo Agendado</h3>
                    <p>Não há jogos com data e horário definidos ainda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($matches_by_date as $date => $date_matches): ?>
                    <div class="calendar-section">
                        <div class="date-header">
                            <i class="fas fa-calendar-day"></i>
                            <?= date('d/m/Y - l', strtotime($date)) ?>
                            <span style="font-size: 0.9rem; opacity: 0.8;">(<?= count($date_matches) ?> jogos)</span>
                        </div>
                        
                        <?php foreach ($date_matches as $match): ?>
                            <div class="match-item">
                                <div class="match-time">
                                    <?= date('H:i', strtotime($match['match_date'])) ?>
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
                                
                                <div class="match-status status-<?= $match['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $match['status'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Jogos Sem Data -->
        <?php if (!empty($unscheduled_matches)): ?>
        <div class="section">
            <div class="section-title">
                <i class="fas fa-calendar-plus"></i>
                Agendar Jogos
                <span style="font-size: 1rem; opacity: 0.7;">(<?= count($unscheduled_matches) ?> jogos)</span>
            </div>
            
            <form method="POST" id="scheduleForm">
                <input type="hidden" name="action" value="schedule_matches">
                
                <?php foreach ($unscheduled_matches as $match): ?>
                    <div class="schedule-form">
                        <div>
                            <strong><?= htmlspecialchars($match['team1_name']) ?></strong> vs <strong><?= htmlspecialchars($match['team2_name']) ?></strong>
                            <?php if ($match['group_name']): ?>
                                <br><small style="opacity: 0.7;"><?= htmlspecialchars($match['group_name']) ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <input type="date" 
                               name="schedules[<?= $match['id'] ?>][date]" 
                               min="<?= date('Y-m-d') ?>"
                               placeholder="Data">
                        
                        <input type="time" 
                               name="schedules[<?= $match['id'] ?>][time]" 
                               placeholder="Horário">
                        
                        <div class="match-status status-<?= $match['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $match['status'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Agendamentos
                    </button>
                    
                    <button type="button" onclick="fillSampleDates()" class="btn btn-secondary">
                        <i class="fas fa-magic"></i> Preencher Datas Exemplo
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function fillSampleDates() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const timeInputs = document.querySelectorAll('input[type="time"]');
            
            let currentDate = new Date();
            currentDate.setDate(currentDate.getDate() + 1); // Começar amanhã
            
            dateInputs.forEach((input, index) => {
                // Distribuir jogos ao longo de vários dias
                const gameDate = new Date(currentDate);
                gameDate.setDate(currentDate.getDate() + Math.floor(index / 3)); // 3 jogos por dia
                
                input.value = gameDate.toISOString().split('T')[0];
            });
            
            timeInputs.forEach((input, index) => {
                // Horários: 14:00, 16:00, 18:00
                const times = ['14:00', '16:00', '18:00'];
                input.value = times[index % 3];
            });
            
            console.log('Datas e horários de exemplo preenchidos!');
        }
    </script>
</body>
</html>
