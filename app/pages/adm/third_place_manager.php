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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terceiro Lugar - <?= htmlspecialchars($tournament['name']) ?></title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: #1E1E1E;
            border-left: 4px solid #cd7f32;
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
            background: linear-gradient(90deg, #cd7f32, #FFD700);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #FFD700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            color: #cd7f32;
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

        .section {
            background: #1E1E1E;
            border-left: 4px solid #cd7f32;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #cd7f32, #FFD700);
        }

        .section-title {
            color: #FFD700;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #cd7f32;
        }

        .btn-success {
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .btn-success:hover {
            background: #4CAF50;
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

        .btn-bronze {
            border-color: #cd7f32;
            color: #FFD700;
        }

        .btn-bronze:hover {
            background: #cd7f32;
            color: white;
        }

        .match-card {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            border: 3px solid #cd7f32;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .match-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #cd7f32, #FFD700);
        }

        .match-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(205, 127, 50, 0.3);
        }

        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .team-name {
            font-weight: 600;
            color: #FFD700;
        }

        .vs {
            color: #cd7f32;
            font-weight: 700;
            font-size: 1.6rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #FFD700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #cd7f32;
        }

        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #cd7f32;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
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
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <h1><i class="fas fa-medal"></i> Disputa do Terceiro Lugar</h1>
            <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

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

        <?php if ($third_place_match): ?>
        <div class="section fade-in" style="animation-delay: 0.3s;">
            <div class="section-title">
                <i class="fas fa-medal"></i> Jogo do Terceiro Lugar
            </div>
            <div class="match-card">
                <div class="match-teams">
                    <span class="team-name"><?= htmlspecialchars($third_place_match['team1_name'] ?? 'TBD') ?></span>
                    <span class="vs">VS</span>
                    <span class="team-name"><?= htmlspecialchars($third_place_match['team2_name'] ?? 'TBD') ?></span>
                </div>
                <?php if ($third_place_match['status'] === 'finalizado'): ?>
                <div style="font-size: 1.6rem; color: #FFD700; font-weight: 700; margin-bottom: 20px; background: #1E1E1E; padding: 10px 15px; border-radius: 8px; border: 2px solid #cd7f32;">
                    <?= $third_place_match['team1_goals'] ?> - <?= $third_place_match['team2_goals'] ?>
                </div>
                <?php else: ?>
                <div style="color: #9E9E9E; margin-bottom: 20px; font-weight: 500; font-size: 1.1rem;">
                    Status: <?= ucfirst($third_place_match['status']) ?>
                </div>
                <?php endif; ?>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="edit_match.php?tournament_id=<?= $tournament_id ?>&match_id=<?= $third_place_match['id'] ?>" class="btn-standard">
                        <i class="fas fa-edit"></i> Editar Jogo
                    </a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_third_place">
                        <button type="submit" class="btn-standard btn-danger" onclick="return confirm('Remover jogo de terceiro lugar?')">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="section fade-in" style="animation-delay: 0.3s;">
            <div class="section-title">
                <i class="fas fa-plus"></i> Criar Jogo do Terceiro Lugar
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_third_place">

                <div class="form-group">
                    <label><i class="fas fa-users"></i> Time 1:</label>
                    <select name="team1_id" required>
                        <option value="">Selecione o primeiro time</option>
                        <?php foreach ($all_teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-users"></i> Time 2:</label>
                    <select name="team2_id" required>
                        <option value="">Selecione o segundo time</option>
                        <?php foreach ($all_teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn-standard btn-bronze">
                        <i class="fas fa-plus"></i> Criar Jogo
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Animações e interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Animação para o card de jogo
            const matchCard = document.querySelector('.match-card');
            if (matchCard) {
                matchCard.style.opacity = '0';
                matchCard.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    matchCard.style.transition = 'all 0.6s ease';
                    matchCard.style.opacity = '1';
                    matchCard.style.transform = 'scale(1)';
                }, 800);
            }

            // Validação do formulário
            const form = document.querySelector('form[method="POST"]');
            if (form && form.querySelector('select[name="team1_id"]')) {
                form.addEventListener('submit', function(e) {
                    const team1 = form.querySelector('select[name="team1_id"]').value;
                    const team2 = form.querySelector('select[name="team2_id"]').value;

                    if (team1 === team2 && team1 !== '') {
                        e.preventDefault();
                        alert('Os times devem ser diferentes!');
                        return false;
                    }
                });
            }

            // Efeitos nos selects
            const selects = document.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('focus', function() {
                    this.style.borderColor = '#FFD700';
                    this.style.boxShadow = '0 0 10px rgba(255, 215, 0, 0.3)';
                });

                select.addEventListener('blur', function() {
                    this.style.borderColor = '#cd7f32';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>
