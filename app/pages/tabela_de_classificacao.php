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
            <div style="margin-bottom: 20px;">
                <h4 class="fade-in">Tabela de Classificação</h4>
            </div>
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
                                            COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team1_goals
                                                             WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team2_goals
                                                             ELSE 0 END),0) AS gm,
                                            COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team2_goals
                                                             WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team1_goals
                                                             ELSE 0 END),0) AS gc,
                                            COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND ((t.id = m.team1_id AND m.team1_goals > m.team2_goals) OR (t.id = m.team2_id AND m.team2_goals > m.team1_goals)) THEN 1 ELSE 0 END),0) AS vitorias,
                                            COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND m.team1_goals = m.team2_goals THEN 1 ELSE 0 END),0) AS empates,
                                            COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND ((t.id = m.team1_id AND m.team1_goals < m.team2_goals) OR (t.id = m.team2_id AND m.team2_goals < m.team1_goals)) THEN 1 ELSE 0 END),0) AS derrotas,
                                            COUNT(CASE WHEN m.status = 'finalizado' THEN m.id END) AS partidas,
                                            (COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND ((t.id = m.team1_id AND m.team1_goals > m.team2_goals) OR (t.id = m.team2_id AND m.team2_goals > m.team1_goals)) THEN 1 ELSE 0 END),0) * 3 +
                                             COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND m.team1_goals = m.team2_goals THEN 1 ELSE 0 END),0)) AS pontos,
                                            (COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team1_goals
                                                             WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team2_goals
                                                             ELSE 0 END),0) -
                                             COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team2_goals
                                                             WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team1_goals
                                                             ELSE 0 END),0)) AS saldo_gols
                                        FROM times t
                                        LEFT JOIN matches m ON (t.id = m.team1_id OR t.id = m.team2_id) AND t.grupo_id = m.group_id AND m.phase = 'grupos'
                                        WHERE t.grupo_id = :grupoId AND t.tournament_id = :tournament_id
                                        GROUP BY t.id, t.nome, t.logo
                                        ORDER BY pontos DESC, saldo_gols DESC, gm DESC, t.nome ASC";

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
                    // Nova função do zero - mais simples e direta
                    $sql = "SELECT team1_id, team2_id, team1_goals, team2_goals, match_date
                            FROM matches
                            WHERE (team1_id = ? OR team2_id = ?)
                            AND status = 'finalizado'
                            AND phase = 'grupos'
                            ORDER BY match_date DESC
                            LIMIT 5";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$timeId, $timeId]);
                    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $resultados = [];

                    foreach ($jogos as $jogo) {
                        $team1_id = $jogo['team1_id'];
                        $team2_id = $jogo['team2_id'];
                        $gols1 = $jogo['team1_goals'];
                        $gols2 = $jogo['team2_goals'];

                        if ($team1_id == $timeId) {
                            // Time jogou como team1
                            if ($gols1 > $gols2) {
                                $resultados[] = 'V'; // Vitória
                            } elseif ($gols1 < $gols2) {
                                $resultados[] = 'D'; // Derrota
                            } else {
                                $resultados[] = 'E'; // Empate
                            }
                        } else {
                            // Time jogou como team2
                            if ($gols2 > $gols1) {
                                $resultados[] = 'V'; // Vitória
                            } elseif ($gols2 < $gols1) {
                                $resultados[] = 'D'; // Derrota
                            } else {
                                $resultados[] = 'E'; // Empate
                            }
                        }
                    }

                    // Completar com 'G' até ter 5 resultados
                    while (count($resultados) < 5) {
                        $resultados[] = 'G';
                    }

                    // Gerar HTML com círculos perfeitos (tamanho reduzido)
                    $html = '';
                    foreach ($resultados as $resultado) {
                        switch ($resultado) {
                            case 'V':
                                $html .= '<div style="background-color: #28a745; display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin: 2px; border: 1px solid #1e7e34; flex-shrink: 0; min-width: 12px; min-height: 12px; box-sizing: border-box;" title="Vitória"></div>';
                                break;
                            case 'D':
                                $html .= '<div style="background-color: #dc3545; display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin: 2px; border: 1px solid #bd2130; flex-shrink: 0; min-width: 12px; min-height: 12px; box-sizing: border-box;" title="Derrota"></div>';
                                break;
                            case 'E':
                                $html .= '<div style="background-color: #6c757d; display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin: 2px; border: 1px solid #545b62; flex-shrink: 0; min-width: 12px; min-height: 12px; box-sizing: border-box;" title="Empate"></div>';
                                break;
                            default:
                                $html .= '<div style="background-color: #f8f9fa; display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin: 2px; border: 1px solid #dee2e6; flex-shrink: 0; min-width: 12px; min-height: 12px; box-sizing: border-box;" title="Sem jogo"></div>';
                                break;
                        }
                    }

                    return $html;
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
    // Animações fade-in
    document.querySelectorAll('.fade-in').forEach(function(el, i) {
        setTimeout(() => el.classList.add('visible'), i * 20);
    });

    // Verificar automaticamente se deve criar eliminatórias (silencioso)
    fetch('../actions/funcoes/auto_classificacao.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            // Execução silenciosa - sem notificação
        })
        .catch(error => {
            console.log('Verificação automática silenciosa falhou:', error);
        });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>