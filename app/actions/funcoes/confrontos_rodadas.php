<?php
include '../../config/conexao.php';

// Trocar $conn para PDO usando sua conexão PDO do conexao.php
$pdo = conectar();

function gerarRodadas($pdo) {
    $sqlGrupos = "SELECT id, nome FROM grupos ORDER BY nome";
    $stmtGrupos = $pdo->query($sqlGrupos);

    $grupos = [];
    if ($stmtGrupos->rowCount() > 0) {
        while ($rowGrupos = $stmtGrupos->fetch(PDO::FETCH_ASSOC)) {
            $grupoId = $rowGrupos['id'];
            $grupoNome = $rowGrupos['nome'];

            // Buscar times no grupo
            $sqlTimes = "SELECT id, nome, logo FROM times WHERE grupo_id = :grupoId ORDER BY id";
            $stmtTimes = $pdo->prepare($sqlTimes);
            $stmtTimes->execute(['grupoId' => $grupoId]);
            $times = $stmtTimes->fetchAll(PDO::FETCH_ASSOC);

            $grupos[$grupoNome] = ['id' => $grupoId, 'times' => $times];
        }
    }

    $rodadas = [];
    foreach ($grupos as $grupoNome => $grupoData) {
        $times = $grupoData['times'];
        $grupoId = $grupoData['id'];
        $quantidadeTimes = count($times);

        // Adiciona BYE se times ímpar
        if ($quantidadeTimes % 2 != 0) {
            $times[] = ["id" => null, "nome" => "BYE", "logo" => null];
            $quantidadeTimes++;
        }

        $totalRodadas = $quantidadeTimes - 1;
        $jogosPorRodada = $quantidadeTimes / 2;

        $rodadasGrupo = [];
        for ($rodada = 0; $rodada < $totalRodadas; $rodada++) {
            $rodadasGrupo[$rodada] = [];
            for ($jogo = 0; $jogo < $jogosPorRodada; $jogo++) {
                $timeA = $times[$jogo];
                $timeB = $times[$quantidadeTimes - 1 - $jogo];

                if ($timeA["nome"] != "BYE" && $timeB["nome"] != "BYE") {
                    $rodadasGrupo[$rodada][] = [
                        'grupo_id' => $grupoId,
                        'timeA_id' => $timeA['id'],
                        'timeA_nome' => $timeA['nome'],
                        'timeB_id' => $timeB['id'],
                        'timeB_nome' => $timeB['nome']
                    ];
                }
            }
            // Rotaciona times (exceto o primeiro)
            $times = array_merge([$times[0]], array_slice($times, -1), array_slice($times, 1, -1));
        }

        foreach ($rodadasGrupo as $index => $partidas) {
            $rodadas[$index + 1][$grupoNome] = $partidas;
        }
    }

    return $rodadas;
}

function inserirConfrontosSeNaoExistir($pdo, $rodadas) {
    foreach ($rodadas as $rodada => $grupos) {
        foreach ($grupos as $grupoNome => $partidas) {
            foreach ($partidas as $partida) {
                // Verificar se confronto já existe para grupo, rodada e times
                $sqlCheck = "SELECT COUNT(*) FROM jogos_fase_grupos WHERE grupo_id = :grupo_id AND rodada = :rodada AND timeA_id = :timeA_id AND timeB_id = :timeB_id";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute([
                    'grupo_id' => $partida['grupo_id'],
                    'rodada' => $rodada,
                    'timeA_id' => $partida['timeA_id'],
                    'timeB_id' => $partida['timeB_id']
                ]);
                $exists = $stmtCheck->fetchColumn();

                if (!$exists) {
                    // Insere o confronto se não existir
                    $sqlInsert = "INSERT INTO jogos_fase_grupos (grupo_id, timeA_id, timeB_id, nome_timeA, nome_timeB, data_jogo, rodada) 
                                  VALUES (:grupo_id, :timeA_id, :timeB_id, :nome_timeA, :nome_timeB, NOW(), :rodada)";
                    $stmtInsert = $pdo->prepare($sqlInsert);
                    $stmtInsert->execute([
                        'grupo_id' => $partida['grupo_id'],
                        'timeA_id' => $partida['timeA_id'],
                        'timeB_id' => $partida['timeB_id'],
                        'nome_timeA' => $partida['timeA_nome'],
                        'nome_timeB' => $partida['timeB_nome'],
                        'rodada' => $rodada
                    ]);
                }
            }
        }
    }
}

$rodadas = gerarRodadas($pdo);
inserirConfrontosSeNaoExistir($pdo, $rodadas);

// Redirecionar após processar
header('Location: /copadaspanelas2/app/pages/adm/rodadas_adm.php?update=success');
exit;
?>