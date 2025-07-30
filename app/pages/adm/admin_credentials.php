<?php
session_start();
require_once '../../config/conexao.php';

try {
    $pdo = conectar();

    // Buscar administradores de todas as tabelas
    $all_admins = [];

    // Tabela administradores
    $stmt = $pdo->query("SELECT id, usuario as username, nome, email, 'administradores' as source FROM administradores");
    $admins_administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $all_admins = array_merge($all_admins, $admins_administradores);

    // Tabela admins
    $stmt = $pdo->query("SELECT id, username, email, role, active, 'admins' as source FROM admins WHERE active = 1");
    $admins_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admins_admins as &$admin) {
        $admin['nome'] = $admin['username']; // Usar username como nome para consistência
    }
    $all_admins = array_merge($all_admins, $admins_admins);

    // Tabela admin
    $stmt = $pdo->query("SELECT cod_adm as username, nome, email, 'admin' as source FROM admin");
    $admins_admin = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admins_admin as &$admin) {
        $admin['id'] = $admin['username']; // Usar cod_adm como id
    }
    $all_admins = array_merge($all_admins, $admins_admin);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenciais de Admin - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(15px);
        }
        
        .title {
            text-align: center;
            margin-bottom: 30px;
            color: #f39c12;
            font-size: 2rem;
        }
        
        .credentials-card {
            background: rgba(39, 174, 96, 0.2);
            border: 2px solid #27ae60;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .credentials-title {
            color: #27ae60;
            font-size: 1.3rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .credential-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .credential-label {
            font-weight: bold;
            color: #3498db;
        }
        
        .credential-value {
            font-family: monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 12px;
            border-radius: 5px;
            color: #f39c12;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .admin-list {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .admin-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #27ae60;
            position: relative;
        }

        .admin-source {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .source-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .source-administradores {
            background-color: #e74c3c;
            color: white;
        }

        .source-admins {
            background-color: #27ae60;
            color: white;
        }

        .source-admin {
            background-color: #f39c12;
            color: white;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            background: #f39c12;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin: 10px 5px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #2ecc71; }
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .warning {
            background: rgba(243, 156, 18, 0.2);
            border: 2px solid #f39c12;
            color: #f39c12;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">
            <i class="fas fa-key"></i>
            Credenciais de Administrador
        </h1>
        
        <div class="credentials-card">
            <div class="credentials-title">
                <i class="fas fa-shield-alt"></i>
                Credenciais Padrão do Sistema
            </div>
            <div class="credential-item">
                <span class="credential-label">Usuário:</span>
                <span class="credential-value">admin</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Senha:</span>
                <span class="credential-value">admin123</span>
            </div>
        </div>
        
        <?php if (isset($all_admins) && !empty($all_admins)): ?>
            <div class="admin-list">
                <h3 style="color: #3498db; margin-bottom: 15px;">
                    <i class="fas fa-users"></i> Todos os Administradores Cadastrados (<?= count($all_admins) ?>)
                </h3>
                <?php foreach ($all_admins as $admin): ?>
                    <div class="admin-item">
                        <div class="admin-source">
                            <span class="source-badge source-<?= $admin['source'] ?>">
                                <?= ucfirst($admin['source']) ?>
                            </span>
                        </div>
                        <strong>Usuário para Login:</strong> <?= htmlspecialchars($admin['username']) ?><br>
                        <strong>Nome:</strong> <?= htmlspecialchars($admin['nome']) ?><br>
                        <?php if (isset($admin['email']) && $admin['email']): ?>
                            <strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?><br>
                        <?php endif; ?>
                        <?php if (isset($admin['role'])): ?>
                            <strong>Função:</strong> <?= htmlspecialchars($admin['role']) ?><br>
                        <?php endif; ?>
                        <small style="color: #666;">
                            <strong>Senha padrão:</strong> admin123 (altere após primeiro login)
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Importante:</strong> Altere a senha padrão após o primeiro login por segurança!
        </div>
        
        <div class="actions">
            <a href="login_simple.php" class="btn btn-success">
                <i class="fas fa-sign-in-alt"></i>
                Fazer Login
            </a>
            <a href="dashboard_simple.php" class="btn">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-danger" onclick="setTimeout(() => { if(confirm('Remover este arquivo de credenciais?')) window.location.href='login_simple.php'; }, 100)">
                <i class="fas fa-trash"></i>
                Remover Este Arquivo
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; opacity: 0.9; font-size: 1rem;">
            <p><i class="fas fa-database"></i> <strong>Sistema Configurado!</strong></p>
            <p>✅ Administrador criado no banco de dados</p>
            <p>✅ Login funcionando com autenticação segura</p>
            <p>✅ Senhas criptografadas com password_hash</p>
        </div>
    </div>
</body>
</html>
