<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Vamos testar cada passo individualmente
$timeId = 46; // Time A

echo "<h1>🔍 Debug Básico - Passo a Passo</h1>";
echo "<p><strong>Time ID:</strong> $timeId</p>";

echo "<hr>";

// PASSO 1: Testar conexão
echo "<h2>PASSO 1: Conexão com Banco</h2>";
try {
    $test = $pdo->query("SELECT 1");
    echo "✅ Conexão OK<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    die();
}

echo "<hr>";

// PASSO 2: Verificar se o time existe
echo "<h2>PASSO 2: Verificar se Time Existe</h2>";
$stmt = $pdo->prepare("SELECT id, nome FROM times WHERE id = ?");
$stmt->execute([$timeId]);
$time = $stmt->fetch(PDO::FETCH_ASSOC);

if ($time) {
    echo "✅ Time encontrado: " . $time['nome'] . " (ID: " . $time['id'] . ")<br>";
} else {
    echo "❌ Time não encontrado!<br>";
    die();
}

echo "<hr>";

// PASSO 3: Consulta mais básica possível
echo "<h2>PASSO 3: Consulta Básica</h2>";
$sql_basico = "SELECT * FROM matches WHERE team1_id = ? OR team2_id = ?";
$stmt = $pdo->prepare($sql_basico);
$stmt->execute([$timeId, $timeId]);
$todos_jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total de jogos (qualquer status/phase): " . count($todos_jogos) . "<br>";

