<?php
/**
 * Sistema de Proteção Automática para Páginas Administrativas
 * Copa das Panelas - Segurança Total
 */

require_once __DIR__ . '/PermissionManager.php';
require_once __DIR__ . '/PagePermissions.php';

class AdminProtection {
    
    private $pdo;
    private $permissionManager;
    private $current_page;
    
    public function __construct($pdo = null) {
        // Iniciar sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->pdo = $pdo;
        $this->current_page = basename($_SERVER['PHP_SELF']);
        
        // Conectar ao banco se não foi fornecido
        if (!$this->pdo) {
            try {
                require_once __DIR__ . '/../config/conexao.php';
                $this->pdo = conectar();
            } catch (Exception $e) {
                $this->redirectToLogin('Erro de conexão com o banco de dados');
                return;
            }
        }
        
        $this->permissionManager = getPermissionManager($this->pdo);
    }
    
    /**
     * Verificar autenticação e permissões
     */
    public function protect() {
        // 1. Verificar se está logado
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin('Você precisa fazer login para acessar esta página');
            return;
        }
        
        // 2. Verificar permissões específicas da página
        if (!$this->hasPagePermission()) {
            $this->redirectToDashboard('Você não tem permissão para acessar esta página');
            return;
        }
        
        // 3. Log de acesso (opcional)
        $this->logAccess();
    }
    
    /**
     * Verificar se usuário está logado
     */
    private function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true &&
               isset($_SESSION['admin_id']) &&
               isset($_SESSION['admin_username']);
    }
    
    /**
     * Verificar permissões específicas da página
     */
    private function hasPagePermission() {
        // Verificar se a página atual pode ser acessada
        return PagePermissions::canAccessPage($this->current_page, $this->permissionManager);
    }
    
    /**
     * Redirecionar para login
     */
    private function redirectToLogin($message = '') {
        if ($message) {
            $_SESSION['login_error'] = $message;
        }
        
        // Salvar URL de destino para redirecionamento após login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: login_simple.php');
        exit;
    }
    
    /**
     * Redirecionar para página de acesso negado
     */
    private function redirectToDashboard($message = '') {
        $required_permissions = PagePermissions::getRequiredPermissions($this->current_page);

        $_SESSION['permission_error'] = [
            'message' => $message,
            'page' => $this->current_page,
            'required_permissions' => $required_permissions,
            'user_permissions' => $this->permissionManager->getUserPermissions()
        ];

        // Log da tentativa de acesso negada
        $this->logAccessDenied();

        header('Location: dashboard_simple.php');
        exit;
    }
    
    /**
     * Registrar acesso à página
     */
    private function logAccess() {
        try {
            require_once __DIR__ . '/system_logger.php';
            $logger = getSystemLogger($this->pdo);
            
            $logger->info("Acesso à página administrativa: {$this->current_page}", [
                'component' => 'admin_access',
                'page' => $this->current_page,
                'user_id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username'],
                'source' => $_SESSION['admin_source'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Falha silenciosa no log
        }
    }
    
    /**
     * Registrar tentativa de acesso negada
     */
    private function logAccessDenied() {
        try {
            require_once __DIR__ . '/system_logger.php';
            $logger = getSystemLogger($this->pdo);
            
            $required_permissions = PagePermissions::getRequiredPermissions($this->current_page);
            
            $logger->warning("Tentativa de acesso negada à página: {$this->current_page}", [
                'component' => 'security',
                'action' => 'access_denied',
                'page' => $this->current_page,
                'required_permissions' => $required_permissions,
                'user_permissions' => $this->permissionManager->getUserPermissions(),
                'user_id' => $_SESSION['admin_id'] ?? null,
                'username' => $_SESSION['admin_username'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Falha silenciosa no log
        }
    }
    
    /**
     * Obter informações da proteção atual
     */
    public function getProtectionInfo() {
        return [
            'current_page' => $this->current_page,
            'is_logged_in' => $this->isLoggedIn(),
            'has_permission' => $this->hasPagePermission(),
            'required_permissions' => PagePermissions::getRequiredPermissions($this->current_page),
            'user_permissions' => $this->permissionManager ? $this->permissionManager->getUserPermissions() : [],
            'is_super_admin' => $this->permissionManager ? $this->permissionManager->isSuperAdmin() : false
        ];
    }
    
    /**
     * Verificar permissão específica (para uso em páginas)
     */
    public function hasPermission($permission) {
        return $this->permissionManager ? $this->permissionManager->hasPermission($permission) : false;
    }
    
    /**
     * Verificar qualquer uma das permissões (para uso em páginas)
     */
    public function hasAnyPermission($permissions) {
        return $this->permissionManager ? $this->permissionManager->hasAnyPermission($permissions) : false;
    }
    
    /**
     * Obter gerenciador de permissões
     */
    public function getPermissionManager() {
        return $this->permissionManager;
    }
}

/**
 * Função helper para proteção automática
 * Use esta função no início de qualquer página administrativa
 */
function protectAdminPage($pdo = null) {
    $protection = new AdminProtection($pdo);
    $protection->protect();
    return $protection;
}

/**
 * Função helper para verificação rápida de permissão
 */
function requirePermission($permission, $pdo = null) {
    $protection = new AdminProtection($pdo);
    if (!$protection->hasPermission($permission)) {
        $_SESSION['permission_error'] = [
            'message' => "Você não tem permissão para executar esta ação.",
            'required_permission' => $permission,
            'user_permissions' => $protection->getPermissionManager()->getUserPermissions()
        ];
        header('Location: dashboard_simple.php');
        exit;
    }
    return $protection;
}
?>
