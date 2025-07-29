<?php
session_start();
require_once '../../config/conexao.php';

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();

// Templates predefinidos (mesmo array da página anterior)
$templates = [
    1 => [
        'id' => 1,
        'name' => 'Copa Simples',
        'description' => 'Torneio com fase de grupos seguida de eliminatórias',
        'teams' => 8,
        'groups' => 2,
        'format' => 'Grupos + Eliminatórias',
        'duration' => '2-3 semanas'
    ],
    2 => [
        'id' => 2,
        'name' => 'Liga Completa',
        'description' => 'Todos jogam contra todos em turno único',
        'teams' => 12,
        'groups' => 1,
        'format' => 'Pontos Corridos',
        'duration' => '4-6 semanas'
    ],
    3 => [
        'id' => 3,
        'name' => 'Mata-Mata',
        'description' => 'Eliminação direta desde o início',
        'teams' => 16,
        'groups' => 0,
        'format' => 'Eliminação Direta',
        'duration' => '1-2 semanas'
    ],
    4 => [
        'id' => 4,
        'name' => 'Copa das Panelas Oficial',
        'description' => 'Formato oficial da Copa das Panelas',
        'teams' => 16,
        'groups' => 4,
        'format' => 'Grupos + Oitavas + Quartas + Semi + Final',
        'duration' => '4-5 semanas'
    ]
];

$template_id = $_GET['id'] ?? 1;
$template = $templates[$template_id] ?? $templates[1];
$step = $_GET['step'] ?? 1;

// Processar formulário
if ($_POST) {
    if (isset($_POST['step1'])) {
        // Redirecionar para step 2 com dados
        $params = http_build_query([
            'id' => $template_id,
            'step' => 2,
            'name' => $_POST['tournament_name'],
            'teams' => $_POST['teams'],
            'groups' => $_POST['groups']
        ]);
        header("Location: template_configurator.php?$params");
        exit;
    }
    
    if (isset($_POST['create_final'])) {
        // Criar torneio final (fornecendo TODOS os campos obrigatórios: nome, name, year, data_inicio, data_fim)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tournaments (nome, name, year, description, status, data_inicio, data_fim, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $result = $stmt->execute([
                $_POST['final_name'],       // nome
                $_POST['final_name'],       // name (mesmo valor)
                date('Y'),                  // year
                $_POST['final_description'], // description
                'draft',                    // status
                date('Y-m-d'),             // data_inicio (hoje)
                date('Y-m-d', strtotime('+30 days'))  // data_fim (30 dias a partir de hoje)
            ]);

            if ($result) {
                $tournament_id = $pdo->lastInsertId();
                $_SESSION['success'] = "Torneio '{$_POST['final_name']}' criado com sucesso!";
                header("Location: edit_tournament_simple.php?id={$tournament_id}&created=1");
                exit;
            }
        } catch (Exception $e) {
            $error = "Erro ao criar torneio: " . $e->getMessage();
        }
    }
}

