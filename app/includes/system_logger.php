<?php
/**
 * Sistema de Logs Copa das Panelas
 * Classe para registrar eventos do sistema automaticamente
 */

class SystemLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTableIfNotExists();
    }
    
    /**
     * Criar tabela de logs se não existir
     */
    private function createTableIfNotExists() {
        try {
            $this->pdo->exec("
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
            // Falha silenciosa se não conseguir criar a tabela
        }
    }
    
    /**
     * Registrar log de informação
     */
    public function info($message, $context = null, $user_id = null, $username = null) {
        return $this->log('INFO', $message, $context, $user_id, $username);
    }
    
    /**
     * Registrar log de sucesso
     */
    public function success($message, $context = null, $user_id = null, $username = null) {
        return $this->log('SUCCESS', $message, $context, $user_id, $username);
    }
    
    /**
     * Registrar log de aviso
     */
    public function warning($message, $context = null, $user_id = null, $username = null) {
        return $this->log('WARNING', $message, $context, $user_id, $username);
    }
    
    /**
     * Registrar log de erro
     */
    public function error($message, $context = null, $user_id = null, $username = null) {
        return $this->log('ERROR', $message, $context, $user_id, $username);
    }
    
    /**
     * Registrar log no banco de dados
     */
    private function log($level, $message, $context = null, $user_id = null, $username = null) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // Se não foi fornecido username mas há sessão admin, usar da sessão
            if (!$username && isset($_SESSION['admin_username'])) {
                $username = $_SESSION['admin_username'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO system_logs (level, message, context, ip_address, user_agent, user_id, username, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $context_json = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;
            $stmt->execute([$level, $message, $context_json, $ip, $user_agent, $user_id, $username]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Registrar login de usuário
     */
    public function logLogin($username, $success = true, $user_id = null) {
        if ($success) {
            return $this->success("Usuário '$username' fez login no sistema", [
                'component' => 'auth',
                'action' => 'login',
                'user' => $username
            ], $user_id, $username);
        } else {
            return $this->warning("Tentativa de login falhada para usuário '$username'", [
                'component' => 'auth',
                'action' => 'login_failed',
                'user' => $username
            ], $user_id, $username);
        }
    }
    
    /**
     * Registrar logout de usuário
     */
    public function logLogout($username, $user_id = null) {
        return $this->info("Usuário '$username' fez logout do sistema", [
            'component' => 'auth',
            'action' => 'logout',
            'user' => $username
        ], $user_id, $username);
    }
    
    /**
     * Registrar criação de torneio
     */
    public function logTournamentCreated($tournament_name, $tournament_id, $username = null) {
        return $this->success("Torneio '$tournament_name' criado com sucesso", [
            'component' => 'tournament',
            'action' => 'create',
            'tournament_id' => $tournament_id,
            'tournament_name' => $tournament_name
        ], null, $username);
    }
    
    /**
     * Registrar edição de torneio
     */
    public function logTournamentUpdated($tournament_name, $tournament_id, $username = null) {
        return $this->info("Torneio '$tournament_name' foi atualizado", [
            'component' => 'tournament',
            'action' => 'update',
            'tournament_id' => $tournament_id,
            'tournament_name' => $tournament_name
        ], null, $username);
    }
    
    /**
     * Registrar exclusão de torneio
     */
    public function logTournamentDeleted($tournament_name, $tournament_id, $username = null) {
        return $this->warning("Torneio '$tournament_name' foi excluído", [
            'component' => 'tournament',
            'action' => 'delete',
            'tournament_id' => $tournament_id,
            'tournament_name' => $tournament_name
        ], null, $username);
    }
    
    /**
     * Registrar criação de time
     */
    public function logTeamCreated($team_name, $team_id, $tournament_id = null, $username = null) {
        return $this->success("Time '$team_name' criado com sucesso", [
            'component' => 'team',
            'action' => 'create',
            'team_id' => $team_id,
            'team_name' => $team_name,
            'tournament_id' => $tournament_id
        ], null, $username);
    }
    
    /**
     * Registrar atualização de resultado de jogo
     */
    public function logMatchResultUpdated($match_id, $team1, $team2, $score, $username = null) {
        return $this->success("Resultado do jogo atualizado: $team1 vs $team2 ($score)", [
            'component' => 'match',
            'action' => 'update_result',
            'match_id' => $match_id,
            'teams' => "$team1 vs $team2",
            'score' => $score
        ], null, $username);
    }
    
    /**
     * Registrar erro de sistema
     */
    public function logSystemError($error_message, $component = 'system', $context = null) {
        return $this->error("Erro no sistema: $error_message", array_merge([
            'component' => $component,
            'action' => 'error'
        ], $context ?? []));
    }
    
    /**
     * Registrar operação de backup
     */
    public function logBackup($filename, $size = null, $success = true, $username = null) {
        if ($success) {
            return $this->success("Backup criado com sucesso: $filename", [
                'component' => 'backup',
                'action' => 'create',
                'filename' => $filename,
                'size' => $size
            ], null, $username);
        } else {
            return $this->error("Falha ao criar backup: $filename", [
                'component' => 'backup',
                'action' => 'create_failed',
                'filename' => $filename
            ], null, $username);
        }
    }
    
    /**
     * Registrar limpeza de cache
     */
    public function logCacheCleared($files_removed = null, $username = null) {
        return $this->info("Cache do sistema limpo", [
            'component' => 'cache',
            'action' => 'clear',
            'files_removed' => $files_removed
        ], null, $username);
    }
    
    /**
     * Registrar otimização do banco
     */
    public function logDatabaseOptimized($tables_optimized = null, $username = null) {
        return $this->success("Otimização do banco de dados concluída", [
            'component' => 'maintenance',
            'action' => 'optimize_database',
            'tables_optimized' => $tables_optimized
        ], null, $username);
    }
    
    /**
     * Registrar tentativa de acesso negada
     */
    public function logAccessDenied($attempted_page = null, $reason = null) {
        return $this->warning("Tentativa de acesso negada", [
            'component' => 'security',
            'action' => 'access_denied',
            'attempted_page' => $attempted_page,
            'reason' => $reason
        ]);
    }
    
    /**
     * Registrar início do sistema
     */
    public function logSystemStart() {
        return $this->info("Sistema iniciado com sucesso", [
            'component' => 'system',
            'action' => 'start'
        ], null, 'system');
    }
    
    /**
     * Registrar monitoramento de recursos
     */
    public function logResourceMonitoring($resource_type, $usage, $threshold = null) {
        $level = 'INFO';
        if ($threshold && $usage > $threshold) {
            $level = 'WARNING';
        }
        
        return $this->log($level, "Monitoramento de recursos: $resource_type em $usage", [
            'component' => 'monitor',
            'action' => 'resource_check',
            'resource_type' => $resource_type,
            'usage' => $usage,
            'threshold' => $threshold
        ], null, 'system');
    }
}

/**
 * Função helper para criar instância do logger
 */
function getSystemLogger($pdo) {
    return new SystemLogger($pdo);
}

/**
 * Função helper para log rápido
 */
function logSystemEvent($pdo, $level, $message, $context = null, $user_id = null, $username = null) {
    $logger = new SystemLogger($pdo);
    return $logger->log($level, $message, $context, $user_id, $username);
}
?>
