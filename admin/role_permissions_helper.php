<?php
/**
 * Sistema de validación de roles y permisos
 * Basado en la jerarquía definida en README.md
 */

/**
 * Jerarquía de roles del sistema:
 * 
 * root: Acceso total al sistema SaaS. Administra empresas, usuarios y planes.
 * support: Soporte técnico limitado. No puede modificar cuentas ni empresas.
 * superadmin: Propietario de empresas. Controla unidades, usuarios y módulos.
 * admin: Administra una unidad o negocio dentro de una empresa.
 * moderator: Gerente de operación local. Supervisa tareas y registros.
 * user: Usuario operativo. Accede según permisos del sistema.
 */

/**
 * Validar si un usuario puede gestionar otra empresa
 */
function canManageCompany($user_id, $company_id, $user_role) {
    global $pdo;
    
    // Solo root puede gestionar cualquier empresa
    if ($user_role === 'root') {
        return true;
    }
    
    // Superadmin y admin solo pueden gestionar empresas donde están asignados
    if (in_array($user_role, ['superadmin', 'admin'])) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM user_companies 
            WHERE user_id = ? AND company_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id, $company_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    return false;
}

/**
 * Validar si un usuario puede gestionar otros usuarios
 */
function canManageUsers($user_role, $target_role = null) {
    $hierarchy = [
        'root' => 6,
        'support' => 1,
        'superadmin' => 5,
        'admin' => 4,
        'moderator' => 3,
        'user' => 2
    ];
    
    $user_level = $hierarchy[$user_role] ?? 0;
    $target_level = $target_role ? ($hierarchy[$target_role] ?? 0) : 0;
    
    // Root puede gestionar a todos
    if ($user_role === 'root') {
        return true;
    }
    
    // Support no puede gestionar usuarios
    if ($user_role === 'support') {
        return false;
    }
    
    // Superadmin puede gestionar admin, moderator, user pero no otros superadmin ni root
    if ($user_role === 'superadmin') {
        return in_array($target_role, ['admin', 'moderator', 'user', null]);
    }
    
    // Admin puede gestionar moderator, user pero no admin, superadmin ni root
    if ($user_role === 'admin') {
        return in_array($target_role, ['moderator', 'user', null]);
    }
    
    // Moderator no puede gestionar usuarios directamente
    if ($user_role === 'moderator') {
        return false;
    }
    
    return false;
}

/**
 * Obtener empresas que un usuario puede gestionar
 */
function getUserManagedCompanies($user_id, $user_role) {
    global $pdo;
    
    // Root puede ver todas las empresas
    if ($user_role === 'root') {
        $stmt = $pdo->prepare("
            SELECT id, name, description 
            FROM companies 
            WHERE status = 'active' 
            ORDER BY name
        ");
        $stmt->execute();
    } else {
        // Otros roles solo ven empresas donde están asignados
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.name, c.description 
            FROM companies c
            INNER JOIN user_companies uc ON c.id = uc.company_id
            WHERE c.status = 'active' 
            AND uc.user_id = ? 
            AND uc.status = 'active'
            ORDER BY c.name
        ");
        $stmt->execute([$user_id]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Validar permisos para acciones específicas
 */
function hasActionPermission($user_role, $action, $context = []) {
    switch ($action) {
        case 'create_company':
            return $user_role === 'root';
            
        case 'delete_company':
            return $user_role === 'root';
            
        case 'manage_company_users':
            return in_array($user_role, ['root', 'superadmin', 'admin']);
            
        case 'assign_superadmin_role':
            return $user_role === 'root';
            
        case 'assign_admin_role':
            return in_array($user_role, ['root', 'superadmin']);
            
        case 'manage_permissions':
            return in_array($user_role, ['root', 'superadmin', 'admin']);
            
        case 'view_all_companies':
            return $user_role === 'root';
            
        default:
            return false;
    }
}

/**
 * Mensaje de error según el contexto de permisos
 */
function getPermissionErrorMessage($user_role, $attempted_action) {
    $messages = [
        'root' => 'Acceso denegado: Se requieren permisos de Root',
        'superadmin' => 'Acceso denegado: Solo el propietario de la empresa puede realizar esta acción',
        'admin' => 'Acceso denegado: Se requieren permisos de administrador de empresa',
        'insufficient' => 'Permisos insuficientes para realizar esta acción'
    ];
    
    return $messages['insufficient'];
}
?>
