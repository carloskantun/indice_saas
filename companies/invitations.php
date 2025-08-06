<?php
session_start();
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    header("Location: /auth/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;

if (!$company_id) {
    header("Location: /companies/index.php");
    exit();
}

$db = getDB();

// Verificar permisos
$stmt = $db->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ?");
$stmt->execute([$user_id, $company_id]);
$user_role = $stmt->fetchColumn();

if (!in_array($user_role, ['superadmin', 'admin'])) {
    header("Location: /companies/index.php?error=no_permissions");
    exit();
}

// Obtener información de la empresa
$stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

// Obtener invitaciones de la empresa
$stmt = $db->prepare("
    SELECT i.*, u.name as inviter_name 
    FROM user_invitations i
    LEFT JOIN users u ON i.sent_by = u.id
    WHERE i.company_id = ? 
    ORDER BY i.sent_date DESC
    LIMIT 50
");
$stmt->execute([$company_id]);
$invitations = $stmt->fetchAll();

$page_title = "Gestionar Invitaciones";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/companies/">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($company['name']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/companies/">
                    <i class="fas fa-arrow-left"></i> Volver a Empresas
                </a>
                <a class="nav-link" href="/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-user-plus"></i> Gestionar Invitaciones</h1>
                        <p class="text-muted">Invita usuarios a unirse a tu empresa</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">
                        <i class="fas fa-plus"></i> Nueva Invitación
                    </button>
                </div>
            </div>
        </div>

        <!-- Estado de la empresa -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Estado de la empresa:</strong> 
                    Activa - Puedes enviar invitaciones a nuevos usuarios
                </div>
            </div>
        </div>

        <!-- Lista de invitaciones -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Invitaciones Enviadas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($invitations)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay invitaciones enviadas</h5>
                                <p class="text-muted">Crea tu primera invitación para empezar a invitar usuarios.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Enviada por</th>
                                            <th>Fecha</th>
                                            <th>Expira</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invitations as $invitation): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($invitation['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo ucfirst($invitation['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'accepted' => 'success',
                                                        'expired' => 'danger',
                                                        'cancelled' => 'secondary'
                                                    ][$invitation['status']] ?? 'secondary';
                                                    
                                                    $status_text = [
                                                        'pending' => 'Pendiente',
                                                        'accepted' => 'Aceptada',
                                                        'expired' => 'Expirada',
                                                        'cancelled' => 'Cancelada'
                                                    ][$invitation['status']] ?? 'Desconocido';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($invitation['inviter_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($invitation['sent_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $expires = strtotime($invitation['expiration_date']);
                                                    $now = time();
                                                    if ($expires > $now) {
                                                        echo date('d/m/Y', $expires);
                                                    } else {
                                                        echo '<span class="text-danger">Expirada</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($invitation['status'] === 'pending' && strtotime($invitation['expiration_date']) > time()): ?>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="copyInvitationLink('<?php echo $invitation['token']; ?>', '<?php echo urlencode($invitation['email']); ?>')">
                                                            <i class="fas fa-copy"></i> Copiar Enlace
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para nueva invitación -->
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Invitación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="inviteForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email del Usuario *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Seleccionar rol</option>
                                <option value="user">Usuario - Acceso básico</option>
                                <option value="moderator">Moderador - Gestión limitada</option>
                                <?php if ($user_role === 'superadmin'): ?>
                                <option value="admin">Administrador - Control completo</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Se creará un enlace que el usuario podrá usar para registrarse directamente en tu empresa.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Invitación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Manejar envío de invitación
    document.getElementById('inviteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        
        fetch('create_invitation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = `¡Invitación creada exitosamente!\n\n` +
                    `📧 Email: ${formData.get('email')}\n` +
                    `👤 Rol: ${data.role}\n` +
                    `🏢 Empresa: ${data.company_name}\n` +
                    `⏰ Expira en: ${data.expires_in}\n\n` +
                    `🔗 Enlace:\n${data.invitation_link}\n\n` +
                    `${data.instructions}`;
                    
                alert(message);
                bootstrap.Modal.getInstance(document.getElementById('inviteModal')).hide();
                this.reset();
                location.reload();
            } else {
                alert('❌ Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    // Copiar enlace de invitación
    function copyInvitationLink(token, email) {
        const link = `${window.location.origin}/auth/register.php?invitation=${token}&email=${email}`;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(() => {
                alert('Enlace copiado al portapapeles');
            });
        } else {
            // Fallback para navegadores más antiguos
            const textArea = document.createElement('textarea');
            textArea.value = link;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Enlace copiado al portapapeles');
        }
    }
    </script>
</body>
</html>
