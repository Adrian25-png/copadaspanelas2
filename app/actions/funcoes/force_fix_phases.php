<?php
/**
 * Correção Forçada de Fases Inválidas
 * Remove TODAS as fases que não deveriam existir baseado no estado atual
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();

// Obter tournament_id
$tournament_id = $_GET['tournament_id'] ?? $_POST['tournament_id'] ?? null;

if (!$tournament_id) {
    $tournamentManager = new TournamentManager($pdo);
    $tournament = $tournamentManager->getCurrentTournament();
    if ($tournament) {
        $tournament_id = $tournament['id'];
    } else {
        die("ID do torneio não especificado e nenhum torneio ativo encontrado.");
    }
}

function forceFix($pdo, $tournament_id) {
    $sequencia_fases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    $fases_removidas = [];
    $relatorio = [];
    
    // Verificar cada fase em ordem
    foreach ($sequencia_fases as $index => $fase) {
        // Verificar se esta fase existe
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
        $stmt->execute([$tournament_id, $fase]);
        $fase_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$fase_existe) {
            $relatorio[] = "✅ Fase {$fase}: Não existe";
            continue;
        }
        
        // Verificar se esta fase deveria existir
        $deveria_existir = true;
        $motivo_remocao = '';
        
        if ($fase !== 'Oitavas') {
            // Para fases que não são Oitavas, verificar TODAS as fases anteriores
            for ($i = 0; $i < $index; $i++) {
                $fase_anterior = $sequencia_fases[$i];
                
                // Verificar se fase anterior existe
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
                $stmt->execute([$tournament_id, $fase_anterior]);
                $fase_anterior_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                
                if (!$fase_anterior_existe) {
                    $deveria_existir = false;
                    $motivo_remocao = "Fase anterior {$fase_anterior} não existe";
                    break;
                }
                
                // Verificar se fase anterior está completa
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total_jogos,
                           SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as jogos_finalizados,
                           SUM(CASE WHEN status = 'finalizado' AND team1_goals = team2_goals THEN 1 ELSE 0 END) as empates
                    FROM matches 
                    WHERE tournament_id = ? AND phase = ?
                ");
                $stmt->execute([$tournament_id, $fase_anterior]);
                $status_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $fase_anterior_completa = ($status_anterior['total_jogos'] > 0 && 
                                         $status_anterior['total_jogos'] == $status_anterior['jogos_finalizados'] && 
                                         $status_anterior['empates'] == 0);
                
                if (!$fase_anterior_completa) {
                    $deveria_existir = false;
                    if ($status_anterior['total_jogos'] != $status_anterior['jogos_finalizados']) {
                        $motivo_remocao = "Fase {$fase_anterior} tem jogos não finalizados ({$status_anterior['jogos_finalizados']}/{$status_anterior['total_jogos']})";
                    } elseif ($status_anterior['empates'] > 0) {
                        $motivo_remocao = "Fase {$fase_anterior} tem {$status_anterior['empates']} empate(s)";
                    }
                    break;
                }
            }
        }
        
        if (!$deveria_existir) {
            // Remover esta fase
            $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
            $stmt->execute([$tournament_id, $fase]);
            $fases_removidas[] = $fase;
            $relatorio[] = "❌ Fase {$fase}: REMOVIDA - {$motivo_remocao}";
        } else {
            $relatorio[] = "✅ Fase {$fase}: Válida - mantida";
        }
    }
    
    return [
        'fases_removidas' => $fases_removidas,
        'relatorio' => $relatorio
    ];
}

// Executar correção forçada
$resultado = forceFix($pdo, $tournament_id);

// Se foi chamado via AJAX, retornar JSON
if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'fases_removidas' => $resultado['fases_removidas'],
        'relatorio' => $resultado['relatorio'],
        'message' => count($resultado['fases_removidas']) > 0 
            ? 'Correção forçada executada. Fases removidas: ' . implode(', ', $resultado['fases_removidas'])
            : 'Nenhuma correção necessária. Todas as fases estão válidas.'
    ]);
    exit;
}

// Se foi chamado diretamente, definir mensagem e redirecionar
if (!empty($resultado['fases_removidas'])) {
    $_SESSION['success'] = 'Correção forçada executada! Fases removidas: ' . implode(', ', $resultado['fases_removidas']) . '. Agora complete as fases anteriores para recriar as fases seguintes.';
} else {
    $_SESSION['info'] = 'Nenhuma correção necessária. Todas as fases estão válidas.';
}

// Redirecionar
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '../adm/check_current_issue.php';
header("Location: $redirect");
exit;
?>
