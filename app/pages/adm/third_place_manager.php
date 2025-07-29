<?php
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
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_third_place':
            try {
                $team1_id = $_POST['team1_id'];
                $team2_id = $_POST['team2_id'];
                
                if (!$team1_id || !$team2_id) {
                    throw new Exception("Selecione os dois times");
                }
                
                if ($team1_id == $team2_id) {
                    throw new Exception("Os times devem ser diferentes");
                }
                
                $stmt = $pdo->prepare("SELECT id FROM matches WHERE tournament_id = ? AND phase = '3º Lugar'");
                $stmt->execute([$tournament_id]);
                if ($stmt->fetch()) {
                    throw new Exception("Já existe um jogo de terceiro lugar");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                    VALUES (?, ?, ?, '3º Lugar', 'agendado', NOW())
                ");
                $stmt->execute([$tournament_id, $team1_id, $team2_id]);
                
                $_SESSION['success'] = "Jogo de terceiro lugar criado!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro: " . $e->getMessage();
            }
            break;
            
        case 'delete_third_place':
            try {
                $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = '3º Lugar'");
                $stmt->execute([$tournament_id]);
                $_SESSION['success'] = "Jogo removido!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro: " . $e->getMessage();
            }
            break;
    }
    
    header("Location: third_place_manager.php?tournament_id=$tournament_id");
    exit;
}

// Buscar dados
try {
    $stmt = $pdo->prepare("
        SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.phase = '3º Lugar'
    ");
    $stmt->execute([$tournament_id]);
    $third_place_match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, nome FROM times WHERE tournament_id = ? ORDER BY nome");
    $stmt->execute([$tournament_id]);
    $all_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $third_place_match = null;
    $all_teams = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Terceiro Lugar - <?= htmlspecialchars($tournament['name']) ?></title>
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
            margin: 0;
            color: #cd7f32;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-primary { background: #3498db; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .match-card {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 2px solid #cd7f32;
        }
        
        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .team-name {
            font-weight: bold;
            color: #ffffff;
        }
        
        .vs {
            color: #cd7f32;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #cd7f32;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-size: 1rem;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-medal"></i> Disputa do Terceiro Lugar</h1>
            <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
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
        
        <?php if ($third_place_match): ?>
        <div class="section">
            <h3 style="color: #cd7f32; margin-bottom: 20px;">
                <i class="fas fa-medal"></i> Jogo do Terceiro Lugar
            </h3>
            <div class="match-card">
                <div class="match-teams">
                    <span class="team-name"><?= htmlspecialchars($third_place_match['team1_name'] ?? 'TBD') ?></span>
                    <span class="vs">VS</span>
                    <span class="team-name"><?= htmlspecialchars($third_place_match['team2_name'] ?? 'TBD') ?></span>
                </div>
                <?php if ($third_place_match['status'] === 'finalizado'): ?>
                <div style="font-size: 1.5rem; color: #cd7f32; font-weight: bold; margin-bottom: 15px;">
                    <?= $third_place_match['team1_goals'] ?> - <?= $third_place_match['team2_goals'] ?>
                </div>
                <?php else: ?>
                <div style="color: #95a5a6; margin-bottom: 15px;">
                    Status: <?= ucfirst($third_place_match['status']) ?>
                </div>
                <?php endif; ?>
                <div>
                    <a href="edit_match.php?tournament_id=<?= $tournament_id ?>&match_id=<?= $third_place_match['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Jogo
                    </a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_third_place">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Remover jogo de terceiro lugar?')">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="section">
            <h3 style="color: #cd7f32; margin-bottom: 20px;">
                <i class="fas fa-plus"></i> Criar Jogo do Terceiro Lugar
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_third_place">
                
                <div class="form-group">
                    <label>Time 1:</label>
                    <select name="team1_id" required>
                        <option value="">Selecione o primeiro time</option>
                        <?php foreach ($all_teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Time 2:</label>
                    <select name="team2_id" required>
                        <option value="">Selecione o segundo time</option>
                        <?php foreach ($all_teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Criar Jogo
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
