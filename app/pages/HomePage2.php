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
    <?php include 'header_geral.php';
        include '../config/conexao.php';
        $noticias = $conn->query("SELECT * FROM noticias ORDER BY data_adicao DESC LIMIT 4");
        $endDate = new DateTime();
        $endDate->modify('+6 days');
        $endTimestamp = $endDate->getTimestamp();
    ?>
    </div>
    
  <!-- Conteúdo Principal -->
  <main>
    <!-- Notícia 1 -->
    <h1 class="titulohome">NOTÍCIAS</h1>
        <div class="news-card">
            <h2>Clássico termina em empate no Maracanã</h2>
            hFlamengo e Fluminense empataram em 2 a 2 em um jogo emocionante marcado por gols no final do segundo tempo.</p>
        </div>

    <!-- Notícia 2 -->
        <div class="news-card">
            <h2>Palmeiras vence e assume liderança do Brasileirão</h2>
            <p>Com gol de Raphael Veiga, o Verdão venceu o Internacional e subiu para a primeira colocação.</p>
        </div>

    <!-- Notícia 3 -->
        <div class="news-card">
            <h2>Real Madrid classificado para a final da Champions</h2>
            <p>Com show de Vinicius Jr., o Real Madrid derrotou o Manchester City e garantiu vaga na final da Liga dos Campeões.</p>
        </div>

    <!-- Conteúdo -->
    <h1 class="titulohome">CONTEÚDO</h1>
    <section id="slider">
        <input type="radio" name="slider" id="s1" checked> 
        <input type="radio" name="slider" id="s2">
        <input type="radio" name="slider" id="s3">
        <input type="radio" name="slider" id="s4">
        <input type="radio" name="slider" id="s5">
        <label for="s1" id="slide1"></label>
        <label for="s2" id="slide2"></label>
        <label for="s3" id="slide3"></label>
        <label for="s4" id="slide4"></label>
        <label for="s5" id="slide5"></label>

        <label for="s1" id="slide1">
            <div class="texto">História da <a href="../pages/sobreNosHistory.php" class="destaque">COPA</a></div>
        </label>
  <!-- repete para os outros slides... -->
    </section>
  </main>

    



  <!-- Rodapé -->
  <?php include 'footer.php'?>        
    <!--
    <div id="countdown-balloon">
        <span id="close-btn">&times;</span>
        <div id="description">INICIO DA COPA DAS PANELAS</div>
        <div id="countdown">
            <div id="days">00</div> dias
            <div id="hours">00</div> horas
            <div id="minutes">00</div> minutos
            <div id="seconds">00</div> segundos
        </div>
    </div>
    -->
    <script>document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const navMenu = document.getElementById('nav-menu');

            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
            });
        });
    </script>
    <script src="../../public/js/homepage.js"></script>
</body>
</html>