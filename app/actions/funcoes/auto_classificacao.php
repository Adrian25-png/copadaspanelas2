<?php
/**
 * Sistema Automático de Classificação para Eliminatórias
 * Verifica se a fase de grupos terminou e classifica automaticamente os times
 */

require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

function verificarFaseGruposCompleta($pdo, $tournament_id) {
    // Verificar se todos os jogos da fase de grupos foram finalizados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_jogos,
               SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as jogos_finalizados
        FROM matches 
        WHERE tournament_id = ? AND phase = 'grupos'
    ");
    $stmt->execute([$tournament_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['total_jogos'] > 0 && $resultado['total_jogos'] == $resultado['jogos_finalizados'];
}

function obterTimesClassificados($pdo, $tournament_id, $times_por_grupo = 2) {
    // Buscar grupos do torneio
    $stmt = $pdo->prepare("SELECT id, nome FROM grupos WHERE tournament_id = ? ORDER BY nome");
    $stmt->execute([$tournament_id]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $times_classificados = [];
    
    foreach ($grupos as $grupo) {
        // Buscar times classificados do grupo (ordenados corretamente)
        $stmt = $pdo->prepare("
            SELECT t.id, t.nome, g.nome as grupo_nome,
                   COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team1_goals
                                    WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team2_goals
                                    ELSE 0 END),0) AS gm,
                   COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team2_goals
                                    WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team1_goals
                                    ELSE 0 END),0) AS gc,
                   COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND ((t.id = m.team1_id AND m.team1_goals > m.team2_goals) OR (t.id = m.team2_id AND m.team2_goals > m.team1_goals)) THEN 1 ELSE 0 END),0) AS vitorias,
                   COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND m.team1_goals = m.team2_goals THEN 1 ELSE 0 END),0) AS empates,
                   (COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND ((t.id = m.team1_id AND m.team1_goals > m.team2_goals) OR (t.id = m.team2_id AND m.team2_goals > m.team1_goals)) THEN 1 ELSE 0 END),0) * 3 + 
                    COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND m.team1_goals = m.team2_goals THEN 1 ELSE 0 END),0)) AS pontos,
                   (COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team1_goals
                                    WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team2_goals
                                    ELSE 0 END),0) - 
                    COALESCE(SUM(CASE WHEN m.status = 'finalizado' AND t.id = m.team1_id THEN m.team2_goals
                                    WHEN m.status = 'finalizado' AND t.id = m.team2_id THEN m.team1_goals
                                    ELSE 0 END),0)) AS saldo_gols
            FROM times t
            LEFT JOIN matches m ON (t.id = m.team1_id OR t.id = m.team2_id) AND t.grupo_id = m.group_id AND m.phase = 'grupos'
            WHERE t.grupo_id = ? AND t.tournament_id = ?
            GROUP BY t.id, t.nome, g.nome
            ORDER BY pontos DESC, saldo_gols DESC, gm DESC, t.nome ASC
            LIMIT ?
        ");
        $stmt->execute([$grupo['id'], $tournament_id, $times_por_grupo]);
        $times_grupo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($times_grupo as $index => $time) {
            $times_classificados[] = [
                'id' => $time['id'],
                'nome' => $time['nome'],
                'grupo_nome' => $time['grupo_nome'],
                'posicao_grupo' => $index + 1,
                'pontos' => $time['pontos'],
                'saldo_gols' => $time['saldo_gols'],
                'gm' => $time['gm']
            ];
        }
    }
    
    return $times_classificados;
}

function criarEliminatorias($pdo, $tournament_id, $times_classificados) {
    // Limpar jogos existentes das eliminatórias
    $phases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    foreach ($phases as $phase) {
        $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
        $stmt->execute([$tournament_id, $phase]);
    }
    
    $num_times = count($times_classificados);
    
    // Determinar formato baseado no número de times classificados
    if ($num_times >= 16) {
        $fase_inicial = 'Oitavas';
        $times_fase = 16;
    } elseif ($num_times >= 8) {
        $fase_inicial = 'Quartas';
        $times_fase = 8;
    } elseif ($num_times >= 4) {
        $fase_inicial = 'Semifinal';
        $times_fase = 4;
    } elseif ($num_times >= 2) {
        $fase_inicial = 'Final';
        $times_fase = 2;
    } else {
        throw new Exception("Número insuficiente de times classificados: $num_times");
    }
    
    // Pegar os melhores times para a fase inicial
    $times_para_fase = array_slice($times_classificados, 0, $times_fase);
    
    // Criar confrontos da fase inicial
    for ($i = 0; $i < count($times_para_fase); $i += 2) {
        if (isset($times_para_fase[$i + 1])) {
            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                VALUES (?, ?, ?, ?, 'agendado', NOW())
            ");
            $stmt->execute([
                $tournament_id,
                $times_para_fase[$i]['id'],
                $times_para_fase[$i + 1]['id'],
                $fase_inicial
            ]);
        }
    }
    
    return [
        'fase_criada' => $fase_inicial,
        'times_classificados' => count($times_para_fase),
        'confrontos_criados' => floor(count($times_para_fase) / 2)
    ];
}

function verificarEExecutarClassificacao($tournament_id = null) {
    $pdo = conectar();
    
    try {
        // Se não especificado, usar torneio ativo
        if (!$tournament_id) {
            $tournamentManager = new TournamentManager($pdo);
            $tournament = $tournamentManager->getCurrentTournament();
            if (!$tournament) {
                return ['status' => 'error', 'message' => 'Nenhum torneio ativo encontrado'];
            }
            $tournament_id = $tournament['id'];
        }
        
        // Verificar se já existem eliminatórias criadas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM matches 
            WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final')
        ");
        $stmt->execute([$tournament_id]);
        $eliminatorias_existem = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($eliminatorias_existem) {
            return ['status' => 'info', 'message' => 'Eliminatórias já foram criadas'];
        }
        
        // Verificar se a fase de grupos está completa
        if (!verificarFaseGruposCompleta($pdo, $tournament_id)) {
            return ['status' => 'info', 'message' => 'Fase de grupos ainda não foi concluída'];
        }
        
        // Obter times classificados (2 por grupo)
        $times_classificados = obterTimesClassificados($pdo, $tournament_id, 2);
        
        if (count($times_classificados) < 2) {
            return ['status' => 'error', 'message' => 'Número insuficiente de times classificados'];
        }
        
        // Criar eliminatórias
        $resultado = criarEliminatorias($pdo, $tournament_id, $times_classificados);
        
        return [
            'status' => 'success',
            'message' => "Eliminatórias criadas automaticamente!",
            'detalhes' => $resultado
        ];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
    }
}

// Se chamado diretamente via AJAX ou GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = $_GET['tournament_id'] ?? $_POST['tournament_id'] ?? null;
    $resultado = verificarEExecutarClassificacao($tournament_id);
    
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
    
    // Redirecionar com mensagem
    session_start();
    if ($resultado['status'] === 'success') {
        $_SESSION['success'] = $resultado['message'];
    } else {
        $_SESSION['error'] = $resultado['message'];
    }
    
    $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '../tabela_de_classificacao.php';
    header("Location: $redirect");
    exit;
}

?>
