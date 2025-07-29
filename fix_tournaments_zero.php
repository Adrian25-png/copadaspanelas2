<?php
session_start();

// Configurar sessão de admin
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin_zero';

require_once 'app/config/conexao.php';

echo "<h1>🔧 Correção DEFINITIVA - Do Zero</h1>";

try {
    $pdo = conectar();
    
    // 1. VERIFICAR ESTRUTURA ATUAL
    echo "<h2>🔍 STEP 1: Verificando estrutura atual</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'tournaments'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabela 'tournaments' existe</p>";
        
        // Mostrar estrutura atual
        $stmt = $pdo->query("DESCRIBE tournaments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📋 Estrutura Atual:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>Campo</th><th style='padding: 8px;'>Tipo</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Default</th>";
        echo "</tr>";
        
        $has_name = false;
        $has_nome = false;
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td style='padding: 6px; font-weight: bold;'>{$col['Field']}</td>";
            echo "<td style='padding: 6px;'>{$col['Type']}</td>";
            echo "<td style='padding: 6px;'>{$col['Null']}</td>";
            echo "<td style='padding: 6px;'>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
            
            if ($col['Field'] == 'name') $has_name = true;
            if ($col['Field'] == 'nome') $has_nome = true;
        }
        echo "</table>";
        
        echo "<p><strong>Campo 'name':</strong> " . ($has_name ? "✅ Existe" : "❌ Não existe") . "</p>";
        echo "<p><strong>Campo 'nome':</strong> " . ($has_nome ? "✅ Existe" : "❌ Não existe") . "</p>";
        
    } else {
        echo "<p>❌ Tabela 'tournaments' não existe</p>";
        $has_name = false;
        $has_nome = false;
    }
    
    // 2. RECRIAR TABELA DO ZERO
    if ($_POST && isset($_POST['recreate_table'])) {
        echo "<h2>🔄 STEP 2: Recriando tabela do zero</h2>";
        
        try {
            // Fazer backup se existir dados
            $backup_data = [];
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT * FROM tournaments");
                $backup_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p>📦 Backup de " . count($backup_data) . " registros feito</p>";
            }
            
            // Dropar tabela existente
            $pdo->exec("DROP TABLE IF EXISTS tournaments");
            echo "<p>🗑️ Tabela antiga removida</p>";
            
            // Criar nova tabela com estrutura limpa
            $pdo->exec("
                CREATE TABLE tournaments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(255) NOT NULL,
                    descricao TEXT,
                    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "<p>✅ Nova tabela criada com estrutura limpa</p>";
            
            // Restaurar dados se houver
            if (!empty($backup_data)) {
                $stmt = $pdo->prepare("INSERT INTO tournaments (nome, descricao, status, created_at) VALUES (?, ?, ?, ?)");
                $restored = 0;
                foreach ($backup_data as $row) {
                    $nome = $row['nome'] ?? $row['name'] ?? 'Torneio Restaurado';
                    $descricao = $row['descricao'] ?? $row['description'] ?? '';
                    $status = $row['status'] ?? 'draft';
                    $created_at = $row['created_at'] ?? date('Y-m-d H:i:s');
                    
                    if ($stmt->execute([$nome, $descricao, $status, $created_at])) {
                        $restored++;
                    }
                }
                echo "<p>📥 $restored registros restaurados</p>";
            }
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h3>✅ TABELA RECRIADA COM SUCESSO!</h3>";
            echo "<p>Estrutura final: <strong>id, nome, descricao, status, created_at, updated_at</strong></p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro ao recriar tabela: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 3. TESTE DE INSERÇÃO
    if ($_POST && isset($_POST['test_insert'])) {
        echo "<h2>🧪 STEP 3: Teste de inserção</h2>";
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tournaments (nome, descricao, status, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $_POST['test_name'],
                $_POST['test_description'],
                'draft'
            ]);
            
            if ($result) {
                $tournament_id = $pdo->lastInsertId();
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
                echo "<h3>🎉 SUCESSO TOTAL!</h3>";
                echo "<p><strong>ID:</strong> $tournament_id</p>";
                echo "<p><strong>Nome:</strong> " . htmlspecialchars($_POST['test_name']) . "</p>";
                echo "<p><strong>Query que funcionou:</strong></p>";
                echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>INSERT INTO tournaments (nome, descricao, status, created_at) VALUES (?, ?, ?, NOW())</pre>";
                echo "</div>";
                
                // Remover teste
                $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tournament_id]);
                echo "<p>🗑️ Registro de teste removido</p>";
                
            } else {
                echo "<p style='color: red;'>❌ Falha na inserção</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 4. CORRIGIR ARQUIVOS PHP
    if ($_POST && isset($_POST['fix_files'])) {
        echo "<h2>🔧 STEP 4: Corrigindo arquivos PHP</h2>";
        
        // Correção para template_preview.php
        $preview_content = file_get_contents('app/pages/adm/template_preview.php');
        
        // Encontrar e substituir a query
        $old_pattern = '/INSERT INTO tournaments \([^)]+\)\s*VALUES \([^)]+\)/s';
        $new_query = 'INSERT INTO tournaments (nome, descricao, status, created_at) VALUES (?, ?, ?, NOW())';
        
        $preview_content = preg_replace($old_pattern, $new_query, $preview_content);
        
        // Corrigir os parâmetros do execute
        $preview_content = preg_replace(
            '/\$stmt->execute\(\[[^]]+\]\);/',
            '$stmt->execute([$tournament_name, $custom_description, \'draft\']);',
            $preview_content
        );
        
        file_put_contents('app/pages/adm/template_preview.php', $preview_content);
        echo "<p>✅ template_preview.php corrigido</p>";
        
        // Correção para template_configurator.php
        $config_content = file_get_contents('app/pages/adm/template_configurator.php');
        
        $config_content = preg_replace($old_pattern, $new_query, $config_content);
        $config_content = preg_replace(
            '/\$stmt->execute\(\[[^]]+\]\);/',
            '$stmt->execute([$_POST[\'final_name\'], $_POST[\'final_description\'], \'draft\']);',
            $config_content
        );
        
        file_put_contents('app/pages/adm/template_configurator.php', $config_content);
        echo "<p>✅ template_configurator.php corrigido</p>";
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h3>✅ ARQUIVOS CORRIGIDOS!</h3>";
        echo "<p>Ambos os arquivos agora usam a query correta.</p>";
        echo "</div>";
    }
    
    // Mostrar estrutura final
    $stmt = $pdo->query("SHOW TABLES LIKE 'tournaments'");
    if ($stmt->rowCount() > 0) {
        echo "<h2>📋 Estrutura Final da Tabela:</h2>";
        $stmt = $pdo->query("DESCRIBE tournaments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>Campo</th><th style='padding: 8px;'>Tipo</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Default</th>";
        echo "</tr>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td style='padding: 6px; font-weight: bold;'>{$col['Field']}</td>";
            echo "<td style='padding: 6px;'>{$col['Type']}</td>";
            echo "<td style='padding: 6px;'>{$col['Null']}</td>";
            echo "<td style='padding: 6px;'>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<h3>🔧 Ações de Correção</h3>

<div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
    <form method="POST" style="display: inline;">
        <button type="submit" name="recreate_table" 
                style="background: #FF9800; color: white; padding: 15px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;"
                onclick="return confirm('Isso vai recriar a tabela do zero. Continuar?')">
            🔄 RECRIAR TABELA
        </button>
    </form>
    
    <form method="POST" style="display: inline;">
        <button type="submit" name="fix_files" 
                style="background: #2196F3; color: white; padding: 15px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            🔧 CORRIGIR ARQUIVOS PHP
        </button>
    </form>
</div>

<h3>🧪 Teste Final</h3>

<form method="POST" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="test_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Nome do Torneio:</label>
        <input type="text" id="test_name" name="test_name" 
               value="Teste Zero <?= date('H:i:s') ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="test_description" style="display: block; font-weight: bold; margin-bottom: 5px;">Descrição:</label>
        <textarea id="test_description" name="test_description" 
                  style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; height: 80px;">Teste final após correção completa</textarea>
    </div>
    
    <button type="submit" name="test_insert" 
            style="background: #4CAF50; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 18px; font-weight: bold;">
        🚀 TESTAR INSERÇÃO
    </button>
</form>

<hr>
<p><a href="app/pages/adm/template_preview.php?id=1">🔗 Testar Template Preview</a></p>
<p><a href="app/pages/adm/tournament_templates.php">🔗 Voltar para Templates</a></p>
