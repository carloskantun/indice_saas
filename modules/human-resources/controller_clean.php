<?php
/**
 * CONTROLADOR MÓDULO RECURSOS HUMANOS - SISTEMA SAAS INDICE
 * Versión limpia sin duplicaciones
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
    return in_array($permission, $allowed_permissions);
}

// Configuración de variables globales
$db = getDB();
$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'] ?? null;
$company_id = $_SESSION['company_id'] ?? null;
$unit_id = $_SESSION['unit_id'] ?? null;

if (!$business_id || !$company_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Contexto de negocio requerido']);
    exit();
}

// Enrutador principal
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
        case 'create_department':
            createDepartment();
            break;
        case 'get_departments':
            getDepartments();
            break;
        case 'create_position':
            createPosition();
            break;
        case 'get_positions':
            getPositions();
            break;
        case 'get_kpis':
            getKPIs();
            break;
        case 'export_csv':
            exportCSV();
            break;
        case 'create_employee_with_invitation':
            createEmployeeWithInvitation();
            break;
        case 'sync_salary':
            syncSalaryToExpenses();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    error_log("Error in HR controller: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

/**
 * Crear empleado tradicional
 */
function createEmployee() {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    if (!hasPermission('employees.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $position_id = (int)($_POST['position_id'] ?? 0);
    $salary = (float)($_POST['salary'] ?? 0);
    $hire_date = $_POST['hire_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    
    if (!$first_name || !$last_name || !$email) {
        echo json_encode(['error' => 'Campos requeridos: nombre, apellido, email']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO employees (first_name, last_name, email, phone, department_id, position_id, 
                                 salary, hire_date, status, company_id, business_id, unit_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $first_name, $last_name, $email, $phone, $department_id, $position_id,
            $salary, $hire_date, $status, $company_id, $business_id, $unit_id, $user_id
        ]);
        
        $employee_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Empleado creado exitosamente',
            'employee_id' => $employee_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al crear empleado: ' . $e->getMessage()]);
    }
}

/**
 * Editar empleado
 */
function editEmployee() {
    global $db, $company_id, $business_id, $unit_id;
    
    if (!hasPermission('employees.edit')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $position_id = (int)($_POST['position_id'] ?? 0);
    $salary = (float)($_POST['salary'] ?? 0);
    $hire_date = $_POST['hire_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    if (!$employee_id || !$first_name || !$last_name || !$email) {
        echo json_encode(['error' => 'Campos requeridos']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            UPDATE employees 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, department_id = ?, 
                position_id = ?, salary = ?, hire_date = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND company_id = ? AND business_id = ?
        ");
        
        $stmt->execute([
            $first_name, $last_name, $email, $phone, $department_id, $position_id,
            $salary, $hire_date, $status, $employee_id, $company_id, $business_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Empleado actualizado exitosamente'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al actualizar empleado: ' . $e->getMessage()]);
    }
}

/**
 * Eliminar empleado
 */
function deleteEmployee() {
    global $db, $company_id, $business_id;
    
    if (!hasPermission('employees.delete')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    
    if (!$employee_id) {
        echo json_encode(['error' => 'ID de empleado requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            DELETE FROM employees 
            WHERE id = ? AND company_id = ? AND business_id = ?
        ");
        $stmt->execute([$employee_id, $company_id, $business_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Empleado eliminado exitosamente'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al eliminar empleado: ' . $e->getMessage()]);
    }
}

/**
 * Obtener empleado específico
 */
function getEmployee() {
    global $db, $company_id, $business_id;
    
    if (!hasPermission('employees.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $employee_id = (int)($_GET['employee_id'] ?? 0);
    
    if (!$employee_id) {
        echo json_encode(['error' => 'ID de empleado requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT e.*, d.name as department_name, p.name as position_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE e.id = ? AND e.company_id = ? AND e.business_id = ?
        ");
        $stmt->execute([$employee_id, $company_id, $business_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee) {
            echo json_encode($employee);
        } else {
            echo json_encode(['error' => 'Empleado no encontrado']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener empleado: ' . $e->getMessage()]);
    }
}

/**
 * Crear departamento
 */
function createDepartment() {
    global $db, $company_id, $business_id, $user_id;
    
    if (!hasPermission('departments.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!$name) {
        echo json_encode(['error' => 'Nombre del departamento requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO departments (name, description, company_id, business_id, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $company_id, $business_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Departamento creado exitosamente',
            'department_id' => $db->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al crear departamento: ' . $e->getMessage()]);
    }
}

/**
 * Obtener departamentos
 */
function getDepartments() {
    global $db, $company_id, $business_id;
    
    if (!hasPermission('departments.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM departments 
            WHERE company_id = ? AND business_id = ?
            ORDER BY name
        ");
        $stmt->execute([$company_id, $business_id]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($departments);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener departamentos: ' . $e->getMessage()]);
    }
}

/**
 * Crear posición
 */
function createPosition() {
    global $db, $company_id, $business_id, $user_id;
    
    if (!hasPermission('positions.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    
    if (!$name) {
        echo json_encode(['error' => 'Nombre de la posición requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO positions (name, description, department_id, company_id, business_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $department_id, $company_id, $business_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Posición creada exitosamente',
            'position_id' => $db->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al crear posición: ' . $e->getMessage()]);
    }
}

/**
 * Obtener posiciones
 */
function getPositions() {
    global $db, $company_id, $business_id;
    
    if (!hasPermission('positions.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT p.*, d.name as department_name
            FROM positions p
            LEFT JOIN departments d ON p.department_id = d.id
            WHERE p.company_id = ? AND p.business_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$company_id, $business_id]);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($positions);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener posiciones: ' . $e->getMessage()]);
    }
}

/**
 * Obtener KPIs del módulo
 */
function getKPIs() {
    global $db, $company_id, $business_id;
    
    if (!hasPermission('employees.kpis')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    try {
        // Total empleados
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_employees FROM employees 
            WHERE company_id = ? AND business_id = ? AND status = 'active'
        ");
        $stmt->execute([$company_id, $business_id]);
        $total_employees = $stmt->fetchColumn();
        
        // Nuevos este mes
        $stmt = $db->prepare("
            SELECT COUNT(*) as new_this_month FROM employees 
            WHERE company_id = ? AND business_id = ? 
            AND hire_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $stmt->execute([$company_id, $business_id]);
        $new_this_month = $stmt->fetchColumn();
        
        // Total departamentos
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_departments FROM departments 
            WHERE company_id = ? AND business_id = ?
        ");
        $stmt->execute([$company_id, $business_id]);
        $total_departments = $stmt->fetchColumn();
        
        // Promedio salario
        $stmt = $db->prepare("
            SELECT AVG(salary) as avg_salary FROM employees 
            WHERE company_id = ? AND business_id = ? AND status = 'active' AND salary > 0
        ");
        $stmt->execute([$company_id, $business_id]);
        $avg_salary = $stmt->fetchColumn() ?: 0;
        
        echo json_encode([
            'total_employees' => (int)$total_employees,
            'new_this_month' => (int)$new_this_month,
            'total_departments' => (int)$total_departments,
            'avg_salary' => round($avg_salary, 2)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener KPIs: ' . $e->getMessage()]);
    }
}

/**
 * Exportar datos a CSV
 */
function exportCSV() {
    global $db, $company_id, $business_id;
    
    if (!hasPermission('employees.export')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT e.first_name, e.last_name, e.email, e.phone, 
                   d.name as department, p.name as position, 
                   e.salary, e.hire_date, e.status
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE e.company_id = ? AND e.business_id = ?
            ORDER BY e.first_name, e.last_name
        ");
        $stmt->execute([$company_id, $business_id]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = 'empleados_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Nombre', 'Apellido', 'Email', 'Teléfono', 'Departamento', 'Posición', 'Salario', 'Fecha Contratación', 'Estado']);
        
        // Data
        foreach ($employees as $employee) {
            fputcsv($output, $employee);
        }
        
        fclose($output);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al exportar: ' . $e->getMessage()]);
    }
}

/**
 * Crear empleado con invitación (NUEVO SISTEMA)
 */
function createEmployeeWithInvitation() {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    if (!hasPermission('employees.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    // Incluir sistema de invitaciones
    require_once 'includes/invitation_system.php';
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $position_id = (int)($_POST['position_id'] ?? 0);
    $salary = (float)($_POST['salary'] ?? 0);
    $hire_date = $_POST['hire_date'] ?? date('Y-m-d');
    $salary_frequency = $_POST['salary_frequency'] ?? 'Mensual';
    
    // Permisos del sistema
    $assigned_modules = $_POST['assigned_modules'] ?? [];
    $system_role = $_POST['system_role'] ?? 'user';
    
    if (!$first_name || !$last_name || !$email) {
        echo json_encode(['error' => 'Campos requeridos: nombre, apellido, email']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // 1. Crear registro de empleado
        $employee_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'department_id' => $department_id,
            'position_id' => $position_id,
            'salary' => $salary,
            'salary_frequency' => $salary_frequency,
            'hire_date' => $hire_date,
            'status' => 'active',
            'company_id' => $company_id,
            'business_id' => $business_id,
            'unit_id' => $unit_id,
            'created_by' => $user_id
        ];
        
        $employee_id = createEmployeeRecord($employee_data);
        
        // 2. Detectar si el usuario ya existe
        $existing_user = detectExistingUser($email);
        
        if ($existing_user) {
            // Usuario existe - vincular directamente
            $result = linkExistingUserToCompany($existing_user['id'], $company_id, $system_role, $assigned_modules);
            $message = "Empleado creado y vinculado a usuario existente";
        } else {
            // Usuario nuevo - crear invitación
            $invitation_data = [
                'employee_id' => $employee_id,
                'email' => $email,
                'company_id' => $company_id,
                'role' => $system_role,
                'modules' => $assigned_modules,
                'invited_by' => $user_id
            ];
            
            $result = createUserInvitation($invitation_data);
            $message = "Empleado creado e invitación enviada";
        }
        
        // 3. Sincronizar salario con gastos si tiene salario
        if ($salary > 0) {
            syncSalaryToExpenses($employee_id, $employee_data);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'employee_id' => $employee_id,
            'user_existed' => !empty($existing_user)
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error in createEmployeeWithInvitation: " . $e->getMessage());
        echo json_encode(['error' => 'Error al crear empleado: ' . $e->getMessage()]);
    }
}

/**
 * Crear registro de empleado en BD
 */
function createEmployeeRecord($data) {
    global $db;
    
    $stmt = $db->prepare("
        INSERT INTO employees (first_name, last_name, email, phone, department_id, position_id, 
                             salary, salary_frequency, hire_date, status, company_id, business_id, unit_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
        $data['department_id'], $data['position_id'], $data['salary'], $data['salary_frequency'],
        $data['hire_date'], $data['status'], $data['company_id'], $data['business_id'],
        $data['unit_id'], $data['created_by']
    ]);
    
    return $db->lastInsertId();
}

/**
 * Sincronizar salario con módulo de gastos
 */
function syncSalaryToExpenses($employee_id, $employee_data) {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    try {
        // Verificar si existe la tabla expenses
        $stmt = $db->query("SHOW TABLES LIKE 'expenses'");
        if (!$stmt->fetch()) {
            return false;
        }
        
        // Crear gasto recurrente de salario
        $frequency_days = [
            'Semanal' => 7,
            'Quincenal' => 15,
            'Mensual' => 30
        ];
        
        $days = $frequency_days[$employee_data['salary_frequency']] ?? 30;
        $next_payment = date('Y-m-d', strtotime("+$days days"));
        
        $stmt = $db->prepare("
            INSERT INTO expenses (folio, description, amount, expense_date, category, 
                                subcategory, payment_method, status, is_recurring, 
                                recurring_frequency, next_occurrence, employee_id,
                                company_id, business_id, unit_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $folio = generateExpenseFolio();
        $description = "Salario - " . $employee_data['first_name'] . " " . $employee_data['last_name'];
        
        return $stmt->execute([
            $folio, $description, $employee_data['salary'], date('Y-m-d'),
            'Gastos Operativos', 'Nómina', 'Transferencia', 'pending', 1,
            $employee_data['salary_frequency'], $next_payment, $employee_id,
            $company_id, $business_id, $unit_id, $user_id
        ]);
        
    } catch (Exception $e) {
        error_log("Error in syncSalaryToExpenses: " . $e->getMessage());
        return false;
    }
}

/**
 * Generar folio único para gastos
 */
function generateExpenseFolio() {
    global $db, $business_id;
    
    $prefix = 'EXP-' . date('Ymd') . '-';
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM expenses 
        WHERE folio LIKE ? AND business_id = ?
    ");
    $stmt->execute([$prefix . '%', $business_id]);
    $count = $stmt->fetchColumn();
    
    return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

/**
 * Función standalone para sync salary (API endpoint)
 */
function syncSalaryToExpenses() {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    if (!hasPermission('employees.edit')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos']);
        return;
    }
    
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    
    if (!$employee_id) {
        echo json_encode(['error' => 'ID de empleado requerido']);
        return;
    }
    
    try {
        // Obtener datos del empleado
        $stmt = $db->prepare("
            SELECT e.*, CONCAT(e.first_name, ' ', e.last_name) as full_name
            FROM employees e 
            WHERE e.id = ? AND e.company_id = ? AND e.business_id = ?
        ");
        $stmt->execute([$employee_id, $company_id, $business_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            echo json_encode(['error' => 'Empleado no encontrado']);
            return;
        }
        
        $result = syncSalaryToExpenses($employee_id, $employee);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Salario sincronizado con módulo de gastos'
            ]);
        } else {
            echo json_encode(['error' => 'Error al sincronizar salario']);
        }
        
    } catch (Exception $e) {
        error_log("Error in syncSalaryToExpenses API: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor']);
    }
}

?>
