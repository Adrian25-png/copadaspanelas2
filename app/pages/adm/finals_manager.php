<?php
/**
 * Gerenciador de Fases Finais
 * Configuração e gerenciamento das eliminatórias
 */

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
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_knockout_matches':
                try {
                    $phase = $_POST['phase'];
                    $teams = $_POST['teams'] ?? [];
                    
                    if (count($teams) % 2 !== 0) {
                        throw new Exception("Número de times deve ser par para criar confrontos");
                    }
                    
                    // Criar confrontos
                    for ($i = 0; $i < count($teams); $i += 2) {
                        $stmt = $pdo->prepare("
                            INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                            VALUES (?, ?, ?, ?, 'agendado', NOW())
                        ");
                        $stmt->execute([$tournament_id, $teams[$i], $teams[$i + 1], $phase]);
                    }
                    
                    $_SESSION['success'] = "Confrontos de $phase criados com sucesso!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Erro ao criar confrontos: " . $e->getMessage();
                }
                break;
                
            case 'delete_phase_matches':
                try {
                    $phase = $_POST['phase'];
                    $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
                    $stmt->execute([$tournament_id, $phase]);
                    $_SESSION['success'] = "Jogos de $phase removidos com sucesso!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Erro ao remover jogos: " . $e->getMessage();
                }
                break;
        }
        
        header("Location: finals_manager.php?tournament_id=$tournament_id");
        exit;
    }
}

// Buscar dados
try {
    // Times classificados (primeiros e segundos de cada grupo)
    $stmt = $pdo->prepare("
        SELECT t.id, t.nome, g.nome as grupo_nome,
               COALESCE(ts.pontos, 0) as pontos,
               COALESCE(ts.posicao_grupo, 99) as posicao
        FROM times t
        LEFT JOIN grupos g ON t.grupo_id = g.id
        LEFT JOIN team_statistics ts ON t.id = ts.team_id
        WHERE t.tournament_id = ? AND ts.posicao_grupo <= 2
        ORDER BY g.nome, ts.posicao_grupo
    ");
    $stmt->execute([$tournament_id]);
    $qualified_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jogos existentes por fase
    $phases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    $existing_matches = [];
    
    foreach ($phases as $phase) {
        $stmt = $pdo->prepare("
            SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            WHERE m.tournament_id = ? AND m.phase = ?
            ORDER BY m.created_at
        ");
        $stmt->execute([$tournament_id, $phase]);
        $existing_matches[$phase] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $qualified_teams = [];
    $existing_matches = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fases Finais - <?= htmlspecialchars($tournament['name']) ?></title>
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
            max-width: 1200px;
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
            color: #f39c12;
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
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .phase-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .phase-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .phase-title {
            color: #f39c12;
            font-size: 1.3rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 5px;
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
        
        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .match-card {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .team-name {
            font-weight: bold;
            color: #ffffff;
        }
        
        .vs {
            color: #f39c12;
            font-weight: bold;
        }
        
        .match-score {
            text-align: center;
            color: #27ae60;
            font-weight: bold;
        }
        
        .qualified-teams {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .team-card {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .team-card.first-place {
            border-color: #f39c12;
        }
        
        .team-card.second-place {
            border-color: #95a5a6;
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
        
        .info-box {
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #74b9ff;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .matches-grid {
                grid-template-columns: 1fr;
            }
            
            .qualified-teams {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Gerenciar Fases Finais</h1>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
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
        
        <!-- Info sobre times classificados -->
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Times Classificados:</strong> <?= count($qualified_teams) ?> times qualificados para as fases finais.
            Para criar eliminatórias, você precisa de 2, 4, 8 ou 16 times.
        </div>
        
        <!-- Times Classificados -->
        <?php if (!empty($qualified_teams)): ?>
        <div class="phase-section">
            <div class="phase-title">
                <i class="fas fa-users"></i> Times Classificados
            </div>
            <div class="qualified-teams">
                <?php foreach ($qualified_teams as $team): ?>
                <div class="team-card <?= $team['posicao'] == 1 ? 'first-place' : 'second-place' ?>">
                    <div class="team-name"><?= htmlspecialchars($team['nome']) ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">
                        <?= htmlspecialchars($team['grupo_nome']) ?> - <?= $team['posicao'] ?>º lugar
                    </div>
                    <div style="font-size: 0.8rem; color: #f39c12;">
                        <?= $team['pontos'] ?> pts
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Fases das Eliminatórias -->
        <?php foreach ($phases as $phase): ?>
        <div class="phase-section">
            <div class="phase-header">
                <div class="phase-title">
                    <i class="fas fa-trophy"></i> <?= $phase ?>
                </div>
                <div>
                    <?php if (empty($existing_matches[$phase])): ?>
                        <button class="btn btn-success" onclick="createPhaseMatches('<?= $phase ?>')">
                            <i class="fas fa-plus"></i> Criar Confrontos
                        </button>
                    <?php else: ?>
                        <button class="btn btn-danger" onclick="deletePhaseMatches('<?= $phase ?>')">
                            <i class="fas fa-trash"></i> Remover Jogos
                        </button>
                        <a href="match_manager.php?tournament_id=<?= $tournament_id ?>&phase=<?= urlencode($phase) ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Editar Jogos
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($existing_matches[$phase])): ?>
            <div class="matches-grid">
                <?php foreach ($existing_matches[$phase] as $match): ?>
                <div class="match-card">
                    <div class="match-teams">
                        <span class="team-name"><?= htmlspecialchars($match['team1_name'] ?? 'TBD') ?></span>
                        <span class="vs">VS</span>
                        <span class="team-name"><?= htmlspecialchars($match['team2_name'] ?? 'TBD') ?></span>
                    </div>
                    <?php if ($match['status'] === 'finalizado'): ?>
                    <div class="match-score">
                        <?= $match['team1_goals'] ?> - <?= $match['team2_goals'] ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; color: #95a5a6;">
                        <?= ucfirst($match['status']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 20px; opacity: 0.7;">
                <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <div>Nenhum confronto criado para esta fase</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <!-- Ações Rápidas -->
        <div class="phase-section">
            <div class="phase-title">
                <i class="fas fa-magic"></i> Ações Rápidas
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
                <a href="knockout_generator.php?tournament_id=<?= $tournament_id ?>" class="btn btn-warning">
                    <i class="fas fa-magic"></i> Gerador Automático
                </a>
                <a href="../exibir_finais.php" class="btn btn-secondary" target="_blank">
                    <i class="fas fa-eye"></i> Visualizar Chaveamento
                </a>
                <a href="third_place_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-secondary">
                    <i class="fas fa-medal"></i> Disputa 3º Lugar
                </a>
            </div>
        </div>
    </div>

    <script>
        function createPhaseMatches(phase) {
            // Implementar lógica de criação
            console.log('Funcionalidade em desenvolvimento');
        }

        function deletePhaseMatches(phase) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_phase_matches">
                <input type="hidden" name="phase" value="${phase}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
