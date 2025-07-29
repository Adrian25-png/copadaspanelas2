<?php
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>🏆 Inserindo Dados de Teste das Fases Finais</h2>";
    
    // Primeiro, vamos verificar se existe um torneio ativo
    $stmt = $pdo->query("SELECT * FROM tournaments WHERE status IN ('active', 'ativo') ORDER BY id DESC LIMIT 1");
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tournament) {
        echo "<p style='color: red;'>❌ Nenhum torneio ativo encontrado. Criando um torneio de teste...</p>";

        // Criar torneio de teste
        $stmt = $pdo->prepare("INSERT INTO tournaments (name, year, description, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute(['Copa das Panelas 2024 - Teste', 2024, 'Torneio de teste para as fases finais']);
        $tournament_id = $pdo->lastInsertId();

        echo "<p style='color: green;'>✅ Torneio de teste criado com ID: $tournament_id</p>";
    } else {
        $tournament_id = $tournament['id'];
        $tournament_name = $tournament['name'] ?? $tournament['nome'] ?? 'Torneio';
        echo "<p style='color: green;'>✅ Usando torneio existente: {$tournament_name} (ID: $tournament_id)</p>";
    }
    
    // Verificar se a tabela times existe
    try {
        $pdo->query("SELECT 1 FROM times LIMIT 1");
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Tabela times não existe. Criando...</p>";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS times (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                logo BLOB,
                grupo_id INT NOT NULL,
                tournament_id INT NOT NULL,
                token VARCHAR(64) UNIQUE,
                pts INT DEFAULT 0,
                vitorias INT DEFAULT 0,
                empates INT DEFAULT 0,
                derrotas INT DEFAULT 0,
                gm INT DEFAULT 0,
                gc INT DEFAULT 0,
                sg INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
                FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
            )
        ");
    }

    // Verificar se existem times suficientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM times WHERE tournament_id = ?");
    $stmt->execute([$tournament_id]);
    $total_teams = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_teams < 8) {
        echo "<p style='color: orange;'>⚠️ Apenas $total_teams times encontrados. Criando times de teste...</p>";
        
        // Criar times de teste
        $test_teams = [
            'Flamengo', 'Palmeiras', 'Corinthians', 'São Paulo',
            'Santos', 'Grêmio', 'Internacional', 'Atlético-MG',
            'Cruzeiro', 'Botafogo', 'Vasco', 'Fluminense',
            'Bahia', 'Sport', 'Ceará', 'Fortaleza'
        ];
        
        // Verificar se a tabela grupos existe
        try {
            $pdo->query("SELECT 1 FROM grupos LIMIT 1");
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Tabela grupos não existe. Criando...</p>";
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS grupos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(50) NOT NULL,
                    tournament_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
                )
            ");
        }

        // Criar grupos de teste se não existirem
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM grupos WHERE tournament_id = ?");
        $stmt->execute([$tournament_id]);
        $total_groups = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total_groups == 0) {
            echo "<p>📝 Criando grupos de teste...</p>";
            for ($i = 1; $i <= 4; $i++) {
                $stmt = $pdo->prepare("INSERT INTO grupos (nome, tournament_id) VALUES (?, ?)");
                $stmt->execute(["Grupo " . chr(64 + $i), $tournament_id]);
                echo "<p>✅ Grupo " . chr(64 + $i) . " criado</p>";
            }
        }

        // Buscar grupos
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE tournament_id = ? ORDER BY id LIMIT 4");
        $stmt->execute([$tournament_id]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($groups)) {
            echo "<p style='color: red;'>❌ Erro: Nenhum grupo encontrado após criação</p>";
            exit;
        }

        // Criar times
        echo "<p>👥 Criando times de teste...</p>";
        for ($i = 0; $i < min(16, count($test_teams)); $i++) {
            $group_id = $groups[$i % count($groups)]['id'];
            $stmt = $pdo->prepare("INSERT INTO times (nome, tournament_id, grupo_id) VALUES (?, ?, ?)");
            $stmt->execute([$test_teams[$i], $tournament_id, $group_id]);
            echo "<p>✅ {$test_teams[$i]} adicionado ao {$groups[$i % count($groups)]['nome']}</p>";
        }
        
        echo "<p style='color: green;'>✅ Times de teste criados</p>";
    }
    
    // Buscar times para as eliminatórias
    $stmt = $pdo->prepare("SELECT * FROM times WHERE tournament_id = ? ORDER BY id LIMIT 16");
    $stmt->execute([$tournament_id]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($teams) < 8) {
        echo "<p style='color: red;'>❌ Não há times suficientes para as eliminatórias</p>";
        exit;
    }
    
    echo "<h3>🎯 Inserindo Jogos das Eliminatórias</h3>";
    
    // Limpar jogos existentes das fases finais
    $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final', '3º Lugar')");
    $stmt->execute([$tournament_id]);
    echo "<p>🗑️ Jogos anteriores das fases finais removidos</p>";
    
    // OITAVAS DE FINAL (8 jogos)
    echo "<h4>🥇 Oitavas de Final</h4>";
    $oitavas_matches = [
        [$teams[0], $teams[15], 2, 1],
        [$teams[1], $teams[14], 3, 0],
        [$teams[2], $teams[13], 1, 2],
        [$teams[3], $teams[12], 4, 1],
        [$teams[4], $teams[11], 0, 1],
        [$teams[5], $teams[10], 2, 0],
        [$teams[6], $teams[9], 1, 1], // Empate - vamos por 2-1 nos pênaltis
        [$teams[7], $teams[8], 3, 2]
    ];
    
    foreach ($oitavas_matches as $i => $match) {
        $team1 = $match[0];
        $team2 = $match[1];
        $goals1 = $match[2];
        $goals2 = $match[3];
        
        $stmt = $pdo->prepare("
            INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, match_date, status) 
            VALUES (?, 'Oitavas', ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), 'finalizado')
        ");
        $stmt->execute([$tournament_id, $team1['id'], $team2['id'], $goals1, $goals2, 10 - $i]);
        
        echo "<p>⚽ {$team1['nome']} $goals1 x $goals2 {$team2['nome']}</p>";
    }
    
    // QUARTAS DE FINAL (4 jogos) - Vencedores das oitavas
    echo "<h4>🥈 Quartas de Final</h4>";
    $quartas_winners = [$teams[0], $teams[1], $teams[13], $teams[3], $teams[11], $teams[5], $teams[6], $teams[7]];
    $quartas_matches = [
        [$quartas_winners[0], $quartas_winners[1], 1, 0],
        [$quartas_winners[2], $quartas_winners[3], 2, 3],
        [$quartas_winners[4], $quartas_winners[5], 1, 2],
        [$quartas_winners[6], $quartas_winners[7], 0, 1]
    ];
    
    foreach ($quartas_matches as $i => $match) {
        $team1 = $match[0];
        $team2 = $match[1];
        $goals1 = $match[2];
        $goals2 = $match[3];
        
        $stmt = $pdo->prepare("
            INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, match_date, status) 
            VALUES (?, 'Quartas', ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), 'finalizado')
        ");
        $stmt->execute([$tournament_id, $team1['id'], $team2['id'], $goals1, $goals2, 5 - $i]);
        
        echo "<p>⚽ {$team1['nome']} $goals1 x $goals2 {$team2['nome']}</p>";
    }
    
    // SEMIFINAIS (2 jogos)
    echo "<h4>🥉 Semifinais</h4>";
    $semi_winners = [$teams[0], $teams[3], $teams[5], $teams[7]];
    $semi_matches = [
        [$semi_winners[0], $semi_winners[1], 2, 1],
        [$semi_winners[2], $semi_winners[3], 0, 3]
    ];
    
    foreach ($semi_matches as $i => $match) {
        $team1 = $match[0];
        $team2 = $match[1];
        $goals1 = $match[2];
        $goals2 = $match[3];
        
        $stmt = $pdo->prepare("
            INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, match_date, status) 
            VALUES (?, 'Semifinal', ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), 'finalizado')
        ");
        $stmt->execute([$tournament_id, $team1['id'], $team2['id'], $goals1, $goals2, 2 - $i]);
        
        echo "<p>⚽ {$team1['nome']} $goals1 x $goals2 {$team2['nome']}</p>";
    }
    
    // TERCEIRO LUGAR (perdedores das semifinais)
    echo "<h4>🥉 Disputa do 3º Lugar</h4>";
    $third_place_teams = [$teams[3], $teams[5]]; // Perdedores das semis
    $stmt = $pdo->prepare("
        INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, match_date, status) 
        VALUES (?, '3º Lugar', ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY), 'finalizado')
    ");
    $stmt->execute([$tournament_id, $third_place_teams[0]['id'], $third_place_teams[1]['id'], 2, 0]);
    echo "<p>🥉 {$third_place_teams[0]['nome']} 2 x 0 {$third_place_teams[1]['nome']}</p>";
    
    // FINAL (vencedores das semifinais)
    echo "<h4>🏆 FINAL</h4>";
    $final_teams = [$teams[0], $teams[7]]; // Vencedores das semis
    $stmt = $pdo->prepare("
        INSERT INTO matches (tournament_id, phase, team1_id, team2_id, team1_goals, team2_goals, match_date, status) 
        VALUES (?, 'Final', ?, ?, ?, ?, NOW(), 'finalizado')
    ");
    $stmt->execute([$tournament_id, $final_teams[0]['id'], $final_teams[1]['id'], 3, 1]);
    echo "<p>🏆 {$final_teams[0]['nome']} 3 x 1 {$final_teams[1]['nome']}</p>";
    
    echo "<h3 style='color: green;'>✅ Dados das Fases Finais Inseridos com Sucesso!</h3>";
    echo "<p><a href='../exibir_finais.php' target='_blank' style='background: #7B1FA2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Ver Página de Finais</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
