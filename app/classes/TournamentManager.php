<?php

class TournamentManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get current active tournament
     */
    public function getCurrentTournament() {
        $stmt = $this->pdo->query("
            SELECT t.*, ts.num_groups, ts.teams_per_group, ts.final_phase,
                   (SELECT COUNT(*) FROM grupos WHERE tournament_id = t.id) as group_count,
                   (SELECT COUNT(*) FROM times WHERE tournament_id = t.id) as team_count
            FROM tournaments t
            LEFT JOIN tournament_settings ts ON t.id = ts.tournament_id
            WHERE t.status = 'active'
            ORDER BY t.created_at DESC
            LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get tournament by ID
     */
    public function getTournamentById($id) {
        $stmt = $this->pdo->prepare("
            SELECT t.*, ts.num_groups, ts.teams_per_group, ts.final_phase
            FROM tournaments t
            LEFT JOIN tournament_settings ts ON t.id = ts.tournament_id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new tournament - VERSÃO CORRIGIDA
     */
    public function createTournament($name, $year, $description, $num_groups, $teams_per_group, $final_phase) {
        try {
            // 1. Primeiro, fazer backup simples se houver torneio ativo
            $current = $this->getCurrentTournament();
            if ($current) {
                $this->createSimpleBackup($current['id'], 'Antes de criar novo torneio');
            }
            
            // 2. Iniciar transação única para todas as operações
            $this->pdo->beginTransaction();
            
            // 3. Arquivar torneio ativo atual
            if ($current) {
                $stmt = $this->pdo->prepare("UPDATE tournaments SET status = 'archived' WHERE status = 'active'");
                $stmt->execute();
            }
            
            // 4. Criar novo torneio
            $stmt = $this->pdo->prepare("
                INSERT INTO tournaments (name, year, description, status, created_at)
                VALUES (?, ?, ?, 'setup', NOW())
            ");
            $stmt->execute([$name, $year, $description]);
            $tournament_id = $this->pdo->lastInsertId();
            
            if (!$tournament_id) {
                throw new Exception("Falha ao criar torneio - ID não gerado");
            }
            
            // 5. Criar configurações do torneio
            $stmt = $this->pdo->prepare("
                INSERT INTO tournament_settings (tournament_id, num_groups, teams_per_group, final_phase)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$tournament_id, $num_groups, $teams_per_group, $final_phase]);
            
            // 6. Criar grupos
            for ($i = 1; $i <= $num_groups; $i++) {
                $group_name = "Grupo " . chr(64 + $i); // A, B, C, etc.
                $stmt = $this->pdo->prepare("
                    INSERT INTO grupos (nome, tournament_id) VALUES (?, ?)
                ");
                $stmt->execute([$group_name, $tournament_id]);
            }
            
            // 7. Log da atividade
            $stmt = $this->pdo->prepare("
                INSERT INTO tournament_activity_log (tournament_id, action, description, created_at)
                VALUES (?, 'CRIADO', ?, NOW())
            ");
            $stmt->execute([$tournament_id, "Torneio criado: $name"]);
            
            // 8. Commit da transação
            $this->pdo->commit();
            
            return $tournament_id;
            
        } catch (Exception $e) {
            // Rollback apenas se a transação estiver ativa
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            // Log do erro
            error_log("Erro ao criar torneio: " . $e->getMessage());
            
            throw new Exception("Erro ao criar torneio: " . $e->getMessage());
        }
    }
    
    /**
     * Backup simples sem transação
     */
    private function createSimpleBackup($tournament_id, $reason) {
        try {
            // Usar conexão separada para evitar conflitos de transação
            $backup_pdo = new PDO(
                "mysql:host=localhost;dbname=copa;charset=utf8",
                "root",
                "",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $backup_data = json_encode([
                'tournament_id' => $tournament_id,
                'backup_date' => date('Y-m-d H:i:s'),
                'reason' => $reason
            ]);
            
            $stmt = $backup_pdo->prepare("
                INSERT INTO tournaments_backup (original_tournament_id, tournament_data, backup_reason, backup_date)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$tournament_id, $backup_data, $reason]);
            
        } catch (Exception $e) {
            // Log do erro mas não interrompe o processo principal
            error_log("Erro no backup: " . $e->getMessage());
        }
    }
    
    /**
     * Activate tournament (ensures only one active tournament)
     */
    public function activateTournament($tournament_id) {
        try {
            // Verificar se o torneio existe e pode ser ativado
            $tournament = $this->getTournamentById($tournament_id);
            if (!$tournament) {
                throw new Exception("Torneio não encontrado");
            }

            if ($tournament['status'] === 'active') {
                throw new Exception("Torneio já está ativo");
            }

            $this->pdo->beginTransaction();

            // Verificar se há torneio ativo e arquivá-lo
            $current_active = $this->getCurrentTournament();
            if ($current_active && $current_active['id'] != $tournament_id) {
                $stmt = $this->pdo->prepare("UPDATE tournaments SET status = 'archived' WHERE status = 'active'");
                $stmt->execute();

                // Log do arquivamento automático
                $this->logActivity($current_active['id'], 'ARQUIVADO', 'Arquivado automaticamente ao ativar novo torneio');
            }

            // Ativar novo torneio
            $stmt = $this->pdo->prepare("UPDATE tournaments SET status = 'active' WHERE id = ?");
            $stmt->execute([$tournament_id]);

            // Log da ativação
            $this->logActivity($tournament_id, 'ATIVADO', 'Torneio ativado');

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Erro ao ativar torneio: " . $e->getMessage());
        }
    }
    
    /**
     * Get tournament statistics
     */
    public function getTournamentStats($tournament_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM grupos WHERE tournament_id = ?) as total_groups,
                (SELECT COUNT(*) FROM times WHERE tournament_id = ?) as total_teams,
                (SELECT COUNT(*) FROM jogos_fase_grupos jfg 
                 INNER JOIN grupos g ON jfg.grupo_id = g.id 
                 WHERE g.tournament_id = ?) as total_matches,
                (SELECT COUNT(*) FROM jogos_fase_grupos jfg 
                 INNER JOIN grupos g ON jfg.grupo_id = g.id 
                 WHERE g.tournament_id = ? AND jfg.resultado_timeA IS NOT NULL) as completed_matches
        ");
        $stmt->execute([$tournament_id, $tournament_id, $tournament_id, $tournament_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate completion percentage
        if ($stats['total_matches'] > 0) {
            $stats['completion_percentage'] = round(($stats['completed_matches'] / $stats['total_matches']) * 100);
        } else {
            $stats['completion_percentage'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Get activity log
     */
    public function getActivityLog($tournament_id, $limit = 10) {
        // Validar e sanitizar o limit
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 10;
        if ($limit > 100) $limit = 100;

        $stmt = $this->pdo->prepare("
            SELECT action, description, created_at
            FROM tournament_activity_log
            WHERE tournament_id = ?
            ORDER BY created_at DESC
            LIMIT " . $limit
        );
        $stmt->execute([$tournament_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all tournaments
     */
    public function getAllTournaments() {
        $stmt = $this->pdo->query("
            SELECT t.*, ts.num_groups, ts.teams_per_group, ts.final_phase,
                   (SELECT COUNT(*) FROM grupos WHERE tournament_id = t.id) as group_count,
                   (SELECT COUNT(*) FROM times WHERE tournament_id = t.id) as team_count
            FROM tournaments t
            LEFT JOIN tournament_settings ts ON t.id = ts.tournament_id
            ORDER BY t.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete tournament with backup
     */
    public function deleteTournament($tournament_id) {
        try {
            // Primeiro, fazer backup completo do torneio
            $tournament = $this->getTournamentById($tournament_id);
            if (!$tournament) {
                throw new Exception("Torneio não encontrado");
            }

            // Criar backup antes de excluir
            $this->createSimpleBackup($tournament_id, 'Backup antes de exclusão');

            // Iniciar transação
            $this->pdo->beginTransaction();

            // Log da exclusão
            $this->logActivity($tournament_id, 'EXCLUIDO', "Torneio excluído: " . $tournament['name']);

            // Excluir torneio (CASCADE irá excluir dados relacionados)
            $stmt = $this->pdo->prepare("DELETE FROM tournaments WHERE id = ?");
            $stmt->execute([$tournament_id]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Erro ao excluir torneio: " . $e->getMessage());
        }
    }

    /**
     * Archive tournament
     */
    public function archiveTournament($tournament_id) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE tournaments SET status = 'archived' WHERE id = ?");
            $stmt->execute([$tournament_id]);

            $this->logActivity($tournament_id, 'ARQUIVADO', 'Torneio arquivado');

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Erro ao arquivar torneio: " . $e->getMessage());
        }
    }

    /**
     * Log activity
     */
    public function logActivity($tournament_id, $action, $description) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tournament_activity_log (tournament_id, action, description, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$tournament_id, $action, $description]);
        } catch (Exception $e) {
            error_log("Erro ao registrar atividade: " . $e->getMessage());
        }
    }



    /**
     * Update tournament status
     */
    public function updateTournamentStatus($tournament_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE tournaments SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $tournament_id]);
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStatistics($tournament_id) {
        $stmt = $this->pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM times WHERE tournament_id = ?) as total_teams,
                (SELECT COUNT(*) FROM grupos WHERE tournament_id = ?) as total_groups,
                (SELECT COUNT(*) FROM matches WHERE tournament_id = ?) as total_matches,
                (SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND status = 'finalizado') as finished_matches,
                (SELECT COUNT(*) FROM matches WHERE tournament_id = ? AND status = 'agendado') as scheduled_matches,
                (SELECT SUM(team1_goals + team2_goals) FROM matches WHERE tournament_id = ? AND status = 'finalizado') as total_goals
        ");
        $stmt->execute([$tournament_id, $tournament_id, $tournament_id, $tournament_id, $tournament_id, $tournament_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
