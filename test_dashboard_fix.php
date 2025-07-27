<?php
/**
 * Teste específico para verificar se o erro SQL foi corrigido
 */

echo "<h1>🧪 Teste de Correção do Dashboard</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "<h2>1. Carregando Classes</h2>";
    
    require_once 'app/config/conexao.php';
    echo "<p class='success'>✅ Conexão carregada</p>";
    
    require_once 'app/classes/TournamentManager.php';
    echo "<p class='success'>✅ TournamentManager carregado</p>";
    
    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    echo "<p class='success'>✅ TournamentManager instanciado</p>";
    
    echo "<h2>2. Testando Métodos Específicos</h2>";
    
    // Verificar se há torneios
    $tournaments = $tournamentManager->getAllTournaments();
    echo "<p class='info'>📊 Torneios encontrados: " . count($tournaments) . "</p>";
    
    if (empty($tournaments)) {
        echo "<p class='error'>❌ Nenhum torneio encontrado para teste</p>";
        exit;
    }
    
    $test_tournament = $tournaments[0];
    $tournament_id = $test_tournament['id'];
    echo "<p class='info'>🎯 Testando com torneio ID: $tournament_id</p>";
    
    echo "<h2>3. Testando getTournamentById</h2>";
    try {
        $tournament = $tournamentManager->getTournamentById($tournament_id);
        if ($tournament) {
            echo "<p class='success'>✅ getTournamentById funcionando</p>";
            echo "<p class='info'>📋 Nome: " . htmlspecialchars($tournament['name']) . "</p>";
        } else {
            echo "<p class='error'>❌ getTournamentById retornou null</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro em getTournamentById: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>4. Testando getTournamentStats</h2>";
    try {
        $stats = $tournamentManager->getTournamentStats($tournament_id);
        echo "<p class='success'>✅ getTournamentStats funcionando</p>";
        echo "<ul>";
        echo "<li>Grupos: " . ($stats['total_groups'] ?? 0) . "</li>";
        echo "<li>Times: " . ($stats['total_teams'] ?? 0) . "</li>";
        echo "<li>Jogos: " . ($stats['total_matches'] ?? 0) . "</li>";
        echo "<li>Concluídos: " . ($stats['completed_matches'] ?? 0) . "</li>";
        echo "<li>Progresso: " . ($stats['completion_percentage'] ?? 0) . "%</li>";
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro em getTournamentStats: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>5. Testando getActivityLog (PROBLEMA ANTERIOR)</h2>";
    try {
        $logs = $tournamentManager->getActivityLog($tournament_id, 5);
        echo "<p class='success'>✅ getActivityLog funcionando - " . count($logs) . " registros</p>";
        
        if (!empty($logs)) {
            echo "<p class='info'>📝 Logs encontrados:</p>";
            foreach ($logs as $log) {
                echo "<p class='info'>• " . $log['action'] . ": " . htmlspecialchars($log['description']) . " (" . $log['created_at'] . ")</p>";
            }
        } else {
            echo "<p class='info'>ℹ️ Nenhum log encontrado (normal para torneios novos)</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro em getActivityLog: " . $e->getMessage() . "</p>";
        echo "<p class='error'>Detalhes: " . $e->getFile() . " linha " . $e->getLine() . "</p>";
    }
    
    echo "<h2>6. Testando Dashboard Completo</h2>";
    
    // Simular exatamente o que o dashboard faz
    try {
        $tournament_id_param = $tournament_id;
        
        // Get tournament information
        $tournament = $tournamentManager->getTournamentById($tournament_id_param);
        if (!$tournament) {
            throw new Exception("Torneio não encontrado");
        }
        echo "<p class='success'>✅ Torneio carregado</p>";
        
        // Get tournament statistics
        $stats = $tournamentManager->getTournamentStats($tournament_id_param);
        echo "<p class='success'>✅ Estatísticas carregadas</p>";
        
        // Get recent activity
        $recent_activity = $tournamentManager->getActivityLog($tournament_id_param, 5);
        echo "<p class='success'>✅ Log de atividades carregado</p>";
        
        echo "<div style='background:#e8f5e8;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>🎉 DASHBOARD FUNCIONANDO PERFEITAMENTE!</h3>";
        echo "<p><strong>✅ Todos os métodos:</strong> Funcionando</p>";
        echo "<p><strong>✅ Erro SQL:</strong> Corrigido</p>";
        echo "<p><strong>✅ Dashboard:</strong> Pronto para uso</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro no teste completo: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>7. Links de Teste</h2>";
    echo "<p><a href='app/pages/adm/tournament_dashboard.php?id=$tournament_id' target='_blank'>🔗 Testar Dashboard</a></p>";
    echo "<p><a href='app/pages/adm/tournament_list.php' target='_blank'>🔗 Lista de Torneios</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro crítico: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><small>Teste executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
