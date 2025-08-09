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
            
        case 'create_employee_with_invitation':
            createEmployeeWithInvitation();
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
            
        case 'sync_salary_to_expenses':
            syncSalaryToExpenses();
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

// ============================================================================
// NUEVAS FUNCIONES PARA GESTIÓN DE INVITACIONES Y PERMISOS
// ============================================================================

/**
 * Crear empleado con sistema de invitaciones
 */
function createEmployeeWithInvitation() {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    if (!hasPermission('employees.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear empleados']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Datos del empleado
        $employee_data = [
            'employee_number' => trim($_POST['employee_number'] ?? ''),
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'fiscal_id' => trim($_POST['fiscal_id'] ?? ''),
            'hire_date' => $_POST['hire_date'],
            'department_id' => (int)$_POST['department_id'],
            'position_id' => (int)$_POST['position_id'],
            'employment_type' => $_POST['employment_type'],
            'contract_type' => $_POST['contract_type'],
            'salary' => (float)($_POST['salary'] ?? 0),
            'payment_frequency' => $_POST['payment_frequency'],
            'status' => $_POST['status'] ?? 'Activo',
            'notes' => trim($_POST['notes'] ?? ''),
            'company_id' => $company_id,
            'business_id' => $business_id,
            'unit_id' => $unit_id,
            'created_by' => $user_id
        ];
        
        // Verificar si el usuario ya existe
        require_once 'includes/invitation_system.php';
        $invitation_system = new InvitationSystem();
        $user_check = $invitation_system->checkExistingUser($employee_data['email']);
        
        // Crear registro de empleado
        $employee_id = createEmployeeRecord($employee_data);
        
        if (!$employee_id) {
            $db->rollback();
            echo json_encode(['error' => 'Error al crear empleado']);
            return;
        }
        
        // Sincronizar con gastos si está habilitado
        if (isset($_POST['sync_with_expenses']) && $_POST['sync_with_expenses'] === 'on') {
            syncEmployeeSalaryToExpenses($employee_id, $employee_data);
        }
        
        $result = ['success' => true, 'employee_id' => $employee_id];
        
        // Gestionar usuario del sistema
        if (isset($_POST['create_user_account']) && $_POST['create_user_account'] === 'on') {
            if ($user_check['exists']) {
                // Usuario existente - asignar a empresa
                $assign_result = $invitation_system->assignExistingUser(
                    $user_check['user']['id'], 
                    [
                        'company_id' => $company_id,
                        'business_id' => $business_id,
                        'unit_id' => $unit_id,
                        'role' => $_POST['role_template'] ?? 'user'
                    ]
                );
                
                if ($assign_result['success']) {
                    $result['user_action'] = 'assigned';
                    $result['message'] = 'Empleado creado y usuario existente asignado a la empresa';
                } else {
                    $result['user_warning'] = $assign_result['error'];
                }
            } else {
                // Nuevo usuario - crear invitación
                $invitation_data = array_merge($employee_data, [
                    'role' => $_POST['role_template'] ?? 'user',
                    'permissions' => $_POST['permissions'] ?? [],
                    'modules' => $_POST['modules'] ?? []
                ]);
                
                $invitation_result = $invitation_system->createInvitation($invitation_data);
                
                if ($invitation_result['success']) {
                    $result['user_action'] = 'invited';
                    $result['invitation_token'] = $invitation_result['token'];
                    
                    // Enviar email si está habilitado
                    if (isset($_POST['auto_send_invitation']) && $_POST['auto_send_invitation'] === 'on') {
                        $email_result = $invitation_system->sendInvitationEmail(
                            $employee_data['email'], 
                            $invitation_result['token'], 
                            $employee_data
                        );
                        
                        if ($email_result['success']) {
                            $result['email_sent'] = true;
                            $result['message'] = 'Empleado creado e invitación enviada por email';
                        } else {
                            $result['email_warning'] = 'Empleado creado pero error al enviar email';
                        }
                    } else {
                        $result['message'] = 'Empleado creado. Invitación generada (email no enviado)';
                    }
                } else {
                    $result['user_warning'] = 'Empleado creado pero error al generar invitación';
                }
            }
        } else {
            $result['message'] = 'Empleado creado (sin cuenta de usuario)';
        }
        
        $db->commit();
        echo json_encode($result);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error creating employee with invitation: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor']);
    }
}

/**
 * Crear registro de empleado (función auxiliar)
 */
function createEmployeeRecord($data) {
    global $db;
    
    $sql = "INSERT INTO employees (
        company_id, business_id, unit_id, employee_number,
        first_name, last_name, email, phone, fiscal_id,
        department_id, position_id, hire_date, employment_type, contract_type,
        salary, payment_frequency, status, notes, created_by, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $data['company_id'], $data['business_id'], $data['unit_id'], $data['employee_number'],
        $data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['fiscal_id'],
        $data['department_id'], $data['position_id'], $data['hire_date'], $data['employment_type'], $data['contract_type'],
        $data['salary'], $data['payment_frequency'], $data['status'], $data['notes'], $data['created_by']
    ]);
    
    return $result ? $db->lastInsertId() : false;
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
        
        $days = $frequency_days[$employee_data['payment_frequency']] ?? 30;
        $next_payment = date('Y-m-d', strtotime("+{$days} days"));
        
        $folio = generateExpenseFolio();
        
        $sql = "INSERT INTO expenses (
            company_id, business_id, unit_id, folio, amount, description,
            category, subcategory, provider_id, payment_date, status,
            origin, employee_id, recurring_days, next_recurring,
            created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'Nómina', 'Salarios', NULL, ?, 'pending', 'recurring', ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $company_id, $business_id, $unit_id, $folio, $employee_data['salary'],
            "Salario {$employee_data['payment_frequency']} - {$employee_data['first_name']} {$employee_data['last_name']}",
            $next_payment, $employee_id, $days, $next_payment, $user_id
        ]);
        
    } catch (Exception $e) {
        error_log("Error syncing salary to expenses: " . $e->getMessage());
        return false;
    }
}

/**
 * Generar folio para gastos
 */
function generateExpenseFolio() {
    global $db, $company_id;
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM expenses WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $count = $stmt->fetchColumn() + 1;
        
        return 'SAL-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return 'SAL-' . date('YmdHis');
    }
}

?>
