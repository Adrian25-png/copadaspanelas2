<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-01-30 15:30:00
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática

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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-title .icon {
            font-size: 3rem;
            color: rgba(255,255,255,0.9);
        }

        .header-subtitle {
            margin-top: 10px;
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .content-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            font-size: 0.95rem;
            font-weight: 600;
            color: #E0E0E0;
            margin-bottom: 5px;
        }

        .filter-input {
            padding: 12px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filter-input option {
            background: #1a1a2e;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #d946ef);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        
        .teams-table {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            overflow-x: auto;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .table th,
        .table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table th {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(139, 92, 246, 0.3));
            font-weight: 600;
            color: #E0E0E0;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tr:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.01);
            transition: all 0.3s ease;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .team-name {
            font-weight: 600;
            color: #E0E0E0;
        }

        .points {
            font-weight: 700;
            color: #10b981;
            font-size: 1.1rem;
        }

        .positive {
            color: #10b981;
            font-weight: 600;
        }

        .negative {
            color: #ef4444;
            font-weight: 600;
        }

        .neutral {
            color: #f59e0b;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .edit-form {
            display: none;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.2));
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .edit-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin-bottom: 15px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1rem;
        }

        .edit-input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.15);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.6);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: rgba(99, 102, 241, 0.3);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #E0E0E0;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .header-actions {
                justify-content: center;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table {
                font-size: 0.85rem;
            }

            .table th,
            .table td {
                padding: 12px 8px;
            }

            .actions {
                flex-direction: column;
                gap: 5px;
            }

            .main-container {
                padding: 15px;
            }

            .content-card,
            .filters,
            .teams-table {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-users icon"></i>
                    <div>
                        <h1>Todos os Times</h1>
                        <div class="header-subtitle">Visualize e gerencie todos os times do sistema</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="select_tournament_for_team.php" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Novo Time
                    </a>
                    <a href="dashboard_simple.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
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
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_team">
                <input type="hidden" name="team_id" value="${teamId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das linhas da tabela
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animação dos cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });

            // Animação do header
            const header = document.querySelector('.page-header');
            if (header) {
                header.style.opacity = '0';
                header.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    header.style.transition = 'all 0.8s ease';
                    header.style.opacity = '1';
                    header.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>
