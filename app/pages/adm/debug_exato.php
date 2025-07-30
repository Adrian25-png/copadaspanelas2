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

echo "<h1>🎯 Debug Exato - Replicando Tabela de Classificação</h1>";

// Copiar a função EXATAMENTE como está na tabela de classificação
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
    
    // Debug temporário
    $debug = "<!-- DEBUG: TimeID=$timeId, Jogos=" . count($jogos) . ", Resultados=" . implode(',', $resultados) . " -->";
    
    return $debug . $html;
}

// Buscar um time exatamente como na tabela de classificação
$stmt = $pdo->prepare("SELECT id, nome FROM times WHERE tournament_id = (SELECT id FROM tournaments WHERE status = 'active' LIMIT 1) LIMIT 1");
$stmt->execute();
$rowTimes = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rowTimes) {
    die("Nenhum time encontrado!");
}

echo "<p><strong>Time selecionado:</strong> {$rowTimes['nome']} (ID: {$rowTimes['id']})</p>";

// Replicar EXATAMENTE o código da tabela de classificação
echo "<h2>Replicando código da tabela:</h2>";

echo '<div class="larger-col fade-in">';
$ultimosJogosHtml = gerarUltimosJogos($pdo, $rowTimes['id']);

echo "<p><strong>Valor de \$ultimosJogosHtml:</strong></p>";
echo "<pre>" . htmlspecialchars($ultimosJogosHtml) . "</pre>";

echo "<p><strong>Resultado de empty(trim(strip_tags(\$ultimosJogosHtml))):</strong> ";
$isEmpty = empty(trim(strip_tags($ultimosJogosHtml)));
echo $isEmpty ? "TRUE (vazio)" : "FALSE (não vazio)";
echo "</p>";

echo "<p><strong>Valor de trim(strip_tags(\$ultimosJogosHtml)):</strong></p>";
echo "<pre>'" . trim(strip_tags($ultimosJogosHtml)) . "'</pre>";

echo $ultimosJogosHtml;

// Debug temporário - remover depois
if (empty(trim(strip_tags($ultimosJogosHtml)))) {
    echo '<span style="color: red; font-size: 10px;">DEBUG: Vazio</span>';
}
echo '</div>';

echo "<hr>";

// Vamos testar manualmente cada parte
echo "<h2>Teste Manual:</h2>";

$sql = "SELECT team1_id, team2_id, team1_goals, team2_goals, match_date
        FROM matches 
        WHERE (team1_id = ? OR team2_id = ?) 
        AND status = 'finalizado'
        AND phase = 'grupos'
        ORDER BY match_date DESC 
        LIMIT 5";

$stmt = $pdo->prepare($sql);
$stmt->execute([$rowTimes['id'], $rowTimes['id']]);
$jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Jogos encontrados:</strong> " . count($jogos) . "</p>";

if (count($jogos) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Team1</th><th>Team2</th><th>Gols1</th><th>Gols2</th><th>Resultado</th></tr>";
    
    $resultados_manuais = [];
    foreach ($jogos as $jogo) {
        $team1_id = $jogo['team1_id'];
        $team2_id = $jogo['team2_id'];
        $gols1 = $jogo['team1_goals'];
        $gols2 = $jogo['team2_goals'];
        
        if ($team1_id == $rowTimes['id']) {
            if ($gols1 > $gols2) $resultado = 'V';
            elseif ($gols1 < $gols2) $resultado = 'D';
            else $resultado = 'E';
        } else {
            if ($gols2 > $gols1) $resultado = 'V';
            elseif ($gols2 < $gols1) $resultado = 'D';
            else $resultado = 'E';
        }
        
        $resultados_manuais[] = $resultado;
        
        echo "<tr>";
        echo "<td>$team1_id</td>";
        echo "<td>$team2_id</td>";
        echo "<td>$gols1</td>";
        echo "<td>$gols2</td>";
        echo "<td><strong>$resultado</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Array de resultados:</strong> " . implode(', ', $resultados_manuais) . "</p>";
    
    // Completar com G
    while (count($resultados_manuais) < 5) {
        $resultados_manuais[] = 'G';
    }
    
    echo "<p><strong>Array completo:</strong> " . implode(', ', $resultados_manuais) . "</p>";
    
    // Gerar HTML manualmente
    $html_manual = '';
    foreach ($resultados_manuais as $resultado) {
        switch ($resultado) {
            case 'V':
                $html_manual .= '<div class="inf" title="Vitória"></div>';
                break;
            case 'D':
                $html_manual .= '<div class="inf2" title="Derrota"></div>';
                break;
            case 'E':
                $html_manual .= '<div class="inf3" title="Empate"></div>';
                break;
            default:
                $html_manual .= '<div class="inf4" title="Sem jogo"></div>';
                break;
        }
    }
    
    echo "<p><strong>HTML manual:</strong></p>";
    echo "<pre>" . htmlspecialchars($html_manual) . "</pre>";
    
    echo "<p><strong>HTML manual vazio?</strong> " . (empty(trim(strip_tags($html_manual))) ? "SIM" : "NÃO") . "</p>";
    
} else {
    echo "<p style='color: red;'>❌ Nenhum jogo encontrado!</p>";
}

echo "<hr>";
echo "<h2>🔍 Comparação</h2>";
echo "<p><strong>Função retorna vazio:</strong> " . ($isEmpty ? "SIM" : "NÃO") . "</p>";
echo "<p><strong>Deveria retornar vazio:</strong> " . (count($jogos) == 0 ? "SIM" : "NÃO") . "</p>";

if ($isEmpty && count($jogos) > 0) {
    echo "<p style='color: red; font-weight: bold;'>🚨 PROBLEMA ENCONTRADO: Há jogos mas a função retorna vazio!</p>";
} elseif (!$isEmpty && count($jogos) == 0) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ ESTRANHO: Não há jogos mas a função não retorna vazio!</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ Comportamento esperado!</p>";
}
?>
