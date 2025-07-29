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

    <style>
        .no-tournament-message {
            text-align: center;
            padding: 60px 20px;
            background: rgba(30, 30, 30, 0.9);
            border-radius: 15px;
            margin: 40px auto;
            max-width: 500px;
            border: 2px solid rgba(123, 31, 162, 0.3);
        }

        .no-tournament-message h3 {
            color: #E1BEE7;
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .no-tournament-message p {
            color: #B0B0B0;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .no-tournament-message::before {
            content: "🏆";
            font-size: 3rem;
            display: block;
            margin-bottom: 20px;
        }
    </style>
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
            <?php mostrarGrupos(); ?>
            <?php
                function mostrarGrupos() {
                    $pdo = conectar(); // Usando PDO corretamente

                    // Primeiro, buscar o torneio ativo
                    $sqlTorneioAtivo = "SELECT id FROM tournaments WHERE status IN ('active', 'ativo') ORDER BY id DESC LIMIT 1";
                    $stmtTorneio = $pdo->query($sqlTorneioAtivo);
                    $torneioAtivo = $stmtTorneio->fetch(PDO::FETCH_ASSOC);

                    if (!$torneioAtivo) {
                        echo '<div class="no-tournament-message">';
                        echo '<h3>Nenhum torneio ativo</h3>';
                        echo '<p>Não há nenhum torneio ativo no momento.</p>';
                        echo '</div>';
                        return;
                    }

                    $tournament_id = $torneioAtivo['id'];

                    // Buscar apenas grupos do torneio ativo
                    $sqlGrupos = "SELECT id, nome FROM grupos WHERE tournament_id = ? ORDER BY nome";
                    $stmtGrupos = $pdo->prepare($sqlGrupos);
                    $stmtGrupos->execute([$tournament_id]);

                    $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

                    if ($grupos) {
                        foreach ($grupos as $rowGrupos) {
                            $grupoId = $rowGrupos['id'];
                            $grupoNome = $rowGrupos['nome'];

                            echo '<div class="grupo-container fade-in">';
                            echo '<div class="grupo-header fade-in">' . htmlspecialchars($grupoNome) . '</div>';
                            echo '<div class="tabela-flex fade-in">';
                            echo '<div class="tabela-flex-header fade-in">';
                            echo '<div class="clube fade-in">Clube</div>';
                            echo '<div class="small-col fade-in">P</div>';
                            echo '<div class="small-col fade-in">J</div>';
                            echo '<div class="small-col fade-in">V</div>';
                            echo '<div class="small-col fade-in">E</div>';
                            echo '<div class="small-col fade-in">D</div>';
                            echo '<div class="small-col fade-in">GP</div>';
                            echo '<div class="small-col fade-in">GC</div>';
                            echo '<div class="small-col fade-in">SG</div>';
                            echo '<div class="small-col fade-in">%</div>';
                            echo '<div class="larger-col fade-in">ÚLT. JOGOS</div>';
                            echo '</div>';

                            $sqlTimes = "SELECT t.id, t.nome, t.logo,
                                            COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND t.id = j.timeA_id THEN j.gols_marcados_timeA
                                                             WHEN j.resultado_timeB IS NOT NULL AND t.id = j.timeB_id THEN j.gols_marcados_timeB
                                                             ELSE 0 END),0) AS gm,
                                            COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND t.id = j.timeA_id THEN j.gols_marcados_timeB
                                                             WHEN j.resultado_timeB IS NOT NULL AND t.id = j.timeB_id THEN j.gols_marcados_timeA
                                                             ELSE 0 END),0) AS gc,
                                            COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND ((t.id = j.timeA_id AND j.gols_marcados_timeA > j.gols_marcados_timeB) OR (t.id = j.timeB_id AND j.gols_marcados_timeB > j.gols_marcados_timeA)) THEN 1 ELSE 0 END),0) AS vitorias,
                                            COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND j.gols_marcados_timeA = j.gols_marcados_timeB THEN 1 ELSE 0 END),0) AS empates,
                                            COALESCE(SUM(CASE WHEN j.resultado_timeA IS NOT NULL AND ((t.id = j.timeA_id AND j.gols_marcados_timeA < j.gols_marcados_timeB) OR (t.id = j.timeB_id AND j.gols_marcados_timeB < j.gols_marcados_timeA)) THEN 1 ELSE 0 END),0) AS derrotas,
                                            COUNT(CASE WHEN j.resultado_timeA IS NOT NULL THEN j.id END) AS partidas
                                        FROM times t
                                        LEFT JOIN jogos_fase_grupos j ON (t.id = j.timeA_id OR t.id = j.timeB_id) AND t.grupo_id = j.grupo_id
                                        WHERE t.grupo_id = :grupoId AND t.tournament_id = :tournament_id
                                        GROUP BY t.id
                                        ORDER BY (vitorias*3 + empates) DESC, (gm - gc) DESC, gm DESC";

                            $stmtTimes = $pdo->prepare($sqlTimes);
                            $stmtTimes->execute(['grupoId' => $grupoId, 'tournament_id' => $tournament_id]);
                            $times = $stmtTimes->fetchAll(PDO::FETCH_ASSOC);

                            if ($times) {
                                $posicao = 1;
                                foreach ($times as $rowTimes) {
                                    $pts = ($rowTimes['vitorias'] * 3) + $rowTimes['empates'];
                                    $sg = $rowTimes['gm'] - $rowTimes['gc'];

                                    echo '<div class="tabela-flex-row fade-in">';
                                    echo '<div class="time-info fade-in">';
                                    echo '<span class="posicao_num">' . $posicao . '</span>';
                                    if (!empty($rowTimes['logo'])) {
                                        $imageData = base64_encode($rowTimes['logo']);
                                        $imageSrc = 'data:image/jpeg;base64,'.$imageData;
                                        echo '<img src="' . $imageSrc . '" class="logo-time fade-in">';
                                    }
                                    echo '<span class="time-name">' . htmlspecialchars($rowTimes['nome']) . '</span>';
                                    echo '</div>';
                                    echo '<div class="small-col fade-in">' . $pts . '</div>';
                                    echo '<div class="small-col fade-in">' . $rowTimes['partidas'] . '</div>';
                                    echo '<div class="small-col fade-in">' . $rowTimes['vitorias'] . '</div>';
                                    echo '<div class="small-col fade-in">' . $rowTimes['empates'] . '</div>';
                                    echo '<div class="small-col fade-in">' . $rowTimes['derrotas'] . '</div>';
                                    echo '<div class="small-col fade-in">' . $rowTimes['gm'] . '</div>';
                                    echo '<div class="small-col fade-in">' . $rowTimes['gc'] . '</div>';
                                    echo '<div class="small-col fade-in">' . $sg . '</div>';
                                    echo '<div class="small-col fade-in">' . formatarPorcentagemAproveitamento($rowTimes['vitorias'], $rowTimes['partidas']) . '</div>';
                                    echo '<div class="larger-col fade-in">';
                                    echo gerarUltimosJogos($pdo, $rowTimes['id']);
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
                    } else {
                        echo "Nenhum grupo encontrado.";
                    }
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

                function gerarUltimosJogos($pdo, $timeId) {
                    $sqlJogos = "SELECT CASE
                                        WHEN timeA_id = :timeId THEN resultado_timeA
                                        WHEN timeB_id = :timeId THEN resultado_timeB
                                        ELSE 'G'
                                    END AS resultado
                                FROM jogos_fase_grupos
                                WHERE (timeA_id = :timeId OR timeB_id = :timeId)
                                AND resultado_timeA IS NOT NULL
                                ORDER BY data_jogo DESC
                                LIMIT 5";

                    $stmtJogos = $pdo->prepare($sqlJogos);
                    $stmtJogos->execute(['timeId' => $timeId]);
                    $resultJogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

                    $ultimosJogos = [];

                    if ($resultJogos) {
                        foreach ($resultJogos as $rowJogos) {
                            $ultimosJogos[] = $rowJogos['resultado'];
                        }
                    }

                    while (count($ultimosJogos) < 5) {
                        $ultimosJogos[] = 'G';
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