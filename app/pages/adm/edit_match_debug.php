<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


// Debug version to identify the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Edit Match</h1>";

session_start();
echo "<p>Session started successfully</p>";

try {
    require_once '../../config/conexao.php';
    echo "<p>✅ Conexão incluída</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    exit;
}

try {
    require_once '../../classes/TournamentManager.php';
    echo "<p>✅ TournamentManager incluído</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro no TournamentManager: " . $e->getMessage() . "</p>";
    exit;
}

try {
    require_once '../../classes/MatchManager.php';
    echo "<p>✅ MatchManager incluído</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro no MatchManager: " . $e->getMessage() . "</p>";
    exit;
}

try {
    $pdo = conectar();
    echo "<p>✅ PDO conectado</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro no PDO: " . $e->getMessage() . "</p>";
    exit;
}

$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;
$match_id = $_GET['match_id'] ?? null;

echo "<p>Tournament ID: " . ($tournament_id ?? 'NULL') . "</p>";
echo "<p>Match ID: " . ($match_id ?? 'NULL') . "</p>";

if (!$tournament_id || !$match_id) {
    echo "<p>❌ Parâmetros inválidos</p>";
    exit;
}

try {
    $tournamentManager = new TournamentManager($pdo);
    echo "<p>✅ TournamentManager criado</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro ao criar TournamentManager: " . $e->getMessage() . "</p>";
    exit;
}

try {
    $tournament = $tournamentManager->getTournamentById($tournament_id);
    if ($tournament) {
        echo "<p>✅ Torneio encontrado: " . htmlspecialchars($tournament['name']) . "</p>";
    } else {
        echo "<p>❌ Torneio não encontrado</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Erro ao buscar torneio: " . $e->getMessage() . "</p>";
    exit;
}

try {
    $matchManager = new MatchManager($pdo, $tournament_id);
    echo "<p>✅ MatchManager criado</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro ao criar MatchManager: " . $e->getMessage() . "</p>";
    exit;
}

try {
    $match = $matchManager->getMatchById($match_id);
    if ($match) {
        echo "<p>✅ Jogo encontrado: " . htmlspecialchars($match['team1_name']) . " vs " . htmlspecialchars($match['team2_name']) . "</p>";
    } else {
        echo "<p>❌ Jogo não encontrado</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Erro ao buscar jogo: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>POST Data:</h2>";
if ($_POST) {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_match') {
        echo "<p>Processando atualização do jogo...</p>";
        
        try {
            $team1_goals = $_POST['team1_goals'] !== '' ? (int)$_POST['team1_goals'] : null;
            $team2_goals = $_POST['team2_goals'] !== '' ? (int)$_POST['team2_goals'] : null;
            $match_date = $_POST['match_date'] ?: null;
            $match_time = $_POST['match_time'] ?: null;
            $status = $_POST['status'] ?: 'agendado';
            
            echo "<p>Dados processados:</p>";
            echo "<ul>";
            echo "<li>Team1 Goals: " . ($team1_goals ?? 'NULL') . "</li>";
            echo "<li>Team2 Goals: " . ($team2_goals ?? 'NULL') . "</li>";
            echo "<li>Match Date: " . ($match_date ?? 'NULL') . "</li>";
            echo "<li>Match Time: " . ($match_time ?? 'NULL') . "</li>";
            echo "<li>Status: " . $status . "</li>";
            echo "</ul>";
            
            // Combinar data e hora se ambos fornecidos
            $full_datetime = null;
            if ($match_date) {
                $full_datetime = $match_date;
                if ($match_time) {
                    $full_datetime .= ' ' . $match_time;
                } else {
                    $full_datetime .= ' 00:00:00';
                }
            }
            
            echo "<p>Full DateTime: " . ($full_datetime ?? 'NULL') . "</p>";
            
            // Atualizar dados básicos do jogo
            $stmt = $pdo->prepare("
                UPDATE matches 
                SET team1_goals = ?, team2_goals = ?, status = ?, 
                    match_date = ?, updated_at = NOW()
                WHERE id = ? AND tournament_id = ?
            ");
            
            $result = $stmt->execute([$team1_goals, $team2_goals, $status, $full_datetime, $match_id, $tournament_id]);
            
            if ($result) {
                echo "<p>✅ Jogo atualizado com sucesso!</p>";
                echo "<p>Linhas afetadas: " . $stmt->rowCount() . "</p>";
            } else {
                echo "<p>❌ Falha ao atualizar jogo</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Erro durante atualização: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p>Nenhum dado POST recebido</p>";
}

echo "<h2>Teste de Formulário:</h2>";
?>

<form method="POST">
    <input type="hidden" name="action" value="update_match">
    
    <p>
        <label>Team1 Goals:</label>
        <input type="number" name="team1_goals" value="<?= $match['team1_goals'] ?? '' ?>">
    </p>
    
    <p>
        <label>Team2 Goals:</label>
        <input type="number" name="team2_goals" value="<?= $match['team2_goals'] ?? '' ?>">
    </p>
    
    <p>
        <label>Status:</label>
        <select name="status">
            <option value="agendado" <?= $match['status'] === 'agendado' ? 'selected' : '' ?>>Agendado</option>
            <option value="finalizado" <?= $match['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
        </select>
    </p>
    
    <p>
        <label>Data:</label>
        <input type="date" name="match_date" value="<?= $match['match_date'] ? date('Y-m-d', strtotime($match['match_date'])) : '' ?>">
    </p>
    
    <p>
        <label>Hora:</label>
        <input type="time" name="match_time" value="<?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : '' ?>">
    </p>
    
    <p>
        <button type="submit">Salvar Teste</button>
    </p>
</form>

<p><a href="edit_match.php?tournament_id=<?= $tournament_id ?>&match_id=<?= $match_id ?>">Voltar para página original</a></p>
