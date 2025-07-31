<?php
/**
 * Panel Root - Gestión de Empresas
 * Vista CRUD para administrar empresas del sistema
 */

require_once '../config.php';

// Verificar autenticación y permisos de root
if (!checkRole(['root'])) {
    header('Location: ' . BASE_URL . 'auth/index.php?error=access_denied');
    exit();
}

$pdo = getDB();

// Obtener todas las empresas con información relacionada
try {
    $stmt = $pdo->query("
        SELECT 
            c.*,
            p.name as plan_name,
            p.price_monthly,
            u.name as created_by_name,
            COUNT(DISTINCT uc.user_id) as users_count,
            COUNT(DISTINCT un.id) as units_count,
            COUNT(DISTINCT b.id) as businesses_count
        FROM companies c 
        LEFT JOIN plans p ON c.plan_id = p.id
        LEFT JOIN users u ON c.created_by = u.id
        LEFT JOIN user_companies uc ON c.id = uc.company_id AND uc.status = 'active'
        LEFT JOIN units un ON c.id = un.company_id AND un.status = 'active'
        LEFT JOIN businesses b ON un.id = b.unit_id AND b.status = 'active'
        GROUP BY c.id 
        ORDER BY c.created_at DESC
    ");
    $companies = $stmt->fetchAll();
    
    // Obtener todos los planes para el selector
    $stmt = $pdo->query("SELECT id, name, price_monthly FROM plans WHERE is_active = 1 ORDER BY price_monthly ASC");
    $plans = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $companies = [];
    $plans = [];
    $error_message = "Error al cargar datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['companies_management'] ?> - <?= $lang['app_name'] ?></title>
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
        .company-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .company-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .plan-badge {
            background: #e3f2fd;
            color: #1976d2;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }
        .stats-badge {
            background: #f5f5f5;
            color: #666;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            margin: 0.125rem;
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
        .status-active {
            color: #28a745;
        }
        .status-inactive {
            color: #dc3545;
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
                        <a class="nav-link active" href="companies.php">
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
                            <i class="fas fa-building"></i>
                            <?= $lang['companies_management'] ?>
                        </h1>
                        <div>
                            <button type="button" class="btn btn-primary me-2" onclick="refreshData()">
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
                                        <i class="fas fa-building fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= count($companies) ?></h3>
                                            <small><?= $lang['total_companies'] ?></small>
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
                                            <h3 class="mb-0"><?= count(array_filter($companies, fn($c) => $c['status'] === 'active')) ?></h3>
                                            <small><?= $lang['active_companies'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-pause-circle fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= count(array_filter($companies, fn($c) => $c['status'] === 'inactive')) ?></h3>
                                            <small><?= $lang['inactive_companies'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= array_sum(array_column($companies, 'users_count')) ?></h3>
                                            <small>Usuarios Totales</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Empresas -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?= $lang['company_name'] ?></th>
                                            <th><?= $lang['current_plan'] ?></th>
                                            <th>Usuarios</th>
                                            <th>Unidades</th>
                                            <th>Negocios</th>
                                            <th><?= $lang['company_status'] ?></th>
                                            <th><?= $lang['company_created_at'] ?></th>
                                            <th><?= $lang['actions'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($companies)): ?>
                                            <?php foreach ($companies as $company): ?>
                                                <tr>
                                                    <td><?= $company['id'] ?></td>
                                                    <td>
                                                        <div>
                                                            <strong><?= htmlspecialchars($company['name']) ?></strong>
                                                            <?php if ($company['description']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($company['description']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($company['plan_name']): ?>
                                                            <span class="plan-badge">
                                                                <?= htmlspecialchars($company['plan_name']) ?>
                                                                <br><small>$<?= number_format($company['price_monthly'], 2) ?>/mes</small>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Sin plan</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="stats-badge">
                                                            <i class="fas fa-users"></i> <?= $company['users_count'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="stats-badge">
                                                            <i class="fas fa-building"></i> <?= $company['units_count'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="stats-badge">
                                                            <i class="fas fa-store"></i> <?= $company['businesses_count'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($company['status'] === 'active'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check"></i> Activa
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times"></i> Inactiva
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?= date('d/m/Y H:i', strtotime($company['created_at'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-info btn-action btn-sm" 
                                                                    onclick="viewCompany(<?= $company['id'] ?>)" 
                                                                    title="<?= $lang['view_company'] ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-primary btn-action btn-sm" 
                                                                    onclick="editCompany(<?= $company['id'] ?>)" 
                                                                    title="<?= $lang['edit_company'] ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-warning btn-action btn-sm" 
                                                                    onclick="changePlan(<?= $company['id'] ?>)" 
                                                                    title="<?= $lang['change_plan'] ?>">
                                                                <i class="fas fa-exchange-alt"></i>
                                                            </button>
                                                            <?php if ($company['status'] === 'active'): ?>
                                                                <button type="button" class="btn btn-secondary btn-action btn-sm" 
                                                                        onclick="toggleCompanyStatus(<?= $company['id'] ?>, 'inactive')" 
                                                                        title="<?= $lang['deactivate_company'] ?>">
                                                                    <i class="fas fa-pause"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-success btn-action btn-sm" 
                                                                        onclick="toggleCompanyStatus(<?= $company['id'] ?>, 'active')" 
                                                                        title="<?= $lang['activate_company'] ?>">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-danger btn-action btn-sm" 
                                                                    onclick="deleteCompany(<?= $company['id'] ?>, <?= $company['users_count'] ?>)" 
                                                                    title="<?= $lang['delete_company'] ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-building fa-3x mb-3"></i>
                                                    <br><?= $lang['no_companies_found'] ?>
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

    <!-- Modal Editar Empresa -->
    <?php include 'modals/modal_edit_company.php'; ?>

    <!-- Modal Cambiar Plan -->
    <?php include 'modals/modal_change_plan.php'; ?>

    <!-- Modal Ver Empresa -->
    <?php include 'modals/modal_view_company.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <script src="js/root_panel.js"></script>
    <script>
        // Variables globales para la página de empresas
        const availablePlans = <?= json_encode($plans) ?>;
    </script>
</body>
</html>
