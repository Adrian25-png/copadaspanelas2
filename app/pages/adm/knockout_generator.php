<?php
/**
 * Gerador Automático de Eliminatórias
 * Cria automaticamente os confrontos das fases finais
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
            max-width: 1000px;
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
        
        .format-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .format-title {
            color: #f39c12;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .format-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .format-card {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .format-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .format-card.selected {
            border-color: #f39c12;
            background: rgba(243, 156, 18, 0.2);
        }
        
        .format-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .format-number {
            font-size: 2rem;
            font-weight: bold;
            color: #f39c12;
            margin-bottom: 10px;
        }
        
        .format-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .format-description {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .teams-selection {
            display: none;
            margin-top: 25px;
        }
        
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .team-checkbox {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .team-checkbox:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .team-checkbox.selected {
            background: rgba(39, 174, 96, 0.3);
            border-color: #27ae60;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
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
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
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
        
        .selection-counter {
            background: rgba(243, 156, 18, 0.2);
            border: 1px solid #f39c12;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .format-options {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .teams-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-magic"></i> Gerador de Eliminatórias</h1>
            <a href="finals_manager.php?tournament_id=<?= $tournament_id ?>" class="back-link">
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
        
        <!-- Info -->
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Times Disponíveis:</strong> <?= $total_teams ?> times qualificados.
            Selecione o formato de eliminatórias baseado no número de times que você quer incluir.
        </div>
        
        <form method="POST" id="generatorForm">
            <input type="hidden" name="action" value="generate_knockout">
            
            <!-- Seleção de Formato -->
            <div class="format-section">
                <div class="format-title">
                    <i class="fas fa-sitemap"></i> Escolha o Formato das Eliminatórias
                </div>
                
                <div class="format-options">
                    <div class="format-card <?= $total_teams >= 16 ? '' : 'disabled' ?>" onclick="selectFormat(16)">
                        <div class="format-number">16</div>
                        <div class="format-name">Oitavas de Final</div>
                        <div class="format-description">16 times → 8 → 4 → 2 → 1</div>
                    </div>
                    
                    <div class="format-card <?= $total_teams >= 8 ? '' : 'disabled' ?>" onclick="selectFormat(8)">
                        <div class="format-number">8</div>
                        <div class="format-name">Quartas de Final</div>
                        <div class="format-description">8 times → 4 → 2 → 1</div>
                    </div>
                    
                    <div class="format-card <?= $total_teams >= 4 ? '' : 'disabled' ?>" onclick="selectFormat(4)">
                        <div class="format-number">4</div>
                        <div class="format-name">Semifinal</div>
                        <div class="format-description">4 times → 2 → 1</div>
                    </div>
                    
                    <div class="format-card <?= $total_teams >= 2 ? '' : 'disabled' ?>" onclick="selectFormat(2)">
                        <div class="format-number">2</div>
                        <div class="format-name">Final</div>
                        <div class="format-description">2 times → 1</div>
                    </div>
                </div>
                
                <input type="hidden" name="format" id="selectedFormat">
            </div>
            
            <!-- Seleção de Times -->
            <div class="teams-selection" id="teamsSelection">
                <div class="format-title">
                    <i class="fas fa-users"></i> Selecione os Times
                </div>
                
                <div class="selection-counter" id="selectionCounter">
                    Selecionados: <span id="selectedCount">0</span> de <span id="requiredCount">0</span>
                </div>
                
                <div class="teams-grid">
                    <?php foreach ($qualified_teams as $team): ?>
                    <div class="team-checkbox" onclick="toggleTeam(<?= $team['id'] ?>)">
                        <input type="checkbox" name="selected_teams[]" value="<?= $team['id'] ?>" id="team_<?= $team['id'] ?>">
                        <div>
                            <div style="font-weight: bold;"><?= htmlspecialchars($team['nome']) ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.8;">
                                <?= htmlspecialchars($team['grupo_nome']) ?> - <?= $team['posicao'] ?>º lugar (<?= $team['pontos'] ?> pts)
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="selectTopTeams()">
                        <i class="fas fa-star"></i> Selecionar Melhores Times
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                        <i class="fas fa-times"></i> Limpar Seleção
                    </button>
                    <button type="submit" class="btn btn-success" id="generateBtn" disabled>
                        <i class="fas fa-magic"></i> Gerar Eliminatórias
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let selectedFormat = 0;
        let selectedTeams = [];
        
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
            
            // Mostrar seleção de times
            document.getElementById('teamsSelection').style.display = 'block';
            
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
            if (selectedTeams.length === selectedFormat) {
                generateBtn.disabled = false;
            } else {
                generateBtn.disabled = true;
            }
        }
        
        function selectTopTeams() {
            clearSelection();
            
            const checkboxes = document.querySelectorAll('input[name="selected_teams[]"]');
            for (let i = 0; i < Math.min(selectedFormat, checkboxes.length); i++) {
                const teamId = parseInt(checkboxes[i].value);
                checkboxes[i].checked = true;
                checkboxes[i].closest('.team-checkbox').classList.add('selected');
                selectedTeams.push(teamId);
            }
            
            updateSelectionCounter();
        }
        
        function clearSelection() {
            selectedTeams = [];
            document.querySelectorAll('input[name="selected_teams[]"]').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.team-checkbox').classList.remove('selected');
            });
            updateSelectionCounter();
        }
        
        // Submeter formulário diretamente
        document.getElementById('generatorForm').addEventListener('submit', function(e) {
            // Formulário será submetido normalmente
        });
    </script>
</body>
</html>
