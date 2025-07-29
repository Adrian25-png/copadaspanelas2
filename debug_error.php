<?php
session_start();

// Configurar sessão de admin
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin_debug';

require_once 'app/config/conexao.php';

echo "<h1>🔍 Debug do Erro - Template Preview</h1>";

try {
    $pdo = conectar();
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";
    
    // Verificar estrutura da tabela
    echo "<h2>📋 Estrutura da Tabela Tournaments:</h2>";
    $stmt = $pdo->query("DESCRIBE tournaments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>Campo</th><th style='padding: 8px;'>Tipo</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Default</th>";
    echo "</tr>";
    
    foreach ($columns as $col) {
        $is_required = ($col['Null'] == 'NO' && $col['Default'] === null && $col['Extra'] != 'auto_increment');
        $bg_color = $is_required ? '#ffebee' : 'white';
        
        echo "<tr style='background: $bg_color;'>";
        echo "<td style='padding: 6px; font-weight: bold;'>{$col['Field']}</td>";
        echo "<td style='padding: 6px;'>{$col['Type']}</td>";
        echo "<td style='padding: 6px;'>{$col['Null']}</td>";
        echo "<td style='padding: 6px;'>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Simular exatamente o que o template_preview.php faz
    echo "<h2>🧪 Simulando Template Preview</h2>";
    
    if ($_POST && isset($_POST['create_tournament'])) {
        echo "<div style='background: #e3f2fd; padding: 15px; margin: 15px 0; border: 2px solid #2196f3; border-radius: 5px;'>";
        echo "<h3>🔄 Processando criação do torneio...</h3>";
        
        $tournament_name = $_POST['tournament_name'];
        $custom_teams = $_POST['custom_teams'] ?? 8;
        $custom_groups = $_POST['custom_groups'] ?? 2;
        $custom_description = $_POST['custom_description'] ?? 'Torneio criado via template';
        
        echo "<p><strong>Nome:</strong> " . htmlspecialchars($tournament_name) . "</p>";
        echo "<p><strong>Descrição:</strong> " . htmlspecialchars($custom_description) . "</p>";
        
        try {
            // Verificar se a tabela existe
            $pdo->query("SELECT 1 FROM tournaments LIMIT 1");
            echo "<p style='color: green;'>✅ Tabela tournaments acessível</p>";

            // Tentar a query exata do template_preview.php
            echo "<h4>🔧 Executando query:</h4>";
            echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 3px;'>";
            echo "INSERT INTO tournaments (nome, descricao, status, created_at)\n";
            echo "VALUES (?, ?, ?, NOW())";
            echo "</pre>";
            
            $stmt = $pdo->prepare("
                INSERT INTO tournaments (nome, descricao, status, created_at)
                VALUES (?, ?, ?, NOW())
            ");

            echo "<p><strong>Parâmetros:</strong></p>";
            echo "<ul>";
            echo "<li>nome: '" . htmlspecialchars($tournament_name) . "'</li>";
            echo "<li>descricao: '" . htmlspecialchars($custom_description) . "'</li>";
            echo "<li>status: 'draft'</li>";
            echo "</ul>";

            $result = $stmt->execute([
                $tournament_name,
                $custom_description,
                'draft'
            ]);

            if ($result) {
                $tournament_id = $pdo->lastInsertId();
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
                echo "<h3>🎉 SUCESSO!</h3>";
                echo "<p><strong>ID do torneio criado:</strong> $tournament_id</p>";
                echo "<p><strong>Nome:</strong> " . htmlspecialchars($tournament_name) . "</p>";
                echo "</div>";
                
                // Verificar dados inseridos
                $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
                $stmt->execute([$tournament_id]);
                $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<h4>📋 Dados Inseridos:</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                foreach ($tournament as $key => $value) {
                    echo "<tr>";
                    echo "<td style='padding: 6px; font-weight: bold;'>$key</td>";
                    echo "<td style='padding: 6px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Simular redirecionamento
                echo "<p style='color: blue;'>🔄 Normalmente redirecionaria para: edit_tournament_simple.php?id=$tournament_id&created=1</p>";
                
                // Remover o teste
                $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tournament_id]);
                echo "<p style='color: orange;'>🗑️ Registro de teste removido</p>";
                
            } else {
                echo "<p style='color: red;'>❌ Falha na inserção (execute retornou false)</p>";
            }

        } catch (Exception $e) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h3>❌ ERRO CAPTURADO:</h3>";
            echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
            echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
            
            // Análise específica do erro
            if (strpos($e->getMessage(), "doesn't have a default value") !== false) {
                preg_match("/Field '([^']+)'/", $e->getMessage(), $matches);
                if (isset($matches[1])) {
                    $missing_field = $matches[1];
                    echo "<div style='background: #fff3e0; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
                    echo "<h4>🔍 Análise do Erro:</h4>";
                    echo "<p>O campo <strong>'$missing_field'</strong> existe na tabela mas não está sendo fornecido na query.</p>";
                    echo "<p>Isso significa que a tabela tem um campo '$missing_field' obrigatório que não estamos incluindo.</p>";
                    
                    // Verificar se o campo existe na estrutura
                    $field_exists = false;
                    foreach ($columns as $col) {
                        if ($col['Field'] == $missing_field) {
                            $field_exists = true;
                            echo "<p><strong>Campo '$missing_field':</strong> {$col['Type']}, Null: {$col['Null']}, Default: " . ($col['Default'] ?? 'NULL') . "</p>";
                            break;
                        }
                    }
                    
                    if (!$field_exists) {
                        echo "<p style='color: red;'>⚠️ Campo '$missing_field' não encontrado na estrutura atual!</p>";
                    }
                    echo "</div>";
                }
            }
            echo "</div>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>❌ ERRO DE CONEXÃO:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<hr>
<h3>🧪 Formulário de Teste (Igual ao Template Preview)</h3>

<form method="POST" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="tournament_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Nome do Torneio *</label>
        <input type="text" id="tournament_name" name="tournament_name" 
               value="Copa Simples <?= date('Y') ?>" 
               style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 8px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="custom_teams" style="display: block; font-weight: bold; margin-bottom: 5px;">Número de Times</label>
        <input type="number" id="custom_teams" name="custom_teams" 
               value="8" min="4" max="32"
               style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 8px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="custom_groups" style="display: block; font-weight: bold; margin-bottom: 5px;">Número de Grupos</label>
        <input type="number" id="custom_groups" name="custom_groups" 
               value="2" min="0" max="8"
               style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 8px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="custom_description" style="display: block; font-weight: bold; margin-bottom: 5px;">Descrição</label>
        <textarea id="custom_description" name="custom_description" 
                  style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 8px; height: 80px;">Torneio com fase de grupos seguida de eliminatórias</textarea>
    </div>
    
    <button type="submit" name="create_tournament" 
            style="background: #4CAF50; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 18px; font-weight: bold;">
        🚀 CRIAR TORNEIO (TESTE)
    </button>
</form>

<hr>
<p><a href="app/pages/adm/template_preview.php?id=1">🔗 Ir para Template Preview Real</a></p>
<p><a href="fix_tournaments_zero.php">🔗 Voltar para Correção</a></p>
