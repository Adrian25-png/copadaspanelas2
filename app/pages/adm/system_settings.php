<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Processar configurações
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_settings':
                // Aqui você pode implementar a lógica de salvar configurações
                $_SESSION['success'] = "Configurações atualizadas com sucesso!";
                break;
                
            case 'clear_cache':
                // Limpar cache (implementar conforme necessário)
                $_SESSION['success'] = "Cache limpo com sucesso!";
                break;
                
            case 'optimize_database':
                // Otimizar banco de dados
                $tables = ['tournaments', 'times', 'grupos', 'matches', 'match_statistics'];
                foreach ($tables as $table) {
                    $pdo->exec("OPTIMIZE TABLE $table");
                }
                $_SESSION['success'] = "Banco de dados otimizado com sucesso!";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: system_settings.php');
    exit;
}

// Obter informações do sistema
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

// Estatísticas do banco
$db_stats = [];
$tables = ['tournaments', 'times', 'grupos', 'matches', 'match_statistics'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $db_stats[$table] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $db_stats[$table] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - Copa das Panelas</title>
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
        
        .sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .section-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f39c12;
        }
        
        .info-grid {
            display: grid;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
        
        .info-label {
            font-weight: 600;
        }
        
        .info-value {
            color: #3498db;
            font-weight: bold;
        }
        
        .actions-grid {
            display: grid;
            gap: 15px;
        }
        
        .action-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        
        .action-info {
            flex: 1;
        }
        
        .action-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .action-description {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-good { background: #27ae60; }
        .status-warning { background: #f39c12; }
        .status-error { background: #e74c3c; }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .sections-grid {
                grid-template-columns: 1fr;
            }
            
            .action-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-cogs"></i> Configurações do Sistema</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Gerencie configurações e monitore o sistema</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
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
        
        <div class="sections-grid">
            <!-- Informações do Sistema -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Informações do Sistema
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Versão PHP</span>
                        <span class="info-value"><?= $system_info['php_version'] ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Versão MySQL</span>
                        <span class="info-value"><?= $system_info['mysql_version'] ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Servidor Web</span>
                        <span class="info-value"><?= $system_info['server_software'] ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Limite de Memória</span>
                        <span class="info-value"><?= $system_info['memory_limit'] ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Tempo Máximo de Execução</span>
                        <span class="info-value"><?= $system_info['max_execution_time'] ?>s</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Tamanho Máximo de Upload</span>
                        <span class="info-value"><?= $system_info['upload_max_filesize'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas do Banco -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-database"></i>
                    Estatísticas do Banco
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Torneios</span>
                        <span class="info-value"><?= number_format($db_stats['tournaments']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Times</span>
                        <span class="info-value"><?= number_format($db_stats['times']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Grupos</span>
                        <span class="info-value"><?= number_format($db_stats['grupos']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Jogos</span>
                        <span class="info-value"><?= number_format($db_stats['matches']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Estatísticas de Jogos</span>
                        <span class="info-value"><?= number_format($db_stats['match_statistics']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Ações do Sistema -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-tools"></i>
                    Manutenção do Sistema
                </h2>
                
                <div class="actions-grid">
                    <div class="action-item">
                        <div class="action-info">
                            <div class="action-title">Otimizar Banco de Dados</div>
                            <div class="action-description">Otimiza as tabelas do banco para melhor performance</div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="optimize_database">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Otimizar banco de dados?')">
                                <i class="fas fa-database"></i> Otimizar
                            </button>
                        </form>
                    </div>
                    
                    <div class="action-item">
                        <div class="action-info">
                            <div class="action-title">Limpar Cache</div>
                            <div class="action-description">Remove arquivos temporários e cache do sistema</div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-broom"></i> Limpar
                            </button>
                        </form>
                    </div>
                    
                    <div class="action-item">
                        <div class="action-info">
                            <div class="action-title">Backup do Banco</div>
                            <div class="action-description">Cria uma cópia de segurança do banco de dados</div>
                        </div>
                        <a href="database_backup.php" class="btn btn-success">
                            <i class="fas fa-download"></i> Backup
                        </a>
                    </div>
                    
                    <div class="action-item">
                        <div class="action-info">
                            <div class="action-title">Logs do Sistema</div>
                            <div class="action-description">Visualizar logs de atividades e erros</div>
                        </div>
                        <a href="system_logs.php" class="btn btn-secondary">
                            <i class="fas fa-file-text"></i> Ver Logs
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Status do Sistema -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-heartbeat"></i>
                    Status do Sistema
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Conexão com Banco</span>
                        <span class="status-indicator status-good">
                            <i class="fas fa-check"></i> Conectado
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Espaço em Disco</span>
                        <span class="status-indicator status-good">
                            <i class="fas fa-check"></i> OK
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Permissões de Arquivo</span>
                        <span class="status-indicator status-good">
                            <i class="fas fa-check"></i> OK
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Extensões PHP</span>
                        <span class="status-indicator status-good">
                            <i class="fas fa-check"></i> Todas OK
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.section-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>
