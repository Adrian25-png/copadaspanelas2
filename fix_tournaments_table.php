<?php
require_once 'app/config/conexao.php';

$pdo = conectar();

echo "<h2>Corrigindo Tabela Tournaments</h2>";

try {
    // Verificar estrutura atual
    $stmt = $pdo->query("DESCRIBE tournaments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estrutura atual da tabela:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $existing_columns = [];
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Colunas necessárias
    $required_columns = [
        'teams_count' => 'INT DEFAULT 8',
        'groups_count' => 'INT DEFAULT 2', 
        'format_type' => 'VARCHAR(100) DEFAULT "Grupos + Eliminatórias"',
        'start_date' => 'DATE NULL',
        'end_date' => 'DATE NULL'
    ];
    
    echo "<h3>Adicionando colunas faltantes:</h3>";
    
    foreach ($required_columns as $column_name => $column_definition) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE tournaments ADD COLUMN {$column_name} {$column_definition}");
                echo "<p style='color: green;'>✅ Coluna '{$column_name}' adicionada</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Erro ao adicionar '{$column_name}': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Coluna '{$column_name}' já existe</p>";
        }
    }
    
    // Verificar estrutura final
    echo "<h3>Estrutura final da tabela:</h3>";
    $stmt = $pdo->query("DESCRIBE tournaments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Teste de inserção
    echo "<h3>Teste de inserção:</h3>";
    
    $stmt = $pdo->prepare("
        INSERT INTO tournaments (name, description, teams_count, groups_count, format_type, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'draft', NOW())
    ");
    
    $result = $stmt->execute([
        'Teste Corrigido',
        'Teste após correção da tabela',
        8,
        2,
        'Grupos + Eliminatórias'
    ]);
    
    if ($result) {
        $tournament_id = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Teste de inserção bem-sucedido! ID: {$tournament_id}</p>";
        
        // Remover teste
        $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tournament_id]);
        echo "<p>🗑️ Registro de teste removido</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha no teste de inserção</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>✅ Correção Concluída!</h3>";
echo "<p><a href='app/pages/adm/tournament_templates.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔙 Voltar para Templates</a></p>";
echo "<p><a href='app/pages/adm/dashboard_simple.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Dashboard</a></p>";
?>
