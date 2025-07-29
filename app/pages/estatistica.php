<?php
require_once "../config/conexao.php";
require_once '../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

// Obter apenas o torneio ativo
$tournament = $tournamentManager->getCurrentTournament();

// Função para converter dados binários da imagem em base64
function exibirImagem($imagem) {
    if ($imagem != null) {
        return 'data:image/jpeg;base64,' . base64_encode($imagem);
    } else {
        return 'default.jpg'; // caminho para uma imagem padrão
    }
}

// Buscar dados dos jogadores do torneio ativo
if ($tournament) {
    $tournament_id = $tournament['id'];

    // Buscar dados dos jogadores ordenados por gols
    $sql_gols = "SELECT j.nome, j.gols, j.assistencias, j.cartoes_amarelos, j.cartoes_vermelhos, j.imagem, t.nome AS nome_time
                 FROM jogadores j
                 JOIN times t ON j.time_id = t.id
                 WHERE t.tournament_id = ?
                 ORDER BY j.gols DESC";
    $stmt = $pdo->prepare($sql_gols);
    $stmt->execute([$tournament_id]);
    $jogadores_gols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar dados dos jogadores ordenados por assistências
    $sql_assistencias = "SELECT j.nome, j.gols, j.assistencias, j.cartoes_amarelos, j.cartoes_vermelhos, j.imagem, t.nome AS nome_time
                         FROM jogadores j
                         JOIN times t ON j.time_id = t.id
                         WHERE t.tournament_id = ?
                         ORDER BY j.assistencias DESC";
    $stmt = $pdo->prepare($sql_assistencias);
    $stmt->execute([$tournament_id]);
    $jogadores_assistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar dados dos jogadores ordenados por cartões amarelos
    $sql_cartoes_amarelos = "SELECT j.nome, j.gols, j.assistencias, j.cartoes_amarelos, j.cartoes_vermelhos, j.imagem, t.nome AS nome_time
                             FROM jogadores j
                             JOIN times t ON j.time_id = t.id
                             WHERE t.tournament_id = ?
                             ORDER BY j.cartoes_amarelos DESC";
    $stmt = $pdo->prepare($sql_cartoes_amarelos);
    $stmt->execute([$tournament_id]);
    $jogadores_cartoes_amarelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar dados dos jogadores ordenados por cartões vermelhos
    $sql_cartoes_vermelhos = "SELECT j.nome, j.gols, j.assistencias, j.cartoes_amarelos, j.cartoes_vermelhos, j.imagem, t.nome AS nome_time
                              FROM jogadores j
                              JOIN times t ON j.time_id = t.id
                              WHERE t.tournament_id = ?
                              ORDER BY j.cartoes_vermelhos DESC";
    $stmt = $pdo->prepare($sql_cartoes_vermelhos);
    $stmt->execute([$tournament_id]);
    $jogadores_cartoes_vermelhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $jogadores_gols = [];
    $jogadores_assistencias = [];
    $jogadores_cartoes_amarelos = [];
    $jogadores_cartoes_vermelhos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estatísticas dos Jogadores</title>
    <link rel="stylesheet" href="../../public/css/global_standards.css">
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/estatistica.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../public/imgs/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">    
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .no-tournament {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
            color: white;
        }

        .no-tournament i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #95a5a6;
        }

        .tournament-info {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .tournament-info h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #f39c12;
        }
    </style>
