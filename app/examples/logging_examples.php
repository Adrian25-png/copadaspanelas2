<?php
/**
 * Exemplos de como usar o Sistema de Logs Copa das Panelas
 * 
 * Este arquivo demonstra como integrar o sistema de logs em diferentes partes do sistema
 */

require_once '../config/conexao.php';
require_once '../includes/system_logger.php';

// Conectar ao banco e criar instância do logger
$pdo = conectar();
$logger = getSystemLogger($pdo);

// ========================================
// EXEMPLOS DE USO DO SISTEMA DE LOGS
// ========================================

// 1. LOGS DE AUTENTICAÇÃO
// ========================================

// Login bem-sucedido
$logger->logLogin('admin', true, 1);

// Login falhado
$logger->logLogin('usuario_inexistente', false);

// Logout
$logger->logLogout('admin', 1);

// Tentativa de acesso negada
$logger->logAccessDenied('admin_panel.php', 'Usuário não autenticado');

// 2. LOGS DE TORNEIOS
// ========================================

// Criação de torneio
$logger->logTournamentCreated('Copa das Panelas 2024', 1, 'admin');

// Atualização de torneio
$logger->logTournamentUpdated('Copa das Panelas 2024', 1, 'admin');

// Exclusão de torneio
$logger->logTournamentDeleted('Copa das Panelas 2023', 2, 'admin');

// 3. LOGS DE TIMES
// ========================================

// Criação de time
$logger->logTeamCreated('Flamengo', 1, 1, 'admin');

// 4. LOGS DE JOGOS
// ========================================

// Atualização de resultado
$logger->logMatchResultUpdated(1, 'Flamengo', 'Vasco', '2x1', 'admin');

// 5. LOGS DE SISTEMA
// ========================================

// Backup
$logger->logBackup('backup_2024_01_15.sql', '2.5MB', true, 'admin');

// Falha no backup
$logger->logBackup('backup_failed.sql', null, false, 'admin');

// Limpeza de cache
$logger->logCacheCleared(156, 'admin');

// Otimização do banco
$logger->logDatabaseOptimized(8, 'admin');

// Erro de sistema
$logger->logSystemError('Conexão com banco perdida', 'database', [
    'error_code' => 'CONNECTION_LOST',
    'retry_count' => 3
]);

// Início do sistema
$logger->logSystemStart();

// Monitoramento de recursos
$logger->logResourceMonitoring('memory', '85%', '80%');

// 6. LOGS PERSONALIZADOS
// ========================================

// Log de informação
$logger->info('Usuário visualizou relatório de estatísticas', [
    'component' => 'reports',
    'report_type' => 'statistics',
    'user_id' => 1
], 1, 'admin');

// Log de sucesso
$logger->success('Importação de dados concluída', [
    'component' => 'import',
    'records_imported' => 150,
    'file_name' => 'teams.csv'
], 1, 'admin');

// Log de aviso
$logger->warning('Limite de armazenamento próximo do máximo', [
    'component' => 'storage',
    'current_usage' => '90%',
    'max_capacity' => '100GB'
]);

// Log de erro
$logger->error('Falha ao enviar email de notificação', [
    'component' => 'email',
    'recipient' => 'admin@copa.com',
    'error_message' => 'SMTP connection failed'
]);

// ========================================
// EXEMPLO DE INTEGRAÇÃO EM PÁGINAS
// ========================================

/**
 * Exemplo 1: Integração em página de criação de torneio
 */
function exemploCreateTournament() {
    global $pdo, $logger;
    
    if ($_POST && isset($_POST['tournament_name'])) {
        try {
            $tournament_name = $_POST['tournament_name'];
            
            // Inserir torneio no banco
            $stmt = $pdo->prepare("INSERT INTO tournaments (name, year) VALUES (?, ?)");
            $stmt->execute([$tournament_name, date('Y')]);
            $tournament_id = $pdo->lastInsertId();
            
            // Registrar log de sucesso
            $logger->logTournamentCreated($tournament_name, $tournament_id);
            
            return ['success' => true, 'message' => 'Torneio criado com sucesso!'];
            
        } catch (Exception $e) {
            // Registrar log de erro
            $logger->logSystemError('Erro ao criar torneio: ' . $e->getMessage(), 'tournament', [
                'tournament_name' => $tournament_name ?? 'N/A'
            ]);
            
            return ['success' => false, 'message' => 'Erro ao criar torneio'];
        }
    }
}

