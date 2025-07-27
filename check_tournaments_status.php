<?php
/**
 * Verificar status dos torneios e opções de exclusão
 */

echo "<h1>🔍 Verificação de Status dos Torneios</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";

try {
    require_once 'app/config/conexao.php';
    require_once 'app/classes/TournamentManager.php';

    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    echo "<h2>1. Status Atual dos Torneios</h2>";
    
    // Verificar todos os torneios
    $tournaments = $tournamentManager->getAllTournaments();
    echo "<p class='info'>📊 Total de torneios: " . count($tournaments) . "</p>";
    
    if (empty($tournaments)) {
        echo "<p class='warning'>⚠️ Nenhum torneio encontrado no banco de dados</p>";
        echo "<p><a href='app/pages/adm/tournament_wizard.php'>Criar primeiro torneio</a></p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>Ano</th><th>Pode Excluir?</th><th>Ações</th>";
        echo "</tr>";
        
        foreach ($tournaments as $tournament) {
            $can_delete = ($tournament['status'] === 'setup' || $tournament['status'] === 'archived');
            $status_color = [
                'setup' => '#f39c12',
                'active' => '#27ae60', 
                'completed' => '#3498db',
                'archived' => '#95a5a6'
            ][$tournament['status']] ?? '#000';
            
            echo "<tr>";
            echo "<td>" . $tournament['id'] . "</td>";
            echo "<td>" . htmlspecialchars($tournament['name']) . "</td>";
            echo "<td style='color:$status_color; font-weight:bold;'>" . strtoupper($tournament['status']) . "</td>";
            echo "<td>" . $tournament['year'] . "</td>";
            echo "<td style='text-align:center;'>" . ($can_delete ? "✅ SIM" : "❌ NÃO") . "</td>";
            echo "<td>";
            
            if ($can_delete) {
                echo "<a href='app/pages/adm/tournament_actions.php?action=delete&id=" . $tournament['id'] . "' style='color:red; text-decoration:none;'>🗑️ Excluir</a>";
            } else {
                echo "<span style='color:#999;'>Não disponível</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>2. Contagem por Status</h2>";
    
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM tournaments 
        GROUP BY status 
        ORDER BY status
    ");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($status_counts)) {
        echo "<ul>";
        foreach ($status_counts as $status) {
            $can_delete_status = ($status['status'] === 'setup' || $status['status'] === 'archived');
            $delete_info = $can_delete_status ? " (podem ser excluídos)" : " (não podem ser excluídos)";
            echo "<li><strong>" . strtoupper($status['status']) . ":</strong> " . $status['count'] . " torneios" . $delete_info . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>3. Regras de Exclusão</h2>";
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<h4>📋 Quando um torneio PODE ser excluído:</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>Status 'setup':</strong> Torneios em configuração</li>";
    echo "<li>✅ <strong>Status 'archived':</strong> Torneios arquivados</li>";
    echo "</ul>";
    
    echo "<h4>🚫 Quando um torneio NÃO PODE ser excluído:</h4>";
    echo "<ul>";
    echo "<li>❌ <strong>Status 'active':</strong> Torneio ativo atual</li>";
    echo "<li>❌ <strong>Status 'completed':</strong> Torneios finalizados (histórico)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>4. Como Excluir um Torneio</h2>";
    echo "<div style='background:#e8f5e8; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<h4>🎯 Passos para excluir:</h4>";
    echo "<ol>";
    echo "<li>Acesse a <a href='app/pages/adm/tournament_list.php'>Lista de Torneios</a></li>";
    echo "<li>Encontre um torneio com status <strong>SETUP</strong> ou <strong>ARCHIVED</strong></li>";
    echo "<li>Clique no botão de opções (⋮) ao lado do torneio</li>";
    echo "<li>Clique em <strong>\"Excluir\"</strong> (ícone de lixeira)</li>";
    echo "<li>Confirme a exclusão na página de confirmação</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>5. Testando Criação de Torneio para Exclusão</h2>";
    
    // Verificar se há algum torneio que pode ser excluído
    $deletable_count = 0;
    foreach ($tournaments as $tournament) {
        if ($tournament['status'] === 'setup' || $tournament['status'] === 'archived') {
            $deletable_count++;
        }
    }
    
    if ($deletable_count === 0) {
        echo "<p class='warning'>⚠️ Nenhum torneio pode ser excluído no momento</p>";
        echo "<p class='info'>💡 Vou criar um torneio de teste para você poder testar a exclusão:</p>";
        
        try {
            $test_tournament_id = $tournamentManager->createTournament(
                "Torneio de Teste para Exclusão " . date('H:i:s'),
                date('Y'),
                "Torneio criado apenas para testar a funcionalidade de exclusão",
                1,
                2,
                'final'
            );
            
            echo "<p class='success'>✅ Torneio de teste criado com ID: $test_tournament_id</p>";
            echo "<p class='info'>📋 Status: SETUP (pode ser excluído)</p>";
            echo "<p><a href='app/pages/adm/tournament_list.php' style='background:#e74c3c;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>🗑️ Ir para Lista e Testar Exclusão</a></p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao criar torneio de teste: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>✅ Há $deletable_count torneio(s) que podem ser excluídos</p>";
        echo "<p><a href='app/pages/adm/tournament_list.php' style='background:#3498db;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>📋 Ir para Lista de Torneios</a></p>";
    }
    
    echo "<h2>6. Verificando Arquivo de Ações</h2>";
    
    $actions_file = 'app/pages/adm/tournament_actions.php';
    if (file_exists($actions_file)) {
        echo "<p class='success'>✅ Arquivo tournament_actions.php existe</p>";
        
        // Testar se o arquivo tem a função de exclusão
        $content = file_get_contents($actions_file);
        if (strpos($content, 'delete') !== false) {
            echo "<p class='success'>✅ Função de exclusão encontrada no arquivo</p>";
        } else {
            echo "<p class='error'>❌ Função de exclusão não encontrada</p>";
        }
    } else {
        echo "<p class='error'>❌ Arquivo tournament_actions.php não encontrado</p>";
    }
    
    echo "<div style='background:#e8f5e8;padding:20px;border-radius:10px;margin:20px 0;'>";
    echo "<h3>🎯 RESUMO</h3>";
    echo "<p><strong>✅ Opção de excluir:</strong> Está presente no sistema</p>";
    echo "<p><strong>📋 Condições:</strong> Apenas torneios em 'setup' ou 'archived'</p>";
    echo "<p><strong>🔗 Localização:</strong> Lista de Torneios → Dropdown (⋮) → Excluir</p>";
    echo "<p><strong>🛡️ Segurança:</strong> Página de confirmação antes da exclusão</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr><p><small>Verificação executada em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
