<?php
/**
 * Configuración de Email para Sistema de Invitaciones
 * Versión simplificada y funcional
 */

// === CONFIGURACIÓN BÁSICA ===
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'noreply@tuempresa.com');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', 'Índice Producción');
}
if (!defined('MAIL_REPLY_TO')) {
    define('MAIL_REPLY_TO', 'soporte@tuempresa.com');
}

// === CONFIGURACIÓN SMTP (OPCIONAL) ===
// Descomenta y configura según tu proveedor de email

// Gmail / Google Workspace
/*
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tu-email@gmail.com');
define('SMTP_PASSWORD', 'tu-app-password'); // Usar App Password, no la contraseña normal
*/

// Outlook / Office 365
/*
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tu-email@outlook.com');
define('SMTP_PASSWORD', 'tu-contraseña');
*/

/**
 * Función básica para envío de emails (funciona sin configuración adicional)
 */
function sendInvitationEmail($email, $token, $role, $company_name = '') {
    // Si BASE_URL no está definida, usar una URL por defecto
    $base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/indice_saas/';
    $invitation_link = $base_url . "admin/accept_invitation.php?token=" . $token;
    
    $role_names = [
        'superadmin' => 'Superadministrador',
        'admin' => 'Administrador',
        'moderator' => 'Moderador',
        'user' => 'Usuario'
    ];
    $role_name = $role_names[$role] ?? $role;
    $company_name = $company_name ?: 'Índice Producción';
    
    $subject = "Invitación para unirte a $company_name";
    
    // Contenido HTML del email
    $html_message = getEmailTemplate($email, $invitation_link, $role_name, $company_name);
    
    // Contenido de texto plano
    $text_message = getEmailTextContent($email, $invitation_link, $role_name, $company_name);
    
    // Headers para email HTML
    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
        'Reply-To: ' . MAIL_REPLY_TO,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    // Intentar envío con función mail() nativa de PHP
    $result = mail($email, $subject, $html_message, implode("\r\n", $headers));
    
    // Log del resultado (opcional)
    if ($result) {
        error_log("Email enviado exitosamente a: $email");
    } else {
        error_log("Error enviando email a: $email");
    }
    
    return $result;
}

/**
 * Template HTML para el email de invitación
 */
function getEmailTemplate($email, $invitation_link, $role_name, $company_name) {
    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Invitación - $company_name</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white; 
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 30px; 
                text-align: center; 
            }
            .content { 
                padding: 30px; 
            }
            .button { 
                display: inline-block; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 15px 30px; 
                text-decoration: none; 
                border-radius: 25px; 
                font-weight: bold; 
                margin: 20px 0; 
            }
            .footer { 
                background: #f8f9fa; 
                padding: 20px; 
                text-align: center; 
                color: #666; 
                font-size: 14px; 
            }
            .info-box { 
                background: #f8f9fa; 
                border-left: 4px solid #667eea; 
                padding: 15px; 
                margin: 20px 0; 
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎯 $company_name</h1>
                <p>Sistema de Gestión Empresarial</p>
            </div>
            
            <div class='content'>
                <h2>¡Has sido invitado!</h2>
                <p>Hola,</p>
                <p>Has recibido una invitación para unirte a <strong>$company_name</strong> en nuestro sistema de gestión empresarial.</p>
                
                <div class='info-box'>
                    <strong>📧 Email:</strong> $email<br>
                    <strong>👤 Rol asignado:</strong> $role_name<br>
                    <strong>🏢 Empresa:</strong> $company_name
                </div>
                
                <p>Para completar tu registro y acceder al sistema, haz clic en el siguiente botón:</p>
                
                <div style='text-align: center;'>
                    <a href='$invitation_link' class='button'>✅ Aceptar Invitación</a>
                </div>
                
                <p><small>⚠️ <strong>Importante:</strong> Esta invitación expira en 48 horas. Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:</small></p>
                <p style='word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px;'><small>$invitation_link</small></p>
                
                <p>¡Esperamos verte pronto en el sistema!</p>
            </div>
            
            <div class='footer'>
                <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                <p>© " . date('Y') . " $company_name - Sistema de Gestión Empresarial</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Contenido de texto plano para el email
 */
function getEmailTextContent($email, $invitation_link, $role_name, $company_name) {
    return "
¡Has sido invitado a $company_name!

Hola,

Has recibido una invitación para unirte a $company_name en nuestro sistema de gestión empresarial.

Detalles de la invitación:
- Email: $email
- Rol asignado: $role_name  
- Empresa: $company_name

Para completar tu registro, visita el siguiente enlace:
$invitation_link

IMPORTANTE: Esta invitación expira en 48 horas.

¡Esperamos verte pronto en el sistema!

---
Este es un email automático, por favor no respondas a este mensaje.
© " . date('Y') . " $company_name - Sistema de Gestión Empresarial
";
}

/**
 * Función avanzada con PHPMailer (requiere instalación)
 * Descomenta para usar con PHPMailer
 */
/*
function sendInvitationEmailAdvanced($email, $token, $role, $company_name = '') {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendInvitationEmail($email, $token, $role, $company_name);
    }
    
    require_once 'vendor/autoload.php';
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración SMTP (si está definida)
        if (defined('SMTP_HOST')) {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
        }
        
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($email);
        $mail->addReplyTo(MAIL_REPLY_TO);
        
        $base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/indice_saas/';
        $invitation_link = $base_url . "admin/accept_invitation.php?token=" . $token;
        
        $role_names = [
            'superadmin' => 'Superadministrador',
            'admin' => 'Administrador',
            'moderator' => 'Moderador',
            'user' => 'Usuario'
        ];
        $role_name = $role_names[$role] ?? $role;
        $company_name = $company_name ?: 'Índice Producción';
        
        $mail->isHTML(true);
        $mail->Subject = "Invitación para unirte a $company_name";
        $mail->Body = getEmailTemplate($email, $invitation_link, $role_name, $company_name);
        $mail->AltBody = getEmailTextContent($email, $invitation_link, $role_name, $company_name);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando email con PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}
*/

// === INSTRUCCIONES DE USO ===
/*
1. Renombrar este archivo a 'email_config.php'
2. Incluir en config.php: require_once 'admin/email_config.php';
3. Para usar SMTP, descomentar y configurar las constantes SMTP
4. Para PHPMailer avanzado: composer require phpmailer/phpmailer
5. El sistema funcionará con mail() nativo sin configuración adicional
*/
?>
