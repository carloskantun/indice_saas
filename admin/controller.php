<?php
/**
 * Controller para gestión de usuarios admin
 * Maneja invitaciones, roles y permisos de usuarios
 */

require_once '../config.php';
require_once 'permissions_manager.php';
require_once 'db_connection.php';
// email_config.php ya está incluido desde config.php

// Verificar autenticación y permisos de administración
if (!checkRole(['root', 'superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $lang['insufficient_permissions']]);
    exit();
}

// Verificar empresa activa - requerido para admin, opcional para root/superadmin
if (!checkRole(['root', 'superadmin'])) {
    if (!isset($_SESSION['current_company_id']) || empty($_SESSION['current_company_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $lang['no_active_company']]);
        exit();
    }
}

$pdo = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'send_invitation':
        sendInvitation();
        break;
    case 'accept_invitation':
        acceptInvitation();
        break;
    case 'update_user_role':
        updateUserRole();
        break;
    case 'get_users_by_company':
        getUsersByCompany();
        break;
    case 'suspend_user':
        suspendUser();
        break;
    case 'activate_user':
        activateUser();
        break;
    case 'resend_invitation':
        resendInvitation();
        break;
    case 'delete_invitation':
        deleteInvitation();
        break;
    case 'get_units_by_company':
        getUnitsByCompany();
        break;
    case 'get_businesses_by_unit':
        getBusinessesByUnit();
        break;
    case 'get_pending_invitations':
        getPendingInvitations();
        break;
    case 'load_users':
        loadUsers();
        break;
    case 'update_role':
        updateUserRole();
        break;
    case 'assign_unit':
        assignUserUnit();
        break;
    case 'toggle_access':
        toggleUserAccess();
        break;
    case 'load_permissions':
        loadUserPermissions();
        break;
    case 'update_permissions':
        updateUserPermissions();
        break;
    case 'get_modules':
        getSystemModules();
        break;
    case 'apply_role_template':
        applyRoleTemplate();
        break;
    case 'get_permission_matrix':
        getPermissionMatrix();
        break;
    case 'get_companies':
        getCompaniesForPermissions();
        break;
    case 'get_modules':
        getSystemModulesForPermissions();
        break;
    case 'get_permission_matrix':
        getPermissionMatrixForCompany();
        break;
    case 'apply_role_template':
        applyRoleTemplateToUser();
        break;
    case 'bulk_update_permissions':
        bulkUpdateUserPermissions();
        break;
    case 'install_role_templates':
        installRoleTemplates();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Enviar invitación a nuevo usuario
 */
function sendInvitation() {
    global $pdo, $lang;
    
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $unit_id = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $business_id = !empty($_POST['business_id']) ? intval($_POST['business_id']) : null;
    
    // Para root/superadmin, la empresa puede ser especificada o usar la sesión
    if (checkRole(['root', 'superadmin']) && isset($_POST['company_id']) && !empty($_POST['company_id'])) {
        $company_id = intval($_POST['company_id']);
    } else {
        $company_id = intval($_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? 0);
    }
    
    if ($company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar una empresa']);
        return;
    }
    
    $sent_by = intval($_SESSION['user_id']);
    
    // Validaciones
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => $lang['invalid_email']]);
        return;
    }
    
    if (!in_array($role, ['superadmin', 'admin', 'moderator', 'user'])) {
        echo json_encode(['success' => false, 'message' => 'Rol no válido']);
        return;
    }
    
    // Solo superadmin/root puede asignar rol superadmin
    if ($role === 'superadmin' && !checkRole(['root', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => $lang['insufficient_permissions']]);
        return;
    }
    
    try {
        // Verificar si el usuario ya está registrado y en esta empresa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            // Verificar si ya está en esta empresa
            $stmt = $pdo->prepare("
                SELECT id FROM user_companies 
                WHERE user_id = ? AND company_id = ?
            ");
            $stmt->execute([$existing_user['id'], $company_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El usuario ya pertenece a esta empresa']);
                return;
            }
        }
        
        // Verificar si ya existe una invitación pendiente
        $stmt = $pdo->prepare("
            SELECT id FROM user_invitations 
            WHERE email = ? AND company_id = ? AND status = 'pending' 
            AND expiration_date > NOW()
        ");
        $stmt->execute([$email, $company_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => $lang['email_already_invited']]);
            return;
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        
        // Insertar invitación
        $stmt = $pdo->prepare("
            INSERT INTO user_invitations (email, company_id, unit_id, business_id, role, token, sent_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$email, $company_id, $unit_id, $business_id, $role, $token, $sent_by]);
        
        // Obtener nombre de la empresa para el email
        $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch();
        $company_name = $company ? $company['name'] : 'Índice Producción';
        
        // Enviar correo electrónico de invitación
        if (function_exists('sendInvitationEmail')) {
            sendInvitationEmail($email, $token, $role, $company_name);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $lang['invitation_sent']
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Aceptar invitación
 */
function acceptInvitation() {
    global $pdo, $lang;
    
    $token = $_POST['token'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token requerido']);
        return;
    }
    
    try {
        // Verificar token válido
        $stmt = $pdo->prepare("
            SELECT * FROM user_invitations 
            WHERE token = ? AND status = 'pending' AND expiration_date > NOW()
        ");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            echo json_encode(['success' => false, 'message' => $lang['invitation_not_found']]);
            return;
        }
        
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$invitation['email']]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            // Usuario ya existe - solo agregar a la empresa
            $user_id = $existing_user['id'];
            $user_name = $existing_user['name'];
            
            // Verificar si ya está en esta empresa (doble verificación)
            $stmt = $pdo->prepare("
                SELECT id FROM user_companies 
                WHERE user_id = ? AND company_id = ?
            ");
            $stmt->execute([$user_id, $invitation['company_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ya perteneces a esta empresa']);
                return;
            }
        } else {
            // Usuario nuevo - requiere nombre y contraseña
            if (empty($name) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Nombre y contraseña requeridos para usuarios nuevos']);
                return;
            }
            
            // Crear nuevo usuario
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, status, created_at) 
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$name, $invitation['email'], $hashedPassword]);
            $user_id = $pdo->lastInsertId();
            $user_name = $name;
        }
        
        // Asignar a empresa
        $stmt = $pdo->prepare("
            INSERT INTO user_companies (user_id, company_id, role, status) 
            VALUES (?, ?, ?, 'active')
        ");
        $stmt->execute([$user_id, $invitation['company_id'], $invitation['role']]);
        
        // Asignar a unidad si corresponde
        if ($invitation['unit_id']) {
            $stmt = $pdo->prepare("
                INSERT INTO user_units (user_id, unit_id, role, status) 
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$user_id, $invitation['unit_id'], $invitation['role']]);
        }
        
        // Asignar a negocio si corresponde
        if ($invitation['business_id']) {
            $stmt = $pdo->prepare("
                INSERT INTO user_businesses (user_id, business_id, role, status) 
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$user_id, $invitation['business_id'], $invitation['role']]);
        }
        
        // Marcar invitación como aceptada
        $stmt = $pdo->prepare("UPDATE user_invitations SET status = 'accepted', accepted_date = NOW() WHERE token = ?");
        $stmt->execute([$token]);
        
        $message = $existing_user ? 
            "¡Bienvenido de vuelta, $user_name! Has sido agregado a la nueva empresa." :
            "¡Cuenta creada exitosamente! Bienvenido, $user_name.";
            
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Actualizar rol de usuario
 */
function updateUserRole() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_role = $_POST['new_role'] ?? '';
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? 0);
    
    if (!$user_id || !in_array($new_role, ['superadmin', 'admin', 'moderator', 'user'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }
    
    // Solo superadmin/root puede asignar rol superadmin
    if ($new_role === 'superadmin' && !checkRole(['root', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => $lang['insufficient_permissions']]);
        return;
    }
    
    try {
        // Verificar que el usuario pertenece a la empresa
        $stmt = $pdo->prepare("
            SELECT id FROM user_companies 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$user_id, $company_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => $lang['user_not_found']]);
            return;
        }
        
        // Actualizar rol
        $stmt = $pdo->prepare("
            UPDATE user_companies 
            SET role = ?, updated_at = NOW() 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$new_role, $user_id, $company_id]);
        
        echo json_encode(['success' => true, 'message' => $lang['role_updated']]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener usuarios por empresa
 */
function getUsersByCompany() {
    global $pdo, $lang;
    
    $company_id = intval($_GET['company_id'] ?? $_POST['company_id'] ?? $_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? 0);
    
    if ($company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa requerido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.name, u.email, u.status as user_status,
                uc.role, uc.status as company_status, uc.created_at as joined_date,
                c.name as company_name
            FROM users u
            INNER JOIN user_companies uc ON u.id = uc.user_id
            INNER JOIN companies c ON uc.company_id = c.id
            WHERE uc.company_id = ?
            ORDER BY uc.role DESC, u.name ASC
        ");
        $stmt->execute([$company_id]);
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'users' => $users]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Suspender usuario
 */
function suspendUser() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $company_id = intval($_SESSION['current_company_id']);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_companies 
            SET status = 'suspended', updated_at = NOW() 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$user_id, $company_id]);
        
        echo json_encode(['success' => true, 'message' => $lang['user_suspended']]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Activar usuario
 */
function activateUser() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $company_id = intval($_SESSION['current_company_id']);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_companies 
            SET status = 'active', updated_at = NOW() 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$user_id, $company_id]);
        
        echo json_encode(['success' => true, 'message' => $lang['user_activated']]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Reenviar invitación
 */
function resendInvitation() {
    global $pdo, $lang;
    
    $invitation_id = intval($_POST['invitation_id'] ?? 0);
    $company_id = intval($_SESSION['current_company_id']);
    
    try {
        // Verificar que la invitación pertenece a la empresa
        $stmt = $pdo->prepare("
            SELECT * FROM user_invitations 
            WHERE id = ? AND company_id = ? AND status = 'pending'
        ");
        $stmt->execute([$invitation_id, $company_id]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            echo json_encode(['success' => false, 'message' => $lang['invitation_not_found']]);
            return;
        }
        
        // Generar nuevo token y extender expiración
        $new_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("
            UPDATE user_invitations 
            SET token = ?, sent_date = NOW(), expiration_date = DATE_ADD(NOW(), INTERVAL 48 HOUR)
            WHERE id = ?
        ");
        $stmt->execute([$new_token, $invitation_id]);
        
        // Obtener nombre de la empresa para el email
        $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch();
        $company_name = $company ? $company['name'] : 'Índice Producción';
        
        // Reenviar correo electrónico de invitación
        if (function_exists('sendInvitationEmail')) {
            sendInvitationEmail($invitation['email'], $new_token, $invitation['rol'], $company_name);
        }
        
        echo json_encode(['success' => true, 'message' => $lang['invitation_resent']]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Eliminar invitación
 */
function deleteInvitation() {
    global $pdo, $lang;
    
    $invitation_id = intval($_POST['invitation_id'] ?? 0);
    $company_id = intval($_SESSION['current_company_id']);
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM user_invitations 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$invitation_id, $company_id]);
        
        echo json_encode(['success' => true, 'message' => 'Invitación eliminada']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener unidades por empresa
 */
function getUnitsByCompany() {
    global $pdo;
    
    $company_id = intval($_SESSION['current_company_id']);
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name FROM units 
            WHERE company_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->execute([$company_id]);
        $units = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'units' => $units]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener negocios por unidad
 */
function getBusinessesByUnit() {
    global $pdo;
    
    $unit_id = intval($_GET['unit_id'] ?? 0);
    
    if (!$unit_id) {
        echo json_encode(['success' => false, 'message' => 'ID de unidad requerido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name FROM businesses 
            WHERE unit_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->execute([$unit_id]);
        $businesses = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'businesses' => $businesses]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener invitaciones pendientes
 */
function getPendingInvitations() {
    global $pdo;
    
    $company_id = intval($_SESSION['current_company_id']);
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                i.*, 
                u.name as sent_by_name,
                un.name as unit_name,
                b.name as business_name
            FROM user_invitations i
            LEFT JOIN users u ON i.sent_by = u.id
            LEFT JOIN units un ON i.unit_id = un.id
            LEFT JOIN businesses b ON i.business_id = b.id
            WHERE i.company_id = ?
            ORDER BY i.sent_date DESC
        ");
        $stmt->execute([$company_id]);
        $invitations = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'invitations' => $invitations]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Cargar usuarios de la empresa con información completa
 */
function loadUsers() {
    global $pdo, $lang;
    
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    
    if ($company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                uc.role,
                uc.status,
                uc.last_accessed,
                uc.created_at as joined_date,
                uc.unit_id,
                uc.business_id,
                ut.name as unit_name,
                b.name as business_name,
                CASE 
                    WHEN ui.status = 'pending' THEN 'pending'
                    WHEN uc.status = 'active' THEN 'active'
                    ELSE 'inactive'
                END as access_status
            FROM user_companies uc
            INNER JOIN users u ON uc.user_id = u.id
            LEFT JOIN units ut ON uc.unit_id = ut.id
            LEFT JOIN businesses b ON uc.business_id = b.id
            LEFT JOIN user_invitations ui ON ui.email = u.email AND ui.company_id = uc.company_id AND ui.status = 'pending'
            WHERE uc.company_id = ?
            ORDER BY 
                CASE uc.role
                    WHEN 'superadmin' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'moderator' THEN 3
                    ELSE 4
                END,
                u.name
        ");
        $stmt->execute([$company_id]);
        $users = $stmt->fetchAll();
        
        $html = '';
        foreach ($users as $user) {
            // Role class
            switch($user['role']) {
                case 'superadmin':
                    $roleClass = 'bg-primary';
                    break;
                case 'admin':
                    $roleClass = 'bg-success';
                    break;
                case 'moderator':
                    $roleClass = 'bg-warning';
                    break;
                default:
                    $roleClass = 'bg-secondary';
            }
            
            // Status class
            switch($user['access_status']) {
                case 'active':
                    $statusClass = 'bg-success';
                    break;
                case 'pending':
                    $statusClass = 'bg-warning';
                    break;
                default:
                    $statusClass = 'bg-danger';
            }
            
            $lastAccess = $user['last_accessed'] ? 
                date('d/m/Y H:i', strtotime($user['last_accessed'])) : 
                '<span class="text-muted">' . $lang['never_accessed'] . '</span>';
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($user['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['email']) . '</td>';
            $html .= '<td><span class="badge ' . $roleClass . '">' . ucfirst($user['role']) . '</span></td>';
            $html .= '<td>' . ($user['unit_name'] ?: '<span class="text-muted">' . $lang['no_unit_assigned'] . '</span>') . '</td>';
            $html .= '<td>' . ($user['business_name'] ?: '<span class="text-muted">' . $lang['no_business_assigned'] . '</span>') . '</td>';
            $html .= '<td><span class="badge ' . $statusClass . '">' . ucfirst($user['access_status']) . '</span></td>';
            $html .= '<td>' . $lastAccess . '</td>';
            $html .= '<td>';
            $html .= '<div class="btn-group btn-group-sm">';
            $html .= '<button class="btn btn-outline-primary" onclick="editUser(' . $user['id'] . ')" title="Editar">';
            $html .= '<i class="fas fa-edit"></i></button>';
            if ($user['access_status'] === 'active') {
                $html .= '<button class="btn btn-outline-warning" onclick="toggleAccess(' . $user['id'] . ', false)" title="Deshabilitar">';
                $html .= '<i class="fas fa-user-slash"></i></button>';
            } else {
                $html .= '<button class="btn btn-outline-success" onclick="toggleAccess(' . $user['id'] . ', true)" title="Habilitar">';
                $html .= '<i class="fas fa-user-check"></i></button>';
            }
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Asignar unidad a un usuario
 */
function assignUserUnit() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $unit_id = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $business_id = !empty($_POST['business_id']) ? intval($_POST['business_id']) : null;
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    
    if ($user_id <= 0 || $company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_companies 
            SET unit_id = ?, business_id = ?, updated_at = NOW()
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$unit_id, $business_id, $user_id, $company_id]);
        
        echo json_encode(['success' => true, 'message' => 'Asignación actualizada exitosamente']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Activar/desactivar acceso de usuario
 */
function toggleUserAccess() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $enable = filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN);
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    
    if ($user_id <= 0 || $company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    $newStatus = $enable ? 'active' : 'inactive';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_companies 
            SET status = ?, updated_at = NOW()
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$newStatus, $user_id, $company_id]);
        
        $message = $enable ? $lang['user_activated'] : $lang['user_suspended'];
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Cargar permisos de usuario por módulo
 */
function loadUserPermissions() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    
    if ($user_id <= 0 || $company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    try {
        // Obtener todos los módulos y permisos del usuario
        $stmt = $pdo->prepare("
            SELECT 
                m.id as module_id,
                m.name as module_name,
                m.description,
                m.icon,
                m.category,
                COALESCE(ump.can_view, rpt.can_view, 0) as can_view,
                COALESCE(ump.can_create, rpt.can_create, 0) as can_create,
                COALESCE(ump.can_edit, rpt.can_edit, 0) as can_edit,
                COALESCE(ump.can_delete, rpt.can_delete, 0) as can_delete,
                COALESCE(ump.can_admin, rpt.can_admin, 0) as can_admin,
                uc.role,
                ump.id as permission_id,
                CASE WHEN ump.id IS NOT NULL THEN 'custom' ELSE 'template' END as source
            FROM system_modules m
            INNER JOIN user_companies uc ON uc.company_id = ?
            LEFT JOIN user_module_permissions ump ON ump.user_id = ? AND ump.company_id = ? AND ump.module_id = m.id
            LEFT JOIN role_permission_templates rpt ON rpt.role = uc.role AND rpt.module_id = m.id
            WHERE uc.user_id = ? AND m.is_active = 1
            ORDER BY m.category, m.sort_order, m.name
        ");
        $stmt->execute([$company_id, $user_id, $company_id, $user_id]);
        $permissions = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'permissions' => $permissions]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Actualizar permisos de usuario
 */
function updateUserPermissions() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    $module_id = $_POST['module_id'] ?? '';
    $permissions = json_decode($_POST['permissions'] ?? '{}', true);
    $granted_by = intval($_SESSION['user_id']);
    
    if ($user_id <= 0 || $company_id <= 0 || empty($module_id)) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verificar si ya existe un registro de permisos personalizado
        $stmt = $pdo->prepare("
            SELECT id, can_view, can_create, can_edit, can_delete, can_admin
            FROM user_module_permissions 
            WHERE user_id = ? AND company_id = ? AND module_id = ?
        ");
        $stmt->execute([$user_id, $company_id, $module_id]);
        $existing = $stmt->fetch();
        
        $can_view = intval($permissions['can_view'] ?? 0);
        $can_create = intval($permissions['can_create'] ?? 0);
        $can_edit = intval($permissions['can_edit'] ?? 0);
        $can_delete = intval($permissions['can_delete'] ?? 0);
        $can_admin = intval($permissions['can_admin'] ?? 0);
        
        if ($existing) {
            // Actualizar permisos existentes
            $stmt = $pdo->prepare("
                UPDATE user_module_permissions 
                SET can_view = ?, can_create = ?, can_edit = ?, can_delete = ?, can_admin = ?, 
                    granted_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$can_view, $can_create, $can_edit, $can_delete, $can_admin, $granted_by, $existing['id']]);
            
            // Registrar cambios en auditoría
            logPermissionChanges($user_id, $company_id, $module_id, $existing, $permissions, $granted_by, 'updated');
        } else {
            // Crear nuevos permisos personalizados
            $stmt = $pdo->prepare("
                INSERT INTO user_module_permissions 
                (user_id, company_id, module_id, can_view, can_create, can_edit, can_delete, can_admin, granted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $company_id, $module_id, $can_view, $can_create, $can_edit, $can_delete, $can_admin, $granted_by]);
            
            // Registrar en auditoría
            logPermissionChanges($user_id, $company_id, $module_id, [], $permissions, $granted_by, 'granted');
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Permisos actualizados exitosamente']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener módulos del sistema
 */
function getSystemModules() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, description, icon, category, sort_order
            FROM system_modules 
            WHERE is_active = 1 
            ORDER BY category, sort_order, name
        ");
        $stmt->execute();
        $modules = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'modules' => $modules]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Aplicar plantilla de permisos por rol
 */
function applyRoleTemplate() {
    global $pdo, $lang;
    
    $user_id = intval($_POST['user_id'] ?? 0);
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $granted_by = intval($_SESSION['user_id']);
    
    if ($user_id <= 0 || $company_id <= 0 || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Limpiar permisos personalizados existentes
        $stmt = $pdo->prepare("DELETE FROM user_module_permissions WHERE user_id = ? AND company_id = ?");
        $stmt->execute([$user_id, $company_id]);
        
        // Obtener plantilla de permisos del rol
        $stmt = $pdo->prepare("
            SELECT module_id, can_view, can_create, can_edit, can_delete, can_admin
            FROM role_permission_templates 
            WHERE role = ?
        ");
        $stmt->execute([$role]);
        $templates = $stmt->fetchAll();
        
        // Aplicar permisos de la plantilla
        foreach ($templates as $template) {
            $stmt = $pdo->prepare("
                INSERT INTO user_module_permissions 
                (user_id, company_id, module_id, can_view, can_create, can_edit, can_delete, can_admin, granted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $company_id, $template['module_id'],
                $template['can_view'], $template['can_create'], $template['can_edit'],
                $template['can_delete'], $template['can_admin'], $granted_by
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Plantilla de permisos '$role' aplicada exitosamente"]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener matriz de permisos de la empresa
 */
function getPermissionMatrix() {
    global $pdo;
    
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    
    if ($company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.id as user_id,
                u.name as user_name,
                u.email,
                uc.role,
                m.id as module_id,
                m.name as module_name,
                m.category,
                COALESCE(ump.can_view, rpt.can_view, 0) as can_view,
                COALESCE(ump.can_create, rpt.can_create, 0) as can_create,
                COALESCE(ump.can_edit, rpt.can_edit, 0) as can_edit,
                COALESCE(ump.can_delete, rpt.can_delete, 0) as can_delete,
                COALESCE(ump.can_admin, rpt.can_admin, 0) as can_admin
            FROM user_companies uc
            INNER JOIN users u ON uc.user_id = u.id
            CROSS JOIN system_modules m
            LEFT JOIN user_module_permissions ump ON ump.user_id = u.id AND ump.company_id = uc.company_id AND ump.module_id = m.id
            LEFT JOIN role_permission_templates rpt ON rpt.role = uc.role AND rpt.module_id = m.id
            WHERE uc.company_id = ? AND uc.status = 'active' AND m.is_active = 1
            ORDER BY uc.role, u.name, m.category, m.sort_order
        ");
        $stmt->execute([$company_id]);
        $matrix = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'matrix' => $matrix]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Actualización masiva de permisos
 */
function bulkUpdatePermissions() {
    global $pdo, $lang;
    
    $updates = json_decode($_POST['updates'] ?? '[]', true);
    $company_id = intval($_POST['company_id'] ?? $_SESSION['current_company_id'] ?? 0);
    $granted_by = intval($_SESSION['user_id']);
    
    if (empty($updates) || $company_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        foreach ($updates as $update) {
            $user_id = intval($update['user_id']);
            $module_id = $update['module_id'];
            $permissions = $update['permissions'];
            
            // Verificar si existe registro
            $stmt = $pdo->prepare("
                SELECT id FROM user_module_permissions 
                WHERE user_id = ? AND company_id = ? AND module_id = ?
            ");
            $stmt->execute([$user_id, $company_id, $module_id]);
            $existing = $stmt->fetch();
            
            $can_view = intval($permissions['can_view'] ?? 0);
            $can_create = intval($permissions['can_create'] ?? 0);
            $can_edit = intval($permissions['can_edit'] ?? 0);
            $can_delete = intval($permissions['can_delete'] ?? 0);
            $can_admin = intval($permissions['can_admin'] ?? 0);
            
            if ($existing) {
                // Actualizar existente
                $stmt = $pdo->prepare("
                    UPDATE user_module_permissions 
                    SET can_view = ?, can_create = ?, can_edit = ?, can_delete = ?, can_admin = ?, 
                        granted_by = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$can_view, $can_create, $can_edit, $can_delete, $can_admin, $granted_by, $existing['id']]);
            } else {
                // Crear nuevo
                $stmt = $pdo->prepare("
                    INSERT INTO user_module_permissions 
                    (user_id, company_id, module_id, can_view, can_create, can_edit, can_delete, can_admin, granted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $company_id, $module_id, $can_view, $can_create, $can_edit, $can_delete, $can_admin, $granted_by]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => count($updates) . ' permisos actualizados exitosamente']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Registrar cambios de permisos en auditoría
 */
function logPermissionChanges($user_id, $company_id, $module_id, $old_permissions, $new_permissions, $changed_by, $action) {
    global $pdo;
    
    try {
        $permission_types = ['can_view' => 'view', 'can_create' => 'create', 'can_edit' => 'edit', 'can_delete' => 'delete', 'can_admin' => 'admin'];
        
        foreach ($permission_types as $perm_key => $perm_type) {
            $old_value = isset($old_permissions[$perm_key]) ? intval($old_permissions[$perm_key]) : 0;
            $new_value = isset($new_permissions[$perm_key]) ? intval($new_permissions[$perm_key]) : 0;
            
            if ($old_value !== $new_value) {
                $stmt = $pdo->prepare("
                    INSERT INTO permission_audit_log 
                    (user_id, company_id, module_id, action, permission_type, old_value, new_value, changed_by, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, $company_id, $module_id, $action, $perm_type, 
                    $old_value, $new_value, $changed_by, 
                    $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            }
        }
    } catch (PDOException $e) {
        // Log error but don't fail the main operation
        error_log("Error logging permission changes: " . $e->getMessage());
    }
}

/**
 * Obtener empresas para el sistema de permisos
 */
function getCompaniesForPermissions() {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['current_role'] ?? 'user';
        
        // Solo usuarios root pueden ver todas las empresas del sistema
        if ($user_role === 'root') {
            $stmt = $pdo->prepare("SELECT id, name, description FROM companies WHERE status = 'active' ORDER BY name");
            $stmt->execute();
        } else {
            // Superadmin, admin y otros usuarios solo ven las empresas donde tienen permisos
            // Superadmin solo ve empresas que él creó o donde fue asignado
            $stmt = $pdo->prepare("
                SELECT DISTINCT c.id, c.name, c.description 
                FROM companies c
                INNER JOIN user_companies uc ON c.id = uc.company_id
                WHERE c.status = 'active' AND uc.user_id = ? AND uc.status = 'active'
                ORDER BY c.name
            ");
            $stmt->execute([$user_id]);
        }
        
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'companies' => $companies]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtener módulos del sistema para permisos
 */
function getSystemModulesForPermissions() {
    try {
        $mysqli = createPermissionsConnection();
        $permissions_manager = new PermissionsManager($mysqli);
        
        $modules = $permissions_manager->getSystemModules();
        
        $mysqli->close();
        
        echo json_encode(['success' => true, 'modules' => $modules]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtener matriz de permisos para una empresa
 */
function getPermissionMatrixForCompany() {
    try {
        $company_id = $_GET['company_id'] ?? '';
        
        if (!$company_id) {
            throw new Exception('ID de empresa requerido');
        }
        
        $mysqli = createPermissionsConnection();
        $permissions_manager = new PermissionsManager($mysqli);
        
        $users = $permissions_manager->getCompanyUsers($company_id);
        $modules = $permissions_manager->getSystemModules();
        
        // Obtener permisos detallados para cada usuario
        $matrix = [];
        foreach ($users as $user) {
            $user_permissions = $permissions_manager->getUserPermissions($user['user_id'], $company_id);
            $matrix[$user['user_id']] = [
                'user_info' => $user,
                'permissions' => $user_permissions
            ];
        }
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'matrix' => $matrix,
            'modules' => $modules
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Aplicar plantilla de rol a un usuario
 */
function applyRoleTemplateToUser() {
    try {
        $user_id = $_POST['user_id'] ?? '';
        $company_id = $_POST['company_id'] ?? '';
        $role_name = $_POST['role_name'] ?? '';
        
        if (!$user_id || !$company_id || !$role_name) {
            throw new Exception('Parámetros faltantes');
        }
        
        $mysqli = createPermissionsConnection();
        $permissions_manager = new PermissionsManager($mysqli, $_SESSION['user_id'], $company_id);
        
        $assigned_count = $permissions_manager->assignRoleToUser(
            $user_id, 
            $company_id, 
            $role_name, 
            $_SESSION['user_id']
        );
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true, 
            'message' => "Rol '$role_name' aplicado exitosamente. $assigned_count permisos asignados."
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Actualización masiva de permisos
 */
function bulkUpdateUserPermissions() {
    try {
        $company_id = $_POST['company_id'] ?? '';
        $updates = $_POST['updates'] ?? [];
        
        if (!$company_id || empty($updates)) {
            throw new Exception('Parámetros faltantes');
        }
        
        $mysqli = createPermissionsConnection();
        $permissions_manager = new PermissionsManager($mysqli, $_SESSION['user_id'], $company_id);
        
        $success_count = 0;
        $errors = [];
        
        foreach ($updates as $update) {
            $user_id = $update['user_id'] ?? '';
            $module_id = $update['module_id'] ?? '';
            $permissions = $update['permissions'] ?? [];
            
            if ($user_id && $module_id) {
                $result = $permissions_manager->updateUserPermissions(
                    $user_id, 
                    $company_id, 
                    $module_id, 
                    $permissions, 
                    $_SESSION['user_id']
                );
                
                if ($result) {
                    $success_count++;
                } else {
                    $errors[] = "Error actualizando usuario $user_id, módulo $module_id";
                }
            }
        }
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'updated_count' => $success_count,
            'errors' => $errors,
            'message' => "Actualización completada: $success_count permisos actualizados"
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Instalar plantillas de roles faltantes
 */
function installRoleTemplates() {
    try {
        $mysqli = createPermissionsConnection();
        
        // Verificar si ya existen plantillas
        $check = $mysqli->query("SELECT COUNT(*) as count FROM role_permission_templates");
        $existing_count = $check->fetch_assoc()['count'];
        
        if ($existing_count > 0) {
            // Limpiar plantillas existentes para reinstalar
            $mysqli->query("DELETE FROM role_permission_templates");
        }
        
        // Plantillas de roles
        $role_templates = [
            // SuperAdmin - Acceso total
            ['superadmin', 'orders', 1, 1, 1, 1, 1],
            ['superadmin', 'maintenance', 1, 1, 1, 1, 1],
            ['superadmin', 'customer_service', 1, 1, 1, 1, 1],
            ['superadmin', 'expenses', 1, 1, 1, 1, 1],
            ['superadmin', 'transfers', 1, 1, 1, 1, 1],
            ['superadmin', 'reports', 1, 1, 1, 1, 1],
            ['superadmin', 'users', 1, 1, 1, 1, 1],
            ['superadmin', 'companies', 1, 1, 1, 1, 1],
            ['superadmin', 'settings', 1, 1, 1, 1, 1],
            
            // Admin - Amplio pero sin administración de empresas
            ['admin', 'orders', 1, 1, 1, 1, 0],
            ['admin', 'maintenance', 1, 1, 1, 1, 0],
            ['admin', 'customer_service', 1, 1, 1, 1, 0],
            ['admin', 'expenses', 1, 1, 1, 1, 0],
            ['admin', 'transfers', 1, 1, 1, 1, 0],
            ['admin', 'reports', 1, 1, 1, 0, 0],
            ['admin', 'users', 1, 1, 1, 0, 0],
            ['admin', 'companies', 1, 0, 0, 0, 0],
            ['admin', 'settings', 1, 0, 1, 0, 0],
            
            // Moderator - Supervisión
            ['moderator', 'orders', 1, 1, 1, 0, 0],
            ['moderator', 'maintenance', 1, 1, 1, 0, 0],
            ['moderator', 'customer_service', 1, 1, 1, 0, 0],
            ['moderator', 'expenses', 1, 0, 1, 0, 0],
            ['moderator', 'transfers', 1, 0, 1, 0, 0],
            ['moderator', 'reports', 1, 0, 0, 0, 0],
            ['moderator', 'users', 1, 0, 0, 0, 0],
            ['moderator', 'companies', 1, 0, 0, 0, 0],
            ['moderator', 'settings', 1, 0, 0, 0, 0],
            
            // User - Solo lectura básica
            ['user', 'orders', 1, 0, 0, 0, 0],
            ['user', 'maintenance', 1, 0, 0, 0, 0],
            ['user', 'customer_service', 1, 0, 0, 0, 0],
            ['user', 'expenses', 1, 0, 0, 0, 0],
            ['user', 'transfers', 1, 0, 0, 0, 0],
            ['user', 'reports', 1, 0, 0, 0, 0],
            ['user', 'users', 0, 0, 0, 0, 0],
            ['user', 'companies', 0, 0, 0, 0, 0],
            ['user', 'settings', 0, 0, 0, 0, 0]
        ];
        
        $success_count = 0;
        $stmt = $mysqli->prepare("INSERT INTO role_permission_templates (role_name, module_id, can_view, can_create, can_edit, can_delete, can_admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($role_templates as $template) {
            $stmt->bind_param("ssiiiii", $template[0], $template[1], $template[2], $template[3], $template[4], $template[5], $template[6]);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'message' => "Plantillas de roles instaladas exitosamente. $success_count plantillas creadas.",
            'installed_count' => $success_count
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

?>
