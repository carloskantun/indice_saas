<?php
/**
 * Panel Root - Controlador principal
 * Maneja todas las acciones AJAX del panel de administración
 */

require_once '../config.php';
header('Content-Type: application/json');

// Verificar autenticación y permisos de root
if (!checkRole(['root'])) {
    echo json_encode(['success' => false, 'message' => $lang['access_denied']]);
    exit();
}

$pdo = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_plan':
            createPlan();
            break;
            
        case 'update_plan':
            updatePlan();
            break;
            
        case 'delete_plan':
            deletePlan();
            break;
            
        case 'get_plan':
            getPlan();
            break;
            
        case 'get_modules':
            getModules();
            break;
            
        case 'check_plan_usage':
            checkPlanUsage();
            break;
            
        case 'get_company':
            getCompany();
            break;
            
        case 'update_company':
            updateCompany();
            break;
            
        case 'delete_company':
            deleteCompany();
            break;
            
        case 'toggle_company_status':
            toggleCompanyStatus();
            break;
            
        case 'change_company_plan':
            changeCompanyPlan();
            break;
            
        case 'get_company_usage':
            getCompanyUsage();
            break;
            
        case 'get_company_details':
            getCompanyDetails();
            break;
            
        // ==================== GESTIÓN DE USUARIOS ====================
        case 'get_users':
            getUsers();
            break;
            
        case 'get_user':
            getUser();
            break;
            
        case 'create_user':
            createUser();
            break;
            
        case 'update_user':
            updateUser();
            break;
            
        case 'delete_user':
            deleteUser();
            break;
            
        case 'toggle_user_status':
            toggleUserStatus();
            break;
            
        case 'get_user_companies':
            getUserCompanies();
            break;
            
        case 'update_user_roles':
            updateUserRoles();
            break;
            
        case 'add_user_company_role':
            addUserCompanyRole();
            break;
            
        case 'remove_user_company_role':
            removeUserCompanyRole();
            break;
            
        // ==================== GESTIÓN DE MÓDULOS ====================
        case 'get_modules':
            getModules();
            break;
            
        case 'get_module':
            getModule();
            break;
            
        case 'create_module':
            createModule();
            break;
            
        case 'update_module':
            updateModule();
            break;
            
        case 'delete_module':
            deleteModule();
            break;
            
        case 'toggle_module_status':
            toggleModuleStatus();
            break;
            
        case 'sync_system_modules':
            syncSystemModules();
            break;
            
        case 'get_module_usage':
            getModuleUsage();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function createPlan() {
    global $pdo, $lang;
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_monthly = floatval($_POST['price_monthly'] ?? 0);
    $modules_included = $_POST['modules_included'] ?? [];
    $users_max = intval($_POST['users_max'] ?? 1);
    $units_max = intval($_POST['units_max'] ?? 1);
    $businesses_max = intval($_POST['businesses_max'] ?? 1);
    $storage_max_mb = intval($_POST['storage_max_mb'] ?? 100);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validaciones
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del plan es requerido']);
        return;
    }
    
    // Verificar si ya existe un plan con ese nombre
    $stmt = $pdo->prepare("SELECT id FROM plans WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un plan con ese nombre']);
        return;
    }
    
    // Convertir módulos a JSON
    $modules_json = json_encode($modules_included);
    
    $stmt = $pdo->prepare("
        INSERT INTO plans (name, description, price_monthly, modules_included, users_max, units_max, businesses_max, storage_max_mb, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$name, $description, $price_monthly, $modules_json, $users_max, $units_max, $businesses_max, $storage_max_mb, $is_active])) {
        echo json_encode(['success' => true, 'message' => $lang['plan_created']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el plan']);
    }
}

function updatePlan() {
    global $pdo, $lang;
    
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_monthly = floatval($_POST['price_monthly'] ?? 0);
    $modules_included = $_POST['modules_included'] ?? [];
    $users_max = intval($_POST['users_max'] ?? 1);
    $units_max = intval($_POST['units_max'] ?? 1);
    $businesses_max = intval($_POST['businesses_max'] ?? 1);
    $storage_max_mb = intval($_POST['storage_max_mb'] ?? 100);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validaciones
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de plan no válido']);
        return;
    }
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del plan es requerido']);
        return;
    }
    
    // Verificar si ya existe otro plan con ese nombre
    $stmt = $pdo->prepare("SELECT id FROM plans WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otro plan con ese nombre']);
        return;
    }
    
    // Convertir módulos a JSON
    $modules_json = json_encode($modules_included);
    
    $stmt = $pdo->prepare("
        UPDATE plans 
        SET name = ?, description = ?, price_monthly = ?, modules_included = ?, 
            users_max = ?, units_max = ?, businesses_max = ?, storage_max_mb = ?, is_active = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$name, $description, $price_monthly, $modules_json, $users_max, $units_max, $businesses_max, $storage_max_mb, $is_active, $id])) {
        echo json_encode(['success' => true, 'message' => $lang['plan_updated']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el plan']);
    }
}

function deletePlan() {
    global $pdo, $lang;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de plan no válido']);
        return;
    }
    
    // Verificar si el plan está en uso
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM companies WHERE plan_id = ?");
    $stmt->execute([$id]);
    $usage = $stmt->fetch();
    
    if ($usage['count'] > 0) {
        echo json_encode(['success' => false, 'message' => $lang['plan_in_use']]);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => $lang['plan_deleted']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el plan']);
    }
}

