<?php
/**
 * Gerenciador de Jogos - Sistema Completo
 * Criado do zero para o Copa das Panelas
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../classes/MatchManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
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
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: #f39c12;
        }
        
        .tournament-info {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
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
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .actions-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .actions-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            border: 2px solid transparent;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .matches-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .phase-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #f39c12;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .match-card {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .match-card:hover {
            transform: translateY(-2px);
            background: rgba(0, 0, 0, 0.3);
        }
        
        .match-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto;
            gap: 20px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .team-info {
            text-align: center;
        }
        
        .team-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .team-group {
            font-size: 0.9rem;
            opacity: 0.7;
            color: #bdc3c7;
        }
        
        .match-score {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .vs-text {
            font-size: 1.2rem;
            opacity: 0.7;
        }
        
        .match-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .match-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .status-agendado { background: #f39c12; color: white; }
        .status-em_andamento { background: #3498db; color: white; }
        .status-finalizado { background: #27ae60; color: white; }
        .status-cancelado { background: #e74c3c; color: white; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
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
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            color: white;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: white;
        }

        .modal-close:hover {
            opacity: 0.7;
        }

        .modal-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-teams {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-team {
            text-align: center;
        }

        .modal-team-name {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .modal-score-input {
            width: 80px;
            padding: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .modal-score-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-vs {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f39c12;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .quick-scores {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .quick-score-btn {
            padding: 8px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-score-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 2rem;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-futbol"></i> Gerenciar Jogos</h1>
                <div class="tournament-info"><?= htmlspecialchars($tournament['name']) ?></div>
            </div>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
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
        
        <!-- Estatísticas -->
        <div class="stats-grid">
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
        <div class="actions-section">
            <h2 class="actions-title"><i class="fas fa-tools"></i> Ações Rápidas</h2>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="generate_matches">
                <button type="submit" class="btn btn-success" onclick="return confirm('Gerar jogos da fase de grupos? Jogos já existentes não serão duplicados.')">
                    <i class="fas fa-plus-circle"></i> Gerar Jogos da Fase de Grupos
                </button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="recalculate_stats">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Recalcular todas as estatísticas? Esta ação irá reprocessar todos os resultados.')">
                    <i class="fas fa-calculator"></i> Recalcular Estatísticas
                </button>
            </form>
            
            <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn btn-info">
                <i class="fas fa-trophy"></i> Ver Classificação
            </a>
            
            <a href="bulk_results.php?tournament=<?= $tournament_id ?>" class="btn btn-secondary">
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
            if (confirm(`Tem certeza que deseja excluir o jogo:\n${team1} vs ${team2}?\n\nEsta ação não pode ser desfeita.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_match">
                    <input type="hidden" name="match_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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
    </script>
</body>
</html>
