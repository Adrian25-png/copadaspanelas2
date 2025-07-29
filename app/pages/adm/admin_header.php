<?php
// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login_simple.php');
    exit;
}

// Processar logout
if (isset($_GET['logout'])) {
    // Limpar todas as variáveis de sessão
    $_SESSION = array();

    // Destruir a sessão
    session_destroy();

    // Redirecionar para página inicial do site
    header('Location: ../HomePage2.php');
    exit;
}
?>

<style>
.admin-header {
    background: rgba(0, 0, 0, 0.3);
    padding: 15px 0;
    backdrop-filter: blur(10px);
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.admin-header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.3rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

.admin-logo i {
    color: #f39c12;
    font-size: 1.5rem;
}

.admin-user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    color: white;
}

.admin-user-details {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.admin-user-details i {
    color: #f39c12;
}

.admin-login-time {
    font-size: 0.8rem;
    opacity: 0.7;
}

.admin-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.admin-btn-site {
    background: #27ae60;
    color: white;
}

.admin-btn-site:hover {
    background: #2ecc71;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.admin-btn-logout {
    background: #e74c3c;
    color: white;
}

.admin-btn-logout:hover {
    background: #c0392b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.admin-breadcrumb {
    background: rgba(255, 255, 255, 0.05);
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-breadcrumb-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

.admin-breadcrumb a {
    color: #3498db;
    text-decoration: none;
}

.admin-breadcrumb a:hover {
    color: #5dade2;
}

.admin-breadcrumb-separator {
    color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 768px) {
    .admin-header-content {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .admin-user-info {
        flex-direction: column;
        gap: 10px;
    }

    .admin-user-details {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<div class="admin-header">
    <div class="admin-header-content">
        <a href="dashboard_simple.php" class="admin-logo">
            <i class="fas fa-trophy"></i>
            Copa das Panelas - Admin
        </a>

        <div class="admin-user-info">
            <div>
                <div class="admin-user-details">
                    <i class="fas fa-user-shield"></i>
                    <span><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                </div>
                <div class="admin-login-time">
                    Logado desde <?= date('H:i', $_SESSION['login_time']) ?>
                </div>
            </div>

            <a href="../HomePage2.php" class="admin-btn admin-btn-site" title="Ver site como usuário comum">
                <i class="fas fa-eye"></i>
                Ver Site
            </a>

            <a href="?logout=1" class="admin-btn admin-btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                Sair
            </a>
        </div>
    </div>
</div>

<?php
// Função para gerar breadcrumb
function generateBreadcrumb($current_page = '') {
    $breadcrumbs = [
        'dashboard_simple.php' => 'Dashboard',
        'tournament_list.php' => 'Torneios',
        'create_tournament.php' => 'Criar Torneio',
        'tournament_templates.php' => 'Templates',
        'all_teams.php' => 'Times',
        'select_tournament_for_team_management.php' => 'Gerenciar Times',
        'global_calendar.php' => 'Calendário',
        'bulk_results.php' => 'Resultados',
        'match_reports.php' => 'Relatórios de Jogos',
        'statistics.php' => 'Estatísticas',
        'system_health.php' => 'Status do Sistema',
        'system_logs.php' => 'Logs',
        'system_settings.php' => 'Configurações'
    ];
    
    $current_file = basename($_SERVER['PHP_SELF']);
    
    if ($current_file !== 'dashboard_simple.php') {
        echo '<div class="admin-breadcrumb">';
        echo '<div class="admin-breadcrumb-content">';
        echo '<a href="dashboard_simple.php"><i class="fas fa-home"></i> Dashboard</a>';
        echo '<span class="admin-breadcrumb-separator">›</span>';
        
        if (isset($breadcrumbs[$current_file])) {
            echo '<span>' . $breadcrumbs[$current_file] . '</span>';
        } else {
            echo '<span>' . ucfirst(str_replace(['_', '.php'], [' ', ''], $current_file)) . '</span>';
        }
        
        if ($current_page) {
            echo '<span class="admin-breadcrumb-separator">›</span>';
            echo '<span>' . $current_page . '</span>';
        }
        
        echo '</div>';
        echo '</div>';
    }
}

// Gerar breadcrumb automaticamente
generateBreadcrumb();
?>
