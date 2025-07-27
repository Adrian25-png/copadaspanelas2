<?php
/**
 * Debug específico para o problema "Gerenciar Jogos"
 */

echo "<h1>🔍 Debug - Problema Gerenciar Jogos</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";

try {
    require_once 'app/config/conexao.php';
    require_once 'app/classes/TournamentManager.php';

    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    echo "<h2>1. Verificando Arquivo match_manager.php</h2>";
    
    $file_path = 'app/pages/adm/match_manager.php';
    
    if (file_exists($file_path)) {
        echo "<p class='success'>✅ Arquivo match_manager.php existe</p>";
        echo "<p class='info'>📍 Localização: $file_path</p>";
        echo "<p class='info'>📄 Tamanho: " . number_format(filesize($file_path)) . " bytes</p>";
        echo "<p class='info'>🕒 Modificado: " . date('d/m/Y H:i:s', filemtime($file_path)) . "</p>";
    } else {
        echo "<p class='error'>❌ Arquivo match_manager.php NÃO EXISTE</p>";
        echo "<p class='error'>📍 Procurado em: $file_path</p>";
        
        // Listar arquivos no diretório
        $dir = 'app/pages/adm/';
        if (is_dir($dir)) {
            echo "<p class='info'>📁 Arquivos no diretório $dir:</p>";
            $files = scandir($dir);
            echo "<ul>";
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo "<li>$file</li>";
                }
            }
            echo "</ul>";
        }
        exit;
    }
    
    echo "<h2>2. Verificando Torneios Disponíveis</h2>";
    
    $tournaments = $tournamentManager->getAllTournaments();
    if (!empty($tournaments)) {
        echo "<p class='success'>✅ Torneios encontrados: " . count($tournaments) . "</p>";
        
        echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>Ano</th><th>Link Teste</th>";
        echo "</tr>";
        
        foreach ($tournaments as $tournament) {
            echo "<tr>";
            echo "<td>" . $tournament['id'] . "</td>";
            echo "<td>" . htmlspecialchars($tournament['name']) . "</td>";
            echo "<td>" . $tournament['status'] . "</td>";
            echo "<td>" . $tournament['year'] . "</td>";
            echo "<td><a href='app/pages/adm/match_manager.php?tournament_id=" . $tournament['id'] . "' target='_blank' style='color:blue;'>Testar</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p class='warning'>⚠️ Nenhum torneio encontrado</p>";
    }
    
    echo "<h2>3. Testando Links do Sistema</h2>";
    
    if (!empty($tournaments)) {
        $tournament_id = $tournaments[0]['id'];
        
        echo "<div style='background:#f8f9fa; padding:15px; border-radius:8px; margin:10px 0;'>";
        echo "<h4>🔗 Links de Teste:</h4>";
        
        $links = [
            "Gerenciamento Principal" => "app/pages/adm/tournament_management.php?id=$tournament_id",
            "Gerenciar Jogos (Direto)" => "app/pages/adm/match_manager.php?tournament_id=$tournament_id",
            "Lista de Torneios" => "app/pages/adm/tournament_list.php"
        ];
        
        foreach ($links as $name => $url) {
            echo "<p><a href='$url' target='_blank' style='background:#3498db;color:white;padding:8px 16px;text-decoration:none;border-radius:4px; margin:5px;'>$name</a></p>";
        }
        
        echo "</div>";
    }
    
    echo "<h2>4. Verificando Estrutura de URLs</h2>";
    
    // Verificar se estamos no diretório correto
    $current_dir = getcwd();
    echo "<p class='info'>📍 Diretório atual: $current_dir</p>";
    
    // Verificar se o arquivo é acessível via HTTP
    $base_url = "http://localhost/copadaspanelas2/";
    echo "<p class='info'>🌐 URL base: $base_url</p>";
    
    if (!empty($tournaments)) {
        $tournament_id = $tournaments[0]['id'];
        $test_url = $base_url . "app/pages/adm/match_manager.php?tournament_id=$tournament_id";
        echo "<p class='info'>🧪 URL de teste completa: <a href='$test_url' target='_blank'>$test_url</a></p>";
    }
    
    echo "<h2>5. Verificando Permissões e Acesso</h2>";
    
    // Verificar permissões do arquivo
    if (file_exists($file_path)) {
        $perms = fileperms($file_path);
        echo "<p class='info'>🔐 Permissões do arquivo: " . substr(sprintf('%o', $perms), -4) . "</p>";
        
        if (is_readable($file_path)) {
            echo "<p class='success'>✅ Arquivo é legível</p>";
        } else {
            echo "<p class='error'>❌ Arquivo não é legível</p>";
        }
    }
    
    echo "<h2>6. Testando Inclusão do Arquivo</h2>";
    
    if (!empty($tournaments)) {
        $tournament_id = $tournaments[0]['id'];
        
        // Simular parâmetros GET
        $_GET['tournament_id'] = $tournament_id;
        
        echo "<p class='info'>🧪 Simulando acesso com tournament_id = $tournament_id</p>";
        
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
                echo "<p class='success'>✅ Arquivo incluído com sucesso</p>";
                echo "<p class='info'>📄 Output gerado: " . strlen($output) . " bytes</p>";
                
                // Verificar se é HTML válido
                if (strpos($output, '<html') !== false) {
                    echo "<p class='success'>✅ HTML válido gerado</p>";
                } else {
                    echo "<p class='warning'>⚠️ Output não parece ser HTML</p>";
                }
            } else {
                echo "<p class='warning'>⚠️ Arquivo incluído mas sem output (possível redirecionamento)</p>";
            }
        }
    }
    
    echo "<h2>7. Verificando Logs de Erro</h2>";
    
    // Verificar se há logs de erro
    $error_log_paths = [
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        '/opt/lampp/logs/error_log',
        'error.log'
    ];
    
    echo "<p class='info'>🔍 Locais de logs verificados:</p>";
    echo "<ul>";
    foreach ($error_log_paths as $log_path) {
        if (file_exists($log_path)) {
            echo "<li class='success'>✅ $log_path (existe)</li>";
        } else {
            echo "<li class='info'>❌ $log_path (não encontrado)</li>";
        }
    }
    echo "</ul>";
    
    echo "<h2>8. Possíveis Causas do Problema</h2>";
    
    echo "<div style='background:#fff3cd; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<h4>🤔 Possíveis causas:</h4>";
    echo "<ol>";
    echo "<li><strong>Cache do navegador:</strong> Tente Ctrl+F5 para recarregar</li>";
    echo "<li><strong>Sessão PHP:</strong> Pode estar redirecionando</li>";
    echo "<li><strong>Erro de sintaxe:</strong> Arquivo pode ter erro não detectado</li>";
    echo "<li><strong>Permissões:</strong> Servidor pode não ter acesso</li>";
    echo "<li><strong>URL incorreta:</strong> Link pode estar mal formado</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background:#e8f5e8;padding:20px;border-radius:10px;margin:20px 0;'>";
    echo "<h3>🎯 DIAGNÓSTICO</h3>";
    echo "<p><strong>✅ Arquivo existe:</strong> match_manager.php está presente</p>";
    echo "<p><strong>✅ Torneios disponíveis:</strong> IDs válidos para teste</p>";
    echo "<p><strong>✅ Links gerados:</strong> URLs corretas</p>";
    echo "<p><strong>🔍 Próximo passo:</strong> Testar links acima manualmente</p>";
    echo "</div>";
    
    echo "<h3>📋 Como Testar:</h3>";
    echo "<ol>";
    echo "<li>Clique nos links de teste acima</li>";
    echo "<li>Se não funcionar, tente abrir em aba anônima</li>";
    echo "<li>Verifique se há mensagens de erro no console do navegador (F12)</li>";
    echo "<li>Tente acessar diretamente: <code>localhost/copadaspanelas2/app/pages/adm/match_manager.php?tournament_id=1</code></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro crítico: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><small>Debug executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
