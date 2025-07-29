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
            margin-bottom: 10px;
        }
        
        .tournament-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            background: rgba(39, 174, 96, 0.3);
            border: 1px solid #27ae60;
            color: #2ecc71;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #f39c12;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #f39c12;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .action-description {
            margin-bottom: 20px;
            opacity: 0.8;
            line-height: 1.5;
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
        .btn-danger { background: #e74c3c; color: white; }
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
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .tournament-info h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="tournament-info">
                <h1><i class="fas fa-trophy"></i> <?= htmlspecialchars($tournament['name']) ?></h1>
                <div class="tournament-year">Ano: <?= $tournament['year'] ?></div>
                <div class="tournament-status"><?= ucfirst($tournament['status']) ?></div>
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
            <div class="stat-card">
                <div class="stat-number"><?= $total_groups ?></div>
                <div class="stat-label">Grupos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_teams ?></div>
                <div class="stat-label">Times</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_players ?></div>
                <div class="stat-label">Jogadores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $finished_matches ?>/<?= $total_matches ?></div>
                <div class="stat-label">Jogos Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($final_phases) ?></div>
                <div class="stat-label">Fases Finais</div>
            </div>
        </div>

        <!-- Fases Finais Configuradas -->
        <?php if (!empty($final_phases)): ?>
        <div style="background: rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 1px solid rgba(255, 255, 255, 0.2);">
            <h3 style="color: #f39c12; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-trophy"></i> Fases Finais Configuradas
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <?php foreach ($final_phases as $phase): ?>
                <div style="background: rgba(0, 0, 0, 0.2); border-radius: 10px; padding: 15px; text-align: center;">
                    <div style="font-weight: bold; margin-bottom: 5px;"><?= htmlspecialchars($phase['phase_name']) ?></div>
                    <div style="opacity: 0.8; font-size: 0.9rem;"><?= $phase['teams_required'] ?> times</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Seção Principal de Gerenciamento -->
        <div style="background: rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 1px solid rgba(255, 255, 255, 0.2);">
            <h3 style="color: #f39c12; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-cogs"></i> Gerenciamento Completo do Torneio
            </h3>

            <!-- Seção de Grupos -->
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #3498db; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-layer-group"></i> Gerenciamento de Grupos
                </h4>
                <p style="margin-bottom: 15px; opacity: 0.9;">Organize os times em grupos para a fase inicial do torneio.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="group_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Gerenciar Grupos
                    </a>
                    <a href="team_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-success">
                        <i class="fas fa-users"></i> Gerenciar Times
                    </a>
                    <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-table"></i> Classificação
                    </a>
                </div>
            </div>

            <!-- Seção de Rodadas e Jogos -->
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #27ae60; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-futbol"></i> Gerenciamento de Rodadas e Jogos
                </h4>
                <p style="margin-bottom: 15px; opacity: 0.9;">Gere jogos, organize rodadas e insira resultados da fase de grupos.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-warning">
                        <i class="fas fa-futbol"></i> Gerenciar Jogos
                    </a>
                    <a href="match_schedule.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-calendar-alt"></i> Agenda de Jogos
                    </a>
                    <a href="bulk_edit_matches.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Edição em Lote
                    </a>
                    <a href="all_matches.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Todos os Jogos
                    </a>
                </div>
            </div>

            <!-- Seção de Fases Finais -->
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #f39c12; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-trophy"></i> Gerenciamento de Fases Finais
                </h4>
                <p style="margin-bottom: 15px; opacity: 0.9;">Configure e gerencie as eliminatórias: oitavas, quartas, semifinais e final.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-warning">
                        <i class="fas fa-trophy"></i> Configurar Finais
                    </a>
                    <a href="knockout_generator.php?tournament_id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Gerar Eliminatórias
                    </a>
                    <a href="../exibir_finais.php" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-eye"></i> Visualizar Chaveamento
                    </a>
                    <a href="third_place_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-medal"></i> Terceiro Lugar
                    </a>
                </div>
            </div>

            <!-- Seção de Relatórios e Análises -->
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #9b59b6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-bar"></i> Relatórios e Análises
                </h4>
                <p style="margin-bottom: 15px; opacity: 0.9;">Visualize estatísticas, relatórios e análises do torneio.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="tournament_report.php?tournament_id=<?= $tournament_id ?>" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Relatório Completo
                    </a>
                    <a href="statistics.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-chart-line"></i> Estatísticas
                    </a>
                    <a href="match_reports.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-clipboard-list"></i> Relatórios de Jogos
                    </a>
                </div>
            </div>

            <!-- Seção de Configurações Avançadas -->
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 20px;">
                <h4 style="color: #e74c3c; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-cog"></i> Configurações Avançadas
                </h4>
                <p style="margin-bottom: 15px; opacity: 0.9;">Edite configurações do torneio e gerencie aspectos avançados.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="edit_tournament.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Editar Torneio
                    </a>
                    <a href="player_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-user-friends"></i> Gerenciar Jogadores
                    </a>
                    <a href="system_settings.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                        <i class="fas fa-tools"></i> Configurações Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
