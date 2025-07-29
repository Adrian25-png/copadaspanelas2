<?php
session_start();

// Configurar sessão de admin
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin_debug';

require_once 'app/config/conexao.php';

echo "<h1>🔍 Debug do Problema POST</h1>";

// Mostrar informações da requisição
echo "<div style='background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<h3>📋 Informações da Requisição</h3>";
echo "<p><strong>Método:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "</p>";
echo "</div>";

// Mostrar dados POST se houver
if ($_POST) {
    echo "<div style='background: #e8f5e8; padding: 15px; margin: 15px 0; border: 2px solid #4caf50; border-radius: 5px;'>";
    echo "<h3>✅ Dados POST Recebidos</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3e0; padding: 15px; margin: 15px 0; border: 2px solid #ff9800; border-radius: 5px;'>";
    echo "<h3>⚠️ Nenhum POST Recebido</h3>";
    echo "<p>Esta página ainda não recebeu dados POST.</p>";
    echo "</div>";
}

// Mostrar dados GET se houver
if ($_GET) {
    echo "<div style='background: #e3f2fd; padding: 15px; margin: 15px 0; border: 1px solid #2196f3; border-radius: 5px;'>";
    echo "<h3>📄 Dados GET</h3>";
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";
    echo "</div>";
}

// Mostrar sessão
echo "<div style='background: #f3e5f5; padding: 15px; margin: 15px 0; border: 1px solid #9c27b0; border-radius: 5px;'>";
echo "<h3>🔐 Dados da Sessão</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

try {
    $pdo = conectar();
    
    // Verificar tabela
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e0f2f1; padding: 15px; margin: 15px 0; border: 1px solid #4caf50; border-radius: 5px;'>";
    echo "<h3>🗄️ Status do Banco</h3>";
    echo "<p><strong>Conexão:</strong> ✅ OK</p>";
    echo "<p><strong>Total de logs:</strong> {$count['total']}</p>";
    echo "</div>";
    
    // Processar POST se houver
    if ($_POST && isset($_POST['action'])) {
        echo "<div style='background: #e1f5fe; padding: 15px; margin: 15px 0; border: 2px solid #03a9f4; border-radius: 5px;'>";
        echo "<h3>⚙️ Processando Ação</h3>";
        
        try {
            switch ($_POST['action']) {
                case 'clear_logs':
                    $result = $pdo->exec("DELETE FROM system_logs");
                    echo "<p style='color: green;'>✅ Logs limpos: $result registros removidos</p>";
                    $_SESSION['success'] = "Logs limpos com sucesso! ($result registros removidos)";
                    break;
                    
                case 'populate_sample_logs':
                    $stmt = $pdo->prepare("
                        INSERT INTO system_logs (level, message, ip_address, user_agent, username, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $logs = [
                        ['INFO', 'Log de teste 1', 'debug'],
                        ['SUCCESS', 'Log de teste 2', 'debug'],
                        ['WARNING', 'Log de teste 3', 'debug'],
                    ];
                    
                    $inserted = 0;
                    foreach ($logs as $log) {
                        $result = $stmt->execute([
                            $log[0], $log[1], '127.0.0.1', 'Debug Script', $log[2]
                        ]);
                        if ($result) $inserted++;
                    }
                    
                    echo "<p style='color: green;'>✅ Logs adicionados: $inserted registros</p>";
                    $_SESSION['success'] = "$inserted logs de exemplo adicionados com sucesso!";
                    break;
                    
                default:
                    echo "<p style='color: red;'>❌ Ação desconhecida: " . htmlspecialchars($_POST['action']) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            $_SESSION['error'] = "Erro: " . $e->getMessage();
        }
        
        echo "</div>";
        
        // Simular redirecionamento da página oficial
        echo "<div style='background: #fff9c4; padding: 15px; margin: 15px 0; border: 1px solid #fbc02d; border-radius: 5px;'>";
        echo "<h3>🔄 Simulando Redirecionamento</h3>";
        echo "<p>Na página oficial, haveria um redirecionamento aqui.</p>";
        echo "<p><a href='app/pages/adm/system_logs.php'>🔗 Ir para página oficial</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 15px 0; border: 1px solid #f44336; border-radius: 5px;'>";
    echo "<h3>❌ Erro de Conexão</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<hr>
<h3>🧪 Teste dos Formulários</h3>

<div style="margin: 20px 0;">
    <form method="POST" style="display: inline; margin: 10px;">
        <input type="hidden" name="action" value="populate_sample_logs">
        <button type="submit" style="padding: 15px 25px; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            ➕ Adicionar Logs de Teste
        </button>
    </form>

    <form method="POST" style="display: inline; margin: 10px;">
        <input type="hidden" name="action" value="clear_logs">
        <button type="submit" style="padding: 15px 25px; background: #F44336; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            🗑️ Limpar Logs
        </button>
    </form>
</div>

<hr>
<h3>🔗 Links de Teste</h3>
<ul>
    <li><a href="app/pages/adm/system_logs.php" target="_blank">📋 Página Oficial de Logs</a></li>
    <li><a href="test_buttons_simple.php" target="_blank">🧪 Teste Simples dos Botões</a></li>
    <li><a href="?" target="_blank">🔄 Recarregar esta página</a></li>
</ul>
