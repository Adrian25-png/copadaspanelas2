<?php
/**
 * Debug específico do match_manager.php
 */

// Ativar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🐛 Debug Match Manager</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

echo "<h2>1. Verificação de Sintaxe</h2>";

// Verificar sintaxe do arquivo
$file = 'app/pages/adm/match_manager.php';
$output = shell_exec("php -l $file 2>&1");
echo "<pre>$output</pre>";

echo "<h2>2. Teste de Inclusão Básica</h2>";

try {
    // Simular sessão
    session_start();
    
    // Definir GET parameter
    $_GET['tournament_id'] = 1; // ID de teste
    
    echo "<p class='info'>Tentando incluir o arquivo...</p>";
    
    // Buffer de saída para capturar erros
    ob_start();
    
    include $file;
    
    $content = ob_get_contents();
    ob_end_clean();
    
    echo "<p class='success'>✅ Arquivo incluído com sucesso!</p>";
    echo "<p class='info'>Tamanho do conteúdo: " . strlen($content) . " bytes</p>";
    
} catch (ParseError $e) {
    echo "<p class='error'>❌ Erro de sintaxe: " . $e->getMessage() . "</p>";
    echo "<p class='error'>Linha: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p class='error'>❌ Erro fatal: " . $e->getMessage() . "</p>";
    echo "<p class='error'>Linha: " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Exceção: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificação Manual de Problemas Comuns</h2>";

$file_content = file_get_contents($file);

// Verificar problemas comuns
$checks = [
    'Fechamento de PHP' => preg_match('/\?\>/', $file_content),
    'Caracteres especiais' => preg_match('/[^\x00-\x7F]/', $file_content),
    'Tags PHP abertas' => preg_match('/\<\?php/', $file_content),
    'Aspas não fechadas' => substr_count($file_content, '"') % 2,
    'Parênteses balanceados' => substr_count($file_content, '(') === substr_count($file_content, ')'),
    'Chaves balanceadas' => substr_count($file_content, '{') === substr_count($file_content, '}')
];

foreach ($checks as $check => $result) {
    if ($check === 'Fechamento de PHP' && $result) {
        echo "<p class='error'>❌ $check: Encontrado ?> no final (pode causar problemas)</p>";
    } elseif ($check === 'Caracteres especiais' && $result) {
        echo "<p class='error'>❌ $check: Caracteres não-ASCII encontrados</p>";
    } elseif ($check === 'Aspas não fechadas' && $result) {
        echo "<p class='error'>❌ $check: Aspas desbalanceadas</p>";
    } elseif (($check === 'Parênteses balanceados' || $check === 'Chaves balanceadas') && !$result) {
        echo "<p class='error'>❌ $check: Não balanceados</p>";
    } else {
        echo "<p class='success'>✅ $check: OK</p>";
    }
}

echo "<h2>4. Teste com Torneio Real</h2>";

try {
    require_once 'app/config/conexao.php';
    require_once 'app/classes/TournamentManager.php';
    
    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    $tournaments = $tournamentManager->getAllTournaments();
    
    if (!empty($tournaments)) {
        $tournament_id = $tournaments[0]['id'];
        echo "<p class='success'>✅ Torneio encontrado: ID $tournament_id</p>";
        
        echo "<p><a href='app/pages/adm/match_manager.php?tournament_id=$tournament_id' target='_blank' style='background:#27ae60;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🧪 Testar com Torneio Real</a></p>";
        
    } else {
        echo "<p class='error'>❌ Nenhum torneio encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Informações do Servidor</h2>";

echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Error Reporting:</strong> " . error_reporting() . "</li>";
echo "<li><strong>Display Errors:</strong> " . ini_get('display_errors') . "</li>";
echo "<li><strong>Log Errors:</strong> " . ini_get('log_errors') . "</li>";
echo "<li><strong>Error Log:</strong> " . ini_get('error_log') . "</li>";
echo "</ul>";

echo "<hr><p><small>Debug executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
