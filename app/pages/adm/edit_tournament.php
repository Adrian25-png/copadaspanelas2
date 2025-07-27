<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['id'] ?? null;

if (!$tournament_id) {
    $_SESSION['error'] = "ID do torneio não especificado";
    header('Location: tournament_list.php');
    exit;
}

$tournament = $tournamentManager->getTournamentById($tournament_id);
if (!$tournament) {
    $_SESSION['error'] = "Torneio não encontrado";
    header('Location: tournament_list.php');
    exit;
}

// Processar edição
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_tournament') {
    try {
        $name = trim($_POST['name']);
        $year = (int)$_POST['year'];
        $description = trim($_POST['description']);
        $status = $_POST['status'];
        
        if (empty($name)) {
            throw new Exception("Nome do torneio é obrigatório");
        }
        
        if ($year < 2020 || $year > 2030) {
            throw new Exception("Ano deve estar entre 2020 e 2030");
        }
        
        $stmt = $pdo->prepare("
            UPDATE tournaments 
            SET name = ?, year = ?, description = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $year, $description, $status, $tournament_id])) {
            $tournamentManager->logActivity($tournament_id, 'TOURNAMENT_UPDATED', 
                "Torneio atualizado: $name");
            $_SESSION['success'] = "Torneio atualizado com sucesso!";
        } else {
            throw new Exception("Erro ao atualizar torneio");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: edit_tournament.php?id=$tournament_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Torneio - <?= htmlspecialchars($tournament['name']) ?></title>
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
            max-width: 800px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .form-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f39c12;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #ecf0f1;
        }
        
        .form-input {
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
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
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
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
        
        .info-section {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .info-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Editar Torneio</h1>
            <p style="opacity: 0.8;">Modifique as informações do torneio</p>
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
        
        <!-- Informações Atuais -->
        <div class="info-section">
            <h3 style="margin-bottom: 15px; color: #3498db;">📊 Informações Atuais</h3>
            <div class="info-grid">
                <?php
                $stats = $tournamentManager->getTournamentStatistics($tournament_id);
                ?>
                <div class="info-item">
                    <div class="info-number"><?= $stats['total_teams'] ?? 0 ?></div>
                    <div class="info-label">Times</div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?= $stats['total_groups'] ?? 0 ?></div>
                    <div class="info-label">Grupos</div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?= $stats['total_matches'] ?? 0 ?></div>
                    <div class="info-label">Jogos</div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?= $stats['finished_matches'] ?? 0 ?></div>
                    <div class="info-label">Finalizados</div>
                </div>
            </div>
        </div>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="update_tournament">
            
            <!-- Informações Básicas -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Informações Básicas
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome do Torneio *</label>
                        <input type="text" name="name" class="form-input" 
                               value="<?= htmlspecialchars($tournament['name']) ?>" 
                               required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ano *</label>
                        <input type="number" name="year" class="form-input" 
                               min="2020" max="2030" 
                               value="<?= $tournament['year'] ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-input form-textarea" 
                              maxlength="500"><?= htmlspecialchars($tournament['description'] ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Status do Torneio -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-flag"></i>
                    Status do Torneio
                </h3>
                
                <div class="form-group">
                    <label class="form-label">Status Atual: 
                        <span class="status-badge status-<?= $tournament['status'] ?>">
                            <?= ucfirst($tournament['status']) ?>
                        </span>
                    </label>
                    <select name="status" class="form-input" required>
                        <option value="draft" <?= $tournament['status'] === 'draft' ? 'selected' : '' ?>>
                            Rascunho - Em preparação
                        </option>
                        <option value="active" <?= $tournament['status'] === 'active' ? 'selected' : '' ?>>
                            Ativo - Em andamento
                        </option>
                        <option value="completed" <?= $tournament['status'] === 'completed' ? 'selected' : '' ?>>
                            Finalizado - Concluído
                        </option>
                        <option value="cancelled" <?= $tournament['status'] === 'cancelled' ? 'selected' : '' ?>>
                            Cancelado - Suspenso
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                
                <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                
                <a href="tournament_list.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> Lista de Torneios
                </a>
            </div>
        </form>
        
        <!-- Ações Avançadas -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-cogs"></i>
                Ações Avançadas
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Gerenciar
                </a>
                
                <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn btn-primary">
                    <i class="fas fa-table"></i> Classificação
                </a>
                
                <a href="tournament_report.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Relatório
                </a>
                
                <button onclick="deleteTournament()" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Excluir Torneio
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function deleteTournament() {
            if (confirm('⚠️ ATENÇÃO!\n\nEsta ação excluirá PERMANENTEMENTE:\n- O torneio\n- Todos os times\n- Todos os grupos\n- Todos os jogos\n- Todas as estatísticas\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?')) {
                const confirmation = prompt('Digite "EXCLUIR" para confirmar a exclusão definitiva:');
                if (confirmation === 'EXCLUIR') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'tournament_list.php';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_tournament">
                        <input type="hidden" name="tournament_id" value="<?= $tournament_id ?>">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert('Confirmação incorreta. Exclusão cancelada.');
                }
            }
        }
        
        // Validação do formulário
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const year = parseInt(document.querySelector('input[name="year"]').value);
            
            if (!name) {
                alert('Nome do torneio é obrigatório');
                e.preventDefault();
                return;
            }
            
            if (year < 2020 || year > 2030) {
                alert('Ano deve estar entre 2020 e 2030');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
