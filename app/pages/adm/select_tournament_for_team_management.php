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

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

try {
    $pdo = conectar();
    
    // Buscar torneios disponíveis
    $stmt = $pdo->query("
        SELECT t.*, 
               COUNT(DISTINCT g.id) as total_grupos,
               COUNT(DISTINCT tm.id) as total_times
        FROM tournaments t
        LEFT JOIN grupos g ON t.id = g.tournament_id
        LEFT JOIN times tm ON g.id = tm.grupo_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $tournaments = [];
    $_SESSION['error'] = "Erro ao carregar torneios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Torneio - Gerenciar Times</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            margin: 0;
            padding: 20px;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-title .icon {
            font-size: 3rem;
            color: rgba(255,255,255,0.9);
        }

        .header-subtitle {
            margin-top: 10px;
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-secondary { 
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
        }

        .btn-success { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .tournaments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .tournament-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .tournament-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #d946ef);
        }

        .tournament-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .tournament-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .tournament-icon {
            font-size: 2.5rem;
            color: #6366f1;
        }

        .tournament-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #E0E0E0;
            margin-bottom: 5px;
        }

        .tournament-year {
            font-size: 1rem;
            color: rgba(255,255,255,0.7);
        }

        .tournament-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
        }

        .tournament-description {
            color: rgba(255,255,255,0.8);
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .tournament-actions {
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.6);
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: rgba(99, 102, 241, 0.3);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #E0E0E0;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .tournaments-grid {
                grid-template-columns: 1fr;
            }

            .main-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-users icon"></i>
                    <div>
                        <h1>Gerenciar Times</h1>
                        <div class="header-subtitle">Selecione um torneio para gerenciar seus times</div>
                    </div>
                </div>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Lista de Torneios -->
        <?php if (!empty($tournaments)): ?>
            <div class="tournaments-grid">
                <?php foreach ($tournaments as $tournament): ?>
                    <div class="tournament-card" onclick="window.location.href='team_manager.php?tournament_id=<?= $tournament['id'] ?>'">
                        <div class="tournament-header">
                            <i class="fas fa-trophy tournament-icon"></i>
                            <div>
                                <div class="tournament-title"><?= htmlspecialchars($tournament['name'] ?? $tournament['nome'] ?? 'Torneio') ?></div>
                                <div class="tournament-year"><?= $tournament['year'] ?? date('Y') ?></div>
                            </div>
                        </div>
                        
                        <div class="tournament-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $tournament['total_grupos'] ?></div>
                                <div class="stat-label">Grupos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $tournament['total_times'] ?></div>
                                <div class="stat-label">Times</div>
                            </div>
                        </div>
                        
                        <?php if (!empty($tournament['description'])): ?>
                            <div class="tournament-description">
                                <?= htmlspecialchars(substr($tournament['description'], 0, 100)) ?>
                                <?= strlen($tournament['description']) > 100 ? '...' : '' ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tournament-actions">
                            <div class="btn btn-success">
                                <i class="fas fa-users"></i> Gerenciar Times
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Nenhum Torneio Encontrado</h3>
                <p>Você precisa criar um torneio antes de gerenciar times.</p>
                <a href="tournament_templates.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Criar Primeiro Torneio
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
