<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;

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

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            color: #7B1FA2;
        }

        .page-header p {
            margin: 8px 0 0 0;
            color: #9E9E9E;
            font-size: 1.1rem;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            border: 2px solid #7B1FA2;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(123, 31, 162, 0.3);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: #9E9E9E;
            font-weight: 500;
        }

        .section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
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
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #7B1FA2;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background: #2A2A2A;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(123, 31, 162, 0.2);
        }

        .table th {
            background: #7B1FA2;
            font-weight: 600;
            color: white;
            font-size: 1rem;
        }

        .table td {
            color: #E0E0E0;
        }

        .table tr:hover {
            background: rgba(123, 31, 162, 0.1);
        }

        .group-section {
            margin-bottom: 35px;
        }

        .group-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #E1BEE7;
            margin-bottom: 20px;
            padding: 15px;
            background: #2A2A2A;
            border-left: 4px solid #4CAF50;
            border-radius: 8px;
        }
        
        .match-item {
            background: #2A2A2A;
            border: 1px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 2fr auto 2fr auto auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .match-item:hover {
            border-color: #7B1FA2;
            transform: translateY(-2px);
        }

        .team-name {
            font-weight: 600;
            color: #E1BEE7;
        }

        .match-score {
            font-size: 1.3rem;
            font-weight: 700;
            color: #4CAF50;
            text-align: center;
        }

        .match-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-finalizado { background: #4CAF50; color: white; }
        .status-agendado { background: #FF9800; color: white; }
        .status-em_andamento { background: #2196F3; color: white; }
        .status-cancelado { background: #F44336; color: white; }

        .tournament-info {
            background: #2A2A2A;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 35px;
            position: relative;
            overflow: hidden;
        }

        .tournament-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #64B5F6;
            font-size: 0.9rem;
        }

        .info-value {
            color: #E0E0E0;
            font-weight: 500;
            font-size: 1rem;
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
        
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media print {
            body {
                background: white;
                color: black;
            }

            .main-container {
                background: white;
                box-shadow: none;
            }

            .btn-standard {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-file-alt"></i> Relatório do Torneio</h1>
                <p><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <div>
                <button onclick="window.print()" class="btn-standard btn-success">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <a href="tournament_management.php?tournament_id=<?= $tournament_id ?>" class="btn-standard">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        
        <!-- Informações do Torneio -->
        <div class="tournament-info fade-in">
            <h3 style="margin-bottom: 20px; color: #64B5F6; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle"></i> Informações Gerais
            </h3>
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
                <div style="margin-top: 20px; padding: 15px; background: rgba(100, 181, 246, 0.1); border-radius: 8px;">
                    <strong style="color: #64B5F6;">Descrição:</strong>
                    <span style="color: #E0E0E0;"><?= htmlspecialchars($tournament['description']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="stats-grid fade-in">
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
        <div class="fade-in" style="text-align: center; margin-top: 50px; padding-top: 25px; border-top: 2px solid rgba(123, 31, 162, 0.3); color: #9E9E9E;">
            <p style="margin-bottom: 8px; font-weight: 500;">
                <i class="fas fa-clock" style="margin-right: 8px; color: #7B1FA2;"></i>
                Relatório gerado em <?= date('d/m/Y H:i:s') ?>
            </p>
            <p style="font-weight: 600; color: #E1BEE7;">
                <i class="fas fa-trophy" style="margin-right: 8px; color: #7B1FA2;"></i>
                Sistema Copa das Panelas - Gestão de Torneios
            </p>
        </div>
    </div>

    <script>
        // Animações Copa das Panelas
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Animação especial para cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        card.style.transform = 'scale(1)';
                    }, 200);
                }, 1000 + (index * 100));
            });

            // Animação para seções
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                setTimeout(() => {
                    section.style.borderLeftColor = '#4CAF50';
                    setTimeout(() => {
                        section.style.borderLeftColor = '#7B1FA2';
                    }, 300);
                }, 1500 + (index * 200));
            });
        });
    </script>
</body>
</html>
