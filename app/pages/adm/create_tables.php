<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


/**
 * Script para criar tabelas necessárias no banco de dados
 */

require_once '../../config/conexao.php';

$pdo = conectar();

try {
    echo "<h2>Criando tabelas necessárias...</h2>";
    
    // Criar tabela de logs de atividade
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournament_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tournament_id (tournament_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela tournament_activity_log criada<br>";
    
    // Criar tabela de backups
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            description TEXT,
            file_size BIGINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela backups criada<br>";
    
    // Verificar se as tabelas principais existem e criar se necessário
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournaments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            year INT NOT NULL,
            description TEXT,
            status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_year (year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela tournaments verificada/criada<br>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournament_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT NOT NULL,
            num_groups INT DEFAULT 4,
            teams_per_group INT DEFAULT 4,
            final_phase BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_tournament (tournament_id),
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela tournament_settings verificada/criada<br>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grupos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            tournament_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tournament_id (tournament_id),
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela grupos verificada/criada<br>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS times (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            tournament_id INT NOT NULL,
            grupo_id INT NULL,
            pts INT DEFAULT 0,
            jogos INT DEFAULT 0,
            vitorias INT DEFAULT 0,
            empates INT DEFAULT 0,
            derrotas INT DEFAULT 0,
            gols_pro INT DEFAULT 0,
            gols_contra INT DEFAULT 0,
            saldo_gols INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tournament_id (tournament_id),
            INDEX idx_grupo_id (grupo_id),
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela times verificada/criada<br>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS matches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT NOT NULL,
            group_id INT NULL,
            team1_id INT NOT NULL,
            team2_id INT NOT NULL,
            team1_goals INT NULL,
            team2_goals INT NULL,
            match_date DATETIME NULL,
            status ENUM('agendado', 'em_andamento', 'finalizado', 'cancelado') DEFAULT 'agendado',
            phase ENUM('grupos', 'oitavas', 'quartas', 'semifinal', 'final') DEFAULT 'grupos',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tournament_id (tournament_id),
            INDEX idx_group_id (group_id),
            INDEX idx_status (status),
            INDEX idx_match_date (match_date),
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES grupos(id) ON DELETE SET NULL,
            FOREIGN KEY (team1_id) REFERENCES times(id) ON DELETE CASCADE,
            FOREIGN KEY (team2_id) REFERENCES times(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela matches verificada/criada<br>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS match_statistics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            match_id INT NOT NULL,
            team_id INT NOT NULL,
            goals INT DEFAULT 0,
            assists INT DEFAULT 0,
            yellow_cards INT DEFAULT 0,
            red_cards INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_match_id (match_id),
            INDEX idx_team_id (team_id),
            FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES times(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela match_statistics verificada/criada<br>";
    
    // Criar diretório de backups se não existir
    $backup_dir = '../../../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
        echo "✅ Diretório de backups criado<br>";
    } else {
        echo "✅ Diretório de backups já existe<br>";
    }
    
    echo "<br><h3 style='color: green;'>✅ Todas as tabelas foram criadas com sucesso!</h3>";
    echo "<p><a href='dashboard_simple.php'>← Voltar ao Painel de Administração</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Erro ao criar tabelas:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><strong>Detalhes técnicos:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
