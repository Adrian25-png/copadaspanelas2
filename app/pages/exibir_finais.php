<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fases Finais - Copa das Panelas</title>
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

        /* Sobrescrever CSS externo */
        body {
            display: flex !important;
            flex-direction: column !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            padding-top: 0 !important; /* Remove o padding-top do CSS externo */
        }

        /* Sobrescrever o position fixed do header externo */
        header {
            position: relative !important;
            width: 100% !important;
            z-index: 1000 !important;
            top: auto !important;
            left: auto !important;
        }

        .main-content {
            flex: 1;
            margin-top: 250px; /* Espaço para o header fixo */
            padding: 20px;
            padding-bottom: 60px;
        }

        footer {
            margin-top: auto;
            width: 100%;
            flex-shrink: 0;
        }





        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding-bottom: 40px;
        }

        /* CHAVEAMENTO PRINCIPAL */
        .tournament-bracket {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 60px;
            padding: 40px 20px;
            min-height: 600px;
            position: relative;
            overflow-x: auto;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.8), rgba(15, 5, 29, 0.9));
            border-radius: 15px;
            margin: 20px 0;
        }

        .bracket-round {
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            min-width: 200px;
        }

        /* Espaçamento específico para cada fase */
        .bracket-round:nth-child(1) .match-card { /* Oitavas */
            margin-bottom: 15px;
        }

        .bracket-round:nth-child(2) .match-card { /* Quartas */
            margin-bottom: 60px;
        }

        .bracket-round:nth-child(3) .match-card { /* Semifinal */
            margin-bottom: 120px;
        }

        .bracket-round:nth-child(4) .match-card { /* Final */
            margin-bottom: 0;
        }

        .round-title {
            text-align: center;
            color: #FFD700;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 6px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .match-card {
            background: linear-gradient(135deg, rgba(40, 40, 50, 0.95), rgba(25, 25, 35, 0.95));
            border: 2px solid rgba(255, 215, 0, 0.4);
            border-radius: 10px;
            margin: 8px 0;
            overflow: hidden;
            min-width: 180px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .match-card:hover {
            border-color: rgba(255, 215, 0, 0.8);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.5);
            transform: translateY(-2px);
        }

        .match-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #FFD700, transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .match-card:hover::before {
            opacity: 1;
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
            gap: 10px;
            flex: 1;
        }

        .team-logo {
            width: 24px;
            height: 18px;
            object-fit: cover;
            border-radius: 3px;
        }

        .team-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #ffffff;
        }

        .team-score {
            font-size: 1.1rem;
            font-weight: 700;
            color: #FFD700;
            min-width: 30px;
            text-align: center;
        }

        /* FINAL ESPECIAL */
        .final-round {
            position: relative;
        }

        .final-round .round-title {
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
            border: 2px solid rgba(255, 215, 0, 0.5);
            font-size: 1.2rem;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
        }

        .final-round .match-card {
            border: 3px solid rgba(255, 215, 0, 0.6);
            background: rgba(255, 215, 0, 0.1);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
        }

        .trophy-icon {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            color: #FFD700;
            text-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
            animation: trophyGlow 2s ease-in-out infinite alternate;
        }

        @keyframes trophyGlow {
            0% { text-shadow: 0 0 15px rgba(255, 215, 0, 0.8); }
            100% { text-shadow: 0 0 25px rgba(255, 215, 0, 1); }
        }

        /* TERCEIRO LUGAR DENTRO DO BRACKET */
        .third-place-round {
            position: absolute;
            bottom: 20px;
            right: 0;
            min-width: 200px;
        }

        /* Ajustar container para acomodar terceiro lugar */
        .tournament-bracket {
            position: relative;
            padding-bottom: 120px;
        }

        .bronze-medal-icon {
            text-align: center;
            margin-bottom: 10px;
        }

        .bronze-medal-icon i {
            font-size: 2rem;
            color: #CD7F32;
            text-shadow: 0 0 10px rgba(205, 127, 50, 0.6);
        }

        /* LINHAS CONECTORAS */
        .bracket-round::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -30px;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, #FFD700, rgba(255, 215, 0, 0.3));
            transform: translateY(-50%);
            z-index: 1;
        }

        /* Remover linha da última fase (Final) */
        .bracket-round:last-child::after,
        .third-place-round::after {
            display: none;
        }

        /* Linhas verticais conectoras */
        .bracket-round::before {
            content: '';
            position: absolute;
            top: 25%;
            right: -30px;
            width: 2px;
            height: 50%;
            background: linear-gradient(180deg, #FFD700, rgba(255, 215, 0, 0.3));
            z-index: 1;
        }

        /* Remover linha vertical da primeira e última fase */
        .bracket-round:first-child::before,
        .bracket-round:last-child::before,
        .third-place-round::before {
            display: none;
        }

        /* Linhas especiais para conectar semifinal à final */
        .bracket-round:nth-child(3)::after { /* Semifinal */
            width: 45px;
            right: -45px;
        }

        .bracket-round:nth-child(3)::before { /* Semifinal */
            right: -45px;
        }

        /* CSS do terceiro lugar agora integrado ao bracket */
        .third-place-match {
            border: 2px solid #cd7f32 !important;
            background: rgba(205, 127, 50, 0.1);
        }

        /* RESPONSIVIDADE MELHORADA */
        @media (max-width: 1024px) {
            .tournament-bracket {
                gap: 40px;
                padding: 20px 10px;
                overflow-x: auto;
            }

            .bracket-round {
                min-width: 160px;
            }

            .match-card {
                min-width: 160px;
            }

            /* Ajustar linhas conectoras */
            .bracket-round::after {
                width: 25px;
                right: -25px;
            }

            .bracket-round:nth-child(3)::after {
                width: 35px;
                right: -35px;
            }

            .bracket-round:nth-child(3)::before {
                right: -35px;
            }
        }

        .third-place-match .team-score {
            color: #cd7f32 !important;
        }

        /* NO MATCHES */
        .no-matches {
            text-align: center;
            padding: 100px 20px;
            color: rgba(255, 255, 255, 0.8);
        }

        .no-matches i {
            font-size: 4rem;
            color: #F59E0B;
            margin-bottom: 30px;
            display: block;
        }

        .no-matches h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .no-matches p {
            font-size: 1.1rem;
            line-height: 1.6;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 1200px) {
            .tournament-bracket {
                gap: 30px;
                padding: 15px 10px;
            }

            .bracket-round {
                min-height: 350px;
            }
        }

        @media (max-width: 900px) {
            .main-content {
                margin-top: 220px; /* Menos espaço em tablets */
                padding: 15px;
                padding-bottom: 60px;
            }

            .tournament-bracket {
                flex-direction: column;
                align-items: center;
                gap: 30px;
                min-height: auto;
            }

            .bracket-round {
                min-height: auto;
                width: 100%;
                max-width: 300px;
            }

            .match-card {
                min-width: 100%;
            }

            .third-place-round {
                margin-top: 20px;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin-top: 200px; /* Menos espaço em mobile */
                padding: 10px;
                padding-bottom: 80px;
            }

            .team-name {
                font-size: 0.8rem;
            }

            .round-title {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'header_geral.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php
            include '../config/conexao.php';
            require_once '../classes/TournamentManager.php';

            $pdo = conectar();
            $tournamentManager = new TournamentManager($pdo);
            $tournament = $tournamentManager->getCurrentTournament();

            function renderTeamLogo($pdo, $team_id) {
                if (!$team_id) {
                    echo "<div style='width: 24px; height: 18px; background: rgba(255, 255, 255, 0.2); border-radius: 3px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 0.6rem;'><i class='fas fa-question'></i></div>";
                    return;
                }

                $stmt = $pdo->prepare("SELECT logo FROM times WHERE id = ?");
                $stmt->execute([$team_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row && $row['logo']) {
                    $logo_base64 = base64_encode($row['logo']);
                    echo "<img class='team-logo' src='data:image/png;base64,{$logo_base64}' alt='Logo'>";
                } else {
                    echo "<div style='width: 24px; height: 18px; background: rgba(255, 255, 255, 0.2); border-radius: 3px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 0.6rem;'><i class='fas fa-shield-alt'></i></div>";
                }
            }
        ?>

        <div class="container">
            <?php if ($tournament): ?>
                <?php
                    $tournament_id = $tournament['id'];

                    // Verificar jogos de eliminatórias
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total
                        FROM matches
                        WHERE tournament_id = ? AND phase IN ('Oitavas', 'Quartas', 'Semifinal', 'Final')
                    ");
                    $stmt->execute([$tournament_id]);
                    $total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                    if ($total_matches > 0):
                        // Buscar fases existentes
                        $phases = ['Oitavas', 'Quartas', 'Semifinal', 'Final'];
                        $bracket_data = [];

                        foreach ($phases as $phase) {
                            $stmt = $pdo->prepare("
                                SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
                                FROM matches m
                                LEFT JOIN times t1 ON m.team1_id = t1.id
                                LEFT JOIN times t2 ON m.team2_id = t2.id
                                WHERE m.tournament_id = ? AND m.phase = ?
                                ORDER BY m.created_at
                            ");
                            $stmt->execute([$tournament_id, $phase]);
                            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (!empty($matches)) {
                                $bracket_data[$phase] = $matches;
                            }
                        }
                        ?>

                        <!-- Chaveamento Principal -->
                        <div class="tournament-bracket">
                            <?php foreach ($bracket_data as $phase => $matches): ?>
                                <div class="bracket-round <?= $phase === 'Final' ? 'final-round' : '' ?>">
                                    <?php if ($phase === 'Final'): ?>
                                        <div class="trophy-icon"><i class="fas fa-trophy"></i></div>
                                    <?php endif; ?>

                                    <div class="round-title"><?= htmlspecialchars($phase) ?></div>

                                    <?php foreach ($matches as $match): ?>
                                        <div class="match-card">
                                            <div class="team-line">
                                                <div class="team-info">
                                                    <?php renderTeamLogo($pdo, $match['team1_id']); ?>
                                                    <span class="team-name"><?= htmlspecialchars($match['team1_name'] ?? 'TBD') ?></span>
                                                </div>
                                                <span class="team-score"><?= $match['team1_goals'] ?? '-' ?></span>
                                            </div>
                                            <div class="team-line">
                                                <div class="team-info">
                                                    <?php renderTeamLogo($pdo, $match['team2_id']); ?>
                                                    <span class="team-name"><?= htmlspecialchars($match['team2_name'] ?? 'TBD') ?></span>
                                                </div>
                                                <span class="team-score"><?= $match['team2_goals'] ?? '-' ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php
                            // Buscar jogo do terceiro lugar
                            $stmt = $pdo->prepare("
                                SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
                                FROM matches m
                                LEFT JOIN times t1 ON m.team1_id = t1.id
                                LEFT JOIN times t2 ON m.team2_id = t2.id
                                WHERE m.tournament_id = ? AND m.phase = '3º Lugar'
                                ORDER BY m.created_at
                                LIMIT 1
                            ");
                            $stmt->execute([$tournament_id]);
                            $third_place_match = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($third_place_match):
                            ?>
                                <!-- Terceiro Lugar dentro do bracket -->
                                <div class="bracket-round third-place-round">
                                    <div class="bronze-medal-icon"><i class="fas fa-medal"></i></div>
                                    <div class="round-title">3º LUGAR</div>

                                    <div class="match-card third-place-match">
                                        <div class="team-line">
                                            <div class="team-info">
                                                <?php renderTeamLogo($pdo, $third_place_match['team1_id']); ?>
                                                <span class="team-name"><?= htmlspecialchars($third_place_match['team1_name'] ?? 'TBD') ?></span>
                                            </div>
                                            <span class="team-score"><?= $third_place_match['team1_goals'] ?? '-' ?></span>
                                        </div>
                                        <div class="team-line">
                                            <div class="team-info">
                                                <?php renderTeamLogo($pdo, $third_place_match['team2_id']); ?>
                                                <span class="team-name"><?= htmlspecialchars($third_place_match['team2_name'] ?? 'TBD') ?></span>
                                            </div>
                                            <span class="team-score"><?= $third_place_match['team2_goals'] ?? '-' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <div class="no-matches">
                            <i class="fas fa-calendar-times"></i>
                            <h3>Fases Finais Não Iniciadas</h3>
                            <p>As eliminatórias ainda não foram configuradas para este torneio.<br>
                            Aguarde a conclusão da fase de grupos para o início das eliminatórias.</p>
                        </div>
                    <?php endif; ?>



            <?php else: ?>
                <div class="no-matches">
                    <i class="fas fa-trophy"></i>
                    <h3>Nenhum Torneio Ativo</h3>
                    <p>Não há nenhum torneio ativo no momento.<br>
                    Aguarde o início de um novo torneio!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        // Verificação automática removida para evitar recarregamentos indesejados
        // A progressão automática agora acontece apenas no gerenciador de jogos
        document.addEventListener('DOMContentLoaded', function() {
            // Página carrega normalmente sem verificações automáticas
            console.log('Página de exibição das finais carregada');
        });
    </script>
</body>
</html>
