<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


/**
 * Gerenciador de Jogos das Finais
 * Página para gerenciar resultados dos jogos das eliminatórias
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

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

// Processar ações
if ($_POST) {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'update_match') {
            $match_id = $_POST['match_id'];
            $team1_goals = (int)$_POST['team1_goals'];
            $team2_goals = (int)$_POST['team2_goals'];
            $match_date = $_POST['match_date'] ?? null;
            $match_time = $_POST['match_time'] ?? null;

            // VALIDAÇÃO RIGOROSA: Não permitir empates nas eliminatórias
            if ($team1_goals == $team2_goals) {
                $_SESSION['error'] = "❌ EMPATES NÃO SÃO PERMITIDOS nas eliminatórias! Cada jogo deve ter um vencedor para que o time possa avançar para a próxima fase.";
                header("Location: finals_matches_manager.php?tournament_id=$tournament_id");
                exit;
            }

            // Combinar data e hora se fornecidos
            $match_datetime = null;
            if ($match_date && $match_time) {
                $match_datetime = $match_date . ' ' . $match_time;
            } elseif ($match_date) {
                $match_datetime = $match_date . ' 00:00:00';
            }

            // Atualizar jogo
            $stmt = $pdo->prepare("
                UPDATE matches
                SET team1_goals = ?, team2_goals = ?, status = 'finalizado',
                    match_date = COALESCE(?, match_date),
                    updated_at = NOW()
                WHERE id = ? AND tournament_id = ?
            ");
            $stmt->execute([$team1_goals, $team2_goals, $match_datetime, $match_id, $tournament_id]);

            // PRIMEIRO: Verificar e remover fases inválidas após mudança de resultado
            include_once '../../actions/funcoes/progressao_eliminatorias.php';
            $verificacao_result = verificarERemoverFasesInvalidas($pdo, $tournament_id);

            $mensagem_remocao = '';
            if (!empty($verificacao_result['remocoes'])) {
                $fases_removidas = [];
                foreach ($verificacao_result['remocoes'] as $remocao) {
                    $fases_removidas = array_merge($fases_removidas, $remocao['fases_removidas']);
                }
                if (!empty($fases_removidas)) {
                    $mensagem_remocao = " Fases removidas: " . implode(', ', $fases_removidas) . " (fase anterior incompleta).";
                }
            }

            // SEGUNDO: Verificar e executar progressão automática se aplicável
            $progressao_result = verificarEProgressirEliminatorias($tournament_id);

            if ($progressao_result['status'] === 'success' && isset($progressao_result['progressoes'])) {
                $progressoes_realizadas = array_filter($progressao_result['progressoes'], function($p) {
                    return $p['status'] === 'success';
                });

                if (!empty($progressoes_realizadas)) {
                    $_SESSION['success'] = "Resultado salvo! Progressão automática realizada." . $mensagem_remocao;
                } else {
                    $_SESSION['success'] = "Resultado do jogo atualizado com sucesso!" . $mensagem_remocao;
                }
            } else {
                $_SESSION['success'] = "Resultado do jogo atualizado com sucesso!" . $mensagem_remocao;
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'schedule_match') {
            $match_id = $_POST['match_id'];
            $match_date = $_POST['match_date'];
            $match_time = $_POST['match_time'] ?? '20:00';

            $match_datetime = $match_date . ' ' . $match_time;

            $stmt = $pdo->prepare("
                UPDATE matches
                SET match_date = ?, status = 'agendado', updated_at = NOW()
                WHERE id = ? AND tournament_id = ?
            ");
            $stmt->execute([$match_datetime, $match_id, $tournament_id]);

            $_SESSION['success'] = "Jogo agendado com sucesso!";
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'reset_match') {
            $match_id = $_POST['match_id'];

            $stmt = $pdo->prepare("
                UPDATE matches
                SET team1_goals = NULL, team2_goals = NULL, status = 'agendado', updated_at = NOW()
                WHERE id = ? AND tournament_id = ?
            ");
            $stmt->execute([$match_id, $tournament_id]);

            // Verificar e remover fases inválidas após resetar resultado
            include_once '../../actions/funcoes/progressao_eliminatorias.php';
            $verificacao_result = verificarERemoverFasesInvalidas($pdo, $tournament_id);

            $mensagem_remocao = '';
            if (!empty($verificacao_result['remocoes'])) {
                $fases_removidas = [];
                foreach ($verificacao_result['remocoes'] as $remocao) {
                    $fases_removidas = array_merge($fases_removidas, $remocao['fases_removidas']);
                }
                if (!empty($fases_removidas)) {
                    $mensagem_remocao = " Fases removidas: " . implode(', ', $fases_removidas) . " (fase anterior incompleta).";
                }
            }

            $_SESSION['success'] = "Resultado do jogo resetado com sucesso!" . $mensagem_remocao;
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao processar ação: " . $e->getMessage();
    }
    
    header("Location: finals_matches_manager.php?tournament_id=$tournament_id");
    exit;
}

// Buscar jogos das eliminatórias
try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.team1_id, m.team2_id, m.team1_goals, m.team2_goals,
               m.status, m.phase, m.match_date,
               t1.nome as team1_name, t1.logo as team1_logo,
               t2.nome as team2_name, t2.logo as team2_logo
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final')
        ORDER BY FIELD(m.phase, 'Oitavas', 'Quartas', 'Semifinal', 'Final'), m.id
    ");
    $stmt->execute([$tournament_id]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar por fase
    $matches_by_phase = [];
    foreach ($matches as $match) {
        $matches_by_phase[$match['phase']][] = $match;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao buscar jogos: " . $e->getMessage();
    $matches_by_phase = [];
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Jogos das Finais - <?= htmlspecialchars($tournament['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <style>
        /* Reset básico */
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b69 50%, #7B1FA2 100%);
            color: #E0E0E0;
            min-height: 100vh;
            padding: 20px;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(135deg, #2A2A2A, #7B1FA2);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(123, 31, 162, 0.3);
            border: 1px solid #7B1FA2;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header p {
            font-size: 1.1rem;
            color: #E0E0E0;
            opacity: 0.9;
        }
        
        .action-card {
            background: linear-gradient(135deg, #2A2A2A, #1a1a1a);
            border: 1px solid #7B1FA2;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(123, 31, 162, 0.2);
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(123, 31, 162, 0.3);
        }

        .phase-section {
            margin-bottom: 30px;
        }

        .phase-header {
            background: linear-gradient(135deg, #7B1FA2, #9C27B0);
            color: #E1BEE7;
            padding: 20px 25px;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
            border-radius: 15px 15px 0 0;
            border: 1px solid #7B1FA2;
        }

        .match-card {
            background: #2A2A2A;
            border: 1px solid #7B1FA2;
            border-top: none;
            padding: 20px 25px;
            transition: background-color 0.3s ease;
        }

        .match-card:hover {
            background: #333333;
        }

        .match-card:last-child {
            border-radius: 0 0 15px 15px;
        }
        
        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .teams-display {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #E1BEE7;
        }

        .team {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #E0E0E0;
        }

        .team-logo {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #7B1FA2;
        }

        .vs {
            color: #7B1FA2;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .match-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid;
        }

        .status-agendado {
            background: linear-gradient(135deg, #FF9800, #FFB74D);
            color: #1a1a1a;
            border-color: #FF9800;
        }

        .status-finalizado {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            border-color: #4CAF50;
        }
        
        .match-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #E1BEE7;
            margin-bottom: 8px;
        }

        .form-group input, .form-group select {
            padding: 12px;
            border: 1px solid #7B1FA2;
            border-radius: 8px;
            font-size: 1rem;
            background: #1a1a1a;
            color: #E0E0E0;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #9C27B0;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.2);
        }
        
        .goals-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .goals-input input {
            width: 80px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .btn-standard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #7B1FA2, #9C27B0);
            color: #E1BEE7;
            border: 1px solid #7B1FA2;
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            border: 1px solid #4CAF50;
        }

        .btn-warning {
            background: linear-gradient(135deg, #FF9800, #FFB74D);
            color: #1a1a1a;
            border: 1px solid #FF9800;
        }

        .btn-danger {
            background: linear-gradient(135deg, #F44336, #EF5350);
            color: white;
            border: 1px solid #F44336;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #424242, #616161);
            color: #E0E0E0;
            border: 1px solid #424242;
        }

        .btn-standard:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(123, 31, 162, 0.4);
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            border: 1px solid #4CAF50;
        }

        .alert-error {
            background: linear-gradient(135deg, #F44336, #EF5350);
            color: white;
            border: 1px solid #F44336;
        }
        
        .no-matches {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-matches i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .match-form {
                grid-template-columns: 1fr;
            }
            
            .teams-display {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-futbol"></i> Gerenciar Jogos das Finais</h1>
            <p><?= htmlspecialchars($tournament['name']) ?></p>
        </div>

        <!-- Aviso sobre empates -->
        <div class="action-card" style="background: linear-gradient(135deg, #dc3545, #c82333); border: 1px solid #dc3545; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px; color: white; text-align: center; justify-content: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
                <div>
                    <strong style="font-size: 1.1rem;">REGRA FUNDAMENTAL:</strong>
                    <div style="margin-top: 5px;">Empates não são permitidos nas eliminatórias! Cada jogo deve ter um vencedor.</div>
                </div>
            </div>
        </div>

            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn-standard btn-secondary" style="margin-bottom: 20px;">
                <i class="fas fa-arrow-left"></i> Voltar ao Gerenciamento do Torneio
            </a>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (empty($matches_by_phase)): ?>
                <div class="action-card" style="text-align: center; padding: 40px;">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: #7B1FA2; margin-bottom: 20px;"></i>
                    <h3 style="color: #E1BEE7; margin-bottom: 15px;">Nenhum jogo das eliminatórias encontrado</h3>
                    <p style="color: #E0E0E0; margin-bottom: 25px;">As eliminatórias ainda não foram configuradas para este torneio.</p>
                    <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard btn-primary">
                        <i class="fas fa-trophy"></i> Configurar Eliminatórias
                    </a>
                </div>
            <?php else: ?>
                <?php foreach (['Oitavas', 'Quartas', 'Semifinal', 'Final'] as $phase): ?>
                    <?php if (isset($matches_by_phase[$phase])): ?>
                        <div class="action-card phase-section">
                            <div class="phase-header">
                                <i class="fas fa-trophy"></i>
                                <?= $phase ?>
                                <span style="margin-left: auto; font-size: 0.9rem; opacity: 0.8;">
                                    <?= count($matches_by_phase[$phase]) ?> jogo(s)
                                </span>
                            </div>
                            
                            <?php foreach ($matches_by_phase[$phase] as $match): ?>
                                <div class="match-card">
                                    <div class="match-header">
                                        <div class="teams-display">
                                            <div class="team">
                                                <?php if ($match['team1_logo']): ?>
                                                    <img src="../../../public/imgs/<?= htmlspecialchars($match['team1_logo']) ?>" 
                                                         alt="<?= htmlspecialchars($match['team1_name']) ?>" class="team-logo">
                                                <?php endif; ?>
                                                <span><?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?></span>
                                            </div>
                                            <span class="vs">VS</span>
                                            <div class="team">
                                                <?php if ($match['team2_logo']): ?>
                                                    <img src="../../../public/imgs/<?= htmlspecialchars($match['team2_logo']) ?>" 
                                                         alt="<?= htmlspecialchars($match['team2_name']) ?>" class="team-logo">
                                                <?php endif; ?>
                                                <span><?= htmlspecialchars($match['team2_name'] ?? 'Time 2') ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="match-status status-<?= $match['status'] ?>">
                                            <?= $match['status'] === 'finalizado' ? 'Finalizado' : 'Agendado' ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($match['status'] === 'finalizado'): ?>
                                        <div style="text-align: center; margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #1a1a1a, #2A2A2A); border-radius: 10px; border: 1px solid #7B1FA2;">
                                            <div style="font-size: 2.5rem; font-weight: bold; color: #E1BEE7; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                                                <?= $match['team1_goals'] ?> - <?= $match['team2_goals'] ?>
                                            </div>
                                            <?php if ($match['match_date']): ?>
                                                <div style="color: #E0E0E0; margin-top: 8px; font-size: 0.9rem;">
                                                    <i class="fas fa-calendar" style="color: #7B1FA2;"></i>
                                                    <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Formulário para atualizar resultado -->
                                    <form method="POST" class="match-form">
                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                        
                                        <div class="form-group">
                                            <label>Resultado do Jogo</label>
                                            <div class="goals-input">
                                                <span><?= htmlspecialchars($match['team1_name'] ?? 'Time 1') ?></span>
                                                <input type="number" name="team1_goals" min="0" max="50" 
                                                       value="<?= $match['team1_goals'] ?? '' ?>" placeholder="0">
                                                <span>-</span>
                                                <input type="number" name="team2_goals" min="0" max="50" 
                                                       value="<?= $match['team2_goals'] ?? '' ?>" placeholder="0">
                                                <span><?= htmlspecialchars($match['team2_name'] ?? 'Time 2') ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Data e Hora do Jogo</label>
                                            <div style="display: flex; gap: 10px;">
                                                <input type="date" name="match_date"
                                                       value="<?= $match['match_date'] ? date('Y-m-d', strtotime($match['match_date'])) : '' ?>"
                                                       style="flex: 1;">
                                                <input type="time" name="match_time"
                                                       value="<?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : '20:00' ?>"
                                                       style="flex: 1;">
                                            </div>
                                        </div>
                                        

                                        
                                        <div class="actions">
                                            <button type="submit" name="action" value="update_match" class="btn-standard btn-success">
                                                <i class="fas fa-save"></i> Salvar Resultado
                                            </button>

                                            <button type="submit" name="action" value="schedule_match" class="btn-standard btn-primary">
                                                <i class="fas fa-calendar-plus"></i> Agendar
                                            </button>

                                            <?php if ($match['status'] === 'finalizado'): ?>
                                                <button type="submit" name="action" value="reset_match" class="btn-standard btn-warning"
                                                        onclick="return confirm('Tem certeza que deseja resetar este jogo?')">
                                                    <i class="fas fa-undo"></i> Resetar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="action-card" style="text-align: center; margin-top: 30px;">
                <h3 style="color: #E1BEE7; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-eye"></i> Visualização
                </h3>
                <a href="../exibir_finais.php" class="btn-standard btn-secondary" target="_blank">
                    <i class="fas fa-eye"></i> Visualizar Chaveamento
                </a>
            </div>
        </div>
    </div>

    <script>
        // Validação de empates nas eliminatórias
        function validarResultado(form) {
            const team1Goals = parseInt(form.team1_goals.value) || 0;
            const team2Goals = parseInt(form.team2_goals.value) || 0;

            if (team1Goals === team2Goals) {
                alert('❌ EMPATES NÃO SÃO PERMITIDOS nas eliminatórias!\n\nCada jogo deve ter um vencedor para que o time possa avançar para a próxima fase.\n\nPor favor, defina um resultado com vencedor.');
                return false;
            }

            return true;
        }

        // Efeitos de animação
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar validação a todos os formulários de resultado
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (form.querySelector('input[name="team1_goals"]') && form.querySelector('input[name="team2_goals"]')) {
                    form.addEventListener('submit', function(e) {
                        if (!validarResultado(this)) {
                            e.preventDefault();
                        }
                    });
                }
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

            // Adicionar efeitos hover aos botões
            const buttons = document.querySelectorAll('.btn-standard');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });

                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
