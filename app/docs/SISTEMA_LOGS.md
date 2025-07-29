# Sistema de Logs - Copa das Panelas

## Visão Geral

O Sistema de Logs da Copa das Panelas é uma solução completa para rastreamento de eventos, ações de usuários, erros e atividades do sistema. Ele fornece uma interface web para visualização e gerenciamento de logs, além de uma API PHP para registro automático de eventos.

## Características

- ✅ **Interface Web Completa**: Visualização, filtros e paginação de logs
- ✅ **Múltiplos Níveis**: INFO, SUCCESS, WARNING, ERROR
- ✅ **Contexto Rico**: Armazenamento de dados estruturados em JSON
- ✅ **Filtros Avançados**: Por nível, data, usuário e componente
- ✅ **Paginação**: Navegação eficiente em grandes volumes de logs
- ✅ **Backup Automático**: Criação de backup antes de limpar logs
- ✅ **Integração Fácil**: Classe PHP simples para uso em qualquer página
- ✅ **Design Copa das Panelas**: Interface seguindo o padrão visual do projeto

## Estrutura do Banco de Dados

```sql
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('INFO', 'SUCCESS', 'WARNING', 'ERROR') NOT NULL,
    message TEXT NOT NULL,
    context JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);
```

## Arquivos do Sistema

### Principais
- `app/pages/adm/system_logs.php` - Interface web para visualização de logs
- `app/includes/system_logger.php` - Classe principal do sistema de logs
- `app/examples/logging_examples.php` - Exemplos de uso e integração

### Integrados
- `app/pages/adm/login_simple.php` - Logs de autenticação
- `app/pages/adm/system_settings.php` - Logs de manutenção do sistema

## Como Usar

### 1. Incluir o Sistema de Logs

```php
<?php
require_once '../../config/conexao.php';
require_once '../../includes/system_logger.php';

$pdo = conectar();
$logger = getSystemLogger($pdo);
?>
```

### 2. Registrar Logs Básicos

```php
// Log de informação
$logger->info('Usuário visualizou página de relatórios');

// Log de sucesso
$logger->success('Torneio criado com sucesso');

// Log de aviso
$logger->warning('Tentativa de acesso negada');

// Log de erro
$logger->error('Falha na conexão com banco de dados');
```

### 3. Logs com Contexto

```php
$logger->success('Torneio criado com sucesso', [
    'component' => 'tournament',
    'tournament_id' => 123,
    'tournament_name' => 'Copa das Panelas 2024'
], $user_id, $username);
```

### 4. Logs Especializados

```php
// Logs de autenticação
$logger->logLogin('admin', true, 1);  // Login bem-sucedido
$logger->logLogin('user', false);     // Login falhado
$logger->logLogout('admin', 1);       // Logout

// Logs de torneios
$logger->logTournamentCreated('Copa 2024', 1, 'admin');
$logger->logTournamentUpdated('Copa 2024', 1, 'admin');
$logger->logTournamentDeleted('Copa 2023', 2, 'admin');

// Logs de times
$logger->logTeamCreated('Flamengo', 1, 1, 'admin');

// Logs de jogos
$logger->logMatchResultUpdated(1, 'Flamengo', 'Vasco', '2x1', 'admin');

// Logs de sistema
$logger->logBackup('backup.sql', '2.5MB', true, 'admin');
$logger->logCacheCleared(156, 'admin');
$logger->logDatabaseOptimized(8, 'admin');
$logger->logSystemError('Erro crítico', 'database');
```

## Interface Web

### Acesso
- URL: `http://localhost/copadaspanelas2/app/pages/adm/system_logs.php`
- Requer autenticação de administrador

### Funcionalidades

#### Filtros
- **Nível do Log**: INFO, SUCCESS, WARNING, ERROR
- **Data**: Filtro por data específica
- **Logs por página**: 25, 50, 100, 200

#### Ações
- **Limpar Logs**: Remove todos os logs (cria backup automático)
- **Adicionar Logs de Exemplo**: Popula com dados de demonstração
- **Paginação**: Navegação entre páginas de resultados

