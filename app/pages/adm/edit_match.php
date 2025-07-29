<?php
/**
 * Edição Avançada de Jogos
 */

// Configurar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar na tela
ini_set('log_errors', 1);

session_start();

try {
    require_once '../../config/conexao.php';
    require_once '../../classes/TournamentManager.php';
    require_once '../../classes/MatchManager.php';
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar dependências: " . $e->getMessage();
    header('Location: tournament_list.php');
    exit;
}

try {
    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
} catch (Exception $e) {
    $_SESSION['error'] = "Erro de conexão: " . $e->getMessage();
    header('Location: tournament_list.php');
    exit;
}

// Suporte para diferentes formatos de parâmetros
$tournament_id = $_GET['tournament_id'] ?? null;
$match_id = $_GET['match_id'] ?? $_GET['id'] ?? null;

// Se só temos o ID do jogo, buscar o tournament_id
if (!$tournament_id && $match_id) {
    try {
        $stmt = $pdo->prepare("SELECT tournament_id FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $tournament_id = $result['tournament_id'];
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao buscar dados do jogo: " . $e->getMessage();
        header('Location: tournament_list.php');
        exit;
    }
}

if (!$tournament_id || !$match_id) {
    $_SESSION['error'] = "Parâmetros inválidos";
    header('Location: tournament_list.php');
    exit;
}

$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    $_SESSION['error'] = "Torneio não encontrado";
    header('Location: tournament_list.php');
    exit;
}

try {
    $matchManager = new MatchManager($pdo, $tournament_id);
    $match = $matchManager->getMatchById($match_id);
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao acessar dados do jogo: " . $e->getMessage();
    header('Location: match_manager.php?tournament_id=' . $tournament_id);
    exit;
}

if (!$match) {
    $_SESSION['error'] = "Jogo não encontrado";
    header('Location: match_manager.php?tournament_id=' . $tournament_id);
    exit;
}

