<?php
/**
 * Página Principal de Gerenciamento de Torneio
 * Dashboard central para gerenciar um torneio específico
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['id'] ?? null;

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

// Obter estatísticas do torneio
try {
    // Contar grupos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $total_groups = $stmt->fetchColumn();
    
    // Contar times
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $total_teams = $stmt->fetchColumn();
    
    // Contar jogadores
    $stmt = $pdo->prepare("
        SELECT COUNT(j.id) 
        FROM jogadores j 
        INNER JOIN times t ON j.time_id = t.id 
        WHERE t.tournament_id = ?
    ");
    $stmt->execute([$tournament_id]);
    $total_players = $stmt->fetchColumn();
    
    // Contar jogos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $total_matches = $stmt->fetchColumn();
    
    // Contar jogos finalizados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND status = 'finished'");
    $stmt->execute([$tournament_id]);
    $finished_matches = $stmt->fetchColumn();

    // Obter fases finais configuradas
    $stmt = $pdo->prepare("SELECT * FROM final_phases WHERE tournament_id = ? ORDER BY phase_order");
    $stmt->execute([$tournament_id]);
    $final_phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $total_groups = $total_teams = $total_players = $total_matches = $finished_matches = 0;
    $final_phases = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Torneio - <?= htmlspecialchars($tournament['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <style>
        /* Reset básico */
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Space Grotesk', sans-serif;
        }

        body {
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            line-height: 1.6;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 30px;
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
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
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #E1BEE7;
            font-weight: 700;
        }

        .tournament-info h1 i {
            color: #7B1FA2;
            font-size: 2.2rem;
        }

        .tournament-year {
            font-size: 1.2rem;
            color: #E0E0E0;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .tournament-status {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            border: 2px solid;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.2);
            border-color: #4CAF50;
            color: #66BB6A;
        }
        .status-draft {
            background: rgba(255, 193, 7, 0.2);
            border-color: #FFC107;
            color: #FFD54F;
        }
        .status-completed {
            background: rgba(33, 150, 243, 0.2);
            border-color: #2196F3;
            color: #64B5F6;
        }
        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            border-color: #F44336;
            color: #EF5350;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: 'Space Grotesk', sans-serif;
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
            margin-bottom: 40px;
        }

        .stat-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
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
            background: #252525;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 15px;
            padding-top: 5px;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #E0E0E0;
            opacity: 0.9;
            font-weight: 500;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .action-card:hover {
            background: #252525;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .action-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-top: 5px;
        }

        .action-title i {
            color: #7B1FA2;
            font-size: 1.3rem;
        }

        .action-description {
            margin-bottom: 25px;
            color: #E0E0E0;
            opacity: 0.9;
            line-height: 1.6;
        }

        .btn-standard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            margin-right: 15px;
            margin-bottom: 15px;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-primary {
            border-color: #7B1FA2;
            color: #E1BEE7;
        }

        .btn-success {
            border-color: #4CAF50;
            color: #66BB6A;
        }

        .btn-success:hover {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-warning {
            border-color: #FFC107;
            color: #FFD54F;
        }

        .btn-warning:hover {
            background: #FFC107;
            border-color: #FFC107;
            color: #1E1E1E;
        }

        .btn-danger {
            border-color: #F44336;
            color: #EF5350;
        }

        .btn-danger:hover {
            background: #F44336;
            border-color: #F44336;
        }

        .btn-secondary {
            border-color: #9E9E9E;
            color: #BDBDBD;
        }

        .btn-secondary:hover {
            background: #9E9E9E;
            border-color: #9E9E9E;
            color: white;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
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
            color: #66BB6A;
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
            .main-container {
                padding: 20px 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 25px 20px;
            }

            .tournament-info h1 {
                font-size: 2rem;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stat-card {
                padding: 20px;
            }

            .action-card {
                padding: 25px 20px;
            }

            .btn-standard {
                width: 100%;
                justify-content: center;
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tournament-info h1 {
                font-size: 1.8rem;
            }

            .stat-number {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div class="tournament-info">
                <h1><i class="fas fa-trophy"></i> <?= htmlspecialchars($tournament['name']) ?></h1>
                <div class="tournament-year">Ano: <?= $tournament['year'] ?></div>
                <div class="tournament-status status-<?= $tournament['status'] ?>"><?= ucfirst($tournament['status']) ?></div>
            </div>
            <a href="tournament_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar à Lista
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
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card fade-in">
                <div class="stat-number"><?= $total_groups ?></div>
                <div class="stat-label">Grupos</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?= $total_teams ?></div>
                <div class="stat-label">Times</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?= $total_players ?></div>
                <div class="stat-label">Jogadores</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?= $finished_matches ?>/<?= $total_matches ?></div>
                <div class="stat-label">Jogos Finalizados</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?= count($final_phases) ?></div>
                <div class="stat-label">Fases Finais</div>
            </div>
        </div>

        <!-- Fases Finais Configuradas -->
        <?php if (!empty($final_phases)): ?>
        <div class="action-card fade-in" style="margin-bottom: 30px;">
            <h3 class="action-title">
                <i class="fas fa-trophy"></i> Fases Finais Configuradas
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <?php foreach ($final_phases as $phase): ?>
                <div style="background: #2A2A2A; border: 1px solid #7B1FA2; border-radius: 8px; padding: 15px; text-align: center;">
                    <div style="font-weight: 600; margin-bottom: 5px; color: #E1BEE7;"><?= htmlspecialchars($phase['phase_name']) ?></div>
                    <div style="color: #E0E0E0; opacity: 0.8; font-size: 0.9rem;"><?= $phase['teams_required'] ?> times</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Seção Principal de Gerenciamento -->
        <div class="action-card fade-in" style="margin-bottom: 30px;">
            <h3 class="action-title">
                <i class="fas fa-cogs"></i> Gerenciamento Completo do Torneio
            </h3>

            <!-- Seção de Grupos -->
            <div style="background: #2A2A2A; border: 1px solid #7B1FA2; border-radius: 8px; padding: 25px; margin-bottom: 25px;">
                <h4 style="color: #E1BEE7; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 600;">
                    <i class="fas fa-layer-group" style="color: #7B1FA2;"></i> Gerenciamento de Grupos
                </h4>
                <p style="margin-bottom: 20px; color: #E0E0E0; opacity: 0.9; line-height: 1.6;">Organize os times em grupos para a fase inicial do torneio.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <a href="group_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-primary">
                        <i class="fas fa-cog"></i> Gerenciar Grupos
                    </a>
                    <a href="team_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-success">
                        <i class="fas fa-users"></i> Gerenciar Times
                    </a>
                    <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-table"></i> Classificação
                    </a>
                </div>
            </div>

            <!-- Seção de Rodadas e Jogos -->
            <div style="background: #2A2A2A; border: 1px solid #7B1FA2; border-radius: 8px; padding: 25px; margin-bottom: 25px;">
                <h4 style="color: #E1BEE7; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 600;">
                    <i class="fas fa-futbol" style="color: #7B1FA2;"></i> Gerenciamento de Rodadas e Jogos
                </h4>
                <p style="margin-bottom: 20px; color: #E0E0E0; opacity: 0.9; line-height: 1.6;">Gere jogos, organize rodadas e insira resultados da fase de grupos.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-warning">
                        <i class="fas fa-futbol"></i> Gerenciar Jogos
                    </a>
                    <a href="match_schedule.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-calendar-alt"></i> Agenda de Jogos
                    </a>
                    <a href="bulk_edit_matches.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-edit"></i> Edição em Lote
                    </a>
                    <a href="all_matches.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-list"></i> Todos os Jogos
                    </a>
                </div>
            </div>

            <!-- Seção de Fases Finais -->
            <div style="background: #2A2A2A; border: 1px solid #7B1FA2; border-radius: 8px; padding: 25px; margin-bottom: 25px;">
                <h4 style="color: #E1BEE7; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 600;">
                    <i class="fas fa-trophy" style="color: #7B1FA2;"></i> Gerenciamento de Fases Finais
                </h4>
                <p style="margin-bottom: 20px; color: #E0E0E0; opacity: 0.9; line-height: 1.6;">Configure e gerencie as eliminatórias: oitavas, quartas, semifinais e final.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-warning">
                        <i class="fas fa-trophy"></i> Configurar Finais
                    </a>
                    <a href="knockout_generator.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-primary">
                        <i class="fas fa-magic"></i> Gerar Eliminatórias
                    </a>
                    <a href="../exibir_finais.php" class="btn-standard btn-secondary" target="_blank">
                        <i class="fas fa-eye"></i> Visualizar Chaveamento
                    </a>
                    <a href="third_place_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-medal"></i> Terceiro Lugar
                    </a>
                </div>
            </div>

            <!-- Seção de Relatórios e Análises -->
            <div style="background: #2A2A2A; border: 1px solid #7B1FA2; border-radius: 8px; padding: 25px; margin-bottom: 25px;">
                <h4 style="color: #E1BEE7; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 600;">
                    <i class="fas fa-chart-bar" style="color: #7B1FA2;"></i> Relatórios e Análises
                </h4>
                <p style="margin-bottom: 20px; color: #E0E0E0; opacity: 0.9; line-height: 1.6;">Visualize estatísticas, relatórios e análises do torneio.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <a href="tournament_report.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-primary">
                        <i class="fas fa-file-alt"></i> Relatório Completo
                    </a>
                    <a href="statistics.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-chart-line"></i> Estatísticas
                    </a>
                    <a href="match_reports.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-clipboard-list"></i> Relatórios de Jogos
                    </a>
                </div>
            </div>

            <!-- Seção de Configurações Avançadas -->
            <div style="background: #2A2A2A; border: 1px solid #7B1FA2; border-radius: 8px; padding: 25px;">
                <h4 style="color: #E1BEE7; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; font-weight: 600;">
                    <i class="fas fa-cog" style="color: #7B1FA2;"></i> Configurações Avançadas
                </h4>
                <p style="margin-bottom: 20px; color: #E0E0E0; opacity: 0.9; line-height: 1.6;">Edite configurações do torneio e gerencie aspectos avançados.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <a href="edit_tournament.php?id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-edit"></i> Editar Torneio
                    </a>
                    <a href="player_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-user-friends"></i> Gerenciar Jogadores
                    </a>
                    <a href="system_settings.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-secondary">
                        <i class="fas fa-tools"></i> Configurações Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover dinâmicos aos action-cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Adicionar efeitos hover dinâmicos aos stat-cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
