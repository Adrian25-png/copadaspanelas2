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

// Processar ações de demonstração
$action_result = '';
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create_tournament':
            if ($permissionManager->hasPermission('create_tournament')) {
                $action_result = '<div class="alert success">✅ Ação permitida: Você pode criar torneios!</div>';
            } else {
                $action_result = '<div class="alert error">❌ Ação negada: Você não tem permissão para criar torneios!</div>';
            }
            break;
            
        case 'edit_match':
            if ($permissionManager->hasPermission('edit_match')) {
                $action_result = '<div class="alert success">✅ Ação permitida: Você pode editar jogos!</div>';
            } else {
                $action_result = '<div class="alert error">❌ Ação negada: Você não tem permissão para editar jogos!</div>';
            }
            break;
            
        case 'manage_permissions':
            if ($permissionManager->hasPermission('manage_permissions')) {
                $action_result = '<div class="alert success">✅ Ação permitida: Você pode gerenciar permissões!</div>';
            } else {
                $action_result = '<div class="alert error">❌ Ação negada: Você não tem permissão para gerenciar permissões!</div>';
            }
            break;
            
        case 'delete_admin':
            if ($permissionManager->hasPermission('delete_admin')) {
                $action_result = '<div class="alert success">✅ Ação permitida: Você pode excluir administradores!</div>';
            } else {
                $action_result = '<div class="alert error">❌ Ação negada: Você não tem permissão para excluir administradores!</div>';
            }
            break;
    }
}

$admin_username = $_SESSION['admin_username'];
$admin_source = $_SESSION['admin_source'] ?? 'unknown';
$is_super_admin = $permissionManager->isSuperAdmin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demonstração de Permissões - Copa das Panelas</title>
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
            max-width: 800px;
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
        }

        .demo-section {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .action-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px;
            font-size: 16px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .action-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: bold;
        }

        .alert.success {
            background: rgba(39, 174, 96, 0.3);
            border: 2px solid #27ae60;
            color: #2ecc71;
        }

        .alert.error {
            background: rgba(231, 76, 60, 0.3);
            border: 2px solid #e74c3c;
            color: #e74c3c;
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

        .explanation {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Demonstração do Sistema de Permissões</h1>
            <p>Teste as permissões do seu usuário em tempo real</p>
        </div>

        <div class="user-info">
            <h3><i class="fas fa-user"></i> Usuário Atual</h3>
            <p><strong>Nome:</strong> <?= htmlspecialchars($admin_username) ?></p>
            <p><strong>Origem:</strong> 
                <span class="source-badge source-<?= $admin_source ?>">
                    <?= ucfirst($admin_source) ?>
                </span>
            </p>
            <p><strong>Super Admin:</strong> <?= $is_super_admin ? 'Sim' : 'Não' ?></p>
        </div>

        <?= $action_result ?>

        <div class="demo-section">
            <h3><i class="fas fa-play"></i> Teste as Permissões</h3>
            <p>Clique nos botões abaixo para testar se você tem as permissões correspondentes:</p>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="create_tournament">
                <button type="submit" class="action-button">
                    <i class="fas fa-trophy"></i>
                    Criar Torneio
                </button>
            </form>

            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="edit_match">
                <button type="submit" class="action-button">
                    <i class="fas fa-futbol"></i>
                    Editar Jogo
                </button>
            </form>

            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="manage_permissions">
                <button type="submit" class="action-button">
                    <i class="fas fa-key"></i>
                    Gerenciar Permissões
                </button>
            </form>

            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete_admin">
                <button type="submit" class="action-button">
                    <i class="fas fa-user-times"></i>
                    Excluir Admin
                </button>
            </form>
        </div>

        <div class="explanation">
            <h3><i class="fas fa-info-circle"></i> Como Funciona</h3>
            <ul>
                <li><strong>Super Admins</strong> (das tabelas antigas): Têm acesso total a todas as funcionalidades</li>
                <li><strong>Admins da tabela 'admins'</strong>: Têm permissões específicas configuráveis</li>
                <li><strong>Sistema de Verificação</strong>: Cada página verifica as permissões antes de permitir acesso</li>
                <li><strong>Fallback de Segurança</strong>: Em caso de erro, o acesso é negado por padrão</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="test_permissions.php" class="btn">
                <i class="fas fa-list"></i> Ver Todas as Permissões
            </a>
            <a href="admin_permissions.php" class="btn">
                <i class="fas fa-cog"></i> Configurar Permissões
            </a>
            <a href="dashboard_simple.php" class="btn">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</body>
</html>