function getPlan() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de plan no válido']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    
    if ($plan) {
        // Decodificar módulos JSON
        $plan['modules_included'] = json_decode($plan['modules_included'], true) ?? [];
        echo json_encode(['success' => true, 'plan' => $plan]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Plan no encontrado']);
    }
}

function checkPlanUsage() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de plan no válido']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as companies_count 
        FROM companies 
        WHERE plan_id = ?
    ");
    $stmt->execute([$id]);
    $usage = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'companies_count' => $usage['companies_count'],
        'can_delete' => $usage['companies_count'] == 0
    ]);
}

// ===== FUNCIONES PARA GESTIÓN DE EMPRESAS =====

function getCompany() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name as plan_name, p.price_monthly, u.name as created_by_name
        FROM companies c 
        LEFT JOIN plans p ON c.plan_id = p.id
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $company = $stmt->fetch();
    
    if ($company) {
        echo json_encode(['success' => true, 'company' => $company]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
    }
}

function updateCompany() {
    global $pdo, $lang;
    
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validaciones
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        return;
    }
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de la empresa es requerido']);
        return;
    }
    
    if (!in_array($status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        return;
    }
    
    // Verificar si ya existe otra empresa con ese nombre
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otra empresa con ese nombre']);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE companies 
        SET name = ?, description = ?, status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    if ($stmt->execute([$name, $description, $status, $id])) {
        echo json_encode(['success' => true, 'message' => $lang['company_updated']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la empresa']);
    }
}

function deleteCompany() {
    global $pdo, $lang;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        return;
    }
    
    // Verificar si la empresa tiene usuarios
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_companies WHERE company_id = ?");
    $stmt->execute([$id]);
    $usage = $stmt->fetch();
    
    if ($usage['count'] > 0) {
        echo json_encode(['success' => false, 'message' => $lang['company_has_users']]);
        return;
    }
    
    // Eliminar empresa (esto también eliminará unidades y negocios por CASCADE)
    $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => $lang['company_deleted']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la empresa']);
    }
}

