<?php
require_once '../config.php';

$error = '';
$success = '';

if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $account_type = $_POST['account_type'] ?? 'free'; // 'superadmin' o 'free'
    $plan_id = (int)($_POST['plan_id'] ?? 1); // Plan gratuito por defecto
    $invitation_token = $_GET['invitation'] ?? ''; // Token de invitación para cuentas gratuitas
    
    // Validaciones
    if (empty($name) || empty($email) || empty($password)) {
        $error = $lang['complete_required_fields'] ?? 'Por favor completa todos los campos obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang['invalid_email'];
    } elseif (strlen($password) < 6) {
        $error = $lang['password_too_short'];
    } elseif ($password !== $confirm_password) {
        $error = $lang['passwords_dont_match'];
    } elseif ($account_type === 'free' && empty($invitation_token)) {
        $error = $lang['free_account_needs_invitation'] ?? 'Las cuentas gratuitas requieren invitación. Contacta a un administrador.';
    } elseif ($account_type === 'superadmin' && $plan_id == 1) {
        $error = $lang['superadmin_needs_paid_plan'] ?? 'Las cuentas Superadmin requieren un plan de pago.';
    } else {
        try {
            $db = getDB();
            
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = $lang['email_already_registered'] ?? 'Este correo electrónico ya está registrado';
            } else {
                // Lógica diferente según tipo de cuenta
                if ($account_type === 'free') {
                    // Verificar token de invitación
                    $stmt = $db->prepare("SELECT * FROM user_invitations WHERE token = ? AND email = ? AND status = 'pending' AND expiration_date > NOW()");
                    $stmt->execute([$invitation_token, $email]);
                    $invitation = $stmt->fetch();
                    
                    if (!$invitation) {
                        $error = $lang['invalid_invitation_token'] ?? 'Token de invitación inválido o expirado.';
                    } else {
                        // Crear usuario gratuito
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (name, email, password, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                        $stmt->execute([$name, $email, $hashedPassword]);
                        $user_id = $db->lastInsertId();
                        
                        // Asignar a la empresa de la invitación
                        $stmt = $db->prepare("INSERT INTO user_companies (user_id, company_id, role, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                        $stmt->execute([$user_id, $invitation['company_id'], $invitation['role']]);
                        
                        // Marcar invitación como aceptada
                        $stmt = $db->prepare("UPDATE user_invitations SET status = 'accepted', accepted_date = NOW() WHERE id = ?");
                        $stmt->execute([$invitation['id']]);
                        
                        $success = $lang['free_account_created_success'] ?? 'Cuenta gratuita creada exitosamente. Has sido asignado a la empresa como ' . $invitation['role'] . '.';
                    }
                } else {
                    // Cuenta Superadmin - requiere plan de pago
                    // Verificar que el plan existe
                    $stmt = $db->prepare("SELECT id, name FROM plans WHERE id = ? AND is_active = 1");
                    $stmt->execute([$plan_id]);
                    $selected_plan = $stmt->fetch();
                    
                    if (!$selected_plan) {
                        $error = $lang['invalid_plan_selected'] ?? 'Plan seleccionado no válido.';
                    } else {
                        // Crear usuario
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (name, email, password, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                        $stmt->execute([$name, $email, $hashedPassword]);
                        $user_id = $db->lastInsertId();
                        
                        // Crear empresa personal con el plan seleccionado
                        $company_name = "Empresa de " . $name;
                        $stmt = $db->prepare("INSERT INTO companies (name, description, status, created_by, plan_id, created_at) VALUES (?, ?, 'active', ?, ?, NOW())");
                        $stmt->execute([$company_name, 'Empresa personal creada automáticamente', $user_id, $plan_id]);
                        $company_id = $db->lastInsertId();
                        
                        // Asignar usuario como superadmin de su empresa
                        $stmt = $db->prepare("INSERT INTO user_companies (user_id, company_id, role, status, created_at) VALUES (?, ?, 'superadmin', 'active', NOW())");
                        $stmt->execute([$user_id, $company_id]);
                        
                        $success = $lang['superadmin_account_created'] ?? 'Cuenta Superadmin creada exitosamente con el plan ' . $selected_plan['name'] . '. Ahora puedes proceder al pago e iniciar sesión.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            $error = $lang['system_error'] ?? 'Error del sistema. Intenta más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['register_title']; ?> - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm mt-5">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h2 class="h4 text-primary"><?php echo $lang['app_name']; ?></h2>
                            <p class="text-muted"><?php echo $lang['register_title']; ?></p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <!-- Selector de tipo de cuenta -->
                            <div class="mb-4">
                                <label class="form-label">Tipo de Cuenta *</label>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card border-primary" id="card-superadmin">
                                            <div class="card-body text-center p-3">
                                                <input type="radio" class="form-check-input" id="superadmin" name="account_type" value="superadmin" 
                                                       <?php echo (($_POST['account_type'] ?? 'superadmin') === 'superadmin') ? 'checked' : ''; ?>>
                                                <label for="superadmin" class="form-check-label d-block mt-2">
                                                    <i class="fas fa-crown text-warning fa-2x d-block mb-2"></i>
                                                    <strong>Cuenta Superadmin</strong><br>
                                                    <small class="text-muted">Crea tu empresa con plan de pago</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card border-secondary" id="card-free">
                                            <div class="card-body text-center p-3">
                                                <input type="radio" class="form-check-input" id="free" name="account_type" value="free"
                                                       <?php echo (($_POST['account_type'] ?? '') === 'free') ? 'checked' : ''; ?>>
                                                <label for="free" class="form-check-label d-block mt-2">
                                                    <i class="fas fa-user text-info fa-2x d-block mb-2"></i>
                                                    <strong>Cuenta Gratuita</strong><br>
                                                    <small class="text-muted">Requiere invitación</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($invitation_token)): ?>
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-envelope-open me-2"></i>
                                        Tienes una invitación pendiente. Se creará una cuenta gratuita.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label"><?php echo $lang['name']; ?> *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo $lang['email']; ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo $lang['password']; ?> *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>
                            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar <?php echo $lang['password']; ?> *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <!-- Sección de planes - solo para cuentas superadmin -->
            <div class="mb-4" id="plan-section">
                <label for="plan_id" class="form-label">Seleccionar Plan *</label>
                <select class="form-select" id="plan_id" name="plan_id">
                    <?php
                    try {
                        $db = getDB();
                        $stmt = $db->prepare("SELECT id, name, description, price_monthly FROM plans WHERE is_active = 1 AND price_monthly > 0 ORDER BY price_monthly ASC");
                        $stmt->execute();
                        $plans = $stmt->fetchAll();
                        
                        foreach ($plans as $plan) {
                            $selected = (isset($_POST['plan_id']) && $_POST['plan_id'] == $plan['id']) ? 'selected' : '';
                            $price_text = '$' . number_format($plan['price_monthly'], 2) . '/mes';
                            echo "<option value='{$plan['id']}' $selected>{$plan['name']} - $price_text</option>";
                        }
                    } catch (Exception $e) {
                        echo "<option value='2'>Plan Starter - $29.99/mes</option>";
                    }
                    ?>
                </select>
                <div class="form-text">
                    <small id="plan-description" class="text-muted">Selecciona un plan de pago para tu empresa</small>
                </div>
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Después del registro podrás proceder al pago para activar tu plan.
                </div>
            </div>                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i><?php echo $lang['register']; ?>
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                ¿Ya tienes cuenta? <a href="index.php" class="text-decoration-none"><?php echo $lang['login']; ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const superadminRadio = document.getElementById('superadmin');
        const freeRadio = document.getElementById('free');
        const planSection = document.getElementById('plan-section');
        const planSelect = document.getElementById('plan_id');
        const cardSuperadmin = document.getElementById('card-superadmin');
        const cardFree = document.getElementById('card-free');
        
        // Función para actualizar la interfaz según el tipo de cuenta
        function updateAccountType() {
            if (superadminRadio.checked) {
                planSection.style.display = 'block';
                planSelect.required = true;
                cardSuperadmin.classList.remove('border-secondary');
                cardSuperadmin.classList.add('border-primary');
                cardFree.classList.remove('border-primary');
                cardFree.classList.add('border-secondary');
            } else {
                planSection.style.display = 'none';
                planSelect.required = false;
                cardFree.classList.remove('border-secondary');
                cardFree.classList.add('border-primary');
                cardSuperadmin.classList.remove('border-primary');
                cardSuperadmin.classList.add('border-secondary');
            }
        }
        
        // Event listeners para los radio buttons
        superadminRadio.addEventListener('change', updateAccountType);
        freeRadio.addEventListener('change', updateAccountType);
        
        // Verificar si hay token de invitación en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const hasInvitation = urlParams.has('invitation');
        
        if (hasInvitation) {
            freeRadio.checked = true;
            superadminRadio.disabled = true;
            cardSuperadmin.style.opacity = '0.5';
        }
        
        // Inicializar la interfaz
        updateAccountType();
        
        // Descripción de planes
        if (planSelect) {
            planSelect.addEventListener('change', function() {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                const description = document.getElementById('plan-description');
                if (description) {
                    description.textContent = 'Plan seleccionado: ' + selectedOption.text;
                }
            });
        }
    });
    </script>
</body>
</html>
