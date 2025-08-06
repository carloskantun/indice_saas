<?php
/**
 * Panel de Configuración de Email
 * Permite a superadmin configurar SMTP y probar envío de emails
 */

require_once '../config.php';

// Verificar permisos de superadmin
if (!checkRole(['superadmin'])) {
    redirect('/companies/');
}

$message = '';
$message_type = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                $result = saveEmailConfig($_POST);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'test_email':
                $result = testEmailConfiguration($_POST['test_email']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                break;
        }
    }
}

// Cargar configuración actual
$current_config = loadEmailConfig();

function saveEmailConfig($data) {
    try {
        $config_content = "<?php\n";
        $config_content .= "/**\n * Configuración de Email para Sistema de Invitaciones\n */\n\n";
        
        // Configuración básica
        $config_content .= "// === CONFIGURACIÓN BÁSICA ===\n";
        $config_content .= "if (!defined('MAIL_FROM_EMAIL')) {\n";
        $config_content .= "    define('MAIL_FROM_EMAIL', '" . addslashes($data['mail_from_email']) . "');\n";
        $config_content .= "}\n";
        $config_content .= "if (!defined('MAIL_FROM_NAME')) {\n";
        $config_content .= "    define('MAIL_FROM_NAME', '" . addslashes($data['mail_from_name']) . "');\n";
        $config_content .= "}\n";
        $config_content .= "if (!defined('MAIL_REPLY_TO')) {\n";
        $config_content .= "    define('MAIL_REPLY_TO', '" . addslashes($data['mail_reply_to']) . "');\n";
        $config_content .= "}\n\n";
        
        // Configuración SMTP
        if (!empty($data['smtp_enabled']) && $data['smtp_enabled'] === '1') {
            $config_content .= "// === CONFIGURACIÓN SMTP ===\n";
            $config_content .= "define('SMTP_HOST', '" . addslashes($data['smtp_host']) . "');\n";
            $config_content .= "define('SMTP_PORT', " . intval($data['smtp_port']) . ");\n";
            $config_content .= "define('SMTP_SECURE', '" . addslashes($data['smtp_secure']) . "');\n";
            $config_content .= "define('SMTP_USERNAME', '" . addslashes($data['smtp_username']) . "');\n";
            $config_content .= "define('SMTP_PASSWORD', '" . addslashes($data['smtp_password']) . "');\n\n";
        }
        
        // Agregar funciones de envío
        $config_content .= file_get_contents('email_functions.php');
        
        file_put_contents('email_config.php', $config_content);
        
        return ['success' => true, 'message' => 'Configuración guardada exitosamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()];
    }
}

function loadEmailConfig() {
    // Incluir configuración actual si existe
    if (file_exists('email_config.php')) {
        include 'email_config.php';
    }
    
    $config = [
        'mail_from_email' => defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : '',
        'mail_from_name' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '',
        'mail_reply_to' => defined('MAIL_REPLY_TO') ? MAIL_REPLY_TO : '',
        'smtp_enabled' => defined('SMTP_HOST') ? '1' : '0',
        'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : '',
        'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
        'smtp_secure' => defined('SMTP_SECURE') ? SMTP_SECURE : 'tls',
        'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
        'smtp_password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : ''
    ];
    
    return $config;
}

function testEmailConfiguration($test_email) {
    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email de prueba no válido'];
    }
    
    try {
        // Recargar configuración
        include 'email_config.php';
        
        if (function_exists('sendTestEmail')) {
            $result = sendTestEmail($test_email);
            return $result;
        } else {
            return ['success' => false, 'message' => 'Función de envío no disponible'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error en prueba: ' . $e->getMessage()];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Email - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 px-0">
            <div class="bg-dark text-white p-3" style="min-height: 100vh;">
                <h4><i class="fas fa-cogs"></i> Admin Panel</h4>
                <nav class="nav flex-column mt-4">
                    <a class="nav-link text-white" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link text-white-50 active" href="email_settings.php">
                        <i class="fas fa-envelope me-2"></i> Configuración Email
                    </a>
                    <a class="nav-link text-white" href="permissions_management.php">
                        <i class="fas fa-key me-2"></i> Permisos
                    </a>
                    <div class="border-top border-secondary mt-4 pt-3">
                        <a class="nav-link text-white" href="../companies/">
                            <i class="fas fa-arrow-left me-2"></i> Volver
                        </a>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3">
                            <i class="fas fa-envelope text-primary"></i>
                            Configuración de Email
                        </h1>
                        <p class="text-muted">Configura el sistema de envío de correos electrónicos</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cog"></i>
                                    Configuración General
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_config">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Email Remitente *</label>
                                            <input type="email" name="mail_from_email" class="form-control" 
                                                   value="<?= htmlspecialchars($current_config['mail_from_email']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nombre Remitente *</label>
                                            <input type="text" name="mail_from_name" class="form-control" 
                                                   value="<?= htmlspecialchars($current_config['mail_from_name']) ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email de Respuesta</label>
                                        <input type="email" name="mail_reply_to" class="form-control" 
                                               value="<?= htmlspecialchars($current_config['mail_reply_to']) ?>">
                                    </div>
                                    
                                    <div class="form-check mb-4">
                                        <input type="checkbox" class="form-check-input" id="smtp_enabled" name="smtp_enabled" value="1"
                                               <?= $current_config['smtp_enabled'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="smtp_enabled">
                                            <strong>Usar SMTP (Recomendado)</strong>
                                        </label>
                                        <div class="form-text">
                                            Si no está marcado, se usará la función mail() de PHP (menos confiable)
                                        </div>
                                    </div>
                                    
                                    <div id="smtp_config" style="<?= $current_config['smtp_enabled'] ? '' : 'display: none;' ?>">
                                        <h6 class="border-bottom pb-2 mb-3">Configuración SMTP</h6>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label class="form-label">Servidor SMTP</label>
                                                <input type="text" name="smtp_host" class="form-control" 
                                                       value="<?= htmlspecialchars($current_config['smtp_host']) ?>"
                                                       placeholder="smtp.gmail.com">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Puerto</label>
                                                <input type="number" name="smtp_port" class="form-control" 
                                                       value="<?= $current_config['smtp_port'] ?>" placeholder="587">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Seguridad</label>
                                            <select name="smtp_secure" class="form-select">
                                                <option value="tls" <?= $current_config['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS (Recomendado)</option>
                                                <option value="ssl" <?= $current_config['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                <option value="" <?= empty($current_config['smtp_secure']) ? 'selected' : '' ?>>Ninguna</option>
                                            </select>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Usuario SMTP</label>
                                                <input type="text" name="smtp_username" class="form-control" 
                                                       value="<?= htmlspecialchars($current_config['smtp_username']) ?>"
                                                       placeholder="tu-email@gmail.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Contraseña SMTP</label>
                                                <input type="password" name="smtp_password" class="form-control" 
                                                       value="<?= htmlspecialchars($current_config['smtp_password']) ?>"
                                                       placeholder="tu-contraseña-de-app">
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Para Gmail:</strong> Usa una "contraseña de aplicación" en lugar de tu contraseña normal.
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Guardar Configuración
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-paper-plane"></i>
                                    Prueba de Envío
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="test_email">
                                    <div class="mb-3">
                                        <label class="form-label">Email de Prueba</label>
                                        <input type="email" name="test_email" class="form-control" required
                                               placeholder="prueba@ejemplo.com">
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-paper-plane"></i> Enviar Email de Prueba
                                    </button>
                                </form>
                                
                                <hr>
                                
                                <div class="text-center">
                                    <h6>Estado Actual</h6>
                                    <?php if ($current_config['smtp_enabled']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> SMTP Configurado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-exclamation-triangle"></i> PHP mail()
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('smtp_enabled').addEventListener('change', function() {
    document.getElementById('smtp_config').style.display = this.checked ? 'block' : 'none';
});
</script>

</body>
</html>
