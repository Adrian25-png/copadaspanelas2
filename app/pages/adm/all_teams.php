<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Processar ações
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'delete_team':
                $team_id = $_POST['team_id'];
                
                // Verificar se o time tem jogos
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE team1_id = ? OR team2_id = ?");
                $stmt->execute([$team_id, $team_id]);
                $match_count = $stmt->fetchColumn();
                
                if ($match_count > 0) {
                    throw new Exception("Não é possível excluir o time pois ele possui jogos cadastrados.");
                }
                
                // Excluir time
                $stmt = $pdo->prepare("DELETE FROM times WHERE id = ?");
                $stmt->execute([$team_id]);
                
                $_SESSION['success'] = "Time excluído com sucesso!";
                break;
                
            case 'update_team':
                $team_id = $_POST['team_id'];
                $nome = trim($_POST['nome']);
                
                if (empty($nome)) {
                    throw new Exception("Nome do time é obrigatório");
                }
                
                $stmt = $pdo->prepare("UPDATE times SET nome = ? WHERE id = ?");
                $stmt->execute([$nome, $team_id]);
                
                $_SESSION['success'] = "Time atualizado com sucesso!";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: all_teams.php');
    exit;
}

// Filtros
$tournament_filter = $_GET['tournament'] ?? '';
$group_filter = $_GET['group'] ?? '';
$search = $_GET['search'] ?? '';

// Construir query
$where_conditions = ["1=1"];
$params = [];

if ($tournament_filter) {
    $where_conditions[] = "t.tournament_id = ?";
    $params[] = $tournament_filter;
}

if ($group_filter) {
    $where_conditions[] = "t.grupo_id = ?";
    $params[] = $group_filter;
}

if ($search) {
    $where_conditions[] = "t.nome LIKE ?";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Obter times
try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               g.nome as grupo_nome,
               tour.name as tournament_name,
               COALESCE(t.pts, 0) as pontos,
               (COALESCE(t.vitorias, 0) + COALESCE(t.empates, 0) + COALESCE(t.derrotas, 0)) as jogos,
               COALESCE(t.vitorias, 0) as vitorias,
               COALESCE(t.empates, 0) as empates,
               COALESCE(t.derrotas, 0) as derrotas,
               COALESCE(t.gm, 0) as gols_pro,
               COALESCE(t.gc, 0) as gols_contra,
               COALESCE(t.sg, 0) as saldo_gols
        FROM times t
        LEFT JOIN grupos g ON t.grupo_id = g.id
        LEFT JOIN tournaments tour ON t.tournament_id = tour.id
        WHERE $where_clause
        ORDER BY tour.name, g.nome, t.pts DESC, t.sg DESC
    ");
    $stmt->execute($params);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $teams = [];
    $_SESSION['error'] = "Erro ao carregar times: " . $e->getMessage();
}

// Obter torneios para filtro
try {
    $stmt = $pdo->query("SELECT id, name FROM tournaments ORDER BY name");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tournaments = [];
}

// Obter grupos para filtro
try {
    $stmt = $pdo->query("SELECT id, nome FROM grupos ORDER BY nome");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $groups = [];
}

// Estatísticas
$total_teams = count($teams);
$total_points = array_sum(array_column($teams, 'pontos'));
$total_goals = array_sum(array_column($teams, 'gols_pro'));
$avg_points = $total_teams > 0 ? round($total_points / $total_teams, 1) : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos os Times - Copa das Panelas</title>
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
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
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
        
        .filter-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .teams-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table th {
            background: rgba(0, 0, 0, 0.4);
            font-weight: bold;
            color: #f39c12;
        }
        
        .table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .team-name {
            font-weight: bold;
        }
        
        .points {
            font-weight: bold;
            color: #27ae60;
        }
        
        .positive {
            color: #27ae60;
        }
        
        .negative {
            color: #e74c3c;
        }
        
        .neutral {
            color: #f39c12;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .edit-form {
            display: none;
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .edit-input {
            width: 100%;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin-bottom: 10px;
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
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users"></i> Todos os Times</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Visualize e gerencie todos os times do sistema</p>
            </div>
            <div>
                <a href="select_tournament_for_team.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Novo Time
                </a>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
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
                        <label class="filter-label">Grupo</label>
                        <select name="group" class="filter-input">
                            <option value="">Todos os grupos</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>" <?= $group_filter == $group['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Buscar Time</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Nome do time..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_teams ?></div>
                <div class="stat-label">Total de Times</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_points ?></div>
                <div class="stat-label">Total de Pontos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_goals ?></div>
                <div class="stat-label">Total de Gols</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $avg_points ?></div>
                <div class="stat-label">Média de Pontos</div>
            </div>
        </div>
        
        <!-- Tabela de Times -->
        <?php if (!empty($teams)): ?>
            <div class="teams-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Torneio</th>
                            <th>Grupo</th>
                            <th>Pts</th>
                            <th>J</th>
                            <th>V</th>
                            <th>E</th>
                            <th>D</th>
                            <th>GP</th>
                            <th>GC</th>
                            <th>SG</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
                            <tr id="team-<?= $team['id'] ?>">
                                <td class="team-name"><?= htmlspecialchars($team['nome']) ?></td>
                                <td><?= htmlspecialchars($team['tournament_name']) ?></td>
                                <td><?= htmlspecialchars($team['grupo_nome'] ?: 'Sem grupo') ?></td>
                                <td class="points"><?= $team['pontos'] ?></td>
                                <td><?= $team['jogos'] ?></td>
                                <td class="positive"><?= $team['vitorias'] ?></td>
                                <td class="neutral"><?= $team['empates'] ?></td>
                                <td class="negative"><?= $team['derrotas'] ?></td>
                                <td class="positive"><?= $team['gols_pro'] ?></td>
                                <td class="negative"><?= $team['gols_contra'] ?></td>
                                <td class="<?= $team['saldo_gols'] > 0 ? 'positive' : ($team['saldo_gols'] < 0 ? 'negative' : 'neutral') ?>">
                                    <?= $team['saldo_gols'] > 0 ? '+' : '' ?><?= $team['saldo_gols'] ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button onclick="editTeam(<?= $team['id'] ?>, '<?= htmlspecialchars($team['nome']) ?>')" 
                                                class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteTeam(<?= $team['id'] ?>, '<?= htmlspecialchars($team['nome']) ?>')" 
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Formulário de Edição -->
                                    <div id="edit-form-<?= $team['id'] ?>" class="edit-form">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_team">
                                            <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                            <input type="text" name="nome" class="edit-input" 
                                                   value="<?= htmlspecialchars($team['nome']) ?>" required>
                                            <div>
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-save"></i> Salvar
                                                </button>
                                                <button type="button" onclick="cancelEdit(<?= $team['id'] ?>)" 
                                                        class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-times"></i> Cancelar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Nenhum Time Encontrado</h3>
                <p>Não há times que correspondam aos filtros selecionados.</p>
                <a href="select_tournament_for_team.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Cadastrar Primeiro Time
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function editTeam(teamId, teamName) {
            // Esconder todos os formulários de edição
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Mostrar o formulário específico
            document.getElementById('edit-form-' + teamId).style.display = 'block';
        }
        
        function cancelEdit(teamId) {
            document.getElementById('edit-form-' + teamId).style.display = 'none';
        }
        
        function deleteTeam(teamId, teamName) {
            if (confirm(`⚠️ ATENÇÃO!\n\nTem certeza que deseja excluir o time "${teamName}"?\n\nEsta ação não pode ser desfeita!`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_team">
                    <input type="hidden" name="team_id" value="${teamId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>
