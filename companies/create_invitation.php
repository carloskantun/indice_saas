<?php
session_start();
require_once '../config.php';
require_once '../includes/notifications.php';

// Verificar autenticación
if (!checkAuth()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;

if (!$company_id) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Empresa no seleccionada']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

try {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $role = trim($_POST['role'] ?? 'user');
    
    if (!$email) {
        throw new Exception('Email inválido');
    }
    
    if (!in_array($role, ['user', 'admin', 'moderator'])) {
        throw new Exception('Rol inválido');
    }
    
    $db = getDB();
    
    // Verificar permisos del usuario que invita
    $stmt = $db->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ?");
    $stmt->execute([$user_id, $company_id]);
    $inviter_role = $stmt->fetchColumn();
    
    if (!in_array($inviter_role, ['superadmin', 'admin'])) {
        throw new Exception('No tienes permisos para enviar invitaciones');
    }
    
    // Si es admin, no puede invitar con rol admin o superior
    if ($inviter_role === 'admin' && in_array($role, ['admin', 'superadmin'])) {
        throw new Exception('No puedes invitar con un rol igual o superior al tuyo');
    }
    
    // Verificar si el usuario ya existe y ya está en la empresa
    $stmt = $db->prepare("
        SELECT u.id, u.name
        FROM users u 
        INNER JOIN user_companies uc ON u.id = uc.user_id 
        WHERE u.email = ? AND uc.company_id = ?
    ");
    $stmt->execute([$email, $company_id]);
    if ($existing_member = $stmt->fetch()) {
        throw new Exception('El usuario ' . $existing_member['name'] . ' ya es miembro de esta empresa');
    }
    
    // Verificar si el usuario ya existe en el sistema (pero no en esta empresa)
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        // Usuario existe pero no está en esta empresa
        $user_info = " (Usuario: " . $existing_user['name'] . ")";
    } else {
        // Usuario no existe, se registrará cuando acepte la invitación
        $user_info = " (se registrará al aceptar la invitación)";
    }
    
    // Verificar si ya hay una invitación pendiente
    $stmt = $db->prepare("
        SELECT id FROM user_invitations 
        WHERE email = ? AND company_id = ? AND status = 'pending' AND expiration_date > NOW()
    ");
    $stmt->execute([$email, $company_id]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una invitación pendiente para este email');
    }
    
    // Crear token único
    $token = bin2hex(random_bytes(32));
    
    // Crear invitación
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    $stmt = $db->prepare("
        INSERT INTO user_invitations (company_id, sent_by, email, role, token, expiration_date) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$company_id, $user_id, $email, $role, $token, $expires_at]);
    
    if ($result) {
        // Obtener información de la empresa
        $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company_name = $stmt->fetchColumn();
        
        // Crear notificación interna
        $nivel_mapping = [
            'user' => 1,
            'moderator' => 2, 
            'admin' => 3
        ];
        
        $notification_id = createInvitationNotification(
            $company_id,
            $user_id,
            $email,
            $nivel_mapping[$role] ?? 1,
            'manual_invitation'
        );
        
        // Construir enlace de invitación
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $invitation_link = $base_url . "/auth/register.php?invitation=" . $token . "&email=" . urlencode($email);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Invitación creada exitosamente' . $user_info,
            'invitation_link' => $invitation_link,
            'expires_in' => '7 días',
            'company_name' => $company_name,
            'role' => ucfirst($role),
            'notification_created' => $notification_id ? true : false,
            'instructions' => 'Comparte este enlace con el usuario para que pueda registrarse o unirse a la empresa.'
        ]);
    } else {
        throw new Exception('Error al crear la invitación');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
