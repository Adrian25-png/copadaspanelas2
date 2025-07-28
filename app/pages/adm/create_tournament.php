<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);

/**
 * Determina automaticamente as fases finais baseado no número total de times
 */
function determineFinalPhases($total_teams) {
    $phases = [
        'has_final' => false,
        'has_semifinal' => false,
        'has_quarterfinal' => false,
        'has_round_of_16' => false,
        'qualified_per_group' => 1,
        'description' => ''
    ];

    if ($total_teams >= 32) {
        // 32+ times: Oitavas, Quartas, Semifinal, Final
        $phases['has_final'] = true;
        $phases['has_semifinal'] = true;
        $phases['has_quarterfinal'] = true;
        $phases['has_round_of_16'] = true;
        $phases['qualified_per_group'] = 2; // 2 classificados por grupo
        $phases['description'] = 'Oitavas de Final → Quartas → Semifinal → Final';
    } elseif ($total_teams >= 16) {
        // 16-31 times: Oitavas, Quartas, Semifinal, Final
        $phases['has_final'] = true;
        $phases['has_semifinal'] = true;
        $phases['has_quarterfinal'] = true;
        $phases['has_round_of_16'] = true;
        $phases['qualified_per_group'] = 1; // 1 classificado por grupo (se 16 times) ou 2 (se mais)
        if ($total_teams > 16) {
            $phases['qualified_per_group'] = 2;
        }
        $phases['description'] = 'Oitavas de Final → Quartas → Semifinal → Final';
    } elseif ($total_teams >= 8) {
        // 8-15 times: Quartas, Semifinal, Final
        $phases['has_final'] = true;
        $phases['has_semifinal'] = true;
        $phases['has_quarterfinal'] = true;
        $phases['qualified_per_group'] = 1;
        if ($total_teams > 8) {
            $phases['qualified_per_group'] = 2;
        }
        $phases['description'] = 'Quartas de Final → Semifinal → Final';
    } elseif ($total_teams >= 4) {
        // 4-7 times: Semifinal, Final
        $phases['has_final'] = true;
        $phases['has_semifinal'] = true;
        $phases['qualified_per_group'] = 1;
        if ($total_teams > 4) {
            $phases['qualified_per_group'] = 2;
        }
        $phases['description'] = 'Semifinal → Final';
    } elseif ($total_teams >= 2) {
        // 2-3 times: Apenas Final
        $phases['has_final'] = true;
        $phases['qualified_per_group'] = 1;
        $phases['description'] = 'Final Direta';
    }

    return $phases;
}

