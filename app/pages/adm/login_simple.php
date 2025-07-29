<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../includes/system_logger.php';

$error = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos!';
    } else {
        try {
            $pdo = conectar();
            $logger = getSystemLogger($pdo);

            $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['senha'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['nome'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['login_time'] = time();

                // Registrar login bem-sucedido
                $logger->logLogin($admin['nome'], true, $admin['id']);

                header('Location: dashboard_simple.php');
                exit;
            } else {
                // Registrar tentativa de login falhada
                $logger->logLogin($username, false);
                $error = 'Usuário ou senha incorretos!';
            }
        } catch (Exception $e) {
            // Registrar erro de sistema
            if (isset($logger)) {
                $logger->logSystemError('Erro de conexão durante login: ' . $e->getMessage(), 'auth');
            }
            $error = 'Erro de conexão com o banco de dados!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Copa das Panelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <style>
        /* Reset básico */
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Space Grotesk', sans-serif;
        }

        body {
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #E0E0E0;
            line-height: 1.6;
        }

        .login-container {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 30px rgba(123, 31, 162, 0.3);
            transition: all 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(123, 31, 162, 0.4);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            border-radius: 50%;
            border: 3px solid #7B1FA2;
            padding: 10px;
            background: rgba(123, 31, 162, 0.1);
        }

        .logo-section h1 {
            color: #E1BEE7;
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .logo-section p {
            color: #E0E0E0;
            font-size: 1rem;
            opacity: 0.8;
            margin: 0;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #E1BEE7;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7B1FA2;
            font-size: 1.1rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #E0E0E0;
            font-size: 16px;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #7B1FA2;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(224, 224, 224, 0.5);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: #7B1FA2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Space Grotesk', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: #9C27B0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #EF5350;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Animação de fade-in */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsividade */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }

            .logo-section h1 {
                font-size: 1.5rem;
            }

            .logo-img {
                width: 70px;
                height: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container fade-in">
        <div class="logo-section">
            <img src="../../../public/img/ESCUDO COPA DAS PANELAS.png" alt="Copa das Panelas" class="logo-img">
            <h1>Copa das Panelas</h1>
            <p>Sistema Administrativo</p>
        </div>



        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Digite seu usuário" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha:</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Entrar no Sistema
            </button>
        </form>
    </div>

    <script>
        // Adicionar animação de fade-in
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.login-container');
            setTimeout(() => {
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });

        // Adicionar efeito de foco nos inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
