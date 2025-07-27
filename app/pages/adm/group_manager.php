<?php
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
            font-size: 2rem;
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
        
        .create-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .form-input {
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .group-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .group-card:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .group-name {
            font-size: 1.4rem;
            font-weight: bold;
            color: #f39c12;
            margin-bottom: 5px;
        }
        
        .group-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .teams-list {
            margin: 15px 0;
        }
        
        .team-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .group-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .groups-grid {
                grid-template-columns: 1fr;
            }
            
            .group-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-layer-group"></i> Gerenciar Grupos</h1>
                <p style="margin: 5px 0; opacity: 0.8;"><?= htmlspecialchars($tournament['name']) ?></p>
            </div>
            <a href="tournament_management.php?id=<?= $tournament_id ?>" class="btn btn-secondary">
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
            <div class="create-section">
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
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-magic"></i> Criar Grupos
                            </button>
                        </div>
                    </div>
                </form>
                
                <p style="margin-top: 15px; opacity: 0.7; font-size: 0.9rem;">
                    Os grupos serão criados automaticamente como: Grupo A, Grupo B, Grupo C, etc.
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Adicionar Grupo Individual -->
        <div class="create-section">
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Lista de Grupos -->
        <?php if (!empty($groups)): ?>
            <div class="groups-grid">
                <?php foreach ($groups as $group): ?>
                    <div class="group-card">
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
                                <strong>Times:</strong>
                                <?php foreach ($teams_by_group[$group['id']] as $team): ?>
                                    <div class="team-item">
                                        <i class="fas fa-shield-alt"></i>
                                        <?= htmlspecialchars($team['nome']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="opacity: 0.7; font-style: italic;">
                                Nenhum time neste grupo
                            </div>
                        <?php endif; ?>
                        
                        <div class="group-actions">
                            <button onclick="editGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['nome']) ?>')" 
                                    class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            
                            <button onclick="deleteGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['nome']) ?>', <?= $group['team_count'] ?>)" 
                                    class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($groups)): ?>
            <div class="empty-state">
                <i class="fas fa-layer-group"></i>
                <h3>Nenhum Grupo Criado</h3>
                <p>Crie grupos para organizar os times do torneio.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function editGroup(groupId, groupName) {
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
                alert(`Não é possível excluir o grupo "${groupName}" pois ele possui ${teamCount} time(s).`);
                return;
            }
            
            if (confirm(`Tem certeza que deseja excluir o grupo "${groupName}"?\n\nEsta ação não pode ser desfeita.`)) {
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
            const cards = document.querySelectorAll('.group-card');
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
