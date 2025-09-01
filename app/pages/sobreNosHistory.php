<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>História da Copa das Panelas</title>
    <link rel="stylesheet" href="../../public/css/HomePage2.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/sobre_nosHistory2.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>

    <?php  require_once 'header_geral.php'?> 
    
    <div class="container fade-in">
    <h1>História da copa</h1>
        <p style="margin-bottom: 40px;">A Copa das Panelas, criada em 2018 pelo antigo professor de Física Samir Ferraz, é um evento que visa promover a interação entre os alunos por meio da prática esportiva e proporcionar um alívio diante das exigências da vida acadêmica no Instituto Federal Baiano.</p>
        <p style="margin-bottom: 40px;">Atualmente, a organização do campeonato está a cargo dos discentes de Engenharia Agronômica Danilo Teixeira, Djhonata Kauã e Eduardo Moreira, juntamente com a aluna do 3º ano de Agroecologia, Analice Vianna, com o apoio da comissão composta pelos discentes César Augusto, Pedro Oliveira e Ronaldy Oliveira. Além disso, os professores Marcelo Leite, Ákila Fernandes e Thiago (da T.I.) desempenharam papéis fundamentais na organização do evento.</p>
        <p style="margin-botton: 40px;">É notável o engajamento e a colaboração de todos os envolvidos para o sucesso e a continuidade da Copa das Panelas como uma atividade enriquecedora para a comunidade acadêmica do Instituto Federal Baiano.</p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
    </script>
    <?php require_once 'footer.php' ?>
</body>
</html>


















