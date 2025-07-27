<?php
/**
 * Teste do Assistente Avançado de Criação de Torneio
 */

echo "<h1>🧪 Teste do Assistente Avançado</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    echo "<h2>1. Verificando Arquivos</h2>";
    
    $files_to_check = [
        'app/pages/adm/tournament_wizard_advanced.php' => 'Assistente Avançado',
        'app/pages/adm/tournament_list.php' => 'Lista de Torneios',
        'app/classes/TournamentManager.php' => 'TournamentManager',
        'public/css/tournament_wizard.css' => 'CSS do Wizard'
    ];
    
    foreach ($files_to_check as $file => $name) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ $name existe</p>";
        } else {
            echo "<p class='error'>❌ $name não encontrado</p>";
        }
    }
    
    echo "<h2>2. Verificando Funcionalidades</h2>";
    
    // Verificar se as tabelas necessárias existem
    require_once 'app/config/conexao.php';
    $pdo = conectar();
    
    $tables_to_check = [
        'tournaments' => 'Torneios',
        'tournament_settings' => 'Configurações',
        'grupos' => 'Grupos',
        'times' => 'Times',
        'jogadores' => 'Jogadores'
    ];
    
    foreach ($tables_to_check as $table => $name) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabela $name existe</p>";
        } else {
            echo "<p class='error'>❌ Tabela $name não encontrada</p>";
        }
    }
    
    echo "<h2>3. Testando Estrutura do Banco</h2>";
    
    // Verificar estrutura da tabela times
    $stmt = $pdo->query("DESCRIBE times");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['nome', 'logo', 'grupo_id', 'tournament_id', 'token'];
    foreach ($required_columns as $column) {
        if (in_array($column, $columns)) {
            echo "<p class='success'>✅ Coluna 'times.$column' existe</p>";
        } else {
            echo "<p class='error'>❌ Coluna 'times.$column' não encontrada</p>";
        }
    }
    
    // Verificar estrutura da tabela jogadores
    $stmt = $pdo->query("DESCRIBE jogadores");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['nome', 'posicao', 'numero', 'time_id', 'imagem', 'token'];
    foreach ($required_columns as $column) {
        if (in_array($column, $columns)) {
            echo "<p class='success'>✅ Coluna 'jogadores.$column' existe</p>";
        } else {
            echo "<p class='error'>❌ Coluna 'jogadores.$column' não encontrada</p>";
        }
    }
    
    echo "<h2>4. Simulando Criação de Torneio Completo</h2>";
    
    // Simular dados de um torneio completo
    $test_tournament_data = [
        'name' => 'Teste Torneio Completo ' . date('H:i:s'),
        'year' => date('Y'),
        'description' => 'Torneio de teste com times e jogadores',
        'num_groups' => 2,
        'teams_per_group' => 2,
        'final_phase' => 'final'
    ];
    
    $test_teams = [
        ['name' => 'Time A', 'group_index' => 0],
        ['name' => 'Time B', 'group_index' => 0],
        ['name' => 'Time C', 'group_index' => 1],
        ['name' => 'Time D', 'group_index' => 1]
    ];
    
    $test_players = [
        ['name' => 'Jogador 1', 'position' => 'Atacante', 'number' => 10, 'team_index' => 0],
        ['name' => 'Jogador 2', 'position' => 'Goleiro', 'number' => 1, 'team_index' => 0],
        ['name' => 'Jogador 3', 'position' => 'Meio-campo', 'number' => 8, 'team_index' => 1],
        ['name' => 'Jogador 4', 'position' => 'Defesa', 'number' => 4, 'team_index' => 1]
    ];
    
    echo "<p class='info'>🎯 Dados do teste:</p>";
    echo "<ul>";
    echo "<li>Torneio: " . $test_tournament_data['name'] . "</li>";
    echo "<li>Grupos: " . $test_tournament_data['num_groups'] . "</li>";
    echo "<li>Times: " . count($test_teams) . "</li>";
    echo "<li>Jogadores: " . count($test_players) . "</li>";
    echo "</ul>";
    
    try {
        require_once 'app/classes/TournamentManager.php';
        $tournamentManager = new TournamentManager($pdo);
        
        // Criar torneio
        $tournament_id = $tournamentManager->createTournament(
            $test_tournament_data['name'],
            $test_tournament_data['year'],
            $test_tournament_data['description'],
            $test_tournament_data['num_groups'],
            $test_tournament_data['teams_per_group'],
            $test_tournament_data['final_phase']
        );
        
        echo "<p class='success'>✅ Torneio criado com ID: $tournament_id</p>";
        
        // Obter grupos criados
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE tournament_id = ? ORDER BY nome");
        $stmt->execute([$tournament_id]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✅ Grupos criados: " . count($groups) . "</p>";
        
        // Adicionar times
        $team_ids = [];
        foreach ($test_teams as $team_index => $team) {
            $group_id = $groups[$team['group_index']]['id'];
            
            $stmt = $pdo->prepare("
                INSERT INTO times (nome, grupo_id, tournament_id, pts, vitorias, empates, derrotas, gm, gc, sg, token)
                VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0, 0, ?)
            ");
            $token = bin2hex(random_bytes(16));
            $stmt->execute([$team['name'], $group_id, $tournament_id, $token]);
            
            $team_id = $pdo->lastInsertId();
            $team_ids[$team_index] = $team_id;
        }
        
        echo "<p class='success'>✅ Times adicionados: " . count($team_ids) . "</p>";
        
        // Adicionar jogadores
        $player_count = 0;
        foreach ($test_players as $player) {
            if (isset($team_ids[$player['team_index']])) {
                $team_id = $team_ids[$player['team_index']];
                
                $stmt = $pdo->prepare("
                    INSERT INTO jogadores (nome, posicao, numero, time_id, gols, assistencias, cartoes_amarelos, cartoes_vermelhos, token)
                    VALUES (?, ?, ?, ?, 0, 0, 0, 0, ?)
                ");
                $player_token = bin2hex(random_bytes(16));
                $stmt->execute([
                    $player['name'],
                    $player['position'],
                    $player['number'],
                    $team_id,
                    $player_token
                ]);
                $player_count++;
            }
        }
        
        echo "<p class='success'>✅ Jogadores adicionados: $player_count</p>";
        
        // Verificar resultado final
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM grupos WHERE tournament_id = ?) as grupos,
                (SELECT COUNT(*) FROM times WHERE tournament_id = ?) as times,
                (SELECT COUNT(*) FROM jogadores j INNER JOIN times t ON j.time_id = t.id WHERE t.tournament_id = ?) as jogadores
        ");
        $stmt->execute([$tournament_id, $tournament_id, $tournament_id]);
        $final_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background:#e8f5e8;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>🎉 TESTE COMPLETO REALIZADO COM SUCESSO!</h3>";
        echo "<p><strong>✅ Torneio criado:</strong> ID $tournament_id</p>";
        echo "<p><strong>✅ Grupos:</strong> " . $final_stats['grupos'] . "</p>";
        echo "<p><strong>✅ Times:</strong> " . $final_stats['times'] . "</p>";
        echo "<p><strong>✅ Jogadores:</strong> " . $final_stats['jogadores'] . "</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro na simulação: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>5. Links de Teste</h2>";
    echo "<p><a href='app/pages/adm/tournament_list.php' target='_blank' style='background:#3498db;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>📋 Lista de Torneios</a></p>";
    echo "<p><a href='app/pages/adm/tournament_wizard_advanced.php' target='_blank' style='background:#27ae60;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>🧙‍♂️ Assistente Avançado</a></p>";
    
    if (isset($tournament_id)) {
        echo "<p><a href='app/pages/adm/tournament_dashboard.php?id=$tournament_id' target='_blank' style='background:#e74c3c;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>🎯 Dashboard do Teste</a></p>";
        echo "<p><a href='app/pages/adm/tournament_standings.php?id=$tournament_id' target='_blank' style='background:#f39c12;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>🏆 Classificação do Teste</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro crítico: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><small>Teste executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
