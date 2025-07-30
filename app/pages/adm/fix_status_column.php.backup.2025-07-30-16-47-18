<?php
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>🔧 Correção da Coluna Status</h2>";
    
    // Verificar estrutura atual
    echo "<h3>📋 Estrutura Atual:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM tournaments LIKE 'status'");
    $currentColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentColumn) {
        echo "<p><strong>Tipo atual:</strong> {$currentColumn['Type']}</p>";
        echo "<p><strong>Default atual:</strong> {$currentColumn['Default']}</p>";
    } else {
        echo "<p style='color: red;'>❌ Coluna status não encontrada!</p>";
        exit;
    }
    
    // Verificar valores atuais
    echo "<h3>📊 Valores Atuais:</h3>";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tournaments GROUP BY status");
    $currentValues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($currentValues)) {
        echo "<ul>";
        foreach ($currentValues as $value) {
            echo "<li><strong>{$value['status']}</strong>: {$value['count']} registro(s)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nenhum registro encontrado.</p>";
    }
    
    // Aplicar correção se solicitado
    if (isset($_GET['action']) && $_GET['action'] === 'fix') {
        echo "<h3>🔧 Aplicando Correção...</h3>";
        
        try {
            // Primeiro, vamos verificar se há valores inválidos e corrigi-los
            echo "<p>1. Verificando e corrigindo valores inválidos...</p>";
            
            // Mapear valores incorretos para corretos
            $corrections = [
                'rascunho' => 'draft',
                'ativo' => 'active',
                'finalizado' => 'completed',
                'cancelado' => 'cancelled'
            ];
            
            foreach ($corrections as $wrong => $correct) {
                $stmt = $pdo->prepare("UPDATE tournaments SET status = ? WHERE status = ?");
                $result = $stmt->execute([$correct, $wrong]);
                if ($stmt->rowCount() > 0) {
                    echo "<p style='color: orange;'>⚠️ Corrigido {$stmt->rowCount()} registro(s) de '$wrong' para '$correct'</p>";
                }
            }
            
            echo "<p>2. Alterando estrutura da coluna...</p>";
            
            // Alterar a coluna para aceitar apenas valores válidos
            $pdo->exec("ALTER TABLE tournaments MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft'");
            
            echo "<p style='color: green; font-weight: bold;'>✅ Coluna status corrigida com sucesso!</p>";
            
            // Verificar estrutura após correção
            echo "<h3>📋 Nova Estrutura:</h3>";
            $stmt = $pdo->query("SHOW COLUMNS FROM tournaments LIKE 'status'");
            $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newColumn) {
                echo "<p><strong>Novo tipo:</strong> {$newColumn['Type']}</p>";
                echo "<p><strong>Novo default:</strong> {$newColumn['Default']}</p>";
            }
            
            // Testar inserção
            echo "<h3>🧪 Teste de Inserção:</h3>";
            $testValues = ['draft', 'active', 'completed', 'cancelled'];
            
            foreach ($testValues as $testValue) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tournaments (name, year, status) VALUES (?, ?, ?)");
                    $result = $stmt->execute(["Teste $testValue", 2024, $testValue]);
                    
                    if ($result) {
                        $id = $pdo->lastInsertId();
                        echo "<p style='color: green;'>✅ '$testValue' - OK</p>";
                        
                        // Remover o teste
                        $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$id]);
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ '$testValue' - Erro: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<p style='color: green; font-weight: bold; font-size: 1.2em; margin-top: 20px;'>🎉 Correção concluída! Agora você pode usar os status sem erro.</p>";
            echo "<p><a href='tournament_list.php' style='background: #7B1FA2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Voltar para Lista de Torneios</a></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red; font-weight: bold;'>❌ Erro durante a correção: " . $e->getMessage() . "</p>";
        }
        
    } else {
        // Mostrar botão para aplicar correção
        echo "<h3>🔧 Correção Necessária:</h3>";
        echo "<p>A coluna status precisa ser corrigida para aceitar apenas os valores válidos:</p>";
        echo "<ul>";
        echo "<li><strong>draft</strong> (Rascunho)</li>";
        echo "<li><strong>active</strong> (Ativo)</li>";
        echo "<li><strong>completed</strong> (Finalizado)</li>";
        echo "<li><strong>cancelled</strong> (Cancelado)</li>";
        echo "</ul>";
        
        echo "<div style='background: #2a2a2a; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<p><strong>O que será feito:</strong></p>";
        echo "<ol>";
        echo "<li>Corrigir valores inválidos existentes no banco</li>";
        echo "<li>Alterar a estrutura da coluna para ENUM com valores corretos</li>";
        echo "<li>Testar a inserção dos novos valores</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<p><a href='?action=fix' style='background: #7B1FA2; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🔧 Aplicar Correção</a></p>";
        
        echo "<p style='color: orange; margin-top: 20px;'><strong>⚠️ Aviso:</strong> Esta operação irá modificar a estrutura do banco de dados. Certifique-se de ter um backup.</p>";
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
    max-width: 800px;
    margin: 0 auto;
}

h2, h3 {
    color: #E1BEE7;
}

ul, ol {
    line-height: 1.6;
}

a {
    display: inline-block;
    margin: 10px 5px;
}

.warning {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid #ffc107;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}
</style>
