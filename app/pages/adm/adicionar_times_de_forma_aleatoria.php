<?php
// Função para gerar um token aleatório
function gerarToken($length = 32) {
    return bin2hex(random_bytes($length));
}

$mensagem = ''; // Variável para armazenar mensagem

// Inclui conexão e pega PDO
include '../../config/conexao.php';
$pdo = conectar();

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Dados do formulário
    $nomeTime = $_POST['nome_time'];

    // Verifica se arquivo foi enviado e obtém conteúdo
    if (isset($_FILES['logo_time']) && is_uploaded_file($_FILES['logo_time']['tmp_name'])) {
        // Validação do arquivo de imagem
        if ($_FILES['logo_time']['error'] !== UPLOAD_ERR_OK) {
            $mensagem = "Erro no upload da imagem.";
        } else {
            // Verifica o tamanho do arquivo (máximo 5MB)
            $maxFileSize = 5 * 1024 * 1024; // 5MB em bytes
            if ($_FILES['logo_time']['size'] > $maxFileSize) {
                $mensagem = "A imagem é muito grande. Tamanho máximo: 5MB.";
            } else {
                // Verifica se é uma imagem válida
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['logo_time']['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    $mensagem = "Tipo de arquivo inválido. Use JPEG, PNG, GIF ou WebP.";
                } else {
                    $logoTime = file_get_contents($_FILES['logo_time']['tmp_name']);
                    if ($logoTime === false) {
                        $mensagem = "Erro ao ler a imagem.";
                    }
                }
            }
        }
    } else {
        $logoTime = null; // ou trate erro se quiser obrigar
        $mensagem = "Nenhuma imagem foi enviada.";
    }

    // Só continua se não houver erro de validação de imagem
    if (empty($mensagem)) {
        // Verifica se o nome do time já existe
        $checkSql = "SELECT COUNT(*) as count FROM times WHERE nome = :nome";
        $stmtCheck = $pdo->prepare($checkSql);
        $stmtCheck->bindParam(':nome', $nomeTime);
        $stmtCheck->execute();
        $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC)['count'];

        if ($exists > 0) {
            $mensagem = "Erro: O nome do time '$nomeTime' já existe. Escolha outro nome.";
        } else {
            // Gera um token para o novo time
            $token = gerarToken();

    // Consulta para obter a quantidade de equipes por grupo
    $configSql = "SELECT equipes_por_grupo FROM configuracoes LIMIT 1";
    $stmtConfig = $pdo->query($configSql);
    $configRow = $stmtConfig->fetch(PDO::FETCH_ASSOC);

    if ($configRow) {
        $maxTimesPerGroup = $configRow['equipes_por_grupo'];

        // Consulta para obter a lista de grupos e a quantidade atual de times em cada grupo
        $gruposSql = "
            SELECT g.id, g.nome, COALESCE(t.count, 0) as count
            FROM grupos g
            LEFT JOIN (SELECT grupo_id, COUNT(*) as count FROM times GROUP BY grupo_id) t
            ON g.id = t.grupo_id
        ";
        $stmtGrupos = $pdo->query($gruposSql);
        $gruposDisponiveis = [];

        while ($row = $stmtGrupos->fetch(PDO::FETCH_ASSOC)) {
            if ($row['count'] < $maxTimesPerGroup) {
                $gruposDisponiveis[] = ['id' => $row['id'], 'nome' => $row['nome']];
            }
        }

        if (count($gruposDisponiveis) > 0) {
            shuffle($gruposDisponiveis);
            $grupoId = $gruposDisponiveis[0]['id'];

            // Inserção dos dados na tabela de times com prepare
            $sql = "INSERT INTO times (nome, logo, grupo_id, pts, vitorias, empates, derrotas, gm, gc, sg, token) 
                    VALUES (:nome, :logo, :grupo_id, 0, 0, 0, 0, 0, 0, 0, :token)";
            $stmtInsert = $pdo->prepare($sql);

            $stmtInsert->bindParam(':nome', $nomeTime);
            $stmtInsert->bindParam(':logo', $logoTime, PDO::PARAM_LOB);
            $stmtInsert->bindParam(':grupo_id', $grupoId, PDO::PARAM_INT);
            $stmtInsert->bindParam(':token', $token);

            if ($stmtInsert->execute()) {
                $mensagem = "Time adicionado com sucesso ao grupo " . htmlspecialchars($gruposDisponiveis[0]['nome']) . "!";
            } else {
                $errorInfo = $stmtInsert->errorInfo();
                $mensagem = "Erro ao adicionar time: " . $errorInfo[2];
            }
        } else {
            $mensagem = "Não há grupos disponíveis com capacidade para mais times.";
        }
        } else {
            $mensagem = "Erro ao obter a configuração de equipes por grupo.";
        }
        } // Fecha a verificação de nome duplicado
    } // Fecha a verificação de validação de imagem
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Adicionar Time</title>
    <link rel="stylesheet" href="../../../public/css/cssfooter.css" />
    <link rel="stylesheet" href="../../../public/css/adm/cadastros_times_jogadores_adm/adicionar_times.css" />
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

</head>
<body>
<?php require_once 'header_adm.php' ?>

<div class="main">
    <h1>Adicionar times</h1>
    <div class="formulario fade-in">
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
            <label for="nome_time">Nome do Time:</label>
            <input type="text" id="nome_time" name="nome_time" required />

            <label for="logo_time">Logo do Time (máx. 5MB):</label>
            <input type="file" id="logo_time" name="logo_time" accept="image/jpeg,image/png,image/gif,image/webp" required />

            <input type="submit" value="Adicionar Time" class="submit" />
        </form>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem"><?php echo $mensagem; ?></div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".fade-in").forEach(function (el, i) {
            setTimeout(() => el.classList.add("visible"), i * 20);
        });
    });
</script>

<?php require_once '../footer.php' ?>
</body>
</html>