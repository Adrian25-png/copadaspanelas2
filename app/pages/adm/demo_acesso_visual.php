<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../includes/PermissionManager.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();
$permissionManager = getPermissionManager($pdo);

// Obter informações do usuário atual
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_source = $_SESSION['admin_source'] ?? 'unknown';
$is_super_admin = $permissionManager->isSuperAdmin();

// Definir todas as funcionalidades do sistema
$system_features = [
    'tournaments' => [
        'name' => 'Torneios',
        'icon' => 'fas fa-trophy',
        'color' => '#e74c3c',
        'features' => [
            'view_tournament' => ['name' => 'Ver Lista de Torneios', 'url' => 'tournament_list.php'],
            'create_tournament' => ['name' => 'Criar Torneio', 'url' => 'create_tournament.php'],
            'edit_tournament' => ['name' => 'Editar Torneio', 'url' => 'edit_tournament.php'],
            'delete_tournament' => ['name' => 'Excluir Torneio', 'url' => '#']
        ]
    ],
    'teams' => [
        'name' => 'Times',
        'icon' => 'fas fa-users',
        'color' => '#3498db',
        'features' => [
            'view_team' => ['name' => 'Ver Times', 'url' => 'all_teams.php'],
            'create_team' => ['name' => 'Criar Time', 'url' => 'team_manager.php'],
            'edit_team' => ['name' => 'Editar Time', 'url' => 'team_manager.php'],
            'delete_team' => ['name' => 'Excluir Time', 'url' => '#']
        ]
    ],
    'matches' => [
        'name' => 'Jogos',
        'icon' => 'fas fa-futbol',
        'color' => '#27ae60',
        'features' => [
            'view_match' => ['name' => 'Ver Jogos', 'url' => 'global_calendar.php'],
            'create_match' => ['name' => 'Criar Jogo', 'url' => 'match_manager.php'],
            'edit_match' => ['name' => 'Editar Jogo', 'url' => 'edit_match.php'],
            'edit_results' => ['name' => 'Editar Resultados', 'url' => 'bulk_results.php']
        ]
    ],
    'admins' => [
        'name' => 'Administradores',
        'icon' => 'fas fa-user-shield',
        'color' => '#9b59b6',
        'features' => [
            'view_admin' => ['name' => 'Ver Administradores', 'url' => 'admin_manager.php'],
            'create_admin' => ['name' => 'Criar Admin', 'url' => 'create_admin.php'],
            'edit_admin' => ['name' => 'Editar Admin', 'url' => 'admin_manager.php'],
            'manage_permissions' => ['name' => 'Gerenciar Permissões', 'url' => 'admin_permissions.php']
        ]
    ],
    'system' => [
        'name' => 'Sistema',
        'icon' => 'fas fa-cogs',
        'color' => '#f39c12',
        'features' => [
            'view_statistics' => ['name' => 'Ver Estatísticas', 'url' => 'statistics.php'],
            'system_settings' => ['name' => 'Configurações', 'url' => 'system_settings.php'],
            'backup_restore' => ['name' => 'Backup/Restore', 'url' => '#'],
            'view_logs' => ['name' => 'Ver Logs', 'url' => 'system_logs.php']
        ]
    ]
];

