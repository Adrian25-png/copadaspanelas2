<?php
session_start();
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .admin-selector {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .admin-selector h3 {
            margin-bottom: 15px;
            color: #ecf0f1;
        }
        
        .admin-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }
        
        .admin-selector select option {
            background: #2c3e50;
            color: white;
        }
        
        .permissions-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .permission-group {
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .permission-group h4 {
            margin: 0 0 15px 0;
            color: #3498db;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .permission-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .permission-item input[type="checkbox"] {
            margin-right: 12px;
            transform: scale(1.2);
        }
        
        .permission-item label {
            cursor: pointer;
            flex: 1;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #27ae60;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .no-admin-selected {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .no-admin-selected i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-key"></i> Gerenciar Permissões</h1>
            <p>Configure as permissões de acesso dos administradores</p>
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
            <a href="dashboard_simple.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</body>
</html>
