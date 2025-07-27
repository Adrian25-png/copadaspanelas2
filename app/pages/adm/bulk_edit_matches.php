<?php
/**
 * Edição em Lote de Jogos
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../classes/MatchManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;

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

// Processar edição em lote
if ($_POST && isset($_POST['matches'])) {
    try {
        $pdo->beginTransaction();
        $updated_count = 0;
        
        foreach ($_POST['matches'] as $match_id => $data) {
            $update_needed = false;
            $updates = [];
            $params = [];
            
            // Verificar se há alterações
            if (isset($data['team1_goals']) && $data['team1_goals'] !== '') {
                $updates[] = "team1_goals = ?";
                $params[] = (int)$data['team1_goals'];
                $update_needed = true;
            }
            
            if (isset($data['team2_goals']) && $data['team2_goals'] !== '') {
                $updates[] = "team2_goals = ?";
                $params[] = (int)$data['team2_goals'];
                $update_needed = true;
            }
            
            if (isset($data['status']) && $data['status'] !== '') {
                $updates[] = "status = ?";
                $params[] = $data['status'];
                $update_needed = true;
            }
            
            if (isset($data['match_date']) && $data['match_date'] !== '') {
                $time = $data['match_time'] ?? '00:00';
                $datetime = $data['match_date'] . ' ' . $time . ':00';
                $updates[] = "match_date = ?";
                $params[] = $datetime;
                $update_needed = true;
            }
            
            if ($update_needed) {
                $updates[] = "updated_at = NOW()";
                $params[] = $match_id;
                $params[] = $tournament_id;
                
                $sql = "UPDATE matches SET " . implode(', ', $updates) . " WHERE id = ? AND tournament_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $updated_count++;
                
                // Se o jogo foi finalizado, recalcular estatísticas
                if (isset($data['status']) && $data['status'] === 'finalizado' && 
                    isset($data['team1_goals']) && isset($data['team2_goals'])) {
                    
                    $match = $matchManager->getMatchById($match_id);
                    if ($match) {
                        // Reverter estatísticas antigas se existirem
                        if ($match['status'] === 'finalizado') {
                            $matchManager->revertMatchStatistics($match_id);
                        }
                        
                        // Aplicar novas estatísticas
                        $team1_goals = (int)$data['team1_goals'];
                        $team2_goals = (int)$data['team2_goals'];
                        
                        $matchManager->updateTeamStatistics($match['team1_id'], $team1_goals, $team2_goals);
                        $matchManager->updateTeamStatistics($match['team2_id'], $team2_goals, $team1_goals);
                        $matchManager->updateMatchStatistics($match_id, $match['team1_id'], $team1_goals, $team2_goals);
                        $matchManager->updateMatchStatistics($match_id, $match['team2_id'], $team2_goals, $team1_goals);
                    }
                }
            }
        }
        
        $pdo->commit();
        
        if ($updated_count > 0) {
            $tournamentManager->logActivity($tournament_id, 'EDICAO_LOTE', "$updated_count jogos editados em lote");
            $_SESSION['success'] = "$updated_count jogos atualizados com sucesso!";
        } else {
            $_SESSION['warning'] = "Nenhuma alteração foi feita.";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: bulk_edit_matches.php?tournament_id=$tournament_id");
    exit;
}

// Obter jogos
$matches = $matchManager->getTournamentMatches($tournament_id);

// Status disponíveis
$status_options = [
    '' => '-- Manter --',
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
    <title>Edição em Lote - <?= htmlspecialchars($tournament['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
            margin: 0;
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
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
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
        
        .alert-warning {
            background: rgba(243, 156, 18, 0.2);
            border: 1px solid #f39c12;
            color: #f39c12;
        }
        
        .bulk-actions {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .bulk-actions h3 {
            margin-bottom: 15px;
            color: #f39c12;
        }
        
        .bulk-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .matches-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px 8px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table th {
            background: rgba(52, 152, 219, 0.3);
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        
        .table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .team-cell {
            text-align: left;
            min-width: 120px;
        }
        
        .team-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .team-group {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .score-input {
            width: 50px;
            padding: 6px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
        }
        
        .score-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .date-input,
        .time-input,
        .status-select {
            padding: 6px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
        }
        
        .date-input:focus,
        .time-input:focus,
        .status-select:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-agendado { background: #f39c12; }
        .status-em_andamento { background: #3498db; }
        .status-finalizado { background: #27ae60; }
        .status-cancelado { background: #e74c3c; }
        
        @media (max-width: 768px) {
            .matches-table {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 8px 4px;
            }
            
            .bulk-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-edit"></i> Edição em Lote</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
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
        
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($_SESSION['warning']) ?>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>
        
        <!-- Ações em Lote -->
        <div class="bulk-actions">
            <h3><i class="fas fa-magic"></i> Ações Rápidas</h3>
            <div class="bulk-buttons">
                <button onclick="fillAllDates()" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Preencher Datas (Fins de Semana)
                </button>
                
                <button onclick="setAllStatus('finalizado')" class="btn btn-success">
                    <i class="fas fa-check"></i> Marcar Todos como Finalizados
                </button>
                
                <button onclick="setAllStatus('agendado')" class="btn btn-warning">
                    <i class="fas fa-clock"></i> Marcar Todos como Agendados
                </button>
                
                <button onclick="clearAll()" class="btn btn-secondary">
                    <i class="fas fa-eraser"></i> Limpar Tudo
                </button>
            </div>
        </div>
        
        <!-- Tabela de Jogos -->
        <form method="POST">
            <div class="matches-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time 1</th>
                            <th>Gols</th>
                            <th>X</th>
                            <th>Gols</th>
                            <th>Time 2</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Status</th>
                            <th>Fase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td class="team-cell">
                                    <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                                    <?php if ($match['group_name']): ?>
                                        <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <input type="number" 
                                           name="matches[<?= $match['id'] ?>][team1_goals]" 
                                           class="score-input" 
                                           min="0" max="99" 
                                           value="<?= $match['team1_goals'] ?? '' ?>"
                                           placeholder="0">
                                </td>
                                
                                <td style="font-weight: bold; color: #3498db;">VS</td>
                                
                                <td>
                                    <input type="number" 
                                           name="matches[<?= $match['id'] ?>][team2_goals]" 
                                           class="score-input" 
                                           min="0" max="99" 
                                           value="<?= $match['team2_goals'] ?? '' ?>"
                                           placeholder="0">
                                </td>
                                
                                <td class="team-cell">
                                    <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                                    <?php if ($match['group_name']): ?>
                                        <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <input type="date" 
                                           name="matches[<?= $match['id'] ?>][match_date]" 
                                           class="date-input" 
                                           value="<?= $match['match_date'] ? date('Y-m-d', strtotime($match['match_date'])) : '' ?>">
                                </td>
                                
                                <td>
                                    <input type="time" 
                                           name="matches[<?= $match['id'] ?>][match_time]" 
                                           class="time-input" 
                                           value="<?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : '' ?>">
                                </td>
                                
                                <td>
                                    <select name="matches[<?= $match['id'] ?>][status]" class="status-select">
                                        <?php foreach ($status_options as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $match['status'] === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                
                                <td>
                                    <span class="status-badge status-<?= $match['phase'] ?>">
                                        <?= ucfirst($match['phase']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salvar Todas as Alterações
                </button>
                
                <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    
    <script>
        function fillAllDates() {
            if (confirm('Preencher automaticamente com datas de fins de semana?')) {
                const dateInputs = document.querySelectorAll('input[type="date"]');
                const timeInputs = document.querySelectorAll('input[type="time"]');
                let currentDate = new Date();
                
                // Encontrar próximo sábado
                while (currentDate.getDay() !== 6) {
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                dateInputs.forEach((input, index) => {
                    if (!input.value) {
                        // Alternar entre sábado e domingo
                        if (index % 2 === 1) {
                            currentDate.setDate(currentDate.getDate() + 1); // Domingo
                        }
                        
                        const dateStr = currentDate.toISOString().split('T')[0];
                        input.value = dateStr;
                        
                        // Definir horário padrão
                        if (timeInputs[index] && !timeInputs[index].value) {
                            timeInputs[index].value = index % 2 === 0 ? '15:00' : '17:00';
                        }
                        
                        if (index % 2 === 1) {
                            // Próximo sábado
                            currentDate.setDate(currentDate.getDate() + 6);
                        }
                    }
                });
            }
        }
        
        function setAllStatus(status) {
            if (confirm(`Definir status "${status}" para todos os jogos?`)) {
                document.querySelectorAll('select.status-select').forEach(select => {
                    select.value = status;
                });
            }
        }
        
        function clearAll() {
            if (confirm('Limpar todos os campos editáveis?')) {
                document.querySelectorAll('input[type="number"]').forEach(input => input.value = '');
                document.querySelectorAll('input[type="date"]').forEach(input => input.value = '');
                document.querySelectorAll('input[type="time"]').forEach(input => input.value = '');
                document.querySelectorAll('select.status-select').forEach(select => select.value = '');
            }
        }
        
        // Auto-definir status baseado no resultado
        document.addEventListener('DOMContentLoaded', function() {
            const scoreInputs = document.querySelectorAll('input[type="number"]');
            
            scoreInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    const team1Goals = row.querySelector('input[name*="team1_goals"]').value;
                    const team2Goals = row.querySelector('input[name*="team2_goals"]').value;
                    const statusSelect = row.querySelector('select.status-select');
                    
                    if (team1Goals !== '' && team2Goals !== '') {
                        if (statusSelect.value === '' || statusSelect.value === 'agendado') {
                            statusSelect.value = 'finalizado';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
