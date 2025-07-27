<?php
/**
 * Dashboard Simplificado para Teste
 */

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once '../../config/conexao.php';
    require_once '../../classes/TournamentManager.php';

    $tournament_id = $_GET['id'] ?? null;
    
    if (!$tournament_id) {
        echo "<h1>❌ ID do torneio não fornecido</h1>";
        echo "<p><a href='tournament_list.php'>← Voltar para lista</a></p>";
        exit;
    }

    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);

    // Get tournament information
    $tournament = $tournamentManager->getTournamentById($tournament_id);
    if (!$tournament) {
        echo "<h1>❌ Torneio não encontrado</h1>";
        echo "<p>ID: $tournament_id</p>";
        echo "<p><a href='tournament_list.php'>← Voltar para lista</a></p>";
        exit;
    }

    // Get tournament statistics
    $stats = $tournamentManager->getTournamentStats($tournament_id);
    
    // Get recent activity
    $recent_activity = $tournamentManager->getActivityLog($tournament_id, 5);

} catch (Exception $e) {
    echo "<h1>❌ Erro no Dashboard</h1>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p><a href='tournament_list.php'>← Voltar para lista</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($tournament['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            color: white;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .tournament-info h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        
        .tournament-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .status-setup { background: #f39c12; }
        .status-active { background: #27ae60; }
        .status-completed { background: #3498db; }
        .status-archived { background: #95a5a6; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #ecf0f1;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .activity-log {
            margin-top: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tournament_list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Voltar para Lista de Torneios
        </a>
        
        <div class="header">
            <div class="tournament-info">
                <h1><?= htmlspecialchars($tournament['name']) ?></h1>
                <p>Ano: <?= $tournament['year'] ?> | 
                   Criado em: <?= date('d/m/Y', strtotime($tournament['created_at'])) ?></p>
                <span class="tournament-status status-<?= $tournament['status'] ?>">
                    <?= ($tournament['status'] === 'setup' ? 'Configuração' : 
                        ($tournament['status'] === 'active' ? 'Ativo' : 
                        ($tournament['status'] === 'completed' ? 'Concluído' : 'Arquivado'))) ?>
                </span>
            </div>
            
            <div class="actions">
                <?php if ($tournament['status'] === 'setup'): ?>
                    <a href="tournament_actions.php?action=activate&id=<?= $tournament_id ?>" class="btn btn-success">
                        <i class="fas fa-play"></i> Ativar Torneio
                    </a>
                <?php endif; ?>
                
                <a href="#" class="btn btn-secondary" onclick="alert('Funcionalidade em desenvolvimento')">
                    <i class="fas fa-download"></i> Exportar
                </a>
                
                <?php if ($tournament['status'] === 'active'): ?>
                    <a href="tournament_actions.php?action=archive&id=<?= $tournament_id ?>" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Arquivar
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-layer-group"></i> Grupos</h3>
                <div class="number"><?= $stats['total_groups'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Times</h3>
                <div class="number"><?= $stats['total_teams'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card">
                <h3><i class="fas fa-futbol"></i> Jogos</h3>
                <div class="number"><?= $stats['total_matches'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i> Concluídos</h3>
                <div class="number"><?= $stats['completed_matches'] ?? 0 ?></div>
            </div>
        </div>
        
        <?php if ($tournament['status'] === 'setup'): ?>
            <div style="background: rgba(241, 196, 15, 0.2); border-radius: 10px; padding: 20px; margin: 20px 0; border-left: 4px solid #f1c40f;">
                <h3><i class="fas fa-info-circle"></i> Próximos Passos</h3>
                <p>Este torneio está em configuração. Para começar a usar:</p>
                <ol>
                    <li>Adicione times aos grupos</li>
                    <li>Configure os jogos</li>
                    <li>Ative o torneio</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($recent_activity)): ?>
            <div class="activity-log">
                <h3><i class="fas fa-history"></i> Atividades Recentes</h3>
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <strong><?= htmlspecialchars($activity['action']) ?></strong>: 
                        <?= htmlspecialchars($activity['description']) ?>
                        <small style="float: right; opacity: 0.7;">
                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center; opacity: 0.7;">
            <p>Dashboard do Torneio - Copa das Panelas</p>
        </div>
    </div>
</body>
</html>
