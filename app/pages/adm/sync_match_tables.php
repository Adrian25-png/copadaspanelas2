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

// Verificar se há dados nas duas tabelas
$stmt = $pdo->query("SELECT COUNT(*) as count FROM jogos_fase_grupos");
$jogos_fase_grupos_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM matches WHERE phase IN ('grupos', 'Fase de Grupos')");
$matches_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$sync_needed = false;
$sync_message = "";

if ($jogos_fase_grupos_count > 0 && $matches_count == 0) {
    $sync_needed = true;
    $sync_message = "Dados encontrados apenas em 'jogos_fase_grupos'. Sincronização necessária.";
} elseif ($jogos_fase_grupos_count > 0 && $matches_count > 0) {
    $sync_message = "Dados encontrados em ambas as tabelas. Verificação manual recomendada.";
} elseif ($matches_count > 0) {
    $sync_message = "Dados encontrados apenas em 'matches'. Sistema atualizado.";
} else {
    $sync_message = "Nenhum dado encontrado em ambas as tabelas.";
}

// Processar sincronização se solicitado
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'sync_tables') {
    try {
        $pdo->beginTransaction();
        
        // Buscar dados da tabela antiga
        $stmt = $pdo->query("
            SELECT jfg.*, g.tournament_id 
            FROM jogos_fase_grupos jfg
            LEFT JOIN grupos g ON jfg.grupo_id = g.id
            ORDER BY jfg.id
        ");
        $old_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $synced_count = 0;
        
        foreach ($old_matches as $old_match) {
            // Verificar se já existe na tabela nova
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM matches 
                WHERE tournament_id = ? AND group_id = ? 
                AND team1_id = ? AND team2_id = ?
            ");
            $stmt->execute([
                $old_match['tournament_id'],
                $old_match['grupo_id'],
                $old_match['timeA_id'],
                $old_match['timeB_id']
            ]);
            
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if (!$exists) {
                // Determinar status baseado nos resultados
                $status = 'agendado';
                if ($old_match['resultado_timeA'] !== null && $old_match['resultado_timeB'] !== null) {
                    $status = 'finalizado';
                }
                
                // Inserir na tabela nova
                $stmt = $pdo->prepare("
                    INSERT INTO matches (
                        tournament_id, group_id, team1_id, team2_id,
                        team1_goals, team2_goals, phase, status,
                        match_date, round_number, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'grupos', ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $old_match['tournament_id'],
                    $old_match['grupo_id'],
                    $old_match['timeA_id'],
                    $old_match['timeB_id'],
                    $old_match['gols_marcados_timeA'],
                    $old_match['gols_marcados_timeB'],
                    $status,
                    $old_match['data_jogo'],
                    $old_match['rodada']
                ]);
                
                $synced_count++;
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Sincronização concluída! $synced_count jogos sincronizados.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erro na sincronização: " . $e->getMessage();
    }
    
    header("Location: sync_match_tables.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronização de Tabelas de Jogos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-box {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .status-info { background-color: #e3f2fd; border-left: 4px solid #2196f3; }
        .status-warning { background-color: #fff3e0; border-left: 4px solid #ff9800; }
        .status-success { background-color: #e8f5e8; border-left: 4px solid #4caf50; }
        .status-error { background-color: #ffebee; border-left: 4px solid #f44336; }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background-color: #0056b3; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Sincronização de Tabelas de Jogos</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="status-box status-success">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="status-box status-error">
                ❌ <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="status-box status-info">
            <h3>Status das Tabelas:</h3>
            <table>
                <tr>
                    <th>Tabela</th>
                    <th>Registros</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>jogos_fase_grupos (antiga)</td>
                    <td><?= $jogos_fase_grupos_count ?></td>
                    <td><?= $jogos_fase_grupos_count > 0 ? '✅ Com dados' : '❌ Vazia' ?></td>
                </tr>
                <tr>
                    <td>matches (nova)</td>
                    <td><?= $matches_count ?></td>
                    <td><?= $matches_count > 0 ? '✅ Com dados' : '❌ Vazia' ?></td>
                </tr>
            </table>
        </div>
        
        <div class="status-box <?= $sync_needed ? 'status-warning' : 'status-info' ?>">
            <p><strong>Diagnóstico:</strong> <?= $sync_message ?></p>
        </div>
        
        <?php if ($sync_needed): ?>
            <div class="status-box status-warning">
                <h3>⚠️ Sincronização Necessária</h3>
                <p>Foi detectado que os dados dos jogos estão na tabela antiga <code>jogos_fase_grupos</code>, mas a tabela de classificação foi atualizada para usar a tabela <code>matches</code>.</p>
                <p>Clique no botão abaixo para sincronizar os dados:</p>
                
                <form method="POST" onsubmit="return confirm('Confirma a sincronização dos dados? Esta ação irá copiar os jogos da tabela antiga para a nova.')">
                    <input type="hidden" name="action" value="sync_tables">
                    <button type="submit" class="btn">🔄 Sincronizar Dados</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="status-box status-info">
            <h3>📋 Instruções:</h3>
            <ol>
                <li><strong>Se você tem dados apenas em "jogos_fase_grupos":</strong> Use o botão de sincronização acima</li>
                <li><strong>Se você tem dados em ambas as tabelas:</strong> Verifique manualmente qual está mais atualizada</li>
                <li><strong>Se você tem dados apenas em "matches":</strong> Tudo está correto, nenhuma ação necessária</li>
            </ol>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="dashboard_simple.php" class="btn">← Voltar ao Dashboard</a>
        </div>
    </div>
</body>
</html>
