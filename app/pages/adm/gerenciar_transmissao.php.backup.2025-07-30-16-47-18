<?php
session_start();

// Verificar se é admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_simple.php');
    exit;
}

include '../../config/conexao.php';
$pdo = conectar();

// Verificar se há transmissão ativa
$stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
$liveStream = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar upload de mídia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file']) && !empty($_FILES['media_file']['name'])) {
    // Verificar se admin está logado
    if (!isset($_SESSION['admin_id'])) {
        $message = 'Você precisa estar logado como admin para fazer upload!';
        $messageType = 'error';
    } else {
        // Usar caminho relativo mais simples
        $uploadDir = __DIR__ . '/../../../public/uploads/media/';
        $mediaType = $_POST['media_type'] ?? '';
        $title = trim($_POST['media_title'] ?? '');
        $description = trim($_POST['media_description'] ?? '');

    $allowedTypes = [
        'music' => ['mp3', 'wav', 'ogg', 'm4a'],
        'video' => ['mp4', 'webm', 'ogg'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ];

    $file = $_FILES['media_file'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Debug: verificar erros de upload
    if ($fileError !== UPLOAD_ERR_OK) {
        switch ($fileError) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'Arquivo muito grande (limite do servidor)!';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'Arquivo muito grande (limite do formulário)!';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'Upload incompleto!';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'Nenhum arquivo selecionado!';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Pasta temporária não encontrada!';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Erro de permissão de escrita!';
                break;
            default:
                $message = 'Erro desconhecido no upload!';
        }
        $messageType = 'error';
    } elseif (empty($mediaType)) {
        $message = 'Selecione o tipo de mídia!';
        $messageType = 'error';
    } elseif (!in_array($fileExt, $allowedTypes[$mediaType])) {
        $message = 'Tipo de arquivo não permitido para ' . $mediaType . '!';
        $messageType = 'error';
    } else {
        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $mediaType . '/' . $newFileName;

        // Verificar se a pasta existe
        $targetDir = $uploadDir . $mediaType . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Verificar permissões
        if (!is_writable($targetDir)) {
            $message = 'Pasta de upload sem permissão de escrita! Pasta: ' . $targetDir . ' | Existe: ' . (is_dir($targetDir) ? 'Sim' : 'Não');
            $messageType = 'error';
        } elseif (move_uploaded_file($fileTmp, $uploadPath)) {
            $relativePath = 'public/uploads/media/' . $mediaType . '/' . $newFileName;

            try {
                $stmt = $pdo->prepare("INSERT INTO media_library (title, description, media_type, file_path, file_name, file_size, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $mediaType, $relativePath, $fileName, $fileSize, $_SESSION['admin_id']]);

                $message = 'Mídia enviada com sucesso!';
                $messageType = 'success';
            } catch (Exception $e) {
                // Se erro no banco, deletar arquivo
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                $message = 'Erro ao salvar no banco de dados: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Erro ao mover arquivo! Origem: ' . $fileTmp . ' | Destino: ' . $uploadPath . ' | Pasta gravável: ' . (is_writable($targetDir) ? 'Sim' : 'Não');
            $messageType = 'error';
        }
    }
    } // Fechar o if do admin_id
}

// Processar ações do admin
if (!$message) {
    $message = '';
    $messageType = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_live':
                $youtube_url = trim($_POST['youtube_url']);
                $title = trim($_POST['title']);
                
                if (!empty($youtube_url) && !empty($title)) {
                    // Extrair ID do YouTube
                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
                    
                    if (isset($matches[1])) {
                        $youtube_id = $matches[1];
                        
                        // Desativar streams anteriores
                        $pdo->query("UPDATE live_streams SET status = 'inativo'");
                        
                        // Inserir nova stream
                        $stmt = $pdo->prepare("INSERT INTO live_streams (title, youtube_id, youtube_url, status, admin_id, created_at) VALUES (?, ?, ?, 'ativo', ?, NOW())");
                        $stmt->execute([$title, $youtube_id, $youtube_url, $_SESSION['admin_id']]);
                        
                        $message = 'Transmissão iniciada com sucesso!';
                        $messageType = 'success';
                        
                        // Recarregar dados
                        $stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
                        $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $message = 'URL do YouTube inválida!';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Preencha todos os campos!';
                    $messageType = 'error';
                }
                break;

            case 'start_external_live':
                $external_url = trim($_POST['external_url']);
                $title = trim($_POST['title']);
                $embed_code = trim($_POST['embed_code']);

                if (!empty($external_url) && !empty($title)) {
                    // Desativar streams anteriores
                    $pdo->query("UPDATE live_streams SET status = 'inativo'");

                    // Inserir nova stream externa
                    $stmt = $pdo->prepare("INSERT INTO live_streams (title, external_url, embed_code, status, admin_id, stream_type, created_at) VALUES (?, ?, ?, 'ativo', ?, 'external', NOW())");
                    $stmt->execute([$title, $external_url, $embed_code, $_SESSION['admin_id']]);

                    $message = 'Transmissão externa iniciada com sucesso!';
                    $messageType = 'success';

                    // Recarregar dados
                    $stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
                    $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = 'Preencha todos os campos obrigatórios!';
                    $messageType = 'error';
                }
                break;
                
            case 'stop_live':
                $pdo->query("UPDATE live_streams SET status = 'inativo'");
                $pdo->query("DELETE FROM active_media WHERE media_type = 'youtube'");
                $message = 'Transmissão encerrada!';
                $messageType = 'info';
                $liveStream = null;
                break;

            case 'play_media':
                $media_id = $_POST['media_id'];
                $media_type = $_POST['media_type'];
                $title = $_POST['title'];
                $target_pages = $_POST['target_pages'] ?? null;
                $show_controls = isset($_POST['show_controls']) ? 1 : 0;

                // Parar mídia anterior
                $pdo->query("DELETE FROM active_media");

                // Preparar páginas alvo
                $pages_json = null;
                if (!empty($target_pages) && is_array($target_pages)) {
                    $pages_json = json_encode($target_pages);
                }

                // Buscar duração do arquivo se for mídia local
                $duration = null;
                if ($media_id) {
                    $stmt = $pdo->prepare("SELECT duration FROM media_library WHERE id = ?");
                    $stmt->execute([$media_id]);
                    $mediaInfo = $stmt->fetch();
                    $duration = $mediaInfo['duration'] ?? 180; // fallback 3 minutos
                }

                // Iniciar nova mídia com sincronização global
                $stmt = $pdo->prepare("INSERT INTO active_media (media_id, media_type, title, target_pages, show_controls, start_time, duration, loop_enabled, global_sync, admin_id, created_at) VALUES (?, ?, ?, ?, ?, NOW(), ?, TRUE, TRUE, ?, NOW())");
                $stmt->execute([$media_id, $media_type, $title, $pages_json, $show_controls, $duration, $_SESSION['admin_id']]);

                $message = 'Mídia iniciada com sincronização global!';
                $messageType = 'success';
                break;

            case 'stop_media':
                $pdo->query("DELETE FROM active_media");
                $message = 'Mídia parada!';
                $messageType = 'info';
                break;

            case 'delete_media':
                $media_id = $_POST['media_id'];

                // Buscar arquivo para deletar
                $stmt = $pdo->prepare("SELECT file_path FROM media_library WHERE id = ?");
                $stmt->execute([$media_id]);
                $media = $stmt->fetch();

                if ($media) {
                    $filePath = '../../../' . $media['file_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    // Deletar do banco
                    $stmt = $pdo->prepare("DELETE FROM media_library WHERE id = ?");
                    $stmt->execute([$media_id]);

                    // Parar se estiver tocando
                    $stmt = $pdo->prepare("DELETE FROM active_media WHERE media_id = ?");
                    $stmt->execute([$media_id]);

                    $message = 'Mídia deletada com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Mídia não encontrada!';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Processar agendamento de jogos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule_game') {
    $team1_id = $_POST['team1_id'];
    $team2_id = $_POST['team2_id'];
    $match_date = $_POST['match_date'];
    $match_time = $_POST['match_time'];
    $group_id = $_POST['group_id'] ?? null;

    if (!empty($team1_id) && !empty($team2_id) && !empty($match_date) && !empty($match_time)) {
        $datetime = $match_date . ' ' . $match_time;

        // Verificar se há torneio ativo
        require_once '../../classes/TournamentManager.php';
        $tournamentManager = new TournamentManager($pdo);
        $tournament = $tournamentManager->getCurrentTournament();

        if ($tournament) {
            $stmt = $pdo->prepare("INSERT INTO matches (tournament_id, team1_id, team2_id, group_id, match_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'agendado', NOW())");
            $stmt->execute([$tournament['id'], $team1_id, $team2_id, $group_id, $datetime]);

            $message = 'Jogo agendado com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Nenhum torneio ativo encontrado!';
            $messageType = 'error';
        }
    } else {
        $message = 'Preencha todos os campos obrigatórios!';
        $messageType = 'error';
    }
}

// Buscar próximos 3 jogos agendados
require_once '../../classes/TournamentManager.php';
$tournamentManager = new TournamentManager($pdo);
$tournament = $tournamentManager->getCurrentTournament();

$upcomingGames = [];
if ($tournament) {
    $stmt = $pdo->prepare("
        SELECT m.*,
               t1.nome as team1_name, t1.logo as team1_logo,
               t2.nome as team2_name, t2.logo as team2_logo,
               g.nome as group_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN grupos g ON m.group_id = g.id
        WHERE m.tournament_id = ?
        AND m.match_date >= NOW()
        AND m.status = 'agendado'
        ORDER BY m.match_date ASC
        LIMIT 3
    ");
    $stmt->execute([$tournament['id']]);
    $upcomingGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar times para o formulário
    $stmt = $pdo->prepare("SELECT id, nome FROM times WHERE tournament_id = ? ORDER BY nome");
    $stmt->execute([$tournament['id']]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar grupos para o formulário
    $stmt = $pdo->prepare("SELECT id, nome FROM grupos WHERE tournament_id = ? ORDER BY nome");
    $stmt->execute([$tournament['id']]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar mídia ativa
$stmt = $pdo->query("SELECT * FROM active_media ORDER BY created_at DESC LIMIT 1");
$activeMedia = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar biblioteca de mídia
$stmt = $pdo->prepare("SELECT * FROM media_library WHERE is_active = 1 ORDER BY created_at DESC");
$stmt->execute();
$mediaLibrary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar histórico de transmissões
$stmt = $pdo->prepare("SELECT * FROM live_streams ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$streamHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Transmissão - Admin</title>
    <link rel="stylesheet" href="../../../public/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .admin-header {
            background-color: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .admin-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .admin-subtitle {
            color: #95a5a6;
            font-size: 1.1rem;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: #7B1FA2;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #9C27B0;
            transform: translateY(-2px);
        }

        .control-section {
            background-color: #1E1E1E;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #E1BEE7;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #E0E0E0;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background-color: #2A2A2A;
            border: 2px solid #444;
            border-radius: 8px;
            color: #E0E0E0;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #7B1FA2;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #7B1FA2;
            color: white;
        }

        .btn-primary:hover {
            background-color: #9C27B0;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: #F44336;
            color: white;
        }

        .btn-danger:hover {
            background-color: #E53935;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #777;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #66BB6A;
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #EF5350;
        }

        .alert-info {
            background-color: rgba(33, 150, 243, 0.2);
            border: 1px solid #2196F3;
            color: #64B5F6;
        }

        .live-status {
            background-color: #2A2A2A;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .status-active {
            border-left: 4px solid #4CAF50;
        }

        .status-inactive {
            border-left: 4px solid #666;
        }

        .live-indicator {
            background: linear-gradient(45deg, #ff0000, #ff4444);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 0.8rem;
            animation: pulse 2s infinite;
            display: inline-block;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }

        .history-table th {
            background-color: #2A2A2A;
            color: #E1BEE7;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-ativo {
            background-color: rgba(76, 175, 80, 0.2);
            color: #66BB6A;
        }

        .status-inativo {
            background-color: rgba(158, 158, 158, 0.2);
            color: #9E9E9E;
        }

        /* Estilos para agendamento de jogos */
        .schedule-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        select {
            width: 100%;
            padding: 12px 16px;
            background-color: #2A2A2A;
            border: 2px solid #444;
            border-radius: 8px;
            color: #E0E0E0;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: #7B1FA2;
        }

        .upcoming-games {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .game-card {
            background-color: #2A2A2A;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .game-card:hover {
            border-color: #7B1FA2;
            transform: translateY(-2px);
        }

        .game-date {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .game-date .date {
            font-size: 1.2rem;
            font-weight: 700;
            color: #E1BEE7;
        }

        .game-date .time {
            font-size: 1rem;
            color: #95a5a6;
            margin-top: 5px;
        }

        .game-teams {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .team {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .team-logo-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logo-placeholder-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #1E1E1E;
            border: 2px solid #7B1FA2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #E1BEE7;
        }

        .team-name-small {
            font-weight: 600;
            color: white;
            text-align: center;
            font-size: 0.9rem;
        }

        .vs-small {
            font-weight: 700;
            color: #7B1FA2;
            font-size: 1rem;
            margin: 0 10px;
        }

        .game-group-small {
            text-align: center;
            background-color: rgba(123, 31, 162, 0.2);
            color: #E1BEE7;
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #7B1FA2;
        }

        .no-games-admin {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 20px;
            color: #95a5a6;
            background-color: #2A2A2A;
            border-radius: 8px;
        }

        .no-games-admin i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        /* Estilos para seção de mídia */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .media-card {
            background-color: #2A2A2A;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .media-card:hover {
            border-color: #7B1FA2;
            transform: translateY(-2px);
        }

        .media-card.active {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
        }

        .media-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .media-type-music {
            background-color: rgba(255, 152, 0, 0.2);
            color: #FFB74D;
        }

        .media-type-video {
            background-color: rgba(244, 67, 54, 0.2);
            color: #EF5350;
        }

        .media-type-image {
            background-color: rgba(33, 150, 243, 0.2);
            color: #64B5F6;
        }

        .media-title {
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .media-description {
            color: #95a5a6;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .media-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .upload-area {
            border: 2px dashed #7B1FA2;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background-color: rgba(123, 31, 162, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #E1BEE7;
            background-color: rgba(123, 31, 162, 0.1);
        }

        .upload-area.dragover {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: #7B1FA2;
            margin-bottom: 15px;
        }

        .file-input {
            display: none;
        }

        .media-player {
            background-color: #1E1E1E;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .player-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .volume-slider {
            width: 100px;
        }

        /* Modal para configuração de mídia */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            overflow-y: auto;
            padding: 20px 0;
        }

        .modal-content {
            background-color: #1E1E1E;
            margin: 20px auto;
            padding: 30px;
            border-radius: 12px;
            width: 95%;
            max-width: 600px;
            border: 2px solid #7B1FA2;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        /* Scrollbar personalizada para o modal */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #2A2A2A;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #7B1FA2;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #9C27B0;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            color: #E1BEE7;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .close {
            color: #95a5a6;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #E1BEE7;
        }

        .checkbox-group {
            margin: 15px 0;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #E0E0E0;
            font-weight: 500;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #7B1FA2;
        }

        .pages-section {
            margin: 20px 0;
        }

        .pages-section h4 {
            color: #E1BEE7;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .page-option {
            background-color: #2A2A2A;
            border: 2px solid #444;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .page-option:hover {
            border-color: #7B1FA2;
        }

        .page-option.selected {
            border-color: #7B1FA2;
            background-color: rgba(123, 31, 162, 0.2);
        }

        .page-option input[type="checkbox"] {
            margin-right: 8px;
        }

        .controls-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #2A2A2A;
            border-radius: 8px;
        }

        .controls-section h4 {
            color: #E1BEE7;
            margin-bottom: 10px;
        }

        .control-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #444;
            transition: 0.4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #7B1FA2;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 20px 15px;
            }

            .admin-title {
                font-size: 2rem;
            }

            .back-button {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 20px;
                width: fit-content;
            }

            .schedule-form {
                grid-template-columns: 1fr;
            }

            .form-row {
                flex-direction: column;
            }

            .upcoming-games {
                grid-template-columns: 1fr;
            }

            .game-teams {
                flex-direction: column;
                gap: 10px;
            }

            .team {
                flex-direction: row;
                gap: 10px;
            }

            .media-grid {
                grid-template-columns: 1fr;
            }

            .media-actions {
                justify-content: center;
            }

            .modal-content {
                margin: 1% auto;
                width: 98%;
                max-width: none;
                padding: 20px;
                max-height: 95vh;
            }

            .pages-grid {
                grid-template-columns: 1fr;
            }

            .modal-title {
                font-size: 1.2rem;
            }
        }

        /* Estilos para Seletor de Tipo de Transmissão */
        .form-help {
            display: block;
            margin-top: 5px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }

        .transmission-type-selector {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .transmission-type-selector h3 {
            margin-bottom: 20px;
            color: #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .type-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .type-option {
            cursor: pointer;
            display: block;
        }

        .type-option input[type="radio"] {
            display: none;
        }

        .option-content {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
        }

        .option-content i {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 5px;
        }

        .option-content span {
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
        }

        .option-content small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .type-option input[type="radio"]:checked + .option-content {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.15);
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.3);
        }

        .type-option input[type="radio"]:checked + .option-content i {
            color: #5dade2;
        }

        .type-option:hover .option-content {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }

        .transmission-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .type-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="dashboard_simple.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
    </a>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Gerenciar Transmissão</h1>
            <p class="admin-subtitle">Controle as transmissões ao vivo da Copa das Panelas</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="control-section">
            <h2 class="section-title">
                <i class="fas fa-broadcast-tower"></i> Status da Transmissão
            </h2>

            <?php if ($liveStream): ?>
                <div class="live-status status-active">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <div class="live-indicator">
                            <i class="fas fa-circle"></i> AO VIVO
                        </div>
                        <h3><?= htmlspecialchars($liveStream['title']) ?></h3>
                    </div>
                    <p><strong>Iniciada em:</strong> <?= date('d/m/Y H:i', strtotime($liveStream['created_at'])) ?></p>
                    <p><strong>URL:</strong> <a href="<?= htmlspecialchars($liveStream['youtube_url']) ?>" target="_blank" style="color: #64B5F6;"><?= htmlspecialchars($liveStream['youtube_url']) ?></a></p>
                    
                    <div style="margin-top: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="stop_live">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja encerrar a transmissão?')">
                                <i class="fas fa-stop"></i> Encerrar Transmissão
                            </button>
                        </form>
                        <a href="<?= htmlspecialchars($liveStream['youtube_url']) ?>" target="_blank" class="btn btn-secondary">
                            <i class="fab fa-youtube"></i> Abrir no YouTube
                        </a>
                        <a href="../JogosProximos.php" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> Ver Página Pública
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="live-status status-inactive">
                    <h3><i class="fas fa-video-slash"></i> Nenhuma transmissão ativa</h3>
                    <p>Inicie uma nova transmissão preenchendo o formulário abaixo.</p>
                </div>

                <!-- Seletor de Tipo de Transmissão -->
                <div class="transmission-type-selector">
                    <h3><i class="fas fa-broadcast-tower"></i> Tipo de Transmissão</h3>
                    <div class="type-options">
                        <label class="type-option">
                            <input type="radio" name="transmission_type" value="youtube" checked onchange="toggleTransmissionType()">
                            <div class="option-content">
                                <i class="fab fa-youtube"></i>
                                <span>Live do YouTube</span>
                                <small>Transmitir diretamente do YouTube</small>
                            </div>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="transmission_type" value="external" onchange="toggleTransmissionType()">
                            <div class="option-content">
                                <i class="fas fa-external-link-alt"></i>
                                <span>Live Externa</span>
                                <small>URL de qualquer plataforma</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Formulário YouTube -->
                <form method="POST" action="" id="youtube_form" class="transmission-form">
                    <input type="hidden" name="action" value="start_live">
                    <input type="hidden" name="type" value="youtube">

                    <div class="form-group">
                        <label for="title_youtube">Título da Transmissão:</label>
                        <input type="text" id="title_youtube" name="title" placeholder="Ex: Final - Time A vs Time B" required>
                    </div>

                    <div class="form-group">
                        <label for="youtube_url">URL do YouTube (Live):</label>
                        <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                        <small class="form-help">Cole aqui o link da sua live do YouTube</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fab fa-youtube"></i> Iniciar Live do YouTube
                    </button>
                </form>

                <!-- Formulário Live Externa -->
                <form method="POST" action="" id="external_form" class="transmission-form" style="display: none;">
                    <input type="hidden" name="action" value="start_external_live">
                    <input type="hidden" name="type" value="external">

                    <div class="form-group">
                        <label for="title_external">Título da Transmissão:</label>
                        <input type="text" id="title_external" name="title" placeholder="Ex: Final - Time A vs Time B" required>
                    </div>

                    <div class="form-group">
                        <label for="external_url">URL da Live Externa:</label>
                        <input type="url" id="external_url" name="external_url" placeholder="https://..." required>
                        <small class="form-help">Cole aqui o link de qualquer plataforma (Twitch, Facebook, etc.)</small>
                    </div>

                    <div class="form-group">
                        <label for="embed_code">Código de Incorporação (Opcional):</label>
                        <textarea id="embed_code" name="embed_code" placeholder="<iframe src=... ou código embed da plataforma" rows="4"></textarea>
                        <small class="form-help">Se disponível, cole o código iframe/embed para melhor integração</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-broadcast-tower"></i> Iniciar Live Externa
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="control-section">
            <h2 class="section-title">
                <i class="fas fa-calendar-plus"></i> Agendar Jogo
            </h2>

            <?php if ($tournament && !empty($teams)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="schedule_game">

                    <div class="schedule-form">
                        <div class="form-group">
                            <label for="team1_id">Time 1:</label>
                            <select id="team1_id" name="team1_id" required>
                                <option value="">Selecione o primeiro time</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="team2_id">Time 2:</label>
                            <select id="team2_id" name="team2_id" required>
                                <option value="">Selecione o segundo time</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="match_date">Data do Jogo:</label>
                            <input type="date" id="match_date" name="match_date" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label for="match_time">Horário:</label>
                            <input type="time" id="match_time" name="match_time" required>
                        </div>

                        <div class="form-group">
                            <label for="group_id">Grupo (opcional):</label>
                            <select id="group_id" name="group_id">
                                <option value="">Selecione um grupo</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Agendar Jogo
                    </button>
                </form>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6; padding: 20px;">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Não é possível agendar jogos. Verifique se há um torneio ativo e times cadastrados.
                </p>
            <?php endif; ?>
        </div>

        <div class="control-section">
            <h2 class="section-title">
                <i class="fas fa-clock"></i> Próximos 3 Jogos Agendados
            </h2>

            <?php if (!empty($upcomingGames)): ?>
                <div class="upcoming-games">
                    <?php foreach ($upcomingGames as $game): ?>
                        <?php
                        $matchDate = new DateTime($game['match_date']);
                        $now = new DateTime();
                        $isToday = $matchDate->format('Y-m-d') === $now->format('Y-m-d');
                        ?>
                        <div class="game-card">
                            <div class="game-date">
                                <div class="date">
                                    <?= $matchDate->format('d/m/Y') ?>
                                    <?php if ($isToday): ?>
                                        <span style="color: #FF9800; font-weight: 700;"> (HOJE)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="time"><?= $matchDate->format('H:i') ?></div>
                            </div>

                            <div class="game-teams">
                                <div class="team">
                                    <?php if (!empty($game['team1_logo'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($game['team1_logo']) ?>" alt="Logo" class="team-logo-small">
                                    <?php else: ?>
                                        <div class="logo-placeholder-small"><i class="fas fa-shield-alt"></i></div>
                                    <?php endif; ?>
                                    <span class="team-name-small"><?= htmlspecialchars($game['team1_name']) ?></span>
                                </div>

                                <div class="vs-small">VS</div>

                                <div class="team">
                                    <?php if (!empty($game['team2_logo'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($game['team2_logo']) ?>" alt="Logo" class="team-logo-small">
                                    <?php else: ?>
                                        <div class="logo-placeholder-small"><i class="fas fa-shield-alt"></i></div>
                                    <?php endif; ?>
                                    <span class="team-name-small"><?= htmlspecialchars($game['team2_name']) ?></span>
                                </div>
                            </div>

                            <?php if (!empty($game['group_name'])): ?>
                                <div class="game-group-small"><?= htmlspecialchars($game['group_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-games-admin">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nenhum jogo agendado</h3>
                    <p>Não há jogos programados nos próximos dias.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="control-section">
            <h2 class="section-title">
                <i class="fas fa-music"></i> Gerenciar Mídia
            </h2>

            <!-- Player de Mídia Ativa -->
            <?php if ($activeMedia): ?>
                <div class="media-player">
                    <h3><i class="fas fa-play-circle"></i> Mídia Ativa: <?= htmlspecialchars($activeMedia['title']) ?></h3>

                    <?php if ($activeMedia['media_type'] === 'youtube'): ?>
                        <p><strong>Tipo:</strong> Transmissão YouTube</p>
                        <p><strong>URL:</strong> <a href="<?= htmlspecialchars($activeMedia['youtube_url']) ?>" target="_blank" style="color: #64B5F6;"><?= htmlspecialchars($activeMedia['youtube_url']) ?></a></p>
                    <?php else: ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM media_library WHERE id = ?");
                        $stmt->execute([$activeMedia['media_id']]);
                        $mediaFile = $stmt->fetch();
                        ?>

                        <?php if ($mediaFile): ?>
                            <p><strong>Tipo:</strong> <?= ucfirst($activeMedia['media_type']) ?></p>
                            <p><strong>Arquivo:</strong> <?= htmlspecialchars($mediaFile['file_name']) ?></p>

                            <?php if ($activeMedia['start_time']): ?>
                                <?php
                                $startTime = strtotime($activeMedia['start_time']);
                                $currentTime = time();
                                $elapsedTime = $currentTime - $startTime;
                                $duration = $activeMedia['duration'] ?: 180;

                                if ($activeMedia['loop_enabled'] && $duration > 0) {
                                    $currentPosition = fmod($elapsedTime, $duration);
                                } else {
                                    $currentPosition = $elapsedTime;
                                }

                                $minutes = floor($currentPosition / 60);
                                $seconds = floor($currentPosition % 60);
                                ?>
                                <p><strong>Sincronização Global:</strong>
                                    <span style="color: #4CAF50;">✓ Ativa</span>
                                </p>
                                <p><strong>Posição Atual:</strong>
                                    <span style="color: #E1BEE7; font-weight: 600;">
                                        <?= sprintf('%d:%02d', $minutes, $seconds) ?>
                                        <?php if ($activeMedia['loop_enabled']): ?>
                                            / <?= sprintf('%d:%02d', floor($duration / 60), $duration % 60) ?>
                                        <?php endif; ?>
                                    </span>
                                </p>
                                <p><strong>Iniciado em:</strong> <?= date('d/m/Y H:i:s', $startTime) ?></p>
                                <?php if ($activeMedia['loop_enabled']): ?>
                                    <p><strong>Modo:</strong> <span style="color: #FFB74D;">🔄 Loop Contínuo</span></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($activeMedia['media_type'] === 'music'): ?>
                                <audio controls style="width: 100%; margin: 10px 0;">
                                    <source src="../../../<?= htmlspecialchars($mediaFile['file_path']) ?>" type="audio/mpeg">
                                    Seu navegador não suporta o elemento de áudio.
                                </audio>
                            <?php elseif ($activeMedia['media_type'] === 'video'): ?>
                                <video controls style="width: 100%; max-height: 300px; margin: 10px 0;">
                                    <source src="../../../<?= htmlspecialchars($mediaFile['file_path']) ?>" type="video/mp4">
                                    Seu navegador não suporta o elemento de vídeo.
                                </video>
                            <?php elseif ($activeMedia['media_type'] === 'image'): ?>
                                <img src="../../../<?= htmlspecialchars($mediaFile['file_path']) ?>" alt="Imagem ativa" style="max-width: 100%; max-height: 300px; margin: 10px 0; border-radius: 8px;">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="player-controls">
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="stop_media">
                            <button type="submit" class="btn btn-danger btn-small">
                                <i class="fas fa-stop"></i> Parar Mídia
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Upload de Nova Mídia -->
            <div class="upload-area" onclick="document.getElementById('media_file').click()">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3>Adicionar Nova Mídia</h3>
                <p>Clique aqui ou arraste arquivos para fazer upload</p>
                <p><small>Suportado: MP3, WAV, MP4, JPG, PNG</small></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" id="media-upload-form">
                <input type="file" id="media_file" name="media_file" class="file-input" accept=".mp3,.wav,.ogg,.m4a,.mp4,.webm,.jpg,.jpeg,.png,.gif,.webp">

                <div class="form-row" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="media_type">Tipo de Mídia:</label>
                        <select id="media_type" name="media_type" required>
                            <option value="">Selecione o tipo</option>
                            <option value="music">Música</option>
                            <option value="video">Vídeo</option>
                            <option value="image">Imagem</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="media_title">Título:</label>
                        <input type="text" id="media_title" name="media_title" placeholder="Nome da mídia" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="media_description">Descrição (opcional):</label>
                    <input type="text" id="media_description" name="media_description" placeholder="Descrição da mídia">
                </div>

                <button type="submit" class="btn btn-primary" id="upload-btn" style="display: none;">
                    <i class="fas fa-upload"></i> Fazer Upload
                </button>
            </form>

            <!-- Biblioteca de Mídia -->
            <h3 style="margin-top: 40px; margin-bottom: 20px;">
                <i class="fas fa-folder-open"></i> Biblioteca de Mídia
            </h3>

            <?php if (!empty($mediaLibrary)): ?>
                <div class="media-grid">
                    <?php foreach ($mediaLibrary as $media): ?>
                        <div class="media-card <?= ($activeMedia && $activeMedia['media_id'] == $media['id']) ? 'active' : '' ?>">
                            <div class="media-type-badge media-type-<?= $media['media_type'] ?>">
                                <?= $media['media_type'] ?>
                            </div>

                            <div class="media-title"><?= htmlspecialchars($media['title']) ?></div>

                            <?php if (!empty($media['description'])): ?>
                                <div class="media-description"><?= htmlspecialchars($media['description']) ?></div>
                            <?php endif; ?>

                            <div style="margin-bottom: 15px;">
                                <small style="color: #95a5a6;">
                                    <i class="fas fa-file"></i> <?= htmlspecialchars($media['file_name']) ?><br>
                                    <i class="fas fa-hdd"></i> <?= number_format($media['file_size'] / 1024 / 1024, 2) ?> MB<br>
                                    <i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($media['created_at'])) ?>
                                </small>
                            </div>

                            <!-- Preview da mídia -->
                            <?php if ($media['media_type'] === 'image'): ?>
                                <img src="../../../<?= htmlspecialchars($media['file_path']) ?>" alt="Preview" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;">
                            <?php elseif ($media['media_type'] === 'music'): ?>
                                <audio controls style="width: 100%; margin-bottom: 15px;">
                                    <source src="../../../<?= htmlspecialchars($media['file_path']) ?>" type="audio/mpeg">
                                </audio>
                            <?php elseif ($media['media_type'] === 'video'): ?>
                                <video controls style="width: 100%; height: 120px; margin-bottom: 15px;">
                                    <source src="../../../<?= htmlspecialchars($media['file_path']) ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>

                            <div class="media-actions">
                                <?php if (!$activeMedia || $activeMedia['media_id'] != $media['id']): ?>
                                    <button type="button" class="btn btn-primary btn-small" onclick="openMediaModal(<?= $media['id'] ?>, '<?= $media['media_type'] ?>', '<?= htmlspecialchars($media['title']) ?>')">
                                        <i class="fas fa-play"></i> Reproduzir
                                    </button>
                                <?php else: ?>
                                    <span class="btn btn-secondary btn-small" style="cursor: default;">
                                        <i class="fas fa-play-circle"></i> Reproduzindo
                                    </span>
                                <?php endif; ?>

                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta mídia?')">
                                    <input type="hidden" name="action" value="delete_media">
                                    <input type="hidden" name="media_id" value="<?= $media['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">
                                        <i class="fas fa-trash"></i> Deletar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: #95a5a6; padding: 40px; background-color: #2A2A2A; border-radius: 8px;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h3>Nenhuma mídia encontrada</h3>
                    <p>Faça upload de músicas, vídeos ou imagens para começar.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="control-section">
            <h2 class="section-title">
                <i class="fas fa-history"></i> Histórico de Transmissões
            </h2>

            <?php if (!empty($streamHistory)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Data/Hora</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($streamHistory as $stream): ?>
                            <tr>
                                <td><?= htmlspecialchars($stream['title']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($stream['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $stream['status'] ?>">
                                        <?= ucfirst($stream['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= htmlspecialchars($stream['youtube_url']) ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                                        <i class="fab fa-youtube"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6; padding: 40px;">
                    <i class="fas fa-inbox"></i><br>
                    Nenhuma transmissão encontrada no histórico.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Configuração de Mídia -->
    <div id="mediaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Configurar Reprodução</h3>
                <span class="close" onclick="closeMediaModal()">&times;</span>
            </div>

            <form method="POST" action="" id="mediaConfigForm">
                <input type="hidden" name="action" value="play_media">
                <input type="hidden" name="media_id" id="modal_media_id">
                <input type="hidden" name="media_type" id="modal_media_type">
                <input type="hidden" name="title" id="modal_title">

                <div class="pages-section">
                    <h4><i class="fas fa-globe"></i> Páginas onde reproduzir:</h4>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" id="all_pages" onchange="toggleAllPages()">
                            <strong>Todas as páginas</strong>
                        </label>
                    </div>

                    <div class="pages-grid" id="pages_grid">
                        <div class="page-option">
                            <label>
                                <input type="checkbox" name="target_pages[]" value="JogosProximos">
                                <i class="fas fa-tv"></i> Página de Transmissão
                            </label>
                        </div>
                        <div class="page-option">
                            <label>
                                <input type="checkbox" name="target_pages[]" value="HomePage2">
                                <i class="fas fa-home"></i> Página Inicial
                            </label>
                        </div>
                        <div class="page-option">
                            <label>
                                <input type="checkbox" name="target_pages[]" value="tabela_de_classificacao">
                                <i class="fas fa-table"></i> Tabela de Classificação
                            </label>
                        </div>
                        <div class="page-option">
                            <label>
                                <input type="checkbox" name="target_pages[]" value="exibir_finais">
                                <i class="fas fa-trophy"></i> Eliminatórias
                            </label>
                        </div>
                        <div class="page-option">
                            <label>
                                <input type="checkbox" name="target_pages[]" value="rodadas">
                                <i class="fas fa-calendar"></i> Rodadas
                            </label>
                        </div>
                        <div class="page-option">
                            <label>
                                <input type="checkbox" name="target_pages[]" value="publicacoes">
                                <i class="fas fa-newspaper"></i> Publicações
                            </label>
                        </div>
                    </div>
                </div>

                <div class="controls-section" id="controls_section">
                    <h4><i class="fas fa-cog"></i> Controles Visuais:</h4>
                    <div class="control-option">
                        <label class="switch">
                            <input type="checkbox" name="show_controls" id="show_controls" checked>
                            <span class="slider"></span>
                        </label>
                        <span>Mostrar controles de reprodução para usuários</span>
                    </div>
                    <p style="color: #95a5a6; font-size: 0.9rem; margin-top: 10px;">
                        <i class="fas fa-info-circle"></i>
                        Quando desabilitado, a música tocará em segundo plano sem interface visível.
                    </p>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeMediaModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play"></i> Iniciar Reprodução
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para alternar entre tipos de transmissão
        function toggleTransmissionType() {
            const youtubeRadio = document.querySelector('input[name="transmission_type"][value="youtube"]');
            const externalRadio = document.querySelector('input[name="transmission_type"][value="external"]');
            const youtubeForm = document.getElementById('youtube_form');
            const externalForm = document.getElementById('external_form');

            if (youtubeRadio && youtubeRadio.checked) {
                youtubeForm.style.display = 'block';
                externalForm.style.display = 'none';
            } else if (externalRadio && externalRadio.checked) {
                youtubeForm.style.display = 'none';
                externalForm.style.display = 'block';
            }
        }

        // Validação do formulário
        document.addEventListener('DOMContentLoaded', function() {
            // Validação do formulário de transmissão
            const liveForm = document.querySelector('form[method="POST"]');
            if (liveForm && liveForm.querySelector('input[name="action"][value="start_live"]')) {
                liveForm.addEventListener('submit', function(e) {
                    const youtubeUrl = document.getElementById('youtube_url').value;
                    const title = document.getElementById('title').value;

                    if (!youtubeUrl || !title) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos!');
                        return;
                    }

                    // Validar URL do YouTube
                    const youtubeRegex = /^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/;
                    if (!youtubeRegex.test(youtubeUrl)) {
                        e.preventDefault();
                        alert('Por favor, insira uma URL válida do YouTube!');
                        return;
                    }
                });
            }

            // Validação do formulário de agendamento
            const scheduleForm = document.querySelector('form[method="POST"] input[name="action"][value="schedule_game"]');
            if (scheduleForm) {
                const form = scheduleForm.closest('form');
                const team1Select = document.getElementById('team1_id');
                const team2Select = document.getElementById('team2_id');

                // Função para validar times diferentes
                function validateTeams() {
                    if (team1Select.value && team2Select.value && team1Select.value === team2Select.value) {
                        alert('Os times devem ser diferentes!');
                        team2Select.value = '';
                        return false;
                    }
                    return true;
                }

                // Adicionar eventos de mudança
                team1Select.addEventListener('change', validateTeams);
                team2Select.addEventListener('change', validateTeams);

                // Validação no envio do formulário
                form.addEventListener('submit', function(e) {
                    const team1 = document.getElementById('team1_id').value;
                    const team2 = document.getElementById('team2_id').value;
                    const matchDate = document.getElementById('match_date').value;
                    const matchTime = document.getElementById('match_time').value;

                    if (!team1 || !team2 || !matchDate || !matchTime) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios!');
                        return;
                    }

                    if (team1 === team2) {
                        e.preventDefault();
                        alert('Os times devem ser diferentes!');
                        return;
                    }

                    // Validar se a data não é no passado
                    const selectedDate = new Date(matchDate + ' ' + matchTime);
                    const now = new Date();

                    if (selectedDate <= now) {
                        e.preventDefault();
                        alert('A data e horário do jogo devem ser no futuro!');
                        return;
                    }
                });
            }
        });

        // Gerenciamento de upload de mídia
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('media_file');
            const uploadBtn = document.getElementById('upload-btn');
            const uploadArea = document.querySelector('.upload-area');
            const mediaTypeSelect = document.getElementById('media_type');

            // Mostrar botão de upload quando arquivo for selecionado
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    uploadBtn.style.display = 'inline-flex';

                    // Auto-detectar tipo de mídia baseado na extensão
                    const fileName = this.files[0].name.toLowerCase();
                    if (fileName.match(/\.(mp3|wav|ogg|m4a)$/)) {
                        mediaTypeSelect.value = 'music';
                    } else if (fileName.match(/\.(mp4|webm|ogg)$/)) {
                        mediaTypeSelect.value = 'video';
                    } else if (fileName.match(/\.(jpg|jpeg|png|gif|webp)$/)) {
                        mediaTypeSelect.value = 'image';
                    }

                    // Auto-preencher título baseado no nome do arquivo
                    const titleInput = document.getElementById('media_title');
                    if (!titleInput.value) {
                        const nameWithoutExt = this.files[0].name.replace(/\.[^/.]+$/, "");
                        titleInput.value = nameWithoutExt;
                    }
                }
            });

            // Drag and drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        });

        // Funções do modal de configuração
        function openMediaModal(mediaId, mediaType, title) {
            document.getElementById('modal_media_id').value = mediaId;
            document.getElementById('modal_media_type').value = mediaType;
            document.getElementById('modal_title').value = title;

            // Mostrar/ocultar seção de controles baseado no tipo
            const controlsSection = document.getElementById('controls_section');
            if (mediaType === 'music') {
                controlsSection.style.display = 'block';
                // Para música, desmarcar controles por padrão
                document.getElementById('show_controls').checked = false;
            } else {
                controlsSection.style.display = 'block';
                // Para vídeo/imagem, manter controles por padrão
                document.getElementById('show_controls').checked = true;
            }

            // Marcar "todas as páginas" por padrão
            document.getElementById('all_pages').checked = true;
            toggleAllPages();

            document.getElementById('mediaModal').style.display = 'block';
        }

        function closeMediaModal() {
            document.getElementById('mediaModal').style.display = 'none';
        }

        function toggleAllPages() {
            const allPagesCheckbox = document.getElementById('all_pages');
            const pageCheckboxes = document.querySelectorAll('input[name="target_pages[]"]');
            const pagesGrid = document.getElementById('pages_grid');

            if (allPagesCheckbox.checked) {
                pagesGrid.style.opacity = '0.5';
                pagesGrid.style.pointerEvents = 'none';
                pageCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                });
            } else {
                pagesGrid.style.opacity = '1';
                pagesGrid.style.pointerEvents = 'auto';
                pageCheckboxes.forEach(checkbox => {
                    checkbox.disabled = false;
                });
            }
        }

        // Fechar modal clicando fora
        window.onclick = function(event) {
            const modal = document.getElementById('mediaModal');
            if (event.target === modal) {
                closeMediaModal();
            }
        }

        // Atualizar visual das opções de página
        document.addEventListener('DOMContentLoaded', function() {
            const pageOptions = document.querySelectorAll('.page-option');
            pageOptions.forEach(option => {
                const checkbox = option.querySelector('input[type="checkbox"]');
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                });
            });
        });

        // Auto-refresh a cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