// Processar edição
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_match':
                $team1_goals = $_POST['team1_goals'] !== '' ? (int)$_POST['team1_goals'] : null;
                $team2_goals = $_POST['team2_goals'] !== '' ? (int)$_POST['team2_goals'] : null;
                $match_date = $_POST['match_date'] ?: null;
                $match_time = $_POST['match_time'] ?: null;
                $status = $_POST['status'] ?: 'agendado';
                
                // Combinar data e hora se ambos fornecidos
                $full_datetime = null;
                if ($match_date) {
                    $full_datetime = $match_date;
                    if ($match_time) {
                        $full_datetime .= ' ' . $match_time;
                    } else {
                        $full_datetime .= ' 00:00:00';
                    }
                }
                
                // Atualizar dados básicos do jogo
                $stmt = $pdo->prepare("
                    UPDATE matches 
                    SET team1_goals = ?, team2_goals = ?, status = ?, 
                        match_date = ?, updated_at = NOW()
                    WHERE id = ? AND tournament_id = ?
                ");
                $stmt->execute([$team1_goals, $team2_goals, $status, $full_datetime, $match_id, $tournament_id]);
                
                // Se o jogo foi finalizado, atualizar estatísticas (simplificado)
                if ($status === 'finalizado' && $team1_goals !== null && $team2_goals !== null) {
                    try {
                        // Primeiro, reverter estatísticas antigas se existirem
                        if ($match['status'] === 'finalizado') {
                            // Reverter estatísticas antigas (implementação simplificada)
                        }

                        // Aplicar novas estatísticas (implementação simplificada)
                        // TODO: Implementar atualização de estatísticas

                    } catch (Exception $e) {
                        // Log do erro mas não interrompe o processo
                        error_log("Erro ao atualizar estatísticas: " . $e->getMessage());
                    }
                }
                
                // Log da atividade (simplificado)
                error_log("Jogo ID $match_id editado: {$match['team1_name']} vs {$match['team2_name']}");
                $_SESSION['success'] = "Jogo atualizado com sucesso!";
                
                header("Location: edit_match.php?tournament_id=$tournament_id&match_id=$match_id");
                exit;
                break;
                
            case 'swap_teams':
                // Trocar times de lugar
                $stmt = $pdo->prepare("
                    UPDATE matches 
                    SET team1_id = ?, team2_id = ?, 
                        team1_goals = ?, team2_goals = ?,
                        updated_at = NOW()
                    WHERE id = ? AND tournament_id = ?
                ");
                $stmt->execute([
                    $match['team2_id'], $match['team1_id'],
                    $match['team2_goals'], $match['team1_goals'],
                    $match_id, $tournament_id
                ]);
                
                $_SESSION['success'] = "Times trocados de posição!";
                header("Location: edit_match.php?tournament_id=$tournament_id&match_id=$match_id");
                exit;
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Recarregar dados do jogo após possíveis alterações
$match = $matchManager->getMatchById($match_id);

// Obter todos os times do torneio para possível troca
$stmt = $pdo->prepare("
    SELECT t.id, t.nome, g.nome as grupo_nome 
    FROM times t 
    LEFT JOIN grupos g ON t.grupo_id = g.id 
    WHERE t.tournament_id = ? 
    ORDER BY g.nome, t.nome
");
$stmt->execute([$tournament_id]);
$all_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status disponíveis
$status_options = [
    'agendado' => 'Agendado',
    'em_andamento' => 'Em Andamento',
    'finalizado' => 'Finalizado',
    'cancelado' => 'Cancelado'
];

// Fases disponíveis
$phase_options = [
    'grupos' => 'Fase de Grupos',
    'oitavas' => 'Oitavas de Final',
    'quartas' => 'Quartas de Final',
    'semifinal' => 'Semifinal',
    'final' => 'Final',
    'terceiro_lugar' => 'Terceiro Lugar'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Jogo - <?= htmlspecialchars($tournament['name']) ?></title>
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
            max-width: 800px;
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
            transform: translateY(-2px);
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
        
        .match-preview {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
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
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .team-group {
            font-size: 1rem;
            opacity: 0.7;
        }
        
        .match-score {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .match-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: bold;
        }
        
        .form-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f39c12;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #ecf0f1;
        }
        
        .form-input {
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .score-inputs {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 15px;
            align-items: end;
        }
        
        .score-input {
            text-align: center;
        }
        
        .score-input input {
            width: 80px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .vs-divider {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
            text-align: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
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
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .match-teams {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }
            
            .score-inputs {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-edit"></i> Editar Jogo</h1>
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
        
        <!-- Preview do Jogo -->
        <div class="match-preview">
            <div class="match-teams">
                <div class="team-info">
                    <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                    <?php if ($match['group_name']): ?>
                        <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="match-score">
                    <?php if ($match['status'] === 'finalizado' && $match['team1_goals'] !== null): ?>
                        <?= $match['team1_goals'] ?> - <?= $match['team2_goals'] ?>
                    <?php else: ?>
                        VS
                    <?php endif; ?>
                </div>
                
                <div class="team-info">
                    <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                    <?php if ($match['group_name']): ?>
                        <div class="team-group"><?= htmlspecialchars($match['group_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="match-info">
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value"><?= ucfirst($match['status']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fase</div>
                    <div class="info-value"><?= $phase_options[$match['phase']] ?? ucfirst($match['phase']) ?></div>
                </div>
                <?php if ($match['match_date']): ?>
                    <div class="info-item">
                        <div class="info-label">Data</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($match['match_date'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Horário</div>
                        <div class="info-value"><?= date('H:i', strtotime($match['match_date'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="form-section">
            <h3 class="section-title"><i class="fas fa-bolt"></i> Ações Rápidas</h3>
            
            <div class="quick-actions">
                <button onclick="setResult(0, 0)" class="btn btn-secondary">0-0</button>
                <button onclick="setResult(1, 0)" class="btn btn-secondary">1-0</button>
                <button onclick="setResult(0, 1)" class="btn btn-secondary">0-1</button>
                <button onclick="setResult(1, 1)" class="btn btn-secondary">1-1</button>
                <button onclick="setResult(2, 0)" class="btn btn-secondary">2-0</button>
                <button onclick="setResult(0, 2)" class="btn btn-secondary">0-2</button>
                <button onclick="setResult(2, 1)" class="btn btn-secondary">2-1</button>
                <button onclick="setResult(1, 2)" class="btn btn-secondary">1-2</button>
                <button onclick="setResult(3, 0)" class="btn btn-secondary">3-0</button>
                <button onclick="setResult(0, 3)" class="btn btn-secondary">0-3</button>
            </div>
            
            <div class="quick-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="swap_teams">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Trocar os times de posição?')">
                        <i class="fas fa-exchange-alt"></i> Trocar Times
                    </button>
                </form>
                
                <button onclick="clearResult()" class="btn btn-secondary">
                    <i class="fas fa-eraser"></i> Limpar Resultado
                </button>
                
                <button onclick="setToday()" class="btn btn-primary">
                    <i class="fas fa-calendar-day"></i> Data de Hoje
                </button>
            </div>
        </div>
        
        <!-- Formulário de Edição -->
        <form method="POST">
            <input type="hidden" name="action" value="update_match">
            
            <!-- Resultado -->
            <div class="form-section">
                <h3 class="section-title"><i class="fas fa-futbol"></i> Resultado</h3>
                
                <div class="score-inputs">
                    <div class="score-input">
                        <label class="form-label"><?= htmlspecialchars($match['team1_name']) ?></label>
                        <input type="number" 
                               name="team1_goals" 
                               id="team1_goals"
                               class="form-input" 
                               min="0" max="99" 
                               value="<?= $match['team1_goals'] ?? '' ?>"
                               placeholder="Gols">
                    </div>
                    
                    <div class="vs-divider">X</div>
                    
                    <div class="score-input">
                        <label class="form-label"><?= htmlspecialchars($match['team2_name']) ?></label>
                        <input type="number" 
                               name="team2_goals" 
                               id="team2_goals"
                               class="form-input" 
                               min="0" max="99" 
                               value="<?= $match['team2_goals'] ?? '' ?>"
                               placeholder="Gols">
                    </div>
                </div>
            </div>
            
            <!-- Data e Status -->
            <div class="form-section">
                <h3 class="section-title"><i class="fas fa-cog"></i> Configurações</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Data do Jogo</label>
                        <input type="date" 
                               name="match_date" 
                               id="match_date"
                               class="form-input" 
                               value="<?= $match['match_date'] ? date('Y-m-d', strtotime($match['match_date'])) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Horário</label>
                        <input type="time" 
                               name="match_time" 
                               id="match_time"
                               class="form-input" 
                               value="<?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <?php foreach ($status_options as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $match['status'] === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Ações -->
            <div class="actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                
                <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                
                <button type="button" onclick="deleteMatch()" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Excluir Jogo
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function setResult(goals1, goals2) {
            document.getElementById('team1_goals').value = goals1;
            document.getElementById('team2_goals').value = goals2;
            
            // Automaticamente definir como finalizado se há resultado
            if (goals1 !== '' && goals2 !== '') {
                document.querySelector('select[name="status"]').value = 'finalizado';
            }
        }
        
        function clearResult() {
            document.getElementById('team1_goals').value = '';
            document.getElementById('team2_goals').value = '';
            document.querySelector('select[name="status"]').value = 'agendado';
        }
        
        function setToday() {
            const today = new Date();
            const dateStr = today.toISOString().split('T')[0];
            const timeStr = '20:00';
            
            document.getElementById('match_date').value = dateStr;
            document.getElementById('match_time').value = timeStr;
        }
        
        function deleteMatch() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'match_manager.php?tournament_id=<?= $tournament_id ?>';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_match">
                <input type="hidden" name="match_id" value="<?= $match_id ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Auto-definir status baseado no resultado
        document.addEventListener('DOMContentLoaded', function() {
            const goals1Input = document.getElementById('team1_goals');
            const goals2Input = document.getElementById('team2_goals');
            const statusSelect = document.querySelector('select[name="status"]');
            
            function updateStatus() {
                const goals1 = goals1Input.value;
                const goals2 = goals2Input.value;
                
                if (goals1 !== '' && goals2 !== '') {
                    if (statusSelect.value === 'agendado') {
                        statusSelect.value = 'finalizado';
                    }
                } else {
                    if (statusSelect.value === 'finalizado') {
                        statusSelect.value = 'agendado';
                    }
                }
            }
            
            goals1Input.addEventListener('input', updateStatus);
            goals2Input.addEventListener('input', updateStatus);
        });
    </script>
</body>
</html>
