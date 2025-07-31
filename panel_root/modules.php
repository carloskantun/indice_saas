<?php
/**
 * Panel Root - Gestión de Módulos del Sistema
 * Vista CRUD para administrar módulos disponibles
 */

require_once '../config.php';

// Verificar autenticación y permisos de root
if (!checkRole(['root'])) {
    header('Location: ' . BASE_URL . 'auth/index.php?error=access_denied');
    exit();
}

$pdo = getDB();

// Obtener todos los módulos con estadísticas
try {
    $stmt = $pdo->query("
        SELECT 
            m.*,
            COUNT(DISTINCT pm.plan_id) as plans_using_count,
            GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') as plans_list
        FROM modules m 
        LEFT JOIN plan_modules pm ON m.id = pm.module_id
        LEFT JOIN plans p ON pm.plan_id = p.id AND p.status = 'active'
        GROUP BY m.id 
        ORDER BY m.name ASC
    ");
    $modules = $stmt->fetchAll();
    
    // Obtener estadísticas generales
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_modules,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_modules,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_modules
        FROM modules
    ");
    $moduleStats = $stmt->fetch();
    
    // Obtener planes activos para seleccionar
    $stmt = $pdo->query("SELECT id, name FROM plans WHERE status = 'active' ORDER BY name");
    $activePlans = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $modules = [];
    $moduleStats = ['total_modules' => 0, 'active_modules' => 0, 'inactive_modules' => 0];
    $activePlans = [];
    $error_message = "Error al cargar datos: " . $e->getMessage();
}

// Módulos predefinidos del sistema
$systemModules = [
    'gastos' => [
        'name' => 'Gastos',
        'slug' => 'gastos',
        'description' => 'Gestión y control de gastos empresariales',
        'icon' => 'fas fa-coins',
        'color' => '#e74c3c'
    ],
    'mantenimiento' => [
        'name' => 'Mantenimiento',
        'slug' => 'mantenimiento',
        'description' => 'Control de mantenimiento de equipos y vehículos',
        'icon' => 'fas fa-tools',
        'color' => '#f39c12'
    ],
    'servicio_cliente' => [
        'name' => 'Servicio al Cliente',
        'slug' => 'servicio_cliente',
        'description' => 'Gestión de tickets y atención al cliente',
        'icon' => 'fas fa-headset',
        'color' => '#3498db'
    ],
    'usuarios' => [
        'name' => 'Usuarios',
        'slug' => 'usuarios',
        'description' => 'Gestión de usuarios y permisos',
        'icon' => 'fas fa-users',
        'color' => '#9b59b6'
    ],
    'kpis' => [
        'name' => 'KPIs',
        'slug' => 'kpis',
        'description' => 'Indicadores clave de rendimiento',
        'icon' => 'fas fa-chart-line',
        'color' => '#27ae60'
    ],
    'compras' => [
        'name' => 'Compras',
        'slug' => 'compras',
        'description' => 'Gestión de compras y proveedores',
        'icon' => 'fas fa-shopping-cart',
        'color' => '#34495e'
    ],
    'lavanderia' => [
        'name' => 'Lavandería',
        'slug' => 'lavanderia',
        'description' => 'Control de servicios de lavandería',
        'icon' => 'fas fa-tshirt',
        'color' => '#1abc9c'
    ],
    'transfers' => [
        'name' => 'Transfers',
        'slug' => 'transfers',
        'description' => 'Gestión de servicios de transporte',
        'icon' => 'fas fa-bus',
        'color' => '#e67e22'
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['modules_management'] ?> - <?= $lang['app_name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #343a40;
            min-height: calc(100vh - 56px);
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #495057;
            color: #fff;
        }
        .module-card {
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .module-card.active {
            border-color: #28a745;
        }
        .module-card.inactive {
            border-color: #dc3545;
            opacity: 0.7;
        }
        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 1rem;
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 50%;
        }
        .color-picker {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
        }
        .module-preview {
            min-height: 100px;
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-crown text-warning"></i>
                <?= $lang['root_panel'] ?>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                        <?= $_SESSION['username'] ?? 'Root' ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> <?= $lang['logout'] ?>
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> <?= $lang['dashboard'] ?>
                        </a>
                        <a class="nav-link" href="plans.php">
                            <i class="fas fa-layer-group"></i> <?= $lang['plans_management'] ?>
                        </a>
                        <a class="nav-link" href="companies.php">
                            <i class="fas fa-building"></i> <?= $lang['companies_management'] ?>
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> <?= $lang['users_management'] ?>
                        </a>
                        <a class="nav-link active" href="modules.php">
                            <i class="fas fa-puzzle-piece"></i> <?= $lang['modules_management'] ?>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>
                            <i class="fas fa-puzzle-piece"></i>
                            <?= $lang['modules_management'] ?>
                        </h1>
                        <div>
                            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                                <i class="fas fa-plus"></i> <?= $lang['add_new_module'] ?>
                            </button>
                            <button type="button" class="btn btn-info me-2" onclick="syncSystemModules()">
                                <i class="fas fa-sync-alt"></i> Sincronizar Módulos
                            </button>
                            <button type="button" class="btn btn-primary" onclick="refreshData()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas rápidas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-puzzle-piece fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= $moduleStats['total_modules'] ?></h3>
                                            <small><?= $lang['total_modules'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= $moduleStats['active_modules'] ?></h3>
                                            <small><?= $lang['active_modules'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= $moduleStats['inactive_modules'] ?></h3>
                                            <small><?= $lang['inactive_modules'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-layer-group fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= count($activePlans) ?></h3>
                                            <small>Planes Activos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vista de tarjetas de módulos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-th-large"></i>
                                Vista de Módulos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if (!empty($modules)): ?>
                                    <?php foreach ($modules as $module): ?>
                                        <div class="col-md-3 mb-4">
                                            <div class="card module-card <?= $module['status'] ?>" onclick="viewModule(<?= $module['id'] ?>)">
                                                <div class="card-body text-center">
                                                    <div class="module-icon" style="background-color: <?= htmlspecialchars($module['color']) ?>;">
                                                        <i class="<?= htmlspecialchars($module['icon']) ?>"></i>
                                                    </div>
                                                    <h6 class="card-title"><?= htmlspecialchars($module['name']) ?></h6>
                                                    <p class="card-text small text-muted">
                                                        <?= htmlspecialchars(substr($module['description'], 0, 60)) ?>...
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge <?= $module['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                            <?= $module['status'] === 'active' ? 'Activo' : 'Inactivo' ?>
                                                        </span>
                                                        <small class="text-muted"><?= $module['plans_using_count'] ?> planes</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center text-muted py-5">
                                        <i class="fas fa-puzzle-piece fa-3x mb-3"></i>
                                        <h5><?= $lang['no_modules_found'] ?></h5>
                                        <p>Comience agregando módulos al sistema</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla detallada de módulos -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list"></i>
                                Lista Detallada de Módulos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Módulo</th>
                                            <th>Slug</th>
                                            <th>Descripción</th>
                                            <th>Planes</th>
                                            <th><?= $lang['status'] ?></th>
                                            <th>Creado</th>
                                            <th><?= $lang['actions'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($modules)): ?>
                                            <?php foreach ($modules as $module): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="module-icon me-3" style="width: 40px; height: 40px; font-size: 1rem; background-color: <?= htmlspecialchars($module['color']) ?>;">
                                                                <i class="<?= htmlspecialchars($module['icon']) ?>"></i>
                                                            </div>
                                                            <div>
                                                                <strong><?= htmlspecialchars($module['name']) ?></strong>
                                                                <br><small class="text-muted">ID: <?= $module['id'] ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><code><?= htmlspecialchars($module['slug']) ?></code></td>
                                                    <td class="text-truncate" style="max-width: 200px;">
                                                        <?= htmlspecialchars($module['description']) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($module['plans_using_count'] > 0): ?>
                                                            <span class="badge bg-info"><?= $module['plans_using_count'] ?> planes</span>
                                                            <br><small class="text-muted"><?= htmlspecialchars($module['plans_list']) ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin asignar</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($module['status'] === 'active'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check"></i> Activo
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times"></i> Inactivo
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?= date('d/m/Y', strtotime($module['created_at'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-info btn-action btn-sm" 
                                                                    onclick="viewModule(<?= $module['id'] ?>)" 
                                                                    title="<?= $lang['view_module'] ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-primary btn-action btn-sm" 
                                                                    onclick="editModule(<?= $module['id'] ?>)" 
                                                                    title="<?= $lang['edit_module'] ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($module['status'] === 'active'): ?>
                                                                <button type="button" class="btn btn-secondary btn-action btn-sm" 
                                                                        onclick="toggleModuleStatus(<?= $module['id'] ?>, 'inactive')" 
                                                                        title="<?= $lang['deactivate_module'] ?>">
                                                                    <i class="fas fa-pause"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-success btn-action btn-sm" 
                                                                        onclick="toggleModuleStatus(<?= $module['id'] ?>, 'active')" 
                                                                        title="<?= $lang['activate_module'] ?>">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-danger btn-action btn-sm" 
                                                                    onclick="deleteModule(<?= $module['id'] ?>, <?= $module['plans_using_count'] ?>)" 
                                                                    title="<?= $lang['delete_module'] ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    <i class="fas fa-puzzle-piece fa-3x mb-3"></i>
                                                    <br><?= $lang['no_modules_found'] ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <?php include 'modals/modal_add_module.php'; ?>
    <?php include 'modals/modal_edit_module.php'; ?>
    <?php include 'modals/modal_view_module.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <script src="js/root_panel.js"></script>
    <script>
        // Variables globales para la página de módulos
        const systemModules = <?= json_encode($systemModules) ?>;
        const activePlans = <?= json_encode($activePlans) ?>;
    </script>
</body>
</html>
