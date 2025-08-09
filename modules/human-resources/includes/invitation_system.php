<?php
/**
 * SISTEMA DE INVITACIONES PARA RECURSOS HUMANOS
 * Maneja la detección de usuarios existentes e invitaciones
 */

require_once '../../../config.php';

class InvitationSystem {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Verificar si un usuario ya existe en el sistema por email
     */
    public function checkExistingUser($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.fiscal_id,
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
                // Obtener empresas donde ya participa
                $stmt = $this->db->prepare("
                    SELECT c.name as company_name, uc.role, uc.status 
                    FROM user_companies uc 
                    JOIN companies c ON uc.company_id = c.id 
                    WHERE uc.user_id = ?
                ");
                $stmt->execute([$user['id']]);
                $user['companies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return [
                    'exists' => true,
                    'user' => $user,
                    'message' => "Usuario encontrado: {$user['first_name']} {$user['last_name']}"
                ];
            }
            
            return ['exists' => false, 'message' => 'Usuario no encontrado, se enviará invitación'];
            
        } catch (Exception $e) {
            error_log("Error checking existing user: " . $e->getMessage());
            return ['exists' => false, 'error' => 'Error al verificar usuario'];
        }
    }
    
    /**
     * Crear invitación para nuevo usuario
     */
    public function createInvitation($data) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt = $this->db->prepare("
                INSERT INTO invitations (
                    company_id, business_id, unit_id, 
                    email, first_name, last_name, phone, fiscal_id,
                    department_id, position_id, salary, 
                    role, permissions, modules,
                    token, expires_at, status, 
                    invited_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            
            $result = $stmt->execute([
                $data['company_id'], $data['business_id'], $data['unit_id'],
                $data['email'], $data['first_name'], $data['last_name'], 
                $data['phone'] ?? null, $data['fiscal_id'] ?? null,
                $data['department_id'], $data['position_id'], $data['salary'],
                $data['role'] ?? 'user', 
                json_encode($data['permissions'] ?? []),
                json_encode($data['modules'] ?? []),
                $token, $expires_at,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $invitation_id = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'invitation_id' => $invitation_id,
                    'token' => $token,
                    'message' => 'Invitación creada exitosamente'
                ];
            }
            
            return ['success' => false, 'error' => 'Error al crear invitación'];
            
        } catch (Exception $e) {
            error_log("Error creating invitation: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Asignar usuario existente a nueva empresa/negocio
     */
    public function assignExistingUser($userId, $data) {
        try {
            $this->db->beginTransaction();
            
            // Verificar si ya tiene acceso a esta empresa
            $stmt = $this->db->prepare("
                SELECT id FROM user_companies 
                WHERE user_id = ? AND company_id = ?
            ");
            $stmt->execute([$userId, $data['company_id']]);
            
            if ($stmt->fetch()) {
                $this->db->rollback();
                return ['success' => false, 'error' => 'El usuario ya tiene acceso a esta empresa'];
            }
            
            // Asignar a empresa
            $stmt = $this->db->prepare("
                INSERT INTO user_companies (user_id, company_id, role, status, assigned_by, created_at)
                VALUES (?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([
                $userId, $data['company_id'], 
                $data['role'] ?? 'user', $_SESSION['user_id']
            ]);
            
            // Asignar a unidad si aplica
            if (!empty($data['unit_id'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO user_units (user_id, unit_id, assigned_by, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$userId, $data['unit_id'], $_SESSION['user_id']]);
            }
            
            // Asignar a negocio si aplica
            if (!empty($data['business_id'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO user_businesses (user_id, business_id, assigned_by, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$userId, $data['business_id'], $_SESSION['user_id']]);
            }
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Usuario asignado exitosamente'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error assigning existing user: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al asignar usuario'];
        }
    }
    
    /**
     * Enviar email de invitación
     */
    public function sendInvitationEmail($email, $token, $data) {
        try {
            $company_name = $_SESSION['company_name'] ?? 'Tu empresa';
            $position_title = $data['position_title'] ?? 'Empleado';
            $department_name = $data['department_name'] ?? '';
            
            $invitation_url = "https://app.indiceapp.com/admin/accept_invitation.php?token=" . $token;
            
            $subject = "Invitación para unirse a {$company_name} - Indice SaaS";
            
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #3498db; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .button { background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                    .footer { text-align: center; color: #666; font-size: 12px; padding: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>¡Bienvenido a {$company_name}!</h1>
                    </div>
                    <div class='content'>
                        <h2>Has sido invitado a formar parte de nuestro equipo</h2>
                        <p><strong>Posición:</strong> {$position_title}</p>
                        " . (!empty($department_name) ? "<p><strong>Departamento:</strong> {$department_name}</p>" : "") . "
                        <p>Para comenzar a usar la plataforma Indice SaaS, haz clic en el siguiente enlace:</p>
                        <a href='{$invitation_url}' class='button'>Aceptar Invitación</a>
                        <p><small>Este enlace expira en 7 días.</small></p>
                        <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                    </div>
                    <div class='footer'>
                        <p>© 2025 Indice SaaS - Sistema de Gestión Empresarial</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: no-reply@indiceapp.com" . "\r\n";
            
            // TODO: Implementar con servicio de email real (SendGrid, Mailgun, etc.)
            // Por ahora simular envío exitoso
            error_log("SIMULATED EMAIL SENT TO: $email - TOKEN: $token");
            
            return ['success' => true, 'message' => 'Email de invitación enviado'];
            
        } catch (Exception $e) {
            error_log("Error sending invitation email: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al enviar email'];
        }
    }
    
    /**
     * Obtener módulos disponibles para asignación
     */
    public function getAvailableModules() {
        try {
            $stmt = $this->db->query("
                SELECT id, name, slug, description, icon, color, status 
                FROM modules 
                WHERE status = 'active' 
                ORDER BY order_position, name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting modules: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener permisos por módulo para el formulario
     */
    public function getModulePermissions($module_slug) {
        try {
            $stmt = $this->db->prepare("
                SELECT key_name as permission_key, description 
                FROM permissions 
                WHERE module = ? 
                ORDER BY key_name
            ");
            $stmt->execute([$module_slug]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting module permissions: " . $e->getMessage());
            return [];
        }
    }
}

// API endpoints para AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $invitation = new InvitationSystem();
    
    switch ($action) {
        case 'check_user':
            $email = $_POST['email'] ?? '';
            $result = $invitation->checkExistingUser($email);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'get_modules':
            $modules = $invitation->getAvailableModules();
            header('Content-Type: application/json');
            echo json_encode(['modules' => $modules]);
            break;
            
        case 'get_module_permissions':
            $module_slug = $_POST['module_slug'] ?? '';
            $permissions = $invitation->getModulePermissions($module_slug);
            header('Content-Type: application/json');
            echo json_encode(['permissions' => $permissions]);
            break;
            
        default:
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}
?>
