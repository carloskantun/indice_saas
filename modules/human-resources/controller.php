<?php
/**
 * CONTROLADOR MÓDULO RECURSOS HUMANOS - SISTEMA SAAS INDICE
 * Maneja todas las operaciones CRUD y API del módulo de recursos humanos
 */

require_once '../../config.php';

// Verificar autenticación
if (!checkAuth()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
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
            'employees.export', 'employees.kpis',
            'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
            'positions.view', 'positions.create', 'positions.edit', 'positions.delete'
        ],
        'moderator' => [
            'employees.view', 'employees.create', 'employees.edit',
            'departments.view', 'positions.view'
        ],
        'user' => [
            'employees.view', 'departments.view', 'positions.view'
        ]
    ];
    
    $allowed_permissions = $permission_map[$role] ?? [];
    $has_permission = in_array($permission, $allowed_permissions);
    
    // Log de debug para permisos
    error_log("Permission check: User role '$role' checking '$permission' - " . 
              ($has_permission ? 'GRANTED' : 'DENIED'));
    
    return $has_permission;
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;
$unit_id = $_SESSION['unit_id'] ?? null;

if (!$company_id || !$business_id || !$unit_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Contexto de empresa/negocio requerido']);
    exit();
}

try {
    switch ($action) {
        case 'create_employee':
            createEmployee();
            break;
            
        case 'edit_employee':
            editEmployee();
            break;
            
        case 'delete_employee':
            deleteEmployee();
            break;
            
        case 'get_employee':
            getEmployee();
            break;
            
        case 'get_kpis':
            getKPIs();
            break;
            
        case 'create_department':
            createDepartment();
            break;
            
        case 'edit_department':
            editDepartment();
            break;
            
        case 'delete_department':
            deleteDepartment();
            break;
            
        case 'get_departments':
            getDepartments();
            break;
            
        case 'create_position':
            createPosition();
            break;
            
        case 'edit_position':
            editPosition();
            break;
            
        case 'delete_position':
            deletePosition();
            break;
            
        case 'get_positions':
            getPositions();
            break;
            
        case 'generate_pdf':
            generateEmployeePDF();
            break;
            
        case 'export_csv':
            exportCSV();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("HR Controller Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

// ============================================================================
// FUNCIONES PARA EMPLEADOS
// ============================================================================

function createEmployee() {
    if (!hasPermission('employees.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear empleados']);
        return;
    }

    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    $required_fields = ['first_name', 'last_name', 'department_id', 'position_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['error' => "Campo requerido faltante: $field"]);
            return;
        }
    }

    $sql = "INSERT INTO employees (
        company_id, business_id, unit_id,
        employee_number, first_name, last_name, email, phone,
        department_id, position_id, hire_date, employment_type,
        contract_type, salary, payment_frequency, status,
        created_by, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $company_id, $business_id, $unit_id,
        $_POST['employee_number'] ?? null,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'] ?? null,
        $_POST['phone'] ?? null,
        $_POST['department_id'],
        $_POST['position_id'],
        $_POST['hire_date'] ?? date('Y-m-d'),
        $_POST['employment_type'] ?? 'Tiempo_Completo',
        $_POST['contract_type'] ?? 'Indefinido',
        $_POST['salary'] ?? 0,
        $_POST['payment_frequency'] ?? 'Mensual',
        $_POST['status'] ?? 'Activo',
        $user_id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Empleado creado exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al crear empleado']);
    }
}

function editEmployee() {
    if (!hasPermission('employees.edit')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para editar empleados']);
        return;
    }

    global $db, $company_id, $business_id;
    
    $employee_id = $_POST['employee_id'] ?? null;
    if (!$employee_id) {
        echo json_encode(['error' => 'ID de empleado requerido']);
        return;
    }

    $sql = "UPDATE employees SET 
        first_name = ?, last_name = ?, email = ?, phone = ?,
        department_id = ?, position_id = ?, employment_type = ?,
        contract_type = ?, salary = ?, payment_frequency = ?, status = ?,
        updated_at = NOW()
        WHERE id = ? AND company_id = ? AND business_id = ?";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'] ?? null,
        $_POST['phone'] ?? null,
        $_POST['department_id'],
        $_POST['position_id'],
        $_POST['employment_type'] ?? 'Tiempo_Completo',
        $_POST['contract_type'] ?? 'Indefinido',
        $_POST['salary'] ?? 0,
        $_POST['payment_frequency'] ?? 'Mensual',
        $_POST['status'] ?? 'Activo',
        $employee_id,
        $company_id,
        $business_id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Empleado actualizado exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar empleado']);
    }
}

function deleteEmployee() {
    if (!hasPermission('employees.delete')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para eliminar empleados']);
        return;
    }

    global $db, $company_id, $business_id;
    
    $employee_id = $_POST['employee_id'] ?? null;
    if (!$employee_id) {
        echo json_encode(['error' => 'ID de empleado requerido']);
        return;
    }

    // Soft delete - cambiar status a "Baja" en lugar de eliminar
    $sql = "UPDATE employees SET status = 'Baja', updated_at = NOW() 
            WHERE id = ? AND company_id = ? AND business_id = ?";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$employee_id, $company_id, $business_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Empleado dado de baja exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al dar de baja empleado']);
    }
}

function getEmployee() {
    if (!hasPermission('employees.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver empleados']);
        return;
    }

    global $db, $company_id, $business_id;
    
    $employee_id = $_GET['employee_id'] ?? null;
    if (!$employee_id) {
        echo json_encode(['error' => 'ID de empleado requerido']);
        return;
    }

    $sql = "SELECT e.*, d.name as department_name, p.title as position_title
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE e.id = ? AND e.company_id = ? AND e.business_id = ?";

    $stmt = $db->prepare($sql);
    $stmt->execute([$employee_id, $company_id, $business_id]);
    $employee = $stmt->fetch();

    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['error' => 'Empleado no encontrado']);
    }
}

