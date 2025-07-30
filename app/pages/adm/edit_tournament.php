<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../includes/PermissionManager.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$permissionManager = getPermissionManager($pdo);

// Verificar permissão para editar torneios
$permissionManager->requirePermission('edit_tournament');

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
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            text-align: center;
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

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-header h1 i {
            color: #7B1FA2;
        }

        .form-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #7B1FA2;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            font-size: 1rem;
            color: #9E9E9E;
        }

        .form-input {
            padding: 15px;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #7B1FA2;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }
        
        .form-input::placeholder {
            color: #666;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .btn-standard {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 15px 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 8px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-success {
            background: #4CAF50;
            border: 2px solid #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
            border-color: #45a049;
        }

        .btn-danger {
            background: #F44336;
            border: 2px solid #F44336;
            color: white;
        }

        .btn-danger:hover {
            background: #da190b;
            border-color: #da190b;
        }

        .actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 35px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #2A2A2A;
            border-left-color: #4CAF50;
            color: #4CAF50;
        }

        .alert-error {
            background: #2A2A2A;
            border-left-color: #F44336;
            color: #F44336;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft { background: #FF9800; color: white; }
        .status-active { background: #4CAF50; color: white; }
        .status-completed { background: #2196F3; color: white; }
        .status-cancelled { background: #F44336; color: white; }

        .info-section {
            background: #1E1E1E;
            border-left: 4px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .info-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .info-item {
            text-align: center;
        }
        
        .info-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2196F3;
            margin-bottom: 5px;
        }

        .info-label {
            font-size: 0.9rem;
            color: #9E9E9E;
        }

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <h1><i class="fas fa-edit"></i> Editar Torneio</h1>
            <p style="color: #9E9E9E; font-size: 1.1rem; margin-top: 8px;">Modifique as informações do torneio</p>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Informações Atuais -->
        <div class="info-section fade-in">
            <h3 style="margin-bottom: 20px; color: #2196F3; font-size: 1.3rem; font-weight: 600;">
                <i class="fas fa-chart-bar"></i> Informações Atuais
            </h3>
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
            <div class="form-section fade-in">
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
            
            <div class="actions fade-in">
                <button type="submit" class="btn-standard btn-success">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>

                <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn-standard">
                    <i class="fas fa-times"></i> Cancelar
                </a>

                <a href="tournament_list.php" class="btn-standard">
                    <i class="fas fa-list"></i> Lista de Torneios
                </a>
            </div>
        </form>

        <!-- Ações Avançadas -->
        <div class="form-section fade-in">
            <h3 class="section-title">
                <i class="fas fa-cogs"></i>
                Ações Avançadas
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn-standard">
                    <i class="fas fa-cog"></i> Gerenciar
                </a>

                <a href="tournament_standings.php?id=<?= $tournament_id ?>" class="btn-standard">
                    <i class="fas fa-table"></i> Classificação
                </a>

                <a href="tournament_report.php?id=<?= $tournament_id ?>" class="btn-standard">
                    <i class="fas fa-file-pdf"></i> Relatório
                </a>

                <button onclick="deleteTournament()" class="btn-standard btn-danger">
                    <i class="fas fa-trash"></i> Excluir Torneio
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Animações Copa das Panelas
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Animação especial para números das estatísticas
            const infoNumbers = document.querySelectorAll('.info-number');
            infoNumbers.forEach((number, index) => {
                const finalValue = parseInt(number.textContent);
                number.textContent = '0';

                setTimeout(() => {
                    let current = 0;
                    const increment = finalValue / 20;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= finalValue) {
                            number.textContent = finalValue;
                            clearInterval(timer);
                        } else {
                            number.textContent = Math.floor(current);
                        }
                    }, 50);
                }, 1000 + (index * 200));
            });

            // Efeitos de hover nos inputs
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'scale(1.02)';
                });

                input.addEventListener('blur', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });

        function deleteTournament() {
            if (confirm('Tem certeza que deseja excluir este torneio? Esta ação não pode ser desfeita.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'tournament_list.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_tournament">
                    <input type="hidden" name="tournament_id" value="<?= $tournament_id ?>">
                `;
                document.body.appendChild(form);
                form.submit();
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

            // Animação de salvamento
            const saveButton = document.querySelector('.btn-success');
            if (saveButton) {
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                saveButton.disabled = true;
            }
        });
    </script>
</body>
</html>
