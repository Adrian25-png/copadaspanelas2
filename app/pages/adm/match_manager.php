<?php
/**
 * Gerenciador de Jogos - Sistema Completo
 * Criado do zero para o Copa das Panelas
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../classes/MatchManager.php';
require_once '../../includes/PermissionManager.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$permissionManager = getPermissionManager($pdo);

// Verificar permissão para gerenciar jogos
$permissionManager->requireAnyPermission(['create_match', 'edit_match', 'view_match']);

// Obter ID do torneio
$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;

if (!$tournament_id) {
    $_SESSION['error'] = "ID do torneio não especificado";
    header('Location: tournament_list.php');
    exit;
}

// Verificar se o torneio existe
$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    $_SESSION['error'] = "Torneio não encontrado (ID: $tournament_id)";
    header('Location: tournament_list.php');
    exit;
}

// Inicializar gerenciador de jogos
$matchManager = new MatchManager($pdo, $tournament_id);

// Processar ações
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'generate_matches':
                $matches_created = $matchManager->generateGroupMatches($tournament_id);
                $tournamentManager->logActivity($tournament_id, 'JOGOS_GERADOS', "$matches_created jogos da fase de grupos gerados");
                $_SESSION['success'] = "$matches_created jogos gerados com sucesso!";
                break;
                
            case 'update_match':
                $match_id = $_POST['match_id'];
                $team1_goals = (int)$_POST['team1_goals'];
                $team2_goals = (int)$_POST['team2_goals'];
                $match_date = $_POST['match_date'] ?: null;
                
                $matchManager->updateMatchResult($match_id, $team1_goals, $team2_goals, $match_date);
                $tournamentManager->logActivity($tournament_id, 'RESULTADO_ATUALIZADO', "Resultado atualizado para jogo ID $match_id: $team1_goals x $team2_goals");
                $_SESSION['success'] = "Resultado atualizado com sucesso!";
                break;
                
            case 'delete_match':
                $match_id = $_POST['match_id'];
                $matchManager->deleteMatch($match_id);
                $tournamentManager->logActivity($tournament_id, 'JOGO_EXCLUIDO', "Jogo ID $match_id excluído");
                $_SESSION['success'] = "Jogo excluído com sucesso!";
                break;
                
            case 'recalculate_stats':
                $matchManager->recalculateAllStatistics($tournament_id);
                $tournamentManager->logActivity($tournament_id, 'ESTATISTICAS_RECALCULADAS', "Estatísticas do torneio recalculadas");
                $_SESSION['success'] = "Estatísticas recalculadas com sucesso!";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: match_manager.php?tournament_id=$tournament_id");
    exit;
}

// Obter dados para exibição
$matches = $matchManager->getTournamentMatches($tournament_id);
$statistics = $matchManager->getTournamentStatistics($tournament_id);

// Agrupar jogos por fase
$matches_by_phase = [];
foreach ($matches as $match) {
    $matches_by_phase[$match['phase']][] = $match;
}

// Tradução das fases
$phase_names = [
    'grupos' => 'Fase de Grupos',
    'oitavas' => 'Oitavas de Final',
    'quartas' => 'Quartas de Final',
    'semifinal' => 'Semifinal',
    'final' => 'Final',
    'terceiro_lugar' => 'Terceiro Lugar'
];

// Tradução dos status
$status_names = [
    'agendado' => 'Agendado',
    'em_andamento' => 'Em Andamento',
    'finalizado' => 'Finalizado',
    'cancelado' => 'Cancelado'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Jogos - <?= htmlspecialchars($tournament['name']) ?></title>
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

        .tournament-info {
            color: #9E9E9E;
            font-size: 1.1rem;
            margin-top: 5px;
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
            background: #252525;
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 8px;
            padding-top: 5px;
        }

        .stat-label {
            font-size: 1rem;
            color: #9E9E9E;
            font-weight: 500;
        }

        .actions-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .actions-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .actions-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-top: 5px;
        }

        .actions-title i {
            color: #7B1FA2;
        }
        
        .btn-standard {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
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
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-warning {
            border-color: #FF9800;
            color: #FF9800;
        }

        .btn-warning:hover {
            background: #FF9800;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
        }

        .btn-danger {
            border-color: #F44336;
            color: #F44336;
        }

        .btn-danger:hover {
            background: #F44336;
            color: white;
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
        }

        .btn-info {
            border-color: #2196F3;
            color: #2196F3;
        }

        .btn-info:hover {
            background: #2196F3;
            color: white;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-sm {
            padding: 10px 18px;
            font-size: 0.9rem;
        }

        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            border-color: #666;
            color: #666;
        }

        .matches-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .matches-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .phase-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-top: 5px;
        }

        .phase-title i {
            color: #7B1FA2;
        }

        .match-card {
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .match-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .match-card:hover {
            transform: translateY(-3px);
            background: #333;
            box-shadow: 0 8px 20px rgba(123, 31, 162, 0.3);
        }

        .match-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
            padding-top: 5px;
        }

        .team-info {
            text-align: center;
        }

        .team-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #E1BEE7;
        }

        .team-group {
            font-size: 0.9rem;
            color: #9E9E9E;
        }

        .match-score {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            color: #E1BEE7;
            background: #7B1FA2;
            padding: 15px 20px;
            border-radius: 8px;
            min-width: 80px;
        }

        .vs-text {
            font-size: 1.2rem;
            color: #9E9E9E;
            font-weight: 600;
        }

        .match-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .match-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
            display: inline-block;
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
            padding: 80px 20px;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            color: #E0E0E0;
            position: relative;
            overflow: hidden;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #E1BEE7;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #7B1FA2;
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

        .modal-title {
            font-size: 1.4rem;
            margin-bottom: 25px;
            text-align: center;
            color: #E1BEE7;
            padding-top: 5px;
        }

        .modal-teams {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 25px;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-team {
            text-align: center;
        }

        .modal-team-name {
            font-weight: 600;
            margin-bottom: 15px;
            color: #E1BEE7;
            font-size: 1.1rem;
        }

        .modal-score-input {
            width: 80px;
            padding: 12px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E1BEE7;
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            font-family: 'Space Grotesk', sans-serif;
        }

        .modal-score-input:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333;
            box-shadow: 0 0 10px rgba(123, 31, 162, 0.3);
        }

        .modal-vs {
            font-size: 1.4rem;
            font-weight: 600;
            color: #9E9E9E;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .quick-scores {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin: 25px 0;
        }

        .quick-score-btn {
            padding: 10px;
            background: #2A2A2A;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .quick-score-btn:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .match-header {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .match-actions {
                flex-direction: column;
            }

            .main-container {
                padding: 15px;
            }

            .modal-teams {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .modal-vs {
                order: -1;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .match-score {
                font-size: 1.8rem;
                padding: 12px 15px;
            }

            .quick-scores {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-futbol"></i> Gerenciar Jogos</h1>
                <div class="tournament-info"><?= htmlspecialchars($tournament['name']) ?> - <?= $tournament['year'] ?></div>
            </div>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Torneio
            </a>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="stats-grid fade-in" style="animation-delay: 0.2s;">
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['total_matches'] ?? 0 ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['completed_matches'] ?? 0 ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['scheduled_matches'] ?? 0 ?></div>
                <div class="stat-label">Agendados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['total_goals'] ?? 0 ?></div>
                <div class="stat-label">Total de Gols</div>
            </div>
        </div>

        <!-- Ações -->
        <div class="actions-section fade-in" style="animation-delay: 0.4s;">
            <h2 class="actions-title"><i class="fas fa-tools"></i> Ações Rápidas</h2>

            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="generate_matches">
                <button type="submit" class="btn-standard btn-success" onclick="return confirm('Gerar jogos da fase de grupos? Jogos já existentes não serão duplicados.')">
                    <i class="fas fa-plus-circle"></i> Gerar Jogos da Fase de Grupos
                </button>
            </form>

            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="recalculate_stats">
                <button type="submit" class="btn-standard btn-warning">
                    <i class="fas fa-calculator"></i> Recalcular Estatísticas
                </button>
            </form>

            <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn-standard btn-info">
                <i class="fas fa-trophy"></i> Ver Classificação
            </a>

            <a href="bulk_results.php?tournament=<?= $tournament_id ?>" class="btn-standard">
                <i class="fas fa-edit"></i> Resultados Rápidos
            </a>

            <a href="match_schedule.php?tournament_id=<?= $tournament_id ?>" class="btn btn-info">
                <i class="fas fa-calendar-alt"></i> Agenda de Jogos
            </a>

            <a href="bulk_edit_matches.php?tournament_id=<?= $tournament_id ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edição em Lote
            </a>
        </div>
        
        <!-- Lista de Jogos por Fase -->
        <?php if (!empty($matches_by_phase)): ?>
            <?php foreach ($matches_by_phase as $phase => $phase_matches): ?>
                <div class="matches-section">
                    <div class="phase-title">
                        <i class="fas fa-layer-group"></i>
                        <?= $phase_names[$phase] ?? ucfirst($phase) ?>
                        <span style="font-size: 1rem; opacity: 0.7;">(<?= count($phase_matches) ?> jogos)</span>
                    </div>
                    
                    <?php foreach ($phase_matches as $match): ?>
                        <div class="match-card">
                            <div class="match-header">
                                <div class="team-info">
                                    <div class="team-name"><?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?></div>
                                    <?php if ($match['group_name']): ?>
                                        <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="match-score">
                                    <?php if ($match['status'] === 'finalizado'): ?>
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

                                <div class="match-actions">
                                    <span class="match-status status-<?= $match['status'] ?>">
                                        <?= $status_names[$match['status']] ?? ucfirst($match['status']) ?>
                                    </span>

                                    <?php if ($match['match_date']): ?>
                                        <div style="font-size: 0.9rem; color: #f39c12; margin: 5px 0;">
                                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                                            <br>
                                            <i class="fas fa-clock"></i> <?= date('H:i', strtotime($match['match_date'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size: 0.9rem; color: #95a5a6; margin: 5px 0;">
                                            <i class="fas fa-calendar-times"></i> Sem data agendada
                                        </div>
                                    <?php endif; ?>

                                    <a href="edit_match.php?tournament_id=<?= $tournament_id ?>&match_id=<?= $match['id'] ?>"
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>

                                    <button onclick="quickEdit(<?= $match['id'] ?>, '<?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?>', '<?= htmlspecialchars($match['team2_name'] ?? 'Time 2') ?>', <?= $match['team1_goals'] ?? 0 ?>, <?= $match['team2_goals'] ?? 0 ?>)"
                                            class="btn btn-secondary btn-sm">
                                        <i class="fas fa-bolt"></i> Rápido
                                    </button>

                                    <button onclick="deleteMatch(<?= $match['id'] ?>, '<?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?>', '<?= htmlspecialchars($match['team2_name'] ?? 'Time 2') ?>')"
                                            class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-futbol"></i>
                <h3>Nenhum Jogo Cadastrado</h3>
                <p>Clique em "Gerar Jogos da Fase de Grupos" para começar a criar os jogos do torneio.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Edição Rápida -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h2 class="modal-title">Edição Rápida</h2>

            <div class="modal-teams">
                <div class="modal-team">
                    <div class="modal-team-name" id="modalTeam1"></div>
                    <input type="number" id="modalGoals1" class="modal-score-input" min="0" max="99" placeholder="0">
                </div>

                <div class="modal-vs">VS</div>

                <div class="modal-team">
                    <div class="modal-team-name" id="modalTeam2"></div>
                    <input type="number" id="modalGoals2" class="modal-score-input" min="0" max="99" placeholder="0">
                </div>
            </div>

            <div class="quick-scores">
                <button type="button" class="quick-score-btn" onclick="setModalScore(0, 0)">0-0</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(1, 0)">1-0</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(0, 1)">0-1</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(1, 1)">1-1</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(2, 0)">2-0</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(0, 2)">0-2</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(2, 1)">2-1</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(1, 2)">1-2</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(3, 0)">3-0</button>
                <button type="button" class="quick-score-btn" onclick="setModalScore(0, 3)">0-3</button>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-success" onclick="saveModalResult()">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentMatchId = null;

        function quickEdit(id, team1, team2, goals1, goals2) {
            currentMatchId = id;

            // Preencher dados do modal
            document.getElementById('modalTeam1').textContent = team1;
            document.getElementById('modalTeam2').textContent = team2;
            document.getElementById('modalGoals1').value = goals1 || '';
            document.getElementById('modalGoals2').value = goals2 || '';

            // Mostrar modal
            document.getElementById('editModal').style.display = 'block';

            // Focar no primeiro input
            document.getElementById('modalGoals1').focus();
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            currentMatchId = null;
        }

        function setModalScore(goals1, goals2) {
            document.getElementById('modalGoals1').value = goals1;
            document.getElementById('modalGoals2').value = goals2;
        }

        function saveModalResult() {
            if (!currentMatchId) return;

            const goals1 = document.getElementById('modalGoals1').value;
            const goals2 = document.getElementById('modalGoals2').value;

            if (goals1 === '' || goals2 === '') {
                alert('Por favor, preencha ambos os resultados');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_match">
                <input type="hidden" name="match_id" value="${currentMatchId}">
                <input type="hidden" name="team1_goals" value="${goals1}">
                <input type="hidden" name="team2_goals" value="${goals2}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function deleteMatch(id, team1, team2) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_match">
                <input type="hidden" name="match_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Eventos de teclado e inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Animação de entrada para os cards
            const cards = document.querySelectorAll('.match-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Eventos de teclado para o modal
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('editModal');
                if (modal.style.display === 'block') {
                    if (e.key === 'Escape') {
                        closeEditModal();
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        saveModalResult();
                    }
                }
            });

            // Navegação entre inputs do modal com Tab
            const modalInputs = document.querySelectorAll('.modal-score-input');
            modalInputs.forEach((input, index) => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        const nextIndex = (index + 1) % modalInputs.length;
                        modalInputs[nextIndex].focus();
                    }
                });
            });
        });

        // Fechar modal clicando fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover dinâmicos aos stat-cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                    this.style.boxShadow = '0 15px 35px rgba(123, 31, 162, 0.4)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 25px rgba(123, 31, 162, 0.3)';
                });
            });

            // Adicionar efeitos hover aos match-cards
            const matchCards = document.querySelectorAll('.match-card');
            matchCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 12px 30px rgba(123, 31, 162, 0.4)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 8px 20px rgba(123, 31, 162, 0.3)';
                });
            });

            // Animação de contagem para os números das estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(element => {
                const finalValue = parseInt(element.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);

                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    element.textContent = currentValue;
                }, 50);
            });
        });
    </script>
</body>
</html>
