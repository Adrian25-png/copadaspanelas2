<?php
/**
 * Teste da funcionalidade de edição de imagem de jogadores
 */

echo "<h1>🧪 Teste de Edição de Imagem de Jogadores</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";

try {
    require_once 'app/config/conexao.php';
    require_once 'app/classes/TournamentManager.php';

    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    echo "<h2>1. Verificando Estrutura do Banco</h2>";
    
    // Verificar se a coluna imagem existe na tabela jogadores
    $stmt = $pdo->query("DESCRIBE jogadores");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('imagem', $columns)) {
        echo "<p class='success'>✅ Coluna 'imagem' existe na tabela jogadores</p>";
    } else {
        echo "<p class='error'>❌ Coluna 'imagem' não encontrada na tabela jogadores</p>";
        echo "<p class='warning'>⚠️ Execute o seguinte SQL para adicionar a coluna:</p>";
        echo "<code>ALTER TABLE jogadores ADD COLUMN imagem LONGBLOB;</code>";
    }
    
    echo "<h2>2. Verificando Jogadores Existentes</h2>";
    
    // Buscar jogadores com e sem imagem
    $stmt = $pdo->query("
        SELECT j.id, j.nome, j.posicao, j.numero, t.nome as time_nome,
               CASE WHEN j.imagem IS NOT NULL THEN 'Sim' ELSE 'Não' END as tem_imagem,
               LENGTH(j.imagem) as tamanho_imagem
        FROM jogadores j
        INNER JOIN times t ON j.time_id = t.id
        ORDER BY t.nome, j.nome
        LIMIT 10
    ");
    $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($jogadores)) {
        echo "<p class='info'>📊 Jogadores encontrados: " . count($jogadores) . "</p>";
        
        echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Time</th><th>Posição</th><th>Número</th><th>Tem Imagem</th><th>Tamanho</th>";
        echo "</tr>";
        
        foreach ($jogadores as $jogador) {
            echo "<tr>";
            echo "<td>" . $jogador['id'] . "</td>";
            echo "<td>" . htmlspecialchars($jogador['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($jogador['time_nome']) . "</td>";
            echo "<td>" . htmlspecialchars($jogador['posicao'] ?: 'N/A') . "</td>";
            echo "<td>" . ($jogador['numero'] ?: 'N/A') . "</td>";
            echo "<td>" . $jogador['tem_imagem'] . "</td>";
            echo "<td>" . ($jogador['tamanho_imagem'] ? number_format($jogador['tamanho_imagem']) . ' bytes' : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar jogadores com e sem imagem
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN imagem IS NOT NULL THEN 1 ELSE 0 END) as com_imagem,
                SUM(CASE WHEN imagem IS NULL THEN 1 ELSE 0 END) as sem_imagem
            FROM jogadores
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background:#f8f9fa; padding:15px; border-radius:8px; margin:10px 0;'>";
        echo "<h4>📊 Estatísticas de Imagens:</h4>";
        echo "<ul>";
        echo "<li><strong>Total de jogadores:</strong> " . $stats['total'] . "</li>";
        echo "<li><strong>Com imagem:</strong> " . $stats['com_imagem'] . "</li>";
        echo "<li><strong>Sem imagem:</strong> " . $stats['sem_imagem'] . "</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<p class='warning'>⚠️ Nenhum jogador encontrado no banco</p>";
    }
    
    echo "<h2>3. Testando Funcionalidade de Upload</h2>";
    
    // Verificar se há torneios para testar
    $tournaments = $tournamentManager->getAllTournaments();
    if (!empty($tournaments)) {
        $tournament = $tournaments[0];
        echo "<p class='success'>✅ Torneio encontrado para teste: " . htmlspecialchars($tournament['name']) . "</p>";
        
        // Verificar se há times no torneio
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE tournament_id = ?");
        $stmt->execute([$tournament['id']]);
        $team_count = $stmt->fetchColumn();
        
        if ($team_count > 0) {
            echo "<p class='success'>✅ Times encontrados no torneio: $team_count</p>";
            
            // Link para testar
            echo "<div style='background:#e8f5e8; padding:15px; border-radius:8px; margin:10px 0;'>";
            echo "<h4>🧪 Teste Manual:</h4>";
            echo "<ol>";
            echo "<li><a href='app/pages/adm/player_manager.php?tournament_id=" . $tournament['id'] . "' target='_blank' style='background:#27ae60;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>⚽ Abrir Gerenciador de Jogadores</a></li>";
            echo "<li>Clique no botão de editar (✏️) de qualquer jogador</li>";
            echo "<li>Verifique se aparece a seção de upload de imagem</li>";
            echo "<li>Teste o upload de uma nova foto</li>";
            echo "<li>Verifique se o preview funciona</li>";
            echo "</ol>";
            echo "</div>";
            
        } else {
            echo "<p class='warning'>⚠️ Nenhum time encontrado no torneio</p>";
        }
        
    } else {
        echo "<p class='warning'>⚠️ Nenhum torneio encontrado</p>";
    }
    
    echo "<h2>4. Verificando Arquivos Modificados</h2>";
    
    $files_to_check = [
        'app/pages/adm/player_manager.php' => 'Gerenciador de Jogadores'
    ];
    
    foreach ($files_to_check as $file => $name) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ $name existe</p>";
            
            $content = file_get_contents($file);
            
            // Verificar se contém as modificações
            $checks = [
                'edit_imagem' => 'Campo de upload de imagem',
                'previewEditImage' => 'Função de preview',
                'current_image_section' => 'Seção de imagem atual',
                'enctype="multipart/form-data"' => 'Suporte a upload no form'
            ];
            
            foreach ($checks as $search => $description) {
                if (strpos($content, $search) !== false) {
                    echo "<p class='success'>✅ $description implementado</p>";
                } else {
                    echo "<p class='error'>❌ $description não encontrado</p>";
                }
            }
            
        } else {
            echo "<p class='error'>❌ $name não encontrado</p>";
        }
    }
    
    echo "<h2>5. Funcionalidades Implementadas</h2>";
    
    echo "<div style='background:#e8f5e8; padding:20px; border-radius:10px; margin:20px 0;'>";
    echo "<h3>🎉 FUNCIONALIDADES ADICIONADAS:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Upload de imagem na edição:</strong> Campo para nova foto</li>";
    echo "<li>✅ <strong>Preview da imagem atual:</strong> Mostra foto existente</li>";
    echo "<li>✅ <strong>Preview da nova imagem:</strong> Visualização antes de salvar</li>";
    echo "<li>✅ <strong>Processamento no backend:</strong> Salva nova imagem no banco</li>";
    echo "<li>✅ <strong>Interface melhorada:</strong> Seção organizada para imagens</li>";
    echo "<li>✅ <strong>Validação:</strong> Mantém imagem atual se não enviar nova</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>📋 Como Usar:</h3>";
    echo "<ol>";
    echo "<li>Acesse o <strong>Gerenciador de Jogadores</strong></li>";
    echo "<li>Clique no botão <strong>Editar</strong> (✏️) de um jogador</li>";
    echo "<li>No modal, você verá:</li>";
    echo "<ul>";
    echo "<li><strong>Foto Atual:</strong> Se o jogador já tem foto</li>";
    echo "<li><strong>Campo de Upload:</strong> Para selecionar nova foto</li>";
    echo "<li><strong>Preview:</strong> Visualização da nova foto selecionada</li>";
    echo "</ul>";
    echo "<li>Selecione uma nova foto (opcional)</li>";
    echo "<li>Clique em <strong>Salvar</strong></li>";
    echo "</ol>";
    
    echo "<div style='background:#fff3cd; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<h4>💡 Dicas:</h4>";
    echo "<ul>";
    echo "<li>Se não selecionar nova foto, a atual será mantida</li>";
    echo "<li>Formatos suportados: JPG, PNG, GIF, WebP</li>";
    echo "<li>A imagem é armazenada diretamente no banco de dados</li>";
    echo "<li>O preview mostra como ficará a nova foto</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro crítico: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><small>Teste executado em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
