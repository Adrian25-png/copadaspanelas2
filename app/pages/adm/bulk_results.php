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
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
        
        .filters {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-label {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .filter-input {
            padding: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }
        
        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .match-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .match-teams {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .team-info {
            text-align: center;
        }
        
        .team-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .vs-text {
            font-size: 1.2rem;
            font-weight: bold;
            color: #f39c12;
        }
        
        .score-inputs {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .score-input {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin: 0 auto;
            display: block;
        }
        
        .score-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .match-info {
            font-size: 0.9rem;
            opacity: 0.8;
            text-align: center;
        }
        
        .save-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
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
        
        @media (max-width: 768px) {
            .header {
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
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-list-ol"></i> Resultados em Lote</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Insira múltiplos resultados de uma vez</p>
            </div>
            <a href="dashboard_simple.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Dashboard
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
        
        <!-- Filtros -->
        <div class="filters">
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
                        <button type="submit" class="btn btn-primary">
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
                
                <div class="matches-grid">
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
                
                <div class="save-section">
                    <button type="submit" class="btn btn-success" style="font-size: 1.2rem; padding: 15px 30px;">
                        <i class="fas fa-save"></i> Salvar Todos os Resultados
                    </button>
                    <p style="margin-top: 10px; opacity: 0.8;">
                        Preencha os placares e clique para salvar todos de uma vez.<br>
                        As estatísticas dos times serão atualizadas automaticamente.
                    </p>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-futbol"></i>
                <h3>Nenhum Jogo Encontrado</h3>
                <p>Não há jogos que correspondam aos filtros selecionados.</p>
                <a href="?status=agendado" class="btn btn-primary">
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
        
        // Confirmação antes de salvar
        document.getElementById('bulkResultsForm').addEventListener('submit', function(e) {
            const filledInputs = document.querySelectorAll('.score-input[value]:not([value=""])').length;
            if (filledInputs === 0) {
                alert('Preencha pelo menos um resultado antes de salvar.');
                e.preventDefault();
                return;
            }
            
            if (!confirm(`Tem certeza que deseja salvar os resultados?\n\nEsta ação irá:\n- Finalizar os jogos\n- Atualizar as estatísticas dos times\n- Não poderá ser desfeita facilmente`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
