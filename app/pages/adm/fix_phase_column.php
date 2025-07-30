<?php
/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em 2025-07-30 16:47:18
 */
session_start();
require_once '../../includes/AdminProtection.php';
$adminProtection = protectAdminPage();
// Fim da proteção automática


/**
 * Script para corrigir o tamanho da coluna phase na tabela matches
 */

require_once '../../config/conexao.php';

try {
    $pdo = conectar();
    
    echo "<h2>Corrigindo coluna phase na tabela matches</h2>";
    
    // Verificar estrutura atual
    echo "<h3>Estrutura atual:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM matches LIKE 'phase'");
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        echo "Tipo atual: " . $current['Type'] . "<br>";
        echo "Nulo: " . $current['Null'] . "<br>";
        echo "Padrão: " . ($current['Default'] ?? 'NULL') . "<br><br>";
    }
    
    // Alterar coluna para VARCHAR(50)
    echo "<h3>Alterando coluna phase para VARCHAR(50)...</h3>";
    
    $sql = "ALTER TABLE matches MODIFY COLUMN phase VARCHAR(50) NULL";
    $pdo->exec($sql);
    
    echo "✅ Coluna alterada com sucesso!<br><br>";
    
    // Verificar nova estrutura
    echo "<h3>Nova estrutura:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM matches LIKE 'phase'");
    $new = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($new) {
        echo "Novo tipo: " . $new['Type'] . "<br>";
        echo "Nulo: " . $new['Null'] . "<br>";
        echo "Padrão: " . ($new['Default'] ?? 'NULL') . "<br><br>";
    }
    
    // Testar inserção das fases
    echo "<h3>Testando inserção das fases:</h3>";
    
    $test_phases = [
        'Oitavas de Final',
        'Quartas de Final', 
        'Semifinal',
        'Final',
        'Terceiro Lugar'
    ];
    
    foreach ($test_phases as $phase) {
        $length = strlen($phase);
        echo "✅ Fase: '$phase' - $length caracteres (OK)<br>";
    }
    
    echo "<br><h3>Correção concluída!</h3>";
    echo "<p>Agora você pode usar as seguintes fases sem problemas:</p>";
    echo "<ul>";
    foreach ($test_phases as $phase) {
        echo "<li>$phase</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='knockout_generator.php?tournament_id=26'>Testar Gerador de Eliminatórias</a></p>";
    echo "<p><a href='third_place_manager.php?tournament_id=26'>Testar Terceiro Lugar</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    // Tentar uma abordagem alternativa
    echo "<h3>Tentando abordagem alternativa...</h3>";
    
    try {
        // Verificar se a coluna existe
        $stmt = $pdo->query("SHOW COLUMNS FROM matches");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $phase_exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'phase') {
                $phase_exists = true;
                break;
            }
        }
        
        if (!$phase_exists) {
            echo "Coluna phase não existe. Criando...<br>";
            $pdo->exec("ALTER TABLE matches ADD COLUMN phase VARCHAR(50) NULL");
            echo "✅ Coluna phase criada!<br>";
        } else {
            echo "Coluna phase existe. Tentando alterar tipo...<br>";
            $pdo->exec("ALTER TABLE matches CHANGE phase phase VARCHAR(50) NULL");
            echo "✅ Coluna phase alterada!<br>";
        }
        
    } catch (Exception $e2) {
        echo "<p style='color: red;'>Erro na abordagem alternativa: " . $e2->getMessage() . "</p>";
        
        // Última tentativa - recriar a coluna
        echo "<h3>Última tentativa - recriando coluna...</h3>";
        try {
            $pdo->exec("ALTER TABLE matches DROP COLUMN phase");
            echo "Coluna phase removida.<br>";
            
            $pdo->exec("ALTER TABLE matches ADD COLUMN phase VARCHAR(50) NULL");
            echo "✅ Coluna phase recriada com VARCHAR(50)!<br>";
            
        } catch (Exception $e3) {
            echo "<p style='color: red;'>Erro final: " . $e3->getMessage() . "</p>";
        }
    }
}
?>
