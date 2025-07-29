<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    include '../config/conexao.php';
    $pdo = conectar();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Buscar estado atual de sincronização
        getCurrentSyncState($pdo);
    } elseif ($method === 'POST') {
        // Atualizar estado de sincronização
        updateSyncState($pdo);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getCurrentSyncState($pdo) {
    // Buscar mídia ativa
    $stmt = $pdo->query("
        SELECT am.*, ml.file_path, ml.file_name, ml.duration as file_duration
        FROM active_media am
        LEFT JOIN media_library ml ON am.media_id = ml.id
        WHERE am.id = (SELECT MAX(id) FROM active_media)
        LIMIT 1
    ");
    $activeMedia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activeMedia) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma mídia ativa']);
        return;
    }
    
    // Calcular posição atual baseada no tempo do servidor
    $currentTime = time();
    $startTime = strtotime($activeMedia['start_time']);
    $duration = $activeMedia['duration'] ?: $activeMedia['file_duration'] ?: 180; // fallback 3 minutos
    
    $elapsedTime = $currentTime - $startTime;
    
    // Se tem loop habilitado, calcular posição dentro do loop
    if ($activeMedia['loop_enabled'] && $duration > 0) {
        $currentPosition = fmod($elapsedTime, $duration);
    } else {
        $currentPosition = $elapsedTime;
    }
    
    // Se a música já terminou e não tem loop
    $isPlaying = true;
    if (!$activeMedia['loop_enabled'] && $elapsedTime >= $duration) {
        $isPlaying = false;
        $currentPosition = $duration;
    }
    
    $response = [
        'success' => true,
        'sync_data' => [
            'media_id' => $activeMedia['media_id'],
            'media_type' => $activeMedia['media_type'],
            'title' => $activeMedia['title'],
            'file_path' => $activeMedia['file_path'],
            'file_name' => $activeMedia['file_name'],
            'target_pages' => $activeMedia['target_pages'],
            'show_controls' => (bool)$activeMedia['show_controls'],
            'current_position' => round($currentPosition, 3),
            'duration' => $duration,
            'is_playing' => $isPlaying,
            'loop_enabled' => (bool)$activeMedia['loop_enabled'],
            'global_sync' => (bool)$activeMedia['global_sync'],
            'start_time' => $activeMedia['start_time'],
            'server_time' => date('Y-m-d H:i:s'),
            'server_timestamp' => $currentTime,
            'youtube_id' => $activeMedia['youtube_id'],
            'youtube_url' => $activeMedia['youtube_url']
        ]
    ];
    
    echo json_encode($response);
}

function updateSyncState($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        echo json_encode(['success' => false, 'error' => 'Ação não especificada']);
        return;
    }
    
    switch ($input['action']) {
        case 'start_global_sync':
            startGlobalSync($pdo, $input);
            break;
        case 'stop_global_sync':
            stopGlobalSync($pdo);
            break;
        case 'update_position':
            updatePosition($pdo, $input);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
}

function startGlobalSync($pdo, $data) {
    $mediaId = $data['media_id'];
    $duration = $data['duration'] ?? null;
    $startPosition = $data['start_position'] ?? 0;
    
    // Calcular tempo de início global baseado na posição atual
    $globalStartTime = date('Y-m-d H:i:s', time() - $startPosition);
    
    // Atualizar mídia ativa com tempo de início
    $stmt = $pdo->prepare("
        UPDATE active_media 
        SET start_time = ?, duration = ?, global_sync = TRUE, loop_enabled = TRUE
        WHERE media_id = ?
    ");
    $stmt->execute([$globalStartTime, $duration, $mediaId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sincronização global iniciada',
        'start_time' => $globalStartTime,
        'server_time' => date('Y-m-d H:i:s')
    ]);
}

function stopGlobalSync($pdo) {
    $stmt = $pdo->prepare("UPDATE active_media SET global_sync = FALSE WHERE id = (SELECT MAX(id) FROM active_media am2)");
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Sincronização global parada']);
}

function updatePosition($pdo, $data) {
    $position = $data['position'] ?? 0;
    $mediaId = $data['media_id'];
    
    // Recalcular tempo de início baseado na nova posição
    $newStartTime = date('Y-m-d H:i:s', time() - $position);
    
    $stmt = $pdo->prepare("UPDATE active_media SET start_time = ? WHERE media_id = ?");
    $stmt->execute([$newStartTime, $mediaId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Posição atualizada',
        'new_start_time' => $newStartTime
    ]);
}
?>
