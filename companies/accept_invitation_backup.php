<?php
/**
 * Página para aceptar invitaciones a empresas
 * Versión simplificada y robusta
 */

require_once '../config.php';

// Verificar que el usuario esté logueado
if (!checkAuth()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect('auth/index.php?error=login_required');
}

$token = $_GET['token'] ?? '';
$error = '';
$invitation = null;

if (empty($token)) {
    $error = $lang['invalid_token'] ?? 'Token de invitación no válido';
} else {
    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];
        
        // Obtener email del usuario actual
        $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_email = $stmt->fetchColumn();
        
        if ($user_email) {
            // Buscar la invitación
            $stmt = $db->prepare("
                SELECT ui.*, c.name as company_name 
                FROM user_invitations ui
                JOIN companies c ON ui.company_id = c.id
                WHERE ui.token = ? AND ui.status = 'pending' AND ui.email = ?
            ");
            $stmt->execute([$token, $user_email]);
            $invitation = $stmt->fetch();
            
            if (!$invitation) {
                $error = $lang['invitation_not_found'] ?? 'Invitación no encontrada o ya procesada';
            }
        } else {
            $error = 'Usuario no encontrado';
        }
        
    } catch (Exception $e) {
        error_log("Error cargando invitación: " . $e->getMessage());
        $error = $lang['error_processing_invitation'] ?? 'Error procesando la invitación. Intenta más tarde.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['accept_invitation'] ?? 'Aceptar Invitación'; ?> - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    </style>
</head>
<body>
    <div class="container">
        <div class="invitation-card">
            <div class="invitation-header">
                <div class="mb-3">
                    <i class="fas fa-envelope-open fa-3x"></i>
                </div>
                <h3 class="mb-0"><?php echo $lang['invitation_to_company'] ?? 'Invitación a Empresa'; ?></h3>
            </div>
            
            <div class="p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <div class="text-center">
                        <a href="../companies/" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i><?php echo $lang['back_to_companies'] ?? 'Volver a Empresas'; ?>
                        </a>
                    </div>
                <?php elseif ($invitation): ?>
                    <div class="text-center mb-4">
                        <h4 class="text-success mb-3">
                            <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($invitation['company_name']); ?>
                        </h4>
                        <p class="text-muted">
                            <?php echo $lang['invitation_message'] ?? 'Has sido invitado a unirte a esta empresa como'; ?>
                            <strong><?php echo htmlspecialchars($invitation['role'] ?? 'usuario'); ?></strong>
                        </p>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" onclick="processInvitation('accept')">
                            <i class="fas fa-check me-2"></i><?php echo $lang['accept_invitation'] ?? 'Aceptar Invitación'; ?>
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="processInvitation('reject')">
                            <i class="fas fa-times me-2"></i><?php echo $lang['reject_invitation'] ?? 'Rechazar'; ?>
                        </button>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <?php echo $lang['invitation_for'] ?? 'Invitación para'; ?>: <?php echo htmlspecialchars($invitation['email']); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processInvitation(action) {
            const token = '<?php echo htmlspecialchars($token); ?>';
            const actionText = action === 'accept' ? 'aceptando' : 'rechazando';
            
            // Deshabilitar botones
            document.querySelectorAll('button').forEach(btn => btn.disabled = true);
            
            // Mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + actionText + '...';
            
            fetch('invitation_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}_invitation&token=${token}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar éxito y redirigir
                    document.querySelector('.p-4').innerHTML = `
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle me-2"></i>
                            ${data.message}
                        </div>
                        <div class="text-center">
                            <a href="../companies/" class="btn btn-primary">
                                <i class="fas fa-building me-2"></i>Ir a Empresas
                            </a>
                        </div>
                    `;
                } else {
                    // Mostrar error
                    document.querySelector('.p-4').innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.error || 'Error procesando la invitación'}
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                <i class="fas fa-redo me-2"></i>Intentar de nuevo
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalText;
                document.querySelectorAll('button').forEach(btn => btn.disabled = false);
                alert('Error de conexión. Intenta de nuevo.');
            });
        }
    </script>
