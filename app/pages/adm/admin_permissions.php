<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../includes/PermissionManager.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

try {
    $pdo = conectar();
    $permissionManager = getPermissionManager($pdo);

    // Verificar permissão para gerenciar permissões
    $permissionManager->requirePermission('manage_permissions');

} catch (Exception $e) {
    $error = "Erro de conexão: " . $e->getMessage();
    $pdo = null;
}

// Definir permissões disponíveis
$available_permissions = [
    'tournaments' => [
        'name' => 'Torneios',
        'icon' => 'fas fa-trophy',
        'permissions' => [
            'create_tournament' => 'Criar Torneios',
            'edit_tournament' => 'Editar Torneios',
            'delete_tournament' => 'Excluir Torneios',
            'view_tournament' => 'Visualizar Torneios'
        ]
    ],
    'teams' => [
        'name' => 'Times',
        'icon' => 'fas fa-users',
        'permissions' => [
            'create_team' => 'Criar Times',
            'edit_team' => 'Editar Times',
            'delete_team' => 'Excluir Times',
            'view_team' => 'Visualizar Times'
        ]
    ],
    'matches' => [
        'name' => 'Jogos',
        'icon' => 'fas fa-futbol',
        'permissions' => [
            'create_match' => 'Criar Jogos',
            'edit_match' => 'Editar Jogos',
            'delete_match' => 'Excluir Jogos',
            'view_match' => 'Visualizar Jogos',
            'edit_results' => 'Editar Resultados'
        ]
    ],
    'admins' => [
        'name' => 'Administradores',
        'icon' => 'fas fa-users-cog',
        'permissions' => [
            'create_admin' => 'Criar Administradores',
            'edit_admin' => 'Editar Administradores',
            'delete_admin' => 'Excluir Administradores',
            'view_admin' => 'Visualizar Administradores',
            'manage_permissions' => 'Gerenciar Permissões'
        ]
    ],
    'system' => [
        'name' => 'Sistema',
        'icon' => 'fas fa-cogs',
        'permissions' => [
            'view_statistics' => 'Ver Estatísticas',
            'system_settings' => 'Configurações do Sistema',
            'backup_restore' => 'Backup e Restauração',
            'view_logs' => 'Ver Logs do Sistema'
        ]
    ]
];

// Processar atualizações de permissões
if ($_POST && isset($_POST['admin_id'])) {
    $admin_id = $_POST['admin_id'];
    $permissions = $_POST['permissions'] ?? [];
    
    try {
        // Remover permissões existentes
        $stmt = $pdo->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        
        // Adicionar novas permissões
        if (!empty($permissions)) {
            $stmt = $pdo->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
            foreach ($permissions as $permission) {
                $stmt->execute([$admin_id, $permission]);
            }
        }
        
        $success = "Permissões atualizadas com sucesso!";
        
    } catch (Exception $e) {
        $error = "Erro ao atualizar permissões: " . $e->getMessage();
    }
}

