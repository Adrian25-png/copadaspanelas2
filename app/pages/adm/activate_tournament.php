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
require_once '../../classes/TournamentManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tournament_id = $input['tournament_id'] ?? null;

if (!$tournament_id) {
    echo json_encode(['success' => false, 'message' => 'Tournament ID required']);
    exit;
}

try {
    $pdo = conectar();
    $tournamentManager = new TournamentManager($pdo);
    
    // Activate tournament
    $result = $tournamentManager->activateTournament($tournament_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Tournament activated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to activate tournament']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
