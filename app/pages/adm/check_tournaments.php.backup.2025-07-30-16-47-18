<?php
require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>🏆 Status dos Torneios</h2>";
    
    // Buscar todos os torneios
    $stmt = $pdo->query("SELECT * FROM tournaments ORDER BY id DESC");
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tournaments)) {
        echo "<p style='color: red;'>❌ Nenhum torneio encontrado no banco de dados</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #333; color: white;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Nome</th>";
        echo "<th style='padding: 10px;'>Ano</th>";
        echo "<th style='padding: 10px;'>Status</th>";
        echo "<th style='padding: 10px;'>Descrição</th>";
        echo "<th style='padding: 10px;'>Ações</th>";
        echo "</tr>";
        
        foreach ($tournaments as $tournament) {
            $statusColor = '';
            switch ($tournament['status']) {
                case 'active':
                case 'ativo':
                    $statusColor = 'color: green; font-weight: bold;';
                    break;
                case 'inactive':
                case 'inativo':
                    $statusColor = 'color: red;';
                    break;
                default:
                    $statusColor = 'color: orange;';
            }
            
            echo "<tr>";
            echo "<td style='padding: 8px; text-align: center;'>{$tournament['id']}</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($tournament['name'] ?? 'N/A') . "</td>";
            echo "<td style='padding: 8px; text-align: center;'>{$tournament['year']}</td>";
            echo "<td style='padding: 8px; text-align: center; $statusColor'>{$tournament['status']}</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($tournament['description'] ?? 'N/A') . "</td>";
            echo "<td style='padding: 8px; text-align: center;'>";
            
            if ($tournament['status'] === 'active' || $tournament['status'] === 'ativo') {
                echo "<a href='?action=deactivate&id={$tournament['id']}' style='color: red; text-decoration: none;'>❌ Desativar</a>";
            } else {
                echo "<a href='?action=activate&id={$tournament['id']}' style='color: green; text-decoration: none;'>✅ Ativar</a>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Processar ações
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = $_GET['action'];
        $tournament_id = (int)$_GET['id'];
        
        if ($action === 'activate') {
            // Desativar todos os outros torneios primeiro
            $stmt = $pdo->prepare("UPDATE tournaments SET status = 'inactive'");
            $stmt->execute();
            
            // Ativar o torneio selecionado
            $stmt = $pdo->prepare("UPDATE tournaments SET status = 'active' WHERE id = ?");
            $stmt->execute([$tournament_id]);
            
            echo "<p style='color: green; font-weight: bold;'>✅ Torneio ID $tournament_id ativado com sucesso!</p>";
            echo "<script>setTimeout(() => window.location.href = 'check_tournaments.php', 1500);</script>";
            
        } elseif ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE tournaments SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$tournament_id]);
            
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Torneio ID $tournament_id desativado!</p>";
            echo "<script>setTimeout(() => window.location.href = 'check_tournaments.php', 1500);</script>";
        }
    }
    
    // Verificar grupos e times do torneio ativo
    $stmt = $pdo->query("SELECT * FROM tournaments WHERE status IN ('active', 'ativo') ORDER BY id DESC LIMIT 1");
    $activeTournament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeTournament) {
        echo "<h3>📊 Dados do Torneio Ativo: {$activeTournament['name']}</h3>";
        
        // Contar grupos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM grupos WHERE tournament_id = ?");
        $stmt->execute([$activeTournament['id']]);
        $totalGroups = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Contar times
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM times WHERE tournament_id = ?");
        $stmt->execute([$activeTournament['id']]);
        $totalTeams = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Contar jogos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogos_fase_grupos WHERE grupo_id IN (SELECT id FROM grupos WHERE tournament_id = ?)");
        $stmt->execute([$activeTournament['id']]);
        $totalGames = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<ul>";
        echo "<li><strong>Grupos:</strong> $totalGroups</li>";
        echo "<li><strong>Times:</strong> $totalTeams</li>";
        echo "<li><strong>Jogos da Fase de Grupos:</strong> $totalGames</li>";
        echo "</ul>";
        
        if ($totalGroups == 0 || $totalTeams == 0) {
            echo "<p style='color: orange;'>⚠️ O torneio ativo não possui grupos ou times cadastrados.</p>";
        }
    } else {
        echo "<h3 style='color: red;'>❌ Nenhum Torneio Ativo</h3>";
        echo "<p>Não há nenhum torneio ativo no momento. Ative um torneio para ver os dados na tabela de classificação.</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='../tabela_de_classificacao.php' target='_blank' style='background: #7B1FA2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Ver Tabela de Classificação</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    background: #1a1a1a;
    color: white;
    padding: 20px;
}

table {
    background: #2a2a2a;
    color: white;
}

th {
    background: #333 !important;
}

tr:nth-child(even) {
    background: #333;
}

a {
    padding: 5px 10px;
    border-radius: 3px;
    text-decoration: none;
    font-weight: bold;
}
</style>
