<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


/**
 * Gerador Automático de Eliminatórias
 * Cria automaticamente os confrontos das fases finais
 */

session_start();

// Verificar autenticação
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login_simple.php');
    exit;
}

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

// Processar geração automática
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'generate_knockout') {
    try {
        $format = $_POST['format']; // 16, 8, 4, 2
        $teams = $_POST['selected_teams'] ?? [];
        
        if (count($teams) != $format) {
            throw new Exception("Número de times selecionados não confere com o formato escolhido");
        }
        
        // Limpar jogos existentes das fases finais
        $phases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
        foreach ($phases as $phase) {
            $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
            $stmt->execute([$tournament_id, $phase]);
        }

        // Embaralhar times para sorteio
        shuffle($teams);

        // Gerar confrontos baseado no formato
        switch ($format) {
            case 16:
                // Oitavas de Final (16 -> 8)
                for ($i = 0; $i < 16; $i += 2) {
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                        VALUES (?, ?, ?, 'Oitavas', 'agendado', NOW())
                    ");
                    $stmt->execute([$tournament_id, $teams[$i], $teams[$i + 1]]);
                }
                break;

            case 8:
                // Quartas de Final (8 -> 4)
                for ($i = 0; $i < 8; $i += 2) {
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                        VALUES (?, ?, ?, 'Quartas', 'agendado', NOW())
                    ");
                    $stmt->execute([$tournament_id, $teams[$i], $teams[$i + 1]]);
                }
                break;

            case 4:
                // Semifinal (4 -> 2)
                for ($i = 0; $i < 4; $i += 2) {
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                        VALUES (?, ?, ?, 'Semifinal', 'agendado', NOW())
                    ");
                    $stmt->execute([$tournament_id, $teams[$i], $teams[$i + 1]]);
                }
                break;

            case 2:
                // Final (2 -> 1)
                $stmt = $pdo->prepare("
                    INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                    VALUES (?, ?, ?, 'Final', 'agendado', NOW())
                ");
                $stmt->execute([$tournament_id, $teams[0], $teams[1]]);
                break;
        }
        
        $_SESSION['success'] = "Eliminatórias geradas com sucesso!";
        header("Location: finals_manager.php?tournament_id=$tournament_id");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao gerar eliminatórias: " . $e->getMessage();
    }
}

// Buscar times disponíveis
try {
    // Primeiro tentar buscar times com estatísticas (classificados)
    $stmt = $pdo->prepare("
        SELECT t.id, t.nome,
               COALESCE(g.nome, 'Sem Grupo') as grupo_nome,
               COALESCE(ts.pontos, 0) as pontos,
               COALESCE(ts.posicao_grupo, 1) as posicao
        FROM times t
        LEFT JOIN grupos g ON t.grupo_id = g.id
        LEFT JOIN team_statistics ts ON t.id = ts.team_id
        WHERE t.tournament_id = ? AND (ts.posicao_grupo <= 2 OR ts.posicao_grupo IS NULL)
        ORDER BY COALESCE(ts.posicao_grupo, 1), COALESCE(ts.pontos, 0) DESC
    ");
    $stmt->execute([$tournament_id]);
    $qualified_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Se não há times com estatísticas, pegar todos os times do torneio
    if (empty($qualified_teams)) {
        $stmt = $pdo->prepare("
            SELECT t.id, t.nome,
                   COALESCE(g.nome, 'Sem Grupo') as grupo_nome,
                   0 as pontos,
                   1 as posicao
            FROM times t
            LEFT JOIN grupos g ON t.grupo_id = g.id
            WHERE t.tournament_id = ?
            ORDER BY t.nome
        ");
        $stmt->execute([$tournament_id]);
        $qualified_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    // Em caso de erro, tentar busca mais simples
    try {
        $stmt = $pdo->prepare("SELECT id, nome, 'Time' as grupo_nome, 0 as pontos, 1 as posicao FROM times WHERE tournament_id = ? ORDER BY nome");
        $stmt->execute([$tournament_id]);
        $qualified_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $qualified_teams = [];
    }
}

$total_teams = count($qualified_teams);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Eliminatórias - <?= htmlspecialchars($tournament['name']) ?></title>
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
            max-width: 1100px;
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

        .format-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .format-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .format-title {
            color: #E1BEE7;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .format-title i {
            color: #7B1FA2;
        }

        .format-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .format-card {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .format-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #7B1FA2;
            transition: all 0.3s ease;
        }

        .format-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(123, 31, 162, 0.3);
            border-color: #7B1FA2;
        }

        .format-card:hover::before {
            width: 100%;
            opacity: 0.1;
        }

        .format-card.selected {
            border-color: #E1BEE7;
            background: rgba(225, 190, 231, 0.1);
        }

        .format-card.selected::before {
            background: #E1BEE7;
        }

        .format-card.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .format-card.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: transparent;
        }

        .format-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 15px;
        }

        .format-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #E1BEE7;
            font-size: 1.1rem;
        }

        .format-description {
            font-size: 0.95rem;
            color: #9E9E9E;
            line-height: 1.4;
        }

        .teams-selection {
            display: none;
            margin-top: 30px;
            background: #1E1E1E;
            border-left: 4px solid #4CAF50;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .teams-selection::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4CAF50, #81C784);
        }

        .teams-selection h3 {
            color: #4CAF50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .team-checkbox {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .team-checkbox::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #4CAF50;
            transform: scaleY(0);
            transition: all 0.3s ease;
        }

        .team-checkbox:hover {
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
        }

        .team-checkbox:hover::before {
            transform: scaleY(1);
        }

        .team-checkbox.selected {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4CAF50;
        }

        .team-checkbox.selected::before {
            transform: scaleY(1);
        }

        .team-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4CAF50;
        }

        .team-checkbox label {
            color: #E1BEE7;
            font-weight: 500;
            cursor: pointer;
            flex: 1;
        }

        .btn-success {
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .btn-success:hover {
            background: #4CAF50;
            color: white;
        }

        .btn-warning {
            border-color: #FF9800;
            color: #FF9800;
        }

        .btn-warning:hover {
            background: #FF9800;
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

        .btn-standard:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-standard:disabled:hover {
            background: #1E1E1E;
            color: #E1BEE7;
            transform: none;
            box-shadow: none;
        }
            transform: none;
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

        .info-box {
            background: #1E1E1E;
            border-left: 4px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            color: #64B5F6;
            position: relative;
            overflow: hidden;
        }

        .info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .selection-counter {
            background: #1E1E1E;
            border: 2px solid #FF9800;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            color: #FFB74D;
            position: relative;
            overflow: hidden;
        }

        .selection-counter::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF9800, #FFB74D);
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

        .slide-in {
            opacity: 0;
            transform: translateX(-30px);
            transition: all 0.5s ease;
        }

        .slide-in.visible {
            opacity: 1;
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .format-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .teams-grid {
                grid-template-columns: 1fr;
            }

            .format-card {
                padding: 20px 15px;
            }

            .format-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <h1><i class="fas fa-magic"></i> Gerador de Eliminatórias</h1>
            <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="btn-standard">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Mensagens -->
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

        <!-- Info -->
        <div class="info-box fade-in" style="animation-delay: 0.3s;">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Times Disponíveis:</strong> <?= $total_teams ?> times qualificados.<br>
                Selecione o formato de eliminatórias baseado no número de times que você quer incluir.
            </div>
        </div>

        <form method="POST" id="generatorForm">
            <input type="hidden" name="action" value="generate_knockout">

            <!-- Seleção de Formato -->
            <div class="format-section fade-in" style="animation-delay: 0.4s;">
                <div class="format-title">
                    <i class="fas fa-sitemap"></i> Escolha o Formato das Eliminatórias
                </div>

                <div class="format-options">
                    <div class="format-card slide-in <?= $total_teams >= 16 ? '' : 'disabled' ?>" onclick="selectFormat(16)" style="animation-delay: 0.5s;">
                        <div class="format-number">16</div>
                        <div class="format-name">Oitavas de Final</div>
                        <div class="format-description">16 times → 8 → 4 → 2 → 1</div>
                    </div>

                    <div class="format-card slide-in <?= $total_teams >= 8 ? '' : 'disabled' ?>" onclick="selectFormat(8)" style="animation-delay: 0.6s;">
                        <div class="format-number">8</div>
                        <div class="format-name">Quartas de Final</div>
                        <div class="format-description">8 times → 4 → 2 → 1</div>
                    </div>

                    <div class="format-card slide-in <?= $total_teams >= 4 ? '' : 'disabled' ?>" onclick="selectFormat(4)" style="animation-delay: 0.7s;">
                        <div class="format-number">4</div>
                        <div class="format-name">Semifinal</div>
                        <div class="format-description">4 times → 2 → 1</div>
                    </div>

                    <div class="format-card slide-in <?= $total_teams >= 2 ? '' : 'disabled' ?>" onclick="selectFormat(2)" style="animation-delay: 0.8s;">
                        <div class="format-number">2</div>
                        <div class="format-name">Final</div>
                        <div class="format-description">2 times → 1</div>
                    </div>
                </div>

                <input type="hidden" name="format" id="selectedFormat">
            </div>

            <!-- Seleção de Times -->
            <div class="teams-selection" id="teamsSelection">
                <h3><i class="fas fa-users"></i> Selecione os Times</h3>

                <div class="selection-counter" id="selectionCounter">
                    <i class="fas fa-check-circle"></i>
                    Selecionados: <span id="selectedCount">0</span> de <span id="requiredCount">0</span>
                </div>

                <div class="teams-grid">
                    <?php foreach ($qualified_teams as $team): ?>
                    <div class="team-checkbox" onclick="toggleTeam(<?= $team['id'] ?>)">
                        <input type="checkbox" name="selected_teams[]" value="<?= $team['id'] ?>" id="team_<?= $team['id'] ?>">
                        <label for="team_<?= $team['id'] ?>">
                            <div style="font-weight: 600; margin-bottom: 4px;"><?= htmlspecialchars($team['nome']) ?></div>
                            <div style="font-size: 0.85rem; color: #9E9E9E; margin-top: 4px;">
                                <?= htmlspecialchars($team['grupo_nome']) ?> - <?= $team['posicao'] ?>º lugar (<?= $team['pontos'] ?> pts)
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin-top: 30px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <button type="button" class="btn-standard btn-warning" onclick="selectTopTeams()">
                        <i class="fas fa-star"></i> Selecionar Melhores Times
                    </button>
                    <button type="button" class="btn-standard" onclick="clearSelection()">
                        <i class="fas fa-times"></i> Limpar Seleção
                    </button>
                    <button type="submit" class="btn-standard btn-success" id="generateBtn" disabled>
                        <i class="fas fa-magic"></i> Gerar Eliminatórias
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let selectedFormat = 0;
        let selectedTeams = [];

        // Animações na inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Aplicar slide-in aos cards de formato
            const slideElements = document.querySelectorAll('.slide-in');
            slideElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, 500 + (index * 100));
            });
        });

        function selectFormat(format) {
            // Verificar se o formato é válido
            const totalTeams = <?= $total_teams ?>;
            if (totalTeams < format) return;

            // Remover seleção anterior
            document.querySelectorAll('.format-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Selecionar novo formato
            event.target.closest('.format-card').classList.add('selected');
            selectedFormat = format;
            document.getElementById('selectedFormat').value = format;
            document.getElementById('requiredCount').textContent = format;

            // Mostrar seleção de times com animação
            const teamsSelection = document.getElementById('teamsSelection');
            teamsSelection.style.display = 'block';
            teamsSelection.style.opacity = '0';
            teamsSelection.style.transform = 'translateY(30px)';

            setTimeout(() => {
                teamsSelection.style.transition = 'all 0.6s ease';
                teamsSelection.style.opacity = '1';
                teamsSelection.style.transform = 'translateY(0)';
            }, 100);

            // Limpar seleção anterior
            clearSelection();
        }

        function toggleTeam(teamId) {
            const checkbox = document.getElementById('team_' + teamId);
            const teamCard = checkbox.closest('.team-checkbox');

            if (checkbox.checked) {
                // Desmarcar
                checkbox.checked = false;
                teamCard.classList.remove('selected');
                selectedTeams = selectedTeams.filter(id => id !== teamId);
            } else {
                // Verificar limite
                if (selectedTeams.length >= selectedFormat) {
                    alert(`Você só pode selecionar ${selectedFormat} times para este formato.`);
                    return;
                }

                // Marcar
                checkbox.checked = true;
                teamCard.classList.add('selected');
                selectedTeams.push(teamId);
            }

            updateSelectionCounter();
        }

        function updateSelectionCounter() {
            document.getElementById('selectedCount').textContent = selectedTeams.length;

            const generateBtn = document.getElementById('generateBtn');
            const counter = document.getElementById('selectionCounter');

            if (selectedTeams.length === selectedFormat) {
                generateBtn.disabled = false;
                generateBtn.style.opacity = '1';
                generateBtn.style.transform = 'scale(1.05)';
                counter.style.borderColor = '#4CAF50';
                counter.style.color = '#4CAF50';
            } else {
                generateBtn.disabled = true;
                generateBtn.style.opacity = '0.6';
                generateBtn.style.transform = 'scale(1)';
                counter.style.borderColor = '#FF9800';
                counter.style.color = '#FFB74D';
            }
        }

        function selectTopTeams() {
            clearSelection();

            const checkboxes = document.querySelectorAll('input[name="selected_teams[]"]');
            for (let i = 0; i < Math.min(selectedFormat, checkboxes.length); i++) {
                const teamId = parseInt(checkboxes[i].value);
                checkboxes[i].checked = true;

                // Animação sequencial
                setTimeout(() => {
                    checkboxes[i].closest('.team-checkbox').classList.add('selected');
                }, i * 100);

                selectedTeams.push(teamId);
            }

            setTimeout(() => {
                updateSelectionCounter();
            }, checkboxes.length * 100);
        }

        function clearSelection() {
            selectedTeams = [];
            document.querySelectorAll('input[name="selected_teams[]"]').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.team-checkbox').classList.remove('selected');
            });
            updateSelectionCounter();
        }

        // Submeter formulário com confirmação
        document.getElementById('generatorForm').addEventListener('submit', function(e) {
            if (selectedTeams.length !== selectedFormat) {
                e.preventDefault();
                alert('Selecione exatamente ' + selectedFormat + ' times antes de gerar as eliminatórias.');
                return false;
            }

            const confirmed = confirm(`Confirma a geração das eliminatórias com ${selectedFormat} times?\n\nISTO IRÁ REMOVER TODOS OS JOGOS EXISTENTES DAS FASES FINAIS!`);
            if (!confirmed) {
                e.preventDefault();
                return false;
            }

            // Mostrar loading no botão
            const btn = document.getElementById('generateBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