#### Visualização
- **Timestamp**: Data e hora formatada
- **Nível**: Badge colorido por tipo
- **Mensagem**: Descrição do evento
- **Contexto**: Dados estruturados em JSON
- **Usuário**: Nome do usuário responsável
- **IP**: Endereço IP de origem
- **User Agent**: Informações do navegador

## Níveis de Log

### INFO (Azul)
- Eventos informativos normais
- Visualizações de páginas
- Operações de rotina

### SUCCESS (Verde)
- Operações concluídas com sucesso
- Criações, atualizações bem-sucedidas
- Logins válidos

### WARNING (Laranja)
- Situações que requerem atenção
- Tentativas de acesso negadas
- Recursos próximos do limite

### ERROR (Vermelho)
- Erros e falhas do sistema
- Conexões perdidas
- Operações falhadas

## Integração em Páginas Existentes

### Exemplo: Página de Criação de Torneio

```php
<?php
session_start();
require_once '../../config/conexao.php';
require_once '../../includes/system_logger.php';

$pdo = conectar();
$logger = getSystemLogger($pdo);

if ($_POST && isset($_POST['tournament_name'])) {
    try {
        $tournament_name = $_POST['tournament_name'];
        
        // Criar torneio
        $stmt = $pdo->prepare("INSERT INTO tournaments (name) VALUES (?)");
        $stmt->execute([$tournament_name]);
        $tournament_id = $pdo->lastInsertId();
        
        // Registrar log
        $logger->logTournamentCreated($tournament_name, $tournament_id);
        
        $_SESSION['success'] = 'Torneio criado com sucesso!';
        
    } catch (Exception $e) {
        $logger->logSystemError('Erro ao criar torneio: ' . $e->getMessage(), 'tournament');
        $_SESSION['error'] = 'Erro ao criar torneio';
    }
}
?>
```

### Exemplo: Middleware de Autenticação

```php
function checkAuth() {
    global $logger;
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        $logger->logAccessDenied($_SERVER['REQUEST_URI'], 'Usuário não autenticado');
        header('Location: login_simple.php');
        exit;
    }
}
```

## Consultas Personalizadas

```php
// Buscar logs de erro dos últimos 7 dias
$stmt = $pdo->prepare("
    SELECT * FROM system_logs 
    WHERE level = 'ERROR' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
");
$stmt->execute();
$recent_errors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar logs de um usuário específico
$stmt = $pdo->prepare("
    SELECT * FROM system_logs 
    WHERE username = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute(['admin']);
$user_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar logs por componente
$stmt = $pdo->prepare("
    SELECT * FROM system_logs 
    WHERE JSON_EXTRACT(context, '$.component') = ?
    ORDER BY created_at DESC
");
$stmt->execute(['tournament']);
$tournament_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

## Manutenção

### Limpeza Automática
Considere implementar limpeza automática de logs antigos:

```php
// Manter apenas logs dos últimos 90 dias
$pdo->exec("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
```

### Backup
O sistema cria backups automáticos antes de limpar logs:

```sql
CREATE TABLE system_logs_backup_2024_01_15_14_30_25 AS SELECT * FROM system_logs;
```

## Monitoramento

### Alertas por Email
Implemente alertas para logs críticos:

```php
if ($level === 'ERROR') {
    // Enviar email para administradores
    mail('admin@copa.com', 'Erro no Sistema', $message);
}
```

### Dashboard de Métricas
Crie dashboards com estatísticas de logs:

```php
// Contagem por nível nas últimas 24h
$stats = $pdo->query("
    SELECT level, COUNT(*) as count 
    FROM system_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY level
")->fetchAll(PDO::FETCH_ASSOC);
```

## Segurança

- ✅ Logs não contêm senhas ou dados sensíveis
- ✅ Acesso restrito a administradores
- ✅ Sanitização de dados de entrada
- ✅ Proteção contra SQL injection
- ✅ Backup automático antes de exclusões

## Suporte

Para dúvidas ou problemas com o sistema de logs:
1. Verifique os exemplos em `app/examples/logging_examples.php`
2. Consulte esta documentação
3. Teste na interface web em `system_logs.php`
