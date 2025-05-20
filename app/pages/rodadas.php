<!DOCTYPE html>
<html>
<head>
    <title>Rodadas das Fases de Grupo</title>
    <link rel="stylesheet" href="../../public/css/adm/rodadas_adm.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">    
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<!-- Navegação -->
<?php
    include 'header_geral.php';
    require_once '../config/conexao.php'; // inclui a função conectar()

    // Cria a conexão PDO
    $pdo = conectar();

    // Consulta para notícias, você já tinha isso e está correto
    $noticias = $pdo->query("SELECT * FROM noticias ORDER BY data_adicao DESC LIMIT 4");

    $endDate = new DateTime();
    $endDate->modify('+6 days');
    $endTimestamp = $endDate->getTimestamp();
?>

<h1 id="dynamic-text" class="fade-in">FASES DE GRUPO</h1>
<div class="rodada_container1 fade-in">
<div id="rodadas-wrapper" class="fade-in">
    <div class="nav-arrow left" onclick="previousRodada()"><img src="../../public/img/esquerda.svg" alt=""></div>
    <div class="table-container">
        <?php exibirRodadas($pdo); // Passa o objeto PDO para a função ?>
    </div>
    <div class="nav-arrow right" onclick="nextRodada()"><img src="../../public/img/direita.svg" alt=""></div>
</div>
</div>

<?php
function exibirRodadas($pdo) {
    // Busca as rodadas distintas da tabela jogos_fase_grupos ordenadas
    $stmtRodadas = $pdo->query("SELECT DISTINCT rodada FROM jogos_fase_grupos ORDER BY rodada");
    $rodadas = $stmtRodadas->fetchAll(PDO::FETCH_ASSOC);

    if (count($rodadas) > 0) {
        // Para cada rodada
        foreach ($rodadas as $rowRodada) {
            $rodada = $rowRodada['rodada'];
            echo '<div class="rodada-container">';
            echo '<h2 class="rodada-header">' . htmlspecialchars($rodada) . 'ª RODADA</h2>';

            // Buscar grupos distintos para essa rodada
            $sqlGrupos = "SELECT DISTINCT grupo_id, nome AS grupo_nome 
                          FROM jogos_fase_grupos 
                          JOIN grupos ON jogos_fase_grupos.grupo_id = grupos.id 
                          ORDER BY grupo_id";
            $stmtGrupos = $pdo->query($sqlGrupos);
            $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($grupos as $rowGrupo) {
                $grupoId = $rowGrupo['grupo_id'];
                // Pega só a última letra do nome do grupo
                $grupoNome = substr($rowGrupo['grupo_nome'], -1);

                // Busca os confrontos do grupo e rodada atuais
                $sqlConfrontos = "SELECT jfg.id, 
                                         tA.nome AS nome_timeA, 
                                         tB.nome AS nome_timeB, 
                                         tA.logo AS logo_timeA, 
                                         tB.logo AS logo_timeB, 
                                         jfg.gols_marcados_timeA, 
                                         jfg.gols_marcados_timeB
                                  FROM jogos_fase_grupos jfg
                                  JOIN times tA ON jfg.timeA_id = tA.id
                                  JOIN times tB ON jfg.timeB_id = tB.id
                                  WHERE jfg.grupo_id = :grupoId AND jfg.rodada = :rodada";

                $stmtConfrontos = $pdo->prepare($sqlConfrontos);
                $stmtConfrontos->execute(['grupoId' => $grupoId, 'rodada' => $rodada]);
                $confrontos = $stmtConfrontos->fetchAll(PDO::FETCH_ASSOC);

                if (count($confrontos) > 0) {
                    foreach ($confrontos as $rowConfronto) {
                        $timeA_nome = $rowConfronto['nome_timeA'];
                        $timeB_nome = $rowConfronto['nome_timeB'];

                        // Se tiver logo, converte para base64 para exibir na img
                        $logoA = !empty($rowConfronto['logo_timeA']) ? 'data:image/jpeg;base64,' . base64_encode($rowConfronto['logo_timeA']) : '';
                        $logoB = !empty($rowConfronto['logo_timeB']) ? 'data:image/jpeg;base64,' . base64_encode($rowConfronto['logo_timeB']) : '';

                        $golsA = $rowConfronto['gols_marcados_timeA'];
                        $golsB = $rowConfronto['gols_marcados_timeB'];

                        echo '<div class="time_teste">';
                        echo '<div class="time-row">';
                        if ($logoA) {
                            echo '<img src="' . $logoA . '" class="logo-time">';
                        }
                        echo '<span class="time-name">' . htmlspecialchars($timeA_nome) . '</span>';
                        echo '</div>';

                        echo '<div class="no-break">' . htmlspecialchars($golsA) . ' X ' . htmlspecialchars($golsB) . '</div>';

                        echo '<div class="time-row">';
                        echo '<span class="time-name_b">' . htmlspecialchars($timeB_nome) . '</span>';
                        if ($logoB) {
                            echo '<img src="' . $logoB . '" class="logo-time">';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Nenhum confronto encontrado para o grupo ' . htmlspecialchars($grupoNome) . ' na ' . htmlspecialchars($rodada) . 'ª rodada.</p>';
                }
            }

            echo '</div>'; // fecha rodada-container
        }
    } else {
        echo '<p>Nenhuma rodada encontrada.</p>';
    }

    // Não precisa fechar conexão com PDO explicitamente
}
?>

<script>
    var currentRodadaIndex = 0;
    var rodadaContainers = document.getElementsByClassName('rodada-container');

    function showRodada(index) {
        for (var i = 0; i < rodadaContainers.length; i++) {
            rodadaContainers[i].style.display = i === index ? 'block' : 'none';
        }
    }

    function previousRodada() {
        if (currentRodadaIndex > 0) {
            currentRodadaIndex--;
            showRodada(currentRodadaIndex);
        }
    }

    function nextRodada() {
        if (currentRodadaIndex < rodadaContainers.length - 1) {
            currentRodadaIndex++;
            showRodada(currentRodadaIndex);
        }
    }

    showRodada(currentRodadaIndex);

    document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
</script>

<?php include 'footer.php'?>
</body>
</html>