<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>🔍 Verificação da Coluna Status</h2>";
    
    // Verificar estrutura da tabela tournaments
    echo "<h3>📋 Estrutura da Tabela tournaments:</h3>";
    $stmt = $pdo->query("DESCRIBE tournaments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #333; color: white;'>";
    echo "<th style='padding: 10px;'>Campo</th>";
    echo "<th style='padding: 10px;'>Tipo</th>";
    echo "<th style='padding: 10px;'>Null</th>";
    echo "<th style='padding: 10px;'>Key</th>";
    echo "<th style='padding: 10px;'>Default</th>";
    echo "<th style='padding: 10px;'>Extra</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$column['Field']}</td>";
        echo "<td style='padding: 8px; font-weight: bold;'>{$column['Type']}</td>";
        echo "<td style='padding: 8px;'>{$column['Null']}</td>";
        echo "<td style='padding: 8px;'>{$column['Key']}</td>";
        echo "<td style='padding: 8px;'>{$column['Default']}</td>";
        echo "<td style='padding: 8px;'>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar valores únicos na coluna status
    echo "<h3>📊 Valores Atuais na Coluna Status:</h3>";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tournaments GROUP BY status");
    $statusValues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($statusValues)) {
        echo "<p>Nenhum registro encontrado na tabela tournaments.</p>";
    } else {
        echo "<ul>";
        foreach ($statusValues as $status) {
            echo "<li><strong>{$status['status']}</strong>: {$status['count']} registro(s)</li>";
        }
        echo "</ul>";
    }
    
    // Tentar inserir valores problemáticos para testar
    echo "<h3>🧪 Teste de Inserção de Valores:</h3>";
    
    $testValues = ['rascunho', 'cancelado', 'draft', 'cancelled', 'active', 'setup'];
    
    foreach ($testValues as $testValue) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tournaments (name, year, status) VALUES (?, ?, ?)");
            $result = $stmt->execute(["Teste $testValue", 2024, $testValue]);
            
            if ($result) {
                $id = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ '$testValue' - Inserido com sucesso (ID: $id)</p>";
                
                // Remover o teste
                $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$id]);
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ '$testValue' - Erro: " . $e->getMessage() . "</p>";
        }
    }
    
    // Propor correção
    echo "<h3>🔧 Correção Sugerida:</h3>";
    echo "<p>Para corrigir o problema, precisamos alterar a coluna status para aceitar os valores corretos:</p>";
    
    echo "<div style='background: #2a2a2a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<code style='color: #00ff00;'>";
    echo "ALTER TABLE tournaments MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'cancelled', 'rascunho', 'cancelado') DEFAULT 'draft';";
    echo "</code>";
    echo "</div>";
    
    echo "<p>Ou mapear os valores no código PHP:</p>";
    echo "<ul>";
    echo "<li>'rascunho' → 'draft'</li>";
    echo "<li>'cancelado' → 'cancelled'</li>";
    echo "</ul>";
    
    // Botão para aplicar correção
    if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
        echo "<h3>🔧 Aplicando Correção...</h3>";
        try {
            $pdo->exec("ALTER TABLE tournaments MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'cancelled', 'rascunho', 'cancelado') DEFAULT 'draft'");
            echo "<p style='color: green; font-weight: bold;'>✅ Coluna status corrigida com sucesso!</p>";
            echo "<script>setTimeout(() => window.location.href = 'check_status_column.php', 2000);</script>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro ao corrigir: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><a href='?fix=true' style='background: #7B1FA2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Aplicar Correção</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    background: #1a1a1a;
    color: white;
    padding: 20px;
}

table {
    background: #2a2a2a;
    color: white;
}

th {
    background: #333 !important;
}

tr:nth-child(even) {
    background: #333;
}

code {
    font-family: 'Courier New', monospace;
    font-size: 14px;
}
</style>
