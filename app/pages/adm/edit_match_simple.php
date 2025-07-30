<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/conexao.php';

$pdo = conectar();
$tournament_id = $_GET['tournament_id'] ?? null;
$match_id = $_GET['match_id'] ?? null;

if (!$tournament_id || !$match_id) {
    die("Parâmetros inválidos");
}

// Processar formulário
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_match') {
    try {
        $team1_goals = $_POST['team1_goals'] !== '' ? (int)$_POST['team1_goals'] : null;
        $team2_goals = $_POST['team2_goals'] !== '' ? (int)$_POST['team2_goals'] : null;
        $status = $_POST['status'] ?: 'agendado';
        $match_date = $_POST['match_date'] ?: null;
        $match_time = $_POST['match_time'] ?: null;
        
        // Combinar data e hora
        $full_datetime = null;
        if ($match_date) {
            $full_datetime = $match_date;
            if ($match_time) {
                $full_datetime .= ' ' . $match_time;
            } else {
                $full_datetime .= ' 00:00:00';
            }
        }
        
        // Atualizar jogo
        $stmt = $pdo->prepare("
            UPDATE matches 
            SET team1_goals = ?, team2_goals = ?, status = ?, match_date = ?, updated_at = NOW()
            WHERE id = ? AND tournament_id = ?
        ");
        
        $result = $stmt->execute([$team1_goals, $team2_goals, $status, $full_datetime, $match_id, $tournament_id]);
        
        if ($result) {
            $_SESSION['success'] = "Jogo atualizado com sucesso!";
            header("Location: edit_match_simple.php?tournament_id=$tournament_id&match_id=$match_id");
            exit;
        } else {
            $_SESSION['error'] = "Erro ao atualizar jogo";
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro: " . $e->getMessage();
    }
}

// Buscar dados do jogo
$stmt = $pdo->prepare("
    SELECT m.*, 
           t1.nome as team1_name, t2.nome as team2_name
    FROM matches m
    LEFT JOIN times t1 ON m.team1_id = t1.id
    LEFT JOIN times t2 ON m.team2_id = t2.id
    WHERE m.id = ? AND m.tournament_id = ?
");
$stmt->execute([$match_id, $tournament_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Jogo não encontrado");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Jogo - Versão Simples</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .score-row {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: end;
        }
        
        .vs {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #666;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .match-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .team-names {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .current-score {
            font-size: 24px;
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Jogo - Versão Simples</h1>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Info do Jogo -->
        <div class="match-info">
            <div class="team-names">
                <?= htmlspecialchars($match['team1_name']) ?> vs <?= htmlspecialchars($match['team2_name']) ?>
            </div>
            <div class="current-score">
                <?php if ($match['status'] === 'finalizado' && $match['team1_goals'] !== null): ?>
                    <?= $match['team1_goals'] ?> - <?= $match['team2_goals'] ?>
                <?php else: ?>
                    Sem resultado
                <?php endif; ?>
            </div>
            <div>Status: <?= ucfirst($match['status']) ?></div>
            <?php if ($match['match_date']): ?>
                <div>Data: <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Formulário -->
        <form method="POST">
            <input type="hidden" name="action" value="update_match">
            
            <h3>Resultado</h3>
            <div class="score-row">
                <div class="form-group">
                    <label><?= htmlspecialchars($match['team1_name']) ?></label>
                    <input type="number" name="team1_goals" min="0" max="99" 
                           value="<?= $match['team1_goals'] ?? '' ?>" placeholder="Gols">
                </div>
                
                <div class="vs">X</div>
                
                <div class="form-group">
                    <label><?= htmlspecialchars($match['team2_name']) ?></label>
                    <input type="number" name="team2_goals" min="0" max="99" 
                           value="<?= $match['team2_goals'] ?? '' ?>" placeholder="Gols">
                </div>
            </div>
            
            <h3>Configurações</h3>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="agendado" <?= $match['status'] === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                    <option value="em_andamento" <?= $match['status'] === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="finalizado" <?= $match['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                    <option value="cancelado" <?= $match['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Data</label>
                <input type="date" name="match_date" 
                       value="<?= $match['match_date'] ? date('Y-m-d', strtotime($match['match_date'])) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Horário</label>
                <input type="time" name="match_time" 
                       value="<?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : '' ?>">
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-success">Salvar Alterações</button>
                <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">Cancelar</a>
                <a href="edit_match.php?tournament_id=<?= $tournament_id ?>&match_id=<?= $match_id ?>" class="btn btn-primary">Versão Completa</a>
            </div>
        </form>
    </div>
</body>
</html>
