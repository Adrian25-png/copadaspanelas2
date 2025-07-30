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
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

$tournament_id = $_GET['tournament_id'] ?? null;

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

// Processar ações
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create_groups':
                $num_groups = (int)$_POST['num_groups'];
                $prefix = trim($_POST['prefix']) ?: 'Grupo';
                
                if ($num_groups < 1 || $num_groups > 20) {
                    throw new Exception("Número de grupos deve estar entre 1 e 20");
                }
                
                // Verificar se já existem grupos
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE tournament_id = ?");
                $stmt->execute([$tournament_id]);
                $existing_groups = $stmt->fetchColumn();
                
                if ($existing_groups > 0) {
                    throw new Exception("Já existem grupos criados para este torneio");
                }
                
                // Criar grupos
                for ($i = 1; $i <= $num_groups; $i++) {
                    $group_name = $prefix . ' ' . chr(64 + $i); // A, B, C, etc.
                    $stmt = $pdo->prepare("INSERT INTO grupos (nome, tournament_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$group_name, $tournament_id]);
                }
                
                $tournamentManager->logActivity($tournament_id, 'GROUPS_CREATED', "$num_groups grupos criados");
                $_SESSION['success'] = "$num_groups grupos criados com sucesso!";
                break;
                
            case 'add_group':
                $name = trim($_POST['group_name']);
                
                if (empty($name)) {
                    throw new Exception("Nome do grupo é obrigatório");
                }
                
                $stmt = $pdo->prepare("INSERT INTO grupos (nome, tournament_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$name, $tournament_id]);
                
                $tournamentManager->logActivity($tournament_id, 'GROUP_ADDED', "Grupo '$name' adicionado");
                $_SESSION['success'] = "Grupo '$name' adicionado com sucesso!";
                break;
                
            case 'delete_group':
                $group_id = $_POST['group_id'];
                
                // Verificar se o grupo tem times
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM times WHERE grupo_id = ?");
                $stmt->execute([$group_id]);
                $team_count = $stmt->fetchColumn();
                
                if ($team_count > 0) {
                    throw new Exception("Não é possível excluir o grupo pois ele possui times");
                }
                
                $stmt = $pdo->prepare("SELECT nome FROM grupos WHERE id = ?");
                $stmt->execute([$group_id]);
                $group_name = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM grupos WHERE id = ? AND tournament_id = ?");
                $stmt->execute([$group_id, $tournament_id]);
                
                $tournamentManager->logActivity($tournament_id, 'GROUP_DELETED', "Grupo '$group_name' excluído");
                $_SESSION['success'] = "Grupo excluído com sucesso!";
                break;
                
            case 'update_group':
                $group_id = $_POST['group_id'];
                $name = trim($_POST['group_name']);
                
                if (empty($name)) {
                    throw new Exception("Nome do grupo é obrigatório");
                }
                
                $stmt = $pdo->prepare("UPDATE grupos SET nome = ? WHERE id = ? AND tournament_id = ?");
                $stmt->execute([$name, $group_id, $tournament_id]);
                
                $tournamentManager->logActivity($tournament_id, 'GROUP_UPDATED', "Grupo '$name' atualizado");
                $_SESSION['success'] = "Grupo atualizado com sucesso!";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: group_manager.php?tournament_id=$tournament_id");
    exit;
}

// Obter grupos e times
$stmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM times WHERE grupo_id = g.id) as team_count,
           (SELECT COUNT(*) FROM matches WHERE group_id = g.id) as match_count
    FROM grupos g
    WHERE g.tournament_id = ?
    ORDER BY g.nome
