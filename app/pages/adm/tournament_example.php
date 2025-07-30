<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática

 include 'admin_header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemplo Prático de Torneio - Copa das Panelas</title>
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
        
        .example-card {
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
        
        .step-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #3498db;
        }
        
        .step-title {
            color: #3498db;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .group-card {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
        }
        
        .group-title {
            color: #3498db;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .team-list {
            list-style: none;
            padding: 0;
        }
        
        .team-list li {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #f39c12;
        }
        
        .matches-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .matches-table th,
        .matches-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .matches-table th {
            background: rgba(255, 255, 255, 0.2);
            color: #f39c12;
            font-weight: bold;
        }
        
        .standings-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .standings-table th,
        .standings-table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .standings-table th {
            background: rgba(255, 255, 255, 0.2);
            color: #f39c12;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .qualified {
            background: rgba(39, 174, 96, 0.3) !important;
            border-left: 4px solid #27ae60;
        }
        
        .eliminated {
            background: rgba(231, 76, 60, 0.3) !important;
            border-left: 4px solid #e74c3c;
        }
        
        .bracket {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        
        .bracket-round {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
        }
        
        .bracket-title {
            color: #f39c12;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .bracket-match {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
        }
        
        .bracket-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .bracket-score {
            font-size: 1.2rem;
            font-weight: bold;
            color: #f39c12;
        }
        
        .winner {
            color: #27ae60;
            font-weight: bold;
        }
        
        .loser {
            opacity: 0.6;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #f39c12;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #2ecc71; }
        
        .final-result {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(243, 156, 18, 0.3);
        }
        
        .champion-title {
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .podium {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .podium-place {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        
        .place-1 { border: 3px solid #ffd700; }
        .place-2 { border: 3px solid #c0c0c0; }
        .place-3 { border: 3px solid #cd7f32; }
        
        .medal {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .gold { color: #ffd700; }
        .silver { color: #c0c0c0; }
        .bronze { color: #cd7f32; }
    </style>
</head>
<body>
    <div class="container">
        <div class="example-card">
            <h1 class="title">
                <i class="fas fa-play-circle"></i>
                Exemplo Prático: Copa das Panelas 2024
            </h1>
            
            <!-- PASSO 1: Criação do Torneio -->
            <div class="step-section">
                <div class="step-title">
                    <i class="fas fa-plus-circle"></i>
                    PASSO 1: Criação do Torneio
                </div>
                
                <div style="background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="color: #27ae60; margin-bottom: 10px;">✅ Torneio Criado:</h4>
                    <p><strong>Nome:</strong> Copa das Panelas 2024</p>
                    <p><strong>Formato:</strong> 4 grupos com 4 times cada (16 times total)</p>
                    <p><strong>Classificação:</strong> 2 primeiros de cada grupo</p>
                    <p><strong>Eliminatórias:</strong> Quartas → Semifinais → Final</p>
                </div>
                
                <div class="groups-grid">
                    <div class="group-card">
                        <div class="group-title">GRUPO A</div>
                        <ul class="team-list">
                            <li>Águias FC</li>
                            <li>Leões United</li>
                            <li>Tigres SC</li>
                            <li>Panteras FC</li>
                        </ul>
                    </div>
                    <div class="group-card">
                        <div class="group-title">GRUPO B</div>
                        <ul class="team-list">
                            <li>Falcões FC</li>
                            <li>Lobos United</li>
                            <li>Tubarões SC</li>
                            <li>Dragões FC</li>
                        </ul>
                    </div>
                    <div class="group-card">
                        <div class="group-title">GRUPO C</div>
                        <ul class="team-list">
                            <li>Cobras FC</li>
                            <li>Jaguares United</li>
                            <li>Pumas SC</li>
                            <li>Condores FC</li>
                        </ul>
                    </div>
                    <div class="group-card">
                        <div class="group-title">GRUPO D</div>
                        <ul class="team-list">
                            <li>Ursos FC</li>
                            <li>Raposas United</li>
                            <li>Linces SC</li>
                            <li>Leopardos FC</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- PASSO 2: Fase de Grupos -->
            <div class="step-section">
                <div class="step-title">
                    <i class="fas fa-futbol"></i>
                    PASSO 2: Fase de Grupos - Resultados
                </div>
                
                <h4 style="color: #f39c12; margin-bottom: 15px;">Exemplo de Jogos do Grupo A:</h4>
                <table class="matches-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Jogo</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>15/01</td>
                            <td>Águias FC vs Leões United</td>
                            <td><strong>3 x 1</strong></td>
                        </tr>
                        <tr>
                            <td>15/01</td>
                            <td>Tigres SC vs Panteras FC</td>
                            <td><strong>2 x 2</strong></td>
                        </tr>
                        <tr>
                            <td>18/01</td>
                            <td>Águias FC vs Tigres SC</td>
                            <td><strong>1 x 0</strong></td>
                        </tr>
                        <tr>
                            <td>18/01</td>
                            <td>Leões United vs Panteras FC</td>
                            <td><strong>4 x 1</strong></td>
                        </tr>
                        <tr>
                            <td>21/01</td>
                            <td>Águias FC vs Panteras FC</td>
                            <td><strong>2 x 0</strong></td>
                        </tr>
                        <tr>
                            <td>21/01</td>
                            <td>Leões United vs Tigres SC</td>
                            <td><strong>1 x 1</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <h4 style="color: #f39c12; margin-bottom: 15px;">Classificação Final do Grupo A:</h4>
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Time</th>
                            <th>J</th>
                            <th>V</th>
                            <th>E</th>
                            <th>D</th>
                            <th>GP</th>
                            <th>GC</th>
                            <th>SG</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="qualified">
                            <td><strong>1º</strong></td>
                            <td><strong>Águias FC</strong></td>
                            <td>3</td>
                            <td>3</td>
                            <td>0</td>
                            <td>0</td>
                            <td>6</td>
                            <td>1</td>
                            <td>+5</td>
                            <td><strong>9</strong></td>
                        </tr>
                        <tr class="qualified">
                            <td><strong>2º</strong></td>
                            <td><strong>Leões United</strong></td>
                            <td>3</td>
                            <td>1</td>
                            <td>1</td>
                            <td>1</td>
                            <td>6</td>
                            <td>4</td>
                            <td>+2</td>
                            <td><strong>4</strong></td>
                        </tr>
                        <tr class="eliminated">
                            <td>3º</td>
                            <td>Tigres SC</td>
                            <td>3</td>
                            <td>0</td>
                            <td>2</td>
                            <td>1</td>
                            <td>3</td>
                            <td>4</td>
                            <td>-1</td>
                            <td>2</td>
                        </tr>
                        <tr class="eliminated">
                            <td>4º</td>
                            <td>Panteras FC</td>
                            <td>3</td>
                            <td>0</td>
                            <td>1</td>
                            <td>2</td>
                            <td>3</td>
                            <td>6</td>
                            <td>-3</td>
                            <td>1</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- PASSO 3: Eliminatórias -->
            <div class="step-section">
                <div class="step-title">
                    <i class="fas fa-sword"></i>
                    PASSO 3: Fases Eliminatórias
                </div>
                
                <h4 style="color: #f39c12; margin-bottom: 15px;">Chaveamento das Eliminatórias:</h4>
                <div class="bracket">
                    <div class="bracket-round">
                        <div class="bracket-title">QUARTAS DE FINAL</div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Águias FC</span>
                                <span class="bracket-score">2 x 0</span>
                                <span class="loser">Raposas United</span>
                            </div>
                        </div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Falcões FC</span>
                                <span class="bracket-score">3 x 1</span>
                                <span class="loser">Leões United</span>
                            </div>
                        </div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Cobras FC</span>
                                <span class="bracket-score">1 x 0</span>
                                <span class="loser">Lobos United</span>
                            </div>
                        </div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Ursos FC</span>
                                <span class="bracket-score">2 x 1</span>
                                <span class="loser">Jaguares United</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bracket-round">
                        <div class="bracket-title">SEMIFINAIS</div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Águias FC</span>
                                <span class="bracket-score">4 x 2</span>
                                <span class="loser">Falcões FC</span>
                            </div>
                        </div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Ursos FC</span>
                                <span class="bracket-score">1 x 0</span>
                                <span class="loser">Cobras FC</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bracket-round">
                        <div class="bracket-title">3º LUGAR</div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Falcões FC</span>
                                <span class="bracket-score">3 x 1</span>
                                <span class="loser">Cobras FC</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bracket-round">
                        <div class="bracket-title">FINAL</div>
                        <div class="bracket-match">
                            <div class="bracket-teams">
                                <span class="winner">Águias FC</span>
                                <span class="bracket-score">2 x 1</span>
                                <span class="loser">Ursos FC</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RESULTADO FINAL -->
            <div class="final-result">
                <div class="champion-title">
                    <i class="fas fa-crown"></i>
                    ÁGUIAS FC CAMPEÃO!
                </div>
                <p style="font-size: 1.2rem;">Copa das Panelas 2024</p>
            </div>
            
            <div class="podium">
                <div class="podium-place place-1">
                    <div class="medal gold">🥇</div>
                    <h3>1º LUGAR</h3>
                    <h4>Águias FC</h4>
                    <p>Campeão Invicto</p>
                </div>
                <div class="podium-place place-2">
                    <div class="medal silver">🥈</div>
                    <h3>2º LUGAR</h3>
                    <h4>Ursos FC</h4>
                    <p>Vice-Campeão</p>
                </div>
                <div class="podium-place place-3">
                    <div class="medal bronze">🥉</div>
                    <h3>3º LUGAR</h3>
                    <h4>Falcões FC</h4>
                    <p>Terceiro Colocado</p>
                </div>
            </div>
            
            <div style="background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 15px; padding: 25px; text-align: center; margin: 30px 0;">
                <h3 style="color: #27ae60; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i>
                    TORNEIO CONCLUÍDO COM SUCESSO!
                </h3>
                <p>✅ 16 times participaram</p>
                <p>✅ 24 jogos na fase de grupos</p>
                <p>✅ 7 jogos nas eliminatórias</p>
                <p>✅ Total: 31 jogos realizados</p>
                <p>✅ Campeão definido!</p>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="tournament_wizard.php" class="btn btn-success" style="font-size: 1.2rem; padding: 20px 40px;">
                    <i class="fas fa-magic"></i>
                    CRIAR MEU TORNEIO
                </a>
                <a href="dashboard_simple.php" class="btn" style="font-size: 1.2rem; padding: 20px 40px;">
                    <i class="fas fa-tachometer-alt"></i>
                    DASHBOARD
                </a>
            </div>
        </div>
    </div>
</body>
</html>
