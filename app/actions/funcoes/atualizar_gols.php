<?php
include '../../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = conectar();

    if (isset($_POST['confrontos']) && !empty($_POST['confrontos'])) {
        foreach ($_POST['confrontos'] as $confrontoId) {
            if (isset($_POST['golsA_' . $confrontoId]) && isset($_POST['golsB_' . $confrontoId])) {
                $golsA = intval($_POST['golsA_' . $confrontoId]);
                $golsB = intval($_POST['golsB_' . $confrontoId]);

                if ($golsA > $golsB) {
                    $resultadoA = 'V';
                    $resultadoB = 'D';
                } elseif ($golsA < $golsB) {
                    $resultadoA = 'D';
                    $resultadoB = 'V';
                } else {
                    $resultadoA = 'E';
                    $resultadoB = 'E';
                }

                $sqlUpdate = "UPDATE jogos_fase_grupos SET 
                              gols_marcados_timeA = :golsA, 
                              gols_marcados_timeB = :golsB, 
                              resultado_timeA = :resultadoA, 
                              resultado_timeB = :resultadoB 
                              WHERE id = :id";

                $stmt = $pdo->prepare($sqlUpdate);
                $stmt->execute([
                    ':golsA' => $golsA,
                    ':golsB' => $golsB,
                    ':resultadoA' => $resultadoA,
                    ':resultadoB' => $resultadoB,
                    ':id' => $confrontoId
                ]);
            } else {
                die("Dados insuficientes para confronto ID $confrontoId.");
            }
        }

        header('Location: /copadaspanelas2/app/pages/adm/rodadas_adm.php');
        exit();
    } else {
        die("Nenhum confronto selecionado para atualização.");
    }
} else {
    die("Método inválido. Apenas POST permitido.");
}