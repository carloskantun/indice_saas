<?php
/**
 * Página de aceptación de invitaciones mejorada
 * Maneja tanto usuarios nuevos como existentes
 */

require_once '../config.php';
require_once 'db_connection.php';

$token = $_GET['token'] ?? '';
$invitation = null;
$error_message = '';
$existing_user = null;

if ($token) {
    try {
        $pdo = getDB();
        
        // Verificar invitación
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as company_name, u.name as unit_name, b.name as business_name
            FROM user_invitations i
            LEFT JOIN companies c ON i.company_id = c.id
            LEFT JOIN units u ON i.unit_id = u.id
            LEFT JOIN businesses b ON i.business_id = b.id
            WHERE i.token = ? AND i.status = 'pending' AND i.expiration_date > NOW()
        ");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch();
        
        if ($invitation) {
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$invitation['email']]);
            $existing_user = $stmt->fetch();
        } else {
            $error_message = 'Invitación no válida o expirada';
        }
    } catch (Exception $e) {
        $error_message = 'Error al procesar la invitación';
    }
} else {
    $error_message = 'Token de invitación requerido';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceptar Invitación - Índice SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .invitation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .invitation-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .invitation-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-accept {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .existing-user-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .new-user-badge {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="invitation-card">
            <div class="invitation-header">
                <div class="mb-3">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <h3 class="mb-0">Índice SaaS</h3>
                <p class="mb-0 opacity-75">Sistema de Gestión Empresarial</p>
            </div>
            
            <div class="invitation-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <div class="text-center">
                        <a href="../auth/" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Ir al Login
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Estado del usuario -->
                    <div class="text-center mb-4">
                        <?php if ($existing_user): ?>
                            <div class="existing-user-badge mb-3">
                                <i class="fas fa-user-check me-2"></i>Usuario Existente
                            </div>
                            <h4 class="text-success">
                                ¡Hola de nuevo, <?php echo htmlspecialchars($existing_user['name']); ?>!
                            </h4>
                            <p class="text-muted">Has sido invitado a una nueva empresa</p>
                        <?php else: ?>
                            <div class="new-user-badge mb-3">
                                <i class="fas fa-user-plus me-2"></i>Usuario Nuevo
                            </div>
                            <h4 class="text-primary">
                                <i class="fas fa-envelope-open me-2"></i>¡Bienvenido!
                            </h4>
                            <p class="text-muted">Completa tu registro para acceder al sistema</p>
                        <?php endif; ?>
                    </div>

                    <!-- Detalles de la invitación -->
                    <div class="invitation-info mb-4 p-3 bg-light rounded">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-info-circle me-2"></i> Detalles de la Invitación
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">Email:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['email']); ?></div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">Rol:</small>
                                <div class="fw-bold text-primary"><?php echo ucfirst($invitation['role']); ?></div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">Empresa:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['company_name']); ?></div>
                            </div>
                            <?php if ($invitation['unit_name']): ?>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">Unidad:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['unit_name']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($invitation['business_name']): ?>
                            <div class="col-md-12 mb-2">
                                <small class="text-muted">Negocio:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['business_name']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form id="acceptInvitationForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <?php if (!$existing_user): ?>
                            <!-- Campos para usuario nuevo -->
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i> Nombre Completo
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i> Contraseña
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>

                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label">
                                    <i class="fas fa-lock me-1"></i> Confirmar Contraseña
                                </label>
                                <input type="password" class="form-control" id="confirmPassword" required>
                            </div>
                        <?php else: ?>
                            <!-- Mensaje para usuario existente -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Como ya tienes una cuenta, solo necesitas confirmar tu aceptación para acceder a esta nueva empresa.
                            </div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-accept btn-lg">
                                <i class="fas fa-check me-2"></i>
                                <?php echo $existing_user ? 'Unirse a la Empresa' : 'Crear Cuenta y Unirse'; ?>
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Esta invitación expira el <?php echo date('d/m/Y H:i', strtotime($invitation['expiration_date'])); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const isExistingUser = <?php echo $existing_user ? 'true' : 'false'; ?>;
        
        document.getElementById('acceptInvitationForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validaciones para usuarios nuevos
            if (!isExistingUser) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const name = document.getElementById('name').value;
                
                if (!name.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'El nombre es requerido'
                    });
                    return;
                }
                
                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Las contraseñas no coinciden'
                    });
                    return;
                }
                
                if (password.length < 6) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'La contraseña debe tener al menos 6 caracteres'
                    });
                    return;
                }
            }
            
            // Mostrar loading
            Swal.fire({
                title: isExistingUser ? 'Uniéndote a la empresa...' : 'Creando tu cuenta...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                const formData = new FormData(this);
                formData.append('action', 'accept_invitation');
                
                const response = await fetch('controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '../auth/';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud'
                });
            }
        });
    </script>
</body>
</html>