</body>
</html>
                ");
                $stmt->execute([$_SESSION['user_id'], $invitation['company_id']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $error = $lang['already_member'] ?? 'Ya eres miembro de esta empresa';
                    
                    // Marcar invitación como aceptada de todas formas
                    $stmt = $db->prepare("
                        UPDATE user_invitations 
                        SET status = 'accepted', accepted_date = NOW() 
                        WHERE token = ?
                    ");
                    $stmt->execute([$token]);
                } else {
                    // Procesar la invitación
                    if (isset($_POST['action'])) {
                        if ($_POST['action'] === 'accept') {
                            // Aceptar invitación
                            $db->beginTransaction();
                            
                            try {
                                // 1. Agregar usuario a la empresa
                                $stmt = $db->prepare("
                                    INSERT INTO user_companies (user_id, company_id, role, status, joined_at)
                                    VALUES (?, ?, ?, 'active', NOW())
                                ");
                                $stmt->execute([
                                    $_SESSION['user_id'],
                                    $invitation['company_id'],
                                    $invitation['role'] ?? 'user'
                                ]);
                                
                                // 2. Marcar invitación como aceptada
                                $stmt = $db->prepare("
                                    UPDATE user_invitations 
                                    SET status = 'accepted', accepted_date = NOW() 
                                    WHERE token = ?
                                ");
                                $stmt->execute([$token]);
                                
                                // 3. Crear notificación para quien invitó
                                if ($invitation['sent_by']) {
                                    $stmt = $db->prepare("
                                        INSERT INTO notifications (user_id, company_id, type, title, message, icon, color, status, created_at)
                                        VALUES (?, ?, 'user_joined', 'Usuario se unió', ?, 'fas fa-user-check', 'success', 'pending', NOW())
                                    ");
                                    $stmt->execute([
                                        $invitation['sent_by'],
                                        $invitation['company_id'],
                                        "El usuario {$current_user_email} aceptó la invitación y se unió a {$invitation['company_name']}"
                                    ]);
                                }
                                
                                $db->commit();
                                $success = ($lang['successfully_joined'] ?? "¡Te has unido exitosamente a") . " {$invitation['company_name']}!";
                                
                                // Redirigir después de 3 segundos
                                header("refresh:3;url=index.php");
                                
                            } catch (Exception $e) {
                                $db->rollback();
                                throw $e;
                            }
                            
                        } elseif ($_POST['action'] === 'reject') {
                            // Rechazar invitación
                            $stmt = $db->prepare("
                                UPDATE user_invitations 
                                SET status = 'rejected', accepted_date = NOW() 
                                WHERE token = ?
                            ");
                            $stmt->execute([$token]);
                            
                            $success = $lang['invitation_rejected'] ?? 'Invitación rechazada correctamente';
                            header("refresh:2;url=index.php");
                        }
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error procesando invitación: " . $e->getMessage());
        $error = $lang['error_processing_invitation'] ?? 'Error procesando la invitación. Intenta más tarde.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['accept_invitation'] ?? 'Aceptar Invitación'; ?> - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .invitation-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }
        .company-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #007bff, #6610f2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        .invitation-header {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <?php if ($error): ?>
                    <div class="card invitation-card">
                        <div class="card-body text-center p-4">
                            <div class="mb-4">
                                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="text-danger mb-3">Error</h3>
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Empresas
                            </a>
                        </div>
                    </div>
                    
                <?php elseif ($success): ?>
                    <div class="card invitation-card">
                        <div class="invitation-header text-center p-4">
                            <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
                            <h3 class="mt-3 mb-0">¡Éxito!</h3>
                        </div>
                        <div class="card-body text-center p-4">
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($success); ?></p>
                            <p class="small text-muted">Serás redirigido automáticamente...</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-building me-2"></i>Ir a Empresas
                            </a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="card invitation-card">
                        <div class="invitation-header text-center p-4">
                            <i class="fas fa-envelope-open" style="font-size: 3rem;"></i>
                            <h3 class="mt-3 mb-0">Invitación a Empresa</h3>
                        </div>
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="company-logo">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h4 class="text-primary mb-2"><?php echo htmlspecialchars($invitation['company_name']); ?></h4>
                                <?php if (!empty($invitation['invited_by_name'])): ?>
                                    <p class="text-muted">
                                        Invitado por: <strong><?php echo htmlspecialchars($invitation['invited_by_name']); ?></strong>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="alert alert-info text-center">
                                <h6><i class="fas fa-info-circle me-2"></i>Has sido invitado</h6>
                                <p class="mb-0">
                                    Se te ha invitado a unirte a <strong><?php echo htmlspecialchars($invitation['company_name']); ?></strong> 
                                    con el rol de <span class="badge bg-secondary"><?php echo htmlspecialchars($invitation['role'] ?? 'usuario'); ?></span>
                                </p>
                            </div>
                            
                            <form method="POST" class="text-center">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <button type="submit" name="action" value="accept" class="btn btn-success btn-lg me-md-2">
                                        <i class="fas fa-check me-2"></i>Aceptar Invitación
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-lg">
                                        <i class="fas fa-times me-2"></i>Rechazar
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Invitación enviada el <?php echo date('d/m/Y', strtotime($invitation['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="../" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Ir al Dashboard Principal
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
