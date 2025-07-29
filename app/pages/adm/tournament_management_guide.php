<?php include 'admin_header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guia de Gerenciamento - Copa das Panelas</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .guide-card {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
        }
        
        .title {
            text-align: center;
            margin-bottom: 30px;
            color: #f39c12;
            font-size: 2.5rem;
        }
        
        .success-banner {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }
        
        .phases-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .phase-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
        }
        
        .phase-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .phase-card.active {
            border-left-color: #f39c12;
            background: rgba(243, 156, 18, 0.2);
        }
        
        .phase-card.completed {
            border-left-color: #27ae60;
            background: rgba(39, 174, 96, 0.2);
        }
        
        .phase-title {
            color: #3498db;
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .phase-card.active .phase-title {
            color: #f39c12;
        }
        
        .phase-card.completed .phase-title {
            color: #27ae60;
        }
        
        .phase-steps {
            list-style: none;
            padding: 0;
        }
        
        .phase-steps li {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
            border-left: 3px solid #3498db;
        }
        
        .step-example {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .example-title {
            color: #3498db;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .match-example {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        
        .teams-vs {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .team-info {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        
        .vs {
            font-size: 2rem;
            font-weight: bold;
            color: #f39c12;
            text-align: center;
        }
        
        .score-input {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .score-group {
            text-align: center;
        }
        
        .score-group input {
            width: 60px;
            height: 60px;
            font-size: 2rem;
            text-align: center;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-weight: bold;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #f39c12;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            margin: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #2ecc71; }
        
        .btn-primary { background: #3498db; }
        .btn-primary:hover { background: #2980b9; }
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }
        
        .action-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #f39c12;
        }
        
        .tips-section {
            background: rgba(243, 156, 18, 0.2);
            border: 2px solid #f39c12;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .tips-title {
            color: #f39c12;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .tip-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="guide-card">
            <div class="success-banner">
                <h1 style="margin-bottom: 15px;">
                    <i class="fas fa-trophy"></i>
                    TORNEIO CRIADO COM SUCESSO!
                </h1>
                <p>Agora vamos gerenciar seu torneio do início até a final</p>
            </div>
            
            <h1 class="title">
                <i class="fas fa-map"></i>
                Guia Completo de Gerenciamento
            </h1>
            
            <div class="phases-timeline">
                <div class="phase-card active">
                    <div class="phase-title">
                        <i class="fas fa-play"></i>
                        FASE 1: Fase de Grupos
                    </div>
                    <ul class="phase-steps">
                        <li>✅ Times organizados em grupos</li>
                        <li>🎯 Inserir resultados dos jogos</li>
                        <li>📊 Acompanhar classificação</li>
                        <li>🏆 Definir classificados</li>
                    </ul>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="match_manager_corrected.php" class="btn btn-primary">
                            <i class="fas fa-futbol"></i>
                            Gerenciar Jogos
                        </a>
                    </div>
                </div>
                
                <div class="phase-card">
                    <div class="phase-title">
                        <i class="fas fa-sword"></i>
                        FASE 2: Oitavas/Quartas
                    </div>
                    <ul class="phase-steps">
                        <li>⏳ Aguardando fase de grupos</li>
                        <li>🎯 Criar confrontos eliminatórios</li>
                        <li>⚔️ Jogos mata-mata</li>
                        <li>🏆 Definir semifinalistas</li>
                    </ul>
                    <div style="text-align: center; margin-top: 15px;">
                        <button class="btn" disabled>
                            <i class="fas fa-lock"></i>
                            Bloqueado
                        </button>
                    </div>
                </div>
                
                <div class="phase-card">
                    <div class="phase-title">
                        <i class="fas fa-medal"></i>
                        FASE 3: Semifinais
                    </div>
                    <ul class="phase-steps">
                        <li>⏳ Aguardando quartas</li>
                        <li>🎯 4 melhores times</li>
                        <li>⚔️ Jogos decisivos</li>
                        <li>🏆 Definir finalistas</li>
                    </ul>
                    <div style="text-align: center; margin-top: 15px;">
                        <button class="btn" disabled>
                            <i class="fas fa-lock"></i>
                            Bloqueado
                        </button>
                    </div>
                </div>
                
                <div class="phase-card">
                    <div class="phase-title">
                        <i class="fas fa-crown"></i>
                        FASE 4: Final
                    </div>
                    <ul class="phase-steps">
                        <li>⏳ Aguardando semifinais</li>
                        <li>🥉 Disputa 3º lugar</li>
                        <li>🏆 GRANDE FINAL</li>
                        <li>👑 CAMPEÃO!</li>
                    </ul>
                    <div style="text-align: center; margin-top: 15px;">
                        <button class="btn" disabled>
                            <i class="fas fa-lock"></i>
                            Bloqueado
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="step-example">
                <div class="example-title">
                    <i class="fas fa-play-circle"></i>
                    EXEMPLO: Como Inserir Resultado de um Jogo
                </div>
                
                <div class="match-example">
                    <h4 style="color: #f39c12; text-align: center; margin-bottom: 20px;">
                        Grupo A - Rodada 1
                    </h4>
                    
                    <div class="teams-vs">
                        <div class="team-info">
                            <strong>Águias FC</strong>
                            <div style="font-size: 0.8rem; opacity: 0.7;">1º do Grupo A</div>
                        </div>
                        <div class="vs">VS</div>
                        <div class="team-info">
                            <strong>Leões United</strong>
                            <div style="font-size: 0.8rem; opacity: 0.7;">2º do Grupo A</div>
                        </div>
                    </div>
                    
                    <div class="score-input">
                        <div class="score-group">
                            <label style="color: #3498db; margin-bottom: 10px; display: block;">Gols Águias FC</label>
                            <input type="number" value="3" min="0">
                        </div>
                        <div class="score-group">
                            <label style="color: #3498db; margin-bottom: 10px; display: block;">Gols Leões United</label>
                            <input type="number" value="1" min="0">
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <button class="btn btn-success" onclick="saveExampleResult()">
                            <i class="fas fa-save"></i>
                            Salvar Resultado
                        </button>
                    </div>
                    
                    <div id="result_saved" style="display: none; background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 10px; padding: 15px; margin-top: 15px; text-align: center;">
                        <strong style="color: #27ae60;">✅ Resultado Salvo!</strong><br>
                        Águias FC 3 x 1 Leões United<br>
                        <small>Classificação atualizada automaticamente</small>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <h4>Inserir Resultados</h4>
                    <p>Registre os resultados dos jogos conforme acontecem</p>
                    <a href="match_manager_corrected.php" class="btn btn-primary">
                        Acessar
                    </a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h4>Ver Classificação</h4>
                    <p>Acompanhe a classificação em tempo real</p>
                    <a href="standings_corrected.php" class="btn btn-success">
                        Acessar
                    </a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>Estatísticas</h4>
                    <p>Veja estatísticas detalhadas do torneio</p>
                    <a href="statistics.php" class="btn">
                        Acessar
                    </a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <h4>Calendário</h4>
                    <p>Visualize todos os jogos programados</p>
                    <a href="global_calendar.php" class="btn">
                        Acessar
                    </a>
                </div>
            </div>
            
            <div class="tips-section">
                <div class="tips-title">
                    <i class="fas fa-lightbulb"></i>
                    Dicas Importantes para Gerenciar seu Torneio
                </div>
                
                <div class="tip-item">
                    <strong>📊 Atualize Resultados Regularmente:</strong>
                    Insira os resultados logo após cada jogo para manter a classificação sempre atualizada.
                </div>
                
                <div class="tip-item">
                    <strong>🏆 Acompanhe a Classificação:</strong>
                    Verifique regularmente quais times estão se classificando para as próximas fases.
                </div>
                
                <div class="tip-item">
                    <strong>⚔️ Prepare as Eliminatórias:</strong>
                    Após a fase de grupos, o sistema criará automaticamente os confrontos das eliminatórias.
                </div>
                
                <div class="tip-item">
                    <strong>🥉 Não Esqueça do 3º Lugar:</strong>
                    O sistema criará automaticamente a disputa de terceiro lugar entre os perdedores das semifinais.
                </div>
                
                <div class="tip-item">
                    <strong>📱 Interface Intuitiva:</strong>
                    Use as interfaces corrigidas que seguem todas as regras oficiais do futsal.
                </div>
            </div>
            
            <div style="background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 15px; padding: 25px; text-align: center; margin-bottom: 30px;">
                <h3 style="color: #27ae60; margin-bottom: 15px;">
                    <i class="fas fa-rocket"></i>
                    SEU TORNEIO ESTÁ PRONTO!
                </h3>
                <p>✅ Estrutura completa criada</p>
                <p>✅ Times organizados em grupos</p>
                <p>✅ Jogos programados</p>
                <p>✅ Sistema de classificação ativo</p>
                <p>✅ Pronto para começar!</p>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="dashboard_simple.php" class="btn btn-success" style="font-size: 1.2rem; padding: 20px 40px;">
                    <i class="fas fa-tachometer-alt"></i>
                    IR PARA O DASHBOARD
                </a>
                <a href="match_manager_corrected.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 20px 40px;">
                    <i class="fas fa-futbol"></i>
                    COMEÇAR A INSERIR RESULTADOS
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function saveExampleResult() {
            // Simular salvamento
            document.getElementById('result_saved').style.display = 'block';
            
            // Animar o card
            const resultDiv = document.getElementById('result_saved');
            resultDiv.style.animation = 'fadeIn 0.5s ease';
            
            setTimeout(() => {
                console.log('✅ Exemplo de resultado salvo!');
            }, 1000);
        }
    </script>
</body>
</html>