// ============================================================================
// FUNCIONES PARA DEPARTAMENTOS
// ============================================================================

function createDepartment() {
    if (!hasPermission('departments.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear departamentos']);
        return;
    }

    global $db, $company_id, $business_id, $user_id;
    
    $name = $_POST['name'] ?? '';
    if (empty($name)) {
        echo json_encode(['error' => 'Nombre del departamento requerido']);
        return;
    }

    $sql = "INSERT INTO departments (company_id, business_id, name, description, manager_id, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $company_id, $business_id, $name,
        $_POST['description'] ?? null,
        $_POST['manager_id'] ?? null,
        $user_id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Departamento creado exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al crear departamento']);
    }
}

function getDepartments() {
    if (!hasPermission('departments.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver departamentos']);
        return;
    }

    global $db, $company_id, $business_id;

    $sql = "SELECT d.*, 
            CONCAT(e.first_name, ' ', e.last_name) as manager_name,
            (SELECT COUNT(*) FROM employees WHERE department_id = d.id AND status != 'Baja') as employee_count
            FROM departments d
            LEFT JOIN employees e ON d.manager_id = e.id
            WHERE d.company_id = ? AND d.business_id = ? AND d.status = 'active'
            ORDER BY d.name";

    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $business_id]);
    $departments = $stmt->fetchAll();

    echo json_encode(['success' => true, 'departments' => $departments]);
}

// ============================================================================
// FUNCIONES PARA POSICIONES
// ============================================================================

function createPosition() {
    if (!hasPermission('positions.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear posiciones']);
        return;
    }

    global $db, $company_id, $business_id, $user_id;
    
    $title = $_POST['title'] ?? '';
    $department_id = $_POST['department_id'] ?? null;
    
    if (empty($title) || empty($department_id)) {
        echo json_encode(['error' => 'Título y departamento requeridos']);
        return;
    }

    $sql = "INSERT INTO positions (company_id, business_id, department_id, title, description, min_salary, max_salary, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $company_id, $business_id, $department_id, $title,
        $_POST['description'] ?? null,
        $_POST['min_salary'] ?? 0,
        $_POST['max_salary'] ?? 0,
        $user_id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Posición creada exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al crear posición']);
    }
}

function getPositions() {
    if (!hasPermission('positions.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver posiciones']);
        return;
    }

    global $db, $company_id, $business_id;

    $department_id = $_GET['department_id'] ?? null;
    $where_clause = $department_id ? "AND p.department_id = ?" : "";
    $params = $department_id ? [$company_id, $business_id, $department_id] : [$company_id, $business_id];

    $sql = "SELECT p.*, d.name as department_name,
            (SELECT COUNT(*) FROM employees WHERE position_id = p.id AND status != 'Baja') as employee_count
            FROM positions p
            LEFT JOIN departments d ON p.department_id = d.id
            WHERE p.company_id = ? AND p.business_id = ? $where_clause AND p.status = 'active'
            ORDER BY d.name, p.title";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $positions = $stmt->fetchAll();

    echo json_encode(['success' => true, 'positions' => $positions]);
}

// ============================================================================
// FUNCIONES DE KPIs Y REPORTES
// ============================================================================

function getKPIs() {
    if (!hasPermission('employees.kpis')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver KPIs']);
        return;
    }

    global $db, $company_id, $business_id;

    // Total de empleados activos
    $sql = "SELECT COUNT(*) FROM employees WHERE company_id = ? AND business_id = ? AND status = 'Activo'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $business_id]);
    $total_employees = $stmt->fetchColumn();

    // Nuevos empleados este mes
    $sql = "SELECT COUNT(*) FROM employees WHERE company_id = ? AND business_id = ? 
            AND MONTH(hire_date) = MONTH(CURDATE()) AND YEAR(hire_date) = YEAR(CURDATE())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $business_id]);
    $new_employees_month = $stmt->fetchColumn();

    // Total nómina mensual
    $sql = "SELECT SUM(salary) FROM employees WHERE company_id = ? AND business_id = ? 
            AND status = 'Activo' AND payment_frequency = 'Mensual'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $business_id]);
    $total_payroll = $stmt->fetchColumn() ?? 0;

    // Distribución por departamentos
    $sql = "SELECT d.name, COUNT(e.id) as count 
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'Activo'
            WHERE d.company_id = ? AND d.business_id = ?
            GROUP BY d.id, d.name
            ORDER BY count DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $business_id]);
    $department_distribution = $stmt->fetchAll();

    // Empleados por status
    $sql = "SELECT status, COUNT(*) as count 
            FROM employees 
            WHERE company_id = ? AND business_id = ?
            GROUP BY status";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $business_id]);
    $status_distribution = $stmt->fetchAll();

    $kpis = [
        'total_employees' => $total_employees,
        'new_employees_month' => $new_employees_month,
        'total_payroll' => $total_payroll,
        'department_distribution' => $department_distribution,
        'status_distribution' => $status_distribution
    ];

    echo json_encode(['success' => true, 'kpis' => $kpis]);
}

function generateEmployeePDF() {
    if (!hasPermission('employees.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para generar PDF']);
        return;
    }

    // TODO: Implementar generación de PDF
    echo json_encode(['success' => true, 'message' => 'Función PDF en desarrollo']);
}

function exportCSV() {
    if (!hasPermission('employees.export')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para exportar']);
        return;
    }

    // TODO: Implementar exportación CSV
    echo json_encode(['success' => true, 'message' => 'Función CSV en desarrollo']);
}
?>
