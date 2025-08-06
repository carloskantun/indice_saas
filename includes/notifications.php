<?php
// Sistema de notificaciones internas para invitaciones

// Función para crear la tabla de notificaciones si no existe
function createNotificationsTable() {
    try {
        $db = getDB();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            data JSON,
            status ENUM('pending', 'unread', 'read', 'completed') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_company_user (company_id, user_id),
            INDEX idx_status (status),
            INDEX idx_type (type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        return true;
        
    } catch (Exception $e) {
        error_log("Error creando tabla notifications: " . $e->getMessage());
        return false;
    }
}

// Crear la tabla automáticamente al incluir este archivo
createNotificationsTable();

// Sistema de notificaciones internas para invitaciones
function createInvitationNotification($company_id, $inviter_user_id, $email, $nivel, $reason = 'email_failed') {
    try {
        $db = getDB();
        
        // Buscar si el usuario invitado ya existe en el sistema
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $invited_user = $stmt->fetch();
        
        // Para usuarios existentes: crear notificación para el usuario invitado
        // Para usuarios nuevos: crear notificación para el invitador (informativa)
        if ($invited_user) {
            $notification_user_id = $invited_user['id'];
            $title = 'Nueva invitación recibida';
            $message = sprintf(
                'Has sido invitado a unirte a una empresa con nivel %d. %s',
                $nivel,
                $reason === 'email_failed' ? 'El email no pudo ser enviado, pero puedes aceptar desde aquí.' : 'Puedes aceptar la invitación desde aquí.'
            );
        } else {
            // Usuario no existe - notificación informativa para el invitador
            $notification_user_id = $inviter_user_id;
            $title = 'Invitación creada - Usuario nuevo';
            $message = sprintf(
                'Se ha creado una invitación para %s con nivel %d. El usuario deberá registrarse primero para aceptar la invitación.',
                $email,
                $nivel
            );
        }
        
        // Crear la notificación
        $stmt = $db->prepare("
            INSERT INTO notifications (company_id, user_id, type, title, message, data, created_at, status) 
            VALUES (?, ?, 'invitation', ?, ?, ?, NOW(), 'pending')
        ");
        
        $data = json_encode([
            'email' => $email,
            'nivel' => $nivel,
            'inviter_id' => $inviter_user_id,
            'invited_user_id' => $invited_user ? $invited_user['id'] : null,
            'reason' => $reason,
            'can_accept' => $invited_user ? true : false, // Solo usuarios existentes pueden aceptar
            'user_exists' => $invited_user ? true : false
        ]);
        
        $stmt->execute([$company_id, $notification_user_id, $title, $message, $data]);
        
        return $db->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error creando notificación de invitación: " . $e->getMessage());
        return false;
    }
}

function getPendingInvitations($company_id, $user_id = null) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT * FROM notifications 
            WHERE company_id = ? AND type = 'invitation' AND status = 'pending'
        ";
        $params = [$company_id];
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error obteniendo invitaciones pendientes: " . $e->getMessage());
        return [];
    }
}

function acceptInvitationFromNotification($notification_id, $accepting_user_id, $password) {
    try {
        $db = getDB();
        
        // Obtener la notificación
        $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ? AND status = 'pending'");
        $stmt->execute([$notification_id]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return ['success' => false, 'message' => 'Notificación no encontrada'];
        }
        
        $data = json_decode($notification['data'], true);
        if (!$data || !$data['can_accept']) {
            return ['success' => false, 'message' => 'Esta invitación no puede ser aceptada desde aquí'];
        }
        
        // Verificar que el usuario que acepta coincida con el email de la invitación
        $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$accepting_user_id]);
        $user_email = $stmt->fetchColumn();
        
        if ($user_email !== $data['email']) {
            return ['success' => false, 'message' => 'Solo el usuario invitado puede aceptar esta invitación'];
        }
        
        // Verificar que el usuario no sea ya miembro de la empresa
        $stmt = $db->prepare("SELECT id FROM user_companies WHERE user_id = ? AND company_id = ?");
        $stmt->execute([$accepting_user_id, $notification['company_id']]);
        $existing_membership = $stmt->fetch();
        
        if ($existing_membership) {
            // Marcar notificación como completada ya que el usuario ya es miembro
            $stmt = $db->prepare("UPDATE notifications SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$notification_id]);
            
            return ['success' => false, 'message' => 'Ya eres miembro de esta empresa'];
        }
        
        // Crear la relación usuario-empresa
        $stmt = $db->prepare("
            INSERT INTO user_companies (user_id, company_id, nivel, fecha_registro) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $accepting_user_id,
            $notification['company_id'],
            $data['nivel']
        ]);
        
        if ($result) {
            // Marcar notificación como completada
            $stmt = $db->prepare("UPDATE notifications SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$notification_id]);
            
            // Crear notificación de confirmación para el invitador
            $stmt = $db->prepare("
                INSERT INTO notifications (company_id, user_id, type, title, message, created_at, status) 
                VALUES (?, ?, 'invitation_accepted', 'Invitación aceptada', ?, NOW(), 'unread')
            ");
            
            $confirmation_message = sprintf('El usuario %s ha aceptado la invitación y ahora forma parte de la empresa.', $user_email);
            $stmt->execute([$notification['company_id'], $data['inviter_id'], $confirmation_message]);
            
            return ['success' => true, 'message' => 'Invitación aceptada exitosamente. Ahora formas parte de la empresa.'];
        }
        
        return ['success' => false, 'message' => 'Error al procesar la invitación'];
        
    } catch (Exception $e) {
        error_log("Error aceptando invitación: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del sistema'];
    }
}

function getNotifications($company_id, $user_id, $limit = 10) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE company_id = ? AND user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$company_id, $user_id, $limit]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error obteniendo notificaciones: " . $e->getMessage());
        return [];
    }
}

function markNotificationAsRead($notification_id, $user_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            UPDATE notifications 
            SET status = 'read', updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notification_id, $user_id]);
        
    } catch (Exception $e) {
        error_log("Error marcando notificación como leída: " . $e->getMessage());
        return false;
    }
}

function displayNotifications($company_id, $user_id) {
    $notifications = getNotifications($company_id, $user_id);
    
    if (empty($notifications)) {
        return '<div class="text-muted text-center py-3">No hay notificaciones</div>';
    }
    
    $html = '';
    foreach ($notifications as $notification) {
        $isUnread = $notification['status'] === 'unread' || $notification['status'] === 'pending';
        $data = json_decode($notification['data'], true);
        
        $html .= '<div class="notification-item border-bottom py-2 ' . ($isUnread ? 'bg-light' : '') . '">';
        $html .= '<div class="d-flex justify-content-between align-items-start">';
        $html .= '<div class="flex-grow-1">';
        $html .= '<h6 class="mb-1">' . htmlspecialchars($notification['title']) . '</h6>';
        $html .= '<p class="mb-1 small">' . htmlspecialchars($notification['message']) . '</p>';
        $html .= '<small class="text-muted">' . date('d/m/Y H:i', strtotime($notification['created_at'])) . '</small>';
        $html .= '</div>';
        
        // Botones de acción para invitaciones pendientes
        if ($notification['type'] === 'invitation' && $notification['status'] === 'pending' && $data && $data['can_accept']) {
            $html .= '<div class="ms-2">';
            $html .= '<button class="btn btn-sm btn-success accept-invitation" data-notification-id="' . $notification['id'] . '">';
            $html .= '<i class="fas fa-check"></i> Aceptar';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    return $html;
}
