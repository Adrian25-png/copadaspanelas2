<?php
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>🔧 Correção Final da Coluna Status</h2>";
    
    // Verificar estrutura atual
    echo "<h3>📋 Estrutura Atual:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM tournaments LIKE 'status'");
    $currentColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentColumn) {
        echo "<p><strong>Tipo atual:</strong> {$currentColumn['Type']}</p>";
        echo "<p><strong>Default atual:</strong> {$currentColumn['Default']}</p>";
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
    }
    
    // Aplicar correção se solicitado
    if (isset($_GET['action']) && $_GET['action'] === 'fix') {
        echo "<h3>🔧 Aplicando Correção Final...</h3>";
        
        try {
            echo "<p>1. Corrigindo valores inválidos...</p>";
            
            // Mapear valores incorretos para corretos
            $corrections = [
                'setup' => 'draft',
                'rascunho' => 'draft',
                'ativo' => 'active',
                'finalizado' => 'completed',
                'cancelado' => 'cancelled',
                'arquivado' => 'completed' // archived -> completed
            ];
            
            foreach ($corrections as $wrong => $correct) {
                $stmt = $pdo->prepare("UPDATE tournaments SET status = ? WHERE status = ?");
                $result = $stmt->execute([$correct, $wrong]);
                if ($stmt->rowCount() > 0) {
                    echo "<p style='color: orange;'>⚠️ Corrigido {$stmt->rowCount()} registro(s) de '$wrong' para '$correct'</p>";
                }
            }
            
            echo "<p>2. Alterando estrutura da coluna para incluir todos os valores necessários...</p>";
            
            // Alterar a coluna para aceitar todos os valores necessários
            $pdo->exec("ALTER TABLE tournaments MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'cancelled', 'archived') DEFAULT 'draft'");
            
            echo "<p style='color: green; font-weight: bold;'>✅ Coluna status corrigida com sucesso!</p>";
            
            // Verificar estrutura após correção
            echo "<h3>📋 Nova Estrutura:</h3>";
            $stmt = $pdo->query("SHOW COLUMNS FROM tournaments LIKE 'status'");
            $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newColumn) {
                echo "<p><strong>Novo tipo:</strong> {$newColumn['Type']}</p>";
                echo "<p><strong>Novo default:</strong> {$newColumn['Default']}</p>";
            }
            
            // Testar inserção de todos os valores
            echo "<h3>🧪 Teste de Inserção de Todos os Valores:</h3>";
            $testValues = ['draft', 'active', 'completed', 'cancelled', 'archived'];
            
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
            
            echo "<div style='background: #2a2a2a; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h4 style='color: #00ff00;'>🎉 Correção Concluída!</h4>";
            echo "<p><strong>Valores aceitos agora:</strong></p>";
            echo "<ul>";
            echo "<li><strong>draft</strong> - Rascunho (padrão para novos torneios)</li>";
            echo "<li><strong>active</strong> - Ativo (torneio em andamento)</li>";
            echo "<li><strong>completed</strong> - Finalizado</li>";
            echo "<li><strong>cancelled</strong> - Cancelado</li>";
            echo "<li><strong>archived</strong> - Arquivado (usado pelo sistema)</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<p><a href='create_tournament.php' style='background: #7B1FA2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🆕 Criar Torneio</a>";
            echo "<a href='tournament_list.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Lista de Torneios</a></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red; font-weight: bold;'>❌ Erro durante a correção: " . $e->getMessage() . "</p>";
        }
        
    } else {
        // Mostrar botão para aplicar correção
        echo "<h3>🔧 Correção Necessária:</h3>";
        echo "<p>A coluna status precisa ser atualizada para aceitar todos os valores usados pelo sistema:</p>";
        
        echo "<div style='background: #2a2a2a; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<p><strong>Valores necessários:</strong></p>";
        echo "<ul>";
        echo "<li><strong>draft</strong> - Rascunho (usado na criação)</li>";
        echo "<li><strong>active</strong> - Ativo</li>";
        echo "<li><strong>completed</strong> - Finalizado</li>";
        echo "<li><strong>cancelled</strong> - Cancelado</li>";
        echo "<li><strong>archived</strong> - Arquivado (usado pelo TournamentManager)</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<p><strong>⚠️ Problema identificado:</strong></p>";
        echo "<p>O sistema está tentando usar valores como 'setup' e 'archived' que não estão na estrutura ENUM atual.</p>";
        echo "</div>";
        
        echo "<p><a href='?action=fix' style='background: #7B1FA2; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🔧 Aplicar Correção Final</a></p>";
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
    max-width: 900px;
    margin: 0 auto;
}

h2, h3, h4 {
    color: #E1BEE7;
}

ul, ol {
    line-height: 1.6;
}

a {
    display: inline-block;
    margin: 10px 5px;
}

code {
    background: #333;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
