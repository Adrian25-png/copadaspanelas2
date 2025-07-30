<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../classes/TournamentManager.php';
require_once '../../includes/PermissionManager.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login_simple.php');
    exit;
}

$pdo = conectar();
$tournamentManager = new TournamentManager($pdo);
$permissionManager = getPermissionManager($pdo);

// Verificar permissão para criar torneios
$permissionManager->requirePermission('create_tournament');

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
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
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

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            color: #E1BEE7;
            font-weight: 700;
        }

        .page-title i {
            color: #7B1FA2;
            font-size: 2.2rem;
        }

        .page-subtitle {
            color: #E0E0E0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .form-section {
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
        }

        .form-section:hover {
            background: #252525;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(123, 31, 162, 0.2);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #E1BEE7;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-top: 5px;
        }

        .section-title i {
            color: #7B1FA2;
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #E1BEE7;
            font-size: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            background: #2A2A2A;
            color: #E0E0E0;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #E1BEE7;
            background: #333333;
            box-shadow: 0 0 15px rgba(123, 31, 162, 0.3);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #9E9E9E;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(123, 31, 162, 0.1);
            border: 1px solid rgba(123, 31, 162, 0.3);
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .checkbox-item:hover {
            background: rgba(123, 31, 162, 0.2);
            border-color: #7B1FA2;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: #7B1FA2;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-standard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: #1E1E1E;
            border: 2px solid #7B1FA2;
            border-radius: 8px;
            color: #E1BEE7;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn-standard:hover {
            background: #7B1FA2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 31, 162, 0.4);
        }

        .btn-primary {
            border-color: #7B1FA2;
            color: #E1BEE7;
        }

        .btn-primary:hover {
            background: #7B1FA2;
            border-color: #7B1FA2;
        }

        .btn-secondary {
            border-color: #9E9E9E;
            color: #BDBDBD;
        }

        .btn-secondary:hover {
            background: #9E9E9E;
            border-color: #9E9E9E;
            color: white;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 2px solid #4CAF50;
            color: #66BB6A;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #F44336;
            color: #EF5350;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .info-box {
            background: rgba(33, 150, 243, 0.2);
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        .info-box h4 {
            margin: 0 0 15px 0;
            color: #64B5F6;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 5px;
        }

        .info-box h4 i {
            color: #2196F3;
        }

        .info-box p {
            margin: 8px 0;
            color: #E0E0E0;
            opacity: 0.9;
        }

        .form-actions {
            text-align: center;
            margin-top: 40px;
            padding: 30px;
            background: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .form-actions::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
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

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }

            .page-title {
                font-size: 2rem;
                flex-direction: column;
                gap: 10px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .form-actions {
                text-align: center;
            }

            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header fade-in">
            <h1 class="page-title"><i class="fas fa-trophy"></i> Criar Novo Torneio</h1>
            <p class="page-subtitle">Configure um novo torneio de futebol no sistema Copa das Panelas</p>
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
            <div class="form-section fade-in">
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
            <div class="form-section fade-in">
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
            
            <div class="form-actions fade-in">
                <button type="submit" class="btn-standard btn-primary">
                    <i class="fas fa-plus-circle"></i> Criar Torneio
                </button>

                <a href="tournament_list.php" class="btn-standard btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>

                <button type="button" class="btn-standard" onclick="previewTournament()">
                    <i class="fas fa-eye"></i> Visualizar
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar fade-in aos elementos
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Adicionar efeitos hover dinâmicos aos form-sections
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach(section => {
                section.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });

                section.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

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
                alert('⚠️ Nome do torneio é obrigatório');
                e.preventDefault();
                return;
            }

            if (numGroups < 1 || numGroups > 20) {
                alert('⚠️ Número de grupos deve estar entre 1 e 20');
                e.preventDefault();
                return;
            }

            if (teamsPerGroup < 2 || teamsPerGroup > 10) {
                alert('⚠️ Times por grupo deve estar entre 2 e 10');
                e.preventDefault();
                return;
            }

            const totalTeams = numGroups * teamsPerGroup;
            if (totalTeams > 200) {
                if (!confirm(`⚠️ ATENÇÃO: Este torneio terá ${totalTeams} times.\n\nIsso pode impactar a performance do sistema.\n\nTem certeza que deseja continuar?`)) {
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>
