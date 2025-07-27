<?php
/**
 * Match Manager - Versão Debug
 */

echo "<h1>🔧 Match Manager - Debug</h1>";
echo "<style>body{font-family:Arial;margin:20px;background:#f0f0f0;}</style>";

echo "<p><strong>✅ Arquivo PHP carregando...</strong></p>";
echo "<p>📅 Data/Hora: " . date('d/m/Y H:i:s') . "</p>";

// Mostrar parâmetros recebidos
echo "<h3>📋 Parâmetros Recebidos:</h3>";
echo "<ul>";
echo "<li><strong>tournament_id:</strong> " . ($_GET['tournament_id'] ?? 'não informado') . "</li>";
echo "<li><strong>id:</strong> " . ($_GET['id'] ?? 'não informado') . "</li>";
echo "<li><strong>URL completa:</strong> " . $_SERVER['REQUEST_URI'] . "</li>";
echo "</ul>";

$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;
echo "<p><strong>🎯 Tournament ID final:</strong> " . ($tournament_id ?? 'NULL') . "</p>";

try {
    echo "<p>🔄 Carregando conexão...</p>";
    require_once '../../config/conexao.php';
    echo "<p>✅ Conexão carregada</p>";
    
    echo "<p>🔄 Conectando ao banco...</p>";
    $pdo = conectar();
    echo "<p>✅ Banco conectado</p>";
    
    echo "<p>🔄 Carregando TournamentManager...</p>";
    require_once '../../classes/TournamentManager.php';
    echo "<p>✅ TournamentManager carregado</p>";
    
    echo "<p>🔄 Instanciando TournamentManager...</p>";
    $tournamentManager = new TournamentManager($pdo);
    echo "<p>✅ TournamentManager instanciado</p>";
    
    if ($tournament_id) {
        echo "<p>🔄 Buscando torneio ID: $tournament_id...</p>";
        $tournament = $tournamentManager->getTournamentById($tournament_id);
        
        if ($tournament) {
            echo "<p>✅ Torneio encontrado: " . htmlspecialchars($tournament['name']) . "</p>";
            echo "<p>📊 Status: " . $tournament['status'] . "</p>";
            echo "<p>📅 Ano: " . $tournament['year'] . "</p>";
            
            // Verificar estrutura do torneio
            echo "<h3>🏗️ Estrutura do Torneio:</h3>";
            
            // Grupos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE tournament_id = ?");
            $stmt->execute([$tournament_id]);
            $group_count = $stmt->fetchColumn();
            echo "<p>👥 Grupos: $group_count</p>";
            
            // Times
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE tournament_id = ?");
            $stmt->execute([$tournament_id]);
            $team_count = $stmt->fetchColumn();
            echo "<p>⚽ Times: $team_count</p>";
            
            // Jogos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogos WHERE tournament_id = ?");
            $stmt->execute([$tournament_id]);
            $match_count = $stmt->fetchColumn();
            echo "<p>🏟️ Jogos: $match_count</p>";
            
            echo "<div style='background:#e8f5e8; padding:15px; border-radius:8px; margin:20px 0;'>";
            echo "<h3>✅ SUCESSO!</h3>";
            echo "<p>O arquivo match_manager.php está funcionando corretamente.</p>";
            echo "<p>Torneio ID $tournament_id foi encontrado e carregado com sucesso.</p>";
            echo "</div>";
            
        } else {
            echo "<p>❌ Torneio ID $tournament_id NÃO encontrado</p>";
            
            // Listar torneios disponíveis
            echo "<h3>📋 Torneios Disponíveis:</h3>";
            $tournaments = $tournamentManager->getAllTournaments();
            if (!empty($tournaments)) {
                echo "<ul>";
                foreach ($tournaments as $t) {
                    echo "<li>ID: {$t['id']} - " . htmlspecialchars($t['name']) . " 
                          <a href='match_manager_debug.php?tournament_id={$t['id']}'>[Testar]</a></li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Nenhum torneio encontrado no banco.</p>";
            }
        }
        
    } else {
        echo "<p>❌ Nenhum tournament_id fornecido</p>";
        echo "<p>📋 Exemplo de uso: match_manager_debug.php?tournament_id=1</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>🔗 Links de Teste:</h3>";
echo "<p><a href='match_manager_debug.php?tournament_id=1'>Debug com ID 1</a></p>";
echo "<p><a href='match_manager_debug.php?tournament_id=7'>Debug com ID 7</a></p>";
echo "<p><a href='match_manager.php?tournament_id=1'>Match Manager Real (ID 1)</a></p>";
echo "<p><a href='tournament_management.php?id=1'>Gerenciamento Principal</a></p>";

echo "<hr>";
echo "<p><small>Debug executado com sucesso!</small></p>";
?>
