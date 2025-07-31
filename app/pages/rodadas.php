<!-- Header -->
<?php include 'header_geral.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rodadas - Copa das Panelas</title>
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

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            line-height: 1.6;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0px 20px 20px 20px;
        }



        .rodadas-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 40px 0;
            gap: 25px;
        }

        .nav-button {
            background-color: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #E1BEE7;
            font-size: 1.5rem;
        }

        .nav-button:hover {
            background-color: #7B1FA2;
            color: white;
            transform: scale(1.05);
        }

        .nav-button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
            background-color: #1E1E1E;
            color: #666;
        }

        .rodada-indicator {
            background-color: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.2rem;
            min-width: 200px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .rodada-indicator::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: rgba(123, 31, 162, 0.3);
            width: var(--progress, 0%);
            transition: width 0.3s ease;
        }

        .rodada-indicator span {
            position: relative;
            z-index: 1;
            color: #E1BEE7;
        }

        .rodada-container {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .rodada-container.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .match-card {
            background-color: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
        }

        .match-card:hover {
            transform: translateY(-5px);
            background-color: #252525;
        }

        .match-card.highlight {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(243, 156, 18, 0); }
            100% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0); }
        }

        .group-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(123, 31, 162, 0.2);
            color: #E1BEE7;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #7B1FA2;
        }

        .teams-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 40px;
        }

        .team {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            max-width: 140px;
        }

        .team-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .team-logo:hover {
            transform: scale(1.1);
            border-color: #7B1FA2;
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #2A2A2A;
            border: 3px solid #7B1FA2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 1.5rem;
            color: #E1BEE7;
        }

        .team-name {
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .team-score {
            background-color: rgba(123, 31, 162, 0.2);
            border: 1px solid #7B1FA2;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1.2rem;
            min-width: 50px;
            text-align: center;
            color: #E1BEE7;
        }

        .vs-divider {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 20px;
        }

        .vs-text {
            font-weight: 700;
            font-size: 1.5rem;
            color: #E1BEE7;
            margin-bottom: 10px;
        }

        .match-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-finalizado {
            background-color: rgba(46, 125, 50, 0.2);
            color: #66BB6A;
            border: 1px solid #4CAF50;
        }

        .status-agendado {
            background-color: rgba(123, 31, 162, 0.2);
            color: #E1BEE7;
            border: 1px solid #7B1FA2;
        }

        .status-em_andamento {
            background-color: rgba(255, 152, 0, 0.2);
            color: #FFB74D;
            border: 1px solid #FF9800;
        }

        .no-tournament {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }

        .no-tournament i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: #95a5a6;
        }

        .no-tournament h3 {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .no-tournament p {
            font-size: 1.1rem;
            opacity: 0.8;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .empty-rodada {
            text-align: center;
            padding: 60px 20px;
            color: white;
            opacity: 0.7;
        }

        .empty-rodada i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #95a5a6;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2.2rem;
            }

            .matches-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .match-card {
                padding: 20px;
            }

            .teams-container {
                flex-direction: column;
                gap: 20px;
            }

            .vs-divider {
                margin: 0;
                flex-direction: row;
                gap: 15px;
            }

            .team {
                max-width: none;
                width: 100%;
            }

            .rodadas-navigation {
                gap: 15px;
            }

            .nav-button {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <?php
        require_once '../config/conexao.php';
        require_once '../classes/TournamentManager.php';

        $pdo = conectar();
        $tournamentManager = new TournamentManager($pdo);

        // Obter apenas o torneio ativo
        $tournament = $tournamentManager->getCurrentTournament();
    ?>

    <div class="main-container">


        <?php if ($tournament): ?>
            <?php
                // Buscar e organizar rodadas
                $rodadas_data = obterRodadas($pdo, $tournament['id']);

                if (!empty($rodadas_data)):
            ?>
                <div class="rodadas-navigation">
                    <button class="nav-button" id="prevBtn" onclick="previousRodada()">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <div class="rodada-indicator" id="rodadaIndicator">
                        <span>1ª RODADA</span>
                        <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;" id="rodadaStats">
                            <!-- Estatísticas da rodada -->
                        </div>
                    </div>

                    <button class="nav-button" id="nextBtn" onclick="nextRodada()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div id="rodadasContainer">
                    <?php foreach ($rodadas_data as $rodada_num => $jogos): ?>
                        <div class="rodada-container" data-rodada="<?= $rodada_num ?>">
                            <div class="matches-grid">
                                <?php foreach ($jogos as $jogo): ?>
                                    <div class="match-card">
                                        <div class="group-badge">
                                            GRUPO <?= htmlspecialchars($jogo['group_letter']) ?>
                                        </div>

                                        <div class="teams-container">
                                            <div class="team">
                                                <?php if (!empty($jogo['team1_logo'])): ?>
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($jogo['team1_logo']) ?>"
                                                         alt="<?= htmlspecialchars($jogo['team1_name']) ?>"
                                                         class="team-logo">
                                                <?php else: ?>
                                                    <div class="logo-placeholder">
                                                        <i class="fas fa-shield-alt"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="team-name"><?= htmlspecialchars($jogo['team1_name']) ?></div>
                                                <div class="team-score"><?= $jogo['team1_goals'] ?? '-' ?></div>
                                            </div>

                                            <div class="vs-divider">
                                                <div class="vs-text">VS</div>
                                                <div class="match-status status-<?= $jogo['status'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $jogo['status'])) ?>
                                                </div>
                                                <?php if (!empty($jogo['match_date'])): ?>
                                                    <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;">
                                                        <?= date('d/m H:i', strtotime($jogo['match_date'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="team">
                                                <?php if (!empty($jogo['team2_logo'])): ?>
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($jogo['team2_logo']) ?>"
                                                         alt="<?= htmlspecialchars($jogo['team2_name']) ?>"
                                                         class="team-logo">
                                                <?php else: ?>
                                                    <div class="logo-placeholder">
                                                        <i class="fas fa-shield-alt"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="team-name"><?= htmlspecialchars($jogo['team2_name']) ?></div>
                                                <div class="team-score"><?= $jogo['team2_goals'] ?? '-' ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-rodada fade-in">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nenhuma Rodada Encontrada</h3>
                    <p>Não há jogos da fase de grupos para este campeonato.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-tournament fade-in">
                <i class="fas fa-trophy"></i>
                <h3>Nenhum Campeonato Ativo</h3>
                <p>Não há nenhum campeonato ativo no momento. Entre em contato com a administração para mais informações.</p>
            </div>
        <?php endif; ?>
    </div>

<?php
function obterRodadas($pdo, $tournament_id) {
    // Buscar jogos da fase de grupos do torneio ativo
    $stmt = $pdo->prepare("
        SELECT m.*,
               t1.nome as team1_name, t1.logo as team1_logo,
               t2.nome as team2_name, t2.logo as team2_logo,
               g.nome as group_name,
               ROW_NUMBER() OVER (PARTITION BY m.group_id ORDER BY m.created_at) as rodada
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN grupos g ON m.group_id = g.id
        WHERE m.tournament_id = ? AND m.phase = 'grupos'
        ORDER BY rodada, g.nome, m.created_at
    ");
    $stmt->execute([$tournament_id]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rodadas = [];

    if (!empty($matches)) {
        foreach ($matches as $match) {
            $rodada_num = $match['rodada'];
            $group_name = $match['group_name'] ?? 'Grupo';
            $group_letter = substr($group_name, -1);

            $rodadas[$rodada_num][] = [
                'id' => $match['id'],
                'team1_name' => $match['team1_name'] ?? 'TBD',
                'team2_name' => $match['team2_name'] ?? 'TBD',
                'team1_logo' => $match['team1_logo'],
                'team2_logo' => $match['team2_logo'],
                'team1_goals' => $match['team1_goals'],
                'team2_goals' => $match['team2_goals'],
                'group_name' => $group_name,
                'group_letter' => $group_letter,
                'status' => $match['status'] ?? 'agendado',
                'match_date' => $match['match_date']
            ];
        }
    }

    return $rodadas;
}
?>

    <script>
        let currentRodadaIndex = 0;
        let totalRodadas = 0;

        function initializeRodadas() {
            const rodadaContainers = document.querySelectorAll('.rodada-container');
            totalRodadas = rodadaContainers.length;

            if (totalRodadas > 0) {
                showRodada(0);
                updateNavigationButtons();
            }
        }

        function showRodada(index) {
            const rodadaContainers = document.querySelectorAll('.rodada-container');
            const indicator = document.getElementById('rodadaIndicator');
            const statsDiv = document.getElementById('rodadaStats');

            // Esconder todas as rodadas
            rodadaContainers.forEach(container => {
                container.classList.remove('active');
            });

            // Mostrar rodada atual
            if (rodadaContainers[index]) {
                rodadaContainers[index].classList.add('active');

                // Atualizar indicador
                const rodadaNum = rodadaContainers[index].getAttribute('data-rodada');
                const span = indicator.querySelector('span');
                span.textContent = `${rodadaNum}ª RODADA`;

                // Contar jogos e estatísticas
                const matchCards = rodadaContainers[index].querySelectorAll('.match-card');
                const totalJogos = matchCards.length;
                let jogosFinalizados = 0;

                matchCards.forEach(card => {
                    const status = card.querySelector('.match-status');
                    if (status && status.textContent.toLowerCase().includes('finalizado')) {
                        jogosFinalizados++;
                    }
                });

                // Atualizar estatísticas
                statsDiv.textContent = `${totalJogos} jogos • ${jogosFinalizados} finalizados • ${totalJogos - jogosFinalizados} pendentes`;

                // Atualizar progresso
                const progress = ((index + 1) / totalRodadas) * 100;
                indicator.style.setProperty('--progress', `${progress}%`);
            }

            updateNavigationButtons();
        }

        function updateNavigationButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            prevBtn.disabled = currentRodadaIndex === 0;
            nextBtn.disabled = currentRodadaIndex === totalRodadas - 1;
        }

        function nextRodada() {
            if (currentRodadaIndex < totalRodadas - 1) {
                currentRodadaIndex++;
                showRodada(currentRodadaIndex);
            }
        }

        function previousRodada() {
            if (currentRodadaIndex > 0) {
                currentRodadaIndex--;
                showRodada(currentRodadaIndex);
            }
        }

        // Navegação por teclado
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                previousRodada();
            } else if (e.key === 'ArrowRight') {
                nextRodada();
            }
        });

        // Inicialização
        document.addEventListener("DOMContentLoaded", function() {
            // Inicializar rodadas
            initializeRodadas();

            // Animações fade-in
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((el, i) => {
                setTimeout(() => {
                    el.classList.add('visible');
                }, i * 150);
            });

            // Adicionar efeitos de hover nos cards
            const matchCards = document.querySelectorAll('.match-card');
            matchCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px) scale(1)';
                });
            });

            // Verificar automaticamente progressão das eliminatórias (silencioso)
            setTimeout(() => {
                // Verificar classificação para eliminatórias (fase de grupos)
                fetch('../actions/funcoes/auto_classificacao.php?ajax=1')
                    .then(response => response.json())
                    .then(data => {
                        // Execução silenciosa - sem notificação
                    })
                    .catch(error => console.log('Verificação automática de classificação falhou:', error));

                // Verificar progressão das eliminatórias
                fetch('../actions/funcoes/progressao_eliminatorias.php?ajax=1')
                    .then(response => response.json())
                    .then(data => {
                        // Execução silenciosa - sem notificação
                    })
                    .catch(error => console.log('Verificação automática de progressão falhou:', error));
            }, 2000); // Aguardar 2 segundos após carregar a página
        });

    </script>

<?php include 'footer.php'?>
</body>
</html>