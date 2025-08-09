<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    redirect('auth/');
}

$business_id = $_GET['business_id'] ?? null;
if (!$business_id) {
    redirect('companies/');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Verificar que el usuario tiene acceso a este negocio
try {
    $stmt = $db->prepare("
        SELECT b.name as business_name, u.name as unit_name, c.name as company_name, 
               c.id as company_id, u.id as unit_id, uc.role 
        FROM businesses b 
        INNER JOIN units u ON b.unit_id = u.id
        INNER JOIN companies c ON u.company_id = c.id
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? AND b.id = ?
    ");
    $stmt->execute([$user_id, $business_id]);
    $businessData = $stmt->fetch();
    
    if (!$businessData) {
        redirect('companies/');
    }
    
    // Guardar en sesión
    $_SESSION['business_id'] = $business_id;
    $_SESSION['unit_id'] = $businessData['unit_id'];
    $_SESSION['company_id'] = $businessData['company_id'];
    $_SESSION['current_role'] = $businessData['role'];
    $_SESSION['business_name'] = $businessData['business_name'];
    $_SESSION['unit_name'] = $businessData['unit_name'];
    $_SESSION['company_name'] = $businessData['company_name'];
    
} catch (Exception $e) {
    redirect('companies/');
}

// Obtener módulos desde la base de datos
try {
    $stmt = $db->prepare("
        SELECT id, name, slug, description, url, icon, color, status
        FROM modules 
        WHERE status = 'active' 
        AND slug IN ('expenses', 'human-resources', 'mantenimiento', 'inventario', 'ventas', 'servicio_cliente')
        ORDER BY name ASC
    ");
    $stmt->execute();
    $modulesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir a formato compatible con el template actual
    $availableModules = [];
    foreach ($modulesList as $module) {
        // Verificar si el usuario tiene permisos para este módulo
        if (hasModuleAccess($module['slug'], $businessData['role'])) {
            // Limpiar URL para evitar duplicación de /modules/
            $cleanUrl = $module['url'];
            if (strpos($cleanUrl, '/modules/') === 0) {
                $cleanUrl = substr($cleanUrl, 9); // Remover '/modules/' del inicio
            }
            $cleanUrl = ltrim($cleanUrl, '/');
            
            $availableModules[] = [
                'id' => $module['slug'],
                'name' => $module['name'],
                'description' => $module['description'],
                'icon' => $module['icon'] ?: 'fas fa-puzzle-piece',
                'color' => getBootstrapColor($module['color']),
                'url' => $cleanUrl,
                'active' => true
            ];
        }
    }
    
} catch (Exception $e) {
    // Log del error para debug
    error_log("ERROR en modules/index.php - Consulta BD falló: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Fallback a módulos hardcodeados si hay error con la BD
    $availableModules = [
        [
            'id' => 'expenses',
            'name' => 'Gastos',
            'description' => 'Gestión de gastos e ingresos del negocio',
            'icon' => 'fas fa-money-bill-wave',
            'color' => 'success',
            'url' => 'expenses/',
            'active' => true
        ]
    ];
}

/**
 * Verificar si el usuario tiene acceso a un módulo
 */
function hasModuleAccess($moduleSlug, $userRole) {
    // Superadmin y root tienen acceso a todo
    if (in_array($userRole, ['root', 'superadmin', 'superadministrador'])) {
        return true;
    }
    
    // Configuración de acceso por módulo y rol
    $moduleAccess = [
        'expenses' => ['admin', 'moderator', 'user'],
        'human-resources' => ['admin', 'moderator'], // Admin y moderator tienen acceso completo
        'mantenimiento' => ['admin', 'moderator', 'user'],
        'inventario' => ['admin', 'moderator', 'user'],
        'ventas' => ['admin', 'moderator', 'user'],
        'servicio_cliente' => ['admin', 'moderator', 'user']
    ];
    
    $allowedRoles = $moduleAccess[$moduleSlug] ?? ['admin'];
    return in_array($userRole, $allowedRoles);
}

/**
 * Convertir color hex a clase Bootstrap
 */
function getBootstrapColor($hexColor) {
    $colorMap = [
        '#3498db' => 'primary',
        '#2ecc71' => 'success', 
        '#e74c3c' => 'danger',
        '#f39c12' => 'warning',
        '#9b59b6' => 'info',
        '#34495e' => 'secondary',
        '#1abc9c' => 'info'
    ];
    
    return $colorMap[$hexColor] ?? 'primary';
}

// Módulos hardcodeados como fallback (mantener por compatibilidad)
$fallbackModules = [
    [
        'id' => 'gastos',
        'name' => $lang['gastos'] ?? 'Gastos',
        'description' => 'Gestión de gastos e ingresos del negocio',
        'icon' => 'fas fa-money-bill-wave',
        'color' => 'success',
        'url' => 'expenses/',
        'active' => true
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['modules']; ?> - <?php echo htmlspecialchars($businessData['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../companies/">
                <i class="fas fa-building me-2"></i><?php echo $lang['app_name']; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="fas fa-arrow-right mx-2"></i><?php echo htmlspecialchars($businessData['company_name']); ?>
                            <i class="fas fa-arrow-right mx-2"></i><?php echo htmlspecialchars($businessData['unit_name']); ?>
                            <i class="fas fa-arrow-right mx-2"></i><?php echo htmlspecialchars($businessData['business_name']); ?>
                        </span>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../companies/">
                                <i class="fas fa-building me-2"></i><?php echo $lang['companies']; ?>
                            </a></li>
                            <li><a class="dropdown-item" href="../units/?company_id=<?php echo $businessData['company_id']; ?>">
                                <i class="fas fa-sitemap me-2"></i><?php echo $lang['units']; ?>
                            </a></li>
                            <li><a class="dropdown-item" href="../businesses/?unit_id=<?php echo $businessData['unit_id']; ?>">
                                <i class="fas fa-store me-2"></i><?php echo $lang['businesses']; ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i><?php echo $lang['logout']; ?>
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../companies/"><?php echo $lang['companies']; ?></a></li>
                        <li class="breadcrumb-item"><a href="../units/?company_id=<?php echo $businessData['company_id']; ?>"><?php echo $lang['units']; ?></a></li>
                        <li class="breadcrumb-item"><a href="../businesses/?unit_id=<?php echo $businessData['unit_id']; ?>"><?php echo $lang['businesses']; ?></a></li>
                        <li class="breadcrumb-item active"><?php echo $lang['modules']; ?></li>
                    </ol>
                </nav>
                <h1 class="h3 mb-1"><?php echo $lang['modules']; ?></h1>
                <p class="text-muted mb-0">Módulos funcionales para "<?php echo htmlspecialchars($businessData['business_name']); ?>"</p>
            </div>
        </div>

        <!-- Grilla de módulos -->
        <div class="row">
            <?php foreach ($availableModules as $module): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm module-card <?php echo $module['active'] ? '' : 'module-disabled'; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="bg-<?php echo $module['color']; ?> rounded-circle d-flex align-items-center justify-content-center module-icon" 
                                     style="width: 60px; height: 60px;">
                                    <i class="<?php echo $module['icon']; ?> text-white fa-lg"></i>
                                </div>
                                <?php if ($module['active']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Próximamente</span>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($module['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($module['description']); ?></p>
                            
                            <?php if ($module['active']): ?>
                                <div class="mt-auto">
                                    <a href="<?php echo $module['url']; ?>?business_id=<?php echo $business_id; ?>" 
                                       class="btn btn-<?php echo $module['color']; ?> w-100">
                                        <i class="fas fa-arrow-right me-2"></i>Acceder
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="mt-auto">
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        <i class="fas fa-lock me-2"></i>No disponible
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Información adicional -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle text-primary me-2"></i>Información del Negocio
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Empresa:</strong> <?php echo htmlspecialchars($businessData['company_name']); ?></p>
                                <p class="mb-2"><strong>Unidad:</strong> <?php echo htmlspecialchars($businessData['unit_name']); ?></p>
                                <p class="mb-0"><strong>Negocio:</strong> <?php echo htmlspecialchars($businessData['business_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Tu rol:</strong> <span class="badge bg-primary"><?php echo $lang[$businessData['role']]; ?></span></p>
                                <p class="mb-0"><strong>Módulos activos:</strong> <?php echo count(array_filter($availableModules, function($m) { return $m['active']; })); ?> de <?php echo count($availableModules); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/modules.js"></script>
</body>
</html>
