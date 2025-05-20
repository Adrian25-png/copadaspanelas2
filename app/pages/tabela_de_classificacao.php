<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Tabela de Classificação</title>
    <link rel="stylesheet" href="../../public/css/tabela_classifica.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <!-- LINK da imagem de LOGIN e icones do YOUTUBE e INSTAGRAM do FOOTER-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<!-- Navegação -->
<?php
    include 'header_geral.php';
    include '../config/conexao.php'; // inclui o arquivo com a função conectar()

    $pdo = conectar(); // cria a conexão PDO

    // Agora use $pdo para a query
    $noticias = $pdo->query("SELECT * FROM noticias ORDER BY data_adicao DESC LIMIT 4");

    $endDate = new DateTime();
    $endDate->modify('+6 days');
    $endTimestamp = $endDate->getTimestamp();
    ?>

<div class="main">
    <div class="wrapper-container">
        <h1 class="fade-in">FASE DE GRUPOS</h1>  
        <div id="tabela-wrapper" class="fade-in">
            <h4 class="fade-in">Tabela de Classificação</h4>
            <?php function mostrarGrupos() {
                include '../config/conexao.php';

                $sqlGrupos = "SELECT id, nome FROM grupos ORDER BY nome";
                $resultGrupos = $conn->query($sqlGrupos);

                if ($resultGrupos->num_rows > 0) {
                    while ($rowGrupos = $resultGrupos->fetch_assoc()) {
                        $grupoId = $rowGrupos['id'];
                        $grupoNome = $rowGrupos['nome'];

                        echo '<div class="grupo-container fade-in">';
                        echo '<div class="grupo-header fade-in">' . $grupoNome . '</div>';
                        echo '<div class="tabela-flex fade-in">';
                        echo '<div class="tabela-flex-header fade-in">';
                        echo '<div class="clube fade-in">Clube</div>'; // Coluna de Nome do Clube
                        echo '<div class="small-col fade-in">P</div>'; // Coluna de Pontos
                        echo '<div class="small-col fade-in">J</div>'; // Coluna de Partidas
                        echo '<div class="small-col fade-in">V</div>'; // Coluna de Vitórias
                        echo '<div class="small-col fade-in">E</div>'; // Coluna de Empates
                        echo '<div class="small-col fade-in">D</div>'; // Coluna de Derrotas
                        echo '<div class="small-col fade-in">GP</div>'; // Coluna de Gols Pró
                        echo '<div class="small-col fade-in">GC</div>'; // Coluna de Gols Contra
                        echo '<div class="small-col fade-in">SG</div>'; // Coluna de Saldo de Gols
                        echo '<div class="small-col fade-in">%</div>'; // Coluna de Porcentagem de Aproveitamento
                        echo '<div class="larger-col fade-in">ÚLT. JOGOS</div>'; // Coluna de Últimos Jogos
                        echo '</div>';

                        $sqlTimes = "SELECT t.id, t.nome, t.logo, 
                                            COALESCE(SUM(j.gols_marcados_timeA), 0) + COALESCE(SUM(j.gols_marcados_timeB), 0) AS gm,
                                            COALESCE(SUM(j.gols_marcados_timeB), 0) + COALESCE(SUM(j.gols_marcados_timeA), 0) AS gc,
                                            COALESCE(SUM(j.gols_marcados_timeA > j.gols_marcados_timeB), 0) AS vitorias,
                                            COALESCE(SUM(j.gols_marcados_timeA = j.gols_marcados_timeB), 0) AS empates,
                                            COALESCE(SUM(j.gols_marcados_timeA < j.gols_marcados_timeB), 0) AS derrotas,
                                            COALESCE(SUM(j.gols_marcados_timeA - j.gols_marcados_timeB), 0) AS sg,
                                            COUNT(j.id) AS partidas,
                                            COALESCE(SUM(j.gols_marcados_timeA > j.gols_marcados_timeB), 0) * 3 + COALESCE(SUM(j.gols_marcados_timeA = j.gols_marcados_timeB), 0) AS pts
                                    FROM times t
                                    LEFT JOIN jogos_fase_grupos j ON t.id = j.timeA_id OR t.id = j.timeB_id
                                    WHERE t.grupo_id = $grupoId
                                    GROUP BY t.id
                                    ORDER BY pts DESC, gm DESC, gc ASC, sg DESC";
                        $resultTimes = $conn->query($sqlTimes);

                        if ($resultTimes->num_rows > 0) {
                            $posicao = 1;
                            while ($rowTimes = $resultTimes->fetch_assoc()) {
                                echo '<div class="tabela-flex-row fade-in">';
                                echo '<div class="time-info fade-in">';
                                echo '<span class="posicao_num">' . $posicao . '</span>';
                                if (!empty($rowTimes['logo'])) {
                                    $imageData = base64_encode($rowTimes['logo']);
                                    $imageSrc = 'data:image/jpeg;base64,'.$imageData;
                                    echo '<img src="' . $imageSrc . '" class="logo-time fade-in">';
                                }
                                echo '<span class="time-name">' . $rowTimes['nome'] . '</span>';
                                echo '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['pts'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['partidas'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['vitorias'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['empates'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['derrotas'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['gm'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['gc'] . '</div>';
                                echo '<div class="small-col fade-in">' . $rowTimes['sg'] . '</div>';
                                echo '<div class="small-col fade-in">' . formatarPorcentagemAproveitamento($rowTimes['vitorias'], $rowTimes['partidas']) . '</div>';
                                echo '<div class="larger-col fade-in">';
                                echo gerarUltimosJogos($rowTimes['id']);
                                echo '</div>';
                                echo '</div>';
                                $posicao++;
                            }
                        } else {
                            echo '<div class="tabela-flex-row fade-in"><div colspan="11">Nenhum time encontrado para este grupo.</div></div>';
                        }

                        echo '</div>';
                        echo '</div>';
                    }
                }  else {
                    echo "Nenhum grupo encontrado.";
                }

                $conn->close();
                }

                function formatarPorcentagemAproveitamento($vitorias, $partidas) {
                    if ($partidas > 0) {
                        $porcentagem = number_format(($vitorias / $partidas) * 100, 1);
                        if (substr($porcentagem, -2) == '.0') {
                            return substr($porcentagem, 0, -2);
                        } else {
                            return $porcentagem;
                        }
                    } else {
                        return '0';
                    }
                }

                function gerarUltimosJogos($timeId) {
                    include '../config/conexao.php';

                    $sqlJogos = "SELECT CASE 
                                    WHEN timeA_id = $timeId THEN resultado_timeA
                                    WHEN timeB_id = $timeId THEN resultado_timeB
                                    ELSE 'G'
                                END AS resultado
                                FROM jogos_fase_grupos 
                                WHERE timeA_id = $timeId OR timeB_id = $timeId 
                                ORDER BY data_jogo DESC 
                                LIMIT 5";
                    $resultJogos = $conn->query($sqlJogos);

                    $ultimosJogos = [];

                    if ($resultJogos->num_rows > 0) {
                        while ($rowJogos = $resultJogos->fetch_assoc()) {
                            $ultimosJogos[] = $rowJogos['resultado'];
                        }
                    }

                    while (count($ultimosJogos) < 5) {
                        $ultimosJogos[] = 'G'; // Cinza para jogos não existentes
                    }

                    $output = '';
                    foreach ($ultimosJogos as $resultado) {
                        if ($resultado == 'V') {
                            $output .= '<div class="inf fade-in"></div>';
                        } elseif ($resultado == 'D') {
                            $output .= '<div class="inf2 fade-in"></div>';
                        } elseif ($resultado == 'E') {
                            $output .= '<div class="inf3 fade-in"></div>';
                        } else {
                            $output .= '<div class="inf4 fade-in"></div>';
                        }   
                    }

                    return $output;
                }
            ?>

            <div id="legenda-simbolos" class="fade-in">
                <div><div class="simbolo" style="background-color: green;"></div><span class="descricao">Vitória</span></div>
                <div><div class="simbolo" style="background-color: red;"></div><span class="descricao">Derrota</span></div>
                <div><div class="simbolo" style="background-color: gray;"></div><span class="descricao">Empate</span></div>
                <div><div class="simbolo" style="background-color: lightgray;"></div><span class="descricao">Não houve jogo</span></div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>

<?php include 'footer.php'; ?>   
</body>
</html>