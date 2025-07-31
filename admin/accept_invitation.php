<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceptar Invitación - Índice Producción</title>
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
            max-width: 500px;
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
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .invitation-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <?php
    require_once '../config.php';
    
    $token = $_GET['token'] ?? '';
    $invitation = null;
    $error_message = '';
    
    if ($token) {
        $pdo = getDB();
        
        // Verificar token válido
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as company_name, u.name as unit_name, b.name as business_name
            FROM invitations i
            LEFT JOIN companies c ON i.empresa_id = c.id
            LEFT JOIN units u ON i.unidad_id = u.id
            LEFT JOIN businesses b ON i.negocio_id = b.id
            WHERE i.token = ? AND i.status = 'pendiente' AND i.fecha_expiracion > NOW()
        ");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            $error_message = 'Invitación no válida o expirada';
        }
    } else {
        $error_message = 'Token de invitación requerido';
    }
    ?>

    <div class="container">
        <div class="invitation-card">
            <div class="invitation-header">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="mb-0">Índice Producción</h3>
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
                    <div class="text-center mb-4">
                        <h4 class="text-primary">
                            <i class="fas fa-envelope-open me-2"></i>
                            Has sido invitado
                        </h4>
                        <p class="text-muted">Completa tu registro para acceder al sistema</p>
                    </div>

                    <div class="invitation-info">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle me-2"></i> Detalles de la Invitación
                        </h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <small class="text-muted">Email:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['email']); ?></div>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted">Rol:</small>
                                <div class="fw-bold">
                                    <?php 
                                    $roles = [
                                        'superadmin' => '<i class="fas fa-crown text-primary"></i> Superadmin',
                                        'admin' => '<i class="fas fa-user-shield text-success"></i> Administrador',
                                        'moderator' => '<i class="fas fa-user-edit text-warning"></i> Moderador',
                                        'user' => '<i class="fas fa-user text-info"></i> Usuario'
                                    ];
                                    echo $roles[$invitation['rol']] ?? $invitation['rol'];
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <small class="text-muted">Empresa:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['company_name']); ?></div>
                            </div>
                            <?php if ($invitation['unit_name']): ?>
                            <div class="col-sm-6">
                                <small class="text-muted">Unidad:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['unit_name']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($invitation['business_name']): ?>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Negocio:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($invitation['business_name']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <form id="acceptInvitationForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
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

                        <div class="d-grid">
                            <button type="submit" class="btn btn-accept">
                                <i class="fas fa-check me-2"></i> Aceptar Invitación
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Esta invitación expira el <?php echo date('d/m/Y H:i', strtotime($invitation['fecha_expiracion'])); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('acceptInvitationForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
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
            
            const formData = new FormData(this);
            formData.append('action', 'accept_invitation');
            
            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Bienvenido!',
                        text: 'Tu cuenta ha sido creada exitosamente',
                        confirmButtonText: 'Ir al Sistema'
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
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la invitación'
                });
            }
        });
    </script>
</body>
</html>
