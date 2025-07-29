<?php
include 'app/config/conexao.php';
require_once 'app/classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$tournament = $tournamentManager->getCurrentTournament();

if ($tournament) {
    $tournament_id = $tournament['id'];
    
    echo "<h2>DEBUG - Investigando Quartas de Final</h2>";
    echo "<p><strong>Torneio:</strong> " . $tournament['name'] . " (ID: {$tournament_id})</p>";
    
    echo "<hr><h3>1. Verificando nomes exatos das fases no banco:</h3>";
    $stmt = $pdo->prepare("
        SELECT DISTINCT phase, COUNT(*) as total
        FROM matches
        WHERE tournament_id = ?
        GROUP BY phase
        ORDER BY phase
    ");
    $stmt->execute([$tournament_id]);
    $all_phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Fase (nome exato)</th><th>Quantidade</th></tr>";
    foreach ($all_phases as $phase) {
        $phase_name = $phase['phase'];
        $highlight = ($phase_name === 'Quartas') ? "style='background: yellow;'" : "";
        echo "<tr {$highlight}><td>'{$phase_name}'</td><td>{$phase['total']}</td></tr>";
    }
    echo "</table>";
    
    echo "<hr><h3>2. Testando consulta específica para 'Quartas':</h3>";
    $stmt = $pdo->prepare("
        SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.phase = 'Quartas'
        ORDER BY m.created_at
    ");
    $stmt->execute([$tournament_id]);
    $quartas_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Resultado:</strong> " . count($quartas_matches) . " jogo(s) encontrado(s)</p>";
    
    if (count($quartas_matches) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Fase</th><th>Time 1</th><th>Time 2</th><th>Placar</th><th>Status</th></tr>";
        foreach ($quartas_matches as $match) {
            echo "<tr>";
            echo "<td>{$match['id']}</td>";
            echo "<td>'{$match['phase']}'</td>";
            echo "<td>" . ($match['team1_name'] ?? 'TBD') . "</td>";
            echo "<td>" . ($match['team2_name'] ?? 'TBD') . "</td>";
            echo "<td>" . ($match['team1_goals'] ?? '-') . " x " . ($match['team2_goals'] ?? '-') . "</td>";
            echo "<td>{$match['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr><h3>3. Simulando o código da página exibir_finais.php:</h3>";
    
    // Replicar exatamente o código da página
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM matches
        WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final')
    ");
    $stmt->execute([$tournament_id]);
    $total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Total de jogos de eliminatórias:</strong> {$total_matches}</p>";
    
    if ($total_matches > 0) {
        $phases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
        $bracket_data = [];
        
        echo "<p><strong>Testando cada fase:</strong></p>";
        echo "<ul>";
        
        foreach ($phases as $phase) {
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
            
            $count = count($matches);
            $color = $count > 0 ? 'green' : 'red';
            echo "<li style='color: {$color};'><strong>{$phase}:</strong> {$count} jogo(s)";
            
            if (!empty($matches)) {
                $bracket_data[$phase] = $matches;
                echo " ✅ SERÁ EXIBIDO";
            } else {
                echo " ❌ NÃO SERÁ EXIBIDO";
            }
            echo "</li>";
        }
        echo "</ul>";
        
        echo "<p><strong>Array bracket_data final:</strong></p>";
        echo "<pre>";
        foreach ($bracket_data as $phase => $matches) {
            echo "{$phase}: " . count($matches) . " jogo(s)\n";
        }
        echo "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ Nenhum jogo de eliminatórias encontrado!</p>";
    }
    
    echo "<hr><h3>4. Possíveis problemas:</h3>";
    echo "<ul>";
    echo "<li>Nome da fase pode ter espaços extras ou caracteres especiais</li>";
    echo "<li>Pode estar usando 'Quartas de Final' em vez de 'Quartas'</li>";
    echo "<li>Problema de encoding/charset</li>";
    echo "<li>Tournament_id incorreto</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><a href='app/pages/exibir_finais.php' style='background: #FFD700; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Ver Página de Finais</a></p>";
    
} else {
    echo "<p style='color: red;'>❌ Nenhum torneio ativo encontrado!</p>";
}
?>
