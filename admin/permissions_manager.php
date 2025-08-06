<?php
/*
 * Sistema de Permisos Granulares - Funciones Principales
 * Autor: Sistema Índice SaaS
 * Fecha: Agosto 2025
 */

class PermissionsManager {
    private $mysqli;
    private $current_user_id;
    private $current_company_id;
    
    public function __construct($mysqli, $user_id = null, $company_id = null) {
        $this->mysqli = $mysqli;
        $this->current_user_id = $user_id;
        $this->current_company_id = $company_id;
    }
    
    /**
     * Verificar si un usuario tiene un permiso específico
     */
    public function hasPermission($user_id, $company_id, $module_id, $permission_type) {
        $stmt = $this->mysqli->prepare("
            SELECT {$permission_type} 
            FROM user_module_permissions 
            WHERE user_id = ? AND company_id = ? AND module_id = ?
        ");
        
        $stmt->bind_param("iis", $user_id, $company_id, $module_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return (bool)$row[$permission_type];
        }
        
        return false;
    }
    
    /**
     * Obtener todos los permisos de un usuario para una empresa
     */
    public function getUserPermissions($user_id, $company_id) {
        $stmt = $this->mysqli->prepare("
            SELECT 
                ump.*,
                sm.name as module_name,
                sm.icon as module_icon,
                sm.category as module_category
            FROM user_module_permissions ump
            JOIN system_modules sm ON ump.module_id = sm.id
            WHERE ump.user_id = ? AND ump.company_id = ?
            ORDER BY sm.sort_order
        ");
        
        $stmt->bind_param("ii", $user_id, $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[$row['module_id']] = $row;
        }
        
        return $permissions;
    }
    
    /**
     * Asignar permisos a un usuario usando una plantilla de rol
     */
    public function assignRoleToUser($user_id, $company_id, $role_name, $granted_by) {
        // Obtener plantilla del rol
        $stmt = $this->mysqli->prepare("
            SELECT * FROM role_permission_templates 
            WHERE role_name = ?
        ");
        
        $stmt->bind_param("s", $role_name);
        $stmt->execute();
        $templates = $stmt->get_result();
        
        $success_count = 0;
        
        // Insertar/actualizar permisos basados en la plantilla
        while ($template = $templates->fetch_assoc()) {
            $insert_stmt = $this->mysqli->prepare("
                INSERT INTO user_module_permissions 
                (user_id, company_id, module_id, can_view, can_create, can_edit, can_delete, can_admin, granted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                can_view = VALUES(can_view),
                can_create = VALUES(can_create),
                can_edit = VALUES(can_edit),
                can_delete = VALUES(can_delete),
                can_admin = VALUES(can_admin),
                granted_by = VALUES(granted_by),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $insert_stmt->bind_param("iisiiiiiii", 
                $user_id, 
                $company_id, 
                $template['module_id'],
                $template['can_view'],
                $template['can_create'],
                $template['can_edit'],
                $template['can_delete'],
                $template['can_admin'],
                $granted_by
            );
            
            if ($insert_stmt->execute()) {
                $success_count++;
                
                // Registrar en auditoría
                $this->logPermissionChange(
                    $granted_by,
                    $user_id,
                    $company_id,
                    $template['module_id'],
                    'role_assigned',
                    null,
                    json_encode($template)
                );
            }
        }
        
        return $success_count;
    }
    
    /**
     * Actualizar permisos específicos de un usuario
     */
    public function updateUserPermissions($user_id, $company_id, $module_id, $permissions, $granted_by) {
        // Obtener permisos actuales para auditoría
        $old_permissions = $this->getUserModulePermissions($user_id, $company_id, $module_id);
        
        $stmt = $this->mysqli->prepare("
            INSERT INTO user_module_permissions 
            (user_id, company_id, module_id, can_view, can_create, can_edit, can_delete, can_admin, granted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            can_view = VALUES(can_view),
            can_create = VALUES(can_create),
            can_edit = VALUES(can_edit),
            can_delete = VALUES(can_delete),
            can_admin = VALUES(can_admin),
            granted_by = VALUES(granted_by),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bind_param("iisiiiiii", 
            $user_id, 
            $company_id, 
            $module_id,
            $permissions['can_view'] ?? 0,
            $permissions['can_create'] ?? 0,
            $permissions['can_edit'] ?? 0,
            $permissions['can_delete'] ?? 0,
            $permissions['can_admin'] ?? 0,
            $granted_by
        );
        
        if ($stmt->execute()) {
            // Registrar en auditoría
            $this->logPermissionChange(
                $granted_by,
                $user_id,
                $company_id,
                $module_id,
                'permissions_updated',
                json_encode($old_permissions),
                json_encode($permissions)
            );
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener permisos de un módulo específico
     */
    private function getUserModulePermissions($user_id, $company_id, $module_id) {
        $stmt = $this->mysqli->prepare("
            SELECT can_view, can_create, can_edit, can_delete, can_admin
            FROM user_module_permissions
            WHERE user_id = ? AND company_id = ? AND module_id = ?
        ");
        
        $stmt->bind_param("iis", $user_id, $company_id, $module_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Eliminar todos los permisos de un usuario en una empresa
     */
    public function removeUserPermissions($user_id, $company_id, $removed_by) {
        // Obtener permisos actuales para auditoría
        $current_permissions = $this->getUserPermissions($user_id, $company_id);
        
        $stmt = $this->mysqli->prepare("
            DELETE FROM user_module_permissions 
            WHERE user_id = ? AND company_id = ?
        ");
        
        $stmt->bind_param("ii", $user_id, $company_id);
        
        if ($stmt->execute()) {
            // Registrar en auditoría
            foreach ($current_permissions as $module_id => $permissions) {
                $this->logPermissionChange(
                    $removed_by,
                    $user_id,
                    $company_id,
                    $module_id,
                    'permissions_removed',
                    json_encode($permissions),
                    null
                );
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener usuarios con permisos en una empresa
     */
    public function getCompanyUsers($company_id) {
        $stmt = $this->mysqli->prepare("
            SELECT DISTINCT
                ump.user_id,
                u.nombre as user_name,
                u.email as user_email,
                u.role as user_role,
                COUNT(ump.module_id) as modules_count,
                MAX(ump.updated_at) as last_updated
            FROM user_module_permissions ump
            LEFT JOIN usuarios u ON ump.user_id = u.id
            WHERE ump.company_id = ?
            GROUP BY ump.user_id
            ORDER BY u.nombre
        ");
        
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtener módulos disponibles
     */
    public function getSystemModules() {
        $result = $this->mysqli->query("
            SELECT * FROM system_modules 
            WHERE is_active = 1 
            ORDER BY sort_order
        ");
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtener plantillas de roles disponibles
     */
    public function getRoleTemplates() {
        $result = $this->mysqli->query("
            SELECT 
                role_name,
                COUNT(*) as modules_count,
                GROUP_CONCAT(module_id) as modules
            FROM role_permission_templates 
            GROUP BY role_name
            ORDER BY role_name
        ");
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Registrar cambios en el log de auditoría
     */
    private function logPermissionChange($user_id, $target_user_id, $company_id, $module_id, $action, $old_permissions, $new_permissions) {
        $stmt = $this->mysqli->prepare("
            INSERT INTO permission_audit_log 
            (user_id, target_user_id, company_id, module_id, action, old_permissions, new_permissions, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->bind_param("iiisissss", 
            $user_id, 
            $target_user_id, 
            $company_id, 
            $module_id, 
            $action, 
            $old_permissions, 
            $new_permissions,
            $ip_address,
            $user_agent
        );
        
        $stmt->execute();
    }
    
    /**
     * Verificar si el usuario actual puede administrar permisos
     */
    public function canManagePermissions($user_id, $company_id) {
        return $this->hasPermission($user_id, $company_id, 'users', 'can_admin') ||
               $this->hasPermission($user_id, $company_id, 'companies', 'can_admin');
    }
    
    /**
     * Obtener histórico de cambios de permisos
     */
    public function getPermissionHistory($user_id = null, $company_id = null, $limit = 50) {
        $where_conditions = [];
        $params = [];
        $types = "";
        
        if ($user_id) {
            $where_conditions[] = "target_user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }
        
        if ($company_id) {
            $where_conditions[] = "company_id = ?";
            $params[] = $company_id;
            $types .= "i";
        }
        
        $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $sql = "
            SELECT 
                pal.*,
                u1.nombre as user_name,
                u2.nombre as target_user_name,
                sm.name as module_name
            FROM permission_audit_log pal
            LEFT JOIN usuarios u1 ON pal.user_id = u1.id
            LEFT JOIN usuarios u2 ON pal.target_user_id = u2.id
            LEFT JOIN system_modules sm ON pal.module_id = sm.id
            $where_clause
            ORDER BY pal.created_at DESC
            LIMIT $limit
        ";
        
        if ($params) {
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->mysqli->query($sql);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

/**
 * Funciones helper para uso global
 */

/**
 * Verificar permiso rápido (función global)
 */
function checkPermission($user_id, $company_id, $module_id, $permission_type) {
    global $mysqli;
    
    if (!$mysqli) {
        return false;
    }
    
    $manager = new PermissionsManager($mysqli, $user_id, $company_id);
    return $manager->hasPermission($user_id, $company_id, $module_id, $permission_type);
}

/**
 * Middleware de verificación de permisos
 */
function requirePermission($module_id, $permission_type, $redirect_url = 'acceso_denegado.php') {
    session_start();
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
        header("Location: ../auth/index.php");
        exit;
    }
    
    if (!checkPermission($_SESSION['user_id'], $_SESSION['company_id'], $module_id, $permission_type)) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Generar botones de acción basados en permisos
 */
function generateActionButtons($user_id, $company_id, $module_id, $record_id = null) {
    $buttons = [];
    
    if (checkPermission($user_id, $company_id, $module_id, 'can_view')) {
        $buttons[] = "<button class='btn btn-sm btn-info' onclick='viewRecord($record_id)'><i class='fas fa-eye'></i></button>";
    }
    
    if (checkPermission($user_id, $company_id, $module_id, 'can_edit')) {
        $buttons[] = "<button class='btn btn-sm btn-warning' onclick='editRecord($record_id)'><i class='fas fa-edit'></i></button>";
    }
    
    if (checkPermission($user_id, $company_id, $module_id, 'can_delete')) {
        $buttons[] = "<button class='btn btn-sm btn-danger' onclick='deleteRecord($record_id)'><i class='fas fa-trash'></i></button>";
    }
    
    return implode(' ', $buttons);
}

?>
