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

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

try {
    $pdo = conectar();

    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('INFO', 'SUCCESS', 'WARNING', 'ERROR') NOT NULL,
            message TEXT NOT NULL,
            context TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            user_id INT NULL,
            username VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level (level),
            INDEX idx_created_at (created_at),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Processar ações POST
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'clear_logs':
                // Limpar logs
                $result = $pdo->exec("DELETE FROM system_logs");
                $_SESSION['success'] = "Logs limpos com sucesso! ($result registros removidos)";
                break;

            case 'populate_sample_logs':
                // Popular com logs de exemplo
                $sample_logs = [
                    ['INFO', 'Sistema iniciado com sucesso', 'system'],
                    ['SUCCESS', 'Usuário admin fez login no sistema', 'admin'],
                    ['SUCCESS', 'Torneio "Copa das Panelas 2024" criado com sucesso', 'admin'],
                    ['WARNING', 'Tentativa de acesso negada para usuário não autorizado', 'unknown'],
                    ['ERROR', 'Erro na conexão com banco de dados - timeout', 'system'],
                    ['INFO', 'Backup automático do banco de dados executado', 'system'],
                    ['SUCCESS', 'Otimização do banco de dados concluída', 'admin'],
                    ['WARNING', 'Uso de memória acima de 80%', 'system'],
                    ['INFO', 'Cache do sistema limpo', 'admin'],
                    ['ERROR', 'Falha ao enviar email de notificação', 'system'],
                    ['SUCCESS', 'Time "Flamengo" criado com sucesso', 'admin'],
                    ['INFO', 'Resultado do jogo atualizado: Flamengo vs Vasco (2x1)', 'admin'],
                    ['WARNING', 'Limite de armazenamento próximo do máximo', 'system'],
                    ['SUCCESS', 'Importação de dados concluída', 'admin'],
                    ['ERROR', 'Falha ao conectar com servidor de email', 'system'],
                ];

                $stmt = $pdo->prepare("
                    INSERT INTO system_logs (level, message, ip_address, user_agent, username, created_at)
                    VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE))
                ");

                $inserted = 0;
                foreach ($sample_logs as $index => $log) {
                    $minutes_ago = rand(1, 1440); // Entre 1 minuto e 24 horas atrás
                    $result = $stmt->execute([
                        $log[0],
                        $log[1],
                        '192.168.1.' . rand(100, 200),
                        'Mozilla/5.0 (System Log Generator)',
                        $log[2],
                        $minutes_ago
                    ]);
                    if ($result) $inserted++;
                }

                $_SESSION['success'] = "$inserted logs de exemplo adicionados com sucesso!";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro: " . $e->getMessage();
    }

    header('Location: system_logs.php');
    exit;
}

