<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


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

        .tournament-info h1 {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .tournament-info h1 i {
            color: #7B1FA2;
        }

        .tournament-year {
            font-size: 1.1rem;
            color: #9E9E9E;
            padding-top: 5px;
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

        .filters {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
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
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .filters select {
            padding: 12px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            min-width: 160px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 500;
        }

        .filters select:focus {
            outline: none;
            border-color: #E1BEE7;
            box-shadow: 0 0 10px rgba(123, 31, 162, 0.3);
        }

        .filters select option {
            background: #2A2A2A;
            color: #E0E0E0;
        }

        .bulk-actions {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .bulk-actions::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .matches-table {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .matches-table::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #2A2A2A;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #E1BEE7;
            border-bottom: 2px solid #7B1FA2;
            font-size: 0.95rem;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #333;
            vertical-align: middle;
            color: #E0E0E0;
        }

        .table tr:hover {
            background: #2A2A2A;
        }

        .table input, .table select {
            padding: 8px 10px;
            border: 2px solid #7B1FA2;
            border-radius: 6px;
            background: #2A2A2A;
            color: #E0E0E0;
            width: 100%;
            max-width: 130px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 500;
        }

        .table input:focus, .table select:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333;
            box-shadow: 0 0 8px rgba(123, 31, 162, 0.3);
        }

        .table input::placeholder {
            color: #9E9E9E;
        }

        .table select option {
            background: #2A2A2A;
            color: #E0E0E0;
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
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .btn-success:hover {
            background: #4CAF50;
            color: white;
        }

        .btn-warning {
            border-color: #FF9800;
            color: #FF9800;
        }

        .btn-warning:hover {
            background: #FF9800;
            color: white;
        }

        .btn-danger {
            border-color: #F44336;
            color: #F44336;
        }

        .btn-danger:hover {
            background: #F44336;
            color: white;
        }

        .btn-info {
            border-color: #2196F3;
            color: #2196F3;
        }

        .btn-info:hover {
            background: #2196F3;
            color: white;
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

        .match-teams {
            font-weight: 600;
            color: #E1BEE7;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 2px solid;
        }

        .status-agendado {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            border-color: #2196F3;
        }
        .status-finalizado {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border-color: #4CAF50;
        }
        .status-em_andamento {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
            border-color: #FF9800;
        }
        .status-cancelado {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border-color: #F44336;
        }

        .checkbox-column {
            width: 60px;
            text-align: center;
        }

        .checkbox-column input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #7B1FA2;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #9E9E9E;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 25px;
            color: #7B1FA2;
            opacity: 0.6;
        }

        .empty-state h3 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: #E1BEE7;
        }

        .empty-state p {
            font-size: 1.1rem;
            line-height: 1.5;
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
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .tournament-info h1 {
                font-size: 1.8rem;
            }

            .filters, .bulk-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .table {
                font-size: 0.85rem;
            }

            .table th, .table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
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
            <div class="alert alert-success fade-in" style="animation-delay: 0.2s;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in" style="animation-delay: 0.2s;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filters fade-in" style="animation-delay: 0.3s;">
            <label><strong><i class="fas fa-filter"></i> Filtros:</strong></label>

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

            <button onclick="clearFilters()" class="btn-standard">
                <i class="fas fa-times"></i> Limpar Filtros
            </button>
        </div>

        <!-- Ações em Lote -->
        <div class="bulk-actions fade-in" style="animation-delay: 0.4s;">
            <label><strong><i class="fas fa-tasks"></i> Ações em Lote:</strong></label>

            <button onclick="selectAll()" class="btn-standard btn-info">
                <i class="fas fa-check-square"></i> Selecionar Todos
            </button>

            <button onclick="selectNone()" class="btn-standard">
                <i class="fas fa-square"></i> Desmarcar Todos
            </button>

            <button onclick="deleteSelected()" class="btn-standard btn-danger">
                <i class="fas fa-trash"></i> Excluir Selecionados
            </button>
        </div>

        <!-- Tabela de Jogos -->
        <?php if (empty($matches)): ?>
            <div class="empty-state fade-in" style="animation-delay: 0.5s;">
                <i class="fas fa-futbol"></i>
                <h3>Nenhum Jogo Encontrado</h3>
                <p>Não há jogos que correspondam aos filtros selecionados.</p>
            </div>
        <?php else: ?>
            <form method="POST" id="bulkEditForm">
                <input type="hidden" name="action" value="bulk_update">

                <div class="matches-table fade-in" style="animation-delay: 0.5s;">
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
                    <button type="submit" class="btn-standard btn-success">
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
                alert('Por favor, selecione pelo menos um jogo para excluir.');
                return;
            }

            if (!confirm(`Tem certeza que deseja excluir ${selected.length} jogo(s) selecionado(s)? Esta ação não pode ser desfeita.`)) {
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

        // Animações e interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover às linhas da tabela
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.boxShadow = '0 3px 10px rgba(123, 31, 162, 0.2)';
                });

                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });

            // Validação do formulário
            const bulkEditForm = document.getElementById('bulkEditForm');
            if (bulkEditForm) {
                bulkEditForm.addEventListener('submit', function(e) {
                    const inputs = this.querySelectorAll('input[type="date"], input[type="time"], input[type="number"], select');
                    let hasChanges = false;

                    inputs.forEach(input => {
                        if (input.value && input.value !== input.defaultValue) {
                            hasChanges = true;
                        }
                    });

                    if (!hasChanges) {
                        e.preventDefault();
                        alert('Nenhuma alteração foi detectada. Faça pelo menos uma modificação antes de salvar.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
