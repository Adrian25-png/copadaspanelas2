<?php
// Inclui o arquivo de conexão com o banco de dados e obtém a conexão PDO
include "../../config/conexao.php";
$conn = conectar(); // chama a função para obter o PDO

// Verifica se o token do time foi passado via GET
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header("Location: listar_times.php");
    exit();
}

$token = $_GET['token'];

// Processa o envio do formulário (atualização de dados)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $grupo_id = $_POST['grupo_id'];
    $pts = $_POST['pts'];
    $vitorias = $_POST['vitorias'];
    $empates = $_POST['empates'];
    $derrotas = $_POST['derrotas'];
    $gm = $_POST['gm'];
    $gc = $_POST['gc'];
    $sg = $_POST['sg'];

    // Consulta para obter a imagem atual
    $sql = "SELECT logo FROM times WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
    $time = $stmt->fetch(PDO::FETCH_ASSOC);
    $logo_atual = $time['logo'] ?? null;
    $stmt = null;

    // Verifica se foi enviada uma nova imagem
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $imagem = $_FILES['logo']['tmp_name'];
        $imagem_tipo = mime_content_type($imagem);

        // Verifica o tipo de imagem
        if (in_array($imagem_tipo, ['image/jpeg', 'image/png'])) {
            $logo = file_get_contents($imagem);
        } else {
            echo "<script>alert('Tipo de arquivo inválido. Somente JPEG e PNG são permitidos.');</script>";
            $logo = $logo_atual;
        }
    } else {
        $logo = $logo_atual;
    }

    // Atualiza os dados do time
    $sql = "UPDATE times SET nome = ?, logo = ?, grupo_id = ?, pts = ?, vitorias = ?, empates = ?, derrotas = ?, gm = ?, gc = ?, sg = ? WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $params = [$nome, $logo, $grupo_id, $pts, $vitorias, $empates, $derrotas, $gm, $gc, $sg, $token];
    if ($stmt->execute($params)) {
        header("Location: listar_times.php");
        exit();
    } else {
        echo "<script>alert('Erro ao atualizar time');</script>";
    }
    $stmt = null;
}

// Consulta para obter os dados do time pelo token
$sql = "SELECT * FROM times WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$token]);
$time = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$time) {
    header("Location: listar_times.php");
    exit();
}
$stmt = null;

// Consulta para obter todos os grupos
$sql = "SELECT id, nome FROM grupos";
$grupos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Fecha conexão (opcional, PDO fecha automaticamente no fim do script)
$conn = null;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Time</title>
    <link rel="stylesheet" href="../../../public/css/cssfooter.css" />
    <link rel="stylesheet" href="../../../public/css/adm/editar_times.css" />
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        function previewImage(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagem-preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imagem-preview').style.display = 'none';
            }
        }
    </script>
</head>
<?php require_once 'header_adm.php' ?>

<body>
    <div class="container">
        <div class="form-container">
            <h2>Editar Time</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($time['token']); ?>" />

                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" maxlength="30" required value="<?php echo htmlspecialchars($time['nome']); ?>" />

                <label for="grupo_id">Grupo:</label>
                <select id="grupo_id" name="grupo_id" required>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>" <?php if ($grupo['id'] == $time['grupo_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($grupo['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="pts">Pontos:</label>
                <input type="number" id="pts" name="pts" min="0" max="100" required value="<?php echo htmlspecialchars($time['pts']); ?>" />

                <label for="vitorias">Vitórias:</label>
                <input type="number" id="vitorias" name="vitorias" min="0" max="100" required value="<?php echo htmlspecialchars($time['vitorias']); ?>" />

                <label for="empates">Empates:</label>
                <input type="number" id="empates" name="empates" min="0" max="100" required value="<?php echo htmlspecialchars($time['empates']); ?>" />

                <label for="derrotas">Derrotas:</label>
                <input type="number" id="derrotas" name="derrotas" min="0" max="100" required value="<?php echo htmlspecialchars($time['derrotas']); ?>" />

                <label for="gm">Gols Marcados:</label>
                <input type="number" id="gm" name="gm" min="0" max="1000" required value="<?php echo htmlspecialchars($time['gm']); ?>" />

                <label for="gc">Gols Contra:</label>
                <input type="number" id="gc" name="gc" min="0" max="1000" required value="<?php echo htmlspecialchars($time['gc']); ?>" />

                <label for="sg">Saldo de Gols:</label>
                <input type="number" id="sg" name="sg" min="-1000" max="1000" required value="<?php echo htmlspecialchars($time['sg']); ?>" />

                <label for="logo">Logo:</label>
                <input type="file" id="logo" name="logo" accept="image/jpeg, image/png" onchange="previewImage(this)" />

                <?php if (!empty($time['logo'])): ?>
                    <img id="imagem-preview" src="data:image/jpeg;base64,<?php echo base64_encode($time['logo']); ?>" alt="Logo Atual" />
                <?php else: ?>
                    <img id="imagem-preview" style="display:none;" alt="Logo Atual" />
                <?php endif; ?>

                <input type="submit" value="Salvar" />
                <a href="listar_times.php" class="btn-cancel">Cancelar</a>
            </form>
        </div>
    </div>
    <?php require_once '../footer.php'; ?>
</body>
</html>