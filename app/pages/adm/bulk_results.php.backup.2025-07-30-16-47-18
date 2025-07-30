<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Processar resultados em lote
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_bulk_results') {
    try {
        $results_saved = 0;
        $pdo->beginTransaction();
        
        foreach ($_POST['matches'] as $match_id => $result) {
            if (!empty($result['team1_goals']) && !empty($result['team2_goals'])) {
                $team1_goals = (int)$result['team1_goals'];
                $team2_goals = (int)$result['team2_goals'];
                
                // Atualizar resultado do jogo
                $stmt = $pdo->prepare("
                    UPDATE matches 
                    SET team1_goals = ?, team2_goals = ?, status = 'finalizado', updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$team1_goals, $team2_goals, $match_id])) {
                    $results_saved++;
                    
                    // Obter informações do jogo para atualizar estatísticas dos times
                    $stmt = $pdo->prepare("SELECT team1_id, team2_id FROM matches WHERE id = ?");
                    $stmt->execute([$match_id]);
                    $match_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($match_info) {
                        // Atualizar estatísticas do time 1
                        if ($team1_goals > $team2_goals) {
                            // Vitória do time 1
                            $pdo->prepare("UPDATE times SET vitorias = vitorias + 1, pts = pts + 3, gm = gm + ?, gc = gc + ?, sg = sg + ? WHERE id = ?")
                                ->execute([$team1_goals, $team2_goals, ($team1_goals - $team2_goals), $match_info['team1_id']]);
                            // Derrota do time 2
                            $pdo->prepare("UPDATE times SET derrotas = derrotas + 1, gm = gm + ?, gc = gc + ?, sg = sg + ? WHERE id = ?")
                                ->execute([$team2_goals, $team1_goals, ($team2_goals - $team1_goals), $match_info['team2_id']]);
                        } elseif ($team2_goals > $team1_goals) {
                            // Vitória do time 2
                            $pdo->prepare("UPDATE times SET vitorias = vitorias + 1, pts = pts + 3, gm = gm + ?, gc = gc + ?, sg = sg + ? WHERE id = ?")
                                ->execute([$team2_goals, $team1_goals, ($team2_goals - $team1_goals), $match_info['team2_id']]);
                            // Derrota do time 1
                            $pdo->prepare("UPDATE times SET derrotas = derrotas + 1, gm = gm + ?, gc = gc + ?, sg = sg + ? WHERE id = ?")
                                ->execute([$team1_goals, $team2_goals, ($team1_goals - $team2_goals), $match_info['team1_id']]);
                        } else {
                            // Empate
                            $pdo->prepare("UPDATE times SET empates = empates + 1, pts = pts + 1, gm = gm + ?, gc = gc + ? WHERE id = ?")
                                ->execute([$team1_goals, $team2_goals, $match_info['team1_id']]);
                            $pdo->prepare("UPDATE times SET empates = empates + 1, pts = pts + 1, gm = gm + ?, gc = gc + ? WHERE id = ?")
                                ->execute([$team2_goals, $team1_goals, $match_info['team2_id']]);
                        }
                    }
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "$results_saved resultados salvos com sucesso e estatísticas atualizadas!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erro ao salvar resultados: " . $e->getMessage();
    }
    
    header("Location: bulk_results.php");
    exit;
}

// Filtros
$tournament_filter = $_GET['tournament'] ?? '';
$status_filter = $_GET['status'] ?? 'agendado';

// Construir query
$where_conditions = ["1=1"];
$params = [];

