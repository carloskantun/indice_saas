<?php
/*
 * CONTROLADOR MÓDULO PROCESOS Y TAREAS
 * Maneja todas las operaciones y lógica de negocio del módulo
 */

require_once '../../config.php';
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Obtener company_id del usuario actual
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? $_SESSION['current_company_id'] ?? 1;

// Si no está en sesión, obtenerlo de la base de datos
if (!$company_id || $company_id == 1) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT company_id FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        if ($user_data && isset($user_data['company_id'])) {
            $company_id = $user_data['company_id'];
        }
    } catch (Exception $e) {
        $company_id = 1; // Fallback
    }
}

// Verificar que tenemos una empresa válida
if (!$company_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No hay empresa seleccionada']);
    exit;
}

// Obtener conexión PDO global
$pdo = getDB();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Sistema de permisos del módulo
function hasModulePermission($permission) {
    if (!checkAuth()) return false;
    
    $role = $_SESSION['current_role'] ?? 'user';
    if (in_array($role, ['root', 'superadmin'])) return true;
    
    $permission_map = [
        'admin' => [
            'processes.view', 'processes.create', 'processes.edit', 'processes.delete',
            'processes.export', 'processes.kpis', 'processes.start', 'processes.pause',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete', 'tasks.assign',
            'tasks.complete', 'tasks.export', 'tasks.kpis', 'tasks.reassign',
            'workflows.view', 'workflows.create', 'workflows.edit', 'workflows.delete',
            'templates.view', 'templates.create', 'templates.edit', 'templates.delete',
            'reports.view', 'reports.export', 'automation.configure'
        ],
        'moderator' => [
            'processes.view', 'processes.create', 'processes.edit', 'processes.start',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.assign', 'tasks.complete',
            'workflows.view', 'workflows.create', 'workflows.edit',
            'templates.view', 'templates.create', 'reports.view'
        ],
        'user' => [
            'processes.view', 'tasks.view', 'tasks.edit', 'tasks.complete',
            'workflows.view', 'templates.view', 'reports.view'
        ]
    ];
    
    return in_array($permission, $permission_map[$role] ?? []);
}

