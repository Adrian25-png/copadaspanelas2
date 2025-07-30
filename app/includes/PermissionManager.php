<?php
/**
 * Sistema de Gerenciamento de Permissões
 * Copa das Panelas
 */

class PermissionManager {
    private $pdo;
    private $admin_id;
    private $admin_source;
    private $permissions_cache = null;
    
    public function __construct($pdo, $admin_id = null, $admin_source = null) {
        $this->pdo = $pdo;
        $this->admin_id = $admin_id ?? $_SESSION['admin_id'] ?? null;
        $this->admin_source = $admin_source ?? $_SESSION['admin_source'] ?? null;
    }
    
    /**
     * Verificar se o usuário tem uma permissão específica
     */
    public function hasPermission($permission) {
        // Se não há admin logado, negar acesso
        if (!$this->admin_id) {
            return false;
        }
        
        // Se o admin veio das tabelas antigas (administradores ou admin), dar acesso total
        if ($this->admin_source && in_array($this->admin_source, ['administradores', 'admin'])) {
            return true;
        }
        
        // Para admins da tabela 'admins', verificar permissões específicas
        if ($this->admin_source === 'admins') {
            return $this->checkAdminPermission($permission);
        }
        
        // Por padrão, dar acesso total (fallback para compatibilidade)
        return true;
    }
    
    /**
     * Verificar permissão específica na tabela admin_permissions
     */
    private function checkAdminPermission($permission) {
        if ($this->permissions_cache === null) {
            $this->loadPermissions();
        }
        
        return in_array($permission, $this->permissions_cache);
    }
    
    /**
     * Carregar permissões do banco de dados
     */
    private function loadPermissions() {
        try {
            $stmt = $this->pdo->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
            $stmt->execute([$this->admin_id]);
            $this->permissions_cache = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $this->permissions_cache = [];
        }
    }
    
    /**
     * Verificar múltiplas permissões (OR - pelo menos uma)
     */
    public function hasAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Verificar múltiplas permissões (AND - todas)
     */
    public function hasAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Obter todas as permissões do usuário
     */
    public function getUserPermissions() {
        if ($this->admin_source && in_array($this->admin_source, ['administradores', 'admin'])) {
            // Retornar todas as permissões para admins das tabelas antigas
            return [
                'create_tournament', 'edit_tournament', 'delete_tournament', 'view_tournament',
                'create_team', 'edit_team', 'delete_team', 'view_team',
                'create_match', 'edit_match', 'delete_match', 'view_match', 'edit_results',
                'create_admin', 'edit_admin', 'delete_admin', 'view_admin', 'manage_permissions',
                'view_statistics', 'system_settings', 'backup_restore', 'view_logs'
            ];
        }
        
        if ($this->permissions_cache === null) {
            $this->loadPermissions();
        }
        
        return $this->permissions_cache;
    }
    
    /**
     * Middleware para verificar permissão e redirecionar se necessário
     */
    public function requirePermission($permission, $redirect_url = 'dashboard_simple.php') {
        if (!$this->hasPermission($permission)) {
            $_SESSION['permission_error'] = [
                'message' => "Você não tem permissão para acessar esta funcionalidade.",
                'required_permission' => $permission,
                'user_permissions' => $this->getUserPermissions()
            ];
            header("Location: $redirect_url");
            exit;
        }
    }
    
    /**
     * Middleware para verificar qualquer uma das permissões
     */
    public function requireAnyPermission($permissions, $redirect_url = 'dashboard_simple.php') {
        if (!$this->hasAnyPermission($permissions)) {
            $_SESSION['permission_error'] = [
                'message' => "Você não tem permissão para acessar esta funcionalidade.",
                'required_permissions' => $permissions,
                'user_permissions' => $this->getUserPermissions()
            ];
            header("Location: $redirect_url");
            exit;
        }
    }
    
    /**
     * Verificar se é super admin (das tabelas antigas)
     */
    public function isSuperAdmin() {
        return $this->admin_source && in_array($this->admin_source, ['administradores', 'admin']);
    }
    
    /**
     * Atualizar permissões de um admin
     */
    public function updateAdminPermissions($admin_id, $permissions) {
        try {
            // Remover permissões existentes
            $stmt = $this->pdo->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
            
            // Adicionar novas permissões
            if (!empty($permissions)) {
                $stmt = $this->pdo->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
                foreach ($permissions as $permission) {
                    $stmt->execute([$admin_id, $permission]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obter permissões de um admin específico
     */
    public function getAdminPermissions($admin_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
}

/**
 * Função helper para criar instância do gerenciador de permissões
 */
function getPermissionManager($pdo, $admin_id = null, $admin_source = null) {
    return new PermissionManager($pdo, $admin_id, $admin_source);
}
?>
