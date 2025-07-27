<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id) {
    header('Location: tournament_list.php');
    exit;
}

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

try {
    // Get tournament data
    $tournament = $tournamentManager->getTournamentById($tournament_id);
    if (!$tournament) {
        throw new Exception('Tournament not found');
    }
    
    // Get groups
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE tournament_id = ? ORDER BY nome");
    $stmt->execute([$tournament_id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get teams with stats
    $stmt = $pdo->prepare("
        SELECT t.*, g.nome as group_name,
               COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND t.id = j.timeA_id THEN j.gols_marcados_timeA 
                                WHEN j.resultado_timeB IS NOT NULL AND t.id = j.timeB_id THEN j.gols_marcados_timeB 
                                ELSE 0 END),0) AS gm,
               COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND t.id = j.timeA_id THEN j.gols_marcados_timeB 
                                WHEN j.resultado_timeB IS NOT NULL AND t.id = j.timeB_id THEN j.gols_marcados_timeA 
                                ELSE 0 END),0) AS gc,
               COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND ((t.id = j.timeA_id AND j.gols_marcados_timeA > j.gols_marcados_timeB) OR (t.id = j.timeB_id AND j.gols_marcados_timeB > j.gols_marcados_timeA)) THEN 1 ELSE 0 END),0) AS vitorias,
               COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND j.gols_marcados_timeA = j.gols_marcados_timeB THEN 1 ELSE 0 END),0) AS empates,
               COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND ((t.id = j.timeA_id AND j.gols_marcados_timeA < j.gols_marcados_timeB) OR (t.id = j.timeB_id AND j.gols_marcados_timeB < j.gols_marcados_timeA)) THEN 1 ELSE 0 END),0) AS derrotas,
               (COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND ((t.id = j.timeA_id AND j.gols_marcados_timeA > j.gols_marcados_timeB) OR (t.id = j.timeB_id AND j.gols_marcados_timeB > j.gols_marcados_timeA)) THEN 1 ELSE 0 END),0) * 3 + 
                COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND j.gols_marcados_timeA = j.gols_marcados_timeB THEN 1 ELSE 0 END),0)) AS pts
        FROM times t
        INNER JOIN grupos g ON t.grupo_id = g.id
        LEFT JOIN jogos_fase_grupos j ON (t.id = j.timeA_id OR t.id = j.timeB_id) AND t.grupo_id = j.grupo_id
        WHERE t.tournament_id = ?
        GROUP BY t.id
        ORDER BY g.nome, pts DESC, (gm - gc) DESC, gm DESC
    ");
    $stmt->execute([$tournament_id]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get matches
    $stmt = $pdo->prepare("
        SELECT jfg.*, g.nome as group_name,
               ta.nome as team_a_name, tb.nome as team_b_name
        FROM jogos_fase_grupos jfg
        INNER JOIN grupos g ON jfg.grupo_id = g.id
        INNER JOIN times ta ON jfg.timeA_id = ta.id
        INNER JOIN times tb ON jfg.timeB_id = tb.id
        WHERE g.tournament_id = ?
        ORDER BY jfg.rodada, g.nome, jfg.id
    ");
    $stmt->execute([$tournament_id]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create export data
    $export_data = [
        'tournament' => $tournament,
        'groups' => $groups,
        'teams' => $teams,
        'matches' => $matches,
        'export_date' => date('Y-m-d H:i:s'),
        'export_version' => '1.0'
    ];
    
    // Set headers for download
    $filename = 'tournament_' . $tournament['name'] . '_' . date('Y-m-d') . '.json';
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error exporting tournament: ' . $e->getMessage();
    header('Location: tournament_list.php');
    exit;
}
?>
