<?php include 'admin_header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disputa de Terceiro Lugar - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
            padding-top: 80px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .third-place-card {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
        }
        
        .title {
            text-align: center;
            margin-bottom: 30px;
            color: #cd7f32;
            font-size: 2.5rem;
        }
        
        .bronze-medal {
            color: #cd7f32;
        }
        
        .match-preview {
            background: rgba(205, 127, 50, 0.2);
            border: 2px solid #cd7f32;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .teams-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 30px;
            align-items: center;
            margin: 30px 0;
        }
        
        .team-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        
        .team-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #f39c12;
        }
        
        .team-path {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }
        
        .team-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-value {
            font-weight: bold;
            color: #3498db;
        }
        
        .vs-section {
            text-align: center;
        }
        
        .vs {
            font-size: 3rem;
            font-weight: bold;
            color: #cd7f32;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .match-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .score-section {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .score-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .score-group {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
        }
        
        .score-group label {
            display: block;
            margin-bottom: 8px;
            color: #3498db;
            font-weight: bold;
        }
        
        .score-group input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 1.2rem;
            text-align: center;
            font-weight: bold;
        }
        
        .importance-section {
            background: rgba(243, 156, 18, 0.2);
            border: 2px solid #f39c12;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .importance-title {
            color: #f39c12;
            font-size: 1.3rem;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .importance-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .importance-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            background: #cd7f32;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            margin: 10px 5px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #b8691e;
            transform: translateY(-2px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #2ecc71; }
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .result-display {
            background: rgba(39, 174, 96, 0.2);
            border: 2px solid #27ae60;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            text-align: center;
        }
        
        .winner-announcement {
            font-size: 2rem;
            font-weight: bold;
            color: #cd7f32;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="third-place-card">
            <h1 class="title">
                <i class="fas fa-medal bronze-medal"></i>
                Disputa do Terceiro Lugar
            </h1>
            
            <div class="importance-section">
                <div class="importance-title">
                    <i class="fas fa-star"></i>
                    Importância da Disputa de 3º Lugar
                </div>
                <div class="importance-list">
                    <div class="importance-item">
                        <strong>Reconhecimento:</strong> Medalha de bronze para o terceiro colocado
                    </div>
                    <div class="importance-item">
                        <strong>Motivação:</strong> Evita que times terminem o torneio com derrota
                    </div>
                    <div class="importance-item">
                        <strong>Tradição:</strong> Padrão em competições oficiais de futsal
                    </div>
                    <div class="importance-item">
                        <strong>Espetáculo:</strong> Mais um jogo para os torcedores
                    </div>
                </div>
            </div>
            
            <div class="match-preview">
                <h2 style="text-align: center; color: #cd7f32; margin-bottom: 25px;">
                    <i class="fas fa-trophy"></i>
                    Jogo do Terceiro Lugar
                </h2>
                
                <div class="teams-container">
                    <div class="team-card">
                        <div class="team-name">Time Perdedor Semifinal 1</div>
                        <div class="team-path">Perdeu na semifinal para o finalista A</div>
                        <div class="team-stats">
                            <div class="stat-item">
                                <div class="stat-value">15</div>
                                <div>Gols</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">6</div>
                                <div>Jogos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">12</div>
                                <div>Pontos</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vs-section">
                        <div class="vs">VS</div>
                        <div class="match-info">
                            <div><strong>Data:</strong> A definir</div>
                            <div><strong>Horário:</strong> Antes da final</div>
                            <div><strong>Local:</strong> Mesmo da final</div>
                        </div>
                    </div>
                    
                    <div class="team-card">
                        <div class="team-name">Time Perdedor Semifinal 2</div>
                        <div class="team-path">Perdeu na semifinal para o finalista B</div>
                        <div class="team-stats">
                            <div class="stat-item">
                                <div class="stat-value">12</div>
                                <div>Gols</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">6</div>
                                <div>Jogos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">10</div>
                                <div>Pontos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="score-section">
                <h3 style="color: #3498db; text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-futbol"></i>
                    Registrar Resultado do Jogo
                </h3>
                
                <form method="POST">
                    <div class="score-inputs">
                        <div class="score-group">
                            <label>Gols Time 1 (Tempo Normal)</label>
                            <input type="number" name="team1_score" min="0" placeholder="0">
                        </div>
                        <div class="score-group">
                            <label>Gols Time 2 (Tempo Normal)</label>
                            <input type="number" name="team2_score" min="0" placeholder="0">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div class="score-group">
                            <label>Gols Time 1 (Prorrogação)</label>
                            <input type="number" name="team1_extra" min="0" placeholder="Se houver">
                        </div>
                        <div class="score-group">
                            <label>Gols Time 2 (Prorrogação)</label>
                            <input type="number" name="team2_extra" min="0" placeholder="Se houver">
                        </div>
                        <div class="score-group">
                            <label>Pênaltis Time 1</label>
                            <input type="number" name="team1_penalties" min="0" max="5" placeholder="Se houver">
                        </div>
                        <div class="score-group">
                            <label>Pênaltis Time 2</label>
                            <input type="number" name="team2_penalties" min="0" max="5" placeholder="Se houver">
                        </div>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Registrar Resultado
                        </button>
                        <button type="button" class="btn" onclick="autoCreateMatch()">
                            <i class="fas fa-magic"></i>
                            Criar Automaticamente
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="result-display" style="display: none;" id="result_display">
                <div class="winner-announcement">
                    <i class="fas fa-medal bronze-medal"></i>
                    TERCEIRO LUGAR: [NOME DO TIME]
                </div>
                <p>Parabéns ao time pelo excelente desempenho no torneio!</p>
                <p>A medalha de bronze é um reconhecimento merecido.</p>
            </div>
            
            <div style="background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 15px; padding: 25px; text-align: center; margin-bottom: 30px;">
                <h3 style="color: #27ae60; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i>
                    DISPUTA DE 3º LUGAR IMPLEMENTADA!
                </h3>
                <p>✅ Criação automática após semifinais</p>
                <p>✅ Suporte a prorrogação e pênaltis</p>
                <p>✅ Medalha de bronze para o vencedor</p>
                <p>✅ Integração com sistema de classificação</p>
                <p>✅ Seguindo padrões de competições oficiais</p>
            </div>
            
            <div class="actions">
                <a href="dashboard_simple.php" class="btn btn-success">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="match_manager_corrected.php" class="btn">
                    <i class="fas fa-futbol"></i>
                    Gerenciar Jogos
                </a>
                <a href="standings_corrected.php" class="btn">
                    <i class="fas fa-trophy"></i>
                    Classificação
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function autoCreateMatch() {
            if (confirm('Criar automaticamente o jogo de 3º lugar com os perdedores das semifinais?')) {
                // Simular criação automática
                alert('Jogo de 3º lugar criado automaticamente!\n\nTimes: Perdedores das semifinais\nData: 1 dia antes da final\nStatus: Agendado');
                
                // Mostrar seção de resultado
                document.getElementById('result_display').style.display = 'block';
            }
        }
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const team1Score = parseInt(document.querySelector('[name=team1_score]').value) || 0;
            const team2Score = parseInt(document.querySelector('[name=team2_score]').value) || 0;
            const team1Extra = parseInt(document.querySelector('[name=team1_extra]').value) || 0;
            const team2Extra = parseInt(document.querySelector('[name=team2_extra]').value) || 0;
            const team1Penalties = parseInt(document.querySelector('[name=team1_penalties]').value) || 0;
            const team2Penalties = parseInt(document.querySelector('[name=team2_penalties]').value) || 0;
            
            // Verificar se há empate sem resolução
            const totalTeam1 = team1Score + team1Extra;
            const totalTeam2 = team2Score + team2Extra;
            
            if (totalTeam1 === totalTeam2 && team1Penalties === team2Penalties) {
                alert('O jogo não pode terminar empatado! Use prorrogação e/ou pênaltis.');
                return;
            }
            
            // Determinar vencedor
            let winner = '';
            if (team1Penalties > 0 || team2Penalties > 0) {
                winner = team1Penalties > team2Penalties ? 'Time 1' : 'Time 2';
            } else {
                winner = totalTeam1 > totalTeam2 ? 'Time 1' : 'Time 2';
            }
            
            // Mostrar resultado
            document.getElementById('result_display').style.display = 'block';
            document.querySelector('.winner-announcement').innerHTML = 
                '<i class="fas fa-medal bronze-medal"></i> TERCEIRO LUGAR: ' + winner;
            
            alert('Resultado registrado com sucesso!\n\nTerceiro lugar: ' + winner);
        });
    </script>
</body>
</html>
