<?php
session_start();
require_once("../../config/conexao.php"); // Usa require_once para garantir que carrega uma vez

// Verificar se o usuário já está logado
if (isset($_SESSION['admin_id'])) {
    header("Location: welcome_adm.php");
    exit();
}

// Gerar um token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem_erro = '';

// Função para processar o login
function processarLogin($cod_adm, $senha) {
    global $mensagem_erro;

    $pdo = conectar(); // Chama a função conectar()

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE cod_adm = ?");
    
    if ($stmt) {
        $stmt->execute([$cod_adm]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            if (password_verify($senha, $admin['senha'])) {
                // Autenticado com sucesso
                $_SESSION['admin_id'] = $admin['cod_adm'];
                $_SESSION['admin_nome'] = $admin['nome'];

                // Redirecionar para a URL de referência ou para uma página padrão
                $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'welcome_adm.php';
                unset($_SESSION['redirect_url']); // Limpar a URL de redirecionamento após login
                header("Location: $redirect_url");
                exit();
            } else {
                $mensagem_erro = "Senha incorreta.";
            }
        } else {
            $mensagem_erro = "Administrador não encontrado.";
        }
    } else {
        $mensagem_erro = "Erro na preparação da declaração.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('<p style="color: red;">Token CSRF inválido.</p>');
    }

    $cod_adm = $_POST['cod_adm'];
    $senha = $_POST['senha'];

    processarLogin($cod_adm, $senha);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../../../public/css/cadastro_adm/login.css?v=1.0.1">
</head>
<body>
    <div class="form-container">
        <form action="login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="input-field">    
                <input type="text" id="cod_adm" name="cod_adm" required>
                <label for="cod_adm">Código do Administrador</label>
            </div>
            <div class="input-field">
                <input type="password" id="senha" name="senha" required>
                <label for="senha">Senha</label>
            </div>
            <button type="submit">Login</button>

            <?php if (!empty($mensagem_erro)): ?>
                <p class="senha_incorreta"><?php echo htmlspecialchars($mensagem_erro); ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>