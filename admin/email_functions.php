<?php
/**
 * Funciones de Email para Sistema de Invitaciones
 * Versión mejorada con soporte SMTP sin dependencias externas
 */

/**
 * Función principal para envío de emails de invitación
 */
if (!function_exists('sendInvitationEmail')) {
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
    
    $role_display = $role_names[$role] ?? ucfirst($role);
    $subject = "Invitación a " . ($company_name ?: 'Índice Producción');
    
    $html_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 ¡Has sido invitado!</h1>
                <p>Te han invitado a formar parte de " . htmlspecialchars($company_name ?: 'nuestro equipo') . "</p>
            </div>
            <div class='content'>
                <h2>Detalles de la invitación:</h2>
                <ul>
                    <li><strong>Empresa:</strong> " . htmlspecialchars($company_name ?: 'Índice Producción') . "</li>
                    <li><strong>Rol asignado:</strong> " . htmlspecialchars($role_display) . "</li>
                    <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                </ul>
                
                <p>Para aceptar esta invitación y configurar tu cuenta, haz clic en el siguiente botón:</p>
                
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($invitation_link) . "' class='button'>
                        ✅ Aceptar Invitación
                    </a>
                </p>
                
                <p><small><strong>Nota:</strong> Este enlace expirará en 7 días por seguridad.</small></p>
                
                <hr>
                <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 4px;'>
                    " . htmlspecialchars($invitation_link) . "
                </p>
            </div>
            <div class='footer'>
                <p>Este email fue enviado automáticamente por " . htmlspecialchars($company_name ?: 'Índice Producción') . "</p>
                <p>Si no esperabas esta invitación, puedes ignorar este mensaje.</p>
            </div>
        </div>
    </body>
    </html>";
    
    $plain_body = "
¡Has sido invitado a " . ($company_name ?: 'Índice Producción') . "!

Detalles de la invitación:
- Empresa: " . ($company_name ?: 'Índice Producción') . "
- Rol: " . $role_display . "
- Email: " . $email . "

Para aceptar esta invitación, visita el siguiente enlace:
" . $invitation_link . "

Nota: Este enlace expirará en 7 días por seguridad.

---
Este email fue enviado automáticamente.
Si no esperabas esta invitación, puedes ignorar este mensaje.
    ";
    
    return sendEmail($email, $subject, $html_body, $plain_body);
}
}

/**
 * Función para enviar email de prueba
 */
if (!function_exists('sendTestEmail')) {
function sendTestEmail($email) {
    $subject = "✅ Prueba de Configuración - Índice SaaS";
    
    $html_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 500px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ ¡Email funcionando!</h1>
                <p>La configuración de email se probó exitosamente</p>
            </div>
            <div style='padding: 20px; background: #f8f9fa; margin-top: 10px; border-radius: 8px;'>
                <p><strong>Detalles de la prueba:</strong></p>
                <ul>
                    <li>Fecha: " . date('d/m/Y H:i:s') . "</li>
                    <li>Destinatario: " . htmlspecialchars($email) . "</li>
                    <li>Sistema: Índice SaaS</li>
                </ul>
                <p>Si recibes este email, la configuración SMTP está funcionando correctamente.</p>
            </div>
        </div>
    </body>
    </html>";
    
    $plain_body = "
✅ Email de prueba - Índice SaaS

¡La configuración de email funciona correctamente!

Detalles:
- Fecha: " . date('d/m/Y H:i:s') . "
- Destinatario: " . $email . "
- Sistema: Índice SaaS

Si recibes este mensaje, el envío de emails está configurado correctamente.
    ";
    
    return sendEmail($email, $subject, $html_body, $plain_body);
}
}

/**
 * Función general para envío de emails
 */
if (!function_exists('sendEmail')) {
function sendEmail($to, $subject, $html_body, $plain_body = null) {
    try {
        // Configuración básica
        $from_email = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@indicesaas.com';
        $from_name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Índice SaaS';
        $reply_to = defined('MAIL_REPLY_TO') ? MAIL_REPLY_TO : $from_email;
        
        // Intentar usar SMTP si está configurado
        if (defined('SMTP_HOST') && defined('SMTP_USERNAME')) {
            $smtp_host = constant('SMTP_HOST');
            $smtp_username = constant('SMTP_USERNAME');
            if (!empty($smtp_host) && !empty($smtp_username)) {
                return sendEmailSMTP($to, $subject, $html_body, $plain_body, $from_email, $from_name, $reply_to);
            }
        }
        
        // Usar PHP mail() como fallback
        return sendEmailPHP($to, $subject, $html_body, $from_email, $from_name, $reply_to);
        
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al enviar email: ' . $e->getMessage()];
    }
}
}

