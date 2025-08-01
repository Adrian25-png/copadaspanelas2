<?php
    include 'header_geral.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/HomePage2.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/slidesHome.css">
    <!-- LINK da imagem de LOGIN e icones do YOUTUBE e INSTAGRAM do FOOTER-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Copa das Panelas</title>
</head>

<body>
    <!-- Navegação -->
    <?php
    include '../config/conexao.php'; // inclui o arquivo com a função conectar()

    $pdo = conectar(); // cria a conexão PDO

    // Agora use $pdo para a query
    $noticias = $pdo->query("SELECT * FROM noticias ORDER BY data_adicao DESC LIMIT 4");

    $endDate = new DateTime();
    $endDate->modify('+6 days');
    $endTimestamp = $endDate->getTimestamp();
    ?>

    <!-- Conteúdo Principal -->
    <main>
        <!-- Conteúdo -->
        <h1 class="titulohome fade-in">CONTEÚDO</h1>
        <section id="slider">
            <input type="radio" name="slider" id="s1" checked>
            <input type="radio" name="slider" id="s2">
            <input type="radio" name="slider" id="s3">
            <input type="radio" name="slider" id="s4">
            <input type="radio" name="slider" id="s5">
            <!-- Apenas UM label por slide -->
            <!-- Slide 1 -->
            <label for="s1" id="slide1" class="slide">
                <div class="texto">
                    <a href="sobreNosHistory.php" class="destaque">História da COPA</a>
                </div>
            </label>

            <!-- Slide 2 -->
            <label for="s2" id="slide2" class="slide">
                <div class="texto">
                    <a href="publicacoes.php" class="destaque">Publicações</a>
                </div>
            </label>

            <!-- Slide 3 -->
            <label for="s3" id="slide3" class="slide">
                <div class="texto">
                    <a href="tabela_de_classificacao.php" class="destaque">Classificados</a>
                </div>
            </label>

            <!-- Slide 4 -->
            <label for="s4" id="slide4" class="slide">
                <div class="texto">
                    <a href="sobreNosTeam.php" class="destaque">Bastidores</a>
                </div>
            </label>

            <!-- Slide 5 -->
            <label for="s5" id="slide5" class="slide">
                <div class="texto">
                    <a href="equipedev.php" class="destaque">Equipe Dev</a>
                </div>
            </label>

        </section>
    </main>

    <!-- Rodapé -->
    <?php include 'footer.php' ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
    </script>
</body>

</html>