// Buscar administradores
$admins = [];
$selected_admin = $_GET['admin_id'] ?? null;
$current_permissions = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, username, role FROM admins WHERE active = 1 ORDER BY username");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar permissões atuais se um admin foi selecionado
        if ($selected_admin) {
            $stmt = $pdo->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
            $stmt->execute([$selected_admin]);
            $current_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {
        $error = "Erro ao buscar dados: " . $e->getMessage();
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            $error .= " - Execute primeiro o setup das tabelas.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Permissões - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-title .icon {
            font-size: 3rem;
            color: rgba(255,255,255,0.9);
        }

        .header-subtitle {
            margin-top: 10px;
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .admin-selector {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-selector h3 {
            margin-bottom: 20px;
            color: #E0E0E0;
            font-weight: 700;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-selector h3 i {
            color: #6366f1;
            font-size: 1.2rem;
        }

        .admin-selector select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .admin-selector select:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .admin-selector select option {
            background: #1a1a2e;
            color: white;
        }
        
        .permissions-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .permissions-container h3 {
            margin-bottom: 25px;
            color: #E0E0E0;
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .permissions-container h3 i {
            color: #6366f1;
            font-size: 1.2rem;
        }

        .permission-group {
            margin-bottom: 25px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 15px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .permission-group::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #d946ef);
        }

        .permission-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .permission-group h4 {
            margin: 0 0 20px 0;
            color: #E0E0E0;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .permission-group h4 i {
            color: #6366f1;
            font-size: 1.2rem;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .permission-item:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .permission-item input[type="checkbox"] {
            margin-right: 15px;
            transform: scale(1.3);
            accent-color: #6366f1;
        }

        .permission-item label {
            cursor: pointer;
            flex: 1;
            font-weight: 500;
            color: #E0E0E0;
            font-size: 1rem;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .btn:active {
            transform: translateY(0);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .no-admin-selected {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.6);
        }

        .no-admin-selected i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: rgba(99, 102, 241, 0.3);
        }

        .no-admin-selected h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #E0E0E0;
        }

        .no-admin-selected p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
            
            .permission-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .permission-item input[type="checkbox"] {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-key icon"></i>
                    <div>
                        <h1>Gerenciar Permissões</h1>
                        <div class="header-subtitle">Configure as permissões de acesso dos administradores</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="dashboard_simple.php" class="btn btn-warning">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-selector">
            <h3><i class="fas fa-user-cog"></i> Selecionar Administrador</h3>
            <select onchange="window.location.href='?admin_id=' + this.value">
                <option value="">Escolha um administrador...</option>
                <?php foreach ($admins as $admin): ?>
                    <option value="<?= $admin['id'] ?>" <?= $selected_admin == $admin['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($admin['username']) ?> (<?= htmlspecialchars($admin['role']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($selected_admin): ?>
            <form method="POST">
                <input type="hidden" name="admin_id" value="<?= $selected_admin ?>">
                
                <div class="permissions-container">
                    <h3><i class="fas fa-shield-alt"></i> Configurar Permissões</h3>
                    
                    <?php foreach ($available_permissions as $group_key => $group): ?>
                        <div class="permission-group">
                            <h4>
                                <i class="<?= $group['icon'] ?>"></i>
                                <?= $group['name'] ?>
                            </h4>
                            
                            <?php foreach ($group['permissions'] as $perm_key => $perm_name): ?>
                                <div class="permission-item">
                                    <input type="checkbox" 
                                           id="<?= $perm_key ?>" 
                                           name="permissions[]" 
                                           value="<?= $perm_key ?>"
                                           <?= in_array($perm_key, $current_permissions) ? 'checked' : '' ?>>
                                    <label for="<?= $perm_key ?>">
                                        <?= $perm_name ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salvar Permissões
                </button>
            </form>
        <?php else: ?>
            <div class="no-admin-selected">
                <i class="fas fa-user-slash"></i>
                <h3>Nenhum administrador selecionado</h3>
                <p>Selecione um administrador acima para configurar suas permissões.</p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="admin_manager.php" class="btn btn-secondary">
                <i class="fas fa-users-cog"></i> Gerenciar Admins
            </a>
        </div>
    </div>

    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animação do header
            const header = document.querySelector('.page-header');
            if (header) {
                header.style.opacity = '0';
                header.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    header.style.transition = 'all 0.8s ease';
                    header.style.opacity = '1';
                    header.style.transform = 'translateY(0)';
                }, 100);
            }

            // Animação dos alertas
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach((alert, index) => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(-30px)';
                setTimeout(() => {
                    alert.style.transition = 'all 0.5s ease';
                    alert.style.opacity = '1';
                    alert.style.transform = 'translateX(0)';
                }, 200 + (index * 100));
            });

            // Animação do seletor de admin
            const adminSelector = document.querySelector('.admin-selector');
            if (adminSelector) {
                adminSelector.style.opacity = '0';
                adminSelector.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    adminSelector.style.transition = 'all 0.6s ease';
                    adminSelector.style.opacity = '1';
                    adminSelector.style.transform = 'translateY(0)';
                }, 300);
            }

            // Animação do container de permissões
            const permissionsContainer = document.querySelector('.permissions-container');
            if (permissionsContainer) {
                permissionsContainer.style.opacity = '0';
                permissionsContainer.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    permissionsContainer.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    permissionsContainer.style.opacity = '1';
                    permissionsContainer.style.transform = 'translateY(0)';
                }, 500);
            }

            // Animação dos grupos de permissões
            const permissionGroups = document.querySelectorAll('.permission-group');
            permissionGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    group.style.transition = 'all 0.5s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateX(0)';
                }, 700 + (index * 150));
            });

            // Animação dos itens de permissão
            const permissionItems = document.querySelectorAll('.permission-item');
            permissionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1)';
                }, 900 + (index * 50));
            });

            // Animação do estado vazio
            const emptyState = document.querySelector('.no-admin-selected');
            if (emptyState) {
                emptyState.style.opacity = '0';
                emptyState.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    emptyState.style.transition = 'all 0.6s ease';
                    emptyState.style.opacity = '1';
                    emptyState.style.transform = 'scale(1)';
                }, 500);
            }

            // Animação dos botões de ação
            const actionButtons = document.querySelectorAll('.actions .btn');
            actionButtons.forEach((btn, index) => {
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    btn.style.transition = 'all 0.4s ease';
                    btn.style.opacity = '1';
                    btn.style.transform = 'translateY(0)';
                }, 1200 + (index * 100));
            });
        });

        // Melhorar a experiência do select
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.querySelector('.admin-selector select');
            if (select) {
                select.addEventListener('change', function() {
                    if (this.value) {
                        // Adicionar loading visual
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';

                        // Simular carregamento
                        setTimeout(() => {
                            window.location.href = '?admin_id=' + this.value;
                        }, 300);
                    }
                });
            }
        });
    </script>
</body>
</html>
