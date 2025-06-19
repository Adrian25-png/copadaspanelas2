<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Times Classificados para as Finais</title>
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/classificar.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <?php include 'header_geral.php'; ?>
    <h1>CLASSIFICADOS</h1>
    <div class="main">
    <div id="tabela-wrapper" class="fade-in">
        <?php
        function exibirTimes($titulo, $tabela) {
            include_once '../config/conexao.php';
            $pdo = conectar();
        
            // Proteção contra SQL Injection
            $tabelasPermitidas = ['oitavas_de_final', 'quartas_de_final', 'semifinais', 'final'];
            if (!in_array($tabela, $tabelasPermitidas)) {
                echo "<p>Tabela inválida!</p>";
                return;
            }
        
            $sql = "SELECT t.id, t.logo, t.nome AS time_nome, tf.grupo_nome 
                    FROM $tabela AS tf
                    JOIN times AS t ON tf.time_id = t.id";
            $result = $pdo->query($sql);
        
            if ($result->rowCount() > 0) {
                echo "<div class='grupo-container fade-in'>";
                echo "<h2 class='grupo-header'>$titulo</h2>";
        
                $posicao = 1;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $logoData = !empty($row['logo']) ? 'data:image/jpeg;base64,' . base64_encode($row['logo']) : '';
        
                    echo "<div class='grupo-item fade-in'>";
                    echo "<div class='position'>" . $posicao . "</div>";
                    if (!empty($logoData)) {
                        echo "<img src=\"$logoData\" alt=\"Logo\">";
                    } else {
                        echo "<div class='no-logo'>Sem Logo</div>"; // opcional
                    }
                    echo "<div class='time-info'>";
                    echo "<div class='time-name'>" . htmlspecialchars($row['time_nome']) . "</div>";
                    echo "<div class='grupo-name'>" . htmlspecialchars($row['grupo_nome']) . "</div>";
                    echo "</div>";
                    echo "</div>";
        
                    $posicao++;
                }
        
                echo "</div>";
            } else {
                echo "<p class='no-data'>Nenhum time encontrado para $titulo.</p>";
            }
        }

        exibirTimes('Oitavas de Final', 'oitavas_de_final');
        exibirTimes('Quartas de Final', 'quartas_de_final');
        exibirTimes('Semifinais', 'semifinais');
        exibirTimes('Final', 'final');
        ?>
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
