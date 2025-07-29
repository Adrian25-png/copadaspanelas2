<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Gerenciar Transmissão</title>
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
            max-width: 800px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ecf0f1;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-help {
            display: block;
            margin-top: 5px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid #3498db;
            color: #3498db;
        }
        
        @media (max-width: 768px) {
            .type-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-broadcast-tower"></i> Teste - Gerenciar Transmissão</h1>
            <p>Página de teste para verificar o funcionamento das transmissões</p>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Esta é uma página de teste.</strong> Para usar a versão completa, faça login como administrador.
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
        <form id="youtube_form" class="transmission-form">
            <div class="form-group">
                <label for="title_youtube">Título da Transmissão:</label>
                <input type="text" id="title_youtube" name="title" placeholder="Ex: Final - Time A vs Time B" required>
            </div>
            
            <div class="form-group">
                <label for="youtube_url">URL do YouTube (Live):</label>
                <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                <small class="form-help">Cole aqui o link da sua live do YouTube</small>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="testYoutube()">
                <i class="fab fa-youtube"></i> Testar Live do YouTube
            </button>
        </form>

        <!-- Formulário Live Externa -->
        <form id="external_form" class="transmission-form" style="display: none;">
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
            
            <button type="button" class="btn btn-primary" onclick="testExternal()">
                <i class="fas fa-broadcast-tower"></i> Testar Live Externa
            </button>
        </form>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="app/pages/adm/login_simple.php" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-sign-in-alt"></i> Fazer Login como Admin
            </a>
        </div>
    </div>
    
    <script>
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
        
        function testYoutube() {
            const title = document.getElementById('title_youtube').value;
            const url = document.getElementById('youtube_url').value;
            
            if (!title || !url) {
                alert('Preencha todos os campos!');
                return;
            }
            
            alert('Teste YouTube:\nTítulo: ' + title + '\nURL: ' + url + '\n\nEm produção, isso iniciaria a transmissão!');
        }
        
        function testExternal() {
            const title = document.getElementById('title_external').value;
            const url = document.getElementById('external_url').value;
            const embed = document.getElementById('embed_code').value;
            
            if (!title || !url) {
                alert('Preencha os campos obrigatórios!');
                return;
            }
            
            alert('Teste Externo:\nTítulo: ' + title + '\nURL: ' + url + '\nEmbed: ' + (embed ? 'Fornecido' : 'Não fornecido') + '\n\nEm produção, isso iniciaria a transmissão!');
        }
    </script>
</body>
</html>
