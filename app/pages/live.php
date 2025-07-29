<?php
require_once '../config/conexao.php';

$pdo = conectar();

// Buscar transmissão ativa
$liveStream = null;
try {
    $stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
    $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Tabela pode não existir
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $liveStream ? htmlspecialchars($liveStream['title']) . ' - ' : '' ?>Transmissão Ao Vivo - Copa das Panelas</title>
    <link rel="stylesheet" href="../../public/css/global_standards.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    
    <style>
        .main-content {
            margin-top: 250px;
            padding: 20px;
            padding-bottom: 60px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-bottom: 40px;
        }
        
        .live-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .live-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #FFD700;
        }
        
        .live-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .live-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }
        
        .live-player {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            cursor: pointer;
        }

        .live-player iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            pointer-events: auto;
        }

        .player-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 10;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .live-player:hover .player-overlay {
            opacity: 1;
        }

        .fullscreen-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .fullscreen-btn:hover {
            background: rgba(0, 0, 0, 0.95);
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .fullscreen-btn i {
            display: block;
        }

        /* Estilo para tela cheia personalizada */
        .custom-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: black;
            z-index: 9999;
            display: none;
        }

        .custom-fullscreen iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .exit-fullscreen {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1.1rem;
            z-index: 10000;
        }

        .exit-fullscreen:hover {
            background: rgba(255, 0, 0, 0.8);
        }
        
        .live-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .live-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #FFD700;
        }
        
        .live-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 25px;
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
        
        .btn-youtube {
            background: #ff0000;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .no-live {
            text-align: center;
            padding: 80px 20px;
            opacity: 0.7;
        }
        
        .no-live i {
            font-size: 5rem;
            margin-bottom: 30px;
            opacity: 0.5;
            color: #95a5a6;
        }
        
        .no-live h3 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #ecf0f1;
        }
        
        .no-live p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
        }
        
        .refresh-btn {
            background: #3498db;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-top: 200px;
                padding: 15px;
            }
            
            .live-container {
                padding: 20px;
            }
            
            .live-header h1 {
                font-size: 2rem;
            }
            
            .live-title {
                font-size: 1.4rem;
            }
            
            .live-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header_geral.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="live-header">
                <h1><i class="fas fa-broadcast-tower"></i> Transmissão Ao Vivo</h1>
                <?php if ($liveStream): ?>
                    <div class="live-status">
                        <i class="fas fa-circle"></i> AO VIVO
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($liveStream): ?>
                <div class="live-container">
                    <div class="live-info">
                        <div class="live-title"><?= htmlspecialchars($liveStream['title']) ?></div>
                    </div>
                    
                    <div class="live-player" id="livePlayer">
                        <iframe id="liveIframe"
                                src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($liveStream['youtube_id']) ?>?autoplay=1&controls=0&disablekb=1&fs=0&modestbranding=1&rel=0&iv_load_policy=3&cc_load_policy=0&playsinline=1&enablejsapi=0&origin=<?= urlencode($_SERVER['HTTP_HOST']) ?>"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                        </iframe>
                        <div class="player-overlay" onclick="toggleFullscreen()">
                            <button class="fullscreen-btn" title="Expandir para Tela Cheia">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="live-actions">
                        <div style="text-align: center; color: rgba(255, 255, 255, 0.5); font-size: 0.85rem; margin-top: 10px;">
                            <i class="fas fa-mouse-pointer"></i>
                            Passe o mouse sobre o vídeo para expandir
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="live-container">
                    <div class="no-live">
                        <i class="fas fa-video-slash"></i>
                        <h3>Nenhuma transmissão ativa</h3>
                        <p>No momento não há transmissões ao vivo.<br>Volte em breve para acompanhar os jogos!</p>
                        
                        <button onclick="location.reload()" class="refresh-btn">
                            <i class="fas fa-sync-alt"></i> Verificar Novamente
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="JogosProximos.php" class="btn btn-primary">
                    <i class="fas fa-calendar"></i> Ver Próximos Jogos
                </a>
                <a href="HomePage2.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Página Inicial
                </a>
            </div>
        </div>
    </main>

    <!-- Tela Cheia Personalizada -->
    <div id="customFullscreen" class="custom-fullscreen">
        <button class="exit-fullscreen" onclick="exitFullscreen()" title="Sair da Tela Cheia">
            <i class="fas fa-times"></i> Sair
        </button>
        <iframe id="fullscreenIframe" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    
    <script>
        // Função para tela cheia personalizada
        function toggleFullscreen() {
            const customFullscreen = document.getElementById('customFullscreen');
            const fullscreenIframe = document.getElementById('fullscreenIframe');
            const originalIframe = document.getElementById('liveIframe');

            // Criar URL limpa para tela cheia (sem qualquer controle)
            const videoId = originalIframe.src.match(/embed\/([^?]+)/)[1];
            const cleanUrl = `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&controls=0&disablekb=1&fs=0&modestbranding=1&rel=0&iv_load_policy=3&cc_load_policy=0&playsinline=1&enablejsapi=0&showinfo=0`;

            fullscreenIframe.src = cleanUrl;

            // Mostrar tela cheia
            customFullscreen.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Prevenir qualquer interação
            setTimeout(() => {
                fullscreenIframe.style.pointerEvents = 'none';
            }, 1000);
        }

        function exitFullscreen() {
            const customFullscreen = document.getElementById('customFullscreen');
            const fullscreenIframe = document.getElementById('fullscreenIframe');

            // Esconder tela cheia
            customFullscreen.style.display = 'none';
            document.body.style.overflow = 'auto';

            // Limpar iframe de tela cheia
            fullscreenIframe.src = '';
        }

        // Sair da tela cheia com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                exitFullscreen();
            }
        });

        // Prevenir todas as interações indesejadas
        const livePlayer = document.getElementById('livePlayer');
        if (livePlayer) {
            // Prevenir clique direito
            livePlayer.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            // Prevenir seleção de texto
            livePlayer.addEventListener('selectstart', function(e) {
                e.preventDefault();
            });

            // Prevenir arrastar
            livePlayer.addEventListener('dragstart', function(e) {
                e.preventDefault();
            });

            // Prevenir teclas de atalho no player
            livePlayer.addEventListener('keydown', function(e) {
                // Bloquear espaço (pause), setas, etc.
                if ([32, 37, 38, 39, 40, 75, 77, 70].includes(e.keyCode)) {
                    e.preventDefault();
                }
            });
        }

        // Bloquear interação direta com iframe após carregamento
        setTimeout(() => {
            const iframe = document.getElementById('liveIframe');
            if (iframe) {
                iframe.style.pointerEvents = 'none';
            }
        }, 3000);

        // Auto-refresh a cada 30 segundos para verificar novas transmissões
        setInterval(function() {
            // Só recarrega se não há transmissão ativa
            <?php if (!$liveStream): ?>
                location.reload();
            <?php endif; ?>
        }, 30000);
    </script>
</body>
</html>
