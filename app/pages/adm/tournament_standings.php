<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


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

        .tournament-info h1 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .tournament-info h1 i {
            color: #7B1FA2;
        }

        .tournament-year {
            font-size: 1.1rem;
            color: #9E9E9E;
            margin-top: 5px;
        }

        .back-link {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(550px, 1fr));
            gap: 25px;
        }

        .group-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .group-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .group-card:hover {
            transform: translateY(-5px);
            background: #252525;
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .group-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #E1BEE7;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding-top: 5px;
        }

        .group-title i {
            color: #7B1FA2;
        }

        .standings-table {
            width: 100%;
            border-collapse: collapse;
            background: #2A2A2A;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #7B1FA2;
        }

        .standings-table th {
            background: #7B1FA2;
            padding: 15px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            position: relative;
        }

        .standings-table th:first-child {
            text-align: center;
        }

        .standings-table th:nth-child(2) {
            text-align: left;
        }

        .standings-table td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid rgba(123, 31, 162, 0.2);
            color: #E0E0E0;
            font-weight: 500;
        }

        .standings-table td:first-child {
            text-align: center;
        }

        .standings-table td:nth-child(2) {
            text-align: left;
        }

        .standings-table tr:hover {
            background: rgba(123, 31, 162, 0.1);
        }

        .standings-table tbody tr:last-child td {
            border-bottom: none;
        }

        .position {
            font-weight: bold;
            color: #E1BEE7;
            background: #7B1FA2;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .team-name {
            text-align: left !important;
            font-weight: 600;
            color: #E1BEE7;
        }

        .points {
            font-weight: bold;
            color: #4CAF50;
            font-size: 1.1rem;
        }

        .qualified {
            background: rgba(76, 175, 80, 0.1);
            border-left: 3px solid #4CAF50;
        }

        .eliminated {
            background: rgba(244, 67, 54, 0.1);
            border-left: 3px solid #F44336;
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

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-left-color: #F44336;
            color: #EF5350;
        }

        .alert-error::before {
            background: linear-gradient(90deg, #F44336, #EF5350);
        }

        .empty-group {
            text-align: center;
            padding: 60px 20px;
            color: #9E9E9E;
        }

        .empty-group i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #7B1FA2;
            opacity: 0.6;
        }

        .empty-group h3 {
            color: #E1BEE7;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .empty-group p {
            font-size: 1rem;
            line-height: 1.5;
        }

        .legend {
            background: #1E1E1E;
            border-left: 4px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .legend::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .legend-title {
            font-weight: 600;
            color: #64B5F6;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.1rem;
            padding-top: 5px;
        }

        .legend-title i {
            color: #2196F3;
        }

        .legend-item {
            margin-bottom: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #E0E0E0;
        }

        .legend-item .color-indicator {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            display: inline-block;
        }

        .legend-item .qualified-indicator {
            background: #4CAF50;
        }

        .legend-item .eliminated-indicator {
            background: #F44336;
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

            .tournament-info h1 {
                font-size: 1.8rem;
            }

            .groups-container {
                grid-template-columns: 1fr;
            }

            .standings-table {
                font-size: 0.85rem;
            }

            .standings-table th,
            .standings-table td {
                padding: 10px 6px;
            }

            .main-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .standings-table {
                font-size: 0.8rem;
            }

            .standings-table th,
            .standings-table td {
                padding: 8px 4px;
            }

            .position {
                width: 25px;
                height: 25px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
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
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Legenda -->
        <div class="legend fade-in" style="animation-delay: 0.2s;">
            <div class="legend-title">
                <i class="fas fa-info-circle"></i> Legenda
            </div>
            <div class="legend-item">
                <span><strong>Pos:</strong> Posição | <strong>J:</strong> Jogos | <strong>V:</strong> Vitórias | <strong>E:</strong> Empates | <strong>D:</strong> Derrotas</span>
            </div>
            <div class="legend-item">
                <span><strong>GM:</strong> Gols Marcados | <strong>GS:</strong> Gols Sofridos | <strong>SG:</strong> Saldo de Gols | <strong>Pts:</strong> Pontos</span>
            </div>
            <div class="legend-item">
                <span class="color-indicator qualified-indicator"></span>
                <span>Times classificados para próxima fase</span>
            </div>
            <div class="legend-item">
                <span class="color-indicator eliminated-indicator"></span>
                <span>Times eliminados</span>
            </div>
        </div>

        <!-- Classificação por Grupo -->
        <div class="groups-container">
            <?php if (empty($grupos)): ?>
                <div class="empty-group fade-in" style="animation-delay: 0.4s;">
                    <i class="fas fa-info-circle"></i>
                    <h3>Nenhum grupo encontrado</h3>
                    <p>Este torneio ainda não possui grupos configurados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grupos as $index => $grupo): ?>
                    <div class="group-card fade-in" style="animation-delay: <?= ($index + 2) * 0.2 ?>s;">
                        <div class="group-title">
                            <i class="fas fa-layer-group"></i>
                            <?= htmlspecialchars($grupo['grupo_nome']) ?>
                        </div>

                        <?php if (empty($classificacao_por_grupo[$grupo['grupo_id']])): ?>
                            <div class="empty-group">
                                <i class="fas fa-users"></i>
                                <h3>Nenhum time cadastrado</h3>
                                <p>Este grupo ainda não possui times cadastrados.</p>
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

    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover dinâmicos aos group-cards
            const groupCards = document.querySelectorAll('.group-card');
            groupCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                    this.style.boxShadow = '0 15px 35px rgba(123, 31, 162, 0.4)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 25px rgba(123, 31, 162, 0.3)';
                });
            });

            // Adicionar efeitos hover às linhas da tabela
            const tableRows = document.querySelectorAll('.standings-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.transition = 'all 0.2s ease';
                });

                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Animação de contagem para os pontos
            const pointsElements = document.querySelectorAll('.points');
            pointsElements.forEach(element => {
                const finalValue = parseInt(element.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);

                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    element.textContent = currentValue;
                }, 50);
            });
        });
    </script>
</body>
</html>
