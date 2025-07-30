<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>Debug - Terceiro Lugar</h2>";
    
    // Verificar todos os jogos com phase
    echo "<h3>Todos os jogos com phase:</h3>";
    $stmt = $pdo->query("SELECT id, tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, status FROM matches WHERE phase IS NOT NULL ORDER BY tournament_id, phase");
    $all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>ID</th><th>Tournament</th><th>Phase</th><th>Team1</th><th>Team2</th><th>Score</th><th>Status</th></tr>";
    
    foreach ($all_matches as $match) {
        $score = ($match['team1_goals'] !== null && $match['team2_goals'] !== null) 
                ? $match['team1_goals'] . '-' . $match['team2_goals'] 
                : 'Sem resultado';
        
        echo "<tr>";
        echo "<td>" . $match['id'] . "</td>";
        echo "<td>" . $match['tournament_id'] . "</td>";
        echo "<td>" . htmlspecialchars($match['phase']) . "</td>";
        echo "<td>" . $match['team1_id'] . "</td>";
        echo "<td>" . $match['team2_id'] . "</td>";
        echo "<td>" . $score . "</td>";
        echo "<td>" . htmlspecialchars($match['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar especificamente jogos de terceiro lugar
    echo "<h3>Jogos de Terceiro Lugar:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE phase = '3º Lugar'");
    $stmt->execute();
    $third_place_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($third_place_matches)) {
        echo "<p style='color: red;'>❌ Nenhum jogo de terceiro lugar encontrado!</p>";
    } else {
        echo "<p style='color: green;'>✅ " . count($third_place_matches) . " jogo(s) de terceiro lugar encontrado(s):</p>";
        foreach ($third_place_matches as $match) {
            echo "<pre>";
            print_r($match);
            echo "</pre>";
        }
    }
    
    // Verificar torneio atual
    echo "<h3>Torneio Atual:</h3>";
    require_once '../../classes/TournamentManager.php';
    $tournamentManager = new TournamentManager($pdo);
    $tournament = $tournamentManager->getCurrentTournament();
    
    if ($tournament) {
        echo "<p>✅ Torneio atual: " . htmlspecialchars($tournament['name']) . " (ID: " . $tournament['id'] . ")</p>";
        
        // Verificar jogos do torneio atual
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, phase
            FROM matches 
            WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')
            GROUP BY phase
            ORDER BY 
                CASE phase
                    WHEN 'Oitavas' THEN 1
                    WHEN 'Quartas' THEN 2
                    WHEN 'Semifinal' THEN 3
                    WHEN 'Final' THEN 4
                    WHEN '3º Lugar' THEN 5
                END
        ");
        $stmt->execute([$tournament['id']]);
        $phases_count = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Fases do torneio atual:</h4>";
        if (empty($phases_count)) {
            echo "<p style='color: red;'>❌ Nenhuma fase encontrada para o torneio atual!</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Fase</th><th>Jogos</th></tr>";
            foreach ($phases_count as $phase) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($phase['phase']) . "</td>";
                echo "<td>" . $phase['total'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nenhum torneio atual encontrado!</p>";
    }
    
    // Testar consulta específica do exibir_finais.php
    echo "<h3>Teste da consulta do exibir_finais.php:</h3>";
    if ($tournament) {
        $tournament_id = $tournament['id'];
        
        // Consulta 1: Total de matches
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM matches
            WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')
        ");
        $stmt->execute([$tournament_id]);
        $total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total de matches das fases finais: $total_matches</p>";
        
        // Consulta 2: Fases existentes
        $stmt = $pdo->prepare("
            SELECT DISTINCT phase
            FROM matches
            WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')
            ORDER BY
                CASE phase
                    WHEN 'Oitavas' THEN 1
                    WHEN 'Quartas' THEN 2
                    WHEN 'Semifinal' THEN 3
                    WHEN 'Final' THEN 4
                    WHEN '3º Lugar' THEN 5
                END
        ");
        $stmt->execute([$tournament_id]);
        $existing_phases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Fases existentes: " . implode(', ', $existing_phases) . "</p>";
        echo "<p>Terceiro lugar está na lista? " . (in_array('3º Lugar', $existing_phases) ? '✅ SIM' : '❌ NÃO') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