try {
    switch ($action) {
        // ============ PROCESOS ============
        case 'get_processes':
            if (!hasModulePermission('processes.view')) {
                throw new Exception('Sin permisos para ver procesos');
            }
            getProcesses();
            break;
            
        case 'create_process':
            if (!hasModulePermission('processes.create')) {
                throw new Exception('Sin permisos para crear procesos');
            }
            createProcess();
            break;
            
        case 'update_process':
            if (!hasModulePermission('processes.edit')) {
                throw new Exception('Sin permisos para editar procesos');
            }
            updateProcess();
            break;
            
        case 'delete_process':
            if (!hasModulePermission('processes.delete')) {
                throw new Exception('Sin permisos para eliminar procesos');
            }
            deleteProcess();
            break;

        case 'start_process':
            if (!hasModulePermission('processes.start')) {
                throw new Exception('Sin permisos para iniciar procesos');
            }
            startProcess();
            break;

        case 'pause_process':
            if (!hasModulePermission('processes.pause')) {
                throw new Exception('Sin permisos para pausar procesos');
            }
            pauseProcess();
            break;

        // ============ TAREAS ============
        case 'get_tasks':
            if (!hasModulePermission('tasks.view')) {
                throw new Exception('Sin permisos para ver tareas');
            }
            getTasks();
            break;
            
        case 'create_task':
            if (!hasModulePermission('tasks.create')) {
                throw new Exception('Sin permisos para crear tareas');
            }
            createTask();
            break;
            
        case 'update_task':
            if (!hasModulePermission('tasks.edit')) {
                throw new Exception('Sin permisos para editar tareas');
            }
            updateTask();
            break;
            
        case 'delete_task':
            if (!hasModulePermission('tasks.delete')) {
                throw new Exception('Sin permisos para eliminar tareas');
            }
            deleteTask();
            break;

        case 'assign_task':
            if (!hasModulePermission('tasks.assign')) {
                throw new Exception('Sin permisos para asignar tareas');
            }
            assignTask();
            break;

        case 'complete_task':
            if (!hasModulePermission('tasks.complete')) {
                throw new Exception('Sin permisos para completar tareas');
            }
            completeTask();
            break;

        case 'update_task_progress':
            if (!hasModulePermission('tasks.edit')) {
                throw new Exception('Sin permisos para actualizar progreso');
            }
            updateTaskProgress();
            break;

        // ============ DATOS AUXILIARES ============
        case 'get_departments':
            getDepartments();
            break;
            
        case 'get_employees':
            getEmployees();
            break;

        case 'get_process_templates':
            if (!hasModulePermission('templates.view')) {
                throw new Exception('Sin permisos para ver plantillas');
            }
            getProcessTemplates();
            break;

        // ============ EXPORTACIÓN ============
        case 'export_processes':
            if (!hasModulePermission('processes.export')) {
                throw new Exception('Sin permisos para exportar procesos');
            }
            exportProcesses();
            break;

        case 'export_tasks':
            if (!hasModulePermission('tasks.export')) {
                throw new Exception('Sin permisos para exportar tareas');
            }
            exportTasks();
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ============ FUNCIONES DE PROCESOS ============

function getProcesses() {
    global $pdo, $company_id;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 25);
    $offset = ($page - 1) * $limit;
    
    // Filtros
    $filters = [];
    $params = [$company_id];
    
    $where_clause = "WHERE p.company_id = ?";
    
    if (!empty($_GET['status'])) {
        $where_clause .= " AND p.status = ?";
        $params[] = $_GET['status'];
    }
    
    if (!empty($_GET['priority'])) {
        $where_clause .= " AND p.priority = ?";
        $params[] = $_GET['priority'];
    }
    
    if (!empty($_GET['department_id'])) {
        $where_clause .= " AND p.department_id = ?";
        $params[] = $_GET['department_id'];
    }
    
    if (!empty($_GET['search'])) {
        $where_clause .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_term = '%' . $_GET['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Ordenamiento
    $order_by = "ORDER BY p.created_at DESC";
    if (!empty($_GET['sort'])) {
        $sort_field = $_GET['sort'];
        $sort_dir = $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';
        
        $allowed_sort = ['name', 'status', 'priority', 'created_at', 'updated_at'];
        if (in_array($sort_field, $allowed_sort)) {
            $order_by = "ORDER BY p.{$sort_field} {$sort_dir}";
        }
    }
    
    // Consulta principal
    $sql = "
        SELECT 
            p.*,
            d.name as department_name,
            CONCAT(u.first_name, ' ', u.last_name) as creator_name,
            (SELECT COUNT(*) FROM tasks t WHERE t.process_id = p.process_id) as total_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.process_id = p.process_id AND t.status = 'completed') as completed_tasks
        FROM processes p
        LEFT JOIN departments d ON p.department_id = d.department_id
        LEFT JOIN users u ON p.created_by = u.user_id
        {$where_clause}
        {$order_by}
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total para paginación
    $count_sql = "SELECT COUNT(*) FROM processes p {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // Calcular progreso para cada proceso
    foreach ($processes as &$process) {
        if ($process['total_tasks'] > 0) {
            $process['progress_percentage'] = round(($process['completed_tasks'] / $process['total_tasks']) * 100, 1);
        } else {
            $process['progress_percentage'] = 0;
        }
        
        // Formatear fechas
        $process['created_at_formatted'] = date('d/m/Y H:i', strtotime($process['created_at']));
        $process['updated_at_formatted'] = date('d/m/Y H:i', strtotime($process['updated_at']));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $processes,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => $total,
            'per_page' => $limit
        ]
    ]);
}

