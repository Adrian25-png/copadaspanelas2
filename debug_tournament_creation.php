<?php
require_once 'app/config/conexao.php';

$pdo = conectar();

echo "<h2>Debug - Criação de Torneios</h2>";

// Verificar se a tabela tournaments existe
try {
    $stmt = $pdo->query("DESCRIBE tournaments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>✅ Tabela 'tournaments' existe</h3>";
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
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Erro na tabela tournaments:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    // Tentar criar a tabela
    echo "<h3>Criando tabela tournaments...</h3>";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tournaments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                teams_count INT DEFAULT 8,
                groups_count INT DEFAULT 2,
                format_type VARCHAR(100) DEFAULT 'Grupos + Eliminatórias',
                status VARCHAR(50) DEFAULT 'draft',
                start_date DATE NULL,
                end_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p style='color: green;'>✅ Tabela tournaments criada com sucesso!</p>";
        
        // Mostrar estrutura da tabela criada
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
        
    } catch (Exception $e2) {
        echo "<p style='color: red;'>❌ Erro ao criar tabela: " . $e2->getMessage() . "</p>";
    }
}

// Testar inserção
echo "<hr><h3>Teste de Inserção</h3>";

try {
    $stmt = $pdo->prepare("
        INSERT INTO tournaments (name, description, teams_count, groups_count, format_type, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'draft', NOW())
    ");
    
    $test_result = $stmt->execute([
        'Teste de Torneio',
        'Torneio criado para teste',
        8,
        2,
        'Grupos + Eliminatórias'
    ]);
    
    if ($test_result) {
        $tournament_id = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Inserção de teste bem-sucedida! ID: {$tournament_id}</p>";
        
        // Buscar o registro inserido
        $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
        $stmt->execute([$tournament_id]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h4>Dados inseridos:</h4>";
        echo "<pre>";
        print_r($tournament);
        echo "</pre>";
        
        // Remover o teste
        $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tournament_id]);
        echo "<p>🗑️ Registro de teste removido</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Falha na inserção de teste</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro no teste de inserção: " . $e->getMessage() . "</p>";
}

// Verificar torneios existentes
echo "<hr><h3>Torneios Existentes</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM tournaments ORDER BY created_at DESC LIMIT 5");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tournaments) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>Times</th><th>Grupos</th><th>Criado em</th></tr>";
        
        foreach ($tournaments as $tournament) {
            echo "<tr>";
            echo "<td>{$tournament['id']}</td>";
            echo "<td>" . htmlspecialchars($tournament['name']) . "</td>";
            echo "<td>{$tournament['status']}</td>";
            echo "<td>{$tournament['teams_count']}</td>";
            echo "<td>{$tournament['groups_count']}</td>";
            echo "<td>{$tournament['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum torneio encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar torneios: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='app/pages/adm/tournament_templates.php'>🔙 Voltar para Templates</a></p>";
echo "<p><a href='app/pages/adm/dashboard_simple.php'>🏠 Dashboard</a></p>";
?>
