<?php
session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Função para verificar status do banco de dados
function checkDatabaseStatus($pdo) {
    try {
        $stmt = $pdo->query("SELECT 1");
        return [
            'status' => 'online',
            'message' => 'Conexão ativa',
            'response_time' => '< 1ms'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'offline',
            'message' => 'Erro: ' . $e->getMessage(),
            'response_time' => 'N/A'
        ];
    }
}

// Função para verificar espaço em disco
function checkDiskSpace() {
    $bytes = disk_free_space(".");
    $total = disk_total_space(".");
    $used = $total - $bytes;
    $percent_used = round(($used / $total) * 100, 2);
    
    return [
        'free' => formatBytes($bytes),
        'total' => formatBytes($total),
        'used' => formatBytes($used),
        'percent_used' => $percent_used,
        'status' => $percent_used > 90 ? 'critical' : ($percent_used > 75 ? 'warning' : 'good')
    ];
}

// Função para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Função para verificar versão do PHP
function checkPHPVersion() {
    $version = phpversion();
    $status = version_compare($version, '7.4.0', '>=') ? 'good' : 'warning';
    
    return [
        'version' => $version,
        'status' => $status,
        'message' => $status === 'good' ? 'Versão adequada' : 'Versão antiga'
    ];
}

// Função para verificar extensões PHP
function checkPHPExtensions() {
    $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
    $extensions = [];
    
    foreach ($required as $ext) {
        $extensions[$ext] = [
            'loaded' => extension_loaded($ext),
            'status' => extension_loaded($ext) ? 'good' : 'critical'
        ];
    }
    
    return $extensions;
}

// Obter informações do sistema
$db_status = checkDatabaseStatus($pdo);
$disk_info = checkDiskSpace();
$php_info = checkPHPVersion();
$extensions = checkPHPExtensions();

