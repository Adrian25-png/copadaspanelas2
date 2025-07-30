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

$pdo = conectar();
$tournament_id = $_GET['id'] ?? null;
$created = $_GET['created'] ?? false;

if (!$tournament_id) {
    header("Location: tournament_list.php");
    exit;
}

// Buscar dados do torneio
try {
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->execute([$tournament_id]);
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erro ao buscar torneio: " . $e->getMessage();
    $tournament = null;
}

if (!$tournament) {
    header("Location: tournament_list.php");
    exit;
}

// Garantir que as colunas existam com valores padrão
$tournament['teams_count'] = $tournament['teams_count'] ?? 8;
$tournament['groups_count'] = $tournament['groups_count'] ?? 2;
$tournament['format_type'] = $tournament['format_type'] ?? 'Grupos + Eliminatórias';
$tournament['start_date'] = $tournament['start_date'] ?? '';
$tournament['end_date'] = $tournament['end_date'] ?? '';

// Processar atualização
if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE tournaments 
            SET name = ?, description = ?, start_date = ?, end_date = ?, status = 'active'
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $start_date, $end_date, $tournament_id]);
        
        $success = "Torneio atualizado com sucesso!";
        
        // Atualizar dados locais
        $tournament['name'] = $name;
        $tournament['description'] = $description;
        $tournament['start_date'] = $start_date;
        $tournament['end_date'] = $end_date;
        $tournament['status'] = 'active';
        
    } catch (Exception $e) {
        $error = "Erro ao atualizar torneio: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Torneio - Copa das Panelas</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .success-banner {
            background: rgba(39, 174, 96, 0.2);
            border: 2px solid #27ae60;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .success-banner i {
            font-size: 3rem;
            color: #27ae60;
            margin-bottom: 15px;
        }
        
        .success-banner h3 {
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        
        .tournament-info {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .tournament-info h3 {
            color: #3498db;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-label {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .info-value {
            color: #3498db;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ecf0f1;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .btn-full {
            width: 100%;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #27ae60;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Editar Torneio</h1>
            <p>Configure os detalhes finais do seu torneio</p>
        </div>
        
        <?php if ($created): ?>
            <div class="success-banner">
                <i class="fas fa-check-circle"></i>
                <h3>Torneio Criado com Sucesso!</h3>
                <p>Agora você pode editar os detalhes e confirmar as configurações.</p>
            </div>
        <?php endif; ?>
        
        <div class="tournament-info">
            <h3><i class="fas fa-info-circle"></i> Configurações do Template</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Times:</span>
                    <span class="info-value"><?= $tournament['teams_count'] ?> times</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Grupos:</span>
                    <span class="info-value"><?= $tournament['groups_count'] ?> grupos</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Formato:</span>
                    <span class="info-value"><?= htmlspecialchars($tournament['format_type']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?= ucfirst($tournament['status']) ?></span>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-trophy"></i> Nome do Torneio
                    </label>
                    <input type="text" id="name" name="name" 
                           value="<?= htmlspecialchars($tournament['name']) ?>"
                           placeholder="Digite o nome do torneio" required>
                </div>
                
                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-align-left"></i> Descrição
                    </label>
                    <textarea id="description" name="description" 
                              placeholder="Descreva o torneio..."><?= htmlspecialchars($tournament['description']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">
                            <i class="fas fa-calendar-alt"></i> Data de Início
                        </label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?= $tournament['start_date'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">
                            <i class="fas fa-calendar-check"></i> Data de Término
                        </label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= $tournament['end_date'] ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-full">
                    <i class="fas fa-save"></i> Salvar e Ativar Torneio
                </button>
            </form>
            
            <div class="actions">
                <a href="team_manager.php?tournament_id=<?= $tournament_id ?>" class="btn btn-primary">
                    <i class="fas fa-users"></i> Gerenciar Times
                </a>
                <a href="tournament_list.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Lista de Torneios
                </a>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
