<?php
/**
 * Sistema de Notificaciones para Indice SaaS
 * Gestiona notificaciones de invitaciones y eventos del sistema
 */

require_once '../config.php';

/**
 * Obtener notificaciones de un usuario
 */
function getUserNotifications($user_id, $limit = 10) {
    $pdo = getDB();
    
    $notifications = [];
    
    try {
        // 1. Invitaciones pendientes
        $stmt = $pdo->prepare("
            SELECT 
                ui.id,
                ui.email,
                ui.role,
                ui.created_at,
                ui.expiration_date,
                c.name as company_name,
                u.name as unit_name,
                b.name as business_name,
                'invitation' as type
            FROM user_invitations ui
            INNER JOIN companies c ON ui.company_id = c.id
            LEFT JOIN units u ON ui.unit_id = u.id
            LEFT JOIN businesses b ON ui.business_id = b.id
            INNER JOIN users usr ON usr.email = ui.email
            WHERE usr.id = ? 
            AND ui.status = 'pending' 
            AND ui.expiration_date > NOW()
            ORDER BY ui.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $invitations = $stmt->fetchAll();
        
        foreach ($invitations as $inv) {
            $location = $inv['company_name'];
            if ($inv['unit_name']) $location .= " > " . $inv['unit_name'];
            if ($inv['business_name']) $location .= " > " . $inv['business_name'];
            
            $notifications[] = [
                'id' => 'inv_' . $inv['id'],
                'type' => 'invitation',
                'title' => 'Nueva invitación',
                'message' => "Has sido invitado como {$inv['role']} en {$location}",
                'created_at' => $inv['created_at'],
                'action_url' => '/admin/accept_invitation.php?token=' . getInvitationToken($inv['id']),
                'icon' => 'fas fa-envelope',
                'color' => 'primary'
            ];
        }
        
        // 2. Otras notificaciones del sistema (futuro)
        // TODO: Agregar notificaciones de cambios de permisos, nuevos módulos, etc.
        
    } catch (Exception $e) {
        error_log("Error getting notifications: " . $e->getMessage());
    }
    
    return $notifications;
}

/**
 * Contar notificaciones no leídas
 */
function getUnreadNotificationsCount($user_id) {
    $notifications = getUserNotifications($user_id, 50);
    return count($notifications);
}

/**
 * Obtener token de invitación por ID
 */
function getInvitationToken($invitation_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT token FROM user_invitations WHERE id = ?");
    $stmt->execute([$invitation_id]);
    $result = $stmt->fetch();
    return $result ? $result['token'] : '';
}

/**
 * Marcar notificación como leída (futuro)
 */
function markNotificationAsRead($user_id, $notification_id) {
    // TODO: Implementar sistema de notificaciones leídas/no leídas
    return true;
}

/**
 * Obtener notificaciones para mostrar en navbar
 */
function getNavbarNotifications($user_id) {
    $notifications = getUserNotifications($user_id, 5);
    $count = getUnreadNotificationsCount($user_id);
    
    return [
        'notifications' => $notifications,
        'count' => $count,
        'has_unread' => $count > 0
    ];
}
?>
