<?php
/**
 * FUNCIONES SIMPLIFICADAS DEL SISTEMA DE INVITACIONES
 * Para uso directo en el controller
 */

/**
 * Detectar si un usuario ya existe por email
 */
function detectExistingUser($email) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email,
                   u.created_at, u.status as user_status,
                   COUNT(uc.company_id) as companies_count
            FROM users u 
            LEFT JOIN user_companies uc ON u.id = uc.user_id 
            WHERE u.email = ? 
            GROUP BY u.id
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'exists' => true,
                'user_id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'status' => $user['user_status'],
                'companies_count' => (int)$user['companies_count']
            ];
        }
        
        return ['exists' => false];
        
    } catch (Exception $e) {
        error_log("Error in detectExistingUser: " . $e->getMessage());
        return ['exists' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Vincular usuario existente a una empresa
 */
function linkExistingUserToCompany($user_id, $company_id, $role = 'user', $modules = []) {
    $db = getDB();
    
    try {
        // Verificar si ya está vinculado
        $stmt = $db->prepare("
            SELECT id FROM user_companies 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$user_id, $company_id]);
        
        if (!$stmt->fetch()) {
            // No existe vinculación, crear
            $stmt = $db->prepare("
                INSERT INTO user_companies (user_id, company_id, role, joined_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $company_id, $role]);
            
            return true;
        }
        
        return true; // Ya estaba vinculado
        
    } catch (Exception $e) {
        error_log("Error in linkExistingUserToCompany: " . $e->getMessage());
        return false;
    }
}

/**
 * Crear invitación para usuario nuevo
 */
function createUserInvitation($invitation_data) {
    $db = getDB();
    
    try {
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $db->prepare("
            INSERT INTO invitations (email, company_id, unit_id, business_id, role, 
                                   token, status, sent_date, expiration_date, sent_by)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?)
        ");
        
        $result = $stmt->execute([
            $invitation_data['email'],
            $invitation_data['company_id'],
            $invitation_data['unit_id'] ?? null,
            $invitation_data['business_id'] ?? null,
            $invitation_data['role'],
            $token,
            $expiration,
            $invitation_data['invited_by']
        ]);
        
        if ($result) {
            // Aquí se puede agregar envío de email
            return [
                'success' => true,
                'invitation_id' => $db->lastInsertId(),
                'token' => $token
            ];
        }
        
        return ['success' => false];
        
    } catch (Exception $e) {
        error_log("Error in createUserInvitation: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
