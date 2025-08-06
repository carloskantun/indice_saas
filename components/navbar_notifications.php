<?php
/**
 * Componente de Notificaciones para Navbar
 * Muestra notificaciones en tiempo real en la barra de navegación
 */

require_once __DIR__ . '/../admin/notifications.php';

// Obtener notificaciones del usuario actual
$navbar_notifications = [];
$notification_count = 0;

if (isset($_SESSION['user_id'])) {
    $navbar_data = getNavbarNotifications($_SESSION['user_id']);
    $navbar_notifications = $navbar_data['notifications'];
    $notification_count = $navbar_data['count'];
}
?>

<!-- Dropdown de Notificaciones -->
<div class="nav-item dropdown me-3">
    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php if ($notification_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $notification_count > 9 ? '9+' : $notification_count ?>
                <span class="visually-hidden">notificaciones no leídas</span>
            </span>
        <?php endif; ?>
    </a>
    
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Notificaciones</h6>
            <?php if ($notification_count > 0): ?>
                <small class="text-muted"><?= $notification_count ?> nueva<?= $notification_count > 1 ? 's' : '' ?></small>
            <?php endif; ?>
        </div>
        
        <?php if (empty($navbar_notifications)): ?>
            <div class="dropdown-item-text text-center py-3">
                <i class="fas fa-bell-slash text-muted mb-2" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0">No hay notificaciones</p>
            </div>
        <?php else: ?>
            <?php foreach ($navbar_notifications as $notification): ?>
                <div class="dropdown-item notification-item" 
                     onclick="handleNotificationClick('<?= $notification['id'] ?>', '<?= htmlspecialchars($notification['action_url'] ?? '') ?>')">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <div class="notification-icon bg-<?= $notification['color'] ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 35px; height: 35px; font-size: 0.8rem;">
                                <i class="<?= $notification['icon'] ?>"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="notification-title mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                            <p class="notification-message mb-1 text-muted small">
                                <?= htmlspecialchars($notification['message']) ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?= timeAgo($notification['created_at']) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
            <?php endforeach; ?>
            
            <div class="dropdown-item text-center">
                <a href="/admin/notifications.php" class="btn btn-sm btn-outline-primary">
                    Ver todas las notificaciones
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-dropdown .dropdown-item {
    white-space: normal;
    padding: 0.75rem 1rem;
}

.notification-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-title {
    font-size: 0.9rem;
    font-weight: 600;
}

.notification-message {
    font-size: 0.85rem;
    line-height: 1.3;
}

.notification-icon {
    font-size: 0.9rem;
}

.badge {
    font-size: 0.6rem;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
function handleNotificationClick(notificationId, actionUrl) {
    if (actionUrl) {
        // Marcar como leída (futuro)
        // markNotificationAsRead(notificationId);
        
        // Redirigir a la acción
        window.location.href = actionUrl;
    }
}

// Función para actualizar notificaciones automáticamente
function refreshNotifications() {
    // TODO: Implementar actualización AJAX de notificaciones
    // fetch('/admin/notifications.php?action=get_notifications')
    //     .then(response => response.json())
    //     .then(data => updateNotificationDisplay(data));
}

// Actualizar cada 30 segundos
// setInterval(refreshNotifications, 30000);
</script>

<?php
/**
 * Función helper para mostrar tiempo transcurrido
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Hace un momento';
    if ($time < 3600) return 'Hace ' . floor($time/60) . ' min';
    if ($time < 86400) return 'Hace ' . floor($time/3600) . ' h';
    if ($time < 2592000) return 'Hace ' . floor($time/86400) . ' días';
    
    return date('j M', strtotime($datetime));
}
?>
