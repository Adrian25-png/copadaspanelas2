<?php
/**
 * Debug específico para o dashboard de torneios
 */

echo "<h1>🔍 Debug Dashboard de Torneios</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "<h2>1. Verificando Conexão e Classes</h2>";
    
    require_once 'app/config/conexao.php';
    echo "<p class='success'>✅ Arquivo conexao.php carregado</p>";
    
    require_once 'app/classes/TournamentManager.php';
    echo "<p class='success'>✅ TournamentManager carregado</p>";
    
    $pdo = conectar();
    echo "<p class='success'>✅ Conexão estabelecida</p>";
    
    $tournamentManager = new TournamentManager($pdo);
    echo "<p class='success'>✅ TournamentManager instanciado</p>";
    
    echo "<h2>2. Verificando Torneios Disponíveis</h2>";
    
    // Listar todos os torneios
    $tournaments = $tournamentManager->getAllTournaments();
    echo "<p class='info'>📊 Total de torneios: " . count($tournaments) . "</p>";
    
    if (!empty($tournaments)) {
        echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Nome</th><th>Status</th><th>Ano</th><th>Ações</th></tr>";
        
        foreach ($tournaments as $tournament) {
            echo "<tr>";
            echo "<td>" . $tournament['id'] . "</td>";
            echo "<td>" . htmlspecialchars($tournament['name']) . "</td>";
            echo "<td>" . $tournament['status'] . "</td>";
            echo "<td>" . $tournament['year'] . "</td>";
            echo "<td><a href='#' onclick='testDashboard(" . $tournament['id'] . ")'>Testar Dashboard</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Nenhum torneio encontrado</p>";
    }
    
    echo "<h2>3. Testando Torneio Ativo</h2>";
    
    $current = $tournamentManager->getCurrentTournament();
    if ($current) {
        echo "<p class='success'>✅ Torneio ativo encontrado: " . htmlspecialchars($current['name']) . "</p>";
        echo "<p class='info'>🆔 ID: " . $current['id'] . "</p>";
        
        // Testar getTournamentStats
        try {
            $stats = $tournamentManager->getTournamentStats($current['id']);
            echo "<p class='success'>✅ Estatísticas carregadas</p>";
            echo "<ul>";
            echo "<li>Grupos: " . ($stats['total_groups'] ?? 0) . "</li>";
            echo "<li>Times: " . ($stats['total_teams'] ?? 0) . "</li>";
            echo "<li>Jogos: " . ($stats['total_matches'] ?? 0) . "</li>";
            echo "<li>Concluídos: " . ($stats['completed_matches'] ?? 0) . "</li>";
            echo "</ul>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro nas estatísticas: " . $e->getMessage() . "</p>";
        }
        
        // Testar getActivityLog
        try {
            $logs = $tournamentManager->getActivityLog($current['id'], 5);
            echo "<p class='success'>✅ Logs carregados: " . count($logs) . " registros</p>";
            if (!empty($logs)) {
                foreach ($logs as $log) {
                    echo "<p class='info'>📝 " . $log['action'] . ": " . htmlspecialchars($log['description']) . "</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro nos logs: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Nenhum torneio ativo encontrado</p>";
    }
    
    echo "<h2>4. Testando Dashboard Diretamente</h2>";
    
    if (!empty($tournaments)) {
        $test_tournament = $tournaments[0];
        echo "<p class='info'>🧪 Testando com torneio: " . htmlspecialchars($test_tournament['name']) . " (ID: " . $test_tournament['id'] . ")</p>";
        
        // Simular o que o dashboard faz
        $tournament_id = $test_tournament['id'];
        
        try {
            $tournament = $tournamentManager->getTournamentById($tournament_id);
            if ($tournament) {
                echo "<p class='success'>✅ Torneio carregado pelo ID</p>";
                echo "<p class='info'>📋 Nome: " . htmlspecialchars($tournament['name']) . "</p>";
                echo "<p class='info'>📊 Status: " . $tournament['status'] . "</p>";
            } else {
                echo "<p class='error'>❌ Torneio não encontrado pelo ID</p>";
            }
            
            $stats = $tournamentManager->getTournamentStats($tournament_id);
            echo "<p class='success'>✅ Estatísticas carregadas para o torneio</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao carregar dados do torneio: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>5. Verificando Arquivos CSS</h2>";
    
    $css_file = 'public/css/tournament_dashboard.css';
    if (file_exists($css_file)) {
        echo "<p class='success'>✅ CSS do dashboard existe</p>";
    } else {
        echo "<p class='error'>❌ CSS do dashboard não encontrado</p>";
    }
    
    echo "<h2>6. Links de Teste</h2>";
    
    if (!empty($tournaments)) {
        foreach ($tournaments as $tournament) {
            echo "<p><a href='app/pages/adm/tournament_dashboard.php?id=" . $tournament['id'] . "' target='_blank'>";
            echo "🔗 Dashboard: " . htmlspecialchars($tournament['name']) . "</a></p>";
        }
    }
    
    echo "<div style='background:#e8f5e8;padding:15px;border-radius:8px;margin:20px 0;'>";
    echo "<h3>🎯 Diagnóstico Completo</h3>";
    echo "<p>Se você está vendo esta página sem erros, o problema pode estar em:</p>";
    echo "<ul>";
    echo "<li>JavaScript no dashboard</li>";
    echo "<li>CSS que está causando problemas de renderização</li>";
    echo "<li>Algum include/require que está falhando</li>";
    echo "<li>Erro específico no HTML do dashboard</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro crítico: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<script>";
echo "function testDashboard(id) {";
echo "  window.open('app/pages/adm/tournament_dashboard.php?id=' + id, '_blank');";
echo "}";
echo "</script>";

echo "<hr><p><small>Debug executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
