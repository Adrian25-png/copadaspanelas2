<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática

 include 'admin_header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistente de Criação de Torneio - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            color: white;
            padding-top: 80px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .wizard-card {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
        }
        
        .title {
            text-align: center;
            margin-bottom: 30px;
            color: #f39c12;
            font-size: 2.5rem;
        }
        
        .steps-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .step-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 25px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .step-item.active {
            background: #f39c12;
            transform: scale(1.05);
        }
        
        .step-item.completed {
            background: #27ae60;
        }
        
        .step-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .step-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #3498db;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .team-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .group-section {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .group-title {
            color: #3498db;
            font-size: 1.3rem;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #f39c12;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            margin: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #2ecc71; }
        
        .btn-primary { background: #3498db; }
        .btn-primary:hover { background: #2980b9; }
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .info-box {
            background: rgba(52, 152, 219, 0.2);
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background: rgba(243, 156, 18, 0.2);
            border: 2px solid #f39c12;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .success-box {
            background: rgba(39, 174, 96, 0.2);
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .preview-table th,
        .preview-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .preview-table th {
            background: rgba(255, 255, 255, 0.2);
            color: #f39c12;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="wizard-card">
            <h1 class="title">
                <i class="fas fa-magic"></i>
                Assistente de Criação de Torneio
            </h1>
            
            <div class="steps-nav">
                <div class="step-item active" onclick="showStep(1)">
                    <i class="fas fa-info-circle"></i>
                    <span>1. Informações Básicas</span>
                </div>
                <div class="step-item" onclick="showStep(2)">
                    <i class="fas fa-users"></i>
                    <span>2. Times</span>
                </div>
                <div class="step-item" onclick="showStep(3)">
                    <i class="fas fa-layer-group"></i>
                    <span>3. Grupos</span>
                </div>
                <div class="step-item" onclick="showStep(4)">
                    <i class="fas fa-calendar"></i>
                    <span>4. Jogos</span>
                </div>
                <div class="step-item" onclick="showStep(5)">
                    <i class="fas fa-trophy"></i>
                    <span>5. Finalizar</span>
                </div>
            </div>
            
            <!-- PASSO 1: Informações Básicas -->
            <div class="step-content active" id="step1">
                <h2 style="color: #3498db; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i>
                    Passo 1: Informações Básicas do Torneio
                </h2>
                
                <div class="info-box">
                    <strong><i class="fas fa-lightbulb"></i> Dica:</strong>
                    Escolha um nome marcante e uma descrição clara para seu torneio. Essas informações aparecerão em todos os relatórios e páginas públicas.
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome do Torneio *</label>
                        <input type="text" id="tournament_name" placeholder="Ex: Copa das Panelas 2024" required>
                    </div>
                    <div class="form-group">
                        <label>Ano *</label>
                        <input type="number" id="tournament_year" value="2024" min="2020" max="2030" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Descrição do Torneio</label>
                    <textarea id="tournament_description" placeholder="Descreva o torneio, suas regras especiais, premiação, etc."></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Número de Grupos *</label>
                        <select id="num_groups" onchange="updateGroupsPreview()">
                            <option value="2">2 Grupos (8 times)</option>
                            <option value="4" selected>4 Grupos (16 times)</option>
                            <option value="6">6 Grupos (24 times)</option>
                            <option value="8">8 Grupos (32 times)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Times por Grupo *</label>
                        <select id="teams_per_group">
                            <option value="4" selected>4 times por grupo</option>
                            <option value="5">5 times por grupo</option>
                            <option value="6">6 times por grupo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Classificados por Grupo</label>
                        <select id="qualified_per_group">
                            <option value="1">1º colocado</option>
                            <option value="2" selected>1º e 2º colocados</option>
                            <option value="3">1º, 2º e 3º colocados</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fase Final</label>
                        <select id="final_phase">
                            <option value="quartas">Quartas de Final</option>
                            <option value="semifinais" selected>Semifinais</option>
                            <option value="oitavas">Oitavas de Final</option>
                        </select>
                    </div>
                </div>
                
                <div id="tournament_preview" class="success-box">
                    <h4 style="color: #27ae60; margin-bottom: 10px;">
                        <i class="fas fa-eye"></i> Prévia do Torneio
                    </h4>
                    <p><strong>Total de Times:</strong> <span id="total_teams">16</span></p>
                    <p><strong>Total de Jogos na Fase de Grupos:</strong> <span id="total_group_matches">24</span></p>
                    <p><strong>Times Classificados:</strong> <span id="total_qualified">8</span></p>
                    <p><strong>Estrutura:</strong> <span id="tournament_structure">4 grupos → Quartas → Semifinais → Final</span></p>
                </div>
            </div>
            
            <!-- PASSO 2: Times -->
            <div class="step-content" id="step2">
                <h2 style="color: #3498db; margin-bottom: 20px;">
                    <i class="fas fa-users"></i>
                    Passo 2: Cadastrar Times
                </h2>
                
                <div class="warning-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Importante:</strong>
                    Você precisa cadastrar exatamente <span id="required_teams">16</span> times para este torneio.
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome do Time</label>
                        <input type="text" id="team_name" placeholder="Digite o nome do time" onkeypress="handleTeamNameKeyPress(event)">
                    </div>
                    <div class="form-group">
                        <label>Logo do Time (opcional)</label>
                        <input type="file" id="team_logo" accept="image/*">
                    </div>
                </div>
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="addTeam()">
                        <i class="fas fa-plus"></i>
                        Adicionar Time
                    </button>
                    <button class="btn" onclick="generateSampleTeams()">
                        <i class="fas fa-magic"></i>
                        Gerar Times de Exemplo
                    </button>
                </div>
                
                <div id="teams_list">
                    <h4 style="color: #f39c12; margin-bottom: 15px;">
                        Times Cadastrados (<span id="teams_count">0</span>/<span id="teams_needed">16</span>)
                    </h4>
                    <div class="teams-grid" id="teams_grid">
                        <!-- Times serão adicionados aqui -->
                    </div>
                </div>
            </div>
            
            <!-- PASSO 3: Grupos -->
            <div class="step-content" id="step3">
                <h2 style="color: #3498db; margin-bottom: 20px;">
                    <i class="fas fa-layer-group"></i>
                    Passo 3: Organizar Grupos
                </h2>
                
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> Informação:</strong>
                    Os times serão distribuídos automaticamente nos grupos de forma equilibrada. Você pode reorganizar manualmente se desejar.
                </div>
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <button class="btn btn-success" onclick="distributeTeamsInGroups()">
                        <i class="fas fa-random"></i>
                        Distribuir Times Automaticamente
                    </button>
                    <button class="btn" onclick="shuffleGroups()">
                        <i class="fas fa-shuffle"></i>
                        Embaralhar Grupos
                    </button>
                </div>
                
                <div id="groups_container">
                    <!-- Grupos serão gerados aqui -->
                </div>
            </div>
            
            <!-- PASSO 4: Jogos -->
            <div class="step-content" id="step4">
                <h2 style="color: #3498db; margin-bottom: 20px;">
                    <i class="fas fa-calendar"></i>
                    Passo 4: Gerar Jogos da Fase de Grupos
                </h2>
                
                <div class="warning-box">
                    <strong><i class="fas fa-clock"></i> Atenção:</strong>
                    Defina as datas e horários dos jogos. O sistema gerará automaticamente todos os confrontos da fase de grupos.
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Data de Início</label>
                        <input type="date" id="start_date" value="2024-01-15">
                    </div>
                    <div class="form-group">
                        <label>Horário dos Jogos</label>
                        <input type="time" id="match_time" value="19:00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Intervalo entre Jogos (dias)</label>
                    <select id="match_interval">
                        <option value="1">1 dia</option>
                        <option value="2">2 dias</option>
                        <option value="3" selected>3 dias</option>
                        <option value="7">1 semana</option>
                    </select>
                </div>
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <button class="btn btn-success" onclick="generateMatches()">
                        <i class="fas fa-calendar-plus"></i>
                        Gerar Todos os Jogos
                    </button>
                </div>
                
                <div id="matches_preview">
                    <!-- Prévia dos jogos será mostrada aqui -->
                </div>
            </div>
            
            <!-- PASSO 5: Finalizar -->
            <div class="step-content" id="step5">
                <h2 style="color: #3498db; margin-bottom: 20px;">
                    <i class="fas fa-trophy"></i>
                    Passo 5: Finalizar e Criar Torneio
                </h2>
                
                <div class="success-box">
                    <h3 style="color: #27ae60; margin-bottom: 15px;">
                        <i class="fas fa-check-circle"></i>
                        Torneio Pronto para Criação!
                    </h3>
                    <p>Revise todas as informações abaixo antes de finalizar:</p>
                </div>
                
                <div id="final_summary">
                    <!-- Resumo final será gerado aqui -->
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button class="btn btn-success" onclick="createTournament()" style="font-size: 1.2rem; padding: 20px 40px;">
                        <i class="fas fa-rocket"></i>
                        CRIAR TORNEIO
                    </button>
                </div>
            </div>
            
            <div class="navigation">
                <button class="btn btn-danger" onclick="previousStep()" id="prev_btn" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                    Anterior
                </button>
                <button class="btn btn-primary" onclick="nextStep()" id="next_btn">
                    Próximo
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let teams = [];
        let groups = [];
        let matches = [];
        
        function showStep(step) {
            // Esconder todos os passos
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Mostrar passo atual
            document.getElementById('step' + step).classList.add('active');
            
            // Atualizar navegação
            document.querySelectorAll('.step-item').forEach((item, index) => {
                item.classList.remove('active');
                if (index + 1 === step) {
                    item.classList.add('active');
                }
                if (index + 1 < step) {
                    item.classList.add('completed');
                }
            });
            
            currentStep = step;
            
            // Controlar botões de navegação
            document.getElementById('prev_btn').style.display = step > 1 ? 'block' : 'none';
            document.getElementById('next_btn').style.display = step < 5 ? 'block' : 'none';
            
            // Atualizar conteúdo específico do passo
            if (step === 5) {
                generateFinalSummary();
            }
        }
        
        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < 5) {
                    showStep(currentStep + 1);
                }
            }
        }
        
        function previousStep() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        }
        
        function validateCurrentStep() {
            switch(currentStep) {
                case 1:
                    if (!document.getElementById('tournament_name').value) {
                        return false;
                    }
                    return true;
                case 2:
                    const requiredTeams = parseInt(document.getElementById('required_teams').textContent);
                    if (teams.length !== requiredTeams) {
                        return false;
                    }
                    return true;
                case 3:
                    if (groups.length === 0) {
                        return false;
                    }
                    return true;
                case 4:
                    if (matches.length === 0) {
                        return false;
                    }
                    return true;
                default:
                    return true;
            }
        }
        
        function updateGroupsPreview() {
            const numGroups = parseInt(document.getElementById('num_groups').value);
            const teamsPerGroup = parseInt(document.getElementById('teams_per_group').value);
            const totalTeams = numGroups * teamsPerGroup;
            
            document.getElementById('total_teams').textContent = totalTeams;
            document.getElementById('required_teams').textContent = totalTeams;
            document.getElementById('teams_needed').textContent = totalTeams;
            
            // Calcular jogos por grupo (todos contra todos)
            const matchesPerGroup = (teamsPerGroup * (teamsPerGroup - 1)) / 2;
            const totalGroupMatches = numGroups * matchesPerGroup;
            document.getElementById('total_group_matches').textContent = totalGroupMatches;
            
            // Calcular classificados
            const qualifiedPerGroup = parseInt(document.getElementById('qualified_per_group').value);
            const totalQualified = numGroups * qualifiedPerGroup;
            document.getElementById('total_qualified').textContent = totalQualified;
            
            // Atualizar estrutura
            const finalPhase = document.getElementById('final_phase').value;
            let structure = `${numGroups} grupos → `;
            if (totalQualified >= 16) structure += 'Oitavas → ';
            if (totalQualified >= 8) structure += 'Quartas → ';
            structure += 'Semifinais → Final';
            document.getElementById('tournament_structure').textContent = structure;
        }
        
        function addTeam() {
            console.log('Função addTeam chamada'); // Debug

            const teamNameInput = document.getElementById('team_name');
            if (!teamNameInput) {
                alert('Erro: Campo de nome do time não encontrado!');
                return;
            }

            const teamName = teamNameInput.value.trim();
            if (!teamName) {
                alert('Por favor, digite o nome do time!');
                teamNameInput.focus();
                return;
            }

            // Verificar se o time já existe
            if (teams.some(team => team.name.toLowerCase() === teamName.toLowerCase())) {
                alert('Este time já foi cadastrado!');
                teamNameInput.focus();
                return;
            }

            const requiredTeamsElement = document.getElementById('required_teams');
            if (!requiredTeamsElement) {
                alert('Erro: Elemento required_teams não encontrado!');
                return;
            }

            const requiredTeams = parseInt(requiredTeamsElement.textContent);
            if (teams.length >= requiredTeams) {
                alert(`Você já cadastrou o máximo de ${requiredTeams} times!`);
                return;
            }

            // Adicionar time
            teams.push({
                id: teams.length + 1,
                name: teamName,
                logo: null
            });

            console.log('Time adicionado:', teamName); // Debug
            console.log('Total de times:', teams.length); // Debug

            // Limpar campo e atualizar lista
            teamNameInput.value = '';
            updateTeamsList();

            // Focar no campo para próximo time
            teamNameInput.focus();

            // Mostrar mensagem de sucesso
            if (teams.length < requiredTeams) {
                const remaining = requiredTeams - teams.length;
                alert(`✅ Time "${teamName}" adicionado com sucesso!\n\nFaltam ${remaining} times para completar o torneio.`);
            } else {
                alert(`✅ Time "${teamName}" adicionado com sucesso!\n\n🏆 Todos os ${requiredTeams} times foram cadastrados!`);
            }
        }
        
        function generateSampleTeams() {
            console.log('Função generateSampleTeams chamada'); // Debug

            const requiredTeamsElement = document.getElementById('required_teams');
            if (!requiredTeamsElement) {
                return;
            }

            const requiredTeams = parseInt(requiredTeamsElement.textContent);

            // Substituir times existentes se houver
            if (teams.length > 0) {
                // Continuar sem confirmação
            }

            const sampleNames = [
                'Águias FC', 'Leões United', 'Tigres FC', 'Panteras SC',
                'Falcões FC', 'Lobos United', 'Tubarões FC', 'Dragões SC',
                'Cobras FC', 'Jaguares United', 'Pumas FC', 'Condores SC',
                'Ursos FC', 'Raposas United', 'Linces FC', 'Leopardos SC',
                'Rinocerontes FC', 'Elefantes United', 'Hipopótamos FC', 'Búfalos SC',
                'Cavalos FC', 'Zebras United', 'Girafas FC', 'Antílopes SC',
                'Flamingos FC', 'Pelicanos United', 'Gaviões FC', 'Corujas SC',
                'Serpentes FC', 'Escorpiões United', 'Aranhas FC', 'Abelhas SC'
            ];

            // Limpar times existentes
            teams = [];

            // Gerar times de exemplo
            for (let i = 0; i < requiredTeams; i++) {
                teams.push({
                    id: i + 1,
                    name: sampleNames[i] || `Time ${i + 1}`,
                    logo: null
                });
            }

            console.log('Times de exemplo gerados:', teams.length); // Debug

            updateTeamsList();

            // Mostrar mensagem de sucesso
            alert(`✅ ${requiredTeams} times de exemplo foram gerados com sucesso!\n\nVocê pode editá-los ou prosseguir para o próximo passo.`);
        }
        
        function updateTeamsList() {
            console.log('Atualizando lista de times...'); // Debug

            const teamsGrid = document.getElementById('teams_grid');
            const teamsCount = document.getElementById('teams_count');

            if (!teamsGrid) {
                console.error('Elemento teams_grid não encontrado!');
                return;
            }

            if (!teamsCount) {
                console.error('Elemento teams_count não encontrado!');
                return;
            }

            // Atualizar contador
            teamsCount.textContent = teams.length;

            // Limpar grid
            teamsGrid.innerHTML = '';

            // Adicionar cada time
            teams.forEach((team, index) => {
                const teamCard = document.createElement('div');
                teamCard.className = 'team-card';
                teamCard.style.animation = 'fadeIn 0.3s ease';
                teamCard.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${team.name}</strong>
                            <div style="font-size: 0.8rem; opacity: 0.7;">Time #${team.id}</div>
                        </div>
                        <button class="btn btn-danger" onclick="removeTeam(${index})" style="padding: 5px 10px; font-size: 0.8rem;" title="Remover time">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                teamsGrid.appendChild(teamCard);
            });

            console.log(`Lista atualizada com ${teams.length} times`); // Debug

            // Atualizar status do botão próximo
            updateNextButtonStatus();
        }
        
        function removeTeam(index) {
            if (index < 0 || index >= teams.length) {
                return;
            }

            const teamName = teams[index].name;

            teams.splice(index, 1);

            // Reindexar IDs
            teams.forEach((team, i) => {
                team.id = i + 1;
            });

            updateTeamsList();

            console.log(`Time "${teamName}" removido`); // Debug
        }

        function updateNextButtonStatus() {
            const nextBtn = document.getElementById('next_btn');
            const requiredTeams = parseInt(document.getElementById('required_teams').textContent);

            if (nextBtn && currentStep === 2) {
                if (teams.length === requiredTeams) {
                    nextBtn.disabled = false;
                    nextBtn.style.opacity = '1';
                    nextBtn.innerHTML = '<i class="fas fa-check"></i> Próximo (Times Completos)';
                } else {
                    nextBtn.disabled = true;
                    nextBtn.style.opacity = '0.5';
                    nextBtn.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Faltam ${requiredTeams - teams.length} times`;
                }
            }
        }
        
        function distributeTeamsInGroups() {
            const numGroups = parseInt(document.getElementById('num_groups').value);
            const teamsPerGroup = parseInt(document.getElementById('teams_per_group').value);
            
            if (teams.length !== numGroups * teamsPerGroup) {
                alert('Número de times não confere com a configuração dos grupos!');
                return;
            }
            
            // Embaralhar times
            const shuffledTeams = [...teams].sort(() => Math.random() - 0.5);
            
            groups = [];
            const groupNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            
            for (let i = 0; i < numGroups; i++) {
                const groupTeams = shuffledTeams.slice(i * teamsPerGroup, (i + 1) * teamsPerGroup);
                groups.push({
                    id: i + 1,
                    name: groupNames[i],
                    teams: groupTeams
                });
            }
            
            displayGroups();
        }
        
        function displayGroups() {
            const container = document.getElementById('groups_container');
            container.innerHTML = '';
            
            groups.forEach(group => {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'group-section';
                groupDiv.innerHTML = `
                    <div class="group-title">
                        <i class="fas fa-layer-group"></i>
                        GRUPO ${group.name}
                    </div>
                    <div class="teams-grid">
                        ${group.teams.map(team => `
                            <div class="team-card">
                                <strong>${team.name}</strong>
                                <div style="font-size: 0.8rem; opacity: 0.7;">Time #${team.id}</div>
                            </div>
                        `).join('')}
                    </div>
                `;
                container.appendChild(groupDiv);
            });
        }
        
        function shuffleGroups() {
            if (groups.length === 0) {
                alert('Primeiro distribua os times nos grupos!');
                return;
            }
            distributeTeamsInGroups();
        }
        
        function generateMatches() {
            if (groups.length === 0) {
                alert('Primeiro organize os grupos!');
                return;
            }
            
            const startDate = new Date(document.getElementById('start_date').value);
            const matchTime = document.getElementById('match_time').value;
            const interval = parseInt(document.getElementById('match_interval').value);
            
            matches = [];
            let currentDate = new Date(startDate);
            let matchId = 1;
            
            groups.forEach(group => {
                const groupTeams = group.teams;
                
                // Gerar todos os confrontos do grupo (todos contra todos)
                for (let i = 0; i < groupTeams.length; i++) {
                    for (let j = i + 1; j < groupTeams.length; j++) {
                        matches.push({
                            id: matchId++,
                            group: group.name,
                            team1: groupTeams[i],
                            team2: groupTeams[j],
                            date: new Date(currentDate),
                            time: matchTime,
                            status: 'agendado'
                        });
                        
                        // Avançar data
                        currentDate.setDate(currentDate.getDate() + interval);
                    }
                }
            });
            
            displayMatchesPreview();
        }
        
        function displayMatchesPreview() {
            const container = document.getElementById('matches_preview');
            container.innerHTML = `
                <h4 style="color: #f39c12; margin-bottom: 15px;">
                    <i class="fas fa-calendar"></i>
                    Jogos Gerados (${matches.length} jogos)
                </h4>
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Grupo</th>
                            <th>Confronto</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${matches.slice(0, 10).map(match => `
                            <tr>
                                <td>${match.date.toLocaleDateString('pt-BR')}</td>
                                <td>${match.time}</td>
                                <td>Grupo ${match.group}</td>
                                <td>${match.team1.name} vs ${match.team2.name}</td>
                            </tr>
                        `).join('')}
                        ${matches.length > 10 ? `
                            <tr>
                                <td colspan="4" style="text-align: center; opacity: 0.7;">
                                    ... e mais ${matches.length - 10} jogos
                                </td>
                            </tr>
                        ` : ''}
                    </tbody>
                </table>
            `;
        }
        
        function generateFinalSummary() {
            const tournamentName = document.getElementById('tournament_name').value;
            const tournamentYear = document.getElementById('tournament_year').value;
            const numGroups = document.getElementById('num_groups').value;
            const teamsPerGroup = document.getElementById('teams_per_group').value;
            
            const container = document.getElementById('final_summary');
            container.innerHTML = `
                <div class="preview-table" style="background: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 20px;">
                    <h3 style="color: #f39c12; margin-bottom: 20px;">📋 Resumo do Torneio</h3>
                    <p><strong>Nome:</strong> ${tournamentName}</p>
                    <p><strong>Ano:</strong> ${tournamentYear}</p>
                    <p><strong>Estrutura:</strong> ${numGroups} grupos com ${teamsPerGroup} times cada</p>
                    <p><strong>Total de Times:</strong> ${teams.length}</p>
                    <p><strong>Total de Jogos:</strong> ${matches.length}</p>
                    <p><strong>Primeiro Jogo:</strong> ${matches.length > 0 ? matches[0].date.toLocaleDateString('pt-BR') : 'N/A'}</p>
                    <p><strong>Último Jogo:</strong> ${matches.length > 0 ? matches[matches.length - 1].date.toLocaleDateString('pt-BR') : 'N/A'}</p>
                </div>
            `;
        }
        
        function createTournament() {
            if (!validateCurrentStep()) return;
            
            const tournamentData = {
                name: document.getElementById('tournament_name').value,
                year: document.getElementById('tournament_year').value,
                description: document.getElementById('tournament_description').value,
                teams: teams,
                groups: groups,
                matches: matches
            };
            
            // Simular criação do torneio
            console.log('🏆 TORNEIO CRIADO COM SUCESSO!');
            
            // Redirecionar para guia de gerenciamento
            window.location.href = 'tournament_management_guide.php';
        }
        
        function handleTeamNameKeyPress(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addTeam();
            }
        }

        function initializePage() {
            console.log('Inicializando página...'); // Debug
            updateGroupsPreview();

            // Focar no campo de nome do torneio
            const tournamentNameInput = document.getElementById('tournament_name');
            if (tournamentNameInput) {
                tournamentNameInput.focus();
            }

            console.log('Página inicializada com sucesso!'); // Debug
        }

        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            initializePage();
        });

        // Fallback para inicialização
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }
    </script>
</body>
</html>