/**
 * Exemplo 2: Integração em página de atualização de resultado
 */
function exemploUpdateMatchResult() {
    global $pdo, $logger;
    
    if ($_POST && isset($_POST['match_id'], $_POST['team1_score'], $_POST['team2_score'])) {
        try {
            $match_id = $_POST['match_id'];
            $team1_score = $_POST['team1_score'];
            $team2_score = $_POST['team2_score'];
            
            // Buscar informações do jogo
            $stmt = $pdo->prepare("
                SELECT m.id, t1.nome as team1, t2.nome as team2 
                FROM matches m 
                JOIN times t1 ON m.team1_id = t1.id 
                JOIN times t2 ON m.team2_id = t2.id 
                WHERE m.id = ?
            ");
            $stmt->execute([$match_id]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($match) {
                // Atualizar resultado
                $stmt = $pdo->prepare("UPDATE matches SET team1_score = ?, team2_score = ? WHERE id = ?");
                $stmt->execute([$team1_score, $team2_score, $match_id]);
                
                // Registrar log
                $score = "$team1_score x $team2_score";
                $logger->logMatchResultUpdated($match_id, $match['team1'], $match['team2'], $score);
                
                return ['success' => true, 'message' => 'Resultado atualizado com sucesso!'];
            }
            
        } catch (Exception $e) {
            $logger->logSystemError('Erro ao atualizar resultado: ' . $e->getMessage(), 'match');
            return ['success' => false, 'message' => 'Erro ao atualizar resultado'];
        }
    }
}

/**
 * Exemplo 3: Middleware de autenticação com logs
 */
function exemploAuthMiddleware() {
    global $logger;
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        // Registrar tentativa de acesso negada
        $logger->logAccessDenied($_SERVER['REQUEST_URI'] ?? 'unknown', 'Usuário não autenticado');
        
        header('Location: login_simple.php');
        exit;
    }
}

/**
 * Exemplo 4: Log de ações administrativas
 */
function exemploAdminAction($action, $details = []) {
    global $logger;
    
    $username = $_SESSION['admin_username'] ?? 'unknown';
    $user_id = $_SESSION['admin_id'] ?? null;
    
    switch ($action) {
        case 'view_sensitive_data':
            $logger->warning("Administrador acessou dados sensíveis", array_merge([
                'component' => 'admin',
                'action' => 'view_sensitive_data'
            ], $details), $user_id, $username);
            break;
            
        case 'bulk_delete':
            $logger->warning("Administrador executou exclusão em massa", array_merge([
                'component' => 'admin',
                'action' => 'bulk_delete'
            ], $details), $user_id, $username);
            break;
            
        case 'system_config_change':
            $logger->info("Configuração do sistema alterada", array_merge([
                'component' => 'admin',
                'action' => 'config_change'
            ], $details), $user_id, $username);
            break;
    }
}

// ========================================
// EXEMPLO DE CONSULTA DE LOGS
// ========================================

/**
 * Função para buscar logs com filtros
 */
function exemploQueryLogs($level = null, $date = null, $component = null, $limit = 50) {
    global $pdo;
    
    $where_conditions = [];
    $params = [];
    
    if ($level) {
        $where_conditions[] = "level = ?";
        $params[] = $level;
    }
    
    if ($date) {
        $where_conditions[] = "DATE(created_at) = ?";
        $params[] = $date;
    }
    
    if ($component) {
        $where_conditions[] = "JSON_EXTRACT(context, '$.component') = ?";
        $params[] = $component;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "SELECT * FROM system_logs $where_clause ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Exemplo de uso da consulta
$recent_errors = exemploQueryLogs('ERROR', null, null, 10);
$today_auth_logs = exemploQueryLogs(null, date('Y-m-d'), 'auth', 20);

echo "Sistema de logs configurado e exemplos executados com sucesso!\n";
echo "Verifique a página system_logs.php para ver os logs gerados.\n";
?>
