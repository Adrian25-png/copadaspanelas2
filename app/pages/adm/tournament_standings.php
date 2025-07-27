<?php
/**
 * Tabela de Classificação do Torneio - Área Administrativa
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    // Verificar se foi especificado um torneio
    $tournament_id = $_GET['id'] ?? null;
    
    if (!$tournament_id) {
        // Se não especificado, usar o torneio ativo
        $current_tournament = $tournamentManager->getCurrentTournament();
        if ($current_tournament) {
            $tournament_id = $current_tournament['id'];
        } else {
            throw new Exception("Nenhum torneio ativo encontrado");
        }
    }
    
    // Obter dados do torneio
    $tournament = $tournamentManager->getTournamentById($tournament_id);
    if (!$tournament) {
        throw new Exception("Torneio não encontrado");
    }
    
    // Obter classificação por grupos
    $stmt = $pdo->prepare("
        SELECT g.nome as grupo_nome, t.nome as time_nome, t.pts, t.vitorias, t.empates, t.derrotas, t.gm, t.gc, t.sg
        FROM times t
        INNER JOIN grupos g ON t.grupo_id = g.id
        WHERE t.tournament_id = ?
        ORDER BY g.nome, t.pts DESC, t.sg DESC, t.gm DESC
    ");
    $stmt->execute([$tournament_id]);
    $classificacao = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar por grupos
    $grupos = [];
    foreach ($classificacao as $time) {
        $grupos[$time['grupo_nome']][] = $time;
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação - <?= isset($tournament) ? htmlspecialchars($tournament['name']) : 'Copa das Panelas' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            color: white;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
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
        
        .groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }
        
        .group-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .group-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: #3498db;
        }
        
        .standings-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .standings-table th {
            background: rgba(0, 0, 0, 0.4);
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .standings-table td {
            padding: 10px 8px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .standings-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .position {
            font-weight: bold;
            color: #f39c12;
        }
        
        .team-name {
            text-align: left !important;
            font-weight: 600;
        }
        
        .points {
            font-weight: bold;
            color: #27ae60;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        @media (max-width: 768px) {
            .groups-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .standings-table th,
            .standings-table td {
                padding: 8px 4px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-trophy"></i> Classificação</h1>
                <?php if (isset($tournament)): ?>
                    <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?> - <?= $tournament['year'] ?></p>
                <?php endif; ?>
            </div>
            <a href="tournament_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <h3><i class="fas fa-exclamation-triangle"></i> Erro</h3>
                <p><?= htmlspecialchars($error_message) ?></p>
                <p><a href="tournament_list.php" style="color: white;">← Voltar para lista de torneios</a></p>
            </div>
        <?php elseif (empty($grupos)): ?>
            <div class="no-data">
                <h3><i class="fas fa-info-circle"></i> Nenhum Time Encontrado</h3>
                <p>Este torneio ainda não possui times cadastrados.</p>
                <p><a href="tournament_dashboard.php?id=<?= $tournament_id ?>" style="color: #3498db;">Ir para o Dashboard do Torneio</a></p>
            </div>
        <?php else: ?>
            <div class="groups-container">
                <?php foreach ($grupos as $grupo_nome => $times): ?>
                    <div class="group-card">
                        <div class="group-title"><?= htmlspecialchars($grupo_nome) ?></div>
                        
                        <table class="standings-table">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th>Time</th>
                                    <th>Pts</th>
                                    <th>V</th>
                                    <th>E</th>
                                    <th>D</th>
                                    <th>GM</th>
                                    <th>GC</th>
                                    <th>SG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($times as $index => $time): ?>
                                    <tr>
                                        <td class="position"><?= $index + 1 ?>º</td>
                                        <td class="team-name"><?= htmlspecialchars($time['time_nome']) ?></td>
                                        <td class="points"><?= $time['pts'] ?></td>
                                        <td><?= $time['vitorias'] ?></td>
                                        <td><?= $time['empates'] ?></td>
                                        <td><?= $time['derrotas'] ?></td>
                                        <td><?= $time['gm'] ?></td>
                                        <td><?= $time['gc'] ?></td>
                                        <td><?= $time['sg'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center; opacity: 0.7;">
            <p>Classificação do Torneio - Copa das Panelas</p>
            <p><small>Pts = Pontos | V = Vitórias | E = Empates | D = Derrotas | GM = Gols Marcados | GC = Gols Contra | SG = Saldo de Gols</small></p>
        </div>
    </div>
</body>
</html>
