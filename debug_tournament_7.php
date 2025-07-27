<?php
/**
 * Debug específico para tournament_id=7
 */

echo "<h1>🔍 Debug Tournament ID = 7</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";

try {
    require_once 'app/config/conexao.php';
    require_once 'app/classes/TournamentManager.php';

    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    echo "<h2>1. Verificando Tournament ID = 7</h2>";
    
    // Verificar se o torneio existe
    $tournament = $tournamentManager->getTournamentById(7);
    if ($tournament) {
        echo "<p class='success'>✅ Torneio ID 7 encontrado</p>";
        echo "<p class='info'>📋 Nome: " . htmlspecialchars($tournament['name']) . "</p>";
        echo "<p class='info'>📊 Status: " . $tournament['status'] . "</p>";
        echo "<p class='info'>📅 Ano: " . $tournament['year'] . "</p>";
    } else {
        echo "<p class='error'>❌ Torneio ID 7 NÃO encontrado</p>";
    }
    
    echo "<h2>2. Listando Todos os Torneios</h2>";
    
    $tournaments = $tournamentManager->getAllTournaments();
    if (!empty($tournaments)) {
        echo "<p class='success'>✅ Total de torneios: " . count($tournaments) . "</p>";
        
        echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>Ano</th><th>Teste Match Manager</th>";
        echo "</tr>";
        
        foreach ($tournaments as $t) {
            $highlight = ($t['id'] == 7) ? "style='background:#ffeb3b;'" : "";
            echo "<tr $highlight>";
            echo "<td>" . $t['id'] . "</td>";
            echo "<td>" . htmlspecialchars($t['name']) . "</td>";
            echo "<td>" . $t['status'] . "</td>";
            echo "<td>" . $t['year'] . "</td>";
            echo "<td><a href='app/pages/adm/match_manager.php?tournament_id=" . $t['id'] . "' target='_blank'>Testar</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p class='warning'>⚠️ Nenhum torneio encontrado</p>";
    }
    
    echo "<h2>3. Testando Acesso ao Match Manager</h2>";
    
    // Simular acesso ao match_manager.php
    $_GET['tournament_id'] = 7;
    
    echo "<p class='info'>🧪 Simulando acesso com tournament_id=7...</p>";
    
    // Verificar se o arquivo existe
    $file_path = 'app/pages/adm/match_manager.php';
    if (file_exists($file_path)) {
        echo "<p class='success'>✅ Arquivo match_manager.php existe</p>";
        
        // Tentar incluir o arquivo
        ob_start();
        $error_occurred = false;
        
        try {
            include $file_path;
        } catch (Exception $e) {
            $error_occurred = true;
            echo "<p class='error'>❌ Erro na inclusão: " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            $error_occurred = true;
            echo "<p class='error'>❌ Erro fatal: " . $e->getMessage() . "</p>";
        }
        
        $output = ob_get_clean();
        
        if (!$error_occurred) {
            if (!empty($output)) {
                echo "<p class='success'>✅ Arquivo executado com sucesso</p>";
                echo "<p class='info'>📄 Output gerado: " . strlen($output) . " bytes</p>";
                
                // Verificar se é HTML válido
                if (strpos($output, '<html') !== false) {
                    echo "<p class='success'>✅ HTML válido gerado</p>";
                } else {
                    echo "<p class='warning'>⚠️ Output não parece ser HTML completo</p>";
                    echo "<p class='info'>🔍 Primeiros 200 caracteres do output:</p>";
                    echo "<pre>" . htmlspecialchars(substr($output, 0, 200)) . "...</pre>";
                }
            } else {
                echo "<p class='warning'>⚠️ Arquivo executado mas sem output (possível redirecionamento)</p>";
            }
        }
        
    } else {
        echo "<p class='error'>❌ Arquivo match_manager.php não encontrado</p>";
    }
    
    echo "<h2>4. Verificando Estrutura do Banco para Tournament 7</h2>";
    
    if ($tournament) {
        // Verificar grupos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE tournament_id = ?");
        $stmt->execute([7]);
        $group_count = $stmt->fetchColumn();
        echo "<p class='info'>👥 Grupos no torneio 7: $group_count</p>";
        
        // Verificar times
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE tournament_id = ?");
        $stmt->execute([7]);
        $team_count = $stmt->fetchColumn();
        echo "<p class='info'>⚽ Times no torneio 7: $team_count</p>";
        
        // Verificar jogos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogos WHERE tournament_id = ?");
        $stmt->execute([7]);
        $match_count = $stmt->fetchColumn();
        echo "<p class='info'>🏟️ Jogos no torneio 7: $match_count</p>";
        
        if ($group_count == 0) {
            echo "<p class='warning'>⚠️ Torneio 7 não tem grupos cadastrados</p>";
        }
        if ($team_count == 0) {
            echo "<p class='warning'>⚠️ Torneio 7 não tem times cadastrados</p>";
        }
    }
    
    echo "<h2>5. Teste Manual Direto</h2>";
    
    echo "<div style='background:#f8f9fa; padding:20px; border-radius:8px; margin:10px 0;'>";
    echo "<h4>🔗 Links de Teste Direto:</h4>";
    
    // Testar com diferentes IDs
    $test_ids = [1, 2, 3, 7];
    foreach ($test_ids as $id) {
        $url = "app/pages/adm/match_manager.php?tournament_id=$id";
        echo "<p><a href='$url' target='_blank' style='background:#3498db;color:white;padding:8px 16px;text-decoration:none;border-radius:4px; margin:5px;'>Testar ID $id</a></p>";
    }
    
    echo "</div>";
    
    echo "<h2>6. Verificando Logs de Erro</h2>";
    
    // Verificar se há erros no log
    $error_log_paths = [
        '/opt/lampp/logs/error_log',
        '/var/log/apache2/error.log',
        'error.log'
    ];
    
    echo "<p class='info'>🔍 Verificando logs de erro:</p>";
    foreach ($error_log_paths as $log_path) {
        if (file_exists($log_path)) {
            echo "<p class='success'>✅ Log encontrado: $log_path</p>";
            
            // Ler últimas linhas do log
            $lines = file($log_path);
            if ($lines) {
                $recent_lines = array_slice($lines, -10);
                echo "<p class='info'>📄 Últimas 10 linhas do log:</p>";
                echo "<pre style='background:#f0f0f0; padding:10px; border-radius:5px; font-size:12px;'>";
                foreach ($recent_lines as $line) {
                    echo htmlspecialchars($line);
                }
                echo "</pre>";
            }
            break;
        } else {
            echo "<p class='info'>❌ Log não encontrado: $log_path</p>";
        }
    }
    
    echo "<div style='background:#e8f5e8;padding:20px;border-radius:10px;margin:20px 0;'>";
    echo "<h3>🎯 DIAGNÓSTICO PARA TOURNAMENT ID = 7</h3>";
    
    if ($tournament) {
        echo "<p><strong>✅ Torneio existe:</strong> ID 7 está no banco de dados</p>";
        echo "<p><strong>📋 Nome:</strong> " . htmlspecialchars($tournament['name']) . "</p>";
        echo "<p><strong>📊 Status:</strong> " . $tournament['status'] . "</p>";
    } else {
        echo "<p><strong>❌ Torneio não existe:</strong> ID 7 não está no banco de dados</p>";
        echo "<p><strong>🔧 Solução:</strong> Use um ID de torneio válido da tabela acima</p>";
    }
    
    echo "</div>";
    
    echo "<h3>📋 Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Teste os links acima para diferentes IDs</li>";
    echo "<li>Se ID 7 não existir, use um ID válido da tabela</li>";
    echo "<li>Verifique se há erros no console do navegador (F12)</li>";
    echo "<li>Tente limpar cache do navegador (Ctrl+F5)</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro crítico: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><small>Debug executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
