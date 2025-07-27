<?php
/**
 * Processamento de ações dos torneios (excluir, arquivar, etc.)
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

// Verificar se há uma ação solicitada
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    $_SESSION['error'] = "Ação ou ID do torneio não especificado";
    header('Location: tournament_list.php');
    exit;
}

$action = $_GET['action'];
$tournament_id = (int)$_GET['id'];

try {
    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    // Verificar se o torneio existe
    $tournament = $tournamentManager->getTournamentById($tournament_id);
    if (!$tournament) {
        $_SESSION['error'] = "Torneio não encontrado";
        header('Location: tournament_list.php');
        exit;
    }
    
    switch ($action) {
        case 'delete':
            // Confirmar exclusão
            if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
                $tournamentManager->deleteTournament($tournament_id);
                $_SESSION['success'] = "Torneio '{$tournament['name']}' excluído com sucesso!";
                header('Location: tournament_list.php');
                exit;
            } else {
                // Mostrar página de confirmação
                showDeleteConfirmation($tournament);
                exit;
            }
            break;
            
        case 'archive':
            $tournamentManager->archiveTournament($tournament_id);
            $_SESSION['success'] = "Torneio '{$tournament['name']}' arquivado com sucesso!";
            header('Location: tournament_list.php');
            exit;
            break;
            
        case 'activate':
            $tournamentManager->activateTournament($tournament_id);
            $_SESSION['success'] = "Torneio '{$tournament['name']}' ativado com sucesso!";
            header('Location: tournament_list.php');
            exit;
            break;
            
        default:
            $_SESSION['error'] = "Ação não reconhecida";
            header('Location: tournament_list.php');
            exit;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: tournament_list.php');
    exit;
}

/**
 * Mostrar página de confirmação de exclusão
 */
function showDeleteConfirmation($tournament) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmar Exclusão - Copa das Panelas</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .confirmation-container {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 15px;
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            }
            
            .warning-icon {
                font-size: 4rem;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            
            h1 {
                color: #2c3e50;
                margin-bottom: 20px;
                font-size: 1.8rem;
            }
            
            .tournament-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                border-left: 4px solid #e74c3c;
            }
            
            .tournament-name {
                font-size: 1.2rem;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 10px;
            }
            
            .tournament-details {
                color: #7f8c8d;
                font-size: 0.9rem;
            }
            
            .warning-text {
                color: #e74c3c;
                font-weight: bold;
                margin: 20px 0;
                font-size: 1.1rem;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 30px;
            }
            
            .btn {
                padding: 12px 30px;
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
            
            .btn-danger {
                background: #e74c3c;
                color: white;
            }
            
            .btn-danger:hover {
                background: #c0392b;
                transform: translateY(-2px);
            }
            
            .btn-secondary {
                background: #95a5a6;
                color: white;
            }
            
            .btn-secondary:hover {
                background: #7f8c8d;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="confirmation-container">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h1>Confirmar Exclusão do Torneio</h1>
            
            <div class="tournament-info">
                <div class="tournament-name">
                    <?= htmlspecialchars($tournament['name']) ?>
                </div>
                <div class="tournament-details">
                    Ano: <?= $tournament['year'] ?> | 
                    Status: <?= ucfirst($tournament['status']) ?> |
                    Grupos: <?= $tournament['num_groups'] ?? 'N/A' ?>
                </div>
            </div>
            
            <div class="warning-text">
                ⚠️ Esta ação não pode ser desfeita!
            </div>
            
            <p>Todos os dados relacionados a este torneio serão permanentemente excluídos, incluindo:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>Times e jogadores</li>
                <li>Jogos e resultados</li>
                <li>Estatísticas</li>
                <li>Configurações</li>
            </ul>
            
            <p><strong>Um backup será criado automaticamente antes da exclusão.</strong></p>
            
            <div class="action-buttons">
                <a href="tournament_actions.php?action=delete&id=<?= $tournament['id'] ?>&confirm=yes" 
                   class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    Sim, Excluir Definitivamente
                </a>
                
                <a href="tournament_list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
