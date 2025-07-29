<?php
session_start();
require_once '../../config/conexao.php';

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();

// Templates predefinidos
$templates = [
    1 => [
        'id' => 1,
        'name' => 'Copa Simples',
        'description' => 'Torneio com fase de grupos seguida de eliminatórias',
        'teams' => 8,
        'groups' => 2,
        'format' => 'Grupos + Eliminatórias',
        'duration' => '2-3 semanas',
        'phases' => ['Fase de Grupos', 'Semifinais', 'Final', 'Disputa 3º Lugar'],
        'details' => [
            'structure' => '8 times divididos em 2 grupos de 4 times cada',
            'group_phase' => 'Todos jogam contra todos dentro do grupo',
            'classification' => 'Os 2 primeiros de cada grupo se classificam',
            'elimination' => 'Semifinais cruzadas e final',
            'third_place' => 'Disputa de 3º lugar entre perdedores das semifinais'
        ]
    ],
    2 => [
        'id' => 2,
        'name' => 'Liga Completa',
        'description' => 'Todos jogam contra todos em turno único',
        'teams' => 12,
        'groups' => 1,
        'format' => 'Pontos Corridos',
        'duration' => '4-6 semanas',
        'phases' => ['Turno Único'],
        'details' => [
            'structure' => '12 times em grupo único',
            'format' => 'Sistema de pontos corridos',
            'matches' => 'Cada time joga 11 partidas (contra todos os outros)',
            'winner' => 'Campeão é quem somar mais pontos',
            'tiebreaker' => 'Critérios: saldo de gols, gols marcados, confronto direto'
        ]
    ],
    3 => [
        'id' => 3,
        'name' => 'Mata-Mata',
        'description' => 'Eliminação direta desde o início',
        'teams' => 16,
        'groups' => 0,
        'format' => 'Eliminação Direta',
        'duration' => '1-2 semanas',
        'phases' => ['Oitavas de Final', 'Quartas de Final', 'Semifinais', 'Final', 'Disputa 3º Lugar'],
        'details' => [
            'structure' => '16 times em chaveamento eliminatório',
            'format' => 'Eliminação direta em jogo único',
            'rounds' => '5 rodadas: Oitavas, Quartas, Semifinais, Final e 3º lugar',
            'matches' => 'Total de 15 jogos',
            'advantage' => 'Formato rápido e emocionante'
        ]
    ],
    4 => [
        'id' => 4,
        'name' => 'Copa das Panelas Oficial',
        'description' => 'Formato oficial da Copa das Panelas',
        'teams' => 16,
        'groups' => 4,
        'format' => 'Grupos + Oitavas + Quartas + Semi + Final',
        'duration' => '4-5 semanas',
        'phases' => ['Fase de Grupos', 'Oitavas de Final', 'Quartas de Final', 'Semifinais', 'Final', 'Disputa 3º Lugar'],
        'details' => [
            'structure' => '16 times divididos em 4 grupos de 4 times cada',
            'group_phase' => 'Todos jogam contra todos dentro do grupo',
            'classification' => 'Os 2 primeiros de cada grupo se classificam (8 times)',
            'elimination' => 'Oitavas, Quartas, Semifinais e Final',
            'matches' => 'Total de 31 jogos (24 na fase de grupos + 7 eliminatórias)',
            'third_place' => 'Disputa de 3º lugar entre perdedores das semifinais'
        ]
    ]
];

$template_id = $_GET['id'] ?? 1;
$template = $templates[$template_id] ?? $templates[1];

