<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

$tournament_id = $_GET['id'] ?? null;

if (!$tournament_id) {
    $_SESSION['error'] = "ID do torneio não especificado";
    header('Location: tournament_list.php');
    exit;
}

// Obter dados do torneio
try {
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->execute([$tournament_id]);
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tournament) {
        $_SESSION['error'] = "Torneio não encontrado";
        header('Location: tournament_list.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Erro ao carregar torneio: " . $e->getMessage();
    header('Location: tournament_list.php');
    exit;
}

// Obter estatísticas do torneio
$stats = [
    'total_teams' => 0,
    'total_groups' => 0,
    'total_matches' => 0,
    'finished_matches' => 0,
    'scheduled_matches' => 0,
    'total_goals' => 0
];

try {
    // Contar times
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $stats['total_teams'] = $stmt->fetchColumn();

    // Contar grupos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $stats['total_groups'] = $stmt->fetchColumn();

    // Contar jogos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $stats['total_matches'] = $stmt->fetchColumn();

    // Contar jogos finalizados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND status = 'finalizado'");
    $stmt->execute([$tournament_id]);
    $stats['finished_matches'] = $stmt->fetchColumn();

    // Contar jogos agendados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND status = 'agendado'");
    $stmt->execute([$tournament_id]);
    $stats['scheduled_matches'] = $stmt->fetchColumn();

    // Somar gols
    $stmt = $pdo->prepare("
        SELECT SUM(COALESCE(team1_goals, 0) + COALESCE(team2_goals, 0))
        FROM matches
        WHERE tournament_id = ? AND status = 'finalizado'
    ");
    $stmt->execute([$tournament_id]);
    $stats['total_goals'] = $stmt->fetchColumn() ?: 0;

} catch (Exception $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter times e classificação
$teams_by_group = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, g.nome as grupo_nome,
               COALESCE(t.pts, 0) as pontos,
               (COALESCE(t.vitorias, 0) + COALESCE(t.empates, 0) + COALESCE(t.derrotas, 0)) as jogos,
               COALESCE(t.vitorias, 0) as vitorias,
               COALESCE(t.empates, 0) as empates,
               COALESCE(t.derrotas, 0) as derrotas,
               COALESCE(t.gm, 0) as gols_pro,
               COALESCE(t.gc, 0) as gols_contra,
               COALESCE(t.sg, 0) as saldo_gols
        FROM times t
        LEFT JOIN grupos g ON t.grupo_id = g.id
        WHERE t.tournament_id = ?
        ORDER BY g.nome, t.pts DESC, t.sg DESC, t.gm DESC
    ");
    $stmt->execute([$tournament_id]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar times por grupo
    foreach ($teams as $team) {
        $group_name = $team['grupo_nome'] ?: 'Sem Grupo';
        $teams_by_group[$group_name][] = $team;
    }
} catch (Exception $e) {
    error_log("Erro ao obter times: " . $e->getMessage());
}

// Agrupar times por grupo
$teams_by_group = [];
foreach ($teams as $team) {
    $group_name = $team['grupo_nome'] ?: 'Sem Grupo';
    $teams_by_group[$group_name][] = $team;
}

// Obter jogos
$stmt = $pdo->prepare("
    SELECT m.*, 
           t1.nome as team1_name, 
           t2.nome as team2_name,
           g.nome as group_name
    FROM matches m
    LEFT JOIN times t1 ON m.team1_id = t1.id
    LEFT JOIN times t2 ON m.team2_id = t2.id
    LEFT JOIN grupos g ON m.group_id = g.id
    WHERE m.tournament_id = ?
    ORDER BY m.match_date DESC, m.created_at DESC
");
$stmt->execute([$tournament_id]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar jogos por status
$matches_by_status = [
    'finalizado' => [],
    'agendado' => [],
    'em_andamento' => [],
    'cancelado' => []
];

foreach ($matches as $match) {
    $matches_by_status[$match['status']][] = $match;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório - <?= htmlspecialchars($tournament['name']) ?></title>
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
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
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
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: bold;
            color: #f39c12;
        }
        
        .table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .group-section {
            margin-bottom: 30px;
        }
        
        .group-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(52, 152, 219, 0.2);
            border-radius: 8px;
        }
        
        .match-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 2fr auto 2fr auto auto;
            gap: 15px;
            align-items: center;
        }
        
        .team-name {
            font-weight: bold;
        }
        
        .match-score {
            font-size: 1.2rem;
            font-weight: bold;
            color: #3498db;
            text-align: center;
        }
        
        .match-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-finalizado { background: #27ae60; }
        .status-agendado { background: #f39c12; }
        .status-em_andamento { background: #3498db; }
        .status-cancelado { background: #e74c3c; }
        
        .tournament-info {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        
        .info-label {
            font-weight: 600;
        }
        
        .info-value {
            color: #3498db;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .match-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 10px;
            }
            
            .table {
                font-size: 0.9rem;
            }
        }
        
        @media print {
            body {
                background: white;
                color: black;
            }
            
            .container {
                background: white;
                box-shadow: none;
            }
            
            .btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-file-alt"></i> Relatório do Torneio</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-success">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        
        <!-- Informações do Torneio -->
        <div class="tournament-info">
            <h3 style="margin-bottom: 15px; color: #3498db;">📋 Informações Gerais</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nome:</span>
                    <span class="info-value"><?= htmlspecialchars($tournament['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ano:</span>
                    <span class="info-value"><?= $tournament['year'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?= ucfirst($tournament['status']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Criado em:</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($tournament['created_at'])) ?></span>
                </div>
            </div>
            
            <?php if ($tournament['description']): ?>
                <div style="margin-top: 15px;">
                    <strong>Descrição:</strong> <?= htmlspecialchars($tournament['description']) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Estatísticas Gerais -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_teams'] ?? 0 ?></div>
                <div class="stat-label">Times Participantes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_groups'] ?? 0 ?></div>
                <div class="stat-label">Grupos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_matches'] ?? 0 ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['finished_matches'] ?? 0 ?></div>
                <div class="stat-label">Jogos Finalizados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_goals'] ?? 0 ?></div>
                <div class="stat-label">Gols Marcados</div>
            </div>
        </div>
        
        <!-- Classificação por Grupos -->
        <?php if (!empty($teams_by_group)): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fas fa-table"></i>
                    Classificação
                </h3>
                
                <?php foreach ($teams_by_group as $group_name => $group_teams): ?>
                    <div class="group-section">
                        <div class="group-title"><?= htmlspecialchars($group_name) ?></div>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th>Time</th>
                                    <th>Pts</th>
                                    <th>J</th>
                                    <th>V</th>
                                    <th>E</th>
                                    <th>D</th>
                                    <th>GP</th>
                                    <th>GC</th>
                                    <th>SG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group_teams as $index => $team): ?>
                                    <tr>
                                        <td><?= $index + 1 ?>º</td>
                                        <td class="team-name"><?= htmlspecialchars($team['nome']) ?></td>
                                        <td><?= $team['pontos'] ?></td>
                                        <td><?= $team['jogos'] ?></td>
                                        <td><?= $team['vitorias'] ?></td>
                                        <td><?= $team['empates'] ?></td>
                                        <td><?= $team['derrotas'] ?></td>
                                        <td><?= $team['gols_pro'] ?></td>
                                        <td><?= $team['gols_contra'] ?></td>
                                        <td><?= $team['saldo_gols'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Jogos Finalizados -->
        <?php if (!empty($matches_by_status['finalizado'])): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fas fa-check-circle"></i>
                    Jogos Finalizados (<?= count($matches_by_status['finalizado']) ?>)
                </h3>
                
                <?php foreach ($matches_by_status['finalizado'] as $match): ?>
                    <div class="match-item">
                        <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                        <div class="match-score"><?= $match['team1_goals'] ?> x <?= $match['team2_goals'] ?></div>
                        <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                        <div><?= $match['group_name'] ? htmlspecialchars($match['group_name']) : ucfirst($match['phase']) ?></div>
                        <div><?= $match['match_date'] ? date('d/m/Y', strtotime($match['match_date'])) : '' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Jogos Agendados -->
        <?php if (!empty($matches_by_status['agendado'])): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fas fa-calendar"></i>
                    Próximos Jogos (<?= count($matches_by_status['agendado']) ?>)
                </h3>
                
                <?php foreach ($matches_by_status['agendado'] as $match): ?>
                    <div class="match-item">
                        <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                        <div style="color: #f39c12; font-weight: bold;">VS</div>
                        <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                        <div><?= $match['group_name'] ? htmlspecialchars($match['group_name']) : ucfirst($match['phase']) ?></div>
                        <div><?= $match['match_date'] ? date('d/m/Y H:i', strtotime($match['match_date'])) : 'A definir' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Rodapé do Relatório -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2); opacity: 0.7;">
            <p>Relatório gerado em <?= date('d/m/Y H:i:s') ?></p>
            <p>Sistema Copa das Panelas - Gestão de Torneios</p>
        </div>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section, .stat-card');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
