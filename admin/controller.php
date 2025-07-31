<?php
/**
 * Controller para gestión de usuarios admin
 * Maneja invitaciones, roles y permisos de usuarios
 */

require_once '../config.php';

// Verificar autenticación y permisos de superadmin
if (!checkRole(['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $lang['insufficient_permissions']]);
    exit();
}

// Verificar empresa activa - requerido para admin, opcional para superadmin
if (!checkRole(['superadmin'])) {
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
    
    // Para superadmin, la empresa puede ser especificada o usar la sesión
    if (checkRole(['superadmin']) && isset($_POST['company_id']) && !empty($_POST['company_id'])) {
        $company_id = intval($_POST['company_id']);
    } else {
        $company_id = intval($_SESSION['current_company_id'] ?? 0);
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
    
    // Solo superadmin puede asignar rol superadmin
    if ($role === 'superadmin' && $_SESSION['current_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => $lang['insufficient_permissions']]);
        return;
    }
    
    try {
        // Verificar si el usuario ya está registrado
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => $lang['email_already_registered']]);
            return;
        }
        
        // Verificar si ya existe una invitación pendiente
        $stmt = $pdo->prepare("
            SELECT id FROM invitations 
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
            INSERT INTO invitations (email, company_id, unit_id, business_id, role, token, sent_by) 
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
    
    if (empty($token) || empty($name) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        return;
    }
    
    try {
        // Verificar token válido
        $stmt = $pdo->prepare("
            SELECT * FROM invitations 
            WHERE token = ? AND status = 'pending' AND expiration_date > NOW()
        ");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            echo json_encode(['success' => false, 'message' => $lang['invitation_not_found']]);
            return;
        }
        
        // Crear usuario
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, status, created_at) 
            VALUES (?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$name, $invitation['email'], $hashedPassword]);
        $user_id = $pdo->lastInsertId();
        
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
        $stmt = $pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE token = ?");
        $stmt->execute([$token]);
        
        echo json_encode(['success' => true, 'message' => $lang['invitation_accepted']]);
        
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
    $company_id = intval($_SESSION['current_company_id']);
    
    if (!$user_id || !in_array($new_role, ['superadmin', 'admin', 'moderator', 'user'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }
    
    // Solo superadmin puede asignar rol superadmin
    if ($new_role === 'superadmin' && $_SESSION['current_role'] !== 'superadmin') {
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
    
    $company_id = intval($_SESSION['current_company_id']);
    
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
            SELECT * FROM invitations 
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
            UPDATE invitations 
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
            DELETE FROM invitations 
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
            FROM invitations i
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
 * Función para enviar correo de invitación (placeholder)
 */
function sendInvitationEmail($email, $token, $role) {
    // Aquí implementarías el envío de correo real
    // Por ejemplo usando PHPMailer o la función mail() de PHP
    
    $invitation_link = BASE_URL . "admin/accept_invitation.php?token=" . $token;
    $subject = "Invitación para unirte al sistema";
    $message = "Has sido invitado como $role. Haz clic en el siguiente enlace para aceptar: $invitation_link";
    
    // mail($email, $subject, $message);
    
    return true;
}
?>
