<?php

/**
 * Classe para gerenciamento completo de jogos
 * Criada do zero para o sistema Copa das Panelas
 */
class MatchManager {
    private $pdo;
    private $tournament_id;
    
    public function __construct($pdo, $tournament_id = null) {
        $this->pdo = $pdo;
        $this->tournament_id = $tournament_id;
        $this->initializeDatabase();
    }
    
    /**
     * Inicializar estrutura do banco de dados
     */
    private function initializeDatabase() {
        try {
            // Criar tabela principal de jogos se não existir
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS matches (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tournament_id INT NOT NULL,
                    group_id INT NULL,
                    team1_id INT NOT NULL,
                    team2_id INT NOT NULL,
                    team1_goals INT DEFAULT NULL,
                    team2_goals INT DEFAULT NULL,
                    phase ENUM('grupos', 'oitavas', 'quartas', 'semifinal', 'final', 'terceiro_lugar') DEFAULT 'grupos',
                    status ENUM('agendado', 'em_andamento', 'finalizado', 'cancelado') DEFAULT 'agendado',
                    match_date DATETIME NULL,
                    round_number INT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_tournament (tournament_id),
                    INDEX idx_group (group_id),
                    INDEX idx_phase (phase),
                    INDEX idx_status (status),
                    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
                    FOREIGN KEY (group_id) REFERENCES grupos(id) ON DELETE SET NULL,
                    FOREIGN KEY (team1_id) REFERENCES times(id) ON DELETE CASCADE,
                    FOREIGN KEY (team2_id) REFERENCES times(id) ON DELETE CASCADE
                )
            ");
            
            // Criar tabela de estatísticas de jogos
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS match_statistics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    match_id INT NOT NULL,
                    team_id INT NOT NULL,
                    goals_scored INT DEFAULT 0,
                    goals_conceded INT DEFAULT 0,
                    result ENUM('V', 'E', 'D') NULL,
                    points INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
                    FOREIGN KEY (team_id) REFERENCES times(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_match_team (match_id, team_id)
                )
            ");
            
        } catch (Exception $e) {
            error_log("Erro ao inicializar banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Gerar jogos da fase de grupos
     */
    public function generateGroupMatches($tournament_id = null) {
        $tournament_id = $tournament_id ?? $this->tournament_id;
        
        if (!$tournament_id) {
            throw new Exception("ID do torneio é obrigatório");
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Obter grupos do torneio
            $stmt = $this->pdo->prepare("
                SELECT id, nome 
                FROM grupos 
                WHERE tournament_id = ? 
                ORDER BY nome
            ");
            $stmt->execute([$tournament_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_matches = 0;
            
            foreach ($groups as $group) {
                // Obter times do grupo
                $stmt = $this->pdo->prepare("
                    SELECT id, nome 
                    FROM times 
                    WHERE grupo_id = ? AND tournament_id = ?
                    ORDER BY nome
                ");
                $stmt->execute([$group['id'], $tournament_id]);
                $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Gerar jogos entre todos os times do grupo (round-robin)
                $group_matches = $this->generateRoundRobinMatches($teams, $group['id'], $tournament_id);
                $total_matches += $group_matches;
            }
            
            $this->pdo->commit();
            return $total_matches;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao gerar jogos: " . $e->getMessage());
        }
    }
    
    /**
     * Gerar jogos round-robin para um grupo
     */
    private function generateRoundRobinMatches($teams, $group_id, $tournament_id) {
        $matches_created = 0;
        $team_count = count($teams);
        
        for ($i = 0; $i < $team_count; $i++) {
            for ($j = $i + 1; $j < $team_count; $j++) {
                $team1 = $teams[$i];
                $team2 = $teams[$j];
                
                // Verificar se o jogo já existe
                if (!$this->matchExists($tournament_id, $team1['id'], $team2['id'])) {
                    $this->createMatch($tournament_id, $group_id, $team1['id'], $team2['id']);
                    $matches_created++;
                }
            }
        }
        
        return $matches_created;
    }
    
    /**
     * Verificar se um jogo já existe
     */
    private function matchExists($tournament_id, $team1_id, $team2_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM matches 
            WHERE tournament_id = ? 
            AND ((team1_id = ? AND team2_id = ?) OR (team1_id = ? AND team2_id = ?))
        ");
        $stmt->execute([$tournament_id, $team1_id, $team2_id, $team2_id, $team1_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Criar um jogo
     */
    private function createMatch($tournament_id, $group_id, $team1_id, $team2_id, $phase = 'grupos') {
        $stmt = $this->pdo->prepare("
            INSERT INTO matches (tournament_id, group_id, team1_id, team2_id, phase, status)
            VALUES (?, ?, ?, ?, ?, 'agendado')
        ");
        return $stmt->execute([$tournament_id, $group_id, $team1_id, $team2_id, $phase]);
    }
    
    /**
     * Atualizar resultado de um jogo
     */
    public function updateMatchResult($match_id, $team1_goals, $team2_goals, $match_date = null) {
        try {
            $this->pdo->beginTransaction();

            // Atualizar o jogo
            $stmt = $this->pdo->prepare("
                UPDATE matches
                SET team1_goals = ?, team2_goals = ?, status = 'finalizado',
                    match_date = COALESCE(?, NOW()), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$team1_goals, $team2_goals, $match_date, $match_id]);

            // Obter dados do jogo
            $match = $this->getMatchById($match_id);
            if (!$match) {
                throw new Exception("Jogo não encontrado");
            }

            // Atualizar estatísticas dos times
            $this->updateTeamStatistics($match['team1_id'], $team1_goals, $team2_goals);
            $this->updateTeamStatistics($match['team2_id'], $team2_goals, $team1_goals);

            // Criar/atualizar estatísticas do jogo
            $this->updateMatchStatistics($match_id, $match['team1_id'], $team1_goals, $team2_goals);
            $this->updateMatchStatistics($match_id, $match['team2_id'], $team2_goals, $team1_goals);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao atualizar resultado: " . $e->getMessage());
        }
    }

    /**
     * Agendar data e horário para um jogo
     */
    public function scheduleMatch($match_id, $match_date, $match_time = null) {
        try {
            // Combinar data e hora se fornecidos
            if ($match_time) {
                $datetime = $match_date . ' ' . $match_time;
            } else {
                $datetime = $match_date . ' 00:00:00';
            }

            $stmt = $this->pdo->prepare("
                UPDATE matches
                SET match_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$datetime, $match_id]);

            return true;

        } catch (Exception $e) {
            throw new Exception("Erro ao agendar jogo: " . $e->getMessage());
        }
    }

    /**
     * Agendar múltiplos jogos em lote
     */
    public function scheduleMultipleMatches($schedules) {
        try {
            $this->pdo->beginTransaction();

            $updated_count = 0;
            foreach ($schedules as $match_id => $schedule) {
                if (!empty($schedule['date'])) {
                    $time = $schedule['time'] ?? '00:00';
                    $datetime = $schedule['date'] . ' ' . $time . ':00';

                    $stmt = $this->pdo->prepare("
                        UPDATE matches
                        SET match_date = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$datetime, $match_id]);
                    $updated_count++;
                }
            }

            $this->pdo->commit();
            return $updated_count;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao agendar jogos: " . $e->getMessage());
        }
    }

    /**
     * Obter jogos por data
     */
    public function getMatchesByDate($tournament_id, $date) {
        $stmt = $this->pdo->prepare("
            SELECT m.*,
                   t1.nome as team1_name, t2.nome as team2_name,
                   g.nome as group_name,
                   DATE(m.match_date) as match_date_only,
                   TIME(m.match_date) as match_time_only
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            LEFT JOIN grupos g ON m.group_id = g.id
            WHERE m.tournament_id = ? AND DATE(m.match_date) = ?
            ORDER BY m.match_date, m.id
        ");
        $stmt->execute([$tournament_id, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter agenda de jogos (próximos 30 dias)
     */
    public function getMatchCalendar($tournament_id, $days_ahead = 30) {
        $stmt = $this->pdo->prepare("
            SELECT m.*,
                   t1.nome as team1_name, t2.nome as team2_name,
                   g.nome as group_name,
                   DATE(m.match_date) as match_date_only,
                   TIME(m.match_date) as match_time_only
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            LEFT JOIN grupos g ON m.group_id = g.id
            WHERE m.tournament_id = ?
            AND m.match_date IS NOT NULL
            AND DATE(m.match_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY m.match_date, m.id
        ");
        $stmt->execute([$tournament_id, $days_ahead]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter jogos sem data agendada
     */
    public function getUnscheduledMatches($tournament_id) {
        $stmt = $this->pdo->prepare("
            SELECT m.*,
                   t1.nome as team1_name, t2.nome as team2_name,
                   g.nome as group_name
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            LEFT JOIN grupos g ON m.group_id = g.id
            WHERE m.tournament_id = ? AND m.match_date IS NULL
            ORDER BY m.phase, g.nome, m.id
        ");
        $stmt->execute([$tournament_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Atualizar estatísticas de um time
     */
    private function updateTeamStatistics($team_id, $goals_for, $goals_against) {
        // Determinar resultado
        if ($goals_for > $goals_against) {
            $wins = 1; $draws = 0; $losses = 0; $points = 3;
        } elseif ($goals_for == $goals_against) {
            $wins = 0; $draws = 1; $losses = 0; $points = 1;
        } else {
            $wins = 0; $draws = 0; $losses = 1; $points = 0;
        }
        
        // Atualizar estatísticas do time
        $stmt = $this->pdo->prepare("
            UPDATE times 
            SET pts = pts + ?, vitorias = vitorias + ?, empates = empates + ?, 
                derrotas = derrotas + ?, gm = gm + ?, gc = gc + ?, 
                sg = gm - gc
            WHERE id = ?
        ");
        $stmt->execute([$points, $wins, $draws, $losses, $goals_for, $goals_against, $team_id]);
    }
    
    /**
     * Atualizar estatísticas do jogo
     */
    private function updateMatchStatistics($match_id, $team_id, $goals_for, $goals_against) {
        // Determinar resultado
        if ($goals_for > $goals_against) {
            $result = 'V'; $points = 3;
        } elseif ($goals_for == $goals_against) {
            $result = 'E'; $points = 1;
        } else {
            $result = 'D'; $points = 0;
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO match_statistics (match_id, team_id, goals_scored, goals_conceded, result, points)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            goals_scored = VALUES(goals_scored),
            goals_conceded = VALUES(goals_conceded),
            result = VALUES(result),
            points = VALUES(points)
        ");
        $stmt->execute([$match_id, $team_id, $goals_for, $goals_against, $result, $points]);
    }
    
    /**
     * Obter jogo por ID
     */
    public function getMatchById($match_id) {
        $stmt = $this->pdo->prepare("
            SELECT m.*, 
                   t1.nome as team1_name, t2.nome as team2_name,
                   g.nome as group_name
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            LEFT JOIN grupos g ON m.group_id = g.id
            WHERE m.id = ?
        ");
        $stmt->execute([$match_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obter todos os jogos de um torneio
     */
    public function getTournamentMatches($tournament_id = null, $phase = null) {
        $tournament_id = $tournament_id ?? $this->tournament_id;
        
        $sql = "
            SELECT m.*, 
                   t1.nome as team1_name, t2.nome as team2_name,
                   g.nome as group_name
            FROM matches m
            LEFT JOIN times t1 ON m.team1_id = t1.id
            LEFT JOIN times t2 ON m.team2_id = t2.id
            LEFT JOIN grupos g ON m.group_id = g.id
            WHERE m.tournament_id = ?
        ";
        
        $params = [$tournament_id];
        
        if ($phase) {
            $sql .= " AND m.phase = ?";
            $params[] = $phase;
        }
        
        $sql .= " ORDER BY m.phase, g.nome, m.round_number, m.id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obter estatísticas do torneio
     */
    public function getTournamentStatistics($tournament_id = null) {
        $tournament_id = $tournament_id ?? $this->tournament_id;
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_matches,
                SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as completed_matches,
                SUM(CASE WHEN status = 'agendado' THEN 1 ELSE 0 END) as scheduled_matches,
                SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as ongoing_matches,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_matches,
                SUM(COALESCE(team1_goals, 0) + COALESCE(team2_goals, 0)) as total_goals
            FROM matches 
            WHERE tournament_id = ?
        ");
        $stmt->execute([$tournament_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Excluir um jogo
     */
    public function deleteMatch($match_id) {
        try {
            $this->pdo->beginTransaction();
            
            // Obter dados do jogo antes de excluir
            $match = $this->getMatchById($match_id);
            if (!$match) {
                throw new Exception("Jogo não encontrado");
            }
            
            // Se o jogo foi finalizado, reverter estatísticas
            if ($match['status'] === 'finalizado') {
                $this->revertTeamStatistics($match['team1_id'], $match['team1_goals'], $match['team2_goals']);
                $this->revertTeamStatistics($match['team2_id'], $match['team2_goals'], $match['team1_goals']);
            }
            
            // Excluir estatísticas do jogo
            $stmt = $this->pdo->prepare("DELETE FROM match_statistics WHERE match_id = ?");
            $stmt->execute([$match_id]);
            
            // Excluir o jogo
            $stmt = $this->pdo->prepare("DELETE FROM matches WHERE id = ?");
            $stmt->execute([$match_id]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao excluir jogo: " . $e->getMessage());
        }
    }
    
    /**
     * Reverter estatísticas de um time
     */
    private function revertTeamStatistics($team_id, $goals_for, $goals_against) {
        // Determinar resultado para reverter
        if ($goals_for > $goals_against) {
            $wins = -1; $draws = 0; $losses = 0; $points = -3;
        } elseif ($goals_for == $goals_against) {
            $wins = 0; $draws = -1; $losses = 0; $points = -1;
        } else {
            $wins = 0; $draws = 0; $losses = -1; $points = 0;
        }
        
        // Reverter estatísticas do time
        $stmt = $this->pdo->prepare("
            UPDATE times 
            SET pts = pts + ?, vitorias = vitorias + ?, empates = empates + ?, 
                derrotas = derrotas + ?, gm = gm + ?, gc = gc + ?, 
                sg = gm - gc
            WHERE id = ?
        ");
        $stmt->execute([$points, $wins, $draws, $losses, -$goals_for, -$goals_against, $team_id]);
    }
    
    /**
     * Reverter estatísticas de um jogo específico
     */
    public function revertMatchStatistics($match_id) {
        try {
            // Obter dados do jogo
            $match = $this->getMatchById($match_id);
            if (!$match || $match['status'] !== 'finalizado') {
                return false;
            }

            // Reverter estatísticas dos times
            $this->revertTeamStatistics($match['team1_id'], $match['team1_goals'], $match['team2_goals']);
            $this->revertTeamStatistics($match['team2_id'], $match['team2_goals'], $match['team1_goals']);

            // Remover estatísticas do jogo
            $stmt = $this->pdo->prepare("DELETE FROM match_statistics WHERE match_id = ?");
            $stmt->execute([$match_id]);

            return true;

        } catch (Exception $e) {
            throw new Exception("Erro ao reverter estatísticas: " . $e->getMessage());
        }
    }

    /**
     * Recalcular todas as estatísticas de um torneio
     */
    public function recalculateAllStatistics($tournament_id = null) {
        $tournament_id = $tournament_id ?? $this->tournament_id;

        try {
            $this->pdo->beginTransaction();

            // Zerar estatísticas de todos os times do torneio
            $stmt = $this->pdo->prepare("
                UPDATE times
                SET pts = 0, vitorias = 0, empates = 0, derrotas = 0,
                    gm = 0, gc = 0, sg = 0
                WHERE tournament_id = ?
            ");
            $stmt->execute([$tournament_id]);

            // Limpar estatísticas de jogos
            $stmt = $this->pdo->prepare("
                DELETE ms FROM match_statistics ms
                INNER JOIN matches m ON ms.match_id = m.id
                WHERE m.tournament_id = ?
            ");
            $stmt->execute([$tournament_id]);

            // Recalcular com base nos jogos finalizados
            $matches = $this->getTournamentMatches($tournament_id);
            foreach ($matches as $match) {
                if ($match['status'] === 'finalizado' &&
                    $match['team1_goals'] !== null &&
                    $match['team2_goals'] !== null) {

                    $this->updateTeamStatistics($match['team1_id'], $match['team1_goals'], $match['team2_goals']);
                    $this->updateTeamStatistics($match['team2_id'], $match['team2_goals'], $match['team1_goals']);
                    $this->updateMatchStatistics($match['id'], $match['team1_id'], $match['team1_goals'], $match['team2_goals']);
                    $this->updateMatchStatistics($match['id'], $match['team2_id'], $match['team2_goals'], $match['team1_goals']);
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao recalcular estatísticas: " . $e->getMessage());
        }
    }
}
?>
