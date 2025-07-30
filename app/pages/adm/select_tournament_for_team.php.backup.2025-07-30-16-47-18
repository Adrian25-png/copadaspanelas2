<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Buscar todos os torneios
try {
    $stmt = $pdo->query("SELECT * FROM tournaments ORDER BY created_at DESC");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tournaments = [];
    $error = "Erro ao carregar torneios: " . $e->getMessage();
}

include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Torneio - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
            padding-top: 80px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .select-card {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
        }
        
        .title {
            text-align: center;
            margin-bottom: 30px;
            color: #f39c12;
            font-size: 2.5rem;
        }
        
        .info-box {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .tournaments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tournament-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tournament-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-left-color: #f39c12;
        }
        
        .tournament-title {
            color: #f39c12;
            font-size: 1.3rem;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .tournament-info {
            margin-bottom: 15px;
        }
        
        .tournament-info p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .tournament-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #f39c12;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #2ecc71; }
        
        .btn-primary { background: #3498db; }
        .btn-primary:hover { background: #2980b9; }
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.8;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #3498db;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .quick-actions {
            background: rgba(243, 156, 18, 0.2);
            border: 2px solid #f39c12;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .quick-title {
            color: #f39c12;
            font-size: 1.3rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .quick-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .quick-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="select-card">
            <h1 class="title">
                <i class="fas fa-trophy"></i>
                Selecionar Torneio para Cadastrar Time
            </h1>
            
            <div class="info-box">
                <h3 style="color: #3498db; margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i>
                    Como Funciona
                </h3>
                <p>Selecione o torneio onde deseja cadastrar um novo time. Cada time deve estar associado a um torneio específico.</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(231, 76, 60, 0.2); border: 2px solid #e74c3c; border-radius: 10px; padding: 20px; margin-bottom: 30px; text-align: center;">
                    <strong style="color: #e74c3c;">❌ Erro:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($tournaments)): ?>
                <div class="tournaments-grid">
                    <?php foreach ($tournaments as $tournament): ?>
                        <?php
                        // Buscar estatísticas do torneio
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total_teams FROM times WHERE tournament_id = ?");
                            $stmt->execute([$tournament['id']]);
                            $total_teams = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total_matches FROM matches WHERE tournament_id = ?");
                            $stmt->execute([$tournament['id']]);
                            $total_matches = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare("SELECT COUNT(*) as completed_matches FROM matches WHERE tournament_id = ? AND status = 'finalizado'");
                            $stmt->execute([$tournament['id']]);
                            $completed_matches = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $total_teams = 0;
                            $total_matches = 0;
                            $completed_matches = 0;
                        }
                        ?>
                        
                        <div class="tournament-card" onclick="window.location.href='team_manager.php?tournament_id=<?= $tournament['id'] ?>'" style="cursor: pointer;">
                            <div class="tournament-title">
                                <?= htmlspecialchars($tournament['nome']) ?>
                            </div>
                            
                            <div class="tournament-info">
                                <p><strong>Ano:</strong> <?= htmlspecialchars($tournament['ano']) ?></p>
                                <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($tournament['status'] ?? 'ativo')) ?></p>
                                <p><strong>Criado:</strong> <?= date('d/m/Y', strtotime($tournament['created_at'])) ?></p>
                            </div>
                            
                            <div class="tournament-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?= $total_teams ?></div>
                                    <div class="stat-label">Times</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $total_matches ?></div>
                                    <div class="stat-label">Jogos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $completed_matches ?></div>
                                    <div class="stat-label">Finalizados</div>
                                </div>
                            </div>
                            
                            <div style="text-align: center;">
                                <a href="team_manager.php?tournament_id=<?= $tournament['id'] ?>" class="btn btn-success" onclick="event.stopPropagation();">
                                    <i class="fas fa-plus"></i>
                                    Cadastrar Time Aqui
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-trophy"></i>
                    <h3>Nenhum Torneio Encontrado</h3>
                    <p>Você precisa criar um torneio antes de cadastrar times.</p>
                    <a href="tournament_wizard.php" class="btn btn-success">
                        <i class="fas fa-magic"></i>
                        Criar Primeiro Torneio
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="quick-actions">
                <div class="quick-title">
                    <i class="fas fa-rocket"></i>
                    Ações Rápidas
                </div>
                <div class="quick-grid">
                    <div class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <h4>Criar Torneio</h4>
                        <p>Assistente completo para criar um novo torneio</p>
                        <a href="tournament_wizard.php" class="btn btn-primary">
                            Criar
                        </a>
                    </div>
                    <div class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h4>Ver Torneios</h4>
                        <p>Lista completa de todos os torneios</p>
                        <a href="tournament_list.php" class="btn">
                            Ver Lista
                        </a>
                    </div>
                    <div class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Todos os Times</h4>
                        <p>Visualizar todos os times cadastrados</p>
                        <a href="all_teams.php" class="btn">
                            Ver Times
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <button onclick="history.back()" class="btn btn-danger">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </button>
                <a href="dashboard_simple.php" class="btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
    

</body>
</html>
