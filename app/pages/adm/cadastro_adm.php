<?php
session_start(); // Inicia a sessão

require_once("../../config/conexao.php"); // Importa a função conectar()

// Ativar exibição de erros (para depuração)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gerar um token CSRF, se ainda não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Gera token aleatório
}

// Função para gerar o código único do administrador
function gerarCodigoAdm($pdo) {
    $ano_atual = date("Y");        // Pega o ano atual (ex: 2025)
    $prefixo = "cpTelsr";          // Prefixo fixo para o código
    $ano_prefixo = $ano_atual . $prefixo; // Ex: 2025cpTelsr

    // Consulta todos os códigos existentes com esse prefixo no banco
    $stmt = $pdo->prepare("
        SELECT cod_adm
        FROM admin
        WHERE cod_adm LIKE ?
    ");
    $like_param = $ano_prefixo . '%'; // Ex: 2025cpTelsr%
    $stmt->execute([$like_param]);

    $ids_existentes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Extrai o número após o prefixo
        $id_atual = (int)substr($row['cod_adm'], strlen($ano_prefixo));
        $ids_existentes[] = $id_atual;
    }

    // Encontra o menor número ainda não usado
    $proximo_id = 1;
    while (in_array($proximo_id, $ids_existentes)) {
        $proximo_id++;
    }

    // Retorna o código completo, ex: 2025cpTelsr3
    return $ano_prefixo . $proximo_id;
}

// Conecta ao banco de dados com PDO
$pdo = conectar();

// Gera o código do novo administrador
$codigo_adm = gerarCodigoAdm($pdo);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Administrador</title>

    <!-- Estilos -->
    <link rel="stylesheet" href="../../../public/css/cadastro_adm/cadastro_adm.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
</head>
<body>

<!-- Cabeçalho padrão do admin -->
<!-- <?php require_once 'header_adm.php'; ?> -->

<div class="form-container">
    <form action="../../actions/cadastro_adm/processar_registro_adm.php" method="post">
        <!-- Campo oculto para token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <!-- Código do Administrador (gerado automaticamente) -->
        <label for="cod_adm">Código do Administrador:</label>
        <input type="text" id="cod_adm" name="cod_adm" value="<?php echo htmlspecialchars($codigo_adm); ?>" readonly>

        <!-- Nome do Administrador -->
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" maxlength="30" required>

        <!-- Email do Administrador -->
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" maxlength="40" required>

        <!-- Senha do Administrador -->
        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" maxlength="20" required>

        <!-- Botão de envio -->
        <button type="submit">Cadastrar</button>

        <!-- Exibição de mensagens de erro ou sucesso -->
        <?php if (isset($_GET['error'])): ?>
            <p class="message error">
                <?php
                switch ($_GET['error']) {
                    case 'token':
                        echo "Token CSRF inválido.";
                        break;
                    case 'email':
                        echo "Email inválido. O email deve terminar com .com.";
                        break;
                    case 'dominio':
                        echo "O email deve ser do Gmail ou Hotmail.";
                        break;
                    case 'email_existente':
                        echo "Email já cadastrado. Por favor, use outro email.";
                        break;
                    case 'nome_existente':
                        echo "Nome já cadastrado. Por favor, escolha outro nome.";
                        break;
                    case 'db':
                        echo "Erro ao cadastrar o administrador. Tente novamente mais tarde.";
                        break;
                }
                ?>
            </p>
        <?php elseif (isset($_GET['success'])): ?>
            <p class="message success">Administrador cadastrado com sucesso!</p>
        <?php endif; ?>
    </form>
</div>

<!-- Rodapé padrão -->
<?php require_once '../footer.php'; ?>
</body>
</html>