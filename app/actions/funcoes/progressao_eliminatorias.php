<?php
/**
 * Sistema Automático de Progressão das Eliminatórias
 * Avança automaticamente os vencedores para a próxima fase
 */

require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

function obterProximaFase($fase_atual) {
    $sequencia_fases = [
        'Oitavas' => 'Quartas',
        'Quartas' => 'Semifinal',
        'Semifinal' => 'Final',
        'Final' => 'Campeao'
    ];

    return $sequencia_fases[$fase_atual] ?? null;
}

function obterFaseAnterior($fase_atual) {
    $sequencia_fases = [
        'Quartas' => 'Oitavas',
        'Semifinal' => 'Quartas',
        'Final' => 'Semifinal'
    ];

    return $sequencia_fases[$fase_atual] ?? null;
}

function validarFaseAnteriorCompleta($pdo, $tournament_id, $fase_atual) {
    // Oitavas não tem fase anterior (é a primeira)
    if ($fase_atual === 'Oitavas') {
        return true;
    }

    $fase_anterior = obterFaseAnterior($fase_atual);
    if (!$fase_anterior) {
        return true; // Se não há fase anterior definida, permitir
    }

    // Verificar se a fase anterior existe
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
    $stmt->execute([$tournament_id, $fase_anterior]);
    $fase_anterior_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if (!$fase_anterior_existe) {
        return false; // Fase anterior não existe, bloquear
    }

    // Verificar se todos os jogos da fase anterior estão finalizados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_jogos,
               SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as jogos_finalizados
        FROM matches
        WHERE tournament_id = ? AND phase = ?
    ");
    $stmt->execute([$tournament_id, $fase_anterior]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar se todos os jogos estão finalizados
    if ($resultado['total_jogos'] == 0 || $resultado['total_jogos'] != $resultado['jogos_finalizados']) {
        return false; // Fase anterior não está completa
    }

    // Verificar se todos os jogos têm vencedores definidos (sem empates)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as empates
        FROM matches
        WHERE tournament_id = ? AND phase = ? AND status = 'finalizado'
        AND team1_goals = team2_goals
    ");
    $stmt->execute([$tournament_id, $fase_anterior]);
    $empates = $stmt->fetch(PDO::FETCH_ASSOC)['empates'];

    if ($empates > 0) {
        return false; // Há empates não resolvidos na fase anterior
    }

    return true; // Fase anterior está completa e válida
}

function removerFasesSeguintes($pdo, $tournament_id, $fase_incompleta) {
    $sequencia_fases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    $indice_fase_incompleta = array_search($fase_incompleta, $sequencia_fases);

    if ($indice_fase_incompleta === false) {
        return ['status' => 'error', 'message' => 'Fase não encontrada'];
    }

    // Obter todas as fases seguintes
    $fases_para_remover = array_slice($sequencia_fases, $indice_fase_incompleta + 1);
    $fases_removidas = [];

    foreach ($fases_para_remover as $fase) {
        // Verificar se a fase existe
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
        $stmt->execute([$tournament_id, $fase]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($existe) {
            // Remover todos os jogos desta fase
            $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
            $stmt->execute([$tournament_id, $fase]);
            $fases_removidas[] = $fase;
        }
    }

    return [
        'status' => 'success',
        'message' => 'Fases seguintes removidas devido à fase incompleta',
        'fases_removidas' => $fases_removidas,
        'fase_incompleta' => $fase_incompleta
    ];
}

function verificarERemoverFasesInvalidas($pdo, $tournament_id) {
    $sequencia_fases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    $remocoes_realizadas = [];

    foreach ($sequencia_fases as $fase) {
        // Verificar se esta fase existe
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
        $stmt->execute([$tournament_id, $fase]);
        $fase_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if (!$fase_existe) {
            continue; // Fase não existe, pular
        }

        // Verificar se esta fase está completa
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_jogos,
                   SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as jogos_finalizados,
                   SUM(CASE WHEN status = 'finalizado' AND team1_goals = team2_goals THEN 1 ELSE 0 END) as empates
            FROM matches
            WHERE tournament_id = ? AND phase = ?
        ");
        $stmt->execute([$tournament_id, $fase]);
        $status_fase = $stmt->fetch(PDO::FETCH_ASSOC);

        $fase_completa = ($status_fase['total_jogos'] > 0 &&
                         $status_fase['total_jogos'] == $status_fase['jogos_finalizados'] &&
                         $status_fase['empates'] == 0);

        if (!$fase_completa) {
            // Fase incompleta - remover todas as fases seguintes
            $resultado_remocao = removerFasesSeguintes($pdo, $tournament_id, $fase);
            if ($resultado_remocao['status'] === 'success' && !empty($resultado_remocao['fases_removidas'])) {
                $remocoes_realizadas[] = $resultado_remocao;
            }
            break; // Parar na primeira fase incompleta
        }
    }

    return [
        'status' => 'success',
        'message' => 'Verificação de fases inválidas concluída',
        'remocoes' => $remocoes_realizadas
    ];
}

function obterVencedorJogo($jogo) {
    if ($jogo['status'] !== 'finalizado') {
        return null;
    }
    
    $gols1 = (int)$jogo['team1_goals'];
    $gols2 = (int)$jogo['team2_goals'];
    
    if ($gols1 > $gols2) {
        return [
            'id' => $jogo['team1_id'],
            'nome' => $jogo['team1_name'],
            'gols_favor' => $gols1,
            'gols_contra' => $gols2
        ];
    } elseif ($gols2 > $gols1) {
        return [
            'id' => $jogo['team2_id'],
            'nome' => $jogo['team2_name'],
            'gols_favor' => $gols2,
            'gols_contra' => $gols1
        ];
    }
    
    // Em caso de empate, verificar se há critério de desempate
    // Por enquanto, retornar null (empate não resolvido)
    return null;
}

function verificarFaseCompleta($pdo, $tournament_id, $fase) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_jogos,
               SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as jogos_finalizados
        FROM matches 
        WHERE tournament_id = ? AND phase = ?
    ");
    $stmt->execute([$tournament_id, $fase]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['total_jogos'] > 0 && $resultado['total_jogos'] == $resultado['jogos_finalizados'];
}

function obterJogosFase($pdo, $tournament_id, $fase) {
    $stmt = $pdo->prepare("
        SELECT m.id, m.team1_id, m.team2_id, m.team1_goals, m.team2_goals, 
               m.status, m.phase, m.match_date,
               t1.nome as team1_name, t2.nome as team2_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.phase = ?
        ORDER BY m.id
    ");
    $stmt->execute([$tournament_id, $fase]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function criarProximaFase($pdo, $tournament_id, $fase_atual, $vencedores) {
    $proxima_fase = obterProximaFase($fase_atual);

    if (!$proxima_fase) {
        return ['status' => 'info', 'message' => 'Torneio concluído!'];
    }

    // VALIDAÇÃO: Verificar se a fase atual está realmente completa antes de criar próxima
    if (!validarFaseAnteriorCompleta($pdo, $tournament_id, $proxima_fase)) {
        return [
            'status' => 'error',
            'message' => "Não é possível criar {$proxima_fase}: fase {$fase_atual} não está completa"
        ];
    }

    if ($proxima_fase === 'Campeao') {
        // Definir campeão
        if (count($vencedores) === 1) {
            $campeao = $vencedores[0];

            // Atualizar status do torneio
            $stmt = $pdo->prepare("UPDATE tournaments SET status = 'completed', winner_team_id = ? WHERE id = ?");
            $stmt->execute([$campeao['id'], $tournament_id]);

            return [
                'status' => 'success',
                'message' => "🏆 Torneio concluído! Campeão: {$campeao['nome']}",
                'campeao' => $campeao
            ];
        } else {
            return ['status' => 'error', 'message' => 'Erro: múltiplos vencedores na final'];
        }
    }

    // Verificar se a próxima fase já existe
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
    $stmt->execute([$tournament_id, $proxima_fase]);
    $fase_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if ($fase_existe) {
        // Fase já existe - SEMPRE atualizar os times classificados para refletir mudanças
        return atualizarProximaFase($pdo, $tournament_id, $proxima_fase, $vencedores);
    }

    // Criar confrontos da próxima fase
    $confrontos_criados = 0;
    for ($i = 0; $i < count($vencedores); $i += 2) {
        if (isset($vencedores[$i + 1])) {
            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                VALUES (?, ?, ?, ?, 'agendado', NOW())
            ");
            $stmt->execute([
                $tournament_id,
                $vencedores[$i]['id'],
                $vencedores[$i + 1]['id'],
                $proxima_fase
            ]);
            $confrontos_criados++;
        }
    }

    return [
        'status' => 'success',
        'message' => "Fase {$proxima_fase} criada automaticamente!",
        'detalhes' => [
            'fase_criada' => $proxima_fase,
            'times_classificados' => count($vencedores),
            'confrontos_criados' => $confrontos_criados
        ]
    ];
}

function atualizarProximaFase($pdo, $tournament_id, $proxima_fase, $vencedores) {
    // Primeiro, verificar se há jogos finalizados na próxima fase
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as jogos_finalizados
        FROM matches
        WHERE tournament_id = ? AND phase = ? AND status = 'finalizado'
    ");
    $stmt->execute([$tournament_id, $proxima_fase]);
    $jogos_finalizados = $stmt->fetch(PDO::FETCH_ASSOC)['jogos_finalizados'];

    if ($jogos_finalizados > 0) {
        // Se há jogos finalizados na próxima fase, não atualizar para não perder resultados
        return [
            'status' => 'info',
            'message' => "Fase {$proxima_fase} não foi atualizada pois já tem jogos finalizados"
        ];
    }

    // Limpar jogos existentes da próxima fase (apenas os agendados)
    $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ? AND status = 'agendado'");
    $stmt->execute([$tournament_id, $proxima_fase]);

    // Criar novos confrontos com os vencedores corretos
    $confrontos_criados = 0;
    for ($i = 0; $i < count($vencedores); $i += 2) {
        if (isset($vencedores[$i + 1])) {
            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                VALUES (?, ?, ?, ?, 'agendado', NOW())
            ");
            $stmt->execute([
                $tournament_id,
                $vencedores[$i]['id'],
                $vencedores[$i + 1]['id'],
                $proxima_fase
            ]);
            $confrontos_criados++;
        }
    }

    return [
        'status' => 'success',
        'message' => "Fase {$proxima_fase} atualizada com os novos vencedores!",
        'detalhes' => [
            'fase_atualizada' => $proxima_fase,
            'times_classificados' => count($vencedores),
            'confrontos_criados' => $confrontos_criados
        ]
    ];
}

function verificarEProgressirEliminatorias($tournament_id = null) {
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

        $fases_eliminatorias = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
        $progressoes_realizadas = [];

        foreach ($fases_eliminatorias as $fase) {
            // NOVA VALIDAÇÃO: Verificar se a fase anterior está completa antes de processar
            if (!validarFaseAnteriorCompleta($pdo, $tournament_id, $fase)) {
                $progressoes_realizadas[] = [
                    'fase' => $fase,
                    'status' => 'blocked',
                    'message' => "Fase {$fase} bloqueada: fase anterior não está completa"
                ];
                continue; // Bloquear progressão se fase anterior não estiver completa
            }

            // Verificar se a fase está completa
            if (!verificarFaseCompleta($pdo, $tournament_id, $fase)) {
                continue; // Fase não completa, pular
            }
            
            // Obter jogos da fase
            $jogos = obterJogosFase($pdo, $tournament_id, $fase);
            
            if (empty($jogos)) {
                continue; // Nenhum jogo nesta fase
            }
            
            // Obter vencedores na ordem correta dos jogos
            $vencedores = [];
            $empates_nao_resolvidos = 0;

            foreach ($jogos as $jogo) {
                $vencedor = obterVencedorJogo($jogo);
                if ($vencedor) {
                    // Adicionar informações do jogo para manter a ordem
                    $vencedor['jogo_id'] = $jogo['id'];
                    $vencedor['fase_origem'] = $jogo['phase'];
                    $vencedores[] = $vencedor;
                } else {
                    $empates_nao_resolvidos++;
                }
            }
            
            if ($empates_nao_resolvidos > 0) {
                $progressoes_realizadas[] = [
                    'fase' => $fase,
                    'status' => 'warning',
                    'message' => "Fase {$fase} tem {$empates_nao_resolvidos} empate(s) não resolvido(s)"
                ];
                continue;
            }
            
            if (count($vencedores) !== count($jogos)) {
                continue; // Nem todos os jogos têm vencedores definidos
            }
            
            // Criar próxima fase
            $resultado = criarProximaFase($pdo, $tournament_id, $fase, $vencedores);
            $progressoes_realizadas[] = array_merge($resultado, ['fase_origem' => $fase]);
            
            // Se chegou ao final do torneio, parar
            if (isset($resultado['campeao'])) {
                break;
            }
        }
        
        if (empty($progressoes_realizadas)) {
            return ['status' => 'info', 'message' => 'Nenhuma progressão necessária no momento'];
        }
        
        return [
            'status' => 'success',
            'message' => 'Verificação de progressão concluída',
            'progressoes' => $progressoes_realizadas
        ];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
    }
}

function obterStatusEliminatorias($tournament_id = null) {
    $pdo = conectar();
    
    if (!$tournament_id) {
        $tournamentManager = new TournamentManager($pdo);
        $tournament = $tournamentManager->getCurrentTournament();
        if (!$tournament) {
            return ['status' => 'error', 'message' => 'Nenhum torneio ativo encontrado'];
        }
        $tournament_id = $tournament['id'];
    }
    
    $fases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    $status_fases = [];
    
    foreach ($fases as $fase) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_jogos,
                   SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as jogos_finalizados,
                   SUM(CASE WHEN status = 'agendado' THEN 1 ELSE 0 END) as jogos_agendados
            FROM matches 
            WHERE tournament_id = ? AND phase = ?
        ");
        $stmt->execute([$tournament_id, $fase]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats['total_jogos'] > 0) {
            $status_fases[$fase] = [
                'total_jogos' => $stats['total_jogos'],
                'jogos_finalizados' => $stats['jogos_finalizados'],
                'jogos_agendados' => $stats['jogos_agendados'],
                'completa' => $stats['total_jogos'] == $stats['jogos_finalizados'],
                'progresso' => $stats['total_jogos'] > 0 ? round(($stats['jogos_finalizados'] / $stats['total_jogos']) * 100, 1) : 0
            ];
        }
    }
    
    return $status_fases;
}

// Se chamado diretamente via AJAX ou GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = $_GET['tournament_id'] ?? $_POST['tournament_id'] ?? null;
    $action = $_GET['action'] ?? $_POST['action'] ?? 'verificar';
    
    if ($action === 'status') {
        $resultado = obterStatusEliminatorias($tournament_id);
    } else {
        $resultado = verificarEProgressirEliminatorias($tournament_id);
    }
    
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
    
    // Redirecionar com mensagem
    session_start();
    if ($resultado['status'] === 'success') {
        $_SESSION['success'] = $resultado['message'];
    } elseif ($resultado['status'] === 'warning') {
        $_SESSION['warning'] = $resultado['message'];
    } else {
        $_SESSION['error'] = $resultado['message'];
    }
    
    $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '../exibir_finais.php';
    header("Location: $redirect");
    exit;
}

?>
