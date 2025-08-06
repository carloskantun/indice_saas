<?php
/**
 * Controlador para el sistema de invitaciones
 * Maneja la aceptación y rechazo de invitaciones
 */

require_once '../config.php';

header('Content-Type: application/json');

if (!checkAuth()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$token = $_POST['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'Token requerido']);
    exit;
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Obtener email del usuario actual
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_email = $stmt->fetchColumn();
    
    if (!$user_email) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    // Buscar la invitación
    $stmt = $db->prepare("
        SELECT ui.*, c.name as company_name 
        FROM user_invitations ui
        JOIN companies c ON ui.company_id = c.id
        WHERE ui.token = ? AND ui.status = 'pending' AND ui.email = ?
    ");
    $stmt->execute([$token, $user_email]);
    $invitation = $stmt->fetch();
    
    if (!$invitation) {
        echo json_encode(['success' => false, 'error' => 'Invitación no válida o ya procesada']);
        exit;
    }
    
    // Verificar si ya existe la relación usuario-empresa
    $stmt = $db->prepare("
        SELECT id FROM user_companies 
        WHERE user_id = ? AND company_id = ?
    ");
    $stmt->execute([$user_id, $invitation['company_id']]);
    $existing_relation = $stmt->fetch();
    
    if ($action === 'accept_invitation') {
        
        if ($existing_relation) {
            // Actualizar estado si ya existe
            $stmt = $db->prepare("
                UPDATE user_companies 
                SET status = 'active', role = ?
                WHERE user_id = ? AND company_id = ?
            ");
            $stmt->execute([$invitation['role'] ?? 'user', $user_id, $invitation['company_id']]);
        } else {
            // Crear nueva relación
            $stmt = $db->prepare("
                INSERT INTO user_companies (user_id, company_id, role, status) 
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$user_id, $invitation['company_id'], $invitation['role'] ?? 'user']);
        }
        
        // Marcar invitación como aceptada
        $stmt = $db->prepare("
            UPDATE user_invitations 
            SET status = 'accepted', accepted_date = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$invitation['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Te has unido exitosamente a ' . $invitation['company_name']
        ]);
        
    } elseif ($action === 'reject_invitation') {
        
        // Marcar invitación como rechazada
        $stmt = $db->prepare("
            UPDATE user_invitations 
            SET status = 'rejected' 
            WHERE id = ?
        ");
        $stmt->execute([$invitation['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Has rechazado la invitación a ' . $invitation['company_name']
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    error_log("Error procesando invitación: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error procesando la invitación. Intenta más tarde.']);
}
