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
    $nivel = intval($_POST['nivel'] ?? 1);
    
    if (!$email) {
        throw new Exception('Email inválido');
    }
    
    if (!in_array($nivel, [1, 2, 3])) {
        throw new Exception('Nivel inválido');
    }
    
    $db = getDB();
    
    // Verificar permisos del usuario que invita
    $stmt = $db->prepare("SELECT nivel FROM user_companies WHERE user_id = ? AND company_id = ?");
    $stmt->execute([$user_id, $company_id]);
    $inviter_level = $stmt->fetchColumn();
    
    if (!$inviter_level || $inviter_level < 2) {
        throw new Exception('No tienes permisos para enviar invitaciones');
    }
    
    // Verificar que no se invite con un nivel mayor al del invitador
    if ($nivel >= $inviter_level) {
        throw new Exception('No puedes invitar con un nivel igual o mayor al tuyo');
    }
    
    // Verificar restricciones del plan
    $restriction_check = checkPlanRestrictions($company_id, 'users', 1);
    if (!$restriction_check['allowed']) {
        throw new Exception($restriction_check['message']);
    }
    
    // Verificar si el usuario ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing_user = $stmt->fetch();
    
    // Verificar si ya está invitado o es miembro
    if ($existing_user) {
        $stmt = $db->prepare("SELECT * FROM user_companies WHERE user_id = ? AND company_id = ?");
        $stmt->execute([$existing_user['id'], $company_id]);
        if ($stmt->fetch()) {
            throw new Exception('Este usuario ya es miembro de la empresa');
        }
    }
    
    // Verificar si ya hay una invitación pendiente
    $stmt = $db->prepare("
        SELECT id FROM notifications 
        WHERE company_id = ? AND type = 'invitation' AND status = 'pending'
        AND JSON_EXTRACT(data, '$.email') = ?
    ");
    $stmt->execute([$company_id, $email]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una invitación pendiente para este email');
    }
    
    // Obtener información de la empresa
    $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company_name = $stmt->fetchColumn();
    
    // Intentar enviar email
    $email_sent = false;
    $email_error = '';
    
    try {
        if (defined('SMTP_HOST') && constant('SMTP_HOST')) {
            // Construir enlace de invitación
            $invitation_token = bin2hex(random_bytes(32));
            $invitation_link = "http://localhost:8000/auth/register.php?invitation=" . $invitation_token . "&email=" . urlencode($email);
            
            $subject = "Invitación a " . $company_name;
            $message = "
            <h2>Has sido invitado a unirte a " . htmlspecialchars($company_name) . "</h2>
            <p>Se te ha invitado a formar parte de la empresa con nivel de acceso: " . ['', 'Usuario', 'Supervisor', 'Administrador'][$nivel] . "</p>
            <p><a href='$invitation_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Aceptar Invitación</a></p>
            <p>Si no puedes hacer clic en el enlace, copia y pega esta URL en tu navegador:</p>
            <p>$invitation_link</p>
            <p>Esta invitación es válida por 7 días.</p>
            ";
            
            // Aquí iría la función de envío de email
            // Por ahora simularemos el fallo
            $email_sent = false;
            $email_error = 'Configuración de email no disponible';
        } else {
            $email_error = 'Email no configurado';
        }
    } catch (Exception $e) {
        $email_error = $e->getMessage();
    }
    
    // Crear notificación interna (siempre)
    $notification_id = createInvitationNotification(
        $company_id, 
        $user_id, 
        $email, 
        $nivel, 
        $email_sent ? 'email_sent' : 'email_failed'
    );
    
    if (!$notification_id) {
        throw new Exception('Error creando la notificación de invitación');
    }
    
    // Respuesta de éxito
    $response = [
        'success' => true,
        'message' => $email_sent 
            ? 'Invitación enviada por email exitosamente' 
            : 'Invitación creada. El usuario puede aceptarla desde su panel de notificaciones.',
        'email_sent' => $email_sent,
        'notification_created' => true
    ];
    
    if (!$email_sent) {
        $response['email_error'] = $email_error;
        $response['fallback_info'] = 'La invitación está disponible en el sistema de notificaciones interno.';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
