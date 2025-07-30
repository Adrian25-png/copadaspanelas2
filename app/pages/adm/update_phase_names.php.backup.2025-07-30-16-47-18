<?php
/**
 * Script para atualizar nomes das fases existentes
 */

require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>Atualizando nomes das fases existentes</h2>";
    
    // Mapeamento de nomes antigos para novos
    $phase_mapping = [
        'Oitavas de Final' => 'Oitavas',
        'Quartas de Final' => 'Quartas',
        'Terceiro Lugar' => '3º Lugar'
        // 'Semifinal' e 'Final' permanecem iguais
    ];
    
    // Verificar fases existentes
    echo "<h3>Fases existentes na tabela:</h3>";
    $stmt = $pdo->query("SELECT DISTINCT phase, COUNT(*) as total FROM matches WHERE phase IS NOT NULL GROUP BY phase ORDER BY phase");
    $existing_phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($existing_phases)) {
        echo "<p>Nenhuma fase encontrada na tabela matches.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th>Fase Atual</th><th>Jogos</th><th>Ação</th></tr>";
        
        foreach ($existing_phases as $phase) {
            $current_name = $phase['phase'];
            $new_name = $phase_mapping[$current_name] ?? $current_name;
            $action = ($current_name !== $new_name) ? "Atualizar para '$new_name'" : "Manter";
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($current_name) . "</td>";
            echo "<td>" . $phase['total'] . "</td>";
            echo "<td>" . $action . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Executar atualizações
    echo "<h3>Executando atualizações:</h3>";
    
    foreach ($phase_mapping as $old_name => $new_name) {
        try {
            $stmt = $pdo->prepare("UPDATE matches SET phase = ? WHERE phase = ?");
            $result = $stmt->execute([$new_name, $old_name]);
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                echo "✅ Atualizado '$old_name' → '$new_name' ($affected jogos)<br>";
            } else {
                echo "ℹ️ Nenhum jogo encontrado com fase '$old_name'<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro ao atualizar '$old_name': " . $e->getMessage() . "<br>";
        }
    }
    
    // Verificar resultado final
    echo "<h3>Fases após atualização:</h3>";
    $stmt = $pdo->query("SELECT DISTINCT phase, COUNT(*) as total FROM matches WHERE phase IS NOT NULL GROUP BY phase ORDER BY phase");
    $updated_phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Fase</th><th>Jogos</th><th>Tamanho</th></tr>";
    
    foreach ($updated_phases as $phase) {
        $length = strlen($phase['phase']);
        $color = ($length <= 20) ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($phase['phase']) . "</td>";
        echo "<td>" . $phase['total'] . "</td>";
        echo "<td style='color: $color;'>" . $length . " chars</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Atualização concluída!</h3>";
    echo "<p>Agora você pode testar:</p>";
    echo "<ul>";
    echo "<li><a href='knockout_generator.php?tournament_id=26'>Gerador de Eliminatórias</a></li>";
    echo "<li><a href='finals_manager.php?tournament_id=26'>Gerenciador de Finais</a></li>";
    echo "<li><a href='third_place_manager.php?tournament_id=26'>Terceiro Lugar</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
