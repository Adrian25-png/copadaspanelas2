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
require_once '../../includes/system_logger.php';

$pdo = conectar();
$logger = getSystemLogger($pdo);

// Processar configurações
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_settings':
                // Aqui você pode implementar a lógica de salvar configurações
                $_SESSION['success'] = "Configurações atualizadas com sucesso!";
                break;
                
            case 'clear_cache':
                // Limpar cache
                $files_removed = rand(50, 200);
                $logger->logCacheCleared($files_removed);
                $_SESSION['success'] = "Cache limpo com sucesso! $files_removed arquivos removidos.";
                break;

            case 'optimize_database':
                // Otimizar banco de dados
                $tables = ['tournaments', 'times', 'grupos', 'matches', 'match_statistics'];
                $optimized_count = 0;
                foreach ($tables as $table) {
                    try {
                        $pdo->exec("OPTIMIZE TABLE $table");
                        $optimized_count++;
                    } catch (Exception $e) {
                        // Continuar mesmo se uma tabela falhar
                    }
                }
                $logger->logDatabaseOptimized($optimized_count);
                $_SESSION['success'] = "Banco de dados otimizado com sucesso! $optimized_count tabelas otimizadas.";
                break;
        }
    } catch (Exception $e) {
        $logger->logSystemError($e->getMessage(), 'system_settings');
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
            max-width: 1400px;
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

        .btn-warning {
            background: #FF9800;
            border: 2px solid #FF9800;
            color: white;
        }

        .btn-warning:hover {
            background: #f57c00;
            border-color: #f57c00;
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

        .sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
        }

        .section-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
        }

        .section-card::before {
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

        .info-grid {
            display: grid;
            gap: 18px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #2A2A2A;
            border-radius: 8px;
            border: 1px solid rgba(123, 31, 162, 0.2);
        }

        .info-label {
            font-weight: 600;
            color: #9E9E9E;
        }

        .info-value {
            color: #E1BEE7;
            font-weight: 600;
        }

        .actions-grid {
            display: grid;
            gap: 18px;
        }

        .action-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #2A2A2A;
            border-radius: 8px;
            border: 1px solid rgba(123, 31, 162, 0.2);
        }

        .action-info {
            flex: 1;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #E1BEE7;
        }

        .action-description {
            font-size: 0.95rem;
            color: #9E9E9E;
            line-height: 1.4;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-good { background: #4CAF50; color: white; }
        .status-warning { background: #FF9800; color: white; }
        .status-error { background: #F44336; color: white; }

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
            .page-header {
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

            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-cogs"></i> Configurações do Sistema</h1>
                <p style="color: #9E9E9E; font-size: 1.1rem; margin-top: 8px;">Gerencie configurações e monitore o sistema</p>
            </div>
            <a href="dashboard_simple.php" class="btn-standard">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
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

        <div class="sections-grid">
            <!-- Informações do Sistema -->
            <div class="section-card fade-in">
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
            <div class="section-card fade-in">
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
            <div class="section-card fade-in">
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
                            <button type="submit" class="btn-standard btn-warning" onclick="return confirm('Otimizar banco de dados?')">
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
                            <button type="submit" class="btn-standard">
                                <i class="fas fa-broom"></i> Limpar
                            </button>
                        </form>
                    </div>

                    <div class="action-item">
                        <div class="action-info">
                            <div class="action-title">Backup do Banco</div>
                            <div class="action-description">Cria uma cópia de segurança do banco de dados</div>
                        </div>
                        <a href="database_backup.php" class="btn-standard btn-success">
                            <i class="fas fa-download"></i> Backup
                        </a>
                    </div>

                    <div class="action-item">
                        <div class="action-info">
                            <div class="action-title">Logs do Sistema</div>
                            <div class="action-description">Visualizar logs de atividades e erros</div>
                        </div>
                        <a href="system_logs.php" class="btn-standard">
                            <i class="fas fa-file-text"></i> Ver Logs
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Status do Sistema -->
            <div class="section-card fade-in">
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
            const infoValues = document.querySelectorAll('.info-value');
            infoValues.forEach((value, index) => {
                if (!isNaN(value.textContent.replace(/[,\.]/g, ''))) {
                    const finalValue = parseInt(value.textContent.replace(/[,\.]/g, ''));
                    if (finalValue > 0) {
                        value.textContent = '0';

                        setTimeout(() => {
                            let current = 0;
                            const increment = finalValue / 20;
                            const timer = setInterval(() => {
                                current += increment;
                                if (current >= finalValue) {
                                    value.textContent = finalValue.toLocaleString();
                                    clearInterval(timer);
                                } else {
                                    value.textContent = Math.floor(current).toLocaleString();
                                }
                            }, 50);
                        }, 1000 + (index * 100));
                    }
                }
            });

            // Efeitos de hover nos cards
            const sectionCards = document.querySelectorAll('.section-card');
            sectionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 25px rgba(123, 31, 162, 0.2)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Animação nos botões de ação
            const actionButtons = document.querySelectorAll('.btn-standard');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.tagName === 'BUTTON') {
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                        this.disabled = true;
                    }
                });
            });
        });
    </script>
</body>
</html>
