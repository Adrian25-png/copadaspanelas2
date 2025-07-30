<?php
/**
 * Script para limpar fases inválidas existentes
 * Remove fases que não deveriam existir baseado no estado das fases anteriores
 */

session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once 'progressao_eliminatorias.php';

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

function limparFasesInvalidasExistentes($pdo, $tournament_id) {
    $sequencia_fases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
    $fases_removidas = [];
    $relatorio = [];
    
    foreach ($sequencia_fases as $index => $fase) {
        // Verificar se esta fase existe
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
        $stmt->execute([$tournament_id, $fase]);
        $fase_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$fase_existe) {
            $relatorio[] = "Fase {$fase}: Não existe - OK";
            continue;
        }
        
        // Para fases que não são Oitavas, verificar se a fase anterior está completa
        if ($fase !== 'Oitavas') {
            $fase_anterior = $sequencia_fases[$index - 1];
            
            // Verificar se fase anterior existe
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE tournament_id = ? AND phase = ?");
            $stmt->execute([$tournament_id, $fase_anterior]);
            $fase_anterior_existe = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if (!$fase_anterior_existe) {
                // Fase anterior não existe, remover esta fase
                $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
                $stmt->execute([$tournament_id, $fase]);
                $fases_removidas[] = $fase;
                $relatorio[] = "Fase {$fase}: REMOVIDA (fase anterior {$fase_anterior} não existe)";
                continue;
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
                // Fase anterior não está completa, remover esta fase
                $stmt = $pdo->prepare("DELETE FROM matches WHERE tournament_id = ? AND phase = ?");
                $stmt->execute([$tournament_id, $fase]);
                $fases_removidas[] = $fase;
                
                $motivo = '';
                if ($status_anterior['total_jogos'] != $status_anterior['jogos_finalizados']) {
                    $motivo = "jogos não finalizados";
                } elseif ($status_anterior['empates'] > 0) {
                    $motivo = "empates não resolvidos";
                }
                
                $relatorio[] = "Fase {$fase}: REMOVIDA (fase anterior {$fase_anterior} incompleta - {$motivo})";
                continue;
            }
        }
        
        $relatorio[] = "Fase {$fase}: Válida - mantida";
    }
    
    return [
        'fases_removidas' => $fases_removidas,
        'relatorio' => $relatorio
    ];
}

// Executar limpeza
$resultado = limparFasesInvalidasExistentes($pdo, $tournament_id);

// Se foi chamado via AJAX, retornar JSON
if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'fases_removidas' => $resultado['fases_removidas'],
        'relatorio' => $resultado['relatorio'],
        'message' => count($resultado['fases_removidas']) > 0 
            ? 'Fases inválidas removidas: ' . implode(', ', $resultado['fases_removidas'])
            : 'Nenhuma fase inválida encontrada'
    ]);
    exit;
}

// Se foi chamado diretamente, definir mensagem e redirecionar
if (!empty($resultado['fases_removidas'])) {
    $_SESSION['success'] = 'Fases inválidas removidas: ' . implode(', ', $resultado['fases_removidas']);
} else {
    $_SESSION['info'] = 'Nenhuma fase inválida encontrada. Todas as fases estão corretas.';
}

// Redirecionar
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '../adm/finals_manager.php?tournament_id=' . $tournament_id;
header("Location: $redirect");
exit;
?>
