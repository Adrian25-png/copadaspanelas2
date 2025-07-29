<?php
include 'app/config/conexao.php';
require_once 'app/classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$tournament = $tournamentManager->getCurrentTournament();

if ($tournament) {
    $tournament_id = $tournament['id'];
    
    echo "<h2>Verificando dados de Eliminatórias para o torneio: " . $tournament['name'] . "</h2>";
    
    // Verificar todas as fases de eliminatórias
    $phases = ['Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar'];
    
    foreach ($phases as $phase) {
        echo "<h3>Fase: {$phase}</h3>";
        
        $stmt = $pdo->prepare("
            SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            WHERE m.tournament_id = ? AND m.phase = ?
            ORDER BY m.created_at
        ");
        $stmt->execute([$tournament_id, $phase]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($matches) > 0) {
            echo "<p style='color: green;'>✅ {$phase}: " . count($matches) . " jogo(s) encontrado(s)</p>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
            echo "<tr><th>ID</th><th>Time 1</th><th>Time 2</th><th>Placar 1</th><th>Placar 2</th><th>Status</th><th>Data</th></tr>";
            
            foreach ($matches as $match) {
                echo "<tr>";
                echo "<td>" . $match['id'] . "</td>";
                echo "<td>" . ($match['team1_name'] ?? 'TBD') . "</td>";
                echo "<td>" . ($match['team2_name'] ?? 'TBD') . "</td>";
                echo "<td>" . ($match['team1_goals'] ?? '-') . "</td>";
                echo "<td>" . ($match['team2_goals'] ?? '-') . "</td>";
                echo "<td>" . $match['status'] . "</td>";
                echo "<td>" . $match['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ {$phase}: Nenhum jogo encontrado</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Resumo:</h3>";
    echo "<ul>";
    
    // Contar total por fase
    foreach ($phases as $phase) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM matches
            WHERE tournament_id = ? AND phase = ?
        ");
        $stmt->execute([$tournament_id, $phase]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $color = $total > 0 ? 'green' : 'red';
        echo "<li style='color: {$color};'>{$phase}: {$total} jogo(s)</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>Ações:</h3>";
    echo "<p><a href='create_quartas_test.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏆 Criar Quartas de Final de Teste</a></p>";
    echo "<p><a href='app/pages/exibir_finais.php' style='background: #FFD700; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👁️ Ver Página de Finais</a></p>";
    
} else {
    echo "<p style='color: red;'>❌ Nenhum torneio ativo encontrado!</p>";
}
?>
