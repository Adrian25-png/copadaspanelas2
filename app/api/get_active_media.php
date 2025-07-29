<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    include '../config/conexao.php';
    $pdo = conectar();
    
    // Buscar mídia ativa
    $stmt = $pdo->query("SELECT * FROM active_media ORDER BY created_at DESC LIMIT 1");
    $activeMedia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activeMedia) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma mídia ativa']);
        exit;
    }
    
    // Buscar dados do arquivo se for mídia local
    $mediaFile = null;
    if ($activeMedia['media_id']) {
        $stmt = $pdo->prepare("SELECT * FROM media_library WHERE id = ?");
        $stmt->execute([$activeMedia['media_id']]);
        $mediaFile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Preparar resposta
    $response = [
        'success' => true,
        'media' => [
            'media_id' => $activeMedia['media_id'],
            'media_type' => $activeMedia['media_type'],
            'title' => $activeMedia['title'],
            'target_pages' => $activeMedia['target_pages'],
            'show_controls' => (bool)$activeMedia['show_controls'],
            'file_path' => $mediaFile ? $mediaFile['file_path'] : null,
            'file_name' => $mediaFile ? $mediaFile['file_name'] : null,
            'youtube_id' => $activeMedia['youtube_id'],
            'youtube_url' => $activeMedia['youtube_url']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
