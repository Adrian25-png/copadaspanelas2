<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    $errors = [];
    
    // Validações
    if (empty($username)) {
        $errors[] = "Nome de usuário é obrigatório";
    } elseif (strlen($username) < 3) {
        $errors[] = "Nome de usuário deve ter pelo menos 3 caracteres";
    }
    
    if (empty($email)) {
        $errors[] = "Email é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }
    
    if (empty($password)) {
        $errors[] = "Senha é obrigatória";
    } elseif (strlen($password) < 6) {
        $errors[] = "Senha deve ter pelo menos 6 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Senhas não coincidem";
    }
    
    // Verificar se usuário já existe
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Usuário ou email já existe";
        }
    }
    
    // Criar administrador
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO admins (username, email, password, role, active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$username, $email, $hashed_password, $role]);
            
            $success = "Administrador criado com sucesso!";
            
            // Limpar campos
            $username = $email = $role = '';
            
        } catch (Exception $e) {
            $errors[] = "Erro ao criar administrador: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Administrador - Copa das Panelas</title>
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
            max-width: 700px;
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

        .form-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #E0E0E0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group label i {
            color: #6366f1;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group select option {
            background: #1a1a2e;
            color: white;
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

        .btn-full {
            width: 100%;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
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

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        .alert li {
            margin-bottom: 5px;
        }

        .password-requirements {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
            font-size: 0.95rem;
        }

        .password-requirements h4 {
            margin: 0 0 15px 0;
            color: #6366f1;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.4;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
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

            .main-container {
                padding: 15px;
            }

            .form-container {
                padding: 25px;
            }

            .actions {
                flex-direction: column;
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

            .form-container {
                padding: 20px;
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
                    <i class="fas fa-user-plus icon"></i>
                    <div>
                        <h1>Cadastrar Administrador</h1>
                        <div class="header-subtitle">Adicione um novo administrador ao sistema</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="admin_manager.php" class="btn btn-warning">
                        <i class="fas fa-users-cog"></i> Gerenciar Admins
                    </a>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Nome de Usuário
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($username ?? '') ?>"
                           placeholder="Digite o nome de usuário" required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           placeholder="Digite o email" required>
                </div>
                
                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-user-tag"></i> Função
                    </label>
                    <select id="role" name="role" required>
                        <option value="">Selecione a função</option>
                        <option value="Super Admin" <?= ($role ?? '') === 'Super Admin' ? 'selected' : '' ?>>
                            Super Administrador
                        </option>
                        <option value="Admin" <?= ($role ?? '') === 'Admin' ? 'selected' : '' ?>>
                            Administrador
                        </option>
                        <option value="Moderator" <?= ($role ?? '') === 'Moderator' ? 'selected' : '' ?>>
                            Moderador
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Senha
                    </label>
                    <input type="password" id="password" name="password" 
                           placeholder="Digite a senha" required>
                    
                    <div class="password-requirements">
                        <h4><i class="fas fa-info-circle"></i> Requisitos da Senha:</h4>
                        <ul>
                            <li>Mínimo de 6 caracteres</li>
                            <li>Recomendado: use letras, números e símbolos</li>
                            <li>Evite senhas óbvias ou pessoais</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmar Senha
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Digite a senha novamente" required>
                </div>
                
                <button type="submit" class="btn btn-success btn-full">
                    <i class="fas fa-save"></i> Cadastrar Administrador
                </button>
            </form>
            
            <div class="actions">
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
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

            // Animação do container do formulário
            const formContainer = document.querySelector('.form-container');
            if (formContainer) {
                formContainer.style.opacity = '0';
                formContainer.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    formContainer.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    formContainer.style.opacity = '1';
                    formContainer.style.transform = 'translateY(0)';
                }, 200);
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
                }, 400 + (index * 100));
            });

            // Animação dos grupos de formulário
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    group.style.transition = 'all 0.4s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, 600 + (index * 100));
            });

            // Animação do botão de submit
            const submitBtn = document.querySelector('.btn-full');
            if (submitBtn) {
                submitBtn.style.opacity = '0';
                submitBtn.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    submitBtn.style.transition = 'all 0.5s ease';
                    submitBtn.style.opacity = '1';
                    submitBtn.style.transform = 'scale(1)';
                }, 1000);
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

        // Validação em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            function validatePasswords() {
                if (passwordInput.value && confirmPasswordInput.value) {
                    if (passwordInput.value === confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#10b981';
                    } else {
                        confirmPasswordInput.style.borderColor = '#ef4444';
                    }
                }
            }

            passwordInput.addEventListener('input', validatePasswords);
            confirmPasswordInput.addEventListener('input', validatePasswords);

            // Validação de força da senha
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const requirements = document.querySelector('.password-requirements');

                if (password.length >= 6) {
                    this.style.borderColor = '#10b981';
                } else if (password.length > 0) {
                    this.style.borderColor = '#f59e0b';
                }
            });
        });

        // Feedback visual no submit
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = document.querySelector('.btn-full');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cadastrando...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
