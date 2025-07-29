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

                // Se está tentando ativar um torneio, verificar se já existe outro ativo
                if ($status === 'active') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments WHERE status = 'active' AND id != ?");
                    $stmt->execute([$tournament_id]);
                    $active_count = $stmt->fetchColumn();

                    if ($active_count > 0) {
                        $_SESSION['error'] = "Erro: Já existe um torneio ativo! Apenas um torneio pode estar ativo por vez. Desative o torneio atual antes de ativar outro.";
                        break;
                    }
                }

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

    // Contar torneios ativos
    $stmt = $pdo->query("SELECT COUNT(*) FROM tournaments WHERE status = 'active'");
    $active_tournaments_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $tournaments = [];
    $active_tournaments_count = 0;
    $_SESSION['error'] = "Erro ao carregar torneios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Torneios - Copa das Panelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <style>
        /* Reset básico */
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Space Grotesk', sans-serif;
        }

        body {
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            line-height: 1.6;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 30px;
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            color: #E1BEE7;
        }

        .page-title i {
            color: #7B1FA2;
            font-size: 2rem;
        }

        .btn-standard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-success {
            border-color: #4CAF50;
            color: #66BB6A;
        }

        .btn-success:hover {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-danger {
            border-color: #F44336;
            color: #EF5350;
        }

        .btn-danger:hover {
            background: #F44336;
            border-color: #F44336;
        }

        .btn-warning {
            border-color: #FF9800;
            color: #FFB74D;
        }

        .btn-warning:hover {
            background: #FF9800;
            border-color: #FF9800;
        }

        .btn-secondary {
            border-color: #9E9E9E;
            color: #BDBDBD;
        }

        .btn-secondary:hover {
            background: #9E9E9E;
            border-color: #9E9E9E;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.8rem;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 2px solid #4CAF50;
            color: #66BB6A;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #F44336;
            color: #EF5350;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .tournaments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }

        .tournament-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .tournament-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .tournament-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(123, 31, 162, 0.3);
            background: #252525;
        }

        .tournament-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .tournament-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #E1BEE7;
            line-height: 1.3;
        }

        .tournament-year {
            font-size: 0.9rem;
            color: #E0E0E0;
            opacity: 0.8;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-draft {
            background: rgba(255, 152, 0, 0.2);
            color: #FFB74D;
            border: 1px solid #FF9800;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #66BB6A;
            border: 1px solid #4CAF50;
        }

        .status-completed {
            background: rgba(33, 150, 243, 0.2);
            color: #64B5F6;
            border: 1px solid #2196F3;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #EF5350;
            border: 1px solid #F44336;
        }

        .tournament-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .stat-item {
            text-align: center;
            background: rgba(123, 31, 162, 0.1);
            border: 1px solid rgba(123, 31, 162, 0.3);
            padding: 15px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(123, 31, 162, 0.2);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #E1BEE7;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #E0E0E0;
            font-weight: 500;
        }

        .tournament-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .status-select {
            padding: 10px 12px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 0.9rem;
            font-family: 'Space Grotesk', sans-serif;
            margin-bottom: 15px;
            transition: border-color 0.3s ease;
            width: 100%;
        }

        .status-select:focus {
            outline: none;
            border-color: #E1BEE7;
        }

        .status-select option {
            background: #2A2A2A;
            color: #E0E0E0;
        }

        .status-select option:disabled {
            color: #9E9E9E;
            background: #1A1A1A;
        }

        .no-tournaments {
            text-align: center;
            padding: 80px 20px;
            color: #E0E0E0;
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .no-tournaments::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .no-tournaments i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: #7B1FA2;
            opacity: 0.7;
        }

        .no-tournaments h3 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #E1BEE7;
            font-weight: 600;
        }

        .no-tournaments p {
            font-size: 1.1rem;
            opacity: 0.8;
            max-width: 500px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #1E1E1E;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #7B1FA2;
            width: 90%;
            max-width: 500px;
            color: #E0E0E0;
            position: relative;
            overflow: hidden;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-top: 10px;
        }

        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            color: #E1BEE7;
            font-weight: 600;
        }

        .close {
            color: #9E9E9E;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #E1BEE7;
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #E1BEE7;
        }

        /* Animações */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }

            .page-title {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .tournaments-grid {
                grid-template-columns: 1fr;
            }

            .tournament-actions {
                justify-content: center;
            }

            .tournament-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-trophy"></i>
                    Lista de Torneios
                </h1>
                <p style="margin: 0; opacity: 0.9; color: #E0E0E0;">Gerencie todos os torneios do sistema Copa das Panelas</p>
            </div>
            <div>
                <a href="create_tournament.php" class="btn-standard btn-success">
                    <i class="fas fa-plus-circle"></i> Novo Torneio
                </a>
                <a href="dashboard_simple.php" class="btn-standard btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Aviso sobre Torneios Ativos -->
        <?php if ($active_tournaments_count > 0): ?>
            <div class="alert alert-info" style="background: rgba(52, 152, 219, 0.2); border: 1px solid #3498db; color: #3498db;">
                <i class="fas fa-info-circle"></i>
                <strong>Torneio Ativo:</strong> Existe <?= $active_tournaments_count ?> torneio ativo no momento.
                Apenas um torneio pode estar ativo por vez. Para ativar outro torneio, primeiro desative o atual.
            </div>
        <?php endif; ?>

        <!-- Lista de Torneios -->
        <?php if (!empty($tournaments)): ?>
            <div class="tournaments-grid">
                <?php foreach ($tournaments as $index => $tournament): ?>
                    <div class="tournament-card fade-in" style="animation-delay: <?= $index * 0.1 ?>s;">
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
                            <p style="color: #E0E0E0; opacity: 0.9; margin: 15px 0; line-height: 1.5;"><?= htmlspecialchars($tournament['description']) ?></p>
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
                                <option value="active"
                                    <?= $tournament['status'] === 'active' ? 'selected' : '' ?>
                                    <?= ($active_tournaments_count > 0 && $tournament['status'] !== 'active') ? 'disabled' : '' ?>>
                                    Ativo<?= ($active_tournaments_count > 0 && $tournament['status'] !== 'active') ? ' (Já existe um ativo)' : '' ?>
                                </option>
                                <option value="completed" <?= $tournament['status'] === 'completed' ? 'selected' : '' ?>>Finalizado</option>
                                <option value="cancelled" <?= $tournament['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </form>
                        
                        <div class="tournament-actions">
                            <a href="tournament_management.php?id=<?= $tournament['id'] ?>" class="btn-standard btn-sm">
                                <i class="fas fa-cog"></i> Gerenciar
                            </a>

                            <a href="edit_tournament.php?id=<?= $tournament['id'] ?>" class="btn-standard btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>

                            <a href="bulk_results.php?tournament=<?= $tournament['id'] ?>" class="btn-standard btn-success btn-sm">
                                <i class="fas fa-chart-line"></i> Resultados
                            </a>

                            <button onclick="deleteTournament(<?= $tournament['id'] ?>, '<?= htmlspecialchars($tournament['name']) ?>')"
                                    class="btn-standard btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-tournaments fade-in">
                <i class="fas fa-trophy"></i>
                <h3>Nenhum Torneio Encontrado</h3>
                <p>Crie seu primeiro torneio para começar a organizar competições no sistema Copa das Panelas.</p>
                <a href="create_tournament.php" class="btn-standard btn-success">
                    <i class="fas fa-plus-circle"></i> Criar Primeiro Torneio
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Adicionar animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Adicionar efeitos hover dinâmicos aos cards
            const tournamentCards = document.querySelectorAll('.tournament-card');
            tournamentCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Efeito de contagem nos números das estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                if (finalValue > 0) {
                    let currentValue = 0;
                    const increment = Math.ceil(finalValue / 10);

                    const counter = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            currentValue = finalValue;
                            clearInterval(counter);
                        }
                        stat.textContent = currentValue;
                    }, 50);
                }
            });
        });

        function deleteTournament(tournamentId, tournamentName) {
            if (confirm(`⚠️ ATENÇÃO: Excluir Torneio\n\nTem certeza que deseja excluir o torneio "${tournamentName}"?\n\n🗑️ Esta ação irá remover:\n• Todos os times\n• Todos os grupos\n• Todas as partidas\n• Todos os dados relacionados\n\n❌ Esta ação não pode ser desfeita!`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_tournament">
                    <input type="hidden" name="tournament_id" value="${tournamentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
