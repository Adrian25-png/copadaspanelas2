<?php
include 'app/config/conexao.php';
require_once 'app/classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$tournament = $tournamentManager->getCurrentTournament();

if ($tournament) {
    $tournament_id = $tournament['id'];
    
    echo "<h2>Criando Chaveamento Completo de Teste para: " . $tournament['name'] . "</h2>";
    
    // Buscar 8 times para criar um chaveamento completo
    $stmt = $pdo->prepare("SELECT id, nome FROM times WHERE tournament_id = ? LIMIT 8");
    $stmt->execute([$tournament_id]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($teams) >= 8) {
        echo "<p>✅ Times encontrados: " . count($teams) . "</p>";
        
        // Limpar jogos de eliminatórias existentes
        $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')");
        $stmt->execute([$tournament_id]);
        echo "<p>🗑️ Jogos de eliminatórias antigos removidos</p>";
        
        try {
            $pdo->beginTransaction();
            
            // QUARTAS DE FINAL (4 jogos)
            echo "<h3>Criando Quartas de Final:</h3>";
            $quartas_winners = [];
            
            for ($i = 0; $i < 4; $i++) {
                $team1_idx = $i * 2;
                $team2_idx = $i * 2 + 1;
                
                if (isset($teams[$team1_idx]) && isset($teams[$team2_idx])) {
                    $team1_goals = rand(1, 4);
                    $team2_goals = rand(0, 3);
                    
                    // Garantir que não seja empate
                    if ($team1_goals == $team2_goals) {
                        $team1_goals++;
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, status, created_at)
                        VALUES (?, 'Quartas', ?, ?, ?, ?, 'finalizado', NOW())
                    ");
                    
                    $stmt->execute([
                        $tournament_id, 
                        $teams[$team1_idx]['id'], 
                        $teams[$team2_idx]['id'], 
                        $team1_goals, 
                        $team2_goals
                    ]);
                    
                    $winner = $team1_goals > $team2_goals ? $teams[$team1_idx] : $teams[$team2_idx];
                    $quartas_winners[] = $winner;
                    
                    echo "<p>⚽ {$teams[$team1_idx]['nome']} {$team1_goals} x {$team2_goals} {$teams[$team2_idx]['nome']} - Vencedor: {$winner['nome']}</p>";
                }
            }
            
            // SEMIFINAIS (2 jogos)
            echo "<h3>Criando Semifinais:</h3>";
            $semi_winners = [];
            $semi_losers = [];
            
            for ($i = 0; $i < 2; $i++) {
                $team1_idx = $i * 2;
                $team2_idx = $i * 2 + 1;
                
                if (isset($quartas_winners[$team1_idx]) && isset($quartas_winners[$team2_idx])) {
                    $team1_goals = rand(1, 3);
                    $team2_goals = rand(0, 2);
                    
                    // Garantir que não seja empate
                    if ($team1_goals == $team2_goals) {
                        $team1_goals++;
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, status, created_at)
                        VALUES (?, 'Semifinal', ?, ?, ?, ?, 'finalizado', NOW())
                    ");
                    
                    $stmt->execute([
                        $tournament_id, 
                        $quartas_winners[$team1_idx]['id'], 
                        $quartas_winners[$team2_idx]['id'], 
                        $team1_goals, 
                        $team2_goals
                    ]);
                    
                    $winner = $team1_goals > $team2_goals ? $quartas_winners[$team1_idx] : $quartas_winners[$team2_idx];
                    $loser = $team1_goals > $team2_goals ? $quartas_winners[$team2_idx] : $quartas_winners[$team1_idx];
                    
                    $semi_winners[] = $winner;
                    $semi_losers[] = $loser;
                    
                    echo "<p>⚽ {$quartas_winners[$team1_idx]['nome']} {$team1_goals} x {$team2_goals} {$quartas_winners[$team2_idx]['nome']} - Vencedor: {$winner['nome']}</p>";
                }
            }
            
            // FINAL
            if (count($semi_winners) >= 2) {
                echo "<h3>Criando Final:</h3>";
                $team1_goals = rand(1, 3);
                $team2_goals = rand(0, 2);
                
                // Garantir que não seja empate
                if ($team1_goals == $team2_goals) {
                    $team1_goals++;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, status, created_at)
                    VALUES (?, 'Final', ?, ?, ?, ?, 'finalizado', NOW())
                ");
                
                $stmt->execute([
                    $tournament_id, 
                    $semi_winners[0]['id'], 
                    $semi_winners[1]['id'], 
                    $team1_goals, 
                    $team2_goals
                ]);
                
                $champion = $team1_goals > $team2_goals ? $semi_winners[0] : $semi_winners[1];
                echo "<p>🏆 FINAL: {$semi_winners[0]['nome']} {$team1_goals} x {$team2_goals} {$semi_winners[1]['nome']} - CAMPEÃO: {$champion['nome']}</p>";
            }
            
            // TERCEIRO LUGAR
            if (count($semi_losers) >= 2) {
                echo "<h3>Criando Disputa do 3º Lugar:</h3>";
                $team1_goals = rand(1, 3);
                $team2_goals = rand(0, 2);
                
                // Garantir que não seja empate
                if ($team1_goals == $team2_goals) {
                    $team1_goals++;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, status, created_at)
                    VALUES (?, '3º Lugar', ?, ?, ?, ?, 'finalizado', NOW())
                ");
                
                $stmt->execute([
                    $tournament_id, 
                    $semi_losers[0]['id'], 
                    $semi_losers[1]['id'], 
                    $team1_goals, 
                    $team2_goals
                ]);
                
                $third_place = $team1_goals > $team2_goals ? $semi_losers[0] : $semi_losers[1];
                echo "<p>🥉 3º LUGAR: {$semi_losers[0]['nome']} {$team1_goals} x {$team2_goals} {$semi_losers[1]['nome']} - 3º Lugar: {$third_place['nome']}</p>";
            }
            
            $pdo->commit();
            
            echo "<hr>";
            echo "<h3 style='color: green;'>✅ Chaveamento Completo Criado com Sucesso!</h3>";
            echo "<p><strong>Fases criadas:</strong> Quartas, Semifinais, Final e 3º Lugar</p>";
            echo "<p><a href='app/pages/exibir_finais.php' style='background: #FFD700; color: black; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 1.2rem;'>🏆 VER CHAVEAMENTO COMPLETO</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<p style='color: red;'>❌ Erro ao criar chaveamento: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Não há times suficientes! Encontrados: " . count($teams) . " (mínimo: 8)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Nenhum torneio ativo encontrado!</p>";
}

echo "<hr>";
echo "<p><a href='check_quartas.php'>🔍 Verificar dados de Eliminatórias</a></p>";
?>