// Processar criação do torneio
if ($_POST && isset($_POST['create_tournament'])) {
    $tournament_name = $_POST['tournament_name'];
    $custom_teams = $_POST['custom_teams'] ?? $template['teams'];
    $custom_groups = $_POST['custom_groups'] ?? $template['groups'];
    $custom_description = $_POST['custom_description'] ?? $template['description'];

    try {
        // Verificar se a tabela existe
        $pdo->query("SELECT 1 FROM tournaments LIMIT 1");

        // Criar o torneio (fornecendo TODOS os campos obrigatórios: nome, name, year, data_inicio, data_fim)
        $stmt = $pdo->prepare("
            INSERT INTO tournaments (nome, name, year, description, status, data_inicio, data_fim, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $tournament_name,           // nome
            $tournament_name,           // name (mesmo valor)
            date('Y'),                  // year
            $custom_description,        // description
            'draft',                    // status
            date('Y-m-d'),             // data_inicio (hoje)
            date('Y-m-d', strtotime('+30 days'))  // data_fim (30 dias a partir de hoje)
        ]);

        if ($result) {
            $tournament_id = $pdo->lastInsertId();
            $_SESSION['success'] = "Torneio '{$tournament_name}' criado com sucesso!";
            header("Location: edit_tournament_simple.php?id={$tournament_id}&created=1");
            exit;
        } else {
            $error = "Erro ao criar torneio";
        }

    } catch (Exception $e) {
        $error = "Erro ao criar torneio: " . $e->getMessage();
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            $error .= " - Tabela 'tournaments' não existe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Template - <?= htmlspecialchars($template['name']) ?></title>
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
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }
        
        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            color: #7B1FA2;
        }
        
        .btn-standard {
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            padding: 15px 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 8px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-success {
            background: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
            border-color: #45a049;
        }

        .alert {
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #2A2A2A;
            border-left-color: #F44336;
            color: #F44336;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .preview-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .preview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .preview-card h3 {
            color: #E1BEE7;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-card h3 i {
            color: #7B1FA2;
        }

        .spec-list {
            list-style: none;
            padding: 0;
        }

        .spec-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .spec-list li:last-child {
            border-bottom: none;
        }

        .spec-label {
            color: #9E9E9E;
            font-weight: 500;
        }

        .spec-value {
            color: #E1BEE7;
            font-weight: 600;
        }

        .phases-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .phase-item {
            background: #2A2A2A;
            padding: 12px 16px;
            border-radius: 6px;
            border-left: 3px solid #7B1FA2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .phase-number {
            background: #7B1FA2;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .details-list {
            list-style: none;
            padding: 0;
        }

        .details-list li {
            padding: 8px 0;
            color: #9E9E9E;
            line-height: 1.5;
        }

        .details-list li strong {
            color: #E1BEE7;
        }

        .creation-form {
            background: #1E1E1E;
            border-left: 4px solid #4CAF50;
            border-radius: 8px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .creation-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4CAF50, #81C784);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #9E9E9E;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #7B1FA2;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
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

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .preview-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
                align-items: center;
            }

            .btn-standard {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-eye"></i> Preview: <?= htmlspecialchars($template['name']) ?></h1>
                <p style="color: #9E9E9E; font-size: 1.1rem; margin-top: 8px;"><?= htmlspecialchars($template['description']) ?></p>
            </div>
            <div>
                <a href="tournament_templates.php" class="btn-standard">
                    <i class="fas fa-arrow-left"></i> Voltar aos Templates
                </a>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Preview Grid -->
        <div class="preview-grid">
            <!-- Especificações -->
            <div class="preview-card fade-in">
                <h3><i class="fas fa-cogs"></i> Especificações</h3>
                <ul class="spec-list">
                    <li>
                        <span class="spec-label"><i class="fas fa-users"></i> Times</span>
                        <span class="spec-value"><?= $template['teams'] ?> times</span>
                    </li>
                    <li>
                        <span class="spec-label"><i class="fas fa-layer-group"></i> Grupos</span>
                        <span class="spec-value"><?= $template['groups'] ?> grupos</span>
                    </li>
                    <li>
                        <span class="spec-label"><i class="fas fa-trophy"></i> Formato</span>
                        <span class="spec-value"><?= htmlspecialchars($template['format']) ?></span>
                    </li>
                    <li>
                        <span class="spec-label"><i class="fas fa-clock"></i> Duração</span>
                        <span class="spec-value"><?= htmlspecialchars($template['duration']) ?></span>
                    </li>
                </ul>
            </div>

            <!-- Fases do Torneio -->
            <div class="preview-card fade-in">
                <h3><i class="fas fa-list-ol"></i> Fases do Torneio</h3>
                <div class="phases-list">
                    <?php foreach ($template['phases'] as $index => $phase): ?>
                        <div class="phase-item">
                            <div class="phase-number"><?= $index + 1 ?></div>
                            <span><?= htmlspecialchars($phase) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Detalhes Completos -->
        <div class="preview-card fade-in" style="margin-bottom: 30px;">
            <h3><i class="fas fa-info-circle"></i> Detalhes do Template</h3>
            <ul class="details-list">
                <?php foreach ($template['details'] as $key => $detail): ?>
                    <li><strong><?= ucfirst(str_replace('_', ' ', $key)) ?>:</strong> <?= htmlspecialchars($detail) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Formulário de Criação -->
        <div class="creation-form fade-in">
            <h3 style="color: #81C784; font-size: 1.4rem; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-plus-circle"></i> Criar Torneio com Este Template
            </h3>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tournament_name">Nome do Torneio *</label>
                        <input type="text" id="tournament_name" name="tournament_name"
                               value="<?= htmlspecialchars($template['name']) ?> <?= date('Y') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="custom_teams">Número de Times</label>
                        <input type="number" id="custom_teams" name="custom_teams"
                               value="<?= $template['teams'] ?>" min="4" max="32">
                    </div>

                    <div class="form-group">
                        <label for="custom_groups">Número de Grupos</label>
                        <input type="number" id="custom_groups" name="custom_groups"
                               value="<?= $template['groups'] ?>" min="0" max="8">
                    </div>

                    <div class="form-group">
                        <label for="custom_description">Descrição</label>
                        <textarea id="custom_description" name="custom_description"
                                  placeholder="Descrição personalizada do torneio..."><?= htmlspecialchars($template['description']) ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="tournament_templates.php" class="btn-standard">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <a href="template_configurator.php?id=<?= $template['id'] ?>" class="btn-standard">
                        <i class="fas fa-cogs"></i> Configuração Avançada
                    </a>
                    <button type="submit" name="create_tournament" class="btn-standard btn-success">
                        <i class="fas fa-plus"></i> Criar Torneio
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Animações Copa das Panelas
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('visible');
                }, index * 150);
            });

            // Validação do formulário
            const form = document.querySelector('form');
            const teamsInput = document.getElementById('custom_teams');
            const groupsInput = document.getElementById('custom_groups');

            if (form && teamsInput && groupsInput) {
                form.addEventListener('submit', function(e) {
                    const teams = parseInt(teamsInput.value);
                    const groups = parseInt(groupsInput.value);

                    if (groups > 0 && teams < groups * 2) {
                        e.preventDefault();
                        alert('Número de times deve ser pelo menos o dobro do número de grupos para formar grupos equilibrados.');
                        teamsInput.focus();
                        return false;
                    }

                    if (teams < 4) {
                        e.preventDefault();
                        alert('Número mínimo de times é 4.');
                        teamsInput.focus();
                        return false;
                    }
                });

                // Auto-ajuste de grupos baseado no número de times
                teamsInput.addEventListener('change', function() {
                    const teams = parseInt(this.value);
                    const currentGroups = parseInt(groupsInput.value);

                    if (currentGroups > 0 && teams < currentGroups * 2) {
                        const suggestedGroups = Math.floor(teams / 4);
                        if (suggestedGroups > 0) {
                            groupsInput.value = suggestedGroups;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
