<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Demo - Fases Finais - Copa das Panelas</title>
    <link rel="stylesheet" href="../../public/css/global_standards.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .tournament-bracket {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            min-height: 500px;
            position: relative;
            flex-wrap: wrap;
        }

        .bracket-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-width: 200px;
        }

        .final-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            order: 2;
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

        .phase-title {
            color: #FFD700;
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .match-simple {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .team-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
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
            border-radius: 2px;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9CA3AF;
            font-size: 0.5rem;
        }

        .team-name {
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .team-score {
            color: #FFD700;
            font-size: 0.8rem;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }

        /* Conectores simples */
        .bracket-section::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 20px;
            height: 2px;
            background: rgba(255, 215, 0, 0.5);
            transform: translateY(-50%);
        }

        .bracket-section:nth-child(odd)::after {
            right: -20px;
        }

        .bracket-section:nth-child(even)::after {
            left: -20px;
        }

        .no-matches {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .no-matches i {
            font-size: 3rem;
            color: #F59E0B;
            margin-bottom: 20px;
            display: block;
        }

        .no-matches h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .no-matches p {
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Responsividade */
        @media (max-width: 1000px) {
            .tournament-bracket {
                flex-direction: column;
                gap: 25px;
            }

            .bracket-section::after {
                display: none;
            }

            .final-section {
                order: 0;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .bracket-section {
                min-width: 100%;
                max-width: 300px;
            }

            .team-name {
                font-size: 0.7rem;
            }

            .trophy {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'header_geral.php'; ?>
    
    <div class="main">
        <div class="container fade-in">
            <div class="tournament-bracket">
                <!-- Oitavas de Final -->
                <div class="bracket-section">
                    <div class="phase-title">Oitavas de Final</div>
                    
                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Barcelona FC</span>
                            </div>
                            <span class="team-score">3</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Real Madrid</span>
                            </div>
                            <span class="team-score">1</span>
                        </div>
                    </div>

                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Manchester City</span>
                            </div>
                            <span class="team-score">2</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Liverpool FC</span>
                            </div>
                            <span class="team-score">0</span>
                        </div>
                    </div>

                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Bayern Munich</span>
                            </div>
                            <span class="team-score">4</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">PSG</span>
                            </div>
                            <span class="team-score">2</span>
                        </div>
                    </div>

                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Juventus</span>
                            </div>
                            <span class="team-score">1</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">AC Milan</span>
                            </div>
                            <span class="team-score">3</span>
                        </div>
                    </div>
                </div>

                <!-- Quartas de Final -->
                <div class="bracket-section">
                    <div class="phase-title">Quartas de Final</div>
                    
                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Barcelona FC</span>
                            </div>
                            <span class="team-score">2</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Manchester City</span>
                            </div>
                            <span class="team-score">1</span>
                        </div>
                    </div>

                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Bayern Munich</span>
                            </div>
                            <span class="team-score">3</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">AC Milan</span>
                            </div>
                            <span class="team-score">0</span>
                        </div>
                    </div>
                </div>

                <!-- Semifinais -->
                <div class="bracket-section">
                    <div class="phase-title">Semifinal</div>
                    
                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Barcelona FC</span>
                            </div>
                            <span class="team-score">1</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Bayern Munich</span>
                            </div>
                            <span class="team-score">2</span>
                        </div>
                    </div>
                </div>

                <!-- Final Central -->
                <div class="final-section">
                    <div class="trophy-container">
                        <div class="trophy"><i class="fas fa-trophy"></i></div>
                    </div>
                    <div class="phase-title">FINAL</div>
                    
                    <div class="match-simple">
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">Bayern Munich</span>
                            </div>
                            <span class="team-score">-</span>
                        </div>
                        <div class="team-line">
                            <div class="team-info">
                                <div class="team-logo"><i class="fas fa-shield-alt"></i></div>
                                <span class="team-name">TBD</span>
                            </div>
                            <span class="team-score">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach(element => {
                element.classList.add('visible');
            });
        });
    </script>
</body>
</html>
