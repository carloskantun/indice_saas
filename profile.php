<?php
/**
 * PERFIL DE USUARIO - SISTEMA SAAS INDICE
 * Página para gestionar el perfil completo del usuario
 */

require_once 'config.php';

// Verificar autenticación
if (!checkAuth()) {
    redirect('auth/');
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Datos básicos
        $name = trim($_POST['name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Datos personales
        $birth_date = $_POST['birth_date'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $bio = trim($_POST['bio'] ?? '');
        
        // Dirección
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $fiscal_id = trim($_POST['fiscal_id'] ?? '');
        
        // Preferencias
        $timezone = $_POST['timezone'] ?? 'America/Mexico_City';
        $language = $_POST['language'] ?? 'es';
        $notifications_email = isset($_POST['notifications_email']) ? 1 : 0;
        $notifications_sms = isset($_POST['notifications_sms']) ? 1 : 0;
        
        // Validaciones básicas
        if (empty($name) || empty($email)) {
            throw new Exception('Nombre y email son requeridos');
        }
        
        // Verificar que el email no esté en uso por otro usuario
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Este email ya está en uso por otro usuario');
        }
        
        // Actualizar usuario
        $stmt = $db->prepare("
            UPDATE users SET 
                name = ?, first_name = ?, last_name = ?, email = ?, phone = ?,
                address = ?, city = ?, state = ?, country = ?, postal_code = ?,
                fiscal_id = ?, birth_date = ?, gender = ?, bio = ?,
                timezone = ?, language = ?, 
                notifications_email = ?, notifications_sms = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $first_name, $last_name, $email, $phone,
            $address, $city, $state, $country, $postal_code,
            $fiscal_id, $birth_date, $gender, $bio,
            $timezone, $language,
            $notifications_email, $notifications_sms,
            $user_id
        ]);
        
        // Actualizar sesión
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        $db->commit();
        $message = 'Perfil actualizado exitosamente';
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Obtener datos actuales del usuario
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        redirect('auth/logout.php');
    }
} catch (Exception $e) {
    $error = 'Error al cargar datos del usuario';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="companies/">
                <i class="fas fa-user-circle me-2"></i><?php echo $lang['app_name']; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="companies/">
                    <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="companies/">Inicio</a></li>
                        <li class="breadcrumb-item active">Mi Perfil</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-1">
                    <i class="fas fa-user-edit text-primary me-2"></i>Mi Perfil
                </h1>
                <p class="text-muted mb-0">Gestiona tu información personal y preferencias</p>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" class="row">
            <!-- Información Básica -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user text-primary me-2"></i>Información Básica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="name" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Nombre(s)</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Apellido(s)</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="birth_date" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                       value="<?php echo $user['birth_date'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Género</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Seleccionar...</option>
                                    <option value="Masculino" <?php echo ($user['gender'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="Femenino" <?php echo ($user['gender'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?php echo ($user['gender'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    <option value="Prefiero no decir" <?php echo ($user['gender'] ?? '') === 'Prefiero no decir' ? 'selected' : ''; ?>>Prefiero no decir</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="fiscal_id" class="form-label">RFC/ID Fiscal</label>
                                <input type="text" class="form-control" id="fiscal_id" name="fiscal_id" 
                                       value="<?php echo htmlspecialchars($user['fiscal_id'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="bio" class="form-label">Biografía</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3" 
                                          placeholder="Cuéntanos algo sobre ti..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dirección y Ubicación -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>Dirección y Ubicación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Calle, número, colonia..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">Estado/Provincia</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">País</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Seleccionar...</option>
                                    <option value="México" <?php echo ($user['country'] ?? '') === 'México' ? 'selected' : ''; ?>>México</option>
                                    <option value="Estados Unidos" <?php echo ($user['country'] ?? '') === 'Estados Unidos' ? 'selected' : ''; ?>>Estados Unidos</option>
                                    <option value="España" <?php echo ($user['country'] ?? '') === 'España' ? 'selected' : ''; ?>>España</option>
                                    <option value="Colombia" <?php echo ($user['country'] ?? '') === 'Colombia' ? 'selected' : ''; ?>>Colombia</option>
                                    <option value="Argentina" <?php echo ($user['country'] ?? '') === 'Argentina' ? 'selected' : ''; ?>>Argentina</option>
                                    <option value="Chile" <?php echo ($user['country'] ?? '') === 'Chile' ? 'selected' : ''; ?>>Chile</option>
                                    <option value="Perú" <?php echo ($user['country'] ?? '') === 'Perú' ? 'selected' : ''; ?>>Perú</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                       value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Preferencias -->
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-cog text-secondary me-2"></i>Preferencias
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="timezone" class="form-label">Zona Horaria</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="America/Mexico_City" <?php echo ($user['timezone'] ?? '') === 'America/Mexico_City' ? 'selected' : ''; ?>>México (GMT-6)</option>
                                    <option value="America/New_York" <?php echo ($user['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Nueva York (GMT-5)</option>
                                    <option value="America/Los_Angeles" <?php echo ($user['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Los Ángeles (GMT-8)</option>
                                    <option value="Europe/Madrid" <?php echo ($user['timezone'] ?? '') === 'Europe/Madrid' ? 'selected' : ''; ?>>Madrid (GMT+1)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="language" class="form-label">Idioma</label>
                                <select class="form-select" id="language" name="language">
                                    <option value="es" <?php echo ($user['language'] ?? '') === 'es' ? 'selected' : ''; ?>>Español</option>
                                    <option value="en" <?php echo ($user['language'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                        </div>

                        <!-- Notificaciones -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-bell text-secondary me-2"></i>Notificaciones
                                </h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="notifications_email" name="notifications_email" 
                                           <?php echo ($user['notifications_email'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications_email">
                                        Recibir notificaciones por email
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notifications_sms" name="notifications_sms" 
                                           <?php echo ($user['notifications_sms'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications_sms">
                                        Recibir notificaciones por SMS
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Último acceso: <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?>
                                </small>
                            </div>
                            <div>
                                <a href="companies/" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-completar nombre completo basado en first_name y last_name
        document.addEventListener('DOMContentLoaded', function() {
            const firstName = document.getElementById('first_name');
            const lastName = document.getElementById('last_name');
            const fullName = document.getElementById('name');
            
            function updateFullName() {
                const first = firstName.value.trim();
                const last = lastName.value.trim();
                if (first || last) {
                    fullName.value = (first + ' ' + last).trim();
                }
            }
            
            firstName.addEventListener('input', updateFullName);
            lastName.addEventListener('input', updateFullName);
        });
    </script>
</body>
</html>
