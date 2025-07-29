<?php
session_start();

echo "<h2>Configurar Login de Admin</h2>";

// Configurar sessão de admin
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_id'] = 1;

echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>✓ Sessão de Admin Configurada</h3>";
echo "<p><strong>admin_logged_in:</strong> " . ($_SESSION['admin_logged_in'] ? 'true' : 'false') . "</p>";
echo "<p><strong>admin_username:</strong> " . ($_SESSION['admin_username'] ?? 'não definido') . "</p>";
echo "<p><strong>admin_id:</strong> " . ($_SESSION['admin_id'] ?? 'não definido') . "</p>";
echo "</div>";

echo "<h3>Agora você pode acessar:</h3>";
echo "<ul>";
echo "<li><a href='app/pages/adm/system_logs.php' target='_blank'>📋 Página de Logs do Sistema</a></li>";
echo "<li><a href='app/pages/adm/dashboard_simple.php' target='_blank'>🏠 Dashboard Admin</a></li>";
echo "<li><a href='app/pages/adm/system_settings.php' target='_blank'>⚙️ Configurações do Sistema</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Debug da Sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
