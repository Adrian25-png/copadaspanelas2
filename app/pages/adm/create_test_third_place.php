<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    $tournament_id = 26; // ID do torneio atual
    
    echo "<h2>Criar Jogo de Terceiro Lugar para Teste</h2>";
    
    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT id FROM matches WHERE tournament_id = ? AND phase = '3º Lugar'");
    $stmt->execute([$tournament_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "<p>✅ Já existe um jogo de terceiro lugar (ID: " . $existing['id'] . ")</p>";
    } else {
        // Buscar dois times quaisquer do torneio
        $stmt = $pdo->prepare("SELECT id, nome FROM times WHERE tournament_id = ? LIMIT 2");
        $stmt->execute([$tournament_id]);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($teams) >= 2) {
            // Criar jogo de terceiro lugar
            $stmt = $pdo->prepare("
                INSERT INTO matches (tournament_id, team1_id, team2_id, phase, status, created_at)
                VALUES (?, ?, ?, '3º Lugar', 'agendado', NOW())
            ");
            $result = $stmt->execute([$tournament_id, $teams[0]['id'], $teams[1]['id']]);
            
            if ($result) {
                $match_id = $pdo->lastInsertId();
                echo "<p>✅ Jogo de terceiro lugar criado com sucesso!</p>";
                echo "<p>ID do jogo: $match_id</p>";
                echo "<p>Times: " . htmlspecialchars($teams[0]['nome']) . " vs " . htmlspecialchars($teams[1]['nome']) . "</p>";
            } else {
                echo "<p>❌ Erro ao criar jogo</p>";
            }
        } else {
            echo "<p>❌ Não há times suficientes no torneio</p>";
        }
    }
    
    // Verificar resultado
    echo "<h3>Verificação:</h3>";
    $stmt = $pdo->prepare("
        SELECT m.*, t1.nome as team1_name, t2.nome as team2_name
        FROM matches m
        LEFT JOIN times t1 ON m.team1_id = t1.id
        LEFT JOIN times t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.phase = '3º Lugar'
    ");
    $stmt->execute([$tournament_id]);
    $third_place_match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($third_place_match) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; background: #f9f9f9;'>";
        echo "<h4>Jogo de Terceiro Lugar:</h4>";
        echo "<p><strong>ID:</strong> " . $third_place_match['id'] . "</p>";
        echo "<p><strong>Fase:</strong> " . htmlspecialchars($third_place_match['phase']) . "</p>";
        echo "<p><strong>Times:</strong> " . htmlspecialchars($third_place_match['team1_name']) . " vs " . htmlspecialchars($third_place_match['team2_name']) . "</p>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($third_place_match['status']) . "</p>";
        echo "</div>";
    } else {
        echo "<p>❌ Nenhum jogo de terceiro lugar encontrado</p>";
    }
    
    echo "<br>";
    echo "<a href='../exibir_finais.php'>Testar página de exibição das finais</a><br>";
    echo "<a href='third_place_manager.php?tournament_id=$tournament_id'>Gerenciar terceiro lugar</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>
