<?php
/**
 * Classificação do Torneio
 * Mostra a classificação de times por grupo para um torneio específico
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['id'] ?? null;

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

// Obter grupos e times com classificação
try {
    $stmt = $pdo->prepare("
        SELECT g.id as grupo_id, g.nome as grupo_nome
        FROM grupos g
        WHERE g.tournament_id = ?
        ORDER BY g.nome
    ");
    $stmt->execute([$tournament_id]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $classificacao_por_grupo = [];
    foreach ($grupos as $grupo) {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   COALESCE(t.pts, 0) as pontos,
                   COALESCE(t.vitorias, 0) as vitorias,
                   COALESCE(t.empates, 0) as empates,
                   COALESCE(t.derrotas, 0) as derrotas,
                   COALESCE(t.gm, 0) as gols_marcados,
                   COALESCE(t.gc, 0) as gols_sofridos,
                   COALESCE(t.sg, 0) as saldo_gols,
                   (COALESCE(t.vitorias, 0) + COALESCE(t.empates, 0) + COALESCE(t.derrotas, 0)) as jogos
            FROM times t
            WHERE t.grupo_id = ? AND t.tournament_id = ?
            ORDER BY t.pts DESC, t.sg DESC, t.gm DESC, t.nome ASC
        ");
        $stmt->execute([$grupo['grupo_id'], $tournament_id]);
        $classificacao_por_grupo[$grupo['grupo_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar classificação: " . $e->getMessage();
    $grupos = [];
    $classificacao_por_grupo = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação - <?= htmlspecialchars($tournament['name']) ?></title>
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
        
        .groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }
        
        .group-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .group-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #f39c12;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
        
        .qualified {
            background: rgba(39, 174, 96, 0.2);
        }
        
        .eliminated {
            background: rgba(231, 76, 60, 0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .empty-group {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
            font-style: italic;
        }
        
        .legend {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .legend-title {
            font-weight: bold;
            color: #3498db;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-item {
            margin-bottom: 8px;
            font-size: 0.9rem;
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
            
            .groups-container {
                grid-template-columns: 1fr;
            }
            
            .standings-table {
                font-size: 0.8rem;
            }
            
            .standings-table th,
            .standings-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="tournament-info">
                <h1><i class="fas fa-trophy"></i> Classificação</h1>
                <div class="tournament-year"><?= htmlspecialchars($tournament['name']) ?> - <?= $tournament['year'] ?></div>
            </div>
            <a href="match_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar aos Jogos
            </a>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Legenda -->
        <div class="legend">
            <div class="legend-title">
                <i class="fas fa-info-circle"></i> Legenda
            </div>
            <div class="legend-item"><strong>Pos:</strong> Posição | <strong>J:</strong> Jogos | <strong>V:</strong> Vitórias | <strong>E:</strong> Empates | <strong>D:</strong> Derrotas</div>
            <div class="legend-item"><strong>GM:</strong> Gols Marcados | <strong>GS:</strong> Gols Sofridos | <strong>SG:</strong> Saldo de Gols | <strong>Pts:</strong> Pontos</div>
        </div>
        
        <!-- Classificação por Grupo -->
        <div class="groups-container">
            <?php if (empty($grupos)): ?>
                <div class="empty-group">
                    <i class="fas fa-info-circle"></i>
                    <p>Nenhum grupo encontrado para este torneio.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grupos as $grupo): ?>
                    <div class="group-card">
                        <div class="group-title">
                            <i class="fas fa-layer-group"></i>
                            <?= htmlspecialchars($grupo['grupo_nome']) ?>
                        </div>
                        
                        <?php if (empty($classificacao_por_grupo[$grupo['grupo_id']])): ?>
                            <div class="empty-group">
                                <p>Nenhum time cadastrado neste grupo</p>
                            </div>
                        <?php else: ?>
                            <table class="standings-table">
                                <thead>
                                    <tr>
                                        <th>Pos</th>
                                        <th>Time</th>
                                        <th>J</th>
                                        <th>V</th>
                                        <th>E</th>
                                        <th>D</th>
                                        <th>GM</th>
                                        <th>GS</th>
                                        <th>SG</th>
                                        <th>Pts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $posicao = 1;
                                    foreach ($classificacao_por_grupo[$grupo['grupo_id']] as $time): 
                                        $classe_linha = '';
                                        if ($posicao <= 2) {
                                            $classe_linha = 'qualified';
                                        }
                                    ?>
                                        <tr class="<?= $classe_linha ?>">
                                            <td class="position"><?= $posicao ?>º</td>
                                            <td class="team-name"><?= htmlspecialchars($time['nome']) ?></td>
                                            <td><?= $time['jogos'] ?></td>
                                            <td><?= $time['vitorias'] ?></td>
                                            <td><?= $time['empates'] ?></td>
                                            <td><?= $time['derrotas'] ?></td>
                                            <td><?= $time['gols_marcados'] ?></td>
                                            <td><?= $time['gols_sofridos'] ?></td>
                                            <td><?= $time['saldo_gols'] >= 0 ? '+' : '' ?><?= $time['saldo_gols'] ?></td>
                                            <td class="points"><?= $time['pontos'] ?></td>
                                        </tr>
                                    <?php 
                                        $posicao++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
