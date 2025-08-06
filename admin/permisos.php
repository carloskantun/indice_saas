<?php
require_once '../config.php';

// Verificar autenticación y permisos de administración
if (!checkRole(['root', 'superadmin', 'admin'])) {
    redirect('/companies/');
}

$pdo = getDB();
$current_company = null;
$company_id = null;

// Para usuarios root, mostrar selector de empresa; para otros, usar empresa activa o requerir selección
if (checkRole(['root'])) {
    // Usuario root puede seleccionar cualquier empresa
    if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
        $company_id = (int)$_GET['company_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? AND status = 'active'");
        $stmt->execute([$company_id]);
        $current_company = $stmt->fetch();
        
        if ($current_company) {
            $_SESSION['current_company_id'] = $company_id;
            $_SESSION['current_company_name'] = $current_company['name'];
        }
    }
} else {
    // Para superadmin/admin, obtener empresa de la sesión o parámetro
    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : ($_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? null);
    
    if ($company_id) {
        // Verificar que el usuario tiene permisos en esta empresa
        $stmt = $pdo->prepare("
            SELECT c.*, uc.role 
            FROM companies c 
            INNER JOIN user_companies uc ON c.id = uc.company_id 
            WHERE c.id = ? AND uc.user_id = ? AND uc.role IN ('superadmin', 'admin') AND uc.status = 'active'
        ");
        $stmt->execute([$company_id, $_SESSION['user_id']]);
        $current_company = $stmt->fetch();
        
        if (!$current_company) {
            redirect('/companies/');
        }
        
        $_SESSION['current_company_id'] = $company_id;
        $_SESSION['current_company_name'] = $current_company['name'];
    } else {
        // No hay empresa seleccionada, redirigir a companies
        redirect('/companies/');
    }
}

// Obtener módulos disponibles (simulado por ahora)
$availableModules = [
    ['id' => 'orders', 'name' => 'Órdenes', 'description' => 'Gestión de órdenes y pedidos'],
    ['id' => 'maintenance', 'name' => 'Mantenimiento', 'description' => 'Control de mantenimiento de equipos'],
    ['id' => 'customer_service', 'name' => 'Servicio al Cliente', 'description' => 'Gestión de atención al cliente'],
    ['id' => 'expenses', 'name' => 'Gastos', 'description' => 'Control de gastos y finanzas'],
    ['id' => 'transfers', 'name' => 'Transferencias', 'description' => 'Gestión de transferencias'],
    ['id' => 'reports', 'name' => 'Reportes', 'description' => 'Generación de reportes y análisis'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['company_permissions']; ?> - <?php echo htmlspecialchars($current_company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .permission-matrix {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .permission-matrix th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            text-align: center;
        }
        .permission-matrix td {
            border: 1px solid #e9ecef;
            vertical-align: middle;
            text-align: center;
            padding: 10px;
        }
        .module-name {
            font-weight: 600;
            color: #495057;
        }
        .module-description {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="./">
                <i class="fas fa-cogs me-2"></i>Admin Panel
            </a>
            <span class="navbar-text text-white">
                <?php echo htmlspecialchars($current_company['name']); ?>
            </span>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../companies/">
                    <i class="fas fa-arrow-left me-1"></i>Volver a Empresas
                </a>
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-1">
                    <i class="fas fa-key text-primary me-2"></i>
                    <?php echo $lang['company_permissions']; ?>
                </h1>
                <p class="text-muted mb-0">
                    Gestiona los permisos detallados por usuario y módulo en <?php echo htmlspecialchars($current_company['name']); ?>
                </p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="loadPermissionMatrix()">
                    <i class="fas fa-sync me-2"></i>Actualizar Matriz
                </button>
                <button class="btn btn-success" onclick="showBulkActions()">
                    <i class="fas fa-layer-group me-2"></i>Acciones Masivas
                </button>
            </div>
        </div>

        <!-- User Selection -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Seleccionar Usuario</h5>
                    </div>
                    <div class="card-body">
                        <select class="form-select" id="userSelect" onchange="loadUserPermissions()">
                            <option value="">Selecciona un usuario para gestionar permisos</option>
                        </select>
                        <div class="mt-3" id="userInfo" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-primary text-white rounded-circle me-3" style="width: 40px; height: 40px;">
                                    <span id="userInitial"></span>
                                </div>
                                <div>
                                    <div class="fw-bold" id="userName"></div>
                                    <small class="text-muted" id="userRole"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-template me-2"></i>Plantillas de Rol</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="applyRoleTemplate('superadmin')" id="btnSuperAdmin">
                                <i class="fas fa-crown me-2"></i>Aplicar Plantilla SuperAdmin
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="applyRoleTemplate('admin')">
                                <i class="fas fa-user-shield me-2"></i>Aplicar Plantilla Admin
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="applyRoleTemplate('moderator')">
                                <i class="fas fa-user-edit me-2"></i>Aplicar Plantilla Moderador
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="applyRoleTemplate('user')">
                                <i class="fas fa-user me-2"></i>Aplicar Plantilla Usuario
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Matrix -->
        <div class="row" id="permissionsMatrix" style="display: none;">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-th me-2"></i>Matriz de Permisos
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-success" onclick="selectAllPermissions()">
                                <i class="fas fa-check-double me-1"></i>Todos
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="deselectAllPermissions()">
                                <i class="fas fa-times me-1"></i>Ninguno
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="saveUserPermissions()">
                                <i class="fas fa-save me-1"></i>Guardar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="30%">Módulo</th>
                                        <th width="15%" class="text-center">Ver</th>
                                        <th width="15%" class="text-center">Crear</th>
                                        <th width="15%" class="text-center">Editar</th>
                                        <th width="15%" class="text-center">Eliminar</th>
                                        <th width="10%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="permissionsTableBody">
                                    <!-- Se llena dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No User Selected Message -->
        <div class="row" id="noUserMessage">
            <div class="col">
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-user-slash fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted">Selecciona un usuario para ver y gestionar sus permisos</h5>
                    <p class="text-muted">
                        Los permisos se organizan por módulos del sistema y tipos de acción.
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div class="row" id="loadingState" style="display: none;">
            <div class="col">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5 class="text-muted">Cargando permisos...</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div class="modal fade" id="bulkActionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-layer-group me-2"></i>Acciones Masivas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Usuarios Seleccionados</label>
                            <select class="form-select" id="bulkUsersSelect" multiple size="6">
                                <!-- Se llena dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Módulos Seleccionados</label>
                            <select class="form-select" id="bulkModulesSelect" multiple size="6">
                                <!-- Se llena dinámicamente -->
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Permisos a Aplicar</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bulkView">
                                    <label class="form-check-label" for="bulkView">Ver</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bulkCreate">
                                    <label class="form-check-label" for="bulkCreate">Crear</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bulkEdit">
                                    <label class="form-check-label" for="bulkEdit">Editar</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bulkDelete">
                                    <label class="form-check-label" for="bulkDelete">Eliminar</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bulkOverwrite">
                                <label class="form-check-label" for="bulkOverwrite">
                                    Sobrescribir permisos existentes
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="applyBulkPermissions()">
                        <i class="fas fa-check me-2"></i>Aplicar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/admin_permissions.js"></script>
</body>
</html>