</head>
<body>
    <!-- Navegação -->
    <?php include 'header_geral.php'; ?>

    <div class="main">
    <?php if ($tournament): ?>
    <div class="container fade-in">

        <!-- Seção de Gols -->
        <div class="section">
            <h1>Gols</h1>
            <?php if (!empty($jogadores_gols)) {
                $i = 1;
                foreach ($jogadores_gols as $jogador) {
                    echo "<div class='player-card'>";
                    echo "<div class='index'>$i</div>";
                    echo "<img src='" . exibirImagem($jogador['imagem']) . "' alt='Imagem do Jogador'>";
                    echo "<div class='info'>";
                    echo "<span class='name'>" . htmlspecialchars($jogador['nome']) . "</span>";
                    echo "<span class='team'>" . htmlspecialchars($jogador['nome_time']) . "</span>";
                    echo "</div>";
                    echo "<span class='stat'>" . htmlspecialchars($jogador['gols']) . "</span>";
                    echo "</div>";
                    $i++;
                }
            } else {
                echo "<div class='player-card'>Nenhum jogador encontrado</div>";
            } ?>
        </div>

        <!-- Seção de Assistências -->
        <div class="section">
            <h1>Assistências</h1>
            <?php if (!empty($jogadores_assistencias)) {
                $i = 1;
                foreach ($jogadores_assistencias as $jogador) {
                    echo "<div class='player-card'>";
                    echo "<div class='index'>$i</div>";
                    echo "<img src='" . exibirImagem($jogador['imagem']) . "' alt='Imagem do Jogador'>";
                    echo "<div class='info'>";
                    echo "<span class='name'>" . htmlspecialchars($jogador['nome']) . "</span>";
                    echo "<span class='team'>" . htmlspecialchars($jogador['nome_time']) . "</span>";
                    echo "</div>";
                    echo "<span class='stat'>" . htmlspecialchars($jogador['assistencias']) . "</span>";
                    echo "</div>";
                    $i++;
                }
            } else {
                echo "<div class='player-card'>Nenhum jogador encontrado</div>";
            } ?>
        </div>

        <!-- Seção de Cartões Amarelos -->
        <div class="section">
            <h1>Cartões Amarelos</h1>
            <?php if (!empty($jogadores_cartoes_amarelos)) {
                $i = 1;
                foreach ($jogadores_cartoes_amarelos as $jogador) {
                    echo "<div class='player-card'>";
                    echo "<div class='index'>$i</div>";
                    echo "<img src='" . exibirImagem($jogador['imagem']) . "' alt='Imagem do Jogador'>";
                    echo "<div class='info'>";
                    echo "<span class='name'>" . htmlspecialchars($jogador['nome']) . "</span>";
                    echo "<span class='team'>" . htmlspecialchars($jogador['nome_time']) . "</span>";
                    echo "</div>";
                    echo "<span class='stat'>" . htmlspecialchars($jogador['cartoes_amarelos']) . "</span>";
                    echo "</div>";
                    $i++;
                }
            } else {
                echo "<div class='player-card'>Nenhum jogador encontrado</div>";
            } ?>
        </div>

        <!-- Seção de Cartões Vermelhos -->
        <div class="section">
            <h1>Cartões Vermelhos</h1>
            <?php if (!empty($jogadores_cartoes_vermelhos)) {
                $i = 1;
                foreach ($jogadores_cartoes_vermelhos as $jogador) {
                    echo "<div class='player-card'>";
                    echo "<div class='index'>$i</div>";
                    echo "<img src='" . exibirImagem($jogador['imagem']) . "' alt='Imagem do Jogador'>";
                    echo "<div class='info'>";
                    echo "<span class='name'>" . htmlspecialchars($jogador['nome']) . "</span>";
                    echo "<span class='team'>" . htmlspecialchars($jogador['nome_time']) . "</span>";
                    echo "</div>";
                    echo "<span class='stat'>" . htmlspecialchars($jogador['cartoes_vermelhos']) . "</span>";
                    echo "</div>";
                    $i++;
                }
            } else {
                echo "<div class='player-card'>Nenhum jogador encontrado</div>";
            } ?>
        </div>

        </div>
    </div>
    <?php else: ?>
        <div class="no-tournament fade-in">
            <i class="fas fa-chart-bar"></i>
            <h3>Nenhum Campeonato Ativo</h3>
            <p>Não há nenhum campeonato ativo no momento. Entre em contato com a administração.</p>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                setTimeout(() => el.classList.add('visible'), i * 20);
            });
        });
    </script>

<?php include 'footer.php'?>  
</body>
</html>