/**
 * Envío via PHP mail() (fallback simple y confiable)
 */
if (!function_exists('sendEmailPHP')) {
function sendEmailPHP($to, $subject, $html_body, $from_email, $from_name, $reply_to) {
    try {
        // Headers para email HTML
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $reply_to,
            'X-Mailer: Indice SaaS'
        ];
        
        $headers_string = implode("\r\n", $headers);
        
        if (mail($to, $subject, $html_body, $headers_string)) {
            error_log("Email enviado exitosamente via PHP mail() a: $to");
            return ['success' => true, 'message' => 'Email enviado exitosamente'];
        } else {
            error_log("Error en PHP mail() enviando a: $to");
            return ['success' => false, 'message' => 'Error en PHP mail() - verificar configuración del servidor'];
        }
    } catch (Exception $e) {
        error_log("Excepción en PHP mail(): " . $e->getMessage());
        return ['success' => false, 'message' => 'Error PHP mail(): ' . $e->getMessage()];
    }
}
}

/**
 * Envío via SMTP (implementación básica sin dependencias)
 */
if (!function_exists('sendEmailSMTP')) {
function sendEmailSMTP($to, $subject, $html_body, $plain_body, $from_email, $from_name, $reply_to) {
    try {
        // Obtener configuración SMTP usando constant() para evitar errores de linter
        $smtp_host = defined('SMTP_HOST') ? constant('SMTP_HOST') : '';
        $smtp_port = defined('SMTP_PORT') ? constant('SMTP_PORT') : 587;
        $smtp_secure = defined('SMTP_SECURE') ? constant('SMTP_SECURE') : 'tls';
        $smtp_username = defined('SMTP_USERNAME') ? constant('SMTP_USERNAME') : '';
        $smtp_password = defined('SMTP_PASSWORD') ? constant('SMTP_PASSWORD') : '';
        
        if (empty($smtp_host) || empty($smtp_username)) {
            throw new Exception("Configuración SMTP incompleta");
        }
        
        // Crear boundary para multipart
        $boundary = uniqid('np');
        
        // Headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$from_name} <{$from_email}>\r\n";
        $headers .= "Reply-To: {$reply_to}\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        
        // Body multipart
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= ($plain_body ?: strip_tags($html_body)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $html_body . "\r\n";
        $body .= "--{$boundary}--\r\n";
        
        // Conectar al servidor SMTP
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        if (!$socket) {
            throw new Exception("No se pudo conectar al servidor SMTP: {$errstr} ({$errno})");
        }
        
        // Leer respuesta inicial
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            throw new Exception("Error en conexión SMTP: {$response}");
        }
        
        // EHLO
        fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $response = fgets($socket, 512);
        
        // STARTTLS si es necesario
        if ($smtp_secure === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                fclose($socket);
                throw new Exception("Error STARTTLS: {$response}");
            }
            
            // Habilitar crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new Exception("Error habilitando TLS");
            }
            
            // EHLO otra vez después de TLS
            fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $response = fgets($socket, 512);
        }
        
        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            throw new Exception("Error AUTH: {$response}");
        }
        
        // Username
        fwrite($socket, base64_encode($smtp_username) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            throw new Exception("Error username: {$response}");
        }
        
        // Password
        fwrite($socket, base64_encode($smtp_password) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            throw new Exception("Error password: {$response}");
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM: <{$from_email}>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            throw new Exception("Error MAIL FROM: {$response}");
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO: <{$to}>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            throw new Exception("Error RCPT TO: {$response}");
        }
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '354') {
            fclose($socket);
            throw new Exception("Error DATA: {$response}");
        }
        
        // Enviar mensaje
        fwrite($socket, "Subject: {$subject}\r\n");
        fwrite($socket, $headers);
        fwrite($socket, "\r\n");
        fwrite($socket, $body);
        fwrite($socket, "\r\n.\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            throw new Exception("Error enviando mensaje: {$response}");
        }
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        error_log("Email enviado exitosamente via SMTP a: $to");
        return ['success' => true, 'message' => 'Email enviado exitosamente via SMTP'];
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        // Fallback a PHP mail() si SMTP falla
        error_log("Intentando fallback a PHP mail()...");
        return sendEmailPHP($to, $subject, $html_body, $from_email, $from_name, $reply_to);
    }
}
}
?>