if ($tournament_filter) {
    $where_conditions[] = "m.tournament_id = ?";
    $params[] = $tournament_filter;
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados em Lote - Copa das Panelas</title>
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
            background: #4CAF50;
            border: 2px solid #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
            border-color: #45a049;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #2A2A2A;
            border-left-color: #4CAF50;
            color: #4CAF50;
        }

        .alert-error {
            background: #2A2A2A;
            border-left-color: #F44336;
            color: #F44336;
        }

        .filters {
            background: #1E1E1E;
            border-left: 4px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .filters::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 1rem;
            font-weight: 600;
            color: #64B5F6;
        }
        
        .filter-input {
            padding: 12px 15px;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: #7B1FA2;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .match-card {
            background: #2A2A2A;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            padding: 25px;
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
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .match-card:hover {
            border-color: #7B1FA2;
            transform: translateY(-2px);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .match-teams {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .team-info {
            text-align: center;
        }

        .team-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #E1BEE7;
        }
        
        .vs-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: #FF9800;
        }

        .score-inputs {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .score-input {
            width: 70px;
            height: 70px;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            border: 3px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: #1E1E1E;
            color: #E1BEE7;
            margin: 0 auto;
            display: block;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .score-input:focus {
            outline: none;
            border-color: #7B1FA2;
            background: #2A2A2A;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .match-info {
            font-size: 0.95rem;
            color: #9E9E9E;
            text-align: center;
            line-height: 1.4;
        }

        .save-section {
            background: #1E1E1E;
            border-left: 4px solid #4CAF50;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            position: sticky;
            bottom: 20px;
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

            .matches-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-list-ol"></i> Resultados em Lote</h1>
                <p style="margin: 8px 0 0 0; color: #9E9E9E; font-size: 1.1rem;">Insira múltiplos resultados de uma vez</p>
            </div>
            <a href="dashboard_simple.php" class="btn-standard">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filters fade-in">
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
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-input">
                            <option value="agendado" <?= $status_filter === 'agendado' ? 'selected' : '' ?>>Agendados</option>
                            <option value="em_andamento" <?= $status_filter === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="finalizado" <?= $status_filter === 'finalizado' ? 'selected' : '' ?>>Finalizados</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn-standard">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Formulário de Resultados -->
        <?php if (!empty($matches)): ?>
            <form method="POST" id="bulkResultsForm">
                <input type="hidden" name="action" value="save_bulk_results">
                
                <div class="matches-grid fade-in">
                    <?php foreach ($matches as $match): ?>
                        <div class="match-card">
                            <div class="match-header">
                                <span style="color: #f39c12; font-weight: bold;">
                                    <?= $match['group_name'] ? htmlspecialchars($match['group_name']) : 'Fase Final' ?>
                                </span>
                                <span style="font-size: 0.9rem; opacity: 0.7;">
                                    <?= htmlspecialchars($match['tournament_name']) ?>
                                </span>
                            </div>
                            
                            <div class="match-teams">
                                <div class="team-info">
                                    <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                                </div>
                                
                                <div class="vs-text">VS</div>
                                
                                <div class="team-info">
                                    <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                                </div>
                            </div>
                            
                            <div class="score-inputs">
                                <input type="number" 
                                       name="matches[<?= $match['id'] ?>][team1_goals]" 
                                       class="score-input" 
                                       min="0" max="99" 
                                       placeholder="0"
                                       value="<?= $match['team1_goals'] ?? '' ?>">
                                
                                <div style="color: #f39c12; font-weight: bold;">X</div>
                                
                                <input type="number" 
                                       name="matches[<?= $match['id'] ?>][team2_goals]" 
                                       class="score-input" 
                                       min="0" max="99" 
                                       placeholder="0"
                                       value="<?= $match['team2_goals'] ?? '' ?>">
                            </div>
                            
                            <div class="match-info">
                                <?php if ($match['match_date']): ?>
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?>
                                <?php else: ?>
                                    <i class="fas fa-clock"></i> Data a definir
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="save-section fade-in">
                    <button type="submit" class="btn-standard btn-success" style="font-size: 1.2rem; padding: 18px 35px;">
                        <i class="fas fa-save"></i> Salvar Todos os Resultados
                    </button>
                    <p style="margin-top: 15px; color: #9E9E9E; line-height: 1.5;">
                        Preencha os placares e clique para salvar todos de uma vez.<br>
                        As estatísticas dos times serão atualizadas automaticamente.
                    </p>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state fade-in" style="text-align: center; padding: 60px 30px; background: #1E1E1E; border-radius: 8px; border-left: 4px solid #FF9800;">
                <i class="fas fa-futbol" style="font-size: 4rem; color: #FF9800; margin-bottom: 20px;"></i>
                <h3 style="color: #E1BEE7; margin-bottom: 15px; font-size: 1.5rem;">Nenhum Jogo Encontrado</h3>
                <p style="color: #9E9E9E; margin-bottom: 25px; font-size: 1.1rem;">Não há jogos que correspondam aos filtros selecionados.</p>
                <a href="?status=agendado" class="btn-standard">
                    <i class="fas fa-calendar"></i> Ver Jogos Agendados
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.match-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Auto-focus no próximo campo
        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.length >= 1) {
                    const nextInput = this.closest('.score-inputs').querySelector('.score-input:last-child');
                    if (nextInput && nextInput !== this) {
                        nextInput.focus();
                    }
                }
            });
        });
        
        // Animações Copa das Panelas
        const fadeElements = document.querySelectorAll('.fade-in');
        fadeElements.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('visible');
            }, index * 200);
        });

        // Animação para inputs de score
        const scoreInputs = document.querySelectorAll('.score-input');
        scoreInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#4CAF50';
                this.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.2)';
            });

            input.addEventListener('blur', function() {
                this.style.borderColor = 'rgba(123, 31, 162, 0.3)';
                this.style.boxShadow = 'none';
            });
        });

        // Validação antes de salvar
        document.getElementById('bulkResultsForm').addEventListener('submit', function(e) {
            const filledInputs = document.querySelectorAll('.score-input[value]:not([value=""])').length;
            if (filledInputs === 0) {
                e.preventDefault();
                alert('Por favor, preencha pelo menos um resultado antes de salvar.');
                return;
            }

            // Animação de salvamento
            const saveButton = document.querySelector('.btn-success');
            if (saveButton) {
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                saveButton.disabled = true;
            }
        });
    </script>
</body>
</html>
