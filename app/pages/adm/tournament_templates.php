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

// Templates predefinidos
$templates = [
    [
        'id' => 1,
        'name' => 'Copa Simples',
        'description' => 'Torneio com fase de grupos seguida de eliminatórias',
        'teams' => 8,
        'groups' => 2,
        'format' => 'Grupos + Eliminatórias',
        'duration' => '2-3 semanas'
    ],
    [
        'id' => 2,
        'name' => 'Liga Completa',
        'description' => 'Todos jogam contra todos em turno único',
        'teams' => 12,
        'groups' => 1,
        'format' => 'Pontos Corridos',
        'duration' => '4-6 semanas'
    ],
    [
        'id' => 3,
        'name' => 'Mata-Mata',
        'description' => 'Eliminação direta desde o início',
        'teams' => 16,
        'groups' => 0,
        'format' => 'Eliminação Direta',
        'duration' => '1-2 semanas'
    ],
    [
        'id' => 4,
        'name' => 'Copa das Panelas Oficial',
        'description' => 'Formato oficial da Copa das Panelas',
        'teams' => 16,
        'groups' => 4,
        'format' => 'Grupos + Oitavas + Quartas + Semi + Final',
        'duration' => '4-5 semanas'
    ]
];

if ($_POST && isset($_POST['template_id'])) {
    $template_id = $_POST['template_id'];
    $tournament_name = $_POST['tournament_name'];

    // Debug
    $debug_info = "POST recebido - Template ID: {$template_id}, Nome: {$tournament_name}";

    // Encontrar o template selecionado
    $selected_template = null;
    foreach ($templates as $template) {
        if ($template['id'] == $template_id) {
            $selected_template = $template;
            break;
        }
    }

    if ($selected_template) {
        try {
            // Verificar se a tabela existe
            $pdo->query("SELECT 1 FROM tournaments LIMIT 1");

            // Criar o torneio automaticamente
            $stmt = $pdo->prepare("
                INSERT INTO tournaments (name, description, teams_count, groups_count, format_type, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'draft', NOW())
            ");

            $description = "Torneio criado usando template: " . $selected_template['name'];

            $result = $stmt->execute([
                $tournament_name,
                $description,
                $selected_template['teams'],
                $selected_template['groups'],
                $selected_template['format']
            ]);

            if ($result) {
                $tournament_id = $pdo->lastInsertId();

                if ($tournament_id) {
                    // Redirecionar para editar o torneio criado
                    header("Location: edit_tournament_simple.php?id={$tournament_id}&created=1");
                    exit;
                } else {
                    $error = "Erro: ID do torneio não foi gerado";
                }
            } else {
                $error = "Erro: Falha na execução da query";
            }

        } catch (Exception $e) {
            $error = "Erro ao criar torneio: " . $e->getMessage();
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                $error .= " - Tabela 'tournaments' não existe. Execute o debug primeiro.";
            }
        }
    } else {
        $error = "Template não encontrado: ID {$template_id}";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates de Torneio - Copa das Panelas</title>
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

        .alert {
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #2A2A2A;
            border-left-color: #4CAF50;
            color: #4CAF50;
        }

        .alert-error {
            background: #2A2A2A;
            border-left-color: #F44336;
            color: #F44336;
        }

        .alert-info {
            background: #2A2A2A;
            border-left-color: #2196F3;
            color: #2196F3;
        }

        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .template-card {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .template-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3);
        }

        .template-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .template-icon {
            font-size: 2.5rem;
            color: #7B1FA2;
        }

        .template-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #E1BEE7;
        }

        .template-description {
            color: #9E9E9E;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 1rem;
        }

        .template-specs {
            margin-bottom: 25px;
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .spec-item:last-child {
            border-bottom: none;
        }

        .spec-label {
            color: #9E9E9E;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .spec-value {
            color: #E1BEE7;
            font-weight: 600;
        }

        .template-actions {
            display: flex;
            gap: 10px;
        }

        .btn-template {
            padding: 12px 20px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            flex: 1;
            justify-content: center;
            font-family: 'Space Grotesk', sans-serif;
            background: #1E1E1E;
            color: #E1BEE7;
        }

        .btn-template:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-template.secondary {
            border-color: #2196F3;
            color: #64B5F6;
        }

        .btn-template.secondary:hover {
            background: #2196F3;
            color: white;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
        }

        .quick-create {
            background: #1E1E1E;
            border-left: 4px solid #FF9800;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .quick-create::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF9800, #FFB74D);
        }

        .quick-create h3 {
            margin-bottom: 20px;
            color: #FFB74D;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .quick-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
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
        .form-group select:focus {
            outline: none;
            border-color: #7B1FA2;
            box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1);
        }

        .form-group input::placeholder {
            color: #666;
        }

        .form-group select option {
            background: #2A2A2A;
            color: #E0E0E0;
        }

        .back-actions {
            text-align: center;
            margin-top: 40px;
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

            .templates-grid {
                grid-template-columns: 1fr;
            }

            .quick-form {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .template-actions {
                flex-direction: column;
            }

            .back-actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
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
                <h1><i class="fas fa-file-alt"></i> Templates de Torneio</h1>
                <p style="color: #9E9E9E; font-size: 1.1rem; margin-top: 8px;">Escolha um modelo pré-configurado para criar seu torneio rapidamente</p>
            </div>
            <div>
                <a href="tournament_list.php" class="btn-standard">
                    <i class="fas fa-list"></i> Ver Torneios
                </a>
                <a href="dashboard_simple.php" class="btn-standard">
                    <i class="fas fa-arrow-left"></i> Voltar
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

        <?php if (isset($debug_info)): ?>
            <div class="alert alert-info fade-in">
                <i class="fas fa-info-circle"></i>
                Debug: <?= htmlspecialchars($debug_info) ?>
            </div>
        <?php endif; ?>

        <!-- Criação Rápida -->
        <div class="quick-create fade-in">
            <h3><i class="fas fa-rocket"></i> Criação Rápida</h3>
            <form method="POST" class="quick-form">
                <div class="form-group">
                    <label for="tournament_name">Nome do Torneio</label>
                    <input type="text" id="tournament_name" name="tournament_name"
                           placeholder="Ex: Copa das Panelas 2024" required>
                </div>

                <div class="form-group">
                    <label for="template_id">Template</label>
                    <select id="template_id" name="template_id" required>
                        <option value="">Escolha um template...</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?= $template['id'] ?>">
                                <?= htmlspecialchars($template['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-standard">
                    <i class="fas fa-plus"></i> Criar Agora
                </button>
            </form>
        </div>

        <!-- Templates Grid -->
        <div class="templates-grid">
            <?php foreach ($templates as $index => $template): ?>
                <div class="template-card fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                    <div class="template-header">
                        <div class="template-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="template-title">
                            <?= htmlspecialchars($template['name']) ?>
                        </div>
                    </div>

                    <div class="template-description">
                        <?= htmlspecialchars($template['description']) ?>
                    </div>

                    <div class="template-specs">
                        <div class="spec-item">
                            <span class="spec-label"><i class="fas fa-users"></i> Times</span>
                            <span class="spec-value"><?= $template['teams'] ?> times</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label"><i class="fas fa-layer-group"></i> Grupos</span>
                            <span class="spec-value"><?= $template['groups'] ?> grupos</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label"><i class="fas fa-cogs"></i> Formato</span>
                            <span class="spec-value"><?= htmlspecialchars($template['format']) ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label"><i class="fas fa-clock"></i> Duração</span>
                            <span class="spec-value"><?= htmlspecialchars($template['duration']) ?></span>
                        </div>
                    </div>

                    <div class="template-actions">
                        <a href="template_preview.php?id=<?= $template['id'] ?>" class="btn-template">
                            <i class="fas fa-magic"></i> Usar Template
                        </a>
                        <a href="template_preview.php?id=<?= $template['id'] ?>" class="btn-template secondary">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ações Adicionais -->
        <div class="back-actions fade-in">
            <a href="create_tournament.php" class="btn-standard">
                <i class="fas fa-plus"></i> Criar do Zero
            </a>
            <a href="../../debug_tournament_creation.php" class="btn-standard">
                <i class="fas fa-bug"></i> Debug Sistema
            </a>
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

            // Efeitos de hover nos cards
            const templateCards = document.querySelectorAll('.template-card');
            templateCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                    this.style.boxShadow = '0 15px 35px rgba(123, 31, 162, 0.4)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 25px rgba(123, 31, 162, 0.3)';
                });
            });
        });

        // Função para auto-preencher nome do torneio baseado no template
        function autoFillTournamentName() {
            const tournamentNameInput = document.getElementById('tournament_name');
            const templateSelect = document.getElementById('template_id');

            if (tournamentNameInput && templateSelect) {
                const selectedOption = templateSelect.options[templateSelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const currentYear = new Date().getFullYear();
                    tournamentNameInput.value = `${selectedOption.text} ${currentYear}`;
                }
            }
        }

        // Event listener para auto-preenchimento
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template_id');
            if (templateSelect) {
                templateSelect.addEventListener('change', function() {
                    if (this.value) {
                        autoFillTournamentName();
                    }
                });
            }
        });
    </script>
</body>
</html>
