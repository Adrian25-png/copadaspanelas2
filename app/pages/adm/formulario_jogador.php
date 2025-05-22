<?php
include '../../config/conexao.php';
$pdo = conectar();

// Função para gerar um token único
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

session_start();

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

include("../../actions/cadastro_adm/session_check.php");

$isAdmin = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$response = [
    'success' => true,
    'message' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $posicao = trim($_POST['posicao']);
    $numero = trim($_POST['numero']);
    $time_id = trim($_POST['time']);

    // Validação do nome
    if (!preg_match("/^[a-zA-Z\s]+$/", $nome)) {
        $response['success'] = false;
        $response['message'] = "Nome do jogador deve ser uma string sem números.";
        echo json_encode($response);
        exit;
    }

    if (empty($nome) || empty($posicao) || empty($numero) || empty($time_id)) {
        $response['success'] = false;
        $response['message'] = "Todos os campos são obrigatórios.";
        echo json_encode($response);
        exit;
    }

    if (!is_numeric($numero) || $numero < 0 || $numero > 99 || strlen($numero) > 2) {
        $response['success'] = false;
        $response['message'] = "Número deve ser um valor entre 0 e 99, com no máximo 2 dígitos.";
        echo json_encode($response);
        exit;
    }

    // Verifica se número já está em uso no mesmo time
    $sql = "SELECT COUNT(*) FROM jogadores WHERE numero = :numero AND time_id = :time_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':numero', $numero, PDO::PARAM_INT);
    $stmt->bindParam(':time_id', $time_id, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $response['success'] = false;
        $response['message'] = "Número já está em uso para este time.";
        echo json_encode($response);
        exit;
    }

    // Processa imagem
    $imagem = $_FILES['imagem'];
    if ($imagem['error'] == UPLOAD_ERR_NO_FILE) {
        $imgData = file_get_contents('../../../public/img/perfil_padrao_jogador.png');
    } else {
        $imgData = file_get_contents($imagem['tmp_name']);
    }

    $token = generateToken();

    $sql = "INSERT INTO jogadores (nome, posicao, numero, time_id, imagem, token)
            VALUES (:nome, :posicao, :numero, :time_id, :imagem, :token)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':posicao', $posicao);
    $stmt->bindParam(':numero', $numero, PDO::PARAM_INT);
    $stmt->bindParam(':time_id', $time_id, PDO::PARAM_INT);
    $stmt->bindParam(':imagem', $imgData, PDO::PARAM_LOB);
    $stmt->bindParam(':token', $token);

    if ($stmt->execute()) {
        $response['message'] = "Jogador adicionado com sucesso!";
    } else {
        $response['success'] = false;
        $response['message'] = "Erro ao adicionar jogador.";
    }

    echo json_encode($response);
    $pdo = null; // fecha conexão
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Jogadores</title>
    <link rel="stylesheet" href="../../../public/css/adm/cadastros_times_jogadores_adm/formulario_jogador.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
<?php require_once 'header_adm.php' ?>

<div class="fundo-tela">
    <div class="formulario" id="main-content">
        <form id="form-jogador" action="" method="post" enctype="multipart/form-data">
            <h1 id="editable">Editar Jogador</h1>

            <label for="nome">Nome do Jogador</label>
            <input type="text" id="nome" name="nome" required maxlength="90">
            <label for="posicao">Posição</label>
            <select id="posicao" name="posicao" required>
                <option value="">Selecione a posição</option>
                <option value="Fixo">Fixo</option>
                <option value="Ala Direita">Ala Direita</option>
                <option value="Ala Esquerda">Ala Esquerda</option>
                <option value="Pivô">Pivô</option>
            </select>
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" required maxlength="3">
            <label for="time">Time</label>
            <select id="time" name="time" required>
                <option value="">Selecione o time</option>
                <?php
                    include '../../../config/conexao.php';
                    $pdo = conectar();

                    $sql = "SELECT id, nome FROM times";
                    $result = $pdo->query($sql);

                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "'>" 
                        . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . "</option>";
                    }

                    $pdo = null;
                ?>
            </select>
            <label for="imagem">Imagem do Jogador</label>
            <input type="file" id="imagem" name="imagem" accept="image/*" onchange="previewImage()">
            <img id="imagem-preview" src="#" alt="Imagem do Jogador">
            <div id="message-container">
                <div id="error-message" class="error-message"></div>
                <div id="success-message" class="success-message"></div>
            </div>
            <input type="submit" value="Cadastrar">
        </form>
    </div>
    <?php include '../footer.php' ?>
</div>
<script>
    function previewImage() {
        const fileInput = document.getElementById('imagem');
        const imagePreview = document.getElementById('imagem-preview');
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    document.getElementById('form-jogador').addEventListener('submit', function (event) {
        event.preventDefault(); // Impede o envio do formulário padrão

        const formData = new FormData(this);
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const errorMessage = document.getElementById('error-message');
            const successMessage = document.getElementById('success-message');

            if (!data.success) {
                errorMessage.textContent = data.message;
                errorMessage.classList.add('visible');
                successMessage.classList.remove('visible');
            } else {
                successMessage.textContent = data.message;
                successMessage.classList.add('visible');
                errorMessage.classList.remove('visible');
                document.getElementById('form-jogador').reset(); // Limpa o formulário
            }
        })
        .catch(error => {
            console.error('Erro:', error);
        });
    });
    const element = document.getElementById('editable');
    let index = 0;
    const speed = 100; // Velocidade de digitação em milissegundos

    function typeWriter() {
        if (index < text.length) {
            element.innerHTML += text.charAt(index);
            index++;
            setTimeout(typeWriter, speed);
        }
    }

    // Inicia a digitação ao carregar a página
    window.onload = typeWriter;

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>
<?php require_once '../footer.php' ?>
</body>
</html>
