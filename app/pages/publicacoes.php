<?php
    // Inclui o arquivo de conexão e cria a conexão chamando a função conectar()
    include '../config/conexao.php';
    $pdo = conectar(); // Agora $pdo está definido corretamente

    // Executa a consulta para buscar as notícias
    $stmt = $pdo->query("SELECT * FROM noticias ORDER BY data_adicao DESC");
    $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna todas as notícias como array associativo
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notícias</title>
    <link rel="stylesheet" href="../../public/css/publi.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <?php include 'header_geral.php'?>

    <h1 class="fade-in">Todas as Notícias</h1>
    <div class="news-container fade-in">
        <?php foreach($noticias as $row): ?>
            <div class="news-block">
                <a href="<?php echo $row['link']; ?>" target="_blank">
                    <div class="img-container">
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row['imagem']); ?>" alt="<?php echo $row['titulo']; ?>">
                    </div>
                    <div class="news-text">
                        <h3><?php echo $row['titulo']; ?></h3>
                        <p><?php echo $row['descricao']; ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
    </script>
    <?php include 'footer.php'?>
</body>
</html>
