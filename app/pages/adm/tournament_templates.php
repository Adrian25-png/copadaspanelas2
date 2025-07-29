<?php
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .template-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            border-color: #3498db;
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.3);
        }
        
        .template-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .template-icon {
            font-size: 2.5rem;
            color: #3498db;
        }
        
        .template-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ecf0f1;
        }
        
        .template-description {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .template-specs {
            margin-bottom: 25px;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .spec-item:last-child {
            border-bottom: none;
        }
        
        .spec-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .spec-value {
            color: #3498db;
            font-weight: 600;
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
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
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .quick-create {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .quick-create h3 {
            margin-bottom: 20px;
            color: #f39c12;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quick-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-group select option {
            background: #2c3e50;
            color: white;
        }
        
        .back-actions {
            text-align: center;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .templates-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-form {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .template-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Templates de Torneio</h1>
            <p>Escolha um modelo pré-configurado para criar seu torneio rapidamente</p>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #e74c3c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($debug_info)): ?>
            <div style="background: rgba(52, 152, 219, 0.2); border: 1px solid #3498db; color: #3498db; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Debug: <?= htmlspecialchars($debug_info) ?>
            </div>
        <?php endif; ?>
        
        <div class="quick-create">
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
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i> Criar Agora
                </button>
            </form>
        </div>
        
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
                <div class="template-card">
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
                            <span class="spec-label">Times</span>
                            <span class="spec-value"><?= $template['teams'] ?> times</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Grupos</span>
                            <span class="spec-value"><?= $template['groups'] ?> grupos</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Formato</span>
                            <span class="spec-value"><?= htmlspecialchars($template['format']) ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Duração</span>
                            <span class="spec-value"><?= htmlspecialchars($template['duration']) ?></span>
                        </div>
                    </div>
                    
                    <div class="template-actions">
                        <button onclick="useTemplate(<?= $template['id'] ?>, '<?= htmlspecialchars($template['name']) ?>')" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Usar Template
                        </button>
                        <button onclick="previewTemplate(<?= $template['id'] ?>)" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="back-actions">
            <a href="tournament_list.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver Torneios
            </a>
            <a href="create_tournament.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Criar do Zero
            </a>
            <a href="../../debug_tournament_creation.php" class="btn btn-warning">
                <i class="fas fa-bug"></i> Debug
            </a>
            <a href="dashboard_simple.php" class="btn btn-success">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function useTemplate(templateId, templateName) {
            const tournamentName = 'Copa das Panelas 2024';

            if (tournamentName && tournamentName.trim() !== '') {
                // Criar formulário dinamicamente
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const templateInput = document.createElement('input');
                templateInput.type = 'hidden';
                templateInput.name = 'template_id';
                templateInput.value = templateId;

                const nameInput = document.createElement('input');
                nameInput.type = 'hidden';
                nameInput.name = 'tournament_name';
                nameInput.value = tournamentName.trim();

                form.appendChild(templateInput);
                form.appendChild(nameInput);
                document.body.appendChild(form);

                // Submeter formulário
                form.submit();
            }
        }

        function previewTemplate(templateId) {
            // Mostrar detalhes do template
            const templates = {
                1: {
                    name: 'Copa Simples',
                    details: '• 8 times divididos em 2 grupos\n• Fase de grupos (todos contra todos)\n• Classificam os 2 primeiros de cada grupo\n• Semifinais e Final\n• Disputa de 3º lugar'
                },
                2: {
                    name: 'Liga Completa',
                    details: '• 12 times em grupo único\n• Todos jogam contra todos\n• Sistema de pontos corridos\n• Campeão é quem fizer mais pontos'
                },
                3: {
                    name: 'Mata-Mata',
                    details: '• 16 times em eliminação direta\n• Oitavas de final\n• Quartas de final\n• Semifinais\n• Final e 3º lugar'
                },
                4: {
                    name: 'Copa das Panelas Oficial',
                    details: '• 16 times divididos em 4 grupos\n• Fase de grupos (4 times por grupo)\n• Oitavas de final (2 primeiros de cada grupo)\n• Quartas, Semifinais e Final\n• Disputa de 3º lugar'
                }
            };

            const template = templates[templateId];
            if (template) {
                alert(template.name + ':\n\n' + template.details);
            }
        }
    </script>
</body>
</html>
