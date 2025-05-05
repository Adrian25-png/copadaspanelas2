<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipe Copa das Panelas</title>
    <link rel="stylesheet" href="../../public/css/HomePage2.css">
    <link rel="stylesheet" href="../../public/css/cssheader.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/sobre_nosTeam2.css">

</head>
<body>

    <?php  require_once 'header.php'?>

    <nav id="nav-menu">
        <ul>
            <li><a href="../pages/HomePage.php">Home</a></li>
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
                    <li><a href="../pages/sobreNosTeam.php">História</a></li>
                    <li><a href="">Estatísticas</a></li> <!--Criar um dropdown para os outros arquivos de estatistica de jogador-->
                </ul>
            </li>
            <li><a href="Jogos Proximos.php">Transmissão</a></li>
            <li><a href="../pages/sobreNosTeam.php">Sobre nós</a></li>
        </ul>
    </nav>
<main>
  <h1 class="titulohome">Equipe Copa das Panelas</h1>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/akila.jpg" alt="Ákila Fernandes" class="foto-criadores">
    <span>Ákila Fernandes</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/marceloatt.jpg" alt="Marcelo Leite" class="foto-criadores">
    <span>Marcelo Leite</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/samir.jpg" alt="Samir Ferraz" class="foto-criadores">
    <span>Samir Ferraz</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/luiscarlos.jpg" alt="Luis Carlos" class="foto-criadores">
    <span>Luis Carlos</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/analiceatt.jpg" alt="Analice Vianna" class="foto-criadores">
    <span>Analice Vianna</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/cesar.jpg" alt="Cesar Augusto" class="foto-criadores">
    <span>Cesar Augusto</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/danilo.jpg" alt="Danilo Teixeira" class="foto-criadores">
    <span>Danilo Teixeira</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/eduardo.jpg" alt="Eduardo Moreira" class="foto-criadores">
    <span>Eduardo Moreira</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/luis.jpg" alt="Luís Henrique" class="foto-criadores">
    <span>Luís Henrique</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/pedroatt.jpg" alt="Pedro Oliveira" class="foto-criadores">
    <span>Pedro Oliveira</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/ronaldyatt.jpg" alt="Ronaldy Oliveira" class="foto-criadores">
    <span>Ronaldy Oliveira</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/vitoratt.jpg" alt="Vitor Manoel" class="foto-criadores">
    <span>Vitor Manoel</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/adrianatt.jpg" alt="Deivid Adrian" class="foto-criadores">
    <span>Deivid Adrian</span>
  </div>

  <div class="news-card">
    <img src="../../public/img/imgSobreNos/denilsonatt.jpg" alt="Denilson Ferreira" class="foto-criadores">
    <span>Denilson Ferreira</span>
  </div>
</main>


    <?php require_once 'footer.php' ?>

</body>
</html>

