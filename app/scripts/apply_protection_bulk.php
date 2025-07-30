<?php
/**
 * Script para Aplicar Proteção em Massa
 * Execute via linha de comando: php apply_protection_bulk.php
 */

// Configurações
$admin_dir = __DIR__ . '/../pages/adm/';
$protection_code = '/**
 * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER
 * Aplicada automaticamente em ' . date('Y-m-d H:i:s') . '
 */
session_start();
require_once \'../../includes/AdminProtection.php\';
$adminProtection = protectAdminPage();
// Fim da proteção automática

';

// Páginas que NÃO devem ser modificadas
$exclude_pages = [
    'login_simple.php',
    'admin_header.php', 
    'index.php',
    'apply_protection_to_all.php',
    'dashboard_simple.php', // já tem proteção customizada
    'create_tournament.php', // já tem proteção customizada
    'edit_tournament.php', // já tem proteção customizada
    'team_manager.php', // já tem proteção customizada
    'match_manager.php', // já tem proteção customizada
    'admin_manager.php', // já tem proteção customizada
    'admin_permissions.php', // já tem proteção customizada
    'statistics.php', // já foi protegida
    'all_teams.php', // já foi protegida
    'tournament_list.php', // já foi protegida
    'admin_credentials.php',
    'test_permissions.php',
    'demo_permissions.php',
    'demo_acesso_visual.php',
    'test_login_debug.php'
];

// Páginas prioritárias para proteger
$priority_pages = [
    'all_matches.php',
    'bulk_results.php',
    'edit_match.php',
    'match_details.php',
    'create_admin.php',
    'system_health.php',
    'system_logs.php',
    'system_settings.php',
    'global_calendar.php',
    'match_reports.php',
    'player_manager.php',
    'finals_manager.php',
    'group_manager.php'
];

$results = [];
$files = glob($admin_dir . '*.php');

echo "=== APLICANDO PROTEÇÃO EM MASSA ===\n";
echo "Diretório: $admin_dir\n";
echo "Total de arquivos PHP: " . count($files) . "\n\n";

foreach ($files as $file) {
    $filename = basename($file);
    
    // Pular arquivos excluídos
    if (in_array($filename, $exclude_pages)) {
        $results[$filename] = 'EXCLUÍDO';
        echo "❌ $filename - EXCLUÍDO\n";
        continue;
    }
    
    // Ler conteúdo
    $content = file_get_contents($file);
    
    // Verificar se já tem proteção
    if (strpos($content, 'AdminProtection') !== false || strpos($content, 'protectAdminPage') !== false) {
        $results[$filename] = 'JÁ PROTEGIDO';
        echo "✅ $filename - JÁ PROTEGIDO\n";
        continue;
    }
    
    // Verificar se é arquivo PHP válido
    if (strpos($content, '<?php') !== 0) {
        $results[$filename] = 'NÃO É PHP VÁLIDO';
        echo "⚠️  $filename - NÃO É PHP VÁLIDO\n";
        continue;
    }
    
    // Aplicar proteção
    $protected_content = applyProtectionToContent($content, $protection_code);
    
    if ($protected_content !== false) {
        // Fazer backup
        $backup_file = $file . '.backup.' . date('Y-m-d-H-i-s');
        copy($file, $backup_file);
        
        // Salvar arquivo protegido
        if (file_put_contents($file, $protected_content)) {
            $results[$filename] = 'PROTEGIDO';
            echo "🔒 $filename - PROTEGIDO COM SUCESSO\n";
        } else {
            $results[$filename] = 'ERRO AO SALVAR';
            echo "❌ $filename - ERRO AO SALVAR\n";
        }
    } else {
        $results[$filename] = 'ERRO NA PROTEÇÃO';
        echo "❌ $filename - ERRO NA PROTEÇÃO\n";
    }
}

// Resumo
echo "\n=== RESUMO ===\n";
$total = count($results);
$protected = count(array_filter($results, function($status) { return $status === 'PROTEGIDO'; }));
$already_protected = count(array_filter($results, function($status) { return $status === 'JÁ PROTEGIDO'; }));
$excluded = count(array_filter($results, function($status) { return $status === 'EXCLUÍDO'; }));
$errors = count(array_filter($results, function($status) { return strpos($status, 'ERRO') !== false; }));

echo "Total de arquivos: $total\n";
echo "Protegidos agora: $protected\n";
echo "Já protegidos: $already_protected\n";
echo "Excluídos: $excluded\n";
echo "Erros: $errors\n";

echo "\n=== PÁGINAS PRIORITÁRIAS ===\n";
foreach ($priority_pages as $page) {
    $status = $results[$page] ?? 'NÃO ENCONTRADO';
    echo "$page: $status\n";
}

/**
 * Aplicar proteção ao conteúdo do arquivo
 */
function applyProtectionToContent($content, $protection_code) {
    // Encontrar posição após <?php
    $php_pos = strpos($content, '<?php');
    if ($php_pos === false) {
        return false;
    }
    
    $insert_pos = $php_pos + 5; // após "<?php"
    
    // Verificar se já tem session_start
    $has_session_start = strpos($content, 'session_start()') !== false;
    
    // Preparar código de proteção
    $final_protection_code = "\n" . $protection_code;
    
    // Se não tem session_start, não adicionar (já está no código de proteção)
    // Se já tem, remover do código de proteção
    if ($has_session_start) {
        $final_protection_code = str_replace("session_start();\n", "", $final_protection_code);
    }
    
    // Inserir proteção
    $protected_content = substr($content, 0, $insert_pos) . $final_protection_code . substr($content, $insert_pos);
    
    return $protected_content;
}

echo "\n=== CONCLUÍDO ===\n";
echo "Proteção aplicada com sucesso!\n";
echo "Backups criados para todos os arquivos modificados.\n";
?>