// Estatísticas do banco
try {
    // Contar registros principais
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM tournaments");
    $stats['tournaments'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM times");
    $stats['teams'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM matches");
    $stats['matches'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM grupos");
    $stats['groups'] = $stmt->fetchColumn();
    
    // Tamanho do banco
    $stmt = $pdo->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = 'copa'
    ");
    $stats['db_size'] = $stmt->fetchColumn() . ' MB';
    
} catch (Exception $e) {
    $stats = [
        'tournaments' => 'N/A',
        'teams' => 'N/A',
        'matches' => 'N/A',
        'groups' => 'N/A',
        'db_size' => 'N/A'
    ];
}

// Informações do servidor
$server_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'server_time' => date('d/m/Y H:i:s'),
    'timezone' => date_default_timezone_get(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . 's',
    'upload_max_filesize' => ini_get('upload_max_filesize')
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Sistema - Copa das Panelas</title>
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
            max-width: 1400px;
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
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .status-title {
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-good { background: #27ae60; }
        .status-warning { background: #f39c12; }
        .status-critical { background: #e74c3c; }
        .status-offline { background: #95a5a6; }
        
        .status-details {
            display: grid;
            gap: 10px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .detail-label {
            font-weight: 600;
        }
        
        .detail-value {
            color: #3498db;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .extensions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .extension-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-good { background: #27ae60; }
        .progress-warning { background: #f39c12; }
        .progress-critical { background: #e74c3c; }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .status-grid {
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
                <h1><i class="fas fa-heartbeat"></i> Status do Sistema</h1>
                <p style="margin: 5px 0; opacity: 0.8;">Monitoramento em tempo real do sistema</p>
            </div>
            <div>
                <button onclick="window.location.reload()" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Status Cards -->
        <div class="status-grid">
            <!-- Banco de Dados -->
            <div class="status-card">
                <div class="status-header">
                    <div class="status-title">
                        <i class="fas fa-database"></i>
                        Banco de Dados
                    </div>
                    <span class="status-indicator status-<?= $db_status['status'] === 'online' ? 'good' : 'critical' ?>"></span>
                </div>
                <div class="status-details">
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><?= ucfirst($db_status['status']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Mensagem:</span>
                        <span class="detail-value"><?= $db_status['message'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tempo de Resposta:</span>
                        <span class="detail-value"><?= $db_status['response_time'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tamanho:</span>
                        <span class="detail-value"><?= $stats['db_size'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Espaço em Disco -->
            <div class="status-card">
                <div class="status-header">
                    <div class="status-title">
                        <i class="fas fa-hdd"></i>
                        Espaço em Disco
                    </div>
                    <span class="status-indicator status-<?= $disk_info['status'] ?>"></span>
                </div>
                <div class="status-details">
                    <div class="detail-item">
                        <span class="detail-label">Total:</span>
                        <span class="detail-value"><?= $disk_info['total'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Usado:</span>
                        <span class="detail-value"><?= $disk_info['used'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Livre:</span>
                        <span class="detail-value"><?= $disk_info['free'] ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill progress-<?= $disk_info['status'] ?>" 
                             style="width: <?= $disk_info['percent_used'] ?>%"></div>
                    </div>
                    <div style="text-align: center; font-size: 0.9rem;">
                        <?= $disk_info['percent_used'] ?>% usado
                    </div>
                </div>
            </div>
            
            <!-- PHP -->
            <div class="status-card">
                <div class="status-header">
                    <div class="status-title">
                        <i class="fab fa-php"></i>
                        PHP
                    </div>
                    <span class="status-indicator status-<?= $php_info['status'] ?>"></span>
                </div>
                <div class="status-details">
                    <div class="detail-item">
                        <span class="detail-label">Versão:</span>
                        <span class="detail-value"><?= $php_info['version'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><?= $php_info['message'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Limite de Memória:</span>
                        <span class="detail-value"><?= $server_info['memory_limit'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tempo Máximo:</span>
                        <span class="detail-value"><?= $server_info['max_execution_time'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Extensões PHP -->
            <div class="status-card">
                <div class="status-header">
                    <div class="status-title">
                        <i class="fas fa-puzzle-piece"></i>
                        Extensões PHP
                    </div>
                </div>
                <div class="extensions-grid">
                    <?php foreach ($extensions as $name => $info): ?>
                        <div class="extension-item">
                            <span class="status-indicator status-<?= $info['status'] ?>"></span>
                            <span><?= strtoupper($name) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas do Banco -->
        <div class="status-card" style="margin-bottom: 30px;">
            <div class="status-header">
                <div class="status-title">
                    <i class="fas fa-chart-bar"></i>
                    Estatísticas do Banco de Dados
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['tournaments'] ?></div>
                    <div class="stat-label">Torneios</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['teams'] ?></div>
                    <div class="stat-label">Times</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['matches'] ?></div>
                    <div class="stat-label">Jogos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['groups'] ?></div>
                    <div class="stat-label">Grupos</div>
                </div>
            </div>
        </div>
        
        <!-- Informações do Servidor -->
        <div class="status-card">
            <div class="status-header">
                <div class="status-title">
                    <i class="fas fa-server"></i>
                    Informações do Servidor
                </div>
            </div>
            <div class="status-details">
                <div class="detail-item">
                    <span class="detail-label">Software:</span>
                    <span class="detail-value"><?= $server_info['server_software'] ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Horário do Servidor:</span>
                    <span class="detail-value"><?= $server_info['server_time'] ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fuso Horário:</span>
                    <span class="detail-value"><?= $server_info['timezone'] ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Upload Máximo:</span>
                    <span class="detail-value"><?= $server_info['upload_max_filesize'] ?></span>
                </div>
            </div>
        </div>
        
        <!-- Rodapé -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2); opacity: 0.7;">
            <p>Status atualizado em <?= date('d/m/Y H:i:s') ?></p>
            <p>Sistema Copa das Panelas - Monitoramento</p>
        </div>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.status-card, .stat-card');
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
        
        // Auto-refresh a cada 30 segundos
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
