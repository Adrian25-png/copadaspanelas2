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
            padding: 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-title .icon {
            font-size: 3rem;
            color: rgba(255,255,255,0.9);
        }

        .header-subtitle {
            margin-top: 10px;
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Space Grotesk', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .btn:active {
            transform: translateY(0);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .status-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #d946ef);
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .status-title {
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #E0E0E0;
        }

        .status-title i {
            color: #6366f1;
            font-size: 1.2rem;
        }
        
        .status-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .status-good {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
        }
        .status-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.5);
        }
        .status-critical {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
        }
        .status-offline {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            box-shadow: 0 0 15px rgba(107, 114, 128, 0.5);
        }

        .status-details {
            display: grid;
            gap: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .detail-label {
            font-weight: 600;
            color: rgba(255,255,255,0.9);
        }

        .detail-value {
            color: #6366f1;
            font-weight: 700;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        
        .extensions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .extension-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .extension-item:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .extension-item span {
            font-weight: 500;
            color: #E0E0E0;
        }

        .progress-bar {
            width: 100%;
            height: 25px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-fill {
            height: 100%;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-good {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .progress-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .progress-critical {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 30px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer p {
            margin-bottom: 10px;
            color: rgba(255,255,255,0.7);
        }

        .footer p:last-child {
            margin-bottom: 0;
            font-weight: 600;
            color: #6366f1;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .header-actions {
                justify-content: center;
            }

            .status-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-container {
                padding: 15px;
            }

            .status-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-heartbeat icon"></i>
                    <div>
                        <h1>Status do Sistema</h1>
                        <div class="header-subtitle">Monitoramento em tempo real do sistema</div>
                    </div>
                </div>
                <div class="header-actions">
                    <button onclick="window.location.reload()" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                    <a href="dashboard_simple.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
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
        <div class="footer">
            <p>Status atualizado em <?= date('d/m/Y H:i:s') ?></p>
            <p>Sistema Copa das Panelas - Monitoramento</p>
        </div>
    </div>

    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animação do header
            const header = document.querySelector('.page-header');
            if (header) {
                header.style.opacity = '0';
                header.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    header.style.transition = 'all 0.8s ease';
                    header.style.opacity = '1';
                    header.style.transform = 'translateY(0)';
                }, 100);
            }

            // Animação dos cards de status
            const statusCards = document.querySelectorAll('.status-card');
            statusCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 300 + (index * 150));
            });

            // Animação dos cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 800 + (index * 100));
            });

            // Animação dos itens de detalhes
            const detailItems = document.querySelectorAll('.detail-item');
            detailItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 1000 + (index * 50));
            });

            // Animação das extensões
            const extensionItems = document.querySelectorAll('.extension-item');
            extensionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1)';
                }, 1200 + (index * 80));
            });

            // Animação das barras de progresso
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 1500 + (index * 200));
            });

            // Animação do footer
            const footer = document.querySelector('.footer');
            if (footer) {
                footer.style.opacity = '0';
                footer.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    footer.style.transition = 'all 0.6s ease';
                    footer.style.opacity = '1';
                    footer.style.transform = 'translateY(0)';
                }, 2000);
            }
        });

        // Indicador visual de atualização
        function showRefreshIndicator() {
            const refreshBtn = document.querySelector('.btn-primary');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
                refreshBtn.disabled = true;
            }
        }

        // Auto-refresh a cada 30 segundos com indicador
        let refreshTimer = setTimeout(() => {
            showRefreshIndicator();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }, 30000);

        // Contador regressivo visual (opcional)
        let countdown = 30;
        const updateCountdown = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(updateCountdown);
            }
        }, 1000);

        // Pausar auto-refresh quando a página não está visível
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearTimeout(refreshTimer);
            } else {
                refreshTimer = setTimeout(() => {
                    showRefreshIndicator();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }, 30000);
            }
        });
    </script>
</body>
</html>
