<?php
// Debug simples para verificar o que está acontecendo

echo "<h1>🔍 Debug Copa das Panelas</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// 1. Verificar se o PHP está funcionando
echo "<h2>1. PHP Status</h2>";
echo "<p class='success'>✅ PHP está funcionando - Versão: " . phpversion() . "</p>";

// 2. Verificar se o arquivo de conexão existe
echo "<h2>2. Arquivo de Conexão</h2>";
if (file_exists('app/config/conexao.php')) {
    echo "<p class='success'>✅ Arquivo conexao.php existe</p>";
    
    try {
        require_once 'app/config/conexao.php';
        echo "<p class='success'>✅ Arquivo conexao.php carregado</p>";
        
        $pdo = conectar();
        echo "<p class='success'>✅ Conexão com banco estabelecida</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Arquivo conexao.php não encontrado</p>";
}

// 3. Verificar se o TournamentManager existe
echo "<h2>3. TournamentManager</h2>";
if (file_exists('app/classes/TournamentManager.php')) {
    echo "<p class='success'>✅ Arquivo TournamentManager.php existe</p>";
    
    try {
        require_once 'app/classes/TournamentManager.php';
        echo "<p class='success'>✅ TournamentManager carregado</p>";
        
        if (isset($pdo)) {
            $tournamentManager = new TournamentManager($pdo);
            echo "<p class='success'>✅ TournamentManager instanciado</p>";
            
            $current = $tournamentManager->getCurrentTournament();
            if ($current) {
                echo "<p class='success'>✅ Torneio atual encontrado: " . htmlspecialchars($current['name']) . "</p>";
            } else {
                echo "<p class='info'>ℹ️ Nenhum torneio ativo encontrado</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro no TournamentManager: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Arquivo TournamentManager.php não encontrado</p>";
}

// 4. Verificar se as páginas existem
echo "<h2>4. Páginas do Sistema</h2>";
$pages = [
    'app/pages/adm/tournament_list.php' => 'Lista de Torneios',
    'app/pages/adm/tournament_wizard.php' => 'Wizard de Criação',
    'app/pages/adm/tournament_dashboard.php' => 'Dashboard'
];

foreach ($pages as $file => $name) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $name existe</p>";
    } else {
        echo "<p class='error'>❌ $name não encontrado</p>";
    }
}

// 5. Verificar CSS
echo "<h2>5. Arquivos CSS</h2>";
$css_files = [
    'public/css/tournament_list.css' => 'CSS Lista',
    'public/css/tournament_wizard.css' => 'CSS Wizard',
    'public/css/tournament_dashboard.css' => 'CSS Dashboard'
];

foreach ($css_files as $file => $name) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $name existe</p>";
    } else {
        echo "<p class='error'>❌ $name não encontrado</p>";
    }
}

// 6. Teste direto da página
echo "<h2>6. Teste de Acesso</h2>";
echo "<p><a href='app/pages/adm/tournament_list.php' target='_blank'>🔗 Testar Lista de Torneios</a></p>";
echo "<p><a href='test_database.php' target='_blank'>🔗 Testar Banco de Dados</a></p>";

// 7. Informações do servidor
echo "<h2>7. Informações do Servidor</h2>";
echo "<p class='info'>📂 Diretório atual: " . getcwd() . "</p>";
echo "<p class='info'>🌐 Servidor: " . $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' . "</p>";
echo "<p class='info'>🔧 PHP: " . phpversion() . "</p>";

echo "<hr><p><small>Debug executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