// Dados do step anterior
$tournament_name = $_GET['name'] ?? $template['name'] . ' ' . date('Y');
$teams_count = $_GET['teams'] ?? $template['teams'];
$groups_count = $_GET['groups'] ?? $template['groups'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Template - <?= htmlspecialchars($template['name']) ?></title>
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
            max-width: 1000px;
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
            text-align: center;
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
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-header h1 i {
            color: #7B1FA2;
        }

        .steps-indicator {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 25px;
            background: #2A2A2A;
            border: 2px solid #444;
            transition: all 0.3s ease;
        }

        .step.active {
            background: #7B1FA2;
            border-color: #7B1FA2;
            color: white;
        }

        .step.completed {
            background: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }

        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .config-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .config-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .config-card h3 {
            color: #E1BEE7;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .config-card h3 i {
            color: #7B1FA2;
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

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #9E9E9E;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
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
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #7B1FA2;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-item {
            background: #2A2A2A;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #7B1FA2;
            text-align: center;
        }

        .summary-label {
            color: #9E9E9E;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .summary-value {
            color: #E1BEE7;
            font-size: 1.2rem;
            font-weight: 600;
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .steps-indicator {
                flex-direction: column;
                align-items: center;
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
        <div class="page-header">
            <h1><i class="fas fa-cogs"></i> Configurar Template</h1>
            <p style="color: #9E9E9E; font-size: 1.1rem; margin-top: 8px;">
                Template: <?= htmlspecialchars($template['name']) ?>
            </p>
        </div>

        <!-- Indicador de Passos -->
        <div class="steps-indicator">
            <div class="step <?= $step == 1 ? 'active' : ($step > 1 ? 'completed' : '') ?>">
                <div class="step-number"><?= $step > 1 ? '✓' : '1' ?></div>
                <span>Configurações Básicas</span>
            </div>
            <div class="step <?= $step == 2 ? 'active' : ($step > 2 ? 'completed' : '') ?>">
                <div class="step-number"><?= $step > 2 ? '✓' : '2' ?></div>
                <span>Revisão e Confirmação</span>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <!-- Step 1: Configurações Básicas -->
            <div class="config-card">
                <h3><i class="fas fa-edit"></i> Configurações Básicas</h3>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="tournament_name">Nome do Torneio *</label>
                            <input type="text" id="tournament_name" name="tournament_name"
                                   value="<?= htmlspecialchars($tournament_name) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="teams">Número de Times *</label>
                            <input type="number" id="teams" name="teams"
                                   value="<?= $teams_count ?>" min="4" max="32" required>
                            <small style="color: #9E9E9E;">Mínimo: 4 times, Máximo: 32 times</small>
                        </div>

                        <div class="form-group">
                            <label for="groups">Número de Grupos</label>
                            <input type="number" id="groups" name="groups"
                                   value="<?= $groups_count ?>" min="0" max="8">
                            <small style="color: #9E9E9E;">0 = sem grupos (eliminação direta)</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="template_preview.php?id=<?= $template_id ?>" class="btn-standard">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" name="step1" class="btn-standard btn-success">
                            <i class="fas fa-arrow-right"></i> Próximo Passo
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($step == 2): ?>
            <!-- Step 2: Revisão e Confirmação -->
            <div class="config-card">
                <h3><i class="fas fa-check-circle"></i> Revisão e Confirmação</h3>

                <!-- Resumo das Configurações -->
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Nome do Torneio</div>
                        <div class="summary-value"><?= htmlspecialchars($tournament_name) ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Template Base</div>
                        <div class="summary-value"><?= htmlspecialchars($template['name']) ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Número de Times</div>
                        <div class="summary-value"><?= $teams_count ?> times</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Número de Grupos</div>
                        <div class="summary-value"><?= $groups_count ?> grupos</div>
                    </div>
                </div>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="final_description">Descrição do Torneio</label>
                            <textarea id="final_description" name="final_description"
                                      placeholder="Adicione uma descrição personalizada para o torneio..."><?= htmlspecialchars($template['description']) ?></textarea>
                        </div>
                    </div>

                    <!-- Campos ocultos -->
                    <input type="hidden" name="final_name" value="<?= htmlspecialchars($tournament_name) ?>">
                    <input type="hidden" name="final_teams" value="<?= $teams_count ?>">
                    <input type="hidden" name="final_groups" value="<?= $groups_count ?>">

                    <div class="form-actions">
                        <a href="template_configurator.php?id=<?= $template_id ?>&step=1" class="btn-standard">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" name="create_final" class="btn-standard btn-success">
                            <i class="fas fa-plus"></i> Criar Torneio
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validação do formulário no step 1
            const teamsInput = document.getElementById('teams');
            const groupsInput = document.getElementById('groups');

            if (teamsInput && groupsInput) {
                function validateConfiguration() {
                    const teams = parseInt(teamsInput.value);
                    const groups = parseInt(groupsInput.value);

                    if (groups > 0 && teams < groups * 2) {
                        groupsInput.setCustomValidity('Número de times deve ser pelo menos o dobro do número de grupos');
                    } else {
                        groupsInput.setCustomValidity('');
                    }
                }

                teamsInput.addEventListener('input', validateConfiguration);
                groupsInput.addEventListener('input', validateConfiguration);

                // Auto-ajuste de grupos baseado no número de times
                teamsInput.addEventListener('change', function() {
                    const teams = parseInt(this.value);
                    const currentGroups = parseInt(groupsInput.value);

                    if (currentGroups > 0 && teams < currentGroups * 2) {
                        const suggestedGroups = Math.floor(teams / 4);
                        if (suggestedGroups > 0) {
                            groupsInput.value = suggestedGroups;
                        } else {
                            groupsInput.value = 0;
                        }
                        validateConfiguration();
                    }
                });
            }
        });
    </script>
</body>
</html>
