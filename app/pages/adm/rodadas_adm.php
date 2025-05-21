<?php
session_start();
include("../../actions/cadastro_adm/session_check.php");
include_once '../../config/conexao.php';
$pdo = conectar();
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Rodadas</title>
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/rodadas_adm.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/cssfooter.css">
    <link rel="stylesheet" href="/copadaspanelas2/public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'header_adm.php'; ?>

<h1 class="titulo-central fade-in">FASES DE GRUPO</h1>

<div class="rodada_container1 fade-in">
    <div id="rodadas-wrapper">
        <div class="nav-arrow left" onclick="previousRodada()"><img src="../../../public/img/esquerda.svg" alt=""></div>
        <div class="table-container">
            <?php exibirRodadas($pdo, $isAdmin); ?>
        </div>
        <div class="nav-arrow right" onclick="nextRodada()"><img src="../../../public/img/direita.svg" alt=""></div>
    </div>

    <?php if ($isAdmin): ?>
        <a href="../../actions/funcoes/confrontos_rodadas.php" class="btn-redirect" id="confirm-link">Classificar Confrontos Rodadas</a>
    <?php endif; ?>
</div>

<!-- Modal de Confirmação -->
<div id="confirm-modal" class="modal fade-in">
    <div class="modal-content">
        <span class="close-btn" id="close-btn">&times;</span>
        <p>Tem certeza que deseja classificar os confrontos das rodadas?</p>
        <button id="confirm-btn">Sim</button>
        <button id="cancel-btn">Não</button>
    </div>
</div>

<?php include '../footer.php'; ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>
<script src="/public/js/rodadas_modal.js"></script>
</body>
</html>

<?php
function exibirRodadas($pdo, $isAdmin) {
    $sqlRodadas = "SELECT DISTINCT rodada FROM jogos_fase_grupos ORDER BY rodada";
    $stmtRodadas = $pdo->prepare($sqlRodadas);
    $stmtRodadas->execute();
    $rodadas = $stmtRodadas->fetchAll(PDO::FETCH_COLUMN);

    if (!$rodadas) {
        echo '<p>Nenhuma rodada encontrada.</p>';
        return;
    }

    foreach ($rodadas as $rodada) {
        echo '<div class="rodada-container">';
        echo '<div class="rodada-header">';
        echo '<h2 class="rodada-header_h1">' . htmlspecialchars($rodada) . 'ª RODADA</h2>';
        echo '</div>';
        echo '<table>';

        $sqlGrupos = "SELECT DISTINCT grupo_id, nome AS grupo_nome FROM jogos_fase_grupos 
                      JOIN grupos ON jogos_fase_grupos.grupo_id = grupos.id ORDER BY grupo_id";
        $stmtGrupos = $pdo->prepare($sqlGrupos);
        $stmtGrupos->execute();
        $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

        foreach ($grupos as $rowGrupo) {
            $grupoId = $rowGrupo['grupo_id'];
            $grupoNome = substr($rowGrupo['grupo_nome'], -1);

            $sqlConfrontos = "SELECT jfg.id, tA.nome AS nome_timeA, tB.nome AS nome_timeB, 
                                     tA.logo AS logo_timeA, tB.logo AS logo_timeB, 
                                     jfg.gols_marcados_timeA, jfg.gols_marcados_timeB
                              FROM jogos_fase_grupos jfg
                              JOIN times tA ON jfg.timeA_id = tA.id
                              JOIN times tB ON jfg.timeB_id = tB.id
                              WHERE jfg.grupo_id = :grupoId AND jfg.rodada = :rodada";
            $stmtConfrontos = $pdo->prepare($sqlConfrontos);
            $stmtConfrontos->execute(['grupoId' => $grupoId, 'rodada' => $rodada]);
            $confrontos = $stmtConfrontos->fetchAll(PDO::FETCH_ASSOC);

            if ($confrontos) {
                if ($isAdmin) echo '<form method="POST" action="../../actions/funcoes/atualizar_gols.php">';
                foreach ($confrontos as $rowConfronto) {
                    $jogoId = $rowConfronto['id'];
                    $timeA_nome = htmlspecialchars($rowConfronto['nome_timeA']);
                    $timeB_nome = htmlspecialchars($rowConfronto['nome_timeB']);
                    $logoA = !empty($rowConfronto['logo_timeA']) ? 'data:image/jpeg;base64,' . base64_encode($rowConfronto['logo_timeA']) : '';
                    $logoB = !empty($rowConfronto['logo_timeB']) ? 'data:image/jpeg;base64,' . base64_encode($rowConfronto['logo_timeB']) : '';
                    $golsA = $rowConfronto['gols_marcados_timeA'];
                    $golsB = $rowConfronto['gols_marcados_timeB'];

                    $resultadoA = $golsA > $golsB ? 'V' : ($golsA < $golsB ? 'D' : 'E');
                    $resultadoB = $golsB > $golsA ? 'V' : ($golsB < $golsA ? 'D' : 'E');

                    echo '<tr class="time_teste">';
                    echo '<td class="time-row">';
                    if ($logoA) echo '<img src="' . $logoA . '" class="logo-time">';
                    echo '<span class="time-name">' . $timeA_nome . '</span></td>';
                    
                    if ($isAdmin) {
                        echo '<td><input type="number" name="golsA_' . $jogoId . '" min="0" value="' . intval($golsA) . '"></td>';
                        echo '<td>X</td>';
                        echo '<td><input type="number" name="golsB_' . $jogoId . '" min="0" value="' . intval($golsB) . '"></td>';
                    } else {
                        echo '<td>' . intval($golsA) . '</td><td>X</td><td>' . intval($golsB) . '</td>';
                    }

                    echo '<td class="time-row">';
                    echo '<span class="time-name_b">' . $timeB_nome . '</span>';
                    if ($logoB) echo '<img src="' . $logoB . '" class="logo-time">';
                    echo '</td>';

                    if ($isAdmin) {
                        echo '<input type="hidden" name="confrontos[]" value="' . $jogoId . '">';
                        echo '<input type="hidden" name="resultadoA_' . $jogoId . '" value="' . $resultadoA . '">';
                        echo '<input type="hidden" name="resultadoB_' . $jogoId . '" value="' . $resultadoB . '">';
                    }

                    echo '</tr>';
                }

                if ($isAdmin) {
                    echo '<tr class="tr_teste"><td colspan="7" class="btn-save-center"><input type="submit" class="btn-save" value="Salvar resultados"></td></tr>';
                    echo '</form>';
                }
            } else {
                echo '<tr><td colspan="7">Nenhum confronto encontrado para o grupo ' . htmlspecialchars($grupoNome) . ' na ' . htmlspecialchars($rodada) . 'ª rodada.</td></tr>';
            }
        }

        echo '</table>';
        echo '</div>';
    }
}
?>