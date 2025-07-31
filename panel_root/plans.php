<?php
/**
 * Panel Root - Gestión de Planes SaaS
 * Vista CRUD para administrar planes del sistema
 */

require_once '../config.php';

// Verificar autenticación y permisos de root
if (!checkRole(['root'])) {
    header('Location: ' . BASE_URL . 'auth/index.php?error=access_denied');
    exit();
}

$pdo = getDB();

// Obtener todos los planes
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               COUNT(c.id) as companies_count 
        FROM plans p 
        LEFT JOIN companies c ON p.id = c.plan_id 
        GROUP BY p.id 
        ORDER BY p.is_active DESC, p.price_monthly ASC
    ");
    $plans = $stmt->fetchAll();
} catch (PDOException $e) {
    $plans = [];
}

// Módulos disponibles
$availableModules = [
    'gastos' => 'Gestión de Gastos',
    'mantenimiento' => 'Mantenimiento',
    'servicio_cliente' => 'Servicio al Cliente',
    'compras' => 'Compras',
    'lavanderia' => 'Lavandería',
    'transfers' => 'Transfers',
    'kpis' => 'KPIs y Métricas',
    'reportes' => 'Reportes',
    'integraciones' => 'Integraciones',
    'api' => 'API Access'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['plans_management'] ?> - <?= $lang['app_name'] ?></title>
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
        .plan-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .plan-price {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .module-badge {
            background: #e9ecef;
            color: #495057;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            margin: 0.125rem;
            display: inline-block;
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
                        <a class="nav-link active" href="plans.php">
                            <i class="fas fa-layer-group"></i> <?= $lang['plans_management'] ?>
                        </a>
                        <a class="nav-link" href="companies.php">
                            <i class="fas fa-building"></i> <?= $lang['companies_management'] ?>
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Gestión de Usuarios
                        </a>
                        <a class="nav-link" href="modules.php">
                            <i class="fas fa-puzzle-piece"></i> Gestión de Módulos
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>
                            <i class="fas fa-layer-group"></i>
                            <?= $lang['plans_management'] ?>
                        </h1>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                            <i class="fas fa-plus"></i>
                            <?= $lang['add_new_plan'] ?>
                        </button>
                    </div>

                    <!-- Tabla de Planes -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?= $lang['plan_name'] ?></th>
                                            <th><?= $lang['monthly_price'] ?></th>
                                            <th>Usuarios</th>
                                            <th>Unidades</th>
                                            <th>Negocios</th>
                                            <th>Storage (MB)</th>
                                            <th>Módulos</th>
                                            <th>Empresas</th>
                                            <th><?= $lang['status'] ?></th>
                                            <th><?= $lang['actions'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($plans)): ?>
                                            <?php foreach ($plans as $plan): ?>
                                                <tr>
                                                    <td><?= $plan['id'] ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($plan['name']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($plan['description']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="text-success fw-bold">
                                                            $<?= number_format($plan['price_monthly'], 2) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $plan['users_max'] == -1 ? $lang['unlimited'] : $plan['users_max'] ?></td>
                                                    <td><?= $plan['units_max'] == -1 ? $lang['unlimited'] : $plan['units_max'] ?></td>
                                                    <td><?= $plan['businesses_max'] == -1 ? $lang['unlimited'] : $plan['businesses_max'] ?></td>
                                                    <td><?= $plan['storage_max_mb'] == -1 ? $lang['unlimited'] : number_format($plan['storage_max_mb']) ?></td>
                                                    <td>
                                                        <?php 
                                                        $modules = json_decode($plan['modules_included'], true) ?? [];
                                                        foreach ($modules as $module):
                                                            if (isset($availableModules[$module])):
                                                        ?>
                                                            <span class="module-badge"><?= $availableModules[$module] ?></span>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?= $plan['companies_count'] ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($plan['is_active']): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-primary btn-action btn-sm me-1" 
                                                                onclick="editPlan(<?= $plan['id'] ?>)" title="<?= $lang['edit'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-action btn-sm" 
                                                                onclick="deletePlan(<?= $plan['id'] ?>, <?= $plan['companies_count'] ?>)" 
                                                                title="<?= $lang['delete'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted py-4">
                                                    <?= $lang['no_plans_found'] ?>
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

    <!-- Modal Agregar Plan -->
    <?php include 'modals/modal_add_plan.php'; ?>

    <!-- Modal Editar Plan -->
    <?php include 'modals/modal_edit_plan.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <script src="js/root_panel.js"></script>
</body>
</html>
