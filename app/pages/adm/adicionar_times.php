<?php
session_start(); // Inicia a sessão

// Função para gerar o formulário com base na quantidade de times
function generateFormFields($numTimes) {
    $fieldsHtml = '';
    for ($i = 0; $i < $numTimes; $i++) {
        $fieldsHtml .= '
        <label for="nome_time_' . $i . '">Nome do Time ' . ($i + 1) . ':</label>
        <input type="text" id="nome_time_' . $i . '" name="nome_time[]" required>
        
        <label for="logo_time_' . $i . '">Logo do Time ' . ($i + 1) . ' (máx. 5MB):</label>
        <input type="file" id="logo_time_' . $i . '" name="logo_time[]" accept="image/jpeg,image/png,image/gif,image/webp" required>
        ';
    }
    return $fieldsHtml;
}

// Função para gerar um token único
function generateUniqueToken() {
    return bin2hex(random_bytes(16));
}

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../../config/conexao.php';
    $pdo = conectar(); // ✅ Chama a função e armazena a conexão

    $grupoId = $_POST['grupo_id'];
    $numTimes = count($_POST['nome_time']);

    // Consulta a configuração
    $configSql = "SELECT equipes_por_grupo FROM configuracoes LIMIT 1";
    $configResult = $pdo->query($configSql);
    $configRow = $configResult->fetch(PDO::FETCH_ASSOC);
    $maxTimesPerGroup = $configRow['equipes_por_grupo'];

    // Conta os times atuais no grupo
    $countSql = "SELECT COUNT(*) as count FROM times WHERE grupo_id = ?";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute([$grupoId]);
    $currentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if (($currentCount + $numTimes) <= $maxTimesPerGroup) {
        $success = true;
        $duplicateNames = [];

        for ($i = 0; $i < $numTimes; $i++) {
            $nomeTime = $_POST['nome_time'][$i];

            // Verifica duplicata
            $checkSql = "SELECT COUNT(*) as count FROM times WHERE nome = ?";
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute([$nomeTime]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($exists > 0) {
                $duplicateNames[] = $nomeTime;
                $success = false;
            } else {
                // Validação do arquivo de imagem
                if (!isset($_FILES['logo_time']['tmp_name'][$i]) || $_FILES['logo_time']['error'][$i] !== UPLOAD_ERR_OK) {
                    $_SESSION['message'] = "Erro no upload da imagem do time: " . $nomeTime;
                    $_SESSION['message_type'] = 'error';
                    $success = false;
                    break;
                }

                // Verifica o tamanho do arquivo (máximo 5MB)
                $maxFileSize = 5 * 1024 * 1024; // 5MB em bytes
                if ($_FILES['logo_time']['size'][$i] > $maxFileSize) {
                    $_SESSION['message'] = "A imagem do time '$nomeTime' é muito grande. Tamanho máximo: 5MB.";
                    $_SESSION['message_type'] = 'error';
                    $success = false;
                    break;
                }

                // Verifica se é uma imagem válida
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['logo_time']['type'][$i];
                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['message'] = "Tipo de arquivo inválido para o time '$nomeTime'. Use JPEG, PNG, GIF ou WebP.";
                    $_SESSION['message_type'] = 'error';
                    $success = false;
                    break;
                }

                $logoTime = file_get_contents($_FILES['logo_time']['tmp_name'][$i]);
                if ($logoTime === false) {
                    $_SESSION['message'] = "Erro ao ler a imagem do time: " . $nomeTime;
                    $_SESSION['message_type'] = 'error';
                    $success = false;
                    break;
                }

                $token = generateUniqueToken();

                $insertSql = "INSERT INTO times (nome, logo, grupo_id, pts, vitorias, empates, derrotas, gm, gc, sg, token)
                              VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0, 0, ?)";
                $stmt = $pdo->prepare($insertSql);
                if (!$stmt->execute([$nomeTime, $logoTime, $grupoId, $token])) {
                    $_SESSION['message'] = "Erro ao adicionar time: " . $nomeTime;
                    $_SESSION['message_type'] = 'error';
                    $success = false;
                    break;
                }
            }
        }

        if ($success) {
            $_SESSION['message'] = count($duplicateNames) > 0
                ? "Times adicionados com sucesso, mas os seguintes nomes já existem: " . implode(", ", $duplicateNames)
                : "Times adicionados com sucesso!";
            $_SESSION['message_type'] = count($duplicateNames) > 0 ? 'warning' : 'success';
        } else {
            $_SESSION['message'] = "Não foi possível adicionar todos os times.";
            if (count($duplicateNames) > 0) {
                $_SESSION['message'] .= " Os seguintes nomes já existem: " . implode(", ", $duplicateNames);
            }
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = "Não é possível adicionar mais times. O grupo já contém o número máximo de times permitido.";
        $_SESSION['message_type'] = 'error';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$numTimesToAdd = isset($_POST['num_times']) ? (int)$_POST['num_times'] : 1;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Times</title>
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../../public/css/adm/cadastros_times_jogadores_adm/adicionar_times.css">
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php require_once 'header_adm.php' ?>
<div class="main fade-in">
<h1>Adicionar Times</h1>
<div class="formulario" id="main-content">
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
        <label for="num_times">Número de Times para Adicionar:</label>
        <select id="num_times" name="num_times" onchange="updateFormFields(this.value)">
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?= $i ?>" <?= ($numTimesToAdd == $i ? 'selected' : '') ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <div id="times-fields">
            <?php echo generateFormFields($numTimesToAdd); ?>
        </div>

        <label for="grupo_id">Grupo:</label>
        <select id="grupo_id" name="grupo_id" required>
            <?php
            require_once '../../config/conexao.php';
            $pdo = conectar(); // ✅ Chamada correta da função

            $sql = "SELECT id, nome FROM grupos ORDER BY nome";
            $result = $pdo->query($sql);

            if ($result->rowCount() > 0) {
                foreach ($result as $row) {
                    echo '<option value="' . $row['id'] . '">' . $row['nome'] . '</option>';
                }
            } else {
                echo '<option value="">Nenhum grupo encontrado</option>';
            }
            ?>
        </select>

        <input type="submit" value="Adicionar Times" class="submit">
        <?php
        if (isset($_SESSION['message'])) {
            $type = $_SESSION['message_type'];
            $class = $type == 'success' ? 'success' : ($type == 'warning' ? 'warning' : 'error');
            echo "<div class='mensagem $class'>{$_SESSION['message']}</div>";
            unset($_SESSION['message'], $_SESSION['message_type']);
        }
        ?>
    </form>
</div>
</div>

<script>
function updateFormFields(num) {
    const container = document.getElementById('times-fields');
    let html = '';
    for (let i = 0; i < num; i++) {
        html += `
        <label for="nome_time_${i}">Nome do Time ${i + 1}:</label>
        <input type="text" id="nome_time_${i}" name="nome_time[]" required>
        <label for="logo_time_${i}">Logo do Time ${i + 1} (máx. 5MB):</label>
        <input type="file" id="logo_time_${i}" name="logo_time[]" accept="image/jpeg,image/png,image/gif,image/webp" required>`;
    }
    container.innerHTML = html;
}
document.addEventListener('DOMContentLoaded', () => {
    updateFormFields(<?php echo $numTimesToAdd; ?>);
    document.querySelectorAll('.fade-in').forEach(el => el.classList.add('visible'));
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>