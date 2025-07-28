<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Processar ações
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'delete_tournament':
                $tournament_id = $_POST['tournament_id'];
                
                // Verificar se existe
                $stmt = $pdo->prepare("SELECT name FROM tournaments WHERE id = ?");
                $stmt->execute([$tournament_id]);
                $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$tournament) {
                    throw new Exception("Torneio não encontrado");
                }
                
                // Excluir em ordem para respeitar foreign keys
                $pdo->beginTransaction();
                
                $pdo->prepare("DELETE FROM matches WHERE tournament_id = ?")->execute([$tournament_id]);
                $pdo->prepare("DELETE FROM times WHERE tournament_id = ?")->execute([$tournament_id]);
                $pdo->prepare("DELETE FROM grupos WHERE tournament_id = ?")->execute([$tournament_id]);
                $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tournament_id]);
                
                $pdo->commit();
                
                $_SESSION['success'] = "Torneio '{$tournament['name']}' excluído com sucesso!";
                break;
                
            case 'update_status':
                $tournament_id = $_POST['tournament_id'];
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("UPDATE tournaments SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $tournament_id]);
                
                $_SESSION['success'] = "Status do torneio atualizado!";
                break;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: tournament_list.php');
    exit;
}

// Obter todos os torneios
try {
    $stmt = $pdo->query("
        SELECT t.*, 
               (SELECT COUNT(*) FROM times WHERE tournament_id = t.id) as team_count,
               (SELECT COUNT(*) FROM grupos WHERE tournament_id = t.id) as group_count,
               (SELECT COUNT(*) FROM matches WHERE tournament_id = t.id) as match_count,
               (SELECT COUNT(*) FROM matches WHERE tournament_id = t.id AND status = 'finalizado') as finished_matches
        FROM tournaments t
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
    <title>Lista de Torneios - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #2ecc71;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .tournaments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .tournament-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tournament-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .tournament-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .tournament-name {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #f39c12;
        }
        
        .tournament-year {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-draft { background: #f39c12; }
        .status-active { background: #27ae60; }
        .status-completed { background: #3498db; }
        .status-cancelled { background: #e74c3c; }
        
        .tournament-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-item {
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .tournament-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        
        .status-select {
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .tournaments-grid {
                grid-template-columns: 1fr;
            }
            
            .tournament-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-trophy"></i> Lista de Torneios</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Gerencie todos os torneios do sistema</p>
            </div>
            <div>
                <a href="create_tournament.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Novo Torneio
                </a>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
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
                    <div class="tournament-card">
                        <div class="tournament-header">
                            <div>
                                <div class="tournament-name"><?= htmlspecialchars($tournament['name']) ?></div>
                                <div class="tournament-year"><?= $tournament['year'] ?></div>
                            </div>
                            <span class="status-badge status-<?= $tournament['status'] ?>">
                                <?= ucfirst($tournament['status']) ?>
                            </span>
                        </div>
                        
                        <?php if ($tournament['description']): ?>
                            <p style="opacity: 0.8; margin: 10px 0;"><?= htmlspecialchars($tournament['description']) ?></p>
                        <?php endif; ?>
                        
                        <div class="tournament-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $tournament['team_count'] ?></div>
                                <div class="stat-label">Times</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $tournament['group_count'] ?></div>
                                <div class="stat-label">Grupos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $tournament['match_count'] ?></div>
                                <div class="stat-label">Jogos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $tournament['finished_matches'] ?></div>
                                <div class="stat-label">Finalizados</div>
                            </div>
                        </div>
                        
                        <!-- Mudança de Status -->
                        <form method="POST" style="margin-bottom: 15px;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="tournament_id" value="<?= $tournament['id'] ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="draft" <?= $tournament['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                                <option value="active" <?= $tournament['status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                                <option value="completed" <?= $tournament['status'] === 'completed' ? 'selected' : '' ?>>Finalizado</option>
                                <option value="cancelled" <?= $tournament['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </form>
                        
                        <div class="tournament-actions">
                            <a href="tournament_management.php?id=<?= $tournament['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-cog"></i> Gerenciar
                            </a>
                            
                            <a href="edit_tournament.php?id=<?= $tournament['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            
                            <a href="bulk_results.php?tournament=<?= $tournament['id'] ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-tachometer-alt"></i> Resultados
                            </a>
                            
                            <button onclick="deleteTournament(<?= $tournament['id'] ?>, '<?= htmlspecialchars($tournament['name']) ?>')" 
                                    class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Nenhum Torneio Encontrado</h3>
                <p>Crie seu primeiro torneio para começar a organizar competições.</p>
                <a href="create_tournament.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Criar Primeiro Torneio
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function deleteTournament(tournamentId, tournamentName) {
            if (confirm(`⚠️ ATENÇÃO!\n\nEsta ação excluirá PERMANENTEMENTE o torneio "${tournamentName}" e todos os dados relacionados:\n\n- Todos os times\n- Todos os grupos\n- Todos os jogos\n- Todas as estatísticas\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?`)) {
                const confirmation = prompt('Digite "EXCLUIR" para confirmar a exclusão definitiva:');
                if (confirmation === 'EXCLUIR') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_tournament">
                        <input type="hidden" name="tournament_id" value="${tournamentId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert('Confirmação incorreta. Exclusão cancelada.');
                }
            }
        }
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.tournament-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
