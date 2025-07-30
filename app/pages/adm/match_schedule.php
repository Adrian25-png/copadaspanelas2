<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


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
            max-width: 1200px;
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

        .tournament-info h1 {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .tournament-info h1 i {
            color: #7B1FA2;
        }

        .tournament-year {
            font-size: 1.1rem;
            color: #9E9E9E;
            padding-top: 5px;
        }

        .back-link {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
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
            padding-top: 5px;
        }

        .section-title i {
            color: #7B1FA2;
        }

        .calendar-section {
            margin-bottom: 30px;
        }

        .date-header {
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .date-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .date-header i {
            color: #7B1FA2;
        }

        .match-item {
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 90px 1fr 140px;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .match-item:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.3);
        }

        .match-time {
            font-weight: 700;
            color: #E1BEE7;
            text-align: center;
            background: #7B1FA2;
            padding: 8px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .match-teams {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .team-name {
            font-weight: 600;
            color: #E1BEE7;
        }

        .vs-text {
            color: #9E9E9E;
            font-weight: 600;
        }

        .match-score {
            background: rgba(76, 175, 80, 0.1);
            border: 2px solid #4CAF50;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            color: #4CAF50;
            text-align: center;
        }

        .match-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            border: 2px solid;
        }

        .status-agendado {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            border-color: #2196F3;
        }
        .status-finalizado {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border-color: #4CAF50;
        }
        .status-em_andamento {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
            border-color: #FF9800;
        }
        .status-cancelado {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border-color: #F44336;
        }

        .schedule-form {
            display: grid;
            grid-template-columns: 1fr 110px 110px 130px;
            gap: 15px;
            align-items: center;
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .schedule-form:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .schedule-form input {
            padding: 12px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #1E1E1E;
            color: #E0E0E0;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 500;
        }

        .schedule-form input:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333;
            box-shadow: 0 0 10px rgba(123, 31, 162, 0.3);
        }

        .schedule-form input::placeholder {
            color: #9E9E9E;
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

        .btn-success {
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .btn-success:hover {
            background: #4CAF50;
            color: white;
        }

        .btn-warning {
            border-color: #FF9800;
            color: #FF9800;
        }

        .btn-warning:hover {
            background: #FF9800;
            color: white;
        }

        .btn-info {
            border-color: #2196F3;
            color: #2196F3;
        }

        .btn-info:hover {
            background: #2196F3;
            color: white;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-left-color: #4CAF50;
            color: #4CAF50;
        }

        .alert-success::before {
            background: linear-gradient(90deg, #4CAF50, #81C784);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-left-color: #F44336;
            color: #EF5350;
        }

        .alert-error::before {
            background: linear-gradient(90deg, #F44336, #EF5350);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9E9E9E;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 25px;
            color: #7B1FA2;
            opacity: 0.6;
        }

        .empty-state h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #E1BEE7;
        }

        .empty-state p {
            font-size: 1rem;
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

            .tournament-info h1 {
                font-size: 1.8rem;
            }

            .match-item {
                grid-template-columns: 1fr;
                gap: 15px;
                text-align: center;
            }

            .schedule-form {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .main-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .tournament-info h1 {
                font-size: 1.6rem;
            }

            .date-header {
                padding: 15px;
            }

            .match-item {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
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
            <div class="alert alert-success fade-in" style="animation-delay: 0.2s;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in" style="animation-delay: 0.2s;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Jogos Agendados -->
        <div class="section fade-in" style="animation-delay: 0.3s;">
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
        <div class="section fade-in" style="animation-delay: 0.4s;">
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

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn-standard btn-success">
                        <i class="fas fa-save"></i> Salvar Agendamentos
                    </button>

                    <button type="button" onclick="fillSampleDates()" class="btn-standard btn-info">
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

            // Feedback visual
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Preenchido!';
            button.style.background = '#4CAF50';

            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 2000);
        }

        // Animações e interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover aos cards de jogos
            const matchItems = document.querySelectorAll('.match-item');
            matchItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 8px 20px rgba(123, 31, 162, 0.3)';
                });

                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Adicionar efeitos hover aos formulários de agendamento
            const scheduleForms = document.querySelectorAll('.schedule-form');
            scheduleForms.forEach(form => {
                form.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(123, 31, 162, 0.2)';
                });

                form.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Validação do formulário
            const scheduleForm = document.getElementById('scheduleForm');
            if (scheduleForm) {
                scheduleForm.addEventListener('submit', function(e) {
                    const dateInputs = this.querySelectorAll('input[type="date"]');
                    const timeInputs = this.querySelectorAll('input[type="time"]');

                    let hasSchedule = false;
                    dateInputs.forEach((input, index) => {
                        if (input.value && timeInputs[index].value) {
                            hasSchedule = true;
                        }
                    });

                    if (!hasSchedule) {
                        e.preventDefault();
                        alert('Por favor, preencha pelo menos uma data e horário para agendar.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
