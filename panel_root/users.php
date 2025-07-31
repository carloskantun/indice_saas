<?php
/**
 * Panel Root - Gestión Global de Usuarios
 * Vista CRUD para administrar usuarios del sistema
 */

require_once '../config.php';

// Verificar autenticación y permisos de root
if (!checkRole(['root'])) {
    header('Location: ' . BASE_URL . 'auth/index.php?error=access_denied');
    exit();
}

$pdo = getDB();

// Obtener todos los usuarios con información relacionada
try {
    $stmt = $pdo->query("
        SELECT 
            u.*,
            COUNT(DISTINCT uc.company_id) as companies_count,
            GROUP_CONCAT(DISTINCT uc.role ORDER BY uc.role SEPARATOR ', ') as roles_list,
            MAX(uc.last_accessed) as last_access
        FROM users u 
        LEFT JOIN user_companies uc ON u.id = uc.user_id AND uc.status = 'active'
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
    
    // Obtener estadísticas por rol
    $stmt = $pdo->query("
        SELECT 
            uc.role,
            COUNT(DISTINCT uc.user_id) as count
        FROM user_companies uc
        WHERE uc.status = 'active'
        GROUP BY uc.role
        ORDER BY count DESC
    ");
    $roleStats = $stmt->fetchAll();
    
    // Obtener todas las empresas para el selector
    $stmt = $pdo->query("SELECT id, name FROM companies WHERE status = 'active' ORDER BY name");
    $companies = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $users = [];
    $roleStats = [];
    $companies = [];
    $error_message = "Error al cargar datos: " . $e->getMessage();
}

// Roles disponibles
$availableRoles = [
    'root' => 'Root',
    'support' => 'Soporte',
    'superadmin' => 'Superadministrador',
    'admin' => 'Administrador',
    'moderator' => 'Moderador',
    'user' => 'Usuario'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['users_management'] ?> - <?= $lang['app_name'] ?></title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            margin: 0.125rem;
            display: inline-block;
        }
        .role-root { background: #dc3545; color: white; }
        .role-support { background: #fd7e14; color: white; }
        .role-superadmin { background: #6f42c1; color: white; }
        .role-admin { background: #0d6efd; color: white; }
        .role-moderator { background: #198754; color: white; }
        .role-user { background: #6c757d; color: white; }
        
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
        .companies-list {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
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
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users"></i> <?= $lang['users_management'] ?>
                        </a>
                        <a class="nav-link" href="modules.php">
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
                            <i class="fas fa-users"></i>
                            <?= $lang['users_management'] ?>
                        </h1>
                        <div>
                            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus"></i> <?= $lang['add_new_user'] ?>
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
                                        <i class="fas fa-users fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= count($users) ?></h3>
                                            <small><?= $lang['total_users'] ?></small>
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
                                            <h3 class="mb-0"><?= count(array_filter($users, fn($u) => $u['status'] === 'active')) ?></h3>
                                            <small><?= $lang['active_users'] ?></small>
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
                                            <h3 class="mb-0"><?= count(array_filter($users, fn($u) => $u['status'] === 'inactive')) ?></h3>
                                            <small><?= $lang['inactive_users'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-crown fa-2x me-3"></i>
                                        <div>
                                            <h3 class="mb-0"><?= count(array_filter($roleStats, fn($r) => $r['role'] === 'root'))[0]['count'] ?? 0 ?></h3>
                                            <small><?= $lang['root_users'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas por rol -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-pie"></i>
                                        <?= $lang['users_by_role'] ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($roleStats as $roleStat): ?>
                                            <div class="col-md-2 text-center mb-2">
                                                <span class="role-badge role-<?= $roleStat['role'] ?>">
                                                    <?= $availableRoles[$roleStat['role']] ?? $roleStat['role'] ?>
                                                </span>
                                                <div class="mt-1">
                                                    <strong><?= $roleStat['count'] ?></strong> usuarios
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Usuarios -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Email</th>
                                            <th>Empresas</th>
                                            <th>Roles</th>
                                            <th><?= $lang['status'] ?></th>
                                            <th>Último Acceso</th>
                                            <th>Registro</th>
                                            <th><?= $lang['actions'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar me-3">
                                                                <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                                            </div>
                                                            <div>
                                                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                                <br><small class="text-muted">ID: <?= $user['id'] ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?= $user['companies_count'] ?> empresas</span>
                                                    </td>
                                                    <td class="companies-list">
                                                        <?php if ($user['roles_list']): ?>
                                                            <?php foreach (explode(', ', $user['roles_list']) as $role): ?>
                                                                <span class="role-badge role-<?= $role ?>">
                                                                    <?= $availableRoles[$role] ?? $role ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin roles</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['status'] === 'active'): ?>
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
                                                        <?php if ($user['last_access']): ?>
                                                            <small><?= date('d/m/Y H:i', strtotime($user['last_access'])) ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted"><?= $lang['never_accessed'] ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-info btn-action btn-sm" 
                                                                    onclick="viewUser(<?= $user['id'] ?>)" 
                                                                    title="<?= $lang['view_user'] ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-primary btn-action btn-sm" 
                                                                    onclick="editUser(<?= $user['id'] ?>)" 
                                                                    title="<?= $lang['edit_user'] ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-warning btn-action btn-sm" 
                                                                    onclick="manageUserRoles(<?= $user['id'] ?>)" 
                                                                    title="Gestionar Roles">
                                                                <i class="fas fa-user-cog"></i>
                                                            </button>
                                                            <?php if ($user['status'] === 'active'): ?>
                                                                <button type="button" class="btn btn-secondary btn-action btn-sm" 
                                                                        onclick="toggleUserStatus(<?= $user['id'] ?>, 'inactive')" 
                                                                        title="<?= $lang['deactivate_user'] ?>">
                                                                    <i class="fas fa-pause"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-success btn-action btn-sm" 
                                                                        onclick="toggleUserStatus(<?= $user['id'] ?>, 'active')" 
                                                                        title="<?= $lang['activate_user'] ?>">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-danger btn-action btn-sm" 
                                                                    onclick="deleteUser(<?= $user['id'] ?>, <?= $user['companies_count'] ?>)" 
                                                                    title="<?= $lang['delete_user'] ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="fas fa-users fa-3x mb-3"></i>
                                                    <br><?= $lang['no_users_found'] ?>
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
    <?php include 'modals/modal_add_user.php'; ?>
    <?php include 'modals/modal_edit_user.php'; ?>
    <?php include 'modals/modal_view_user.php'; ?>
    <?php include 'modals/modal_manage_user_roles.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <script src="js/root_panel.js"></script>
    <script>
        // Variables globales para la página de usuarios
        const availableRoles = <?= json_encode($availableRoles) ?>;
        const availableCompanies = <?= json_encode($companies) ?>;
    </script>
</body>
</html>
