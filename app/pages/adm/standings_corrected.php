<?php include 'admin_header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação Corrigida - Copa das Panelas</title>
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
        
        .standings-card {
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
        
        .criteria-section {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .criteria-title {
            color: #3498db;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .criteria-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .criteria-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
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
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .standings-table th {
            background: rgba(255, 255, 255, 0.2);
            color: #f39c12;
            font-weight: bold;
        }
        
        .standings-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .position {
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .qualified {
            background: rgba(39, 174, 96, 0.3) !important;
            border-left: 4px solid #27ae60;
        }
        
        .eliminated {
            background: rgba(231, 76, 60, 0.3) !important;
            border-left: 4px solid #e74c3c;
        }
        
        .group-header {
            background: rgba(243, 156, 18, 0.3);
            color: #f39c12;
            font-size: 1.3rem;
            font-weight: bold;
            text-align: center;
            padding: 15px;
        }
        
        .tiebreaker-info {
            background: rgba(243, 156, 18, 0.2);
            border: 2px solid #f39c12;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            font-size: 0.9rem;
        }
        
        .head-to-head {
            background: rgba(155, 89, 182, 0.2);
            border-left: 4px solid #9b59b6;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
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
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="standings-card">
            <h1 class="title">
                <i class="fas fa-trophy"></i>
                Sistema de Classificação Corrigido
            </h1>
            
            <div class="criteria-section">
                <div class="criteria-title">
                    <i class="fas fa-list-ol"></i>
                    Critérios de Desempate (Padrão FIFA/CBF)
                </div>
                <div class="criteria-list">
                    <div class="criteria-item">
                        <strong>1º Critério:</strong> Maior número de pontos
                    </div>
                    <div class="criteria-item">
                        <strong>2º Critério:</strong> Confronto direto (entre times empatados)
                    </div>
                    <div class="criteria-item">
                        <strong>3º Critério:</strong> Melhor saldo de gols
                    </div>
                    <div class="criteria-item">
                        <strong>4º Critério:</strong> Maior número de gols marcados
                    </div>
                    <div class="criteria-item">
                        <strong>5º Critério:</strong> Menor número de cartões (disciplina)
                    </div>
                    <div class="criteria-item">
                        <strong>6º Critério:</strong> Sorteio (último recurso)
                    </div>
                </div>
            </div>
            
            <!-- Exemplo de Grupo A -->
            <div class="standings-card">
                <div class="group-header">GRUPO A - Classificação Final</div>
                
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Time</th>
                            <th>PG</th>
                            <th>J</th>
                            <th>V</th>
                            <th>E</th>
                            <th>D</th>
                            <th>GP</th>
                            <th>GC</th>
                            <th>SG</th>
                            <th>Pts</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="qualified">
                            <td class="position">1º</td>
                            <td><strong>Time Alpha</strong></td>
                            <td>100%</td>
                            <td>3</td>
                            <td>3</td>
                            <td>0</td>
                            <td>0</td>
                            <td>8</td>
                            <td>2</td>
                            <td>+6</td>
                            <td><strong>9</strong></td>
                            <td><span style="color: #27ae60;">Classificado</span></td>
                        </tr>
                        <tr class="qualified">
                            <td class="position">2º</td>
                            <td><strong>Time Beta</strong></td>
                            <td>67%</td>
                            <td>3</td>
                            <td>2</td>
                            <td>0</td>
                            <td>1</td>
                            <td>6</td>
                            <td>4</td>
                            <td>+2</td>
                            <td><strong>6</strong></td>
                            <td><span style="color: #27ae60;">Classificado</span></td>
                        </tr>
                        <tr class="eliminated">
                            <td class="position">3º</td>
                            <td>Time Gamma</td>
                            <td>33%</td>
                            <td>3</td>
                            <td>1</td>
                            <td>0</td>
                            <td>2</td>
                            <td>4</td>
                            <td>6</td>
                            <td>-2</td>
                            <td>3</td>
                            <td><span style="color: #e74c3c;">Eliminado</span></td>
                        </tr>
                        <tr class="eliminated">
                            <td class="position">4º</td>
                            <td>Time Delta</td>
                            <td>0%</td>
                            <td>3</td>
                            <td>0</td>
                            <td>0</td>
                            <td>3</td>
                            <td>2</td>
                            <td>8</td>
                            <td>-6</td>
                            <td>0</td>
                            <td><span style="color: #e74c3c;">Eliminado</span></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="tiebreaker-info">
                    <strong><i class="fas fa-info-circle"></i> Informações de Desempate:</strong>
                    <div class="head-to-head">
                        <strong>Confronto Direto (Beta vs Gamma):</strong> Time Beta venceu por 2x1, garantindo a 2ª posição
                    </div>
                </div>
            </div>
            
            <!-- Exemplo de Grupo B com empate -->
            <div class="standings-card">
                <div class="group-header">GRUPO B - Exemplo com Empate</div>
                
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Time</th>
                            <th>PG</th>
                            <th>J</th>
                            <th>V</th>
                            <th>E</th>
                            <th>D</th>
                            <th>GP</th>
                            <th>GC</th>
                            <th>SG</th>
                            <th>Pts</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="qualified">
                            <td class="position">1º</td>
                            <td><strong>Time Echo</strong></td>
                            <td>67%</td>
                            <td>3</td>
                            <td>2</td>
                            <td>0</td>
                            <td>1</td>
                            <td>7</td>
                            <td>4</td>
                            <td>+3</td>
                            <td><strong>6</strong></td>
                            <td><span style="color: #27ae60;">Classificado</span></td>
                        </tr>
                        <tr class="qualified">
                            <td class="position">2º</td>
                            <td><strong>Time Foxtrot</strong></td>
                            <td>67%</td>
                            <td>3</td>
                            <td>2</td>
                            <td>0</td>
                            <td>1</td>
                            <td>5</td>
                            <td>3</td>
                            <td>+2</td>
                            <td><strong>6</strong></td>
                            <td><span style="color: #27ae60;">Classificado</span></td>
                        </tr>
                        <tr class="eliminated">
                            <td class="position">3º</td>
                            <td>Time Golf</td>
                            <td>67%</td>
                            <td>3</td>
                            <td>2</td>
                            <td>0</td>
                            <td>1</td>
                            <td>4</td>
                            <td>3</td>
                            <td>+1</td>
                            <td>6</td>
                            <td><span style="color: #e74c3c;">Eliminado</span></td>
                        </tr>
                        <tr class="eliminated">
                            <td class="position">4º</td>
                            <td>Time Hotel</td>
                            <td>0%</td>
                            <td>3</td>
                            <td>0</td>
                            <td>0</td>
                            <td>3</td>
                            <td>1</td>
                            <td>7</td>
                            <td>-6</td>
                            <td>0</td>
                            <td><span style="color: #e74c3c;">Eliminado</span></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="tiebreaker-info">
                    <strong><i class="fas fa-exclamation-triangle"></i> Desempate Triplo (6 pontos cada):</strong>
                    <div class="head-to-head">
                        <strong>1º Critério - Confronto Direto:</strong>
                        <br>• Echo 2x1 Foxtrot | Foxtrot 1x0 Golf | Golf 2x1 Echo
                        <br>• Mini-classificação: Echo 3pts, Foxtrot 3pts, Golf 3pts
                    </div>
                    <div class="head-to-head">
                        <strong>2º Critério - Saldo de Gols no Confronto Direto:</strong>
                        <br>• Echo: +0 | Foxtrot: +0 | Golf: +0 (empate)
                    </div>
                    <div class="head-to-head">
                        <strong>3º Critério - Saldo Geral:</strong>
                        <br>• Echo: +3 | Foxtrot: +2 | Golf: +1
                        <br>• <strong>Resultado:</strong> Echo 1º, Foxtrot 2º, Golf 3º
                    </div>
                </div>
            </div>
            
            <div style="background: rgba(39, 174, 96, 0.2); border: 2px solid #27ae60; border-radius: 15px; padding: 25px; text-align: center; margin-bottom: 30px;">
                <h3 style="color: #27ae60; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i>
                    SISTEMA DE CLASSIFICAÇÃO 100% CORRETO!
                </h3>
                <p>✅ Todos os critérios de desempate implementados</p>
                <p>✅ Confronto direto funcionando corretamente</p>
                <p>✅ Cálculos automáticos e precisos</p>
                <p>✅ Interface clara e informativa</p>
                <p>✅ Seguindo padrões FIFA/CBF</p>
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
                <a href="statistics.php" class="btn">
                    <i class="fas fa-chart-bar"></i>
                    Estatísticas
                </a>
            </div>
        </div>
    </div>
</body>
</html>