function createProcess() {
    global $pdo, $company_id;
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $priority = $_POST['priority'] ?? 'medium';
    $estimated_duration = (int)($_POST['estimated_duration'] ?? 0);
    
    if (empty($name)) {
        throw new Exception('El nombre del proceso es obligatorio');
    }
    
    $pdo->beginTransaction();
    
    try {
        $sql = "
            INSERT INTO processes (name, description, department_id, priority, estimated_duration, created_by, company_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $description,
            $department_id ?: null,
            $priority,
            $estimated_duration,
            $_SESSION['user_id'],
            $company_id
        ]);
        
        $process_id = $pdo->lastInsertId();
        
        // Si se proporcionaron pasos del proceso
        if (!empty($_POST['steps']) && is_array($_POST['steps'])) {
            foreach ($_POST['steps'] as $index => $step) {
                if (!empty($step['name'])) {
                    $step_sql = "
                        INSERT INTO process_steps (process_id, step_name, step_description, step_order, estimated_hours, responsible_role)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ";
                    $step_stmt = $pdo->prepare($step_sql);
                    $step_stmt->execute([
                        $process_id,
                        $step['name'],
                        $step['description'] ?? '',
                        $index + 1,
                        (int)($step['estimated_hours'] ?? 0),
                        $step['responsible_role'] ?? null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proceso creado exitosamente',
            'process_id' => $process_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

function updateProcess() {
    global $pdo, $company_id;
    
    $process_id = (int)($_POST['process_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'draft';
    $estimated_duration = (int)($_POST['estimated_duration'] ?? 0);
    
    if (!$process_id || empty($name)) {
        throw new Exception('Datos incompletos para actualizar el proceso');
    }
    
    // Verificar que el proceso pertenece a la empresa
    $check_sql = "SELECT process_id FROM processes WHERE process_id = ? AND company_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$process_id, $company_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Proceso no encontrado');
    }
    
    $sql = "
        UPDATE processes 
        SET name = ?, description = ?, department_id = ?, priority = ?, status = ?, estimated_duration = ?, updated_at = NOW()
        WHERE process_id = ? AND company_id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $description,
        $department_id ?: null,
        $priority,
        $status,
        $estimated_duration,
        $process_id,
        $company_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Proceso actualizado exitosamente'
    ]);
}

function deleteProcess() {
    global $pdo, $company_id;
    
    $process_id = (int)($_POST['process_id'] ?? 0);
    
    if (!$process_id) {
        throw new Exception('ID de proceso no válido');
    }
    
    // Verificar que el proceso pertenece a la empresa
    $check_sql = "SELECT process_id FROM processes WHERE process_id = ? AND company_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$process_id, $company_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Proceso no encontrado');
    }
    
    // Verificar si hay tareas asociadas
    $tasks_sql = "SELECT COUNT(*) FROM tasks WHERE process_id = ?";
    $tasks_stmt = $pdo->prepare($tasks_sql);
    $tasks_stmt->execute([$process_id]);
    $task_count = $tasks_stmt->fetchColumn();
    
    if ($task_count > 0) {
        throw new Exception('No se puede eliminar el proceso porque tiene tareas asociadas');
    }
    
    $pdo->beginTransaction();
    
    try {
        // Eliminar pasos del proceso
        $steps_sql = "DELETE FROM process_steps WHERE process_id = ?";
        $steps_stmt = $pdo->prepare($steps_sql);
        $steps_stmt->execute([$process_id]);
        
        // Eliminar proceso
        $sql = "DELETE FROM processes WHERE process_id = ? AND company_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$process_id, $company_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proceso eliminado exitosamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

// ============ FUNCIONES DE TAREAS ============

function getTasks() {
    global $pdo, $company_id;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 25);
    $offset = ($page - 1) * $limit;
    
    // Filtros
    $params = [$company_id];
    $where_clause = "WHERE t.company_id = ?";
    
    if (!empty($_GET['status'])) {
        $where_clause .= " AND t.status = ?";
        $params[] = $_GET['status'];
    }
    
    if (!empty($_GET['priority'])) {
        $where_clause .= " AND t.priority = ?";
        $params[] = $_GET['priority'];
    }
    
    if (!empty($_GET['assigned_to'])) {
        $where_clause .= " AND t.assigned_to = ?";
        $params[] = $_GET['assigned_to'];
    }
    
    if (!empty($_GET['department_id'])) {
        $where_clause .= " AND t.department_id = ?";
        $params[] = $_GET['department_id'];
    }
    
    if (!empty($_GET['process_id'])) {
        $where_clause .= " AND t.process_id = ?";
        $params[] = $_GET['process_id'];
    }
    
    // Filtro por vencimiento
    if (!empty($_GET['due_filter'])) {
        switch ($_GET['due_filter']) {
            case 'overdue':
                $where_clause .= " AND t.due_date < NOW() AND t.status NOT IN ('completed', 'cancelled')";
                break;
            case 'today':
                $where_clause .= " AND DATE(t.due_date) = CURDATE()";
                break;
            case 'week':
                $where_clause .= " AND t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where_clause .= " AND t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }
    
    // Filtro "Mis tareas"
    if (!empty($_GET['my_tasks']) && isset($_SESSION['employee_id'])) {
        $where_clause .= " AND t.assigned_to = ?";
        $params[] = $_SESSION['employee_id'];
    }
    
    if (!empty($_GET['search'])) {
        $where_clause .= " AND (t.title LIKE ? OR t.description LIKE ?)";
        $search_term = '%' . $_GET['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Ordenamiento
    $order_by = "ORDER BY t.created_at DESC";
    if (!empty($_GET['sort'])) {
        $sort_field = $_GET['sort'];
        $sort_dir = $_GET['dir'] === 'desc' ? 'DESC' : 'ASC';
        
        $allowed_sort = ['title', 'status', 'priority', 'due_date', 'created_at', 'completion_percentage'];
        if (in_array($sort_field, $allowed_sort)) {
            $order_by = "ORDER BY t.{$sort_field} {$sort_dir}";
        }
    }
    
    // Consulta principal
    $sql = "
        SELECT 
            t.*,
            p.name as process_name,
            d.name as department_name,
            CONCAT(e.first_name, ' ', e.last_name) as assigned_name,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_by_name,
            CASE 
                WHEN t.due_date < NOW() AND t.status NOT IN ('completed', 'cancelled') THEN 'overdue'
                WHEN DATE(t.due_date) = CURDATE() THEN 'due_today'
                WHEN t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'due_soon'
                ELSE 'normal'
            END as due_status
        FROM tasks t
        LEFT JOIN processes p ON t.process_id = p.process_id
        LEFT JOIN departments d ON t.department_id = d.department_id
        LEFT JOIN employees e ON t.assigned_to = e.employee_id
        LEFT JOIN users u ON t.assigned_by = u.user_id
        {$where_clause}
        {$order_by}
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total para paginación
    $count_sql = "SELECT COUNT(*) FROM tasks t {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // Formatear fechas y datos adicionales
    foreach ($tasks as &$task) {
        $task['created_at_formatted'] = date('d/m/Y H:i', strtotime($task['created_at']));
        $task['due_date_formatted'] = $task['due_date'] ? date('d/m/Y H:i', strtotime($task['due_date'])) : '';
        $task['updated_at_formatted'] = date('d/m/Y H:i', strtotime($task['updated_at']));
        
        if ($task['completed_at']) {
            $task['completed_at_formatted'] = date('d/m/Y H:i', strtotime($task['completed_at']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => $total,
            'per_page' => $limit
        ]
    ]);
}

function createTask() {
    global $pdo, $company_id;
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $process_id = (int)($_POST['process_id'] ?? 0) ?: null;
    $assigned_to = (int)($_POST['assigned_to'] ?? 0) ?: null;
    $priority = $_POST['priority'] ?? 'medium';
    $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $estimated_hours = !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null;
    
    if (empty($title)) {
        throw new Exception('El título de la tarea es obligatorio');
    }
    
    $sql = "
        INSERT INTO tasks (title, description, process_id, assigned_to, assigned_by, priority, department_id, due_date, estimated_hours, company_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        $description,
        $process_id,
        $assigned_to,
        $_SESSION['user_id'],
        $priority,
        $department_id,
        $due_date,
        $estimated_hours,
        $company_id
    ]);
    
    $task_id = $pdo->lastInsertId();
    
    // Registrar asignación si hay un empleado asignado
    if ($assigned_to) {
        $assignment_sql = "
            INSERT INTO task_assignments (task_id, assigned_to, assigned_by, reason)
            VALUES (?, ?, ?, 'Asignación inicial')
        ";
        $assignment_stmt = $pdo->prepare($assignment_sql);
        $assignment_stmt->execute([$task_id, $assigned_to, $_SESSION['user_id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tarea creada exitosamente',
        'task_id' => $task_id
    ]);
}

function updateTask() {
    global $pdo, $company_id;
    
    $task_id = (int)($_POST['task_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $estimated_hours = !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null;
    $completion_percentage = max(0, min(100, (int)($_POST['completion_percentage'] ?? 0)));
    
    if (!$task_id || empty($title)) {
        throw new Exception('Datos incompletos para actualizar la tarea');
    }
    
    // Verificar que la tarea pertenece a la empresa
    $check_sql = "SELECT task_id, status FROM tasks WHERE task_id = ? AND company_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$task_id, $company_id]);
    $current_task = $check_stmt->fetch();
    
    if (!$current_task) {
        throw new Exception('Tarea no encontrada');
    }
    
    // Si se marca como completada, actualizar fecha de completado
    $completed_at = null;
    if ($status === 'completed' && $current_task['status'] !== 'completed') {
        $completed_at = date('Y-m-d H:i:s');
        $completion_percentage = 100;
    } elseif ($status !== 'completed') {
        $completed_at = null;
    }
    
    $sql = "
        UPDATE tasks 
        SET title = ?, description = ?, priority = ?, status = ?, due_date = ?, 
            estimated_hours = ?, completion_percentage = ?, completed_at = ?, updated_at = NOW()
        WHERE task_id = ? AND company_id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        $description,
        $priority,
        $status,
        $due_date,
        $estimated_hours,
        $completion_percentage,
        $completed_at,
        $task_id,
        $company_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tarea actualizada exitosamente'
    ]);
}

function completeTask() {
    global $pdo, $company_id;
    
    $task_id = (int)($_POST['task_id'] ?? 0);
    $actual_hours = !empty($_POST['actual_hours']) ? (float)$_POST['actual_hours'] : null;
    $completion_notes = trim($_POST['completion_notes'] ?? '');
    
    if (!$task_id) {
        throw new Exception('ID de tarea no válido');
    }
    
    // Verificar que la tarea pertenece a la empresa
    $check_sql = "SELECT task_id FROM tasks WHERE task_id = ? AND company_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$task_id, $company_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Tarea no encontrada');
    }
    
    $sql = "
        UPDATE tasks 
        SET status = 'completed', completion_percentage = 100, actual_hours = ?, 
            completed_at = NOW(), updated_at = NOW()
        WHERE task_id = ? AND company_id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$actual_hours, $task_id, $company_id]);
    
    // Registrar en historial si hay notas
    if (!empty($completion_notes)) {
        $history_sql = "
            INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
            VALUES (?, 'status', 'in_progress', 'completed', ?, ?)
        ";
        $history_stmt = $pdo->prepare($history_sql);
        $history_stmt->execute([$task_id, $_SESSION['user_id'], $completion_notes]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tarea marcada como completada'
    ]);
}

// ============ FUNCIONES AUXILIARES ============

function getDepartments() {
    global $pdo, $company_id;
    
    $sql = "SELECT department_id, name FROM departments WHERE company_id = ? ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$company_id]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);
}

function getEmployees() {
    global $pdo, $company_id;
    
    $sql = "
        SELECT 
            e.employee_id, 
            CONCAT(e.first_name, ' ', e.last_name) as name,
            e.email,
            d.name as department_name
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.department_id
        WHERE e.company_id = ? AND e.status = 'active'
        ORDER BY e.first_name, e.last_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$company_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $employees
    ]);
}

function exportProcesses() {
    // Implementar exportación CSV/PDF de procesos
    echo json_encode([
        'success' => true,
        'message' => 'Funcionalidad de exportación en desarrollo'
    ]);
}

function exportTasks() {
    // Implementar exportación CSV/PDF de tareas
    echo json_encode([
        'success' => true,
        'message' => 'Funcionalidad de exportación en desarrollo'
    ]);
}
?>
