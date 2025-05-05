<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/HomePage2.css">
    <link rel="stylesheet" href="../../public/css/cssheader.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">    
    <title>Copa das Panelas</title>
</head>
<body>
    <!-- Navegação -->
    <?php include 'header.php';
        include '../config/conexao.php';
        $noticias = $conn->query("SELECT * FROM noticias ORDER BY data_adicao DESC LIMIT 4");
        $endDate = new DateTime();
        $endDate->modify('+6 days');
        $endTimestamp = $endDate->getTimestamp();
    ?>
    <div class="menu-container">
        <button class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav id="nav-menu">
        <ul>
            <li><a href="../pages/HomePage.php">Home</a></li>

            <!--<li>
                <a href="">Cadastros ▾</a>
                <ul class="dropdown">
                    <li><a href="">Times</a></li>
                    <li><a href="">Jogadores</a></li>
                    <li><a href="">Info C</a></li>
                    <li><a href="">Info D</a></li>
                </ul>
            </li>-->
            <li>
                <a href="">Tabelas de Jogos ▾</a>
                <ul class="dropdown">
                   <li><a href="../pages/tabela_de_classificacao.php">Grupos</a></li>
                   <li><a href="../pages/exibir_finais.php">Eliminatórias</a></li>
                   <li><a href="../pages/rodadas.php">Rodadas</a></li>
                </ul>     
            </li>
            <li>
                <a href="">Dados da Copa ▾</a>
                <ul class="dropdown">
                    <li><a href="../pages/publicacoes.php">Publicações</a></li>
                    <li><a href="../pages/sobreNosHistory.php">História</a></li>
                    <li><a href="">Estatísticas</a></li> <!--Criar um dropdown para os outros arquivos de estatistica de jogador-->
                </ul>
            </li>
            <li><a href="Jogos Proximos.php">Transmissão</a></li>
            <li><a href="../pages/sobreNosTeam.php">Sobre nós</a></li>
        </ul>
    </nav>
    
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
  </main>

    <!-- Conteúdo -->
    <h1 class="titulohome">CONTEÚDO</h1>
        <div class="image">
            <div class="news-card">
                <img src="../../public/img/IMG-20240404-WA0002.jpg" alt="Imagem Descritiva">
            </div>
        </div>



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