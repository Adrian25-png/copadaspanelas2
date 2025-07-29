<?php
require_once 'app/config/conexao.php';

$pdo = conectar();

echo "<h2>Atualizando Tabela live_streams</h2>";

try {
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'live_streams'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<h3>Criando tabela live_streams...</h3>";
        $pdo->exec("
            CREATE TABLE live_streams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                youtube_id VARCHAR(20) NULL,
                youtube_url VARCHAR(500) NULL,
                external_url VARCHAR(500) NULL,
                embed_code TEXT NULL,
                stream_type ENUM('youtube', 'external') DEFAULT 'youtube',
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                admin_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p style='color: green;'>✅ Tabela live_streams criada com sucesso!</p>";
    } else {
        echo "<h3>Tabela live_streams já existe. Verificando colunas...</h3>";
        
        // Verificar estrutura atual
        $stmt = $pdo->query("DESCRIBE live_streams");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
            'external_url' => 'VARCHAR(500) NULL',
            'embed_code' => 'TEXT NULL',
            'stream_type' => 'ENUM("youtube", "external") DEFAULT "youtube"'
        ];
        
        echo "<h3>Adicionando colunas faltantes:</h3>";
        
        foreach ($required_columns as $column_name => $column_definition) {
            if (!in_array($column_name, $existing_columns)) {
                try {
                    $pdo->exec("ALTER TABLE live_streams ADD COLUMN {$column_name} {$column_definition}");
                    echo "<p style='color: green;'>✅ Coluna '{$column_name}' adicionada</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erro ao adicionar '{$column_name}': " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p style='color: blue;'>ℹ️ Coluna '{$column_name}' já existe</p>";
            }
        }
    }
    
    // Verificar estrutura final
    echo "<h3>Estrutura final da tabela:</h3>";
    $stmt = $pdo->query("DESCRIBE live_streams");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        $highlight = in_array($column['Field'], ['external_url', 'embed_code', 'stream_type']) ? "style='background: yellow;'" : "";
        echo "<tr {$highlight}>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Teste de inserção
    echo "<h3>🧪 Teste de inserção:</h3>";
    
    // Teste YouTube
    try {
        $stmt = $pdo->prepare("
            INSERT INTO live_streams (title, youtube_id, youtube_url, stream_type, status, created_at) 
            VALUES (?, ?, ?, 'youtube', 'inativo', NOW())
        ");
        
        $result = $stmt->execute([
            'Teste YouTube',
            'dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);
        
        if ($result) {
            $youtube_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Teste YouTube bem-sucedido! ID: {$youtube_id}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro no teste YouTube: " . $e->getMessage() . "</p>";
    }
    
    // Teste Externo
    try {
        $stmt = $pdo->prepare("
            INSERT INTO live_streams (title, external_url, embed_code, stream_type, status, created_at) 
            VALUES (?, ?, ?, 'external', 'inativo', NOW())
        ");
        
        $result = $stmt->execute([
            'Teste Externo',
            'https://www.twitch.tv/exemplo',
            '<iframe src="https://player.twitch.tv/?channel=exemplo" allowfullscreen></iframe>'
        ]);
        
        if ($result) {
            $external_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Teste Externo bem-sucedido! ID: {$external_id}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro no teste Externo: " . $e->getMessage() . "</p>";
    }
    
    // Limpar testes
    if (isset($youtube_id)) {
        $pdo->prepare("DELETE FROM live_streams WHERE id = ?")->execute([$youtube_id]);
    }
    if (isset($external_id)) {
        $pdo->prepare("DELETE FROM live_streams WHERE id = ?")->execute([$external_id]);
    }
    echo "<p>🗑️ Registros de teste removidos</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro geral: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>✅ Atualização Concluída!</h3>";
echo "<p><a href='app/pages/adm/gerenciar_transmissao.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔙 Testar Transmissões</a></p>";
echo "<p><a href='app/pages/adm/dashboard_simple.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Dashboard</a></p>";
?>
