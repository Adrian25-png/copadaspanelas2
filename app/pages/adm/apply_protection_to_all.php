<?php
/**
 * Script para Aplicar Proteção Automática em Todas as Páginas Administrativas
 * EXECUTE ESTE SCRIPT UMA VEZ PARA PROTEGER TODAS AS PÁGINAS
 */

require_once '../../includes/PagePermissions.php';

// Lista de páginas que NÃO devem ser modificadas
$exclude_pages = [
    'login_simple.php', // página de login
    'admin_header.php', // componente
    'index.php', // redirecionamento
    'apply_protection_to_all.php', // este script
    'PROBLEMA_LOGIN_RESOLVIDO.md',
    'SISTEMA_PERMISSOES_FUNCIONANDO.md',
    'SISTEMA_PERMISSOES_VISUAL.md'
];

// Páginas que já foram modificadas manualmente
$already_protected = [
    'dashboard_simple.php',
    'create_tournament.php',
    'edit_tournament.php',
    'team_manager.php',
    'match_manager.php',
    'admin_manager.php',
    'admin_permissions.php'
];

$current_dir = __DIR__;
$files = glob($current_dir . '/*.php');
$results = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    // Pular arquivos excluídos
    if (in_array($filename, $exclude_pages)) {
        $results[$filename] = 'EXCLUÍDO';
        continue;
    }
    
    // Pular arquivos já protegidos
    if (in_array($filename, $already_protected)) {
        $results[$filename] = 'JÁ PROTEGIDO';
        continue;
    }
    
    // Ler conteúdo do arquivo
    $content = file_get_contents($file);
    
    // Verificar se já tem proteção
    if (strpos($content, 'AdminProtection') !== false || strpos($content, 'protectAdminPage') !== false) {
        $results[$filename] = 'JÁ TEM PROTEÇÃO';
        continue;
    }
    
    // Verificar se é um arquivo PHP válido
    if (strpos($content, '<?php') === false) {
        $results[$filename] = 'NÃO É PHP';
        continue;
    }
    
    // Aplicar proteção
    $protected = applyProtection($content, $filename);
    
    if ($protected !== false) {
        // Fazer backup do arquivo original
        $backup_file = $file . '.backup.' . date('Y-m-d-H-i-s');
        copy($file, $backup_file);
        
        // Salvar arquivo protegido
        if (file_put_contents($file, $protected)) {
            $results[$filename] = 'PROTEGIDO COM SUCESSO';
        } else {
            $results[$filename] = 'ERRO AO SALVAR';
        }
    } else {
        $results[$filename] = 'ERRO NA PROTEÇÃO';
    }
}

/**
 * Aplicar proteção automática ao conteúdo do arquivo
 */
function applyProtection($content, $filename) {
    // Encontrar a posição após <?php
    $php_start = strpos($content, '<?php');
    if ($php_start === false) {
        return false;
    }
    
    // Encontrar o final da tag <?php
    $php_end = $php_start + 5;
    
    // Verificar se já tem session_start()
    $has_session_start = strpos($content, 'session_start()') !== false;
    
    // Código de proteção a ser inserido
    $protection_code = "\n/**\n * PROTEÇÃO AUTOMÁTICA - NÃO REMOVER\n * Aplicada automaticamente em " . date('Y-m-d H:i:s') . "\n */\n";
    
    if (!$has_session_start) {
        $protection_code .= "session_start();\n";
    }
    
    $protection_code .= "require_once '../../includes/AdminProtection.php';\n";
    $protection_code .= "\$adminProtection = protectAdminPage();\n";
    $protection_code .= "// Fim da proteção automática\n\n";
    
    // Inserir proteção após <?php
    $protected_content = substr($content, 0, $php_end) . $protection_code . substr($content, $php_end);
    
    return $protected_content;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicar Proteção - Copa das Panelas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .results-table th,
        .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .results-table th {
            background: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }

        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-success {
            background: #27ae60;
            color: white;
        }

        .status-info {
            background: #3498db;
            color: white;
        }

        .status-warning {
            background: #f39c12;
            color: white;
        }

        .status-error {
            background: #e74c3c;
            color: white;
        }

        .summary {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Aplicação de Proteção Automática</h1>
            <p>Resultado da aplicação de proteção em todas as páginas administrativas</p>
        </div>

        <?php
        $total = count($results);
        $protected = count(array_filter($results, function($status) { return $status === 'PROTEGIDO COM SUCESSO'; }));
        $already_protected = count(array_filter($results, function($status) { return in_array($status, ['JÁ PROTEGIDO', 'JÁ TEM PROTEÇÃO']); }));
        $excluded = count(array_filter($results, function($status) { return $status === 'EXCLUÍDO'; }));
        $errors = count(array_filter($results, function($status) { return strpos($status, 'ERRO') !== false; }));
        ?>

        <div class="summary">
            <h3><i class="fas fa-chart-pie"></i> Resumo da Operação</h3>
            <p><strong>Total de arquivos:</strong> <?= $total ?></p>
            <p><strong>Protegidos agora:</strong> <span style="color: #27ae60;"><?= $protected ?></span></p>
            <p><strong>Já protegidos:</strong> <span style="color: #3498db;"><?= $already_protected ?></span></p>
            <p><strong>Excluídos:</strong> <span style="color: #f39c12;"><?= $excluded ?></span></p>
            <p><strong>Erros:</strong> <span style="color: #e74c3c;"><?= $errors ?></span></p>
        </div>

        <table class="results-table">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Status</th>
                    <th>Permissões Necessárias</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $filename => $status): ?>
                    <?php
                    $status_class = 'status-info';
                    if ($status === 'PROTEGIDO COM SUCESSO') $status_class = 'status-success';
                    elseif (strpos($status, 'ERRO') !== false) $status_class = 'status-error';
                    elseif ($status === 'EXCLUÍDO') $status_class = 'status-warning';
                    
                    $required_perms = PagePermissions::getRequiredPermissions($filename);
                    $perms_text = $required_perms ? implode(', ', $required_perms) : 'Nenhuma';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($filename) ?></strong></td>
                        <td><span class="status <?= $status_class ?>"><?= htmlspecialchars($status) ?></span></td>
                        <td><small><?= htmlspecialchars($perms_text) ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard_simple.php" class="btn">
                <i class="fas fa-home"></i> Voltar ao Dashboard
            </a>
            <a href="test_permissions.php" class="btn">
                <i class="fas fa-list"></i> Testar Permissões
            </a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: rgba(255, 193, 7, 0.2); border-radius: 10px;">
            <h4><i class="fas fa-info-circle"></i> Informações Importantes</h4>
            <ul>
                <li>Backups dos arquivos originais foram criados com extensão .backup</li>
                <li>A proteção foi aplicada automaticamente no início de cada arquivo</li>
                <li>Todas as páginas agora verificam login e permissões antes de executar</li>
                <li>Tentativas de acesso não autorizado são registradas nos logs</li>
            </ul>
        </div>
    </div>
</body>
</html>
