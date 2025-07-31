<?php
/**
 * Configuración de Email para Sistema de Invitaciones
 * Ejemplo de configuración para diferentes servicios de email
 */

// === CONFIGURACIÓN BÁSICA ===
define('MAIL_FROM_EMAIL', 'noreply@tuempresa.com');
define('MAIL_FROM_NAME', 'Índice Producción');
define('MAIL_REPLY_TO', 'soporte@tuempresa.com');

// === CONFIGURACIÓN SMTP ===
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

// Mailgun
/*
define('SMTP_HOST', 'smtp.mailgun.org');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'postmaster@tu-dominio.mailgun.org');
define('SMTP_PASSWORD', 'tu-api-key');
*/

// SendGrid
/*
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'apikey');
define('SMTP_PASSWORD', 'tu-sendgrid-api-key');
*/

// Amazon SES
/*
define('SMTP_HOST', 'email-smtp.us-east-1.amazonaws.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tu-access-key-id');
define('SMTP_PASSWORD', 'tu-secret-access-key');
*/

/**
 * Función mejorada para envío de emails con PHPMailer
 * Requiere: composer require phpmailer/phpmailer
 */
function sendInvitationEmailWithPHPMailer($email, $token, $role, $company_name = '') {
    // Si no tienes PHPMailer instalado, usa la función mail() básica
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendBasicEmail($email, $token, $role, $company_name);
    }
    
    // Importar clases de PHPMailer
    require_once 'vendor/autoload.php'; // Asegurar que el autoloader esté incluido
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración del servidor (solo si las constantes están definidas)
        if (defined('SMTP_HOST')) {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
        }
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($email);
        $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
        
        // Contenido del email
        $invitation_link = BASE_URL . "admin/accept_invitation.php?token=" . $token;
        $role_names = [
            'superadmin' => 'Superadministrador',
            'admin' => 'Administrador',
            'moderator' => 'Moderador',
            'user' => 'Usuario'
        ];
        $role_name = $role_names[$role] ?? $role;
        
        $mail->isHTML(true);
        $mail->Subject = 'Invitación para unirte a ' . ($company_name ?: 'Índice Producción');
        
        $mail->Body = getEmailTemplate($email, $invitation_link, $role_name, $company_name);
        $mail->AltBody = getEmailTextContent($email, $invitation_link, $role_name, $company_name);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Función básica para envío de emails sin PHPMailer
 */
function sendBasicEmail($email, $token, $role, $company_name = '') {
    $invitation_link = BASE_URL . "admin/accept_invitation.php?token=" . $token;
    $role_names = [
        'superadmin' => 'Superadministrador',
        'admin' => 'Administrador',
        'moderator' => 'Moderador',
        'user' => 'Usuario'
    ];
    $role_name = $role_names[$role] ?? $role;
    $company_name = $company_name ?: 'Índice Producción';
    
    $subject = "Invitación para unirte a $company_name";
    $message = getEmailTextContent($email, $invitation_link, $role_name, $company_name);
    
    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
        'Reply-To: ' . MAIL_REPLY_TO,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    return mail($email, $subject, getEmailTemplate($email, $invitation_link, $role_name, $company_name), implode("\r\n", $headers));
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
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; }
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
                <p><small>$invitation_link</small></p>
                
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
 * Función para instalar PHPMailer vía Composer
 * Ejecutar desde la línea de comandos en la raíz del proyecto:
 * 
 * composer require phpmailer/phpmailer
 * 
 * Luego incluir el autoloader en config.php:
 * require_once 'vendor/autoload.php';
 */

// === INSTRUCCIONES DE USO ===
/*
1. Renombrar este archivo a 'email_config.php'
2. Descomentar y configurar las variables SMTP de tu proveedor
3. Incluir este archivo en config.php:
   require_once 'admin/email_config.php';
4. En controller.php, reemplazar la función sendInvitationEmail() 
   con la versión de este archivo
5. Opcionalmente, instalar PHPMailer para mejor funcionalidad:
   composer require phpmailer/phpmailer
*/
?>
