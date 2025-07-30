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
$tables_info = [];
$errors = [];

try {
    // Verificar tabelas principais
    $required_tables = ['tournaments', 'times', 'matches', 'jogadores'];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // Tabela existe, verificar estrutura
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Contar registros
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                
                $tables_info[$table] = [
                    'exists' => true,
                    'columns' => $columns,
                    'count' => $count
                ];
            } else {
                $tables_info[$table] = [
                    'exists' => false,
                    'columns' => [],
                    'count' => 0
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Erro ao verificar tabela $table: " . $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $errors[] = "Erro de conexão: " . $e->getMessage();
}

include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação do Banco - Copa das Panelas</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .check-card {
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
        
        .table-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .table-title {
            color: #3498db;
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-exists {
            color: #27ae60;
        }
        
        .table-missing {
            color: #e74c3c;
        }
        
        .columns-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .columns-table th,
        .columns-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .columns-table th {
            background: rgba(255, 255, 255, 0.2);
            color: #f39c12;
            font-weight: bold;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-exists { color: #27ae60; }
        .stat-missing { color: #e74c3c; }
        
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
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .error-section {
            background: rgba(231, 76, 60, 0.2);
            border: 2px solid #e74c3c;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="check-card">
            <h1 class="title">
                <i class="fas fa-database"></i>
                Verificação do Banco de Dados
            </h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-section">
                    <h3 style="color: #e74c3c; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erros Encontrados
                    </h3>
                    <?php foreach ($errors as $error): ?>
                        <p style="margin: 5px 0;">❌ <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="stat-grid">
                <?php foreach ($tables_info as $table_name => $info): ?>
                    <div class="stat-card">
                        <div class="stat-number <?= $info['exists'] ? 'stat-exists' : 'stat-missing' ?>">
                            <?= $info['exists'] ? '✅' : '❌' ?>
                        </div>
                        <div><strong><?= strtoupper($table_name) ?></strong></div>
                        <div><?= $info['exists'] ? $info['count'] . ' registros' : 'Não existe' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php foreach ($tables_info as $table_name => $info): ?>
                <div class="table-section">
                    <div class="table-title <?= $info['exists'] ? 'table-exists' : 'table-missing' ?>">
                        <i class="fas fa-table"></i>
                        Tabela: <?= strtoupper($table_name) ?>
                        <?= $info['exists'] ? '(EXISTE)' : '(NÃO EXISTE)' ?>
                    </div>
                    
                    <?php if ($info['exists']): ?>
                        <p><strong>Registros:</strong> <?= $info['count'] ?></p>
                        
                        <?php if (!empty($info['columns'])): ?>
                            <table class="columns-table">
                                <thead>
                                    <tr>
                                        <th>Campo</th>
                                        <th>Tipo</th>
                                        <th>Nulo</th>
                                        <th>Chave</th>
                                        <th>Padrão</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($info['columns'] as $column): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($column['Field']) ?></td>
                                            <td><?= htmlspecialchars($column['Type']) ?></td>
                                            <td><?= htmlspecialchars($column['Null']) ?></td>
                                            <td><?= htmlspecialchars($column['Key']) ?></td>
                                            <td><?= htmlspecialchars($column['Default'] ?? 'NULL') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #e74c3c;">Esta tabela precisa ser criada para o sistema funcionar corretamente.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div style="background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 15px; padding: 25px; text-align: center; margin: 30px 0;">
                <h3 style="color: #27ae60; margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i>
                    Status do Sistema
                </h3>
                <?php
                $existing_tables = array_filter($tables_info, function($info) { return $info['exists']; });
                $total_tables = count($tables_info);
                $existing_count = count($existing_tables);
                $percentage = $total_tables > 0 ? round(($existing_count / $total_tables) * 100) : 0;
                ?>
                <p><strong>Tabelas Existentes:</strong> <?= $existing_count ?>/<?= $total_tables ?> (<?= $percentage ?>%)</p>
                
                <?php if ($percentage === 100): ?>
                    <p style="color: #27ae60;">✅ Todas as tabelas principais estão presentes!</p>
                <?php elseif ($percentage >= 75): ?>
                    <p style="color: #f39c12;">⚠️ Maioria das tabelas existe, mas algumas estão faltando.</p>
                <?php else: ?>
                    <p style="color: #e74c3c;">❌ Muitas tabelas estão faltando. Execute o script de criação.</p>
                <?php endif; ?>
            </div>
            
            <div class="actions">
                <a href="create_tables.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    Criar Tabelas
                </a>
                <a href="select_tournament_for_team.php" class="btn">
                    <i class="fas fa-users"></i>
                    Cadastrar Time
                </a>
                <a href="dashboard_simple.php" class="btn btn-danger">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
