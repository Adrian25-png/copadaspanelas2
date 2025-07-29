<?php
include '../config/conexao.php';
require_once '../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$tournament = $tournamentManager->getCurrentTournament();

echo "<h2>Debug - Exibir Finais</h2>";

if ($tournament) {
    $tournament_id = $tournament['id'];
    echo "<p>✅ Torneio: " . htmlspecialchars($tournament['name']) . " (ID: $tournament_id)</p>";
    
    // Verificar se existem jogos de eliminatórias
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM matches
        WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')
    ");
    $stmt->execute([$tournament_id]);
    $total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p>Total de matches das eliminatórias: $total_matches</p>";
    
    if ($total_matches > 0) {
        // Verificar quais fases existem
        $stmt = $pdo->prepare("
            SELECT DISTINCT phase
            FROM matches
            WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')
            ORDER BY
                CASE phase
                    WHEN 'Oitavas' THEN 1
                    WHEN 'Quartas' THEN 2
                    WHEN 'Semifinal' THEN 3
                    WHEN 'Final' THEN 4
                    WHEN '3º Lugar' THEN 5
                END
        ");
        $stmt->execute([$tournament_id]);
        $existing_phases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Fases existentes:</h3>";
        echo "<ul>";
        foreach ($existing_phases as $phase) {
            echo "<li>" . htmlspecialchars($phase) . "</li>";
        }
        echo "</ul>";
        
        echo "<p>Terceiro lugar na lista? " . (in_array('3º Lugar', $existing_phases) ? '✅ SIM' : '❌ NÃO') . "</p>";
        
        // Testar especificamente o terceiro lugar
        if (in_array('3º Lugar', $existing_phases)) {
            echo "<h3>✅ Terceiro lugar encontrado! Detalhes:</h3>";
            
            $stmt = $pdo->prepare("
                SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
                FROM matches m
                LEFT JOIN times t1 ON m.team1_id = t1.id
                LEFT JOIN times t2 ON m.team2_id = t2.id
                WHERE m.tournament_id = ? AND m.phase = '3º Lugar'
                ORDER BY m.created_at
            ");
            $stmt->execute([$tournament_id]);
            $third_place_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Número de jogos de terceiro lugar: " . count($third_place_matches) . "</p>";
            
            foreach ($third_place_matches as $match) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
                echo "<p><strong>Jogo ID:</strong> " . $match['id'] . "</p>";
                echo "<p><strong>Times:</strong> " . htmlspecialchars($match['team1_name'] ?? 'TBD') . " vs " . htmlspecialchars($match['team2_name'] ?? 'TBD') . "</p>";
                echo "<p><strong>Resultado:</strong> " . ($match['team1_goals'] ?? '-') . " - " . ($match['team2_goals'] ?? '-') . "</p>";
                echo "<p><strong>Status:</strong> " . htmlspecialchars($match['status']) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<h3>❌ Terceiro lugar NÃO encontrado!</h3>";
            echo "<p>Fases disponíveis: " . implode(', ', $existing_phases) . "</p>";
        }
        
        // Mostrar todos os jogos do torneio
        echo "<h3>Todos os jogos do torneio:</h3>";
        $stmt = $pdo->prepare("
            SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            WHERE m.tournament_id = ?
            ORDER BY m.phase, m.created_at
        ");
        $stmt->execute([$tournament_id]);
        $all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Fase</th><th>Time 1</th><th>Time 2</th><th>Resultado</th><th>Status</th></tr>";
        
        foreach ($all_matches as $match) {
            $score = ($match['team1_goals'] !== null && $match['team2_goals'] !== null) 
                    ? $match['team1_goals'] . '-' . $match['team2_goals'] 
                    : 'Sem resultado';
            
            $row_color = ($match['phase'] === '3º Lugar') ? 'background-color: #ffffcc;' : '';
            
            echo "<tr style='$row_color'>";
            echo "<td>" . $match['id'] . "</td>";
            echo "<td>" . htmlspecialchars($match['phase'] ?? 'Sem fase') . "</td>";
            echo "<td>" . htmlspecialchars($match['team1_name'] ?? 'TBD') . "</td>";
            echo "<td>" . htmlspecialchars($match['team2_name'] ?? 'TBD') . "</td>";
            echo "<td>" . $score . "</td>";
            echo "<td>" . htmlspecialchars($match['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ Nenhum jogo de eliminatórias encontrado</p>";
    }
    
} else {
    echo "<p>❌ Nenhum torneio ativo encontrado</p>";
}

echo "<br><br>";
echo "<a href='exibir_finais.php'>Ir para página original</a> | ";
echo "<a href='adm/third_place_manager.php?tournament_id=" . ($tournament['id'] ?? '26') . "'>Gerenciar Terceiro Lugar</a>";
?>