");
$stmt->execute([$tournament_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter times por grupo
$teams_by_group = [];
if (!empty($groups)) {
    foreach ($groups as $group) {
        $stmt = $pdo->prepare("SELECT * FROM times WHERE grupo_id = ? ORDER BY nome");
        $stmt->execute([$group['id']]);
        $teams_by_group[$group['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Grupos - <?= htmlspecialchars($tournament['name']) ?></title>
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
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            gap: 15px;
        }

        .page-header h1 i {
            color: #7B1FA2;
        }

        .back-link {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-standard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            margin: 5px;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-primary {
            border-color: #7B1FA2;
            color: #E1BEE7;
        }

        .btn-success {
            border-color: #4CAF50;
            color: #66BB6A;
        }

        .btn-success:hover {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-warning {
            border-color: #FFC107;
            color: #FFD54F;
        }

        .btn-warning:hover {
            background: #FFC107;
            border-color: #FFC107;
            color: #1E1E1E;
        }

        .btn-danger {
            border-color: #F44336;
            color: #EF5350;
        }

        .btn-danger:hover {
            background: #F44336;
            border-color: #F44336;
        }

        .btn-secondary {
            border-color: #9E9E9E;
            color: #BDBDBD;
        }

        .btn-secondary:hover {
            background: #9E9E9E;
            border-color: #9E9E9E;
            color: white;
        }

        .btn-sm {
            padding: 10px 20px;
            font-size: 0.9rem;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-left-color: #4CAF50;
            color: #66BB6A;
        }

        .alert-success::before {
            background: linear-gradient(90deg, #4CAF50, #81C784);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-left-color: #F44336;
            color: #EF5350;
        }

        .alert-error::before {
            background: linear-gradient(90deg, #F44336, #EF5350);
        }

        .form-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
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
            display: flex;
            align-items: center;
            gap: 12px;
            color: #E1BEE7;
            padding-top: 5px;
        }

        .section-title i {
            color: #7B1FA2;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            font-size: 1rem;
            color: #E1BEE7;
        }

        .form-input {
            padding: 14px 16px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333333;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.2);
        }

        .form-input::placeholder {
            color: #9E9E9E;
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }

        .group-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .group-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .group-card:hover {
            transform: translateY(-5px);
            background: #252525;
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-top: 5px;
        }

        .group-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #E1BEE7;
            margin-bottom: 8px;
        }

        .group-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #E0E0E0;
        }

        .stat-item i {
            color: #7B1FA2;
        }

        .teams-list {
            margin: 20px 0;
        }

        .team-item {
            background: #2A2A2A;
            border: 1px solid #7B1FA2;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: #E0E0E0;
            transition: all 0.3s ease;
        }

        .team-item:hover {
            background: #333333;
            border-color: #E1BEE7;
        }

        .group-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #9E9E9E;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 25px;
            color: #7B1FA2;
            opacity: 0.6;
        }

        .empty-state h3 {
            color: #E1BEE7;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .empty-state p {
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Animações */
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
            .main-container {
                padding: 20px 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 25px 20px;
            }

            .page-header h1 {
                font-size: 1.8rem;
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .groups-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .group-actions {
                flex-direction: column;
                gap: 10px;
            }

            .btn-standard {
                width: 100%;
                justify-content: center;
            }

            .group-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 1.6rem;
            }

            .form-section {
                padding: 25px 20px;
            }

            .group-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-layer-group"></i> Gerenciar Grupos</h1>
                <p style="margin: 5px 0; color: #E0E0E0; opacity: 0.9; font-size: 1.1rem;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
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
        
        <!-- Criar Grupos -->
        <?php if (empty($groups)): ?>
            <div class="form-section fade-in">
                <h3 class="section-title">
                    <i class="fas fa-magic"></i>
                    Criar Grupos Automaticamente
                </h3>

                <form method="POST">
                    <input type="hidden" name="action" value="create_groups">

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Número de Grupos</label>
                            <input type="number" name="num_groups" class="form-input"
                                   min="1" max="20" value="4" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prefixo</label>
                            <input type="text" name="prefix" class="form-input"
                                   value="Grupo" placeholder="Grupo" maxlength="20">
                        </div>

                        <div>
                            <button type="submit" class="btn-standard btn-success">
                                <i class="fas fa-magic"></i> Criar Grupos
                            </button>
                        </div>
                    </div>
                </form>

                <p style="margin-top: 20px; color: #E0E0E0; opacity: 0.8; font-size: 1rem; line-height: 1.5;">
                    Os grupos serão criados automaticamente como: Grupo A, Grupo B, Grupo C, etc.
                </p>
            </div>
        <?php endif; ?>

        <!-- Adicionar Grupo Individual -->
        <div class="form-section fade-in">
            <h3 class="section-title">
                <i class="fas fa-plus-circle"></i>
                Adicionar Grupo Individual
            </h3>

            <form method="POST">
                <input type="hidden" name="action" value="add_group">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome do Grupo</label>
                        <input type="text" name="group_name" class="form-input"
                               placeholder="Digite o nome do grupo..."
                               required maxlength="50">
                    </div>

                    <div>
                        <button type="submit" class="btn-standard btn-primary">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Lista de Grupos -->
        <?php if (!empty($groups)): ?>
            <div class="groups-grid">
                <?php foreach ($groups as $index => $group): ?>
                    <div class="group-card fade-in" style="animation-delay: <?= $index * 0.1 ?>s;">
                        <div class="group-header">
                            <div>
                                <div class="group-name"><?= htmlspecialchars($group['nome']) ?></div>
                            </div>
                        </div>

                        <div class="group-stats">
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span><?= $group['team_count'] ?> times</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-futbol"></i>
                                <span><?= $group['match_count'] ?> jogos</span>
                            </div>
                        </div>
                        
                        <?php if (!empty($teams_by_group[$group['id']])): ?>
                            <div class="teams-list">
                                <strong style="color: #E1BEE7; margin-bottom: 10px; display: block;">Times:</strong>
                                <?php foreach ($teams_by_group[$group['id']] as $team): ?>
                                    <div class="team-item">
                                        <i class="fas fa-shield-alt"></i>
                                        <?= htmlspecialchars($team['nome']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="color: #9E9E9E; font-style: italic; padding: 20px; text-align: center;">
                                Nenhum time neste grupo
                            </div>
                        <?php endif; ?>

                        <div class="group-actions">
                            <button onclick="editGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['nome']) ?>')"
                                    class="btn-standard btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </button>

                            <button onclick="deleteGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['nome']) ?>', <?= $group['team_count'] ?>)"
                                    class="btn-standard btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($groups)): ?>
            <div class="empty-state fade-in">
                <i class="fas fa-layer-group"></i>
                <h3>Nenhum Grupo Criado</h3>
                <p>Crie grupos para organizar os times do torneio.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function editGroup(groupId, groupName) {
            // Criar um modal simples para edição
            const newName = prompt('Novo nome do grupo:', groupName);
            if (newName && newName.trim() !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_group">
                    <input type="hidden" name="group_id" value="${groupId}">
                    <input type="hidden" name="group_name" value="${newName.trim()}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteGroup(groupId, groupName, teamCount) {
            if (teamCount > 0) {
                alert('Não é possível excluir um grupo que possui times. Remova os times primeiro.');
                return;
            }

            if (confirm(`Tem certeza que deseja excluir o grupo "${groupName}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_group">
                    <input type="hidden" name="group_id" value="${groupId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 200);
            });

            // Adicionar efeitos hover dinâmicos aos group-cards
            const groupCards = document.querySelectorAll('.group-card');
            groupCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Adicionar efeitos hover dinâmicos aos form-sections
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach(section => {
                section.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 10px 25px rgba(123, 31, 162, 0.2)';
                });

                section.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>
