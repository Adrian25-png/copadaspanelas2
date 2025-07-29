<?php
session_start();

try {
    require_once '../../config/conexao.php';
    $pdo = conectar();
    
    // Estatísticas básicas
    $stmt = $pdo->query("SELECT COUNT(*) FROM tournaments");
    $total_tournaments = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM times");
    $total_teams = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM matches");
    $total_matches = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $total_tournaments = $total_teams = $total_matches = 0;
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Copa das Panelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="shortcut icon" href="../../../public/img/ESCUDO COPA DAS PANELAS.png" type="image/x-icon">
    <style>
        /* Reset básico */
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Space Grotesk', sans-serif;
        }

        body {
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            line-height: 1.6;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .dashboard-welcome {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px 20px;
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-welcome::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7, #7B1FA2);
        }

        .dashboard-welcome h1 {
            color: #E1BEE7;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .dashboard-welcome h1 i {
            color: #7B1FA2;
            font-size: 2.2rem;
        }

        .dashboard-welcome p {
            color: #E0E0E0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(123, 31, 162, 0.3);
            background-color: #252525;
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #7B1FA2;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: #E1BEE7;
            margin-bottom: 8px;
            display: block;
        }

        .stat-label {
            color: #E0E0E0;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(123, 31, 162, 0.2);
            background-color: #252525;
        }

        .action-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(123, 31, 162, 0.3);
        }

        .action-header i {
            font-size: 1.5rem;
            color: #7B1FA2;
        }

        .action-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #E1BEE7;
            margin: 0;
        }

        .action-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .action-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #E0E0E0;
            text-decoration: none;
            padding: 12px 15px;
            background: rgba(123, 31, 162, 0.1);
            border: 1px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action-link i {
            color: #7B1FA2;
            width: 18px;
            text-align: center;
        }

        .action-link:hover {
            background: rgba(123, 31, 162, 0.2);
            border-color: #7B1FA2;
            color: #E1BEE7;
            transform: translateX(5px);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #F44336;
            color: #EF5350;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }

        .error-message i {
            font-size: 1.5rem;
            color: #F44336;
        }

        .footer-info {
            text-align: center;
            margin-top: 60px;
            padding: 30px;
            background: rgba(123, 31, 162, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(123, 31, 162, 0.3);
        }

        .footer-info p {
            color: #E0E0E0;
            opacity: 0.8;
            margin: 0;
            font-size: 1rem;
        }

        /* Animações */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }

            .dashboard-welcome h1 {
                font-size: 2rem;
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .action-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="main-container">
        <div class="dashboard-welcome fade-in">
            <h1>
                <i class="fas fa-trophy"></i>
                Painel de Administração
            </h1>
            <p>Bem-vindo ao sistema Copa das Panelas - Gerencie torneios, times e muito mais</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message fade-in">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Erro: <?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card fade-in" style="animation-delay: 0.1s;">
                <i class="fas fa-trophy stat-icon"></i>
                <span class="stat-number"><?= $total_tournaments ?></span>
                <div class="stat-label">Torneios</div>
            </div>
            <div class="stat-card fade-in" style="animation-delay: 0.2s;">
                <i class="fas fa-users stat-icon"></i>
                <span class="stat-number"><?= $total_teams ?></span>
                <div class="stat-label">Times</div>
            </div>
            <div class="stat-card fade-in" style="animation-delay: 0.3s;">
                <i class="fas fa-futbol stat-icon"></i>
                <span class="stat-number"><?= $total_matches ?></span>
                <div class="stat-label">Jogos</div>
            </div>
        </div>

        <div class="actions-grid">
            <div class="action-card fade-in" style="animation-delay: 0.4s;">
                <div class="action-header">
                    <i class="fas fa-trophy"></i>
                    <h3 class="action-title">Torneios</h3>
                </div>
                <div class="action-links">
                    <a href="tournament_list.php" class="action-link">
                        <i class="fas fa-list"></i>
                        <span>Lista de Torneios</span>
                    </a>
                    <a href="create_tournament.php" class="action-link">
                        <i class="fas fa-plus"></i>
                        <span>Criar Torneio</span>
                    </a>
                    <a href="tournament_templates.php" class="action-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Templates</span>
                    </a>
                </div>
            </div>

            <div class="action-card fade-in" style="animation-delay: 0.5s;">
                <div class="action-header">
                    <i class="fas fa-users"></i>
                    <h3 class="action-title">Times</h3>
                </div>
                <div class="action-links">
                    <a href="all_teams.php" class="action-link">
                        <i class="fas fa-users"></i>
                        <span>Todos os Times</span>
                    </a>
                    <a href="team_manager.php" class="action-link">
                        <i class="fas fa-cog"></i>
                        <span>Gerenciar Times</span>
                    </a>
                </div>
            </div>

            <div class="action-card fade-in" style="animation-delay: 0.6s;">
                <div class="action-header">
                    <i class="fas fa-futbol"></i>
                    <h3 class="action-title">Jogos</h3>
                </div>
                <div class="action-links">
                    <a href="global_calendar.php" class="action-link">
                        <i class="fas fa-calendar"></i>
                        <span>Calendário</span>
                    </a>
                    <a href="bulk_results.php" class="action-link">
                        <i class="fas fa-edit"></i>
                        <span>Resultados</span>
                    </a>
                    <a href="match_reports.php" class="action-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Relatórios</span>
                    </a>
                </div>
            </div>

            <div class="action-card fade-in" style="animation-delay: 0.7s;">
                <div class="action-header">
                    <i class="fas fa-broadcast-tower"></i>
                    <h3 class="action-title">Transmissão</h3>
                </div>
                <div class="action-links">
                    <a href="gerenciar_transmissao.php" class="action-link">
                        <i class="fas fa-broadcast-tower"></i>
                        <span>Gerenciar Live</span>
                    </a>
                    <a href="../JogosProximos.php" class="action-link">
                        <i class="fas fa-eye"></i>
                        <span>Ver Página Pública</span>
                    </a>
                </div>
            </div>

            <div class="action-card fade-in" style="animation-delay: 0.8s;">
                <div class="action-header">
                    <i class="fas fa-user-shield"></i>
                    <h3 class="action-title">Administradores</h3>
                </div>
                <div class="action-links">
                    <a href="admin_manager.php" class="action-link">
                        <i class="fas fa-users-cog"></i>
                        <span>Gerenciar Admins</span>
                    </a>
                    <a href="create_admin.php" class="action-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Cadastrar Admin</span>
                    </a>
                    <a href="admin_permissions.php" class="action-link">
                        <i class="fas fa-key"></i>
                        <span>Permissões</span>
                    </a>
                </div>
            </div>

            <div class="action-card fade-in" style="animation-delay: 0.9s;">
                <div class="action-header">
                    <i class="fas fa-chart-line"></i>
                    <h3 class="action-title">Sistema</h3>
                </div>
                <div class="action-links">
                    <a href="statistics.php" class="action-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Estatísticas</span>
                    </a>
                    <a href="system_health.php" class="action-link">
                        <i class="fas fa-heartbeat"></i>
                        <span>Status do Sistema</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-info fade-in" style="animation-delay: 1s;">
            <p>
                <i class="fas fa-trophy" style="color: #7B1FA2; margin-right: 8px;"></i>
                Sistema Copa das Panelas - Painel Administrativo
            </p>
        </div>
    </div>

    <script>
        // Adicionar animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Adicionar efeitos hover dinâmicos
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Efeito de contagem nos números das estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);

                const counter = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(counter);
                    }
                    stat.textContent = currentValue;
                }, 50);
            });
        });
    </script>
</body>
</html>
