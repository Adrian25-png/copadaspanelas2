<?php
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>Estrutura da tabela matches:</h2>";
    
    // Verificar estrutura da tabela
    $stmt = $pdo->query("DESCRIBE matches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar valores únicos na coluna phase
    echo "<h2>Valores únicos na coluna phase:</h2>";
    $stmt = $pdo->query("SELECT DISTINCT phase, LENGTH(phase) as tamanho FROM matches WHERE phase IS NOT NULL ORDER BY phase");
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Fase</th><th>Tamanho</th></tr>";
    
    foreach ($phases as $phase) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($phase['phase']) . "</td>";
        echo "<td>" . $phase['tamanho'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Testar inserção
    echo "<h2>Teste de inserção:</h2>";
    $test_phases = [
        'Oitavas de Final',
        'Quartas de Final', 
        'Semifinal',
        'Final',
        'Terceiro Lugar'
    ];
    
    foreach ($test_phases as $phase) {
        echo "Fase: '$phase' - Tamanho: " . strlen($phase) . " caracteres<br>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
