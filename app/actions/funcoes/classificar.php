<?php
include_once '../../config/conexao.php';
$pdo = conectar();

function atualizarFaseExecutada($pdo, $fase) {
    $stmt = $pdo->prepare("UPDATE fase_execucao SET executado = TRUE WHERE fase = ?");
    $stmt->execute([$fase]);
}

function faseJaExecutada($pdo, $fase) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM fase_execucao WHERE fase = ? AND executado = 1");
    $stmt->execute([$fase]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] > 0;
}

function inicializarFaseExecucao($pdo) {
    $fases = ['oitavas', 'quartas', 'semifinais', 'final'];
    foreach ($fases as $fase) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM fase_execucao WHERE fase = ?");
        $stmt->execute([$fase]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['count'] == 0) {
            $stmt = $pdo->prepare("INSERT INTO fase_execucao (fase) VALUES (?)");
            $stmt->execute([$fase]);
        }
    }
}

function validarClassificados($times_classificados, $esperado, $grupo) {
    if (count($times_classificados) < $esperado) {
        throw new Exception("Grupo $grupo não possui times suficientes para classificação.");
    }
}

function classificarOitavasDeFinal($pdo) {
    if (faseJaExecutada($pdo, 'oitavas')) return;

    $config = $pdo->query("SELECT fase_final, numero_grupos FROM configuracoes WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    $numeroGrupos = (int) $config['numero_grupos'];
    $num_oitavas = 16;
    $times_por_grupo = intdiv($num_oitavas, $numeroGrupos);

    $pdo->exec("TRUNCATE TABLE oitavas_de_final");
    $pdo->exec("TRUNCATE TABLE oitavas_de_final_confrontos");

    $times_classificados = [];
    for ($i = 1; $i <= $numeroGrupos; $i++) {
        $stmt = $pdo->prepare("SELECT * FROM times WHERE grupo_id = ? ORDER BY pts DESC, sg DESC, gm DESC, id ASC LIMIT ?");
        $stmt->execute([$i, $times_por_grupo]);
        $times_classificados[$i] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        validarClassificados($times_classificados[$i], $times_por_grupo, $i);
    }

    $confrontos = [];
    for ($i = 1; $i <= $numeroGrupos / 2; $i++) {
        $grupoA = $i;
        $grupoB = $i + $numeroGrupos / 2;

        foreach ($times_classificados[$grupoA] as $index => $timeA) {
            $timeB = $times_classificados[$grupoB][count($times_classificados[$grupoB]) - 1 - $index];
            $confrontos[] = ['timeA' => $timeA, 'timeB' => $timeB];
        }
    }

    foreach ($confrontos as $confronto) {
        foreach (['timeA', 'timeB'] as $time) {
            $stmt = $pdo->prepare("INSERT INTO oitavas_de_final (time_id, grupo_nome, time_nome) VALUES (?, ?, ?)");
            $stmt->execute([$confronto[$time]['id'], $confronto[$time]['grupo_nome'], $confronto[$time]['nome']]);
        }
        $stmt = $pdo->prepare("INSERT INTO oitavas_de_final_confrontos (timeA_id, timeB_id, fase) VALUES (?, ?, 'oitavas')");
        $stmt->execute([$confronto['timeA']['id'], $confronto['timeB']['id']]);
    }

    atualizarFaseExecutada($pdo, 'oitavas');
}

function classificarProximaFase($pdo, $fase_atual, $fase_nova, $tabela_atual, $tabela_confrontos, $tabela_nova, $tabela_confrontos_nova) {
    if (faseJaExecutada($pdo, $fase_nova)) return;

    $pdo->exec("TRUNCATE TABLE $tabela_nova");
    $pdo->exec("TRUNCATE TABLE $tabela_confrontos_nova");

    $stmt = $pdo->query("SELECT timeA_id, timeB_id, gols_marcados_timeA, gols_marcados_timeB FROM $tabela_confrontos");
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $classificados = [];

    foreach ($resultados as $row) {
        // Garante que empate não passe times (só maior pontuação)
        if ($row['gols_marcados_timeA'] > $row['gols_marcados_timeB']) {
            $classificados[] = $row['timeA_id'];
        } elseif ($row['gols_marcados_timeB'] > $row['gols_marcados_timeA']) {
            $classificados[] = $row['timeB_id'];
        }
    }

    foreach ($classificados as $id) {
        $stmt = $pdo->prepare("SELECT * FROM $tabela_atual WHERE time_id = ?");
        $stmt->execute([$id]);
        $time = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("INSERT INTO $tabela_nova (time_id, grupo_nome, time_nome) VALUES (?, ?, ?)");
        $stmt->execute([$time['time_id'], $time['grupo_nome'], $time['time_nome']]);
        }
        for ($i = 0; $i < count($classificados) / 2; $i++) {
            $stmt = $pdo->prepare("INSERT INTO $tabela_confrontos_nova (timeA_id, timeB_id, fase) VALUES (?, ?, ?)");
            $stmt->execute([$classificados[$i], $classificados[count($classificados) - 1 - $i], $fase_nova]);
        }
        
        atualizarFaseExecutada($pdo, $fase_nova);

}

function classificarQuartasDeFinal($pdo) {
classificarProximaFase($pdo, 'oitavas', 'quartas', 'oitavas_de_final', 'oitavas_de_final_confrontos', 'quartas_de_final', 'quartas_de_final_confrontos');
}

function classificarSemifinais($pdo) {
classificarProximaFase($pdo, 'quartas', 'semifinais', 'quartas_de_final', 'quartas_de_final_confrontos', 'semifinais', 'semifinais_confrontos');
}

function classificarFinal($pdo) {
classificarProximaFase($pdo, 'semifinais', 'final', 'semifinais', 'semifinais_confrontos', 'final', 'final_confrontos');
}

inicializarFaseExecucao($pdo);
?>