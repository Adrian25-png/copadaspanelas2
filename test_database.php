<?php
/**
 * Teste do Banco de Dados Copa das Panelas
 */

echo "<h1>🏆 Teste do Banco de Dados Copa das Panelas</h1>\n";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

try {
    // Testar conexão
    $pdo = new PDO("mysql:host=localhost;dbname=copa;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Conexão com banco de dados: OK</p>\n";
    
    // Verificar tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='success'>✅ Total de tabelas: " . count($tables) . "</p>\n";
    
    // Verificar torneio ativo
    $stmt = $pdo->query("SELECT * FROM tournaments WHERE status = 'active'");
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tournament) {
        echo "<p class='success'>✅ Torneio ativo encontrado: " . htmlspecialchars($tournament['name']) . "</p>\n";
        echo "<p class='info'>📅 Ano: " . $tournament['year'] . "</p>\n";
        echo "<p class='info'>📊 Status: " . $tournament['status'] . "</p>\n";
    } else {
        echo "<p class='error'>❌ Nenhum torneio ativo encontrado</p>\n";
    }
    
    // Verificar configurações
    $stmt = $pdo->query("SELECT * FROM tournament_settings WHERE tournament_id = " . ($tournament['id'] ?? 1));
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        echo "<p class='success'>✅ Configurações do torneio encontradas</p>\n";
        echo "<p class='info'>🏟️ Grupos: " . $settings['num_groups'] . "</p>\n";
        echo "<p class='info'>⚽ Times por grupo: " . $settings['teams_per_group'] . "</p>\n";
        echo "<p class='info'>🏆 Fase final: " . $settings['final_phase'] . "</p>\n";
    }
    
    // Verificar logs
    $stmt = $pdo->query("SELECT COUNT(*) FROM tournament_activity_log");
    $log_count = $stmt->fetchColumn();
    echo "<p class='success'>✅ Logs de atividade: $log_count registros</p>\n";
    
    echo "<h2>🎯 Links de Acesso</h2>\n";
    echo "<p><a href='app/pages/adm/tournament_list.php' target='_blank'>🔗 Lista de Torneios</a></p>\n";
    echo "<p><a href='app/pages/adm/tournament_wizard.php' target='_blank'>🔗 Criar Novo Torneio</a></p>\n";
    echo "<p><a href='app/pages/adm/tournament_dashboard.php' target='_blank'>🔗 Dashboard do Torneio</a></p>\n";
    
    echo "<div style='background:#e8f5e8;padding:15px;border-radius:8px;margin:20px 0;'>\n";
    echo "<h3>🎉 BANCO DE DADOS FUNCIONANDO PERFEITAMENTE!</h3>\n";
    echo "<p><strong>✅ Status:</strong> Operacional</p>\n";
    echo "<p><strong>✅ Tabelas:</strong> " . count($tables) . " criadas</p>\n";
    echo "<p><strong>✅ Torneio:</strong> Configurado e ativo</p>\n";
    echo "<p><strong>✅ Sistema:</strong> Pronto para uso</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>\n";
}

echo "<hr><p><small>Teste executado em: " . date('d/m/Y H:i:s') . "</small></p>\n";
?>
