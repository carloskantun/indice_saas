<?php
/**
 * COMPONENTE DE BREADCRUMBS INTELIGENTES
 * Muestra navegación optimizada según el contexto del usuario
 */

require_once __DIR__ . '/../includes/smart_navigation.php';

function renderSmartBreadcrumbs($currentPage = '', $customBreadcrumbs = []) {
    if (!checkAuth()) {
        return '';
    }
    
    $smartNav = getSmartNavigation();
    $breadcrumbs = $smartNav->getOptimalBreadcrumbs();
    
    // Agregar breadcrumbs personalizados
    if (!empty($customBreadcrumbs)) {
        $breadcrumbs = array_merge($breadcrumbs, $customBreadcrumbs);
    }
    
    // Si no hay breadcrumbs suficientes, no mostrar nada
    if (count($breadcrumbs) < 1) {
        return '';
    }
    
    ob_start();
    ?>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-3 rounded">
            <li class="breadcrumb-item">
                <a href="/" class="text-decoration-none">
                    <i class="fas fa-home me-1"></i>Inicio
                </a>
            </li>
            
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index === count($breadcrumbs) - 1 && empty($currentPage)): ?>
                    <!-- Último elemento, sin enlace -->
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="<?php echo $crumb['icon']; ?> me-1"></i>
                        <?php echo htmlspecialchars($crumb['name']); ?>
                    </li>
                <?php else: ?>
                    <!-- Elementos intermedios, con enlace -->
                    <li class="breadcrumb-item">
                        <a href="<?php echo $crumb['url']; ?>" class="text-decoration-none">
                            <i class="<?php echo $crumb['icon']; ?> me-1"></i>
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (!empty($currentPage)): ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($currentPage); ?>
                </li>
            <?php endif; ?>
        </ol>
    </nav>
    <?php
    return ob_get_clean();
}

/**
 * Renderizar navegación rápida para usuarios con acceso directo
 */
function renderQuickNavigation() {
    if (!checkAuth()) {
        return '';
    }
    
    $smartNav = getSmartNavigation();
    
    // Solo mostrar para usuarios con acceso directo (empleados básicos)
    if (!$smartNav->hasDirectAccess()) {
        return '';
    }
    
    $userStructure = $smartNav->getUserStructure();
    if (empty($userStructure)) {
        return '';
    }
    
    $item = $userStructure[0];
    
    ob_start();
    ?>
    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-bolt me-2"></i>
        <div class="flex-grow-1">
            <strong>Acceso directo:</strong> 
            <?php echo htmlspecialchars($item['company_name']); ?> → 
            <?php echo htmlspecialchars($item['unit_name']); ?> → 
            <?php echo htmlspecialchars($item['business_name']); ?>
        </div>
        <a href="modules/?business_id=<?php echo $item['business_id']; ?>" 
           class="btn btn-sm btn-primary ms-2">
            <i class="fas fa-rocket me-1"></i>Ir a Módulos
        </a>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Obtener información de contexto actual
 */
function getCurrentContext() {
    return [
        'company_id' => $_SESSION['company_id'] ?? null,
        'company_name' => $_SESSION['company_name'] ?? '',
        'unit_id' => $_SESSION['unit_id'] ?? null,
        'unit_name' => $_SESSION['unit_name'] ?? '',
        'business_id' => $_SESSION['business_id'] ?? null,
        'business_name' => $_SESSION['business_name'] ?? '',
        'user_role' => $_SESSION['current_role'] ?? 'user'
    ];
}

/**
 * Verificar si mostrar breadcrumbs completos o simplificados
 */
function shouldShowFullBreadcrumbs() {
    $context = getCurrentContext();
    $role = $context['user_role'];
    
    // Admins y superadmins siempre ven breadcrumbs completos
    if (in_array($role, ['admin', 'superadmin', 'root'])) {
        return true;
    }
    
    // Usuarios básicos solo si están navegando múltiples niveles
    $smartNav = getSmartNavigation();
    $breadcrumbs = $smartNav->getOptimalBreadcrumbs();
    
    return count($breadcrumbs) > 1;
}
?>
