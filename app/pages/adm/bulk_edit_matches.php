<?php
/**
 * Edição em Lote de Jogos
 * Permite editar múltiplos jogos simultaneamente
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

// Processar edições em lote
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'bulk_update':
                $updates = $_POST['matches'] ?? [];
                $updated_count = 0;
                
                $pdo->beginTransaction();
                
                foreach ($updates as $match_id => $data) {
                    $changes = [];
                    $params = [];
                    
                    // Verificar quais campos foram alterados
                    if (!empty($data['match_date'])) {
                        $date = $data['match_date']; // Formato: Y-m-d
                        $time = !empty($data['match_time']) ? $data['match_time'] : '00:00'; // Formato: H:i

                        // Garantir que o time está no formato correto (HH:MM)
                        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                            $datetime = $date . ' ' . $time . ':00'; // Formato final: Y-m-d H:i:s

                            // Validar se a data/hora é válida
                            if (DateTime::createFromFormat('Y-m-d H:i:s', $datetime) !== false) {
                                $changes[] = "match_date = ?";
                                $params[] = $datetime;
                            }
                        }
                    }
                    
                    if (!empty($data['status']) && $data['status'] !== 'unchanged') {
                        $changes[] = "status = ?";
                        $params[] = $data['status'];
                    }
                    
                    if (isset($data['team1_goals']) && $data['team1_goals'] !== '') {
                        $changes[] = "team1_goals = ?";
                        $params[] = (int)$data['team1_goals'];
                    }
                    
                    if (isset($data['team2_goals']) && $data['team2_goals'] !== '') {
                        $changes[] = "team2_goals = ?";
                        $params[] = (int)$data['team2_goals'];
                    }
                    
                    // Se há mudanças, atualizar
                    if (!empty($changes)) {
                        $changes[] = "updated_at = NOW()";
                        $params[] = $match_id;
                        
                        $sql = "UPDATE matches SET " . implode(', ', $changes) . " WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $updated_count++;
                    }
                }
                
                // Recalcular estatísticas se houve mudanças nos resultados
                if ($updated_count > 0) {
                    $matchManager->recalculateAllStatistics($tournament_id);
                }
                
                $pdo->commit();
                $_SESSION['success'] = "$updated_count jogos atualizados com sucesso!";
                $tournamentManager->logActivity($tournament_id, 'JOGOS_EDITADOS_LOTE', "$updated_count jogos editados em lote");
                break;
                
            case 'bulk_delete':
                $match_ids = $_POST['match_ids'] ?? [];
                if (!empty($match_ids)) {
                    $placeholders = str_repeat('?,', count($match_ids) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM matches WHERE id IN ($placeholders) AND tournament_id = ?");
                    $stmt->execute(array_merge($match_ids, [$tournament_id]));
                    
                    $deleted_count = $stmt->rowCount();
                    $_SESSION['success'] = "$deleted_count jogos excluídos com sucesso!";
                    $tournamentManager->logActivity($tournament_id, 'JOGOS_EXCLUIDOS_LOTE', "$deleted_count jogos excluídos em lote");
                }
                break;
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Erro ao processar edições: " . $e->getMessage();
    }
    
    header("Location: bulk_edit_matches.php?tournament_id=$tournament_id");
    exit;
}

// Filtros
$phase_filter = $_GET['phase'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Obter jogos
try {
    $where_conditions = ["m.tournament_id = ?"];
    $params = [$tournament_id];
    
    if ($phase_filter) {
        $where_conditions[] = "m.phase = ?";
        $params[] = $phase_filter;
    }
    
    if ($status_filter) {
        $where_conditions[] = "m.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT m.*,
               t1.nome as team1_name, t2.nome as team2_name,
               g.nome as group_name,
               DATE(m.match_date) as match_date_only,
               TIME(m.match_date) as match_time_only
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN grupos g ON m.group_id = g.id
        WHERE $where_clause
        ORDER BY m.phase, g.nome, m.created_at
    ");
    $stmt->execute($params);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obter fases disponíveis
    $stmt = $pdo->prepare("SELECT DISTINCT phase FROM matches WHERE tournament_id = ? ORDER BY phase");
    $stmt->execute([$tournament_id]);
    $phases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar jogos: " . $e->getMessage();
    $matches = [];
    $phases = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edição em Lote - <?= htmlspecialchars($tournament['name']) ?></title>
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
        
        .filters {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filters select {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            min-width: 150px;
        }
        
        .filters select option {
            background: #2c3e50;
            color: white;
        }
        
        .bulk-actions {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .matches-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: rgba(0, 0, 0, 0.4);
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: middle;
        }
        
        .table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .table input, .table select {
            padding: 6px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            width: 100%;
            max-width: 120px;
        }
        
        .table input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .table select option {
            background: #2c3e50;
            color: white;
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
        
        .match-teams {
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-agendado { background: rgba(52, 152, 219, 0.3); color: #3498db; }
        .status-finalizado { background: rgba(39, 174, 96, 0.3); color: #27ae60; }
        .status-em_andamento { background: rgba(243, 156, 18, 0.3); color: #f39c12; }
        .status-cancelado { background: rgba(231, 76, 60, 0.3); color: #e74c3c; }
        
        .checkbox-column {
            width: 50px;
            text-align: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #95a5a6;
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
            
            .filters, .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="tournament-info">
                <h1><i class="fas fa-edit"></i> Edição em Lote</h1>
                <div class="tournament-year"><?= htmlspecialchars($tournament['name']) ?> - <?= $tournament['year'] ?></div>
            </div>
            <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar aos Jogos
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
        
        <!-- Filtros -->
        <div class="filters">
            <label><strong>Filtros:</strong></label>
            
            <select onchange="updateFilters()" id="phaseFilter">
                <option value="">Todas as Fases</option>
                <?php foreach ($phases as $phase): ?>
                    <option value="<?= htmlspecialchars($phase) ?>" <?= $phase_filter === $phase ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $phase)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select onchange="updateFilters()" id="statusFilter">
                <option value="">Todos os Status</option>
                <option value="agendado" <?= $status_filter === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                <option value="em_andamento" <?= $status_filter === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                <option value="finalizado" <?= $status_filter === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                <option value="cancelado" <?= $status_filter === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
            
            <button onclick="clearFilters()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Limpar Filtros
            </button>
        </div>
        
        <!-- Ações em Lote -->
        <div class="bulk-actions">
            <button onclick="selectAll()" class="btn btn-secondary">
                <i class="fas fa-check-square"></i> Selecionar Todos
            </button>
            
            <button onclick="selectNone()" class="btn btn-secondary">
                <i class="fas fa-square"></i> Desmarcar Todos
            </button>
            
            <button onclick="deleteSelected()" class="btn btn-danger">
                <i class="fas fa-trash"></i> Excluir Selecionados
            </button>
        </div>
        
        <!-- Tabela de Jogos -->
        <?php if (empty($matches)): ?>
            <div class="empty-state">
                <i class="fas fa-futbol"></i>
                <h3>Nenhum Jogo Encontrado</h3>
                <p>Não há jogos que correspondam aos filtros selecionados.</p>
            </div>
        <?php else: ?>
            <form method="POST" id="bulkEditForm">
                <input type="hidden" name="action" value="bulk_update">
                
                <div class="matches-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()">
                                </th>
                                <th>Jogo</th>
                                <th>Fase/Grupo</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Status</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $match): ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <input type="checkbox" name="selected_matches[]" value="<?= $match['id'] ?>" class="match-checkbox">
                                    </td>
                                    
                                    <td class="match-teams">
                                        <?= htmlspecialchars($match['team1_name']) ?> vs <?= htmlspecialchars($match['team2_name']) ?>
                                    </td>
                                    
                                    <td>
                                        <?= ucfirst(str_replace('_', ' ', $match['phase'])) ?>
                                        <?php if ($match['group_name']): ?>
                                            <br><small><?= htmlspecialchars($match['group_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <input type="date" 
                                               name="matches[<?= $match['id'] ?>][match_date]" 
                                               value="<?= $match['match_date_only'] ?>"
                                               min="<?= date('Y-m-d') ?>">
                                    </td>
                                    
                                    <td>
                                        <input type="time" 
                                               name="matches[<?= $match['id'] ?>][match_time]" 
                                               value="<?= $match['match_time_only'] ?>">
                                    </td>
                                    
                                    <td>
                                        <select name="matches[<?= $match['id'] ?>][status]">
                                            <option value="unchanged">Não alterar</option>
                                            <option value="agendado" <?= $match['status'] === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                                            <option value="em_andamento" <?= $match['status'] === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                                            <option value="finalizado" <?= $match['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                                            <option value="cancelado" <?= $match['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                        </select>
                                    </td>
                                    
                                    <td>
                                        <div style="display: flex; gap: 5px; align-items: center;">
                                            <input type="number" 
                                                   name="matches[<?= $match['id'] ?>][team1_goals]" 
                                                   value="<?= $match['team1_goals'] ?>"
                                                   min="0" max="50" 
                                                   placeholder="0"
                                                   style="width: 50px;">
                                            <span>x</span>
                                            <input type="number" 
                                                   name="matches[<?= $match['id'] ?>][team2_goals]" 
                                                   value="<?= $match['team2_goals'] ?>"
                                                   min="0" max="50" 
                                                   placeholder="0"
                                                   style="width: 50px;">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 30px; text-align: center;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Todas as Alterações
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        function updateFilters() {
            const phase = document.getElementById('phaseFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('phase', phase);
            url.searchParams.set('status', status);
            
            window.location.href = url.toString();
        }
        
        function clearFilters() {
            const url = new URL(window.location);
            url.searchParams.delete('phase');
            url.searchParams.delete('status');
            
            window.location.href = url.toString();
        }
        
        function selectAll() {
            document.querySelectorAll('.match-checkbox').forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        }
        
        function selectNone() {
            document.querySelectorAll('.match-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        }
        
        function toggleAll() {
            const selectAll = document.getElementById('selectAllCheckbox').checked;
            document.querySelectorAll('.match-checkbox').forEach(cb => cb.checked = selectAll);
        }
        
        function deleteSelected() {
            const selected = document.querySelectorAll('.match-checkbox:checked');

            if (selected.length === 0) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">';

            selected.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'match_ids[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
