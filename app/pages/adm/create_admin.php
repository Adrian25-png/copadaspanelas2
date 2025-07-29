<?php
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
        }
        
        .container {
            max-width: 600px;
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
        
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ecf0f1;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-group select option {
            background: #2c3e50;
            color: white;
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
        
        .btn-full {
            width: 100%;
            justify-content: center;
            margin-bottom: 15px;
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
        
        .password-requirements {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #3498db;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Cadastrar Administrador</h1>
            <p>Adicione um novo administrador ao sistema</p>
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
                <a href="admin_manager.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar para Lista
                </a>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
