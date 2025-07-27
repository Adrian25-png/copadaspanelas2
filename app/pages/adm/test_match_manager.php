<?php
/**
 * Teste Simples do Match Manager
 */

echo "<h1>🧪 Teste Simples - Match Manager</h1>";
echo "<style>body{font-family:Arial;margin:20px;background:#f0f0f0;}</style>";

echo "<p><strong>✅ Arquivo PHP funcionando!</strong></p>";
echo "<p>📅 Data/Hora: " . date('d/m/Y H:i:s') . "</p>";

$tournament_id = $_GET['tournament_id'] ?? 'não informado';
echo "<p>🏆 Tournament ID: $tournament_id</p>";

try {
    require_once '../../config/conexao.php';
    echo "<p>✅ Conexão carregada</p>";
    
    $pdo = conectar();
    echo "<p>✅ Banco conectado</p>";
    
    require_once '../../classes/TournamentManager.php';
    echo "<p>✅ TournamentManager carregado</p>";
    
    $tournamentManager = new TournamentManager($pdo);
    echo "<p>✅ TournamentManager instanciado</p>";
    
    if ($tournament_id && $tournament_id !== 'não informado') {
        $tournament = $tournamentManager->getTournamentById($tournament_id);
        if ($tournament) {
            echo "<p>✅ Torneio encontrado: " . htmlspecialchars($tournament['name']) . "</p>";
        } else {
            echo "<p>❌ Torneio não encontrado para ID: $tournament_id</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔗 Links de Teste:</h3>";
echo "<p><a href='test_match_manager.php?tournament_id=1'>Teste com ID 1</a></p>";
echo "<p><a href='test_match_manager.php?tournament_id=2'>Teste com ID 2</a></p>";
echo "<p><a href='match_manager.php?tournament_id=1'>Match Manager Real (ID 1)</a></p>";
echo "<p><a href='tournament_management.php?id=1'>Gerenciamento Principal</a></p>";

echo "<hr>";
echo "<p><small>Teste executado com sucesso!</small></p>";
?>
