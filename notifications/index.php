<?php
session_start();
require_once '../config.php';
require_once '../includes/notifications.php';

// Verificar autenticación
if (!checkAuth()) {
    header("Location: /auth/index.php");
    exit();
}

// Crear tabla de notificaciones si no existe
createNotificationsTable();

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;

// Procesar aceptación de invitación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_invitation'])) {
    $notification_id = $_POST['notification_id'];
    $result = acceptInvitationFromNotification($notification_id, $user_id, ''); // Password no requerido para usuarios existentes
    
    if ($result['success']) {
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'success';
        // Recargar datos de empresas del usuario
        header("Location: /companies/index.php");
        exit();
    } else {
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'danger';
    }
}

// Marcar notificación como leída
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    markNotificationAsRead($notification_id, $user_id);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener notificaciones de todas las empresas del usuario
$db = getDB();
$stmt = $db->prepare("
    SELECT DISTINCT uc.company_id, c.name 
    FROM user_companies uc 
    JOIN companies c ON uc.company_id = c.id 
    WHERE uc.user_id = ?
");
$stmt->execute([$user_id]);
$user_companies = $stmt->fetchAll();

// Obtener invitaciones pendientes para el usuario actual
$stmt = $db->prepare("
    SELECT n.*, c.name as company_name 
    FROM notifications n
    JOIN companies c ON n.company_id = c.id
    WHERE n.user_id = ? 
    AND n.type = 'invitation' 
    AND n.status = 'pending'
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$pending_invitations = $stmt->fetchAll();

$page_title = "Notificaciones";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .notification-item:hover {
            background-color: #f8f9fa !important;
        }
        .notification-unread {
            border-left: 4px solid #0d6efd;
        }
        .notification-pending {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
                        <a class="navbar-brand" href="/">
                <i class="fas fa-chart-line me-2"></i>Índice SaaS
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/companies/index.php">
                    <i class="fas fa-building"></i> Empresas
                </a>
                <a class="nav-link" href="/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-bell"></i> Notificaciones</h1>
                </div>
            </div>
        </div>

        <!-- Invitaciones pendientes -->
        <?php if (!empty($pending_invitations)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-envelope"></i> Invitaciones Pendientes</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pending_invitations as $invitation): ?>
                            <?php 
                            $data = json_decode($invitation['data'], true);
                            $nivel_text = [1 => 'Usuario', 2 => 'Supervisor', 3 => 'Administrador'][$data['nivel']] ?? 'Usuario';
                            ?>
                            <div class="notification-item notification-pending border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-2">
                                            <i class="fas fa-building text-primary"></i>
                                            Invitación a <?php echo htmlspecialchars($invitation['company_name']); ?>
                                        </h6>
                                        <p class="mb-2">
                                            Has sido invitado a unirte como <strong><?php echo $nivel_text; ?></strong>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($invitation['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="notification_id" value="<?php echo $invitation['id']; ?>">
                                            <button type="submit" name="accept_invitation" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Aceptar Invitación
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notificaciones por empresa -->
        <?php foreach ($user_companies as $company): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-building"></i> 
                            <?php echo htmlspecialchars($company['name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $notifications = getNotifications($company['company_id'], $user_id, 20);
                        if (empty($notifications)):
                        ?>
                            <div class="text-muted text-center py-3">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No hay notificaciones para esta empresa
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php 
                                $isUnread = in_array($notification['status'], ['unread', 'pending']);
                                $data = json_decode($notification['data'], true);
                                ?>
                                <div class="notification-item <?php echo $isUnread ? 'notification-unread' : ''; ?> border-bottom py-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <h6 class="mb-0 me-2"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <?php if ($isUnread): ?>
                                                    <span class="badge bg-primary">Nuevo</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <?php if ($isUnread): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" name="mark_read" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-check"></i> Marcar como leída
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($user_companies) && empty($pending_invitations)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay notificaciones</h5>
                        <p class="text-muted">No tienes notificaciones en este momento.</p>
                        <a href="/companies/index.php" class="btn btn-primary">
                            <i class="fas fa-building"></i> Ver Empresas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