$user_permissions = $permissionManager->getUserPermissions();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demonstração de Acesso Visual - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .category {
            margin-bottom: 30px;
        }

        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .feature-card {
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
            transition: all 0.3s;
            cursor: pointer;
        }

        .feature-card.allowed {
            background: rgba(39, 174, 96, 0.2);
            border-left-color: #27ae60;
        }

        .feature-card.denied {
            background: rgba(149, 165, 166, 0.2);
            border-left-color: #95a5a6;
            opacity: 0.6;
        }

        .feature-card.allowed:hover {
            background: rgba(39, 174, 96, 0.3);
            transform: translateY(-2px);
        }

        .feature-status {
            float: right;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-allowed {
            background: #27ae60;
            color: white;
        }

        .status-denied {
            background: #95a5a6;
            color: white;
        }

        .summary {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
        }

        .source-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .source-administradores {
            background: #e74c3c;
            color: white;
        }

        .source-admins {
            background: #27ae60;
            color: white;
        }

        .source-admin {
            background: #f39c12;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-eye"></i> Demonstração de Acesso Visual</h1>
            <p>Veja exatamente quais funcionalidades você pode acessar</p>
        </div>

        <div class="user-info">
            <div>
                <h3><i class="fas fa-user"></i> <?= htmlspecialchars($admin_username) ?></h3>
                <span class="source-badge source-<?= $admin_source ?>">
                    <?= ucfirst($admin_source) ?>
                </span>
                <?php if ($is_super_admin): ?>
                    <span style="color: #27ae60; margin-left: 10px;">
                        <i class="fas fa-crown"></i> Super Admin
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <strong>Permissões:</strong> <?= count($user_permissions) ?>/22
            </div>
        </div>

        <?php
        $total_features = 0;
        $allowed_features = 0;
        
        foreach ($system_features as $category_key => $category):
            $category_allowed = 0;
            $category_total = count($category['features']);
            $total_features += $category_total;
            
            foreach ($category['features'] as $permission => $feature) {
                if ($permissionManager->hasPermission($permission)) {
                    $category_allowed++;
                    $allowed_features++;
                }
            }
        ?>
        
        <div class="category">
            <div class="category-header" style="border-left: 4px solid <?= $category['color'] ?>;">
                <i class="<?= $category['icon'] ?>" style="color: <?= $category['color'] ?>; margin-right: 15px; font-size: 24px;"></i>
                <div>
                    <h3 style="margin: 0;"><?= $category['name'] ?></h3>
                    <small><?= $category_allowed ?>/<?= $category_total ?> funcionalidades disponíveis</small>
                </div>
            </div>
            
            <div class="features-grid">
                <?php foreach ($category['features'] as $permission => $feature): ?>
                    <?php $has_permission = $permissionManager->hasPermission($permission); ?>
                    <div class="feature-card <?= $has_permission ? 'allowed' : 'denied' ?>" 
                         <?= $has_permission ? 'onclick="window.open(\'' . $feature['url'] . '\', \'_blank\')"' : '' ?>>
                        <div class="feature-status <?= $has_permission ? 'status-allowed' : 'status-denied' ?>">
                            <?= $has_permission ? 'PERMITIDO' : 'NEGADO' ?>
                        </div>
                        <strong><?= $feature['name'] ?></strong><br>
                        <small><code><?= $permission ?></code></small>
                        <?php if ($has_permission): ?>
                            <div style="margin-top: 10px;">
                                <i class="fas fa-external-link-alt"></i> Clique para acessar
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php endforeach; ?>

        <div class="summary">
            <h3><i class="fas fa-chart-pie"></i> Resumo do Acesso</h3>
            <p><strong>Total de Funcionalidades:</strong> <?= $total_features ?></p>
            <p><strong>Permitidas:</strong> <span style="color: #27ae60;"><?= $allowed_features ?></span></p>
            <p><strong>Negadas:</strong> <span style="color: #e74c3c;"><?= $total_features - $allowed_features ?></span></p>
            <p><strong>Percentual de Acesso:</strong> <?= round(($allowed_features / $total_features) * 100, 1) ?>%</p>
        </div>

        <div style="text-align: center;">
            <a href="dashboard_simple.php" class="btn">
                <i class="fas fa-home"></i> Voltar ao Dashboard
            </a>
            <a href="test_permissions.php" class="btn">
                <i class="fas fa-list"></i> Detalhes das Permissões
            </a>
            <?php if ($permissionManager->hasPermission('manage_permissions')): ?>
            <a href="admin_permissions.php" class="btn">
                <i class="fas fa-key"></i> Gerenciar Permissões
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
