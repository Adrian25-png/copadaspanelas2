<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

try {
    // Criar tabela de administradores
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'Admin',
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )
    ");
    
    // Criar tabela de permissões
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            permission VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            UNIQUE KEY unique_admin_permission (admin_id, permission)
        )
    ");
    
    // Verificar se já existe um admin padrão
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $admin_count = $stmt->fetchColumn();
    
    // Criar admin padrão se não existir nenhum
    if ($admin_count == 0) {
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO admins (username, email, password, role, active) 
            VALUES ('admin', 'admin@copadaspanelas.com', ?, 'Super Admin', 1)
        ")->execute([$default_password]);
        
        $admin_id = $pdo->lastInsertId();
        
        // Dar todas as permissões ao admin padrão
        $all_permissions = [
            'create_tournament', 'edit_tournament', 'delete_tournament', 'view_tournament',
            'create_team', 'edit_team', 'delete_team', 'view_team',
            'create_match', 'edit_match', 'delete_match', 'view_match', 'edit_results',
            'create_admin', 'edit_admin', 'delete_admin', 'view_admin', 'manage_permissions',
            'view_statistics', 'system_settings', 'backup_restore', 'view_logs'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
        foreach ($all_permissions as $permission) {
            $stmt->execute([$admin_id, $permission]);
        }
        
        $default_created = true;
    }
    
    $success = "Tabelas de administradores configuradas com sucesso!";
    
} catch (Exception $e) {
    $error = "Erro ao configurar tabelas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Tabelas de Admin - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #3498db;
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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
        
        .alert-info {
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid #3498db;
            color: #3498db;
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
            margin: 10px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .credentials {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .credentials h3 {
            margin-top: 0;
            color: #f39c12;
        }
        
        .credential-item {
            margin: 10px 0;
            font-family: monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 12px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-database"></i>
        </div>
        
        <h1>Configuração de Tabelas de Administradores</h1>
        
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
        
        <?php if (isset($default_created)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Administrador padrão criado!</strong><br>
                Use as credenciais abaixo para fazer login:
            </div>
            
            <div class="credentials">
                <h3><i class="fas fa-key"></i> Credenciais de Acesso</h3>
                <div class="credential-item">
                    <strong>Usuário:</strong> admin
                </div>
                <div class="credential-item">
                    <strong>Senha:</strong> admin123
                </div>
                <div class="credential-item">
                    <strong>Email:</strong> admin@copadaspanelas.com
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Importante:</strong> Altere a senha padrão após o primeiro login!
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="admin_manager.php" class="btn btn-success">
                <i class="fas fa-users-cog"></i> Gerenciar Administradores
            </a>
            <a href="dashboard_simple.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Ir para Dashboard
            </a>
        </div>
        
        <div style="margin-top: 20px; opacity: 0.7; font-size: 0.9rem;">
            <p>As tabelas foram configuradas automaticamente no banco de dados.</p>
        </div>
    </div>
</body>
</html>