function toggleCompanyStatus() {
    global $pdo, $lang;
    
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($id <= 0 || !in_array($status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Parámetros no válidos']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE companies SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    
    if ($stmt->execute([$status, $id])) {
        $message = $status === 'active' ? $lang['company_activated'] : $lang['company_deactivated'];
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado de la empresa']);
    }
}

function changeCompanyPlan() {
    global $pdo, $lang;
    
    $companyId = intval($_POST['company_id'] ?? 0);
    $planId = intval($_POST['plan_id'] ?? 0);
    
    if ($companyId <= 0 || $planId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parámetros no válidos']);
        return;
    }
    
    // Verificar que el plan existe y está activo
    $stmt = $pdo->prepare("SELECT name FROM plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$planId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Plan no válido o inactivo']);
        return;
    }
    
    // Actualizar plan de la empresa
    $stmt = $pdo->prepare("UPDATE companies SET plan_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    
    if ($stmt->execute([$planId, $companyId])) {
        echo json_encode(['success' => true, 'message' => $lang['company_plan_changed']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar el plan']);
    }
}

function getCompanyUsage() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        return;
    }
    
    try {
        // Obtener conteos de uso
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT uc.user_id) as users_count,
                COUNT(DISTINCT un.id) as units_count,
                COUNT(DISTINCT b.id) as businesses_count
            FROM companies c
            LEFT JOIN user_companies uc ON c.id = uc.company_id AND uc.status = 'active'
            LEFT JOIN units un ON c.id = un.company_id AND un.status = 'active'
            LEFT JOIN businesses b ON un.id = b.unit_id AND b.status = 'active'
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $usage = $stmt->fetch();
        
        echo json_encode(['success' => true, 'usage' => $usage]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas de uso']);
    }
}

function getCompanyDetails() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        return;
    }
    
    try {
        // Información básica de la empresa
        $stmt = $pdo->prepare("
            SELECT c.*, p.name as plan_name, p.price_monthly, p.modules_included,
                   p.users_max, p.units_max, p.businesses_max, p.storage_max_mb,
                   u.name as created_by_name
            FROM companies c 
            LEFT JOIN plans p ON c.plan_id = p.id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $company = $stmt->fetch();
        
        if (!$company) {
            echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
            return;
        }
        
        // Usuarios de la empresa
        $stmt = $pdo->prepare("
            SELECT u.name, u.email, uc.role, uc.status, uc.last_accessed
            FROM user_companies uc
            INNER JOIN users u ON uc.user_id = u.id
            WHERE uc.company_id = ?
            ORDER BY uc.role, u.name
        ");
        $stmt->execute([$id]);
        $users = $stmt->fetchAll();
        
        // Unidades de la empresa
        $stmt = $pdo->prepare("
            SELECT id, name, description, status, created_at
            FROM units 
            WHERE company_id = ?
            ORDER BY name
        ");
        $stmt->execute([$id]);
        $units = $stmt->fetchAll();
        
        // Negocios de la empresa
        $stmt = $pdo->prepare("
            SELECT b.id, b.name, b.description, b.status, u.name as unit_name
            FROM businesses b
            INNER JOIN units u ON b.unit_id = u.id
            WHERE u.company_id = ?
            ORDER BY u.name, b.name
        ");
        $stmt->execute([$id]);
        $businesses = $stmt->fetchAll();
        
        // Decodificar módulos
        if ($company['modules_included']) {
            $company['modules_array'] = json_decode($company['modules_included'], true) ?? [];
        } else {
            $company['modules_array'] = [];
        }
        
        echo json_encode([
            'success' => true,
            'company' => $company,
            'users' => $users,
            'units' => $units,
            'businesses' => $businesses
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener detalles de la empresa']);
    }
}

// ==================== FUNCIONES DE GESTIÓN DE USUARIOS ====================

/**
 * Obtener lista de usuarios con estadísticas
 */
function getUsers() {
    $pdo = getDB();
    
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
        
        echo json_encode(['success' => true, 'users' => $users]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios']);
    }
}

/**
 * Obtener datos de un usuario específico
 */
function getUser() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        // Datos básicos del usuario
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COUNT(DISTINCT uc.company_id) as companies_count,
                   MAX(uc.last_accessed) as last_access
            FROM users u 
            LEFT JOIN user_companies uc ON u.id = uc.user_id 
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }
        
        // Empresas asignadas
        $stmt = $pdo->prepare("
            SELECT uc.*, c.name as company_name
            FROM user_companies uc
            INNER JOIN companies c ON uc.company_id = c.id
            WHERE uc.user_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$id]);
        $companies = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'user' => $user,
            'companies' => $companies
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuario']);
    }
}

/**
 * Crear nuevo usuario
 */
function createUser() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones
    $required = ['name', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            return;
        }
    }
    
    if ($data['password'] !== $data['password_confirm']) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        // Verificar email único
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Crear usuario
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, status, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['status'] ?? 'active'
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Asignar roles en empresas si se especificaron
        if (!empty($data['company_roles'])) {
            foreach ($data['company_roles'] as $companyRole) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_companies (user_id, company_id, role, status, assigned_at) 
                    VALUES (?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([
                    $userId,
                    $companyRole['company_id'],
                    $companyRole['role']
                ]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario creado exitosamente',
            'user_id' => $userId
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario']);
    }
}

/**
 * Actualizar usuario existente
 */
function updateUser() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $pdo->beginTransaction();
        
        // Actualizar datos básicos
        $updateFields = [];
        $values = [];
        
        if (!empty($data['name'])) {
            $updateFields[] = "name = ?";
            $values[] = $data['name'];
        }
        
        if (!empty($data['email'])) {
            // Verificar email único
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $data['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
                return;
            }
            $updateFields[] = "email = ?";
            $values[] = $data['email'];
        }
        
        if (isset($data['status'])) {
            $updateFields[] = "status = ?";
            $values[] = $data['status'];
        }
        
        if (!empty($data['password'])) {
            if ($data['password'] !== $data['password_confirm']) {
                echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
                return;
            }
            $updateFields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($updateFields)) {
            $values[] = $data['user_id'];
            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $stmt->execute($values);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
    }
}

/**
 * Eliminar usuario
 */
function deleteUser() {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $pdo->beginTransaction();
        
        // Eliminar relaciones con empresas
        $stmt = $pdo->prepare("DELETE FROM user_companies WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Eliminar usuario
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
    }
}

/**
 * Cambiar estado de usuario
 */
function toggleUserStatus() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['status'])) {
        echo json_encode(['success' => false, 'message' => 'Datos requeridos incompletos']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
}

/**
 * Obtener empresas de un usuario
 */
function getUserCompanies() {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $stmt = $pdo->prepare("
            SELECT uc.*, c.name as company_name, c.status as company_status
            FROM user_companies uc
            INNER JOIN companies c ON uc.company_id = c.id
            WHERE uc.user_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$userId]);
        $companies = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'companies' => $companies]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener empresas del usuario']);
    }
}

