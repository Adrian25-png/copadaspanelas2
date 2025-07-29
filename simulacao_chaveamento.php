<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulação - Chaveamento Completo - Copa das Panelas</title>
    <link rel="stylesheet" href="../../public/css/global_standards.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow-x: auto;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: auto;
        }

        .main-content {
            flex: 1;
            margin-top: 140px;
            padding: 20px;
            padding-bottom: 60px;
        }

        footer {
            margin-top: auto;
            width: 100%;
            flex-shrink: 0;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            padding-bottom: 80px;
        }

        .finals-section {
            margin-bottom: 60px;
        }

        .third-place-section {
            margin-bottom: 40px;
        }

        /* CHAVEAMENTO ESTILO BRACKET */
        .tournament-bracket {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 60px;
            width: 100%;
            padding: 40px 20px;
            overflow-x: auto;
            min-height: 600px;
        }

        .bracket-round {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 30px;
            min-width: 200px;
            position: relative;
        }

        .round-title {
            text-align: center;
            color: #FFD700;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .bracket-matches {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        /* Match Cards */
        .match-simple {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.2);
            overflow: hidden;
            min-width: 180px;
        }

        .team-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .team-line:last-child {
            border-bottom: none;
        }

        .team-line:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .team-info {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .team-logo {
            width: 20px;
            height: 15px;
            background: #FFD700;
            border-radius: 2px;
        }

        .team-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #ffffff;
        }

        .team-score {
            font-size: 1.1rem;
            font-weight: 600;
            color: #FFD700;
            min-width: 25px;
            text-align: center;
        }

        /* Final especial */
        .final-round {
            position: relative;
        }

        .trophy-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .trophy {
            font-size: 3rem;
            color: #FFD700;
            text-shadow: 0 0 15px rgba(255, 215, 0, 0.6);
        }

        /* Terceiro Lugar */
        .third-place-wrapper {
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .third-place-container {
            background: rgba(205, 127, 50, 0.15);
            border: 2px solid #cd7f32;
            border-radius: 15px;
            padding: 20px;
            width: 100%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(205, 127, 50, 0.3);
        }

        .third-place-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .bronze-medal {
            color: #cd7f32;
            font-size: 1.5rem;
            text-shadow: 0 0 10px rgba(205, 127, 50, 0.8);
        }

        .third-place-title {
            color: #cd7f32;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .third-place-match {
            border: 2px solid #cd7f32 !important;
            background: rgba(205, 127, 50, 0.05);
        }

        .third-place-match .team-score {
            color: #cd7f32 !important;
        }

        .third-place-result {
            margin-top: 15px;
            padding: 12px;
            background: rgba(205, 127, 50, 0.3);
            border-radius: 10px;
            color: #cd7f32;
            font-weight: bold;
            font-size: 1rem;
        }

        /* Responsividade */
        @media (max-width: 1200px) {
            .tournament-bracket {
                gap: 40px;
                padding: 20px 10px;
            }
        }

        @media (max-width: 900px) {
            .main-content {
                margin-top: 120px;
                padding: 15px;
                padding-bottom: 60px;
            }

            .tournament-bracket {
                flex-direction: column;
                gap: 30px;
                align-items: center;
            }

            .bracket-round {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin-top: 110px;
                padding: 10px;
                padding-bottom: 80px;
            }

            .team-name {
                font-size: 0.8rem;
            }

            .trophy {
                font-size: 2.5rem;
            }

            .third-place-container {
                padding: 18px 15px;
                max-width: 95%;
            }
        }

        /* Título da simulação */
        .simulation-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 215, 0, 0.1);
            border: 2px solid #FFD700;
            border-radius: 15px;
        }

        .simulation-title {
            color: #FFD700;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .simulation-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'app/pages/header_geral.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Cabeçalho da Simulação -->
            <div class="simulation-header">
                <div class="simulation-title">
                    <i class="fas fa-trophy"></i> SIMULAÇÃO - CHAVEAMENTO COMPLETO
                </div>
                <div class="simulation-subtitle">
                    Visualização de como ficará com Oitavas, Quartas, Semifinal e Final
                </div>
            </div>

            <!-- Seção das Finais -->
            <div class="finals-section">
                <div class="tournament-bracket">
                    <!-- OITAVAS DE FINAL -->
                    <div class="bracket-round">
                        <div class="round-title">OITAVAS</div>
                        <div class="bracket-matches">
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time A</span>
                                    </div>
                                    <span class="team-score">3</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time B</span>
                                    </div>
                                    <span class="team-score">1</span>
                                </div>
                            </div>
                            
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time C</span>
                                    </div>
                                    <span class="team-score">2</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time D</span>
                                    </div>
                                    <span class="team-score">0</span>
                                </div>
                            </div>
                            
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time E</span>
                                    </div>
                                    <span class="team-score">1</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time F</span>
                                    </div>
                                    <span class="team-score">4</span>
                                </div>
                            </div>
                            
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time G</span>
                                    </div>
                                    <span class="team-score">2</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time H</span>
                                    </div>
                                    <span class="team-score">1</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QUARTAS DE FINAL -->
                    <div class="bracket-round">
                        <div class="round-title">QUARTAS</div>
                        <div class="bracket-matches">
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time A</span>
                                    </div>
                                    <span class="team-score">2</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time C</span>
                                    </div>
                                    <span class="team-score">1</span>
                                </div>
                            </div>
                            
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time F</span>
                                    </div>
                                    <span class="team-score">3</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time G</span>
                                    </div>
                                    <span class="team-score">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEMIFINAL -->
                    <div class="bracket-round">
                        <div class="round-title">SEMIFINAL</div>
                        <div class="bracket-matches">
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time A</span>
                                    </div>
                                    <span class="team-score">1</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time F</span>
                                    </div>
                                    <span class="team-score">2</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FINAL -->
                    <div class="bracket-round final-round">
                        <div class="trophy-container">
                            <div class="trophy"><i class="fas fa-trophy"></i></div>
                        </div>
                        <div class="round-title">FINAL</div>
                        <div class="bracket-matches">
                            <div class="match-simple">
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time F</span>
                                    </div>
                                    <span class="team-score">3</span>
                                </div>
                                <div class="team-line">
                                    <div class="team-info">
                                        <div class="team-logo"></div>
                                        <span class="team-name">Time X</span>
                                    </div>
                                    <span class="team-score">1</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seção do Terceiro Lugar -->
            <div class="third-place-section">
                <div class="third-place-wrapper">
                    <div class="third-place-container">
                        <div class="third-place-header">
                            <i class="fas fa-medal bronze-medal"></i>
                            <div class="third-place-title">DISPUTA DO 3º LUGAR</div>
                        </div>
                        
                        <div class="match-simple third-place-match">
                            <div class="team-line">
                                <div class="team-info">
                                    <div class="team-logo" style="background: #cd7f32;"></div>
                                    <span class="team-name">Time A</span>
                                </div>
                                <span class="team-score">2</span>
                            </div>
                            
                            <div class="team-line">
                                <div class="team-info">
                                    <div class="team-logo" style="background: #cd7f32;"></div>
                                    <span class="team-name">Time Y</span>
                                </div>
                                <span class="team-score">0</span>
                            </div>
                        </div>
                        
                        <div class="third-place-result">
                            <i class="fas fa-medal"></i> 3º Lugar: Time A
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'app/pages/footer.php'; ?>
</body>
</html>
