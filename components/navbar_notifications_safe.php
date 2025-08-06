<?php
/**
 * Componente de Notificaciones Seguro
 * Versión que no causa errores en el sistema
 */

// Variables por defecto
$notification_count = 0;
$navbar_notifications = [];

// Intentar obtener notificaciones de manera segura
try {
    // Solo obtener notificaciones si tenemos lo necesario
    if (isset($_SESSION['user_id']) && function_exists('getDB')) {
        
        $db = getDB();
        $user_id = $_SESSION['user_id'];
        $total_notifications = [];
        
        // 1. Obtener invitaciones pendientes de la tabla user_invitations
        $stmt = $db->prepare("
            SELECT ui.*, c.name as company_name,
                   'invitation' as type,
                   'Invitación a empresa' as title,
                   CONCAT('Has sido invitado a unirte a la empresa ', c.name) as message,
                   'fas fa-envelope' as icon,
                   'warning' as color,
                   ui.created_at,
                   CONCAT('/companies/accept_invitation.php?token=', ui.token) as action_url
            FROM user_invitations ui
            JOIN companies c ON ui.company_id = c.id
            WHERE ui.email = (SELECT email FROM users WHERE id = ?) 
            AND ui.status = 'pending'
            ORDER BY ui.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $invitations = $stmt->fetchAll();
        $total_notifications = array_merge($total_notifications, $invitations);
        
        // 2. Obtener notificaciones de la tabla notifications si existe (sin columnas problemáticas)
        try {
            // Verificar si la columna action_url existe
            $columns_check = $db->query("SHOW COLUMNS FROM notifications LIKE 'action_url'")->fetch();
            $action_url_column = $columns_check ? 'n.action_url' : "'' as action_url";
            
            $stmt = $db->prepare("
                SELECT n.*, c.name as company_name,
                       n.type,
                       n.title,
                       n.message,
                       'fas fa-bell' as icon,
                       'primary' as color,
                       n.created_at,
                       $action_url_column
                FROM notifications n
                LEFT JOIN companies c ON n.company_id = c.id
                WHERE n.user_id = ? AND n.status = 'pending'
                ORDER BY n.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll();
            $total_notifications = array_merge($total_notifications, $notifications);
        } catch (Exception $e) {
            // Si la tabla notifications no existe o hay error, continuar
            error_log("Notifications table error: " . $e->getMessage());
        }
        
        // Ordenar todas las notificaciones por fecha
        usort($total_notifications, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $navbar_notifications = array_slice($total_notifications, 0, 10);
        $notification_count = count($navbar_notifications);
    }
    
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $notification_count = 0;
    $navbar_notifications = [];
    error_log("Error en notificaciones navbar: " . $e->getMessage());
}

/**
 * Función helper para mostrar tiempo transcurrido
 */
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Hace un momento';
        if ($time < 3600) return 'Hace ' . floor($time/60) . ' min';
        if ($time < 86400) return 'Hace ' . floor($time/3600) . ' h';
        if ($time < 2592000) return 'Hace ' . floor($time/86400) . ' días';
        
        return date('j M', strtotime($datetime));
    }
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
            <h6 class="mb-0"><?php echo $lang['notifications'] ?? 'Notificaciones'; ?></h6>
            <?php if ($notification_count > 0): ?>
                <small class="text-muted"><?= $notification_count ?> nueva<?= $notification_count > 1 ? 's' : '' ?></small>
            <?php endif; ?>
        </div>
        
        <?php if (empty($navbar_notifications)): ?>
            <div class="dropdown-item-text text-center py-3">
                <i class="fas fa-bell-slash text-muted mb-2" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0"><?php echo $lang['no_notifications'] ?? 'No hay notificaciones'; ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($navbar_notifications as $notification): ?>
                <div class="dropdown-item notification-item" 
                     onclick="handleNotificationClick('<?= $notification['id'] ?>', '<?= htmlspecialchars($notification['action_url'] ?? '') ?>')">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <div class="notification-icon bg-<?= $notification['color'] ?? 'primary' ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 35px; height: 35px; font-size: 0.8rem;">
                                <i class="<?= $notification['icon'] ?? 'fas fa-bell' ?>"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="notification-title mb-1"><?= htmlspecialchars($notification['title'] ?? 'Notificación') ?></h6>
                            <p class="notification-message mb-1 text-muted small">
                                <?= htmlspecialchars($notification['message'] ?? '') ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?= timeAgo($notification['created_at'] ?? date('Y-m-d H:i:s')) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
            <?php endforeach; ?>
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
        window.location.href = actionUrl;
    }
}
</script>