/**
 * Actualizar roles de usuario en empresas
 */
function updateUserRoles() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['role_updates'])) {
        echo json_encode(['success' => false, 'message' => 'Datos requeridos incompletos']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $pdo->beginTransaction();
        
        foreach ($data['role_updates'] as $update) {
            $stmt = $pdo->prepare("
                UPDATE user_companies 
                SET role = ?, status = ? 
                WHERE user_id = ? AND company_id = ?
            ");
            $stmt->execute([
                $update['role'],
                $update['status'],
                $data['user_id'],
                $update['company_id']
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Roles actualizados exitosamente']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar roles']);
    }
}

/**
 * Agregar rol de usuario en empresa
 */
function addUserCompanyRole() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['user_id', 'company_id', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            return;
        }
    }
    
    $pdo = getDB();
    
    try {
        // Verificar si ya existe la relación
        $stmt = $pdo->prepare("
            SELECT id FROM user_companies 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$data['user_id'], $data['company_id']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El usuario ya tiene un rol en esta empresa']);
            return;
        }
        
        // Crear nueva relación
        $stmt = $pdo->prepare("
            INSERT INTO user_companies (user_id, company_id, role, status, assigned_at) 
            VALUES (?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$data['user_id'], $data['company_id'], $data['role']]);
        
        echo json_encode(['success' => true, 'message' => 'Rol agregado exitosamente']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al agregar rol']);
    }
}

/**
 * Remover rol de usuario en empresa
 */
function removeUserCompanyRole() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['company_id'])) {
        echo json_encode(['success' => false, 'message' => 'Datos requeridos incompletos']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM user_companies 
            WHERE user_id = ? AND company_id = ?
        ");
        $stmt->execute([$data['user_id'], $data['company_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Rol removido exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Relación no encontrada']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al remover rol']);
    }
}

// ==================== FUNCIONES DE GESTIÓN DE MÓDULOS ====================

/**
 * Obtener lista de módulos con estadísticas
 */
function getModules() {
    $pdo = getDB();
    
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
        
        echo json_encode(['success' => true, 'modules' => $modules]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener módulos']);
    }
}

/**
 * Obtener datos de un módulo específico
 */
function getModule() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de módulo requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        // Datos básicos del módulo
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   COUNT(DISTINCT pm.plan_id) as plans_using_count,
                   GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') as plans_list
            FROM modules m 
            LEFT JOIN plan_modules pm ON m.id = pm.module_id
            LEFT JOIN plans p ON pm.plan_id = p.id AND p.status = 'active'
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$id]);
        $module = $stmt->fetch();
        
        if (!$module) {
            echo json_encode(['success' => false, 'message' => 'Módulo no encontrado']);
            return;
        }
        
        // Planes que usan este módulo
        $stmt = $pdo->prepare("
            SELECT p.*, pm.created_at as assigned_at
            FROM plan_modules pm
            INNER JOIN plans p ON pm.plan_id = p.id
            WHERE pm.module_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$id]);
        $plans = $stmt->fetchAll();
        
        // Empresas con acceso al módulo
        $stmt = $pdo->prepare("
            SELECT c.*, p.name as plan_name, 
                   COUNT(DISTINCT uc.user_id) as users_count
            FROM companies c
            INNER JOIN plans p ON c.plan_id = p.id
            INNER JOIN plan_modules pm ON p.id = pm.plan_id
            LEFT JOIN user_companies uc ON c.id = uc.company_id AND uc.status = 'active'
            WHERE pm.module_id = ? AND c.status = 'active'
            GROUP BY c.id
            ORDER BY c.name
        ");
        $stmt->execute([$id]);
        $companies = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'module' => $module,
            'plans' => $plans,
            'companies' => $companies
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener módulo']);
    }
}

