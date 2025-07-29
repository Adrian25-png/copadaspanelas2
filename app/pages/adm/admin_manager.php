<?php
session_start();
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
} catch (Exception $e) {
    $error = "Erro de conexão: " . $e->getMessage();
    $pdo = null;
}

// Processar ações
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $admin_id = $_POST['admin_id'];
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$admin_id]);
                $success = "Administrador removido com sucesso!";
                break;
                
            case 'toggle_status':
                $admin_id = $_POST['admin_id'];
                $stmt = $pdo->prepare("UPDATE admins SET active = NOT active WHERE id = ?");
                $stmt->execute([$admin_id]);
                $success = "Status do administrador alterado!";
                break;
        }
    }
}

// Buscar todos os administradores
$admins = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, username, email, created_at, last_login, active, role
            FROM admins
            ORDER BY created_at DESC
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Erro ao buscar administradores: " . $e->getMessage();
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
    <title>Gerenciar Administradores - Copa das Panelas</title>
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
            max-width: 1200px;
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
        
        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .admin-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #27ae60;
            color: white;
        }
        
        .status-inactive {
            background: #e74c3c;
            color: white;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #3498db;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
            
            .admin-table {
                padding: 15px;
            }
            
            th, td {
                padding: 8px;
                font-size: 0.9rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Gerenciar Administradores</h1>
            <p>Controle total sobre os administradores do sistema</p>
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
        
        <div class="actions">
            <a href="create_admin.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Cadastrar Novo Admin
            </a>
            <a href="admin_permissions.php" class="btn btn-primary">
                <i class="fas fa-key"></i> Gerenciar Permissões
            </a>
            <a href="dashboard_simple.php" class="btn btn-warning">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
        
        <div class="admin-table">
            <?php if (count($admins) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Função</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Último Login</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= $admin['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($admin['username']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>
                                    <span class="role-badge">
                                        <?= htmlspecialchars($admin['role'] ?? 'Admin') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $admin['active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $admin['active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($admin['created_at'])) ?></td>
                                <td>
                                    <?= $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Nunca' ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-toggle-<?= $admin['active'] ? 'off' : 'on' ?>"></i>
                                                <?= $admin['active'] ? 'Desativar' : 'Ativar' ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Tem certeza que deseja remover este administrador?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Remover
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>Nenhum administrador encontrado</h3>
                    <p>Comece cadastrando o primeiro administrador do sistema.</p>
                    <a href="create_admin.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Cadastrar Primeiro Admin
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