// Processar criação do torneio
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_tournament') {
    try {
        $name = trim($_POST['name']);
        $year = (int)$_POST['year'];
        $description = trim($_POST['description']);
        $num_groups = (int)$_POST['num_groups'];
        $teams_per_group = (int)$_POST['teams_per_group'];
        // Calcular total de times
        $total_teams = $num_groups * $teams_per_group;

        // Determinar automaticamente as fases finais baseado no número de times
        $final_phase_config = determineFinalPhases($total_teams);

        if (empty($name)) {
            throw new Exception("Nome do torneio é obrigatório");
        }

        if ($year < 2020 || $year > 2030) {
            throw new Exception("Ano deve estar entre 2020 e 2030");
        }

        if ($num_groups < 1 || $num_groups > 20) {
            throw new Exception("Número de grupos deve estar entre 1 e 20");
        }

        if ($teams_per_group < 2 || $teams_per_group > 10) {
            throw new Exception("Times por grupo deve estar entre 2 e 10");
        }

        $tournament_id = $tournamentManager->createTournament(
            $name, $year, $description, $num_groups, $teams_per_group, $final_phase_config
        );
        
        $_SESSION['success'] = "Torneio '$name' criado com sucesso!";
        header("Location: tournament_management.php?id=$tournament_id");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Torneio - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .form-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f39c12;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #ecf0f1;
        }
        
        .form-input {
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-input {
            width: 20px;
            height: 20px;
            accent-color: #3498db;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
            color: #2ecc71;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .help-text {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .preview-section {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .preview-title {
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Criar Novo Torneio</h1>
            <p style="opacity: 0.8;">Configure um novo torneio de futebol</p>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form method="POST" id="tournamentForm">
            <input type="hidden" name="action" value="create_tournament">
            
            <!-- Informações Básicas -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Informações Básicas
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome do Torneio *</label>
                        <input type="text" name="name" class="form-input" 
                               placeholder="Ex: Copa das Panelas 2024" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                               required maxlength="100">
                        <div class="help-text">Nome que aparecerá em todos os relatórios</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ano *</label>
                        <input type="number" name="year" class="form-input" 
                               min="2020" max="2030" 
                               value="<?= $_POST['year'] ?? date('Y') ?>" 
                               required>
                        <div class="help-text">Ano de realização do torneio</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-input form-textarea" 
                              placeholder="Descrição opcional do torneio..."
                              maxlength="500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="help-text">Descrição opcional (máximo 500 caracteres)</div>
                </div>
            </div>
            
            <!-- Configurações do Torneio -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-cogs"></i>
                    Configurações do Torneio
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Número de Grupos *</label>
                        <input type="number" name="num_groups" class="form-input" 
                               min="1" max="20" 
                               value="<?= $_POST['num_groups'] ?? 4 ?>" 
                               required id="numGroups">
                        <div class="help-text">Quantos grupos terá o torneio (1-20)</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Times por Grupo *</label>
                        <input type="number" name="teams_per_group" class="form-input" 
                               min="2" max="10" 
                               value="<?= $_POST['teams_per_group'] ?? 4 ?>" 
                               required id="teamsPerGroup">
                        <div class="help-text">Quantos times em cada grupo (2-10)</div>
                    </div>
                </div>
                
                <div class="info-section" style="background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.3); border-radius: 10px; padding: 15px; margin-top: 15px;">
                    <div style="font-weight: bold; color: #3498db; margin-bottom: 10px;">
                        <i class="fas fa-trophy"></i> Fases Finais (Automático)
                    </div>
                    <div id="finalPhasesInfo" style="color: rgba(255, 255, 255, 0.9);">
                        As fases finais serão determinadas automaticamente baseado no número total de times.
                    </div>
                </div>
                
                <!-- Preview -->
                <div class="preview-section" id="tournamentPreview">
                    <div class="preview-title">📊 Resumo do Torneio:</div>
                    <div id="previewContent">
                        <div>🏆 <span id="previewName">Nome do Torneio</span></div>
                        <div>📅 <span id="previewYear"><?= date('Y') ?></span></div>
                        <div>👥 <span id="previewTotalTeams">16</span> times total</div>
                        <div>🏟️ <span id="previewGroups">4</span> grupos com <span id="previewTeamsGroup">4</span> times cada</div>
                        <div>⚽ <span id="previewMatches">24</span> jogos na fase de grupos</div>
                        <div id="previewFinalPhase" style="display: none;">🏆 + Fase final com playoffs</div>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Criar Torneio
                </button>
                
                <a href="tournament_list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                
                <button type="button" class="btn btn-primary" onclick="previewTournament()">
                    <i class="fas fa-eye"></i> Visualizar
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function determineFinalPhasesJS(totalTeams) {
            if (totalTeams >= 32) {
                return 'Oitavas de Final → Quartas → Semifinal → Final (2 classificados por grupo)';
            } else if (totalTeams >= 16) {
                return 'Oitavas de Final → Quartas → Semifinal → Final (' + (totalTeams > 16 ? '2' : '1') + ' classificado(s) por grupo)';
            } else if (totalTeams >= 8) {
                return 'Quartas de Final → Semifinal → Final (' + (totalTeams > 8 ? '2' : '1') + ' classificado(s) por grupo)';
            } else if (totalTeams >= 4) {
                return 'Semifinal → Final (' + (totalTeams > 4 ? '2' : '1') + ' classificado(s) por grupo)';
            } else if (totalTeams >= 2) {
                return 'Final Direta (1 classificado por grupo)';
            } else {
                return 'Configuração insuficiente para fases finais';
            }
        }

        function updatePreview() {
            const name = document.querySelector('input[name="name"]').value || 'Nome do Torneio';
            const year = document.querySelector('input[name="year"]').value || new Date().getFullYear();
            const numGroups = parseInt(document.querySelector('input[name="num_groups"]').value) || 4;
            const teamsPerGroup = parseInt(document.querySelector('input[name="teams_per_group"]').value) || 4;

            const totalTeams = numGroups * teamsPerGroup;
            const groupMatches = numGroups * (teamsPerGroup * (teamsPerGroup - 1) / 2);
            const finalPhasesDescription = determineFinalPhasesJS(totalTeams);

            document.getElementById('previewName').textContent = name;
            document.getElementById('previewYear').textContent = year;
            document.getElementById('previewTotalTeams').textContent = totalTeams;
            document.getElementById('previewGroups').textContent = numGroups;
            document.getElementById('previewTeamsGroup').textContent = teamsPerGroup;
            document.getElementById('previewMatches').textContent = groupMatches;
            document.getElementById('previewFinalPhase').style.display = 'block';
            document.getElementById('previewFinalPhase').innerHTML = '🏆 ' + finalPhasesDescription;

            // Atualizar info das fases finais
            document.getElementById('finalPhasesInfo').innerHTML = finalPhasesDescription;
        }
        
        function previewTournament() {
            updatePreview();
            document.getElementById('tournamentPreview').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Atualizar preview em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', updatePreview);
                input.addEventListener('change', updatePreview);
            });
            
            updatePreview();
        });
        
        // Validação do formulário
        document.getElementById('tournamentForm').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const numGroups = parseInt(document.querySelector('input[name="num_groups"]').value);
            const teamsPerGroup = parseInt(document.querySelector('input[name="teams_per_group"]').value);
            
            if (!name) {
                alert('Nome do torneio é obrigatório');
                e.preventDefault();
                return;
            }
            
            if (numGroups < 1 || numGroups > 20) {
                alert('Número de grupos deve estar entre 1 e 20');
                e.preventDefault();
                return;
            }
            
            if (teamsPerGroup < 2 || teamsPerGroup > 10) {
                alert('Times por grupo deve estar entre 2 e 10');
                e.preventDefault();
                return;
            }
            
            const totalTeams = numGroups * teamsPerGroup;
            if (totalTeams > 200) {
                if (!confirm(`Este torneio terá ${totalTeams} times. Tem certeza que deseja continuar?`)) {
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>
