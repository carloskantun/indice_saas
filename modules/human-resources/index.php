<?php
/**
 * MÓDULO RECURSOS HUMANOS - SISTEMA SAAS INDICE
 * Vista principal con funcionalidades de gestión de empleados
 * Basado en la plantilla del módulo de gastos
 */

require_once '../../config.php';

// Verificar autenticación y permisos
if (!checkAuth()) {
    redirect('auth/');
}

// Función para verificar permisos específicos
function hasPermission($permission) {
    if (!checkAuth()) {
        return false;
    }
    
    $role = $_SESSION['current_role'] ?? 'user';
    if (in_array($role, ['root', 'superadmin'])) {
        return true;
    }
    
    $permission_map = [
        'admin' => [
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'employees.export', 'employees.kpis', 'employees.bonuses', 'employees.attendance',
            'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
            'positions.view', 'positions.create', 'positions.edit', 'positions.delete'
        ],
        'moderator' => [
            'employees.view', 'employees.create', 'employees.edit', 'employees.bonuses', 'employees.attendance',
            'departments.view', 'positions.view'
        ],
        'user' => [
            'employees.view', 'departments.view', 'positions.view', 'employees.bonuses', 'employees.attendance'
        ]
    ];
    
    return in_array($permission, $permission_map[$role] ?? []);
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;
$unit_id = $_SESSION['unit_id'] ?? null;
$current_role = $_SESSION['current_role'] ?? 'user';

if (!$company_id || !$business_id || !$unit_id) {
    die('Error: Contexto de empresa/negocio requerido');
}

// Obtener filtros
$department_id = $_GET['department_id'] ?? '';
$position_id = $_GET['position_id'] ?? '';
$status = $_GET['status'] ?? '';
$employment_type = $_GET['employment_type'] ?? '';
$orden = $_GET['orden'] ?? 'hire_date';
$dir = strtoupper($_GET['dir'] ?? 'DESC');

// Construir WHERE clause
$where_conditions = ["e.company_id = ? AND e.business_id = ?"];
$params = [$company_id, $business_id];

if ($department_id) {
    $where_conditions[] = "e.department_id = ?";
    $params[] = $department_id;
}

if ($position_id) {
    $where_conditions[] = "e.position_id = ?";
    $params[] = $position_id;
}

if ($status) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status;
}

