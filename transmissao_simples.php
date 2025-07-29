<?php
require_once 'app/config/conexao.php';

$pdo = conectar();
$message = '';
$messageType = '';

// Processar formulário
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'start_youtube') {
        $youtube_url = trim($_POST['youtube_url']);
        $title = trim($_POST['title']);
        
        if (!empty($youtube_url) && !empty($title)) {
            // Extrair ID do YouTube
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
            
            if (isset($matches[1])) {
                $youtube_id = $matches[1];
                
                try {
                    // Verificar se a tabela existe, se não, criar
                    $pdo->exec("CREATE TABLE IF NOT EXISTS live_streams (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        youtube_id VARCHAR(20) NULL,
                        youtube_url VARCHAR(500) NULL,
                        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    // Desativar streams anteriores
                    $pdo->exec("UPDATE live_streams SET status = 'inativo'");
                    
                    // Inserir nova stream
                    $stmt = $pdo->prepare("INSERT INTO live_streams (title, youtube_id, youtube_url, status, created_at) VALUES (?, ?, ?, 'ativo', NOW())");
                    $stmt->execute([$title, $youtube_id, $youtube_url]);
                    
                    $message = 'Transmissão iniciada com sucesso!';
                    $messageType = 'success';
                    
                } catch (Exception $e) {
                    $message = 'Erro ao iniciar transmissão: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = 'URL do YouTube inválida!';
                $messageType = 'error';
            }
        } else {
            $message = 'Preencha todos os campos!';
            $messageType = 'error';
        }
    }
    
    if ($action === 'stop_live') {
        try {
            $pdo->exec("UPDATE live_streams SET status = 'inativo'");
            $message = 'Transmissão parada com sucesso!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Erro ao parar transmissão: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Buscar transmissão ativa
$liveStream = null;
try {
    $stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
    $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Tabela pode não existir ainda
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmissão Simples - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #27ae60;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .live-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .live-player {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            margin-bottom: 20px;
        }
        
        .live-player iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 10px;
        }
        
        .live-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .live-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #FFD700;
        }
        
        .live-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .control-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ecf0f1;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .btn-full {
            width: 100%;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .no-live {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .no-live i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-broadcast-tower"></i> Transmissão Simples</h1>
            <p>Gerencie transmissões do YouTube de forma fácil</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Seção da Live Ativa -->
        <?php if ($liveStream): ?>
            <div class="live-section">
                <div class="live-info">
                    <div class="live-title"><?= htmlspecialchars($liveStream['title']) ?></div>
                    <div class="live-status">
                        <i class="fas fa-circle"></i> AO VIVO
                    </div>
                </div>
                
                <div class="live-player">
                    <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($liveStream['youtube_id']) ?>?autoplay=1&mute=1" 
                            allowfullscreen></iframe>
                </div>
                
                <div class="actions">
                    <a href="<?= htmlspecialchars($liveStream['youtube_url']) ?>" target="_blank" class="btn btn-primary">
                        <i class="fab fa-youtube"></i> Abrir no YouTube
                    </a>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="stop_live">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja parar a transmissão?')">
                            <i class="fas fa-stop"></i> Parar Transmissão
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="live-section">
                <div class="no-live">
                    <i class="fas fa-video-slash"></i>
                    <h3>Nenhuma transmissão ativa</h3>
                    <p>Inicie uma nova transmissão usando o formulário abaixo.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Controles -->
        <div class="control-section">
            <h3><i class="fas fa-play"></i> Iniciar Nova Transmissão</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="start_youtube">
                
                <div class="form-group">
                    <label for="title">Título da Transmissão:</label>
                    <input type="text" id="title" name="title" placeholder="Ex: Final - Time A vs Time B" required>
                </div>
                
                <div class="form-group">
                    <label for="youtube_url">URL do YouTube:</label>
                    <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                </div>
                
                <button type="submit" class="btn btn-success btn-full">
                    <i class="fab fa-youtube"></i> Iniciar Transmissão
                </button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="app/pages/adm/dashboard_simple.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>
</body>
</html>
