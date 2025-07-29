<?php
session_start();

// Configurar sessão de admin para teste
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin_debug';

require_once 'app/config/conexao.php';

echo "<h2>Diagnóstico Completo do Sistema de Logs</h2>";

try {
    $pdo = conectar();
    echo "<p style='color: green;'>✓ Conexão estabelecida</p>";
    
    // 1. Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>✗ Tabela system_logs não existe. Criando...</p>";
        $pdo->exec("
            CREATE TABLE system_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                level ENUM('INFO', 'SUCCESS', 'WARNING', 'ERROR') NOT NULL,
                message TEXT NOT NULL,
                context TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                user_id INT NULL,
                username VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_level (level),
                INDEX idx_created_at (created_at),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✓ Tabela criada</p>";
    } else {
        echo "<p style='color: green;'>✓ Tabela system_logs existe</p>";
    }
    
    // 2. Contar logs existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de logs na tabela: <strong>{$count['total']}</strong></p>";
    
    // 3. Mostrar alguns logs se existirem
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimos 5 logs:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Level</th><th>Message</th><th>Username</th><th>Created At</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['level']}</td>";
            echo "<td>" . htmlspecialchars(substr($log['message'], 0, 50)) . "...</td>";
            echo "<td>{$log['username']}</td>";
            echo "<td>{$log['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Testar a query que a página oficial usa
    echo "<h3>Teste da Query Oficial:</h3>";
    
    $level_filter = '';
    $date_filter = '';
    $limit = 50;
    $page = 1;
    $offset = ($page - 1) * $limit;
    
    $where_conditions = [];
    $params = [];
    
    if ($level_filter) {
        $where_conditions[] = "level = ?";
        $params[] = $level_filter;
    }
    
    if ($date_filter) {
        $where_conditions[] = "DATE(created_at) = ?";
        $params[] = $date_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Contar total
    $count_sql = "SELECT COUNT(*) FROM system_logs $where_clause";
    echo "<p>Query de contagem: <code>$count_sql</code></p>";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_logs = $count_stmt->fetchColumn();
    echo "<p>Total encontrado pela query oficial: <strong>$total_logs</strong></p>";
    
    // Buscar logs
    $sql = "SELECT * FROM system_logs $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    echo "<p>Query de busca: <code>$sql</code></p>";
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    echo "<p>Parâmetros: " . implode(', ', $params) . "</p>";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs_found = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Logs encontrados pela query oficial: <strong>" . count($logs_found) . "</strong></p>";
    
    // 5. Processar ações POST se houver
    if ($_POST && isset($_POST['action'])) {
        echo "<div style='background: #e0f7fa; padding: 15px; margin: 15px 0; border-left: 4px solid #00bcd4;'>";
        echo "<h3>Processando ação: " . htmlspecialchars($_POST['action']) . "</h3>";
        
        switch ($_POST['action']) {
            case 'clear_logs':
                $result = $pdo->exec("DELETE FROM system_logs");
                echo "<p style='color: green;'>✓ Logs limpos. Registros removidos: $result</p>";
                break;
                
            case 'populate_sample_logs':
                $sample_logs = [
                    ['INFO', 'Sistema iniciado com sucesso', 'system'],
                    ['SUCCESS', 'Usuário admin fez login no sistema', 'admin'],
                    ['SUCCESS', 'Torneio criado com sucesso', 'admin'],
                    ['WARNING', 'Tentativa de acesso negada', 'unknown'],
                    ['ERROR', 'Erro na conexão com banco de dados', 'system'],
                    ['INFO', 'Backup automático executado', 'system'],
                    ['SUCCESS', 'Otimização do banco concluída', 'admin'],
                    ['WARNING', 'Uso de memória acima de 80%', 'system'],
                    ['INFO', 'Cache do sistema limpo', 'admin'],
                    ['ERROR', 'Falha ao enviar email', 'system'],
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO system_logs (level, message, ip_address, user_agent, username, created_at)
                    VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE))
                ");
                
                $inserted = 0;
                foreach ($sample_logs as $index => $log) {
                    $minutes_ago = rand(1, 1440);
                    $result = $stmt->execute([
                        $log[0],
                        $log[1],
                        '192.168.1.' . rand(100, 200),
                        'Mozilla/5.0 (Test Generator)',
                        $log[2],
                        $minutes_ago
                    ]);
                    if ($result) $inserted++;
                }
                
                echo "<p style='color: green;'>✓ $inserted logs de exemplo adicionados</p>";
                break;
        }
        echo "</div>";
        
        // Recarregar a página para mostrar os resultados
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}
?>

<hr>
<h3>Testar Funcionalidades:</h3>

<form method="POST" style="display: inline; margin: 10px;">
    <input type="hidden" name="action" value="populate_sample_logs">
    <button type="submit" style="padding: 15px 25px; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px;">
        ➕ Adicionar Logs de Exemplo
    </button>
</form>

<form method="POST" style="display: inline; margin: 10px;">
    <input type="hidden" name="action" value="clear_logs">
    <button type="submit" style="padding: 15px 25px; background: #F44336; color: white; border: none; border-radius: 5px; font-size: 16px;">
        🗑️ Limpar Todos os Logs
    </button>
</form>

<hr>
<p><a href="app/pages/adm/system_logs.php" target="_blank">🔗 Abrir página oficial de logs</a></p>
<p><a href="app/pages/adm/login_simple.php" target="_blank">🔑 Fazer login como admin</a></p>