if ($employment_type) {
    $where_conditions[] = "e.employment_type = ?";
    $params[] = $employment_type;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Mapeo de columnas para ordenamiento
$order_map = [
    'employee_number' => 'e.employee_number',
    'name' => 'CONCAT(e.first_name, " ", e.last_name)',
    'department' => 'd.name',
    'position' => 'p.title',
    'hire_date' => 'e.hire_date',
    'salary' => 'e.salary',
    'status' => 'e.status'
];

$order_column = $order_map[$orden] ?? 'e.hire_date';
$direction = $dir === 'ASC' ? 'ASC' : 'DESC';

// Consulta principal para empleados
$sql = "SELECT 
    e.id,
    e.employee_number,
    CONCAT(e.first_name, ' ', e.last_name) AS full_name,
    e.first_name,
    e.last_name,
    e.email,
    e.phone,
    e.hire_date,
    e.employment_type,
    e.contract_type,
    e.salary,
    e.payment_frequency,
    e.status,
    d.name AS department_name,
    p.title AS position_title,
    u.name AS unit_name
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id AND d.company_id = ?
LEFT JOIN positions p ON e.position_id = p.id AND p.company_id = ?
LEFT JOIN units u ON e.unit_id = u.id AND u.company_id = ?
$where_clause
ORDER BY $order_column $direction";

// Agregar company_id para los LEFT JOIN
$params = array_merge([$company_id, $company_id, $company_id], $params);

$stmt = $db->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// KPIs
$kpi_total_sql = "SELECT COUNT(*) FROM employees WHERE company_id = ? AND business_id = ? AND status = 'Activo'";
$stmt = $db->prepare($kpi_total_sql);
$stmt->execute([$company_id, $business_id]);
$kpi_total = $stmt->fetchColumn();

$kpi_new_month_sql = "SELECT COUNT(*) FROM employees WHERE company_id = ? AND business_id = ? 
                      AND MONTH(hire_date) = MONTH(CURDATE()) AND YEAR(hire_date) = YEAR(CURDATE())";
$stmt = $db->prepare($kpi_new_month_sql);
$stmt->execute([$company_id, $business_id]);
$kpi_new_month = $stmt->fetchColumn();

// Departamentos para filtros
$departments_sql = "SELECT id, name FROM departments WHERE company_id = ? AND status = 'active' ORDER BY name";
$stmt = $db->prepare($departments_sql);
$stmt->execute([$company_id]);
$departments = $stmt->fetchAll();

// Posiciones para filtros
$positions_sql = "SELECT id, title FROM positions WHERE company_id = ? AND status = 'active' ORDER BY title";
$stmt = $db->prepare($positions_sql);
$stmt->execute([$company_id]);
$positions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recursos Humanos - <?php echo htmlspecialchars($_SESSION['business_name'] ?? 'SaaS'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link href="css/human-resources.css" rel="stylesheet">
    <style>
        .employee-row.active { background-color: #e8f5e8; }
        .employee-row.inactive { background-color: #ffebee; }
        .employee-row.vacation { background-color: #fff3e0; }
        .sortable-header { cursor: move; }
        .btn-group-toggle .btn { margin: 2px; }
        .table-responsive { max-height: 70vh; overflow-y: auto; }
        .sticky-header th { position: sticky; top: 0; background: white; z-index: 10; }
        .editable-campo { width: 100%; min-width: 100px; }
        
        /* Fix para Select2 en modales de Bootstrap */
        .select2-container {
            z-index: 10000 !important;
        }
        
        .select2-dropdown {
            z-index: 10001 !important;
        }
        
        .modal .select2-container {
            z-index: 10005 !important;
        }
        
        .modal .select2-dropdown {
            z-index: 10006 !important;
        }
        
        /* Evitar que el modal se cierre al hacer click en Select2 */
        .select2-container--open .select2-dropdown {
            z-index: 10007 !important;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.8rem;
            }
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            .btn-group .btn {
                margin-bottom: 0.5rem;
                border-radius: 0.375rem !important;
            }
            .card-body {
                padding: 1rem;
            }
            .filters-section {
                padding: 1rem;
            }
        }
        
        /* Column toggle styles */
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .col-toggle {
            margin-right: 0.5rem;
        }
        
        /* Card header improvements */
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .card-header h5 {
            color: #495057;
            font-weight: 600;
        }
        
        .card-header .badge {
            font-size: 0.8rem;
        }
        
        /* Table header improvements */
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        /* Button group spacing */
        .d-flex.gap-2 {
            gap: 0.5rem !important;
        }
        
        @media (max-width: 768px) {
            .d-flex.gap-2 {
                gap: 0.25rem !important;
            }
            
            .btn {
                font-size: 0.85rem;
                padding: 0.375rem 0.5rem;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <?php 
    // Breadcrumbs inteligentes
    require_once '../../components/smart_breadcrumbs.php';
    echo renderSmartBreadcrumbs('Recursos Humanos', [
        [
            'name' => 'Módulos', 
            'url' => '../', 
            'icon' => 'fas fa-th-large'
        ]
    ]);
    
    // Navegación rápida para usuarios básicos
    echo renderQuickNavigation();
    ?>
    
    <!-- Contenedor de alertas -->
    <div id="alertContainer"></div>
    
    <!-- Header con KPIs -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-users me-2 text-primary"></i>Recursos Humanos</h1>
            <p class="text-muted">Administra empleados, departamentos y posiciones</p>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Empleados Activos</h5>
                    <h3 class="text-primary"><?php echo number_format($kpi_total); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Nuevos este Mes</h5>
                    <h3 class="text-success"><?php echo number_format($kpi_new_month); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php if (hasPermission('employees.create')): ?>
                <button type="button" class="btn btn-success" id="btnNewEmployee" data-bs-toggle="modal" data-bs-target="#employeeModal">
                    <i class="fas fa-plus me-2"></i>Nuevo Empleado
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('departments.view')): ?>
                <button type="button" class="btn btn-info" id="btnDepartments" data-bs-toggle="modal" data-bs-target="#departmentModal">
                    <i class="fas fa-building me-2"></i>Departamentos
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('positions.view')): ?>
                <button type="button" class="btn btn-warning" id="btnPositions" data-bs-toggle="modal" data-bs-target="#positionModal">
                    <i class="fas fa-briefcase me-2"></i>Posiciones
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('employees.bonuses')): ?>
                <button type="button" class="btn btn-secondary" id="btnBonuses" data-bs-toggle="modal" data-bs-target="#bonusesModal">
                    <i class="fas fa-gift me-2"></i>Bonos
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('employees.attendance')): ?>
                <button type="button" class="btn btn-primary" id="btnAttendance" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                    <i class="fas fa-clock me-2"></i>Pase de Lista
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('employees.kpis')): ?>
                <button type="button" class="btn btn-outline-primary" id="btnKPIs" data-bs-toggle="modal" data-bs-target="#kpisModal">
                    <i class="fas fa-chart-pie me-2"></i>KPIs
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="email" checked> Email</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="phone" checked> Teléfono</label></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filtrosCollapse">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Departamento</label>
                        <select name="department_id" class="form-select">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Posición</label>
                        <select name="position_id" class="form-select">
                            <option value="">Todas las posiciones</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo $pos['id']; ?>" <?php echo $position_id == $pos['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estatus</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="Activo" <?php echo $status == 'Activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo $status == 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                            <option value="Vacaciones" <?php echo $status == 'Vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                            <option value="Licencia" <?php echo $status == 'Licencia' ? 'selected' : ''; ?>>Licencia</option>
                            <option value="Baja" <?php echo $status == 'Baja' ? 'selected' : ''; ?>>Baja</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo Empleo</label>
                        <select name="employment_type" class="form-select">
                            <option value="">Todos</option>
                            <option value="Tiempo_Completo" <?php echo $employment_type == 'Tiempo_Completo' ? 'selected' : ''; ?>>Tiempo Completo</option>
                            <option value="Medio_Tiempo" <?php echo $employment_type == 'Medio_Tiempo' ? 'selected' : ''; ?>>Medio Tiempo</option>
                            <option value="Temporal" <?php echo $employment_type == 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                            <option value="Freelance" <?php echo $employment_type == 'Freelance' ? 'selected' : ''; ?>>Freelance</option>
                            <option value="Practicante" <?php echo $employment_type == 'Practicante' ? 'selected' : ''; ?>>Practicante</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Empleados -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>Lista de Empleados 
                <span class="badge bg-primary ms-2"><?php echo count($employees); ?></span>
            </h5>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-columns me-2"></i>Columnas
                </button>
                <ul class="dropdown-menu">
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="employee_number" checked> Número</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="name" checked> Nombre</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="department" checked> Departamento</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="position" checked> Posición</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="hire_date" checked> Fecha Ingreso</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="employment_type" checked> Tipo Empleo</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="salary" checked> Salario</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="status" checked> Estatus</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="email" checked> Email</label></li>
                    <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="phone" checked> Teléfono</label></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="employeesTable">
                    <thead class="sticky-header">
                        <tr>
                            <th data-col="employee_number">
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['orden' => 'employee_number', 'dir' => $orden == 'employee_number' && $dir == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                    Número <?php if ($orden == 'employee_number') echo $dir == 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th data-col="name">
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['orden' => 'name', 'dir' => $orden == 'name' && $dir == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                    Nombre <?php if ($orden == 'name') echo $dir == 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th data-col="department">Departamento</th>
                            <th data-col="position">Posición</th>
                            <th data-col="hire_date">
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['orden' => 'hire_date', 'dir' => $orden == 'hire_date' && $dir == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                    Fecha Ingreso <?php if ($orden == 'hire_date') echo $dir == 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th data-col="employment_type">Tipo Empleo</th>
                            <th data-col="salary">
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['orden' => 'salary', 'dir' => $orden == 'salary' && $dir == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                    Salario <?php if ($orden == 'salary') echo $dir == 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th data-col="status">Estatus</th>
                            <th data-col="email">Email</th>
                            <th data-col="phone">Teléfono</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                        <?php 
                        $row_class = '';
                        switch(strtolower($employee['status'])) {
                            case 'activo': $row_class = 'active'; break;
                            case 'inactivo': 
                            case 'baja': $row_class = 'inactive'; break;
                            case 'vacaciones': 
                            case 'licencia': $row_class = 'vacation'; break;
                        }
                        ?>
                        <tr class="employee-row <?php echo $row_class; ?>" data-employee-id="<?php echo $employee['id']; ?>">
                            <td data-col="employee_number"><?php echo htmlspecialchars($employee['employee_number'] ?? 'N/A'); ?></td>
                            <td data-col="name"><?php echo htmlspecialchars($employee['full_name']); ?></td>
                            <td data-col="department"><?php echo htmlspecialchars($employee['department_name'] ?? 'Sin asignar'); ?></td>
                            <td data-col="position"><?php echo htmlspecialchars($employee['position_title'] ?? 'Sin asignar'); ?></td>
                            <td data-col="hire_date"><?php echo $employee['hire_date'] ? date('d/m/Y', strtotime($employee['hire_date'])) : 'N/A'; ?></td>
                            <td data-col="employment_type">
                                <span class="badge bg-info"><?php echo str_replace('_', ' ', $employee['employment_type']); ?></span>
                            </td>
                            <td data-col="salary">$<?php echo number_format($employee['salary'], 2); ?></td>
                            <td data-col="status">
                                <?php
                                $status_colors = [
                                    'Activo' => 'success',
                                    'Inactivo' => 'secondary',
                                    'Vacaciones' => 'warning',
                                    'Licencia' => 'info',
                                    'Baja' => 'danger'
                                ];
                                $color = $status_colors[$employee['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>"><?php echo $employee['status']; ?></span>
                            </td>
                            <td data-col="email"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></td>
                            <td data-col="phone"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission('employees.edit')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-employee" 
                                            data-employee-id="<?php echo $employee['id']; ?>"
                                            data-bs-toggle="tooltip" title="Editar empleado">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('employees.view')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info view-employee"
                                            data-employee-id="<?php echo $employee['id']; ?>"
                                            data-bs-toggle="tooltip" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('employees.delete') && $employee['status'] != 'Baja'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-employee"
                                            data-employee-id="<?php echo $employee['id']; ?>"
                                            data-employee-name="<?php echo htmlspecialchars($employee['full_name']); ?>"
                                            data-bs-toggle="tooltip" title="Dar de baja">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($employees)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron empleados</h5>
                <p class="text-muted">Intenta ajustar los filtros o agrega nuevos empleados.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Incluir modales aquí -->
<?php include 'modals.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/human-resources.js"></script>

<script>
// Variables globales para el contexto
window.companyId = <?php echo $company_id; ?>;
window.businessId = <?php echo $business_id; ?>;
window.unitId = <?php echo $unit_id; ?>;
window.currentRole = '<?php echo $current_role; ?>';

// Inicializar tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Funcionalidad de columnas
document.querySelectorAll('.col-toggle').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const col = this.dataset.col;
        const cells = document.querySelectorAll(`[data-col="${col}"]`);
        
        cells.forEach(function(cell) {
            cell.style.display = checkbox.checked ? '' : 'none';
        });
    });
});
</script>

</body>
</html>