if (count($todos_jogos) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Team1</th><th>Team2</th><th>Gols1</th><th>Gols2</th><th>Status</th><th>Phase</th></tr>";
    foreach ($todos_jogos as $jogo) {
        echo "<tr>";
        echo "<td>{$jogo['id']}</td>";
        echo "<td>{$jogo['team1_id']}</td>";
        echo "<td>{$jogo['team2_id']}</td>";
        echo "<td>{$jogo['team1_goals']}</td>";
        echo "<td>{$jogo['team2_goals']}</td>";
        echo "<td>{$jogo['status']}</td>";
        echo "<td>{$jogo['phase']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhum jogo encontrado para este time!<br>";
    die();
}

echo "<hr>";

// PASSO 4: Filtrar por status
echo "<h2>PASSO 4: Filtrar por Status 'finalizado'</h2>";
$sql_status = "SELECT * FROM matches WHERE (team1_id = ? OR team2_id = ?) AND status = 'finalizado'";
$stmt = $pdo->prepare($sql_status);
$stmt->execute([$timeId, $timeId]);
$jogos_finalizados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Jogos finalizados: " . count($jogos_finalizados) . "<br>";

if (count($jogos_finalizados) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Team1</th><th>Team2</th><th>Gols1</th><th>Gols2</th><th>Phase</th></tr>";
    foreach ($jogos_finalizados as $jogo) {
        echo "<tr>";
        echo "<td>{$jogo['id']}</td>";
        echo "<td>{$jogo['team1_id']}</td>";
        echo "<td>{$jogo['team2_id']}</td>";
        echo "<td>{$jogo['team1_goals']}</td>";
        echo "<td>{$jogo['team2_goals']}</td>";
        echo "<td>{$jogo['phase']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhum jogo finalizado!<br>";
}

echo "<hr>";

// PASSO 5: Filtrar por phase
echo "<h2>PASSO 5: Filtrar por Phase 'grupos'</h2>";
$sql_phase = "SELECT * FROM matches WHERE (team1_id = ? OR team2_id = ?) AND status = 'finalizado' AND phase = 'grupos'";
$stmt = $pdo->prepare($sql_phase);
$stmt->execute([$timeId, $timeId]);
$jogos_grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Jogos da fase de grupos finalizados: " . count($jogos_grupos) . "<br>";

if (count($jogos_grupos) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Team1</th><th>Team2</th><th>Gols1</th><th>Gols2</th><th>Data</th></tr>";
    foreach ($jogos_grupos as $jogo) {
        echo "<tr>";
        echo "<td>{$jogo['id']}</td>";
        echo "<td>{$jogo['team1_id']}</td>";
        echo "<td>{$jogo['team2_id']}</td>";
        echo "<td>{$jogo['team1_goals']}</td>";
        echo "<td>{$jogo['team2_goals']}</td>";
        echo "<td>{$jogo['match_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhum jogo da fase de grupos finalizado!<br>";
}

echo "<hr>";

// PASSO 6: Testar a função atual da tabela de classificação
echo "<h2>PASSO 6: Testar Função Atual</h2>";

// Incluir a função da tabela de classificação
ob_start();
include '../tabela_de_classificacao.php';
$conteudo = ob_get_clean();

// Extrair apenas a função
function gerarUltimosJogosTest($pdo, $timeId) {
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
    
    echo "Jogos encontrados na função: " . count($jogos) . "<br>";
    
    $resultados = [];
    
    foreach ($jogos as $jogo) {
        $team1_id = $jogo['team1_id'];
        $team2_id = $jogo['team2_id'];
        $gols1 = $jogo['team1_goals'];
        $gols2 = $jogo['team2_goals'];
        
        echo "Processando jogo: Team{$team1_id} {$gols1}x{$gols2} Team{$team2_id}<br>";
        
        if ($team1_id == $timeId) {
            // Time jogou como team1
            if ($gols1 > $gols2) {
                $resultados[] = 'V'; // Vitória
                echo "→ Resultado: Vitória (time1)<br>";
            } elseif ($gols1 < $gols2) {
                $resultados[] = 'D'; // Derrota
                echo "→ Resultado: Derrota (time1)<br>";
            } else {
                $resultados[] = 'E'; // Empate
                echo "→ Resultado: Empate (time1)<br>";
            }
        } else {
            // Time jogou como team2
            if ($gols2 > $gols1) {
                $resultados[] = 'V'; // Vitória
                echo "→ Resultado: Vitória (time2)<br>";
            } elseif ($gols2 < $gols1) {
                $resultados[] = 'D'; // Derrota
                echo "→ Resultado: Derrota (time2)<br>";
            } else {
                $resultados[] = 'E'; // Empate
                echo "→ Resultado: Empate (time2)<br>";
            }
        }
    }
    
    // Completar com 'G' até ter 5 resultados
    while (count($resultados) < 5) {
        $resultados[] = 'G';
    }
    
    echo "Array de resultados: " . implode(', ', $resultados) . "<br>";
    
    // Gerar HTML
    $html = '';
    foreach ($resultados as $resultado) {
        switch ($resultado) {
            case 'V':
                $html .= '<div class="inf" title="Vitória"></div>';
                break;
            case 'D':
                $html .= '<div class="inf2" title="Derrota"></div>';
                break;
            case 'E':
                $html .= '<div class="inf3" title="Empate"></div>';
                break;
            default:
                $html .= '<div class="inf4" title="Sem jogo"></div>';
                break;
        }
    }
    
    echo "HTML gerado: " . htmlspecialchars($html) . "<br>";
    echo "HTML vazio? " . (empty($html) ? "SIM" : "NÃO") . "<br>";
    
    // Debug temporário
    $debug = "<!-- DEBUG: TimeID=$timeId, Jogos=" . count($jogos) . ", Resultados=" . implode(',', $resultados) . " -->";
    
    return $debug . $html;
}

$resultado_funcao = gerarUltimosJogosTest($pdo, $timeId);

echo "<p><strong>Resultado final da função:</strong></p>";
echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($resultado_funcao);
echo "</div>";

echo "<p><strong>Visualização:</strong></p>";
echo "<div style='background: white; padding: 10px; border: 1px solid #ddd;'>";
echo $resultado_funcao;
echo "</div>";

echo "<hr>";

// PASSO 7: Verificar se o problema está na chamada da função
echo "<h2>PASSO 7: Simular Chamada na Tabela</h2>";

echo "<p>Simulando o que acontece na tabela de classificação:</p>";

$ultimosJogosHtml = gerarUltimosJogosTest($pdo, $timeId);
echo "<p>HTML retornado: " . htmlspecialchars($ultimosJogosHtml) . "</p>";

if (empty(trim(strip_tags($ultimosJogosHtml)))) {
    echo "<p style='color: red;'>❌ DEBUG: Vazio (igual ao problema!)</p>";
} else {
    echo "<p style='color: green;'>✅ HTML não está vazio</p>";
}

echo "<hr>";
echo "<h2>🎯 CONCLUSÃO</h2>";
echo "<p>Se chegou até aqui, o problema foi identificado em um dos passos acima.</p>";
echo "<p><a href='../tabela_de_classificacao.php'>📊 Testar na Classificação</a></p>";
?>
