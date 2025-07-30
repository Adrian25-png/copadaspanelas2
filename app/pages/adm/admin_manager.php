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

    // Verificar permissão para gerenciar administradores
    $permissionManager->requireAnyPermission(['create_admin', 'edit_admin', 'view_admin']);

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
            max-width: 1400px;
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

        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
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
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1rem;
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

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
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

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .admin-table {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            overflow-x: auto;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            overflow: hidden;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(139, 92, 246, 0.3));
            font-weight: 600;
            color: #E0E0E0;
            text-transform: uppercase;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.01);
            transition: all 0.3s ease;
        }

        tbody tr {
            transition: all 0.3s ease;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .status-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .status-inactive {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: 1px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.6);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: rgba(99, 102, 241, 0.3);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #E0E0E0;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .header-actions {
                justify-content: center;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }

            .admin-table {
                padding: 20px;
            }

            th, td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .main-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1.8rem;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
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
                    <i class="fas fa-users-cog icon"></i>
                    <div>
                        <h1>Gerenciar Administradores</h1>
                        <div class="header-subtitle">Controle total sobre os administradores do sistema</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="dashboard_simple.php" class="btn btn-warning">
                        <i class="fas fa-arrow-left"></i> Dashboard
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
        
        <div class="actions">
            <a href="create_admin.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Cadastrar Novo Admin
            </a>
            <a href="admin_permissions.php" class="btn btn-primary">
                <i class="fas fa-key"></i> Gerenciar Permissões
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

            // Animação dos botões de ação
            const actionButtons = document.querySelectorAll('.actions .btn');
            actionButtons.forEach((btn, index) => {
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    btn.style.transition = 'all 0.4s ease';
                    btn.style.opacity = '1';
                    btn.style.transform = 'translateY(0)';
                }, 300 + (index * 100));
            });

            // Animação da tabela
            const table = document.querySelector('.admin-table');
            if (table) {
                table.style.opacity = '0';
                table.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    table.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    table.style.opacity = '1';
                    table.style.transform = 'translateY(0)';
                }, 500);
            }

            // Animação das linhas da tabela
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 700 + (index * 50));
            });

            // Animação do estado vazio
            const emptyState = document.querySelector('.empty-state');
            if (emptyState) {
                emptyState.style.opacity = '0';
                emptyState.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    emptyState.style.transition = 'all 0.6s ease';
                    emptyState.style.opacity = '1';
                    emptyState.style.transform = 'scale(1)';
                }, 500);
            }
        });

        // Confirmação de exclusão melhorada
        function confirmDelete(adminName) {
            return confirm(`Tem certeza que deseja remover o administrador "${adminName}"?\n\nEsta ação não pode ser desfeita.`);
        }
    </script>
</body>
</html>