/**
 * Crear nuevo módulo
 */
function createModule() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones
    $required = ['name', 'slug', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            return;
        }
    }
    
    $pdo = getDB();
    
    try {
        // Verificar slug único
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE slug = ?");
        $stmt->execute([$data['slug']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El slug ya está en uso']);
            return;
        }
        
        // Crear módulo
        $stmt = $pdo->prepare("
            INSERT INTO modules (name, slug, description, icon, color, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['icon'] ?? 'fas fa-puzzle-piece',
            $data['color'] ?? '#3498db',
            $data['status'] ?? 'active'
        ]);
        
        $moduleId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Módulo creado exitosamente',
            'module_id' => $moduleId
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear módulo']);
    }
}

/**
 * Actualizar módulo existente
 */
function updateModule() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['module_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de módulo requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        // Verificar slug único (excluyendo el módulo actual)
        if (!empty($data['slug'])) {
            $stmt = $pdo->prepare("SELECT id FROM modules WHERE slug = ? AND id != ?");
            $stmt->execute([$data['slug'], $data['module_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El slug ya está en uso']);
                return;
            }
        }
        
        // Actualizar módulo
        $updateFields = [];
        $values = [];
        
        $allowedFields = ['name', 'slug', 'description', 'icon', 'color', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (!empty($updateFields)) {
            $values[] = $data['module_id'];
            $stmt = $pdo->prepare("UPDATE modules SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $stmt->execute($values);
        }
        
        echo json_encode(['success' => true, 'message' => 'Módulo actualizado exitosamente']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar módulo']);
    }
}

/**
 * Eliminar módulo
 */
function deleteModule() {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID de módulo requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        // Verificar si el módulo está en uso
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM plan_modules WHERE module_id = ?");
        $stmt->execute([$id]);
        $plansUsingModule = $stmt->fetchColumn();
        
        if ($plansUsingModule > 0) {
            echo json_encode(['success' => false, 'message' => 'El módulo está en uso en planes y no puede ser eliminado']);
            return;
        }
        
        // Eliminar módulo
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Módulo eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Módulo no encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar módulo']);
    }
}

/**
 * Cambiar estado de módulo
 */
function toggleModuleStatus() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['module_id']) || empty($data['status'])) {
        echo json_encode(['success' => false, 'message' => 'Datos requeridos incompletos']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        $stmt = $pdo->prepare("UPDATE modules SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['module_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Estado del módulo actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Módulo no encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado del módulo']);
    }
}

/**
 * Sincronizar módulos del sistema
 */
function syncSystemModules() {
    $pdo = getDB();
    
    // Módulos predefinidos del sistema
    $systemModules = [
        'gastos' => [
            'name' => 'Gastos',
            'description' => 'Gestión y control de gastos empresariales',
            'icon' => 'fas fa-coins',
            'color' => '#e74c3c'
        ],
        'mantenimiento' => [
            'name' => 'Mantenimiento',
            'description' => 'Control de mantenimiento de equipos y vehículos',
            'icon' => 'fas fa-tools',
            'color' => '#f39c12'
        ],
        'servicio_cliente' => [
            'name' => 'Servicio al Cliente',
            'description' => 'Gestión de tickets y atención al cliente',
            'icon' => 'fas fa-headset',
            'color' => '#3498db'
        ],
        'usuarios' => [
            'name' => 'Usuarios',
            'description' => 'Gestión de usuarios y permisos',
            'icon' => 'fas fa-users',
            'color' => '#9b59b6'
        ],
        'kpis' => [
            'name' => 'KPIs',
            'description' => 'Indicadores clave de rendimiento',
            'icon' => 'fas fa-chart-line',
            'color' => '#27ae60'
        ],
        'compras' => [
            'name' => 'Compras',
            'description' => 'Gestión de compras y proveedores',
            'icon' => 'fas fa-shopping-cart',
            'color' => '#34495e'
        ],
        'lavanderia' => [
            'name' => 'Lavandería',
            'description' => 'Control de servicios de lavandería',
            'icon' => 'fas fa-tshirt',
            'color' => '#1abc9c'
        ],
        'transfers' => [
            'name' => 'Transfers',
            'description' => 'Gestión de servicios de transporte',
            'icon' => 'fas fa-bus',
            'color' => '#e67e22'
        ]
    ];
    
    try {
        $pdo->beginTransaction();
        $syncedCount = 0;
        
        foreach ($systemModules as $slug => $moduleData) {
            // Verificar si el módulo ya existe
            $stmt = $pdo->prepare("SELECT id FROM modules WHERE slug = ?");
            $stmt->execute([$slug]);
            $existingModule = $stmt->fetch();
            
            if (!$existingModule) {
                // Crear nuevo módulo
                $stmt = $pdo->prepare("
                    INSERT INTO modules (name, slug, description, icon, color, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([
                    $moduleData['name'],
                    $slug,
                    $moduleData['description'],
                    $moduleData['icon'],
                    $moduleData['color']
                ]);
                $syncedCount++;
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Sincronización completada. $syncedCount módulos agregados.",
            'synced_count' => $syncedCount
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al sincronizar módulos']);
    }
}

/**
 * Obtener estadísticas de uso de módulo
 */
function getModuleUsage() {
    $moduleId = $_GET['module_id'] ?? null;
    
    if (!$moduleId) {
        echo json_encode(['success' => false, 'message' => 'ID de módulo requerido']);
        return;
    }
    
    $pdo = getDB();
    
    try {
        // Estadísticas generales
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT pm.plan_id) as plans_count,
                COUNT(DISTINCT c.id) as companies_count,
                COUNT(DISTINCT uc.user_id) as users_count
            FROM modules m
            LEFT JOIN plan_modules pm ON m.id = pm.module_id
            LEFT JOIN companies c ON pm.plan_id = c.plan_id AND c.status = 'active'
            LEFT JOIN user_companies uc ON c.id = uc.company_id AND uc.status = 'active'
            WHERE m.id = ?
        ");
        $stmt->execute([$moduleId]);
        $stats = $stmt->fetch();
        
        // Calcular disponibilidad (porcentaje de empresas activas que tienen acceso)
        $stmt = $pdo->query("SELECT COUNT(*) FROM companies WHERE status = 'active'");
        $totalCompanies = $stmt->fetchColumn();
        
        $availability = $totalCompanies > 0 ? round(($stats['companies_count'] / $totalCompanies) * 100, 1) : 0;
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'plans_count' => $stats['plans_count'],
                'companies_count' => $stats['companies_count'],
                'users_count' => $stats['users_count'],
                'availability' => $availability
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas de uso']);
    }
}
?>
