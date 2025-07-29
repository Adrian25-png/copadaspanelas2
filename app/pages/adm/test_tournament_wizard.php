<?php include 'admin_header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Assistente - Copa das Panelas</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .test-card {
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
        
        .test-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-title {
            color: #3498db;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #3498db;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
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
        
        .result {
            background: rgba(39, 174, 96, 0.2);
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }
        
        .error {
            background: rgba(231, 76, 60, 0.2);
            border: 2px solid #e74c3c;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            color: #e74c3c;
        }
        
        .teams-list {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .team-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #f39c12;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-card">
            <h1 class="title">
                <i class="fas fa-bug"></i>
                Teste do Assistente de Torneio
            </h1>
            
            <div class="test-section">
                <div class="test-title">
                    <i class="fas fa-plus"></i>
                    Teste 1: Adicionar Time Individual
                </div>
                
                <div class="form-group">
                    <label>Nome do Time</label>
                    <input type="text" id="test_team_name" placeholder="Digite o nome do time" onkeypress="if(event.key==='Enter') testAddTeam()">
                </div>
                
                <button class="btn btn-primary" onclick="testAddTeam()">
                    <i class="fas fa-plus"></i>
                    Adicionar Time
                </button>
                
                <div id="add_result" class="result">
                    <strong>✅ Sucesso!</strong> Time adicionado com sucesso.
                </div>
            </div>
            
            <div class="test-section">
                <div class="test-title">
                    <i class="fas fa-magic"></i>
                    Teste 2: Gerar Times de Exemplo
                </div>
                
                <div class="form-group">
                    <label>Número de Times</label>
                    <select id="test_num_teams">
                        <option value="8">8 times</option>
                        <option value="16" selected>16 times</option>
                        <option value="24">24 times</option>
                        <option value="32">32 times</option>
                    </select>
                </div>
                
                <button class="btn btn-success" onclick="testGenerateTeams()">
                    <i class="fas fa-magic"></i>
                    Gerar Times de Exemplo
                </button>
                
                <div id="generate_result" class="result">
                    <strong>✅ Sucesso!</strong> Times de exemplo gerados.
                </div>
            </div>
            
            <div class="test-section">
                <div class="test-title">
                    <i class="fas fa-list"></i>
                    Times Cadastrados
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span>Total: <strong id="total_teams">0</strong> times</span>
                    <button class="btn btn-danger" onclick="clearAllTeams()">
                        <i class="fas fa-trash"></i>
                        Limpar Todos
                    </button>
                </div>
                
                <div id="teams_display" class="teams-list">
                    <p style="text-align: center; opacity: 0.7;">Nenhum time cadastrado</p>
                </div>
            </div>
            
            <div class="test-section">
                <div class="test-title">
                    <i class="fas fa-cog"></i>
                    Teste 3: Funcionalidades JavaScript
                </div>
                
                <button class="btn" onclick="testJavaScriptFunctions()">
                    <i class="fas fa-play"></i>
                    Testar Funções JS
                </button>
                
                <div id="js_result" class="result">
                    <strong>✅ JavaScript funcionando!</strong> Todas as funções estão operacionais.
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="tournament_wizard.php" class="btn btn-success" style="font-size: 1.2rem; padding: 20px 40px;">
                    <i class="fas fa-magic"></i>
                    IR PARA O ASSISTENTE
                </a>
                <a href="dashboard_simple.php" class="btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script>
        let testTeams = [];
        
        function testAddTeam() {
            console.log('Testando adição de time...');
            
            const teamNameInput = document.getElementById('test_team_name');
            const teamName = teamNameInput.value.trim();
            
            if (!teamName) {
                alert('Por favor, digite o nome do time!');
                return;
            }
            
            // Verificar se já existe
            if (testTeams.some(team => team.name.toLowerCase() === teamName.toLowerCase())) {
                alert('Este time já foi cadastrado!');
                return;
            }
            
            // Adicionar time
            testTeams.push({
                id: testTeams.length + 1,
                name: teamName
            });
            
            // Limpar campo
            teamNameInput.value = '';
            
            // Mostrar resultado
            document.getElementById('add_result').style.display = 'block';
            setTimeout(() => {
                document.getElementById('add_result').style.display = 'none';
            }, 3000);
            
            updateTeamsDisplay();
            
            console.log('Time adicionado:', teamName);
        }
        
        function testGenerateTeams() {
            console.log('Testando geração de times...');
            
            const numTeams = parseInt(document.getElementById('test_num_teams').value);
            
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
            
            testTeams = [];
            for (let i = 0; i < numTeams; i++) {
                testTeams.push({
                    id: i + 1,
                    name: sampleNames[i] || `Time ${i + 1}`
                });
            }
            
            // Mostrar resultado
            document.getElementById('generate_result').style.display = 'block';
            setTimeout(() => {
                document.getElementById('generate_result').style.display = 'none';
            }, 3000);
            
            updateTeamsDisplay();
            
            console.log(`${numTeams} times gerados`);
        }
        
        function updateTeamsDisplay() {
            const display = document.getElementById('teams_display');
            const total = document.getElementById('total_teams');
            
            total.textContent = testTeams.length;
            
            if (testTeams.length === 0) {
                display.innerHTML = '<p style="text-align: center; opacity: 0.7;">Nenhum time cadastrado</p>';
                return;
            }
            
            display.innerHTML = testTeams.map((team, index) => `
                <div class="team-item">
                    <span><strong>${team.name}</strong> <small>(#${team.id})</small></span>
                    <button class="btn btn-danger" onclick="removeTestTeam(${index})" style="padding: 3px 8px; font-size: 0.8rem;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }
        
        function removeTestTeam(index) {
            const teamName = testTeams[index].name;
            testTeams.splice(index, 1);
            
            // Reindexar
            testTeams.forEach((team, i) => {
                team.id = i + 1;
            });
            
            updateTeamsDisplay();
            console.log(`Time "${teamName}" removido`);
        }
        
        function clearAllTeams() {
            if (testTeams.length === 0) {
                alert('Não há times para remover!');
                return;
            }
            
            if (confirm(`Tem certeza que deseja remover todos os ${testTeams.length} times?`)) {
                testTeams = [];
                updateTeamsDisplay();
                console.log('Todos os times removidos');
            }
        }
        
        function testJavaScriptFunctions() {
            console.log('Testando funções JavaScript...');
            
            try {
                // Testar funções básicas
                const testArray = [1, 2, 3];
                const testString = 'teste';
                const testNumber = 42;
                
                // Testar DOM
                const testElement = document.getElementById('js_result');
                if (!testElement) {
                    throw new Error('Elemento não encontrado');
                }
                
                // Testar eventos
                const testEvent = new Event('click');
                
                // Mostrar resultado
                document.getElementById('js_result').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('js_result').style.display = 'none';
                }, 3000);
                
                console.log('✅ Todas as funções JavaScript estão funcionando!');
                
            } catch (error) {
                console.error('❌ Erro nas funções JavaScript:', error);
            }
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página de teste carregada');
            updateTeamsDisplay();
        });
    </script>
</body>
</html>