// Configurações de paginação e filtros
$level_filter = $_GET['level'] ?? '';
$date_filter = $_GET['date'] ?? '';
$limit = (int)($_GET['limit'] ?? 50);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($level_filter) {
    $where_conditions[] = "level = ?";
    $params[] = $level_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Buscar logs
try {
    // Contar total
    $count_sql = "SELECT COUNT(*) FROM system_logs $where_clause";
    if (!empty($params)) {
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_logs = $count_stmt->fetchColumn();
    } else {
        $total_logs = $pdo->query($count_sql)->fetchColumn();
    }

    // Buscar logs com paginação
    $sql = "SELECT * FROM system_logs $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $logs = [];
    $total_logs = 0;
    error_log("Erro ao buscar logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - Copa das Panelas</title>
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

        .filters {
            background: #1E1E1E;
            border-left: 4px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .filters::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-weight: 600;
            color: #9E9E9E;
            font-size: 0.95rem;
        }

        .filter-input {
            padding: 12px;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: #7B1FA2;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .logs-container {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .logs-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .logs-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logs-title i {
            color: #7B1FA2;
        }

        .logs-count {
            background: #2A2A2A;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #9E9E9E;
        }

        .log-entry {
            background: #2A2A2A;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .log-entry:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .log-entry.level-INFO {
            border-left-color: #2196F3;
        }

        .log-entry.level-SUCCESS {
            border-left-color: #4CAF50;
        }

        .log-entry.level-WARNING {
            border-left-color: #FF9800;
        }

        .log-entry.level-ERROR {
            border-left-color: #F44336;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .log-level {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .log-level.INFO {
            background: #2196F3;
            color: white;
        }

        .log-level.SUCCESS {
            background: #4CAF50;
            color: white;
        }

        .log-level.WARNING {
            background: #FF9800;
            color: white;
        }

        .log-level.ERROR {
            background: #F44336;
            color: white;
        }

        .log-timestamp {
            color: #9E9E9E;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .log-message {
            color: #E0E0E0;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .log-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .log-detail {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .log-detail-label {
            font-size: 0.8rem;
            color: #9E9E9E;
            text-transform: uppercase;
            font-weight: 600;
        }

        .log-detail-value {
            font-size: 0.9rem;
            color: #E0E0E0;
            word-break: break-all;
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

        .no-logs {
            text-align: center;
            padding: 60px 20px;
            color: #9E9E9E;
        }

        .no-logs i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #7B1FA2;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .logs-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .log-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .log-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-file-text"></i> Logs do Sistema</h1>
                <p style="color: #9E9E9E; font-size: 1.1rem; margin-top: 8px;">Monitore atividades e eventos do sistema</p>
            </div>
            <div>
                <a href="system_settings.php" class="btn-standard">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <form method="POST" action="system_logs.php" style="display: inline;">
                    <input type="hidden" name="action" value="populate_sample_logs">
                    <button type="submit" class="btn-standard">
                        <i class="fas fa-plus"></i> Adicionar Logs de Exemplo
                    </button>
                </form>
                <form method="POST" action="system_logs.php" style="display: inline;">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn-standard btn-danger">
                        <i class="fas fa-trash"></i> Limpar Logs
                    </button>
                </form>
            </div>
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

        <!-- Filtros -->
        <div class="filters fade-in">
            <form method="GET" class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Nível do Log</label>
                    <select name="level" class="filter-input">
                        <option value="">Todos os níveis</option>
                        <option value="INFO" <?= $level_filter === 'INFO' ? 'selected' : '' ?>>INFO</option>
                        <option value="SUCCESS" <?= $level_filter === 'SUCCESS' ? 'selected' : '' ?>>SUCCESS</option>
                        <option value="WARNING" <?= $level_filter === 'WARNING' ? 'selected' : '' ?>>WARNING</option>
                        <option value="ERROR" <?= $level_filter === 'ERROR' ? 'selected' : '' ?>>ERROR</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Data</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>" class="filter-input">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Logs por página</label>
                    <select name="limit" class="filter-input">
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                        <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200</option>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn-standard">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="system_logs.php" class="btn-standard">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </div>

        <!-- Container de Logs -->
        <div class="logs-container fade-in">
            <div class="logs-header">
                <h2 class="logs-title">
                    <i class="fas fa-list"></i>
                    Entradas de Log
                </h2>
                <div class="logs-count">
                    <?= count($logs) ?> de <?= $total_logs ?> entradas
                    <?php if ($total_logs > $limit): ?>
                        (Página <?= $page ?> de <?= ceil($total_logs / $limit) ?>)
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($logs)): ?>
                <div class="no-logs">
                    <i class="fas fa-inbox"></i>
                    <h3>Nenhum log encontrado</h3>
                    <p>Não há logs que correspondam aos filtros selecionados.</p>
                    <?php if (empty($where_conditions)): ?>
                        <p>O sistema de logs foi inicializado. Adicione logs de exemplo ou use o sistema para gerar logs automaticamente.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($logs as $index => $log): ?>
                    <?php
                    $formatted_date = date('d/m/Y H:i:s', strtotime($log['created_at']));
                    ?>
                    <div class="log-entry level-<?= $log['level'] ?> fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                        <div class="log-header">
                            <span class="log-level <?= $log['level'] ?>"><?= $log['level'] ?></span>
                            <span class="log-timestamp">
                                <i class="fas fa-clock"></i> <?= $formatted_date ?>
                            </span>
                        </div>

                        <div class="log-message">
                            <?= htmlspecialchars($log['message']) ?>
                        </div>

                        <div class="log-details">
                            <div class="log-detail">
                                <span class="log-detail-label">IP Address</span>
                                <span class="log-detail-value"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></span>
                            </div>
                            <div class="log-detail">
                                <span class="log-detail-label">Usuário</span>
                                <span class="log-detail-value"><?= htmlspecialchars($log['username'] ?? 'Sistema') ?></span>
                            </div>
                            <?php if (!empty($log['context'])): ?>
                            <div class="log-detail">
                                <span class="log-detail-label">Contexto</span>
                                <span class="log-detail-value"><?= htmlspecialchars($log['context']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="log-detail">
                                <span class="log-detail-label">User Agent</span>
                                <span class="log-detail-value"><?= htmlspecialchars(substr($log['user_agent'] ?? 'N/A', 0, 100)) ?><?= strlen($log['user_agent'] ?? '') > 100 ? '...' : '' ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Paginação -->
                <?php if ($total_logs > $limit): ?>
                    <div style="margin-top: 30px; text-align: center;">
                        <?php
                        $total_pages = ceil($total_logs / $limit);
                        $query_params = $_GET;
                        ?>

                        <?php if ($page > 1): ?>
                            <?php
                            $query_params['page'] = $page - 1;
                            $prev_url = '?' . http_build_query($query_params);
                            ?>
                            <a href="<?= $prev_url ?>" class="btn-standard">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>

                        <span style="margin: 0 20px; color: #9E9E9E;">
                            Página <?= $page ?> de <?= $total_pages ?>
                        </span>

                        <?php if ($page < $total_pages): ?>
                            <?php
                            $query_params['page'] = $page + 1;
                            $next_url = '?' . http_build_query($query_params);
                            ?>
                            <a href="<?= $next_url ?>" class="btn-standard">
                                Próxima <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
                }, index * 150);
            });

            // Efeitos de hover nos logs
            const logEntries = document.querySelectorAll('.log-entry');
            logEntries.forEach(entry => {
                entry.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(10px)';
                    this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.3)';
                });

                entry.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.2)';
                });
            });

            // Auto-refresh dos logs a cada 30 segundos (opcional)
            let autoRefresh = false;
            if (autoRefresh) {
                setInterval(() => {
                    if (!document.hidden) {
                        location.reload();
                    }
                }, 30000);
            }

            // Animação simples nos botões (sem interferir no submit)
            const actionButtons = document.querySelectorAll('button[type="submit"]');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Apenas mostrar feedback visual sem interferir no submit
                    this.style.opacity = '0.7';
                    this.style.transform = 'scale(0.98)';

                    // Restaurar visual após um tempo
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.transform = 'scale(1)';
                    }, 200);
                });
            });

            // Destacar logs por nível
            const levelCounts = {
                'INFO': 0,
                'SUCCESS': 0,
                'WARNING': 0,
                'ERROR': 0
            };

            logEntries.forEach(entry => {
                const level = entry.className.match(/level-(\w+)/)?.[1];
                if (level && levelCounts.hasOwnProperty(level)) {
                    levelCounts[level]++;
                }
            });

            // Adicionar indicadores visuais baseados na quantidade de cada tipo
            if (levelCounts.ERROR > 0) {
                console.warn(`⚠️ ${levelCounts.ERROR} erro(s) encontrado(s) nos logs`);
            }
            if (levelCounts.WARNING > 0) {
                console.info(`⚡ ${levelCounts.WARNING} aviso(s) encontrado(s) nos logs`);
            }

            // Scroll suave para logs específicos (se houver hash na URL)
            if (window.location.hash) {
                const targetLog = document.querySelector(window.location.hash);
                if (targetLog) {
                    setTimeout(() => {
                        targetLog.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        targetLog.style.background = 'rgba(123, 31, 162, 0.2)';
                        setTimeout(() => {
                            targetLog.style.background = '';
                        }, 2000);
                    }, 1000);
                }
            }
        });

        // Função para exportar logs (futura implementação)
        function exportLogs() {
            // Implementar exportação de logs em CSV ou JSON
            console.log('Exportar logs - funcionalidade a ser implementada');
        }

        // Função para busca em tempo real nos logs
        function searchLogs(query) {
            const logEntries = document.querySelectorAll('.log-entry');
            logEntries.forEach(entry => {
                const message = entry.querySelector('.log-message').textContent.toLowerCase();
                const details = entry.querySelector('.log-details').textContent.toLowerCase();

                if (message.includes(query.toLowerCase()) || details.includes(query.toLowerCase())) {
                    entry.style.display = 'block';
                } else {
                    entry.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
