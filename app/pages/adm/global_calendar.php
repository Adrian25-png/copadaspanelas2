<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


session_start();
require_once '../../config/conexao.php';

$pdo = conectar();

// Filtros
$tournament_filter = $_GET['tournament'] ?? '';
$month_filter = $_GET['month'] ?? date('Y-m');
$status_filter = $_GET['status'] ?? '';

// Construir query
$where_conditions = ["1=1"];
$params = [];

if ($tournament_filter) {
    $where_conditions[] = "m.tournament_id = ?";
    $params[] = $tournament_filter;
}

if ($month_filter) {
    $where_conditions[] = "DATE_FORMAT(m.match_date, '%Y-%m') = ?";
    $params[] = $month_filter;
}

if ($status_filter) {
    $where_conditions[] = "m.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Obter jogos
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               t1.nome as team1_name, 
               t2.nome as team2_name,
               g.nome as group_name,
               tour.name as tournament_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        LEFT JOIN grupos g ON m.group_id = g.id
        LEFT JOIN tournaments tour ON m.tournament_id = tour.id
        WHERE $where_clause
        ORDER BY m.match_date ASC, m.created_at ASC
    ");
    $stmt->execute($params);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $matches = [];
    $_SESSION['error'] = "Erro ao carregar jogos: " . $e->getMessage();
}

// Obter torneios para filtro
try {
    $stmt = $pdo->query("SELECT id, name FROM tournaments ORDER BY name");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tournaments = [];
}

// Agrupar jogos por data
$matches_by_date = [];
foreach ($matches as $match) {
    if ($match['match_date']) {
        $date = date('Y-m-d', strtotime($match['match_date']));
        $matches_by_date[$date][] = $match;
    } else {
        $matches_by_date['sem_data'][] = $match;
    }
}

// Estatísticas
$total_matches = count($matches);
$finished_matches = count(array_filter($matches, fn($m) => $m['status'] === 'finalizado'));
$scheduled_matches = count(array_filter($matches, fn($m) => $m['status'] === 'agendado'));
$ongoing_matches = count(array_filter($matches, fn($m) => $m['status'] === 'em_andamento'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Global - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
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
            margin: 0;
            padding: 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-title .icon {
            font-size: 3rem;
            color: rgba(255,255,255,0.9);
        }

        .header-subtitle {
            margin-top: 10px;
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .content-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #E0E0E0;
            margin-bottom: 5px;
        }

        .filter-input {
            padding: 12px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filter-input option {
            background: #1a1a2e;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #d946ef);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        
        .calendar-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .date-header {
            font-size: 1.3rem;
            font-weight: 700;
            color: #E0E0E0;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(139, 92, 246, 0.3));
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .date-header i {
            color: #6366f1;
            font-size: 1.2rem;
        }

        .match-item {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.2));
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .match-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .match-time {
            font-weight: 700;
            color: #6366f1;
            font-size: 1rem;
            background: rgba(99, 102, 241, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .match-teams {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .team-name {
            font-weight: 600;
            color: #E0E0E0;
            font-size: 1rem;
        }

        .vs-text {
            color: #f59e0b;
            font-weight: 700;
            font-size: 1.1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .match-score {
            font-size: 1.2rem;
            font-weight: 700;
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .match-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .status-finalizado {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .status-agendado {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        .status-em_andamento {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        .status-cancelado {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .match-info {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            margin-top: 8px;
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.6);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: rgba(99, 102, 241, 0.3);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #E0E0E0;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }

            .header-title h1 {
                font-size: 2rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .match-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }

            .main-container {
                padding: 15px;
            }

            .content-card,
            .filters,
            .calendar-section {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-calendar-alt icon"></i>
                    <div>
                        <h1>Calendário Global</h1>
                        <div class="header-subtitle">Todos os jogos do sistema em uma visão unificada</div>
                    </div>
                </div>
                <a href="dashboard_simple.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Torneio</label>
                        <select name="tournament" class="filter-input">
                            <option value="">Todos os torneios</option>
                            <?php foreach ($tournaments as $tournament): ?>
                                <option value="<?= $tournament['id'] ?>" <?= $tournament_filter == $tournament['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tournament['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Mês</label>
                        <input type="month" name="month" class="filter-input" value="<?= htmlspecialchars($month_filter) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-input">
                            <option value="">Todos os status</option>
                            <option value="agendado" <?= $status_filter === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                            <option value="em_andamento" <?= $status_filter === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="finalizado" <?= $status_filter === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            <option value="cancelado" <?= $status_filter === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_matches ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $finished_matches ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $scheduled_matches ?></div>
                <div class="stat-label">Agendados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $ongoing_matches ?></div>
                <div class="stat-label">Em Andamento</div>
            </div>
        </div>
        
        <!-- Calendário de Jogos -->
        <?php if (!empty($matches_by_date)): ?>
            <?php foreach ($matches_by_date as $date => $date_matches): ?>
                <div class="calendar-section">
                    <div class="date-header">
                        <i class="fas fa-calendar-day"></i>
                        <?php if ($date === 'sem_data'): ?>
                            Jogos sem Data Definida
                        <?php else: ?>
                            <?= date('d/m/Y - l', strtotime($date)) ?>
                        <?php endif; ?>
                        <span style="font-size: 0.9rem; opacity: 0.8;">(<?= count($date_matches) ?> jogos)</span>
                    </div>
                    
                    <?php foreach ($date_matches as $match): ?>
                        <div class="match-item">
                            <div class="match-time">
                                <?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : 'A definir' ?>
                            </div>
                            
                            <div class="match-teams">
                                <span class="team-name"><?= htmlspecialchars($match['team1_name']) ?></span>
                                <?php if ($match['status'] === 'finalizado' && $match['team1_goals'] !== null): ?>
                                    <span class="match-score"><?= $match['team1_goals'] ?> x <?= $match['team2_goals'] ?></span>
                                <?php else: ?>
                                    <span class="vs-text">VS</span>
                                <?php endif; ?>
                                <span class="team-name"><?= htmlspecialchars($match['team2_name']) ?></span>
                            </div>
                            
                            <div>
                                <span class="match-status status-<?= $match['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $match['status'])) ?>
                                </span>
                                <div class="match-info">
                                    <?= htmlspecialchars($match['tournament_name']) ?>
                                    <?php if ($match['group_name']): ?>
                                        - <?= htmlspecialchars($match['group_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Nenhum Jogo Encontrado</h3>
                <p>Não há jogos que correspondam aos filtros selecionados.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das seções do calendário
            const sections = document.querySelectorAll('.calendar-section');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 150);
            });

            // Animação dos cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animação dos itens de partida
            const matchItems = document.querySelectorAll('.match-item');
            matchItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 500 + (index * 50));
            });

            // Animação do header
            const header = document.querySelector('.page-header');
            if (header) {
                header.style.opacity = '0';
                header.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    header.style.transition = 'all 0.8s ease';
                    header.style.opacity = '1';
                    header.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>
