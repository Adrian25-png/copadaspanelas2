<?php
session_start();

try {
    require_once '../../config/conexao.php';
    $pdo = conectar();
    
    // Estatísticas básicas
    $stmt = $pdo->query("SELECT COUNT(*) FROM tournaments");
    $total_tournaments = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM times");
    $total_teams = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM matches");
    $total_matches = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $total_tournaments = $total_teams = $total_matches = 0;
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
        }
        

        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .welcome {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #f39c12;
            margin-bottom: 10px;
        }
        
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
        }
        
        .action-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #f39c12;
        }
        
        .action-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-link {
            color: white;
            text-decoration: none;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .action-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .error {
            background: rgba(231, 76, 60, 0.3);
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <div class="welcome">
            <h1>🏆 Painel de Administração</h1>
            <p>Bem-vindo ao sistema Copa das Panelas</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                Erro: <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $total_tournaments ?></div>
                <div>Torneios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_teams ?></div>
                <div>Times</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_matches ?></div>
                <div>Jogos</div>
            </div>
        </div>
        
        <div class="actions">
            <div class="action-card">
                <div class="action-title">🏆 Torneios</div>
                <div class="action-links">
                    <a href="tournament_list.php" class="action-link">
                        <i class="fas fa-list"></i> Lista de Torneios
                    </a>
                    <a href="create_tournament.php" class="action-link">
                        <i class="fas fa-plus"></i> Criar Torneio
                    </a>
                    <a href="tournament_templates.php" class="action-link">
                        <i class="fas fa-file-alt"></i> Templates
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-title">👥 Times</div>
                <div class="action-links">
                    <a href="all_teams.php" class="action-link">
                        <i class="fas fa-users"></i> Todos os Times
                    </a>
                    <a href="team_manager.php" class="action-link">
                        <i class="fas fa-cog"></i> Gerenciar Times
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-title">⚽ Jogos</div>
                <div class="action-links">
                    <a href="global_calendar.php" class="action-link">
                        <i class="fas fa-calendar"></i> Calendário
                    </a>
                    <a href="bulk_results.php" class="action-link">
                        <i class="fas fa-edit"></i> Resultados
                    </a>
                    <a href="match_reports.php" class="action-link">
                        <i class="fas fa-file-alt"></i> Relatórios
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <div class="action-title">📺 Transmissão</div>
                <div class="action-links">
                    <a href="gerenciar_transmissao.php" class="action-link">
                        <i class="fas fa-broadcast-tower"></i> Gerenciar Live
                    </a>
                    <a href="../JogosProximos.php" class="action-link">
                        <i class="fas fa-eye"></i> Ver Página Pública
                    </a>
                </div>
            </div>

            <div class="action-card">
                <div class="action-title">👨‍💼 Administradores</div>
                <div class="action-links">
                    <a href="admin_manager.php" class="action-link">
                        <i class="fas fa-users-cog"></i> Gerenciar Admins
                    </a>
                    <a href="create_admin.php" class="action-link">
                        <i class="fas fa-user-plus"></i> Cadastrar Admin
                    </a>
                    <a href="admin_permissions.php" class="action-link">
                        <i class="fas fa-key"></i> Permissões
                    </a>
                </div>
            </div>

            <div class="action-card">
                <div class="action-title">📊 Sistema</div>
                <div class="action-links">
                    <a href="statistics.php" class="action-link">
                        <i class="fas fa-chart-bar"></i> Estatísticas
                    </a>
                    <a href="system_health.php" class="action-link">
                        <i class="fas fa-heartbeat"></i> Status
                    </a>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 40px; opacity: 0.7;">
            <p>Sistema Copa das Panelas - Versão Simplificada</p>
        </div>
    </div>
</body>
</html>
