<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estatísticas dos Jogadores</title>
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/exibir_finais.css">
    <link rel="stylesheet" href="../../public/css/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">    
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>

<body>
    <?php require_once '../pages/header_adm.php'; ?>
    <div class="main">
        <h1 id="titulo_eli" class="fade-in">ELIMINATORIA</h1>
        <div class="container fade-in">
            <div class="bracket">
                <?php
                    include '../config/conexao.php';

                    function exibirImagemLogo($conn, $time_id) {
                        $sql = "SELECT logo FROM times WHERE id = $time_id";
                        $result = $conn->query($sql);
                        if ($result && $row = $result->fetch_assoc()) {
                            $logo_bin = $row['logo'];
                            if ($logo_bin) {
                                $logo_base64 = base64_encode($logo_bin);
                                echo "<img class='flag' src='data:image/png;base64,{$logo_base64}' alt='Logo do time'>";
                            } else {
                                echo "<img class='flag' src='path/to/logos/default.png' alt='Logo padrão'>";
                            }
                        } else {
                            echo "<img class='flag' src='path/to/logos/default.png' alt='Logo padrão'>";
                        }
                    }

                    function exibirConfrontos($conn, $fase, $count, $start = 0) {
                        $tabelaConfrontos = '';
                        switch ($fase) {
                            case 'oitavas':
                                $tabelaConfrontos = 'oitavas_de_final_confrontos';
                                break;
                            case 'quartas':
                                $tabelaConfrontos = 'quartas_de_final_confrontos';
                                break;
                            case 'semifinais':
                                $tabelaConfrontos = 'semifinais_confrontos';
                                break;
                            case 'final':
                                $tabelaConfrontos = 'final_confrontos';
                                break;
                            default:
                                die("Fase desconhecida: " . $fase);
                        }

                        $sql = "SELECT * FROM $tabelaConfrontos LIMIT $start, $count";
                        $result = $conn->query($sql);
                        if (!$result) {
                            die("Erro na consulta de confrontos: " . $conn->error);
                        }

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $timeA_id = $row['timeA_id'];
                                $timeB_id = $row['timeB_id'];

                                // Consultar os nomes dos times
                                $timeA_nome = $conn->query("SELECT nome FROM times WHERE id = $timeA_id")->fetch_assoc()['nome'];
                                $timeB_nome = $conn->query("SELECT nome FROM times WHERE id = $timeB_id")->fetch_assoc()['nome'];

                                $timeA_nome_abreviado = substr($timeA_nome, 0, 3); // Pegar as três primeiras letras do nome
                                $timeB_nome_abreviado = substr($timeB_nome, 0, 3); // Pegar as três primeiras letras do nome

                                if ($fase == 'final') {
                                    // Exibir o confronto da final em uma única div
                                    echo "<div class='match final-match'>";
                                    echo "<div class='team'>";
                                    exibirImagemLogo($conn, $timeA_id);
                                    // echo "<span class='team-name' title='{$timeA_nome}'>{$timeA_nome_abreviado}</span>";
                                    // echo "<span class='score'>{$row['gols_marcados_timeA']}</span>";
                                    echo "</div>";
                                    echo "<span class='vs'>X</span>"; // Adiciona um separador 'VS'
                                    echo "<div class='team'>";
                                    exibirImagemLogo($conn, $timeB_id);
                                    // echo "<span class='team-name' title='{$timeB_nome}'>{$timeB_nome_abreviado}</span>";
                                    // echo "<span class='score'>{$row['gols_marcados_timeB']}</span>";
                                    echo "</div>";
                                    echo "</div>";
                                } else {
                                    // Exibir confrontos para outras fases no formato atual
                                    echo "<div class='match'>";
                                    echo "<div class='team'>";
                                    exibirImagemLogo($conn, $timeA_id);
                                    echo "<span class='team-name' title='{$timeA_nome}'>{$timeA_nome_abreviado}</span>";
                                    echo "<span class='score'>{$row['gols_marcados_timeA']}</span>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "<div class='match'>";
                                    echo "<div class='team'>";
                                    exibirImagemLogo($conn, $timeB_id);
                                    echo "<span class='team-name' title='{$timeB_nome}'>{$timeB_nome_abreviado}</span>";
                                    echo "<span class='score'>{$row['gols_marcados_timeB']}</span>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                            }
                        }
                    }

                    // Exibir os confrontos das fases na ordem desejada
                    echo "<div class='column'>";
                    // echo "<div class='round-label'>Oitavas</div>";
                    exibirConfrontos($conn, 'oitavas', 4, 0);
                    echo "</div>";

                    echo "<div class='column'>";
                    echo "<div class='round-label'>Quartas</div>";
                    exibirConfrontos($conn, 'quartas', 2, 0);
                    echo "</div>";

                    echo "<div class='column'>";
                    echo "<div class='round-label'>Semifinais</div>";
                    exibirConfrontos($conn, 'semifinais', 1, 0);
                    echo "</div>";

                    echo "<div class='column'>";
                    echo "<div class='round-label'>Final</div>";
                    exibirConfrontos($conn, 'final', 1);
                    echo "</div>";

                    echo "<div class='column'>";
                    echo "<div class='round-label'>Semifinais</div>";
                    exibirConfrontos($conn, 'semifinais', 1, 1);
                    echo "</div>";

                    echo "<div class='column'>";
                    echo "<div class='round-label'>Quartas</div>";
                    exibirConfrontos($conn, 'quartas', 2, 2);
                    echo "</div>";

                    echo "<div class='column'>";
                    // echo "<div class='round-label'>Oitavas</div>";
                    exibirConfrontos($conn, 'oitavas', 4, 4);
                    echo "</div>";
                ?>
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

    <?php include 'footer.php'?>  
</body>
</html>
