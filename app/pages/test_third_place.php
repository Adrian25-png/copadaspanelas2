<?php
include '../config/conexao.php';

try {
    $pdo = conectar();
    
    // Buscar jogo de terceiro lugar
    $stmt = $pdo->prepare("
        SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        WHERE m.phase = '3º Lugar'
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $third_place_match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h1>Teste - Terceiro Lugar</h1>";
    
    if ($third_place_match) {
        echo "<h2>✅ Jogo de Terceiro Lugar Encontrado!</h2>";
        echo "<div style='border: 2px solid #cd7f32; padding: 20px; margin: 20px 0; background: rgba(205, 127, 50, 0.1);'>";
        echo "<h3 style='color: #cd7f32;'>🥉 DISPUTA DO 3º LUGAR</h3>";
        echo "<p><strong>ID do Jogo:</strong> " . $third_place_match['id'] . "</p>";
        echo "<p><strong>Torneio ID:</strong> " . $third_place_match['tournament_id'] . "</p>";
        echo "<p><strong>Fase:</strong> " . htmlspecialchars($third_place_match['phase']) . "</p>";
        echo "<p><strong>Time 1:</strong> " . htmlspecialchars($third_place_match['team1_name'] ?? 'TBD') . "</p>";
        echo "<p><strong>Time 2:</strong> " . htmlspecialchars($third_place_match['team2_name'] ?? 'TBD') . "</p>";
        echo "<p><strong>Resultado:</strong> " . ($third_place_match['team1_goals'] ?? '-') . " x " . ($third_place_match['team2_goals'] ?? '-') . "</p>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($third_place_match['status']) . "</p>";
        echo "</div>";
        
        // Mostrar como seria exibido
        echo "<h2>Preview da Exibição:</h2>";
        echo "<div style='display: flex; justify-content: center; margin: 30px 0;'>";
        echo "<div style='background: rgba(205, 127, 50, 0.1); border: 2px solid #cd7f32; border-radius: 15px; padding: 25px; max-width: 400px; text-align: center;'>";
        echo "<div style='display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 15px;'>";
        echo "<span style='color: #cd7f32; font-size: 1.5rem;'>🥉</span>";
        echo "<div style='color: #cd7f32; font-weight: bold; font-size: 1.1rem;'>DISPUTA DO 3º LUGAR</div>";
        echo "</div>";
        
        echo "<div style='border: 2px solid #cd7f32; border-radius: 8px; background: rgba(0, 0, 0, 0.1); overflow: hidden;'>";
        
        // Time 1
        echo "<div style='display: flex; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid rgba(205, 127, 50, 0.3); background: rgba(255, 255, 255, 0.05);'>";
        echo "<span style='font-weight: 500;'>" . htmlspecialchars($third_place_match['team1_name'] ?? 'TBD') . "</span>";
        echo "<span style='font-weight: bold; color: #cd7f32; font-size: 1.1rem;'>" . ($third_place_match['team1_goals'] ?? '-') . "</span>";
        echo "</div>";
        
        // Time 2
        echo "<div style='display: flex; align-items: center; justify-content: space-between; padding: 12px; background: rgba(255, 255, 255, 0.05);'>";
        echo "<span style='font-weight: 500;'>" . htmlspecialchars($third_place_match['team2_name'] ?? 'TBD') . "</span>";
        echo "<span style='font-weight: bold; color: #cd7f32; font-size: 1.1rem;'>" . ($third_place_match['team2_goals'] ?? '-') . "</span>";
        echo "</div>";
        
        echo "</div>";
        
        // Resultado final se jogo finalizado
        if ($third_place_match['status'] === 'finalizado' && $third_place_match['team1_goals'] !== null && $third_place_match['team2_goals'] !== null) {
            $winner = '';
            if ($third_place_match['team1_goals'] > $third_place_match['team2_goals']) {
                $winner = $third_place_match['team1_name'];
            } elseif ($third_place_match['team2_goals'] > $third_place_match['team1_goals']) {
                $winner = $third_place_match['team2_name'];
            } else {
                $winner = 'Empate';
            }
            
            echo "<div style='margin-top: 15px; padding: 10px; background: rgba(205, 127, 50, 0.2); border-radius: 8px; color: #cd7f32; font-weight: bold;'>";
            echo "🥉 3º Lugar: " . htmlspecialchars($winner);
            echo "</div>";
        } else {
            echo "<div style='margin-top: 15px; color: #cd7f32; font-style: italic;'>";
            echo "⏰ Aguardando resultado";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        
    } else {
        echo "<h2>❌ Nenhum Jogo de Terceiro Lugar Encontrado</h2>";
        echo "<p>Não há jogos com phase = '3º Lugar' no banco de dados.</p>";
        
        // Mostrar todos os jogos para debug
        echo "<h3>Todos os jogos no banco:</h3>";
        $stmt = $pdo->query("SELECT id, tournament_id, phase, team1_id, team2_id, status FROM matches ORDER BY id DESC LIMIT 10");
        $all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Tournament</th><th>Phase</th><th>Team1</th><th>Team2</th><th>Status</th></tr>";
        foreach ($all_matches as $match) {
            echo "<tr>";
            echo "<td>" . $match['id'] . "</td>";
            echo "<td>" . $match['tournament_id'] . "</td>";
            echo "<td>" . htmlspecialchars($match['phase'] ?? 'NULL') . "</td>";
            echo "<td>" . $match['team1_id'] . "</td>";
            echo "<td>" . $match['team2_id'] . "</td>";
            echo "<td>" . htmlspecialchars($match['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2>Erro:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='adm/third_place_manager.php?tournament_id=26'>Criar/Gerenciar Terceiro Lugar</a><br>";
echo "<a href='exibir_finais.php'>Página Original das Finais</a>";
?>
