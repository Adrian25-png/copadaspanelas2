<?php
session_start();
require_once 'app/config/conexao.php';

// Simular sessão de admin para teste
$_SESSION['admin_id'] = 1;

$pdo = conectar();

echo "<h2>Debug - Live Externa</h2>";

// Verificar se a tabela existe
try {
    $stmt = $pdo->query("DESCRIBE live_streams");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>✅ Estrutura da tabela live_streams:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $existing_columns = [];
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
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
    
    // Verificar colunas necessárias
    $required_columns = ['external_url', 'embed_code', 'stream_type'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        if (!in_array($col, $existing_columns)) {
            $missing_columns[] = $col;
        }
    }
    
    if (!empty($missing_columns)) {
        echo "<h3 style='color: red;'>❌ Colunas faltantes:</h3>";
        echo "<ul>";
        foreach ($missing_columns as $col) {
            echo "<li style='color: red;'>{$col}</li>";
        }
        echo "</ul>";
        echo "<p><a href='update_live_streams_table.php' style='background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Corrigir Tabela</a></p>";
    } else {
        echo "<h3 style='color: green;'>✅ Todas as colunas necessárias existem!</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Erro ao verificar tabela:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><a href='update_live_streams_table.php' style='background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Criar/Corrigir Tabela</a></p>";
}

// Teste de inserção de live externa
if (empty($missing_columns)) {
    echo "<hr><h3>🧪 Teste de Inserção de Live Externa:</h3>";
    
    try {
        // Simular dados do POST
        $title = 'Teste Live Externa';
        $external_url = 'https://www.twitch.tv/exemplo';
        $embed_code = '<iframe src="https://player.twitch.tv/?channel=exemplo" allowfullscreen></iframe>';
        $admin_id = 1;
        
        // Desativar streams anteriores
        $pdo->query("UPDATE live_streams SET status = 'inativo'");
        
        // Inserir nova stream externa
        $stmt = $pdo->prepare("INSERT INTO live_streams (title, external_url, embed_code, status, admin_id, stream_type, created_at) VALUES (?, ?, ?, 'ativo', ?, 'external', NOW())");
        $result = $stmt->execute([$title, $external_url, $embed_code, $admin_id]);
        
        if ($result) {
            $live_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Inserção bem-sucedida! ID: {$live_id}</p>";
            
            // Buscar o registro inserido
            $stmt = $pdo->prepare("SELECT * FROM live_streams WHERE id = ?");
            $stmt->execute([$live_id]);
            $live_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h4>Dados inseridos:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            foreach ($live_data as $key => $value) {
                echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
            
            // Remover teste
            $pdo->prepare("DELETE FROM live_streams WHERE id = ?")->execute([$live_id]);
            echo "<p>🗑️ Registro de teste removido</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Falha na inserção</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro no teste: " . $e->getMessage() . "</p>";
    }
}

// Formulário de teste
echo "<hr><h3>📝 Teste Manual de Live Externa:</h3>";

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'start_external_live') {
    $external_url = trim($_POST['external_url']);
    $title = trim($_POST['title']);
    $embed_code = trim($_POST['embed_code']);
    
    echo "<h4>Dados recebidos:</h4>";
    echo "<ul>";
    echo "<li><strong>Título:</strong> " . htmlspecialchars($title) . "</li>";
    echo "<li><strong>URL:</strong> " . htmlspecialchars($external_url) . "</li>";
    echo "<li><strong>Embed:</strong> " . htmlspecialchars($embed_code) . "</li>";
    echo "</ul>";
    
    if (!empty($external_url) && !empty($title)) {
        try {
            // Desativar streams anteriores
            $pdo->query("UPDATE live_streams SET status = 'inativo'");
            
            // Inserir nova stream externa
            $stmt = $pdo->prepare("INSERT INTO live_streams (title, external_url, embed_code, status, admin_id, stream_type, created_at) VALUES (?, ?, ?, 'ativo', ?, 'external', NOW())");
            $result = $stmt->execute([$title, $external_url, $embed_code, 1]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Live externa criada com sucesso!</p>";
            } else {
                echo "<p style='color: red;'>❌ Falha ao criar live externa</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Preencha todos os campos obrigatórios!</p>";
    }
}
?>

<form method="POST" style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
    <input type="hidden" name="action" value="start_external_live">
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Título:</label>
        <input type="text" name="title" placeholder="Ex: Final - Time A vs Time B" required 
               style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">URL Externa:</label>
        <input type="url" name="external_url" placeholder="https://www.twitch.tv/exemplo" required 
               style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Código Embed (Opcional):</label>
        <textarea name="embed_code" placeholder="<iframe src=... ou código embed" rows="3" 
                  style="width: 100%; padding: 10px; border-radius: 5px; border: none;"></textarea>
    </div>
    
    <button type="submit" style="background: #27ae60; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer;">
        🚀 Testar Live Externa
    </button>
</form>

<hr>
<p><a href="app/pages/adm/gerenciar_transmissao.php">🔙 Voltar para Gerenciar Transmissão</a></p>
<p><a href="update_live_streams_table.php">🔧 Atualizar Tabela</a></p>
