<?php
/**
 * CONTROLADOR MÓDULO GASTOS - SISTEMA SAAS INDICE
 * Maneja todas las operaciones CRUD y API del módulo de gastos
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
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.pay', 
            'expenses.export', 'expenses.kpis', 'expenses.delete',
            'providers.view', 'providers.create', 'providers.edit', 'providers.delete'
        ],
        'moderator' => [
            'expenses.view', 'expenses.create', 'expenses.pay', 
            'providers.view', 'providers.create'
        ],
        'user' => [
            'expenses.view', 'providers.view'
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
        case 'create_expense':
            createExpense();
            break;
            
        case 'edit_expense':
            editExpense();
            break;
            
        case 'delete_expense':
            deleteExpense();
            break;
            
        case 'add_payment':
            addPayment();
            break;
            
        case 'get_expense':
            getExpense();
            break;
            
        case 'get_kpis':
            getKPIs();
            break;
            
        case 'create_provider':
            createProvider();
            break;
            
        case 'edit_provider':
            editProvider();
            break;
            
        case 'delete_provider':
            deleteProvider();
            break;
            
        case 'get_providers':
            getProviders();
            break;
            
        case 'update_field':
            updateField();
            break;
            
        case 'delete_multiple':
            deleteMultiple();
            break;
            
        case 'create_order':
            createOrder();
            break;
            
        case 'generate_pdf':
            generateExpensePDF();
            break;
            
        case 'get_kpis':
            getKPIs();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}

function createExpense() {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    if (!hasPermission('expenses.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear gastos']);
        return;
    }
    
    $required = ['amount', 'payment_date', 'concept'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    // Generar folio para el gasto
    $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(folio, 4) AS UNSIGNED)) as max_num FROM expenses WHERE company_id = ? AND folio LIKE 'EXP%'");
    $stmt->execute([$company_id]);
    $max_num = $stmt->fetchColumn() ?? 0;
    $folio = 'EXP' . str_pad($max_num + 1, 6, '0', STR_PAD_LEFT);
    
    $stmt = $db->prepare("
        INSERT INTO expenses (
            folio, company_id, unit_id, business_id, provider_id, amount, payment_date,
            expense_type, purchase_type, payment_method, bank_account, concept,
            order_folio, origin, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $folio,
        $company_id,
        $unit_id, 
        $business_id,
        $_POST['provider_id'] ?: null,
        $_POST['amount'],
        $_POST['payment_date'],
        $_POST['expense_type'] ?? 'Unico',
        $_POST['purchase_type'] ?? null,
        $_POST['payment_method'] ?? 'Transferencia',
        $_POST['bank_account'] ?? null,
        $_POST['concept'],
        $_POST['order_folio'] ?? null,
        $_POST['origin'] ?? 'Directo',
        $user_id
    ]);
    
    if ($result) {
        $expense_id = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Gasto creado exitosamente',
            'expense_id' => $expense_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el gasto']);
    }
}

function editExpense() {
    global $db;
    
    if (!hasPermission('expenses.edit')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para editar gastos']);
        return;
    }
    
    $expense_id = $_POST['expense_id'] ?? null;
    if (!$expense_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de gasto requerido']);
        return;
    }
    
    // Verificar que el gasto pertenece al negocio actual
    $stmt = $db->prepare("
        SELECT id FROM expenses 
        WHERE id = ? AND company_id = ? AND business_id = ?
    ");
    $stmt->execute([$expense_id, $_SESSION['company_id'], $_SESSION['business_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Gasto no encontrado']);
        return;
    }
    
    $stmt = $db->prepare("
        UPDATE expenses SET
            provider_id = ?, amount = ?, payment_date = ?, expense_type = ?,
            purchase_type = ?, payment_method = ?, bank_account = ?, concept = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $_POST['provider_id'] ?: null,
        $_POST['amount'],
        $_POST['payment_date'],
        $_POST['expense_type'] ?? 'Unico',
        $_POST['purchase_type'] ?? null,
        $_POST['payment_method'] ?? 'Transferencia',
        $_POST['bank_account'] ?? null,
        $_POST['concept'],
        $expense_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Gasto actualizado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el gasto']);
    }
}

function deleteExpense() {
    global $db;
    
    if (!hasPermission('expenses.delete')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para eliminar gastos']);
        return;
    }
    
    $expense_id = $_POST['expense_id'] ?? null;
    if (!$expense_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de gasto requerido']);
        return;
    }
    
    // Verificar que el gasto pertenece al negocio actual
    $stmt = $db->prepare("
        SELECT id FROM expenses 
        WHERE id = ? AND company_id = ? AND business_id = ?
    ");
    $stmt->execute([$expense_id, $_SESSION['company_id'], $_SESSION['business_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Gasto no encontrado']);
        return;
    }
    
    // Eliminar pagos asociados primero (cascada debería hacerlo automáticamente)
    $stmt = $db->prepare("DELETE FROM expense_payments WHERE expense_id = ?");
    $stmt->execute([$expense_id]);
    
    // Eliminar el gasto
    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
    $result = $stmt->execute([$expense_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Gasto eliminado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar el gasto']);
    }
}

function addPayment() {
    global $db, $user_id;
    
    if (!hasPermission('expenses.pay')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para registrar pagos']);
        return;
    }
    
    $expense_id = $_POST['expense_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $payment_date = $_POST['payment_date'] ?? null;
    
    if (!$expense_id || !$amount || !$payment_date) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos de pago incompletos']);
        return;
    }
    
    // Verificar que el gasto existe y obtener el monto total
    $stmt = $db->prepare("
        SELECT amount, 
               COALESCE((SELECT SUM(amount) FROM expense_payments WHERE expense_id = ?), 0) as paid_amount
        FROM expenses 
        WHERE id = ? AND company_id = ? AND business_id = ?
    ");
    $stmt->execute([$expense_id, $expense_id, $_SESSION['company_id'], $_SESSION['business_id']]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        http_response_code(404);
        echo json_encode(['error' => 'Gasto no encontrado']);
        return;
    }
    
    // Verificar que no exceda el monto total
    $new_total_paid = $expense['paid_amount'] + $amount;
    if ($new_total_paid > $expense['amount']) {
        http_response_code(400);
        echo json_encode([
            'error' => 'El pago excede el monto pendiente',
            'pending_amount' => $expense['amount'] - $expense['paid_amount']
        ]);
        return;
    }
    
    // Insertar el pago
    $stmt = $db->prepare("
        INSERT INTO expense_payments (expense_id, amount, payment_date, comment, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $expense_id,
        $amount,
        $payment_date,
        $_POST['comment'] ?? null,
        $user_id
    ]);
    
    if ($result) {
        // Actualizar estatus del gasto
        $new_status = ($new_total_paid >= $expense['amount']) ? 'Pagado' : 'Pago parcial';
        
        $stmt = $db->prepare("UPDATE expenses SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $expense_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
            'new_status' => $new_status
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al registrar el pago']);
    }
}

function getExpense() {
    global $db;
    
    if (!hasPermission('expenses.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver gastos']);
        return;
    }
    
    $expense_id = $_GET['expense_id'] ?? null;
    if (!$expense_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de gasto requerido']);
        return;
    }
    
    // Obtener datos del gasto
    $stmt = $db->prepare("
        SELECT e.*, p.name as provider_name, u.name as unit_name, b.name as business_name,
               COALESCE((SELECT SUM(ep.amount) FROM expense_payments ep WHERE ep.expense_id = e.id), 0) AS paid_amount
        FROM expenses e
        LEFT JOIN providers p ON e.provider_id = p.id
        LEFT JOIN units u ON e.unit_id = u.id
        LEFT JOIN businesses b ON e.business_id = b.id
        WHERE e.id = ? AND e.company_id = ? AND e.business_id = ?
    ");
    $stmt->execute([$expense_id, $_SESSION['company_id'], $_SESSION['business_id']]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        http_response_code(404);
        echo json_encode(['error' => 'Gasto no encontrado']);
        return;
    }
    
    // Obtener pagos del gasto
    $stmt = $db->prepare("
        SELECT ep.*, u.name as created_by_name
        FROM expense_payments ep
        LEFT JOIN users u ON ep.created_by = u.id
        WHERE ep.expense_id = ?
        ORDER BY ep.payment_date DESC
    ");
    $stmt->execute([$expense_id]);
    $payments = $stmt->fetchAll();
    
    echo json_encode([
        'expense' => $expense,
        'payments' => $payments,
        'pending_amount' => $expense['amount'] - $expense['paid_amount']
    ]);
}

function createProvider() {
    global $db, $company_id, $user_id;
    
    if (!hasPermission('providers.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear proveedores']);
        return;
    }
    
    $name = $_POST['name'] ?? null;
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre del proveedor requerido']);
        return;
    }
    
    $stmt = $db->prepare("
        INSERT INTO providers (company_id, name, phone, email, address, rfc, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $company_id,
        $name,
        $_POST['phone'] ?? null,
        $_POST['email'] ?? null,
        $_POST['address'] ?? null,
        $_POST['rfc'] ?? null,
        $user_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Proveedor creado exitosamente',
            'provider_id' => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el proveedor']);
    }
}

function editProvider() {
    global $db;
    
    if (!hasPermission('providers.edit')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para editar proveedores']);
        return;
    }
    
    $provider_id = $_POST['provider_id'] ?? null;
    if (!$provider_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de proveedor requerido']);
        return;
    }
    
    $stmt = $db->prepare("
        UPDATE providers SET
            name = ?, phone = ?, email = ?, address = ?, rfc = ?, updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");
    
    $result = $stmt->execute([
        $_POST['name'],
        $_POST['phone'] ?? null,
        $_POST['email'] ?? null,
        $_POST['address'] ?? null,
        $_POST['rfc'] ?? null,
        $provider_id,
        $_SESSION['company_id']
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Proveedor actualizado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el proveedor']);
    }
}

function deleteProvider() {
    global $db;
    
    if (!hasPermission('providers.delete')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para eliminar proveedores']);
        return;
    }
    
    $provider_id = $_POST['provider_id'] ?? null;
    if (!$provider_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de proveedor requerido']);
        return;
    }
    
    // Verificar si tiene gastos asociados
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM expenses 
        WHERE provider_id = ? AND company_id = ?
    ");
    $stmt->execute([$provider_id, $_SESSION['company_id']]);
    $expense_count = $stmt->fetchColumn();
    
    if ($expense_count > 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'No se puede eliminar el proveedor porque tiene gastos asociados',
            'expense_count' => $expense_count
        ]);
        return;
    }
    
    $stmt = $db->prepare("DELETE FROM providers WHERE id = ? AND company_id = ?");
    $result = $stmt->execute([$provider_id, $_SESSION['company_id']]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Proveedor eliminado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar el proveedor']);
    }
}

function getProviders() {
    global $db;
    
    if (!hasPermission('providers.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver proveedores']);
        return;
    }
    
    $stmt = $db->prepare("
        SELECT p.*, 
               COUNT(e.id) as expense_count,
               COALESCE(SUM(e.amount), 0) as total_expenses
        FROM providers p
        LEFT JOIN expenses e ON p.id = e.provider_id AND e.company_id = p.company_id
        WHERE p.company_id = ? AND p.status = 'active'
        GROUP BY p.id
        ORDER BY p.name
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $providers = $stmt->fetchAll();
    
    echo json_encode($providers);
}

function updateField() {
    global $db;
    
    if (!hasPermission('expenses.edit')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para editar gastos']);
        return;
    }
    
    $expense_id = $_POST['expense_id'] ?? null;
    $field = $_POST['field'] ?? null;
    $value = $_POST['value'] ?? null;
    
    if (!$expense_id || !$field) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    // Campos permitidos para edición en línea
    $allowed_fields = ['purchase_type', 'payment_method', 'bank_account', 'concept', 'status'];
    
    if (!in_array($field, $allowed_fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'Campo no permitido para edición']);
        return;
    }
    
    // Verificar que el gasto pertenece al negocio actual
    $stmt = $db->prepare("
        SELECT id FROM expenses 
        WHERE id = ? AND company_id = ? AND business_id = ?
    ");
    $stmt->execute([$expense_id, $_SESSION['company_id'], $_SESSION['business_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Gasto no encontrado']);
        return;
    }
    
    // Actualizar campo
    $stmt = $db->prepare("UPDATE expenses SET $field = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$value, $expense_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Campo actualizado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el campo']);
    }
}

function deleteMultiple() {
    global $db;
    
    if (!hasPermission('expenses.delete')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para eliminar gastos']);
        return;
    }
    
    $ids = $_POST['ids'] ?? [];
    
    if (empty($ids) || !is_array($ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'IDs de gastos requeridos']);
        return;
    }
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $params = array_merge($ids, [$_SESSION['company_id'], $_SESSION['business_id']]);
    
    // Verificar que todos los gastos pertenecen al negocio actual
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM expenses 
        WHERE id IN ($placeholders) AND company_id = ? AND business_id = ?
    ");
    $stmt->execute($params);
    $count = $stmt->fetchColumn();
    
    if ($count != count($ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Algunos gastos no pertenecen a este negocio']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Eliminar pagos asociados
        $stmt = $db->prepare("DELETE FROM expense_payments WHERE expense_id IN ($placeholders)");
        $stmt->execute($ids);
        
        // Eliminar gastos
        $stmt = $db->prepare("DELETE FROM expenses WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Gastos eliminados exitosamente',
            'deleted_count' => count($ids)
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar gastos: ' . $e->getMessage()]);
    }
}

function createOrder() {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    if (!hasPermission('expenses.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para crear órdenes']);
        return;
    }
    
    $required = ['amount', 'payment_date', 'concept'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    $expense_type = $_POST['expense_type'] ?? 'Unico';
    $created_orders = [];
    
    if ($expense_type === 'Recurrente') {
        // Validar campos requeridos para órdenes recurrentes
        if (empty($_POST['periodicidad']) || empty($_POST['plazo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Periodicidad y plazo son requeridos para órdenes recurrentes']);
            return;
        }
        
        $created_orders = createRecurringOrders();
    } else {
        // Crear orden única
        $order_data = createSingleOrder();
        if ($order_data) {
            $created_orders[] = $order_data;
        }
    }
    
    if (!empty($created_orders)) {
        echo json_encode([
            'success' => true,
            'message' => count($created_orders) > 1 ? 
                'Se crearon ' . count($created_orders) . ' órdenes recurrentes exitosamente' : 
                'Orden de compra creada exitosamente',
            'orders' => $created_orders
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear la(s) orden(es) de compra']);
    }
}

function createSingleOrder($custom_date = null) {
    global $db, $company_id, $business_id, $unit_id, $user_id;
    
    // Generar folio de orden
    $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(order_folio, 4) AS UNSIGNED)) as max_num FROM expenses WHERE company_id = ? AND order_folio LIKE 'ORD%'");
    $stmt->execute([$company_id]);
    $max_num = $stmt->fetchColumn() ?? 0;
    $order_folio = 'ORD' . str_pad($max_num + 1, 6, '0', STR_PAD_LEFT);
    
    $payment_date = $custom_date ?? $_POST['payment_date'];
    
    $stmt = $db->prepare("
        INSERT INTO expenses (
            folio, company_id, unit_id, business_id, provider_id, amount, payment_date,
            expense_type, purchase_type, payment_method, bank_account, concept,
            order_folio, origin, status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Orden', 'Por pagar', ?, NOW())
    ");
    
    $result = $stmt->execute([
        $order_folio,
        $company_id,
        $unit_id, 
        $business_id,
        $_POST['provider_id'] ?: null,
        $_POST['amount'],
        $payment_date,
        $_POST['expense_type'] ?? 'Unico',
        $_POST['purchase_type'] ?? null,
        $_POST['payment_method'] ?? 'Transferencia',
        $_POST['bank_account'] ?? null,
        $_POST['concept'],
        $order_folio,
        $user_id
    ]);
    
    if ($result) {
        return [
            'order_id' => $db->lastInsertId(),
            'order_folio' => $order_folio,
            'payment_date' => $payment_date
        ];
    }
    
    return false;
}

function createRecurringOrders() {
    $periodicidad = $_POST['periodicidad'];
    $plazo = $_POST['plazo'];
    $start_date = new DateTime($_POST['payment_date']);
    $created_orders = [];
    
    // Calcular número de órdenes basado en periodicidad y plazo
    $interval_map = [
        'Diario' => ['P1D', 90], // máximo 90 días
        'Semanal' => ['P1W', 52], // máximo 52 semanas
        'Quincenal' => ['P2W', 26], // máximo 26 quincenas
        'Mensual' => ['P1M', 12] // máximo 12 meses
    ];
    
    $plazo_months = [
        'Trimestral' => 3,
        'Semestral' => 6,
        'Anual' => 12
    ];
    
    if (!isset($interval_map[$periodicidad]) || !isset($plazo_months[$plazo])) {
        return [];
    }
    
    $interval_spec = $interval_map[$periodicidad][0];
    $max_iterations = $interval_map[$periodicidad][1];
    $total_months = $plazo_months[$plazo];
    
    // Calcular número real de iteraciones basado en el plazo
    if ($periodicidad === 'Mensual') {
        $iterations = $total_months;
    } elseif ($periodicidad === 'Quincenal') {
        $iterations = $total_months * 2;
    } elseif ($periodicidad === 'Semanal') {
        $iterations = $total_months * 4;
    } elseif ($periodicidad === 'Diario') {
        $iterations = $total_months * 30;
    }
    
    $iterations = min($iterations, $max_iterations);
    
    $interval = new DateInterval($interval_spec);
    $current_date = clone $start_date;
    
    for ($i = 0; $i < $iterations; $i++) {
        $order_data = createSingleOrder($current_date->format('Y-m-d'));
        if ($order_data) {
            $created_orders[] = $order_data;
        }
        $current_date->add($interval);
    }
    
    return $created_orders;
}

function generateExpensePDF() {
    global $db, $company_id;
    
    if (!hasPermission('expenses.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver gastos']);
        return;
    }
    
    $expense_id = $_GET['expense_id'] ?? null;
    if (!$expense_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de gasto requerido']);
        return;
    }
    
    // Obtener datos del gasto
    $stmt = $db->prepare("
        SELECT e.*, p.name as provider_name, p.rfc as provider_rfc, p.address as provider_address,
               c.name as company_name, c.address as company_address, c.rfc as company_rfc
        FROM expenses e
        LEFT JOIN providers p ON e.provider_id = p.id
        LEFT JOIN companies c ON e.company_id = c.id
        WHERE e.id = ? AND e.company_id = ?
    ");
    $stmt->execute([$expense_id, $company_id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        http_response_code(404);
        echo json_encode(['error' => 'Gasto no encontrado']);
        return;
    }
    
    // Generar HTML para PDF
    $html = generateExpenseHTML($expense);
    
    // Aquí podrías usar una librería como TCPDF o mPDF
    // Por ahora retornamos el HTML para previsualización
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}

function generateExpenseHTML($expense) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Comprobante de Gasto</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
            .company-info { text-align: right; margin-bottom: 20px; }
            .expense-details { margin: 20px 0; }
            .detail-row { margin: 10px 0; }
            .detail-label { font-weight: bold; display: inline-block; width: 150px; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            .amount-box { background: #f5f5f5; padding: 15px; border: 2px solid #333; text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>COMPROBANTE DE GASTO</h1>
            <h2>Folio: ' . htmlspecialchars($expense['folio']) . '</h2>
        </div>
        
        <div class="company-info">
            <strong>' . htmlspecialchars($expense['company_name']) . '</strong><br>
            ' . htmlspecialchars($expense['company_address']) . '<br>
            RFC: ' . htmlspecialchars($expense['company_rfc']) . '
        </div>
        
        <div class="expense-details">
            <div class="detail-row">
                <span class="detail-label">Proveedor:</span>
                ' . htmlspecialchars($expense['provider_name'] ?: 'Sin proveedor') . '
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de Pago:</span>
                ' . date('d/m/Y', strtotime($expense['payment_date'])) . '
            </div>
            <div class="detail-row">
                <span class="detail-label">Tipo de Gasto:</span>
                ' . htmlspecialchars($expense['expense_type']) . '
            </div>
            <div class="detail-row">
                <span class="detail-label">Método de Pago:</span>
                ' . htmlspecialchars($expense['payment_method']) . '
            </div>
            <div class="detail-row">
                <span class="detail-label">Concepto:</span>
                ' . nl2br(htmlspecialchars($expense['concept'])) . '
            </div>
        </div>
        
        <div class="amount-box">
            <h2>MONTO: $' . number_format($expense['amount'], 2) . '</h2>
            <p>Status: ' . htmlspecialchars($expense['status']) . '</p>
        </div>
        
        <div class="footer">
            <p>Documento generado el ' . date('d/m/Y H:i:s') . '</p>
            <p>Sistema de Gestión - Índice SAAS</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

function getKPIs() {
    global $db, $company_id, $business_id, $unit_id;
    
    if (!hasPermission('expenses.view')) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para ver KPIs']);
        return;
    }
    
    try {
        // KPIs básicos
        $kpis = [];
        
        // Total gastado este mes
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_mes
            FROM expenses 
            WHERE company_id = ? AND unit_id = ? AND business_id = ? 
            AND MONTH(payment_date) = MONTH(CURDATE()) 
            AND YEAR(payment_date) = YEAR(CURDATE())
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['total_mes'] = $stmt->fetchColumn();
        
        // Total gastado año actual
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_ano
            FROM expenses 
            WHERE company_id = ? AND unit_id = ? AND business_id = ? 
            AND YEAR(payment_date) = YEAR(CURDATE())
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['total_ano'] = $stmt->fetchColumn();
        
        // Gastos pendientes
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount - paid_amount), 0) as pendientes
            FROM expenses 
            WHERE company_id = ? AND unit_id = ? AND business_id = ? 
            AND status = 'Por pagar'
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['pendientes'] = $stmt->fetchColumn();
        
        // Número de gastos por status
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count
            FROM expenses 
            WHERE company_id = ? AND unit_id = ? AND business_id = ? 
            GROUP BY status
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['por_status'] = $stmt->fetchAll();
        
        // Top 5 proveedores por gasto
        $stmt = $db->prepare("
            SELECT p.name, COALESCE(SUM(e.amount), 0) as total
            FROM expenses e
            LEFT JOIN providers p ON e.provider_id = p.id
            WHERE e.company_id = ? AND e.unit_id = ? AND e.business_id = ? 
            AND YEAR(e.payment_date) = YEAR(CURDATE())
            GROUP BY e.provider_id, p.name
            ORDER BY total DESC
            LIMIT 5
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['top_proveedores'] = $stmt->fetchAll();
        
        // Gastos por tipo
        $stmt = $db->prepare("
            SELECT expense_type, COALESCE(SUM(amount), 0) as total
            FROM expenses 
            WHERE company_id = ? AND unit_id = ? AND business_id = ? 
            AND YEAR(payment_date) = YEAR(CURDATE())
            GROUP BY expense_type
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['por_tipo'] = $stmt->fetchAll();
        
        // Promedio mensual
        $stmt = $db->prepare("
            SELECT AVG(monthly_total) as promedio_mensual
            FROM (
                SELECT SUM(amount) as monthly_total
                FROM expenses 
                WHERE company_id = ? AND unit_id = ? AND business_id = ? 
                AND YEAR(payment_date) = YEAR(CURDATE())
                GROUP BY MONTH(payment_date)
            ) as monthly_totals
        ");
        $stmt->execute([$company_id, $unit_id, $business_id]);
        $kpis['promedio_mensual'] = $stmt->fetchColumn() ?: 0;
        
        echo json_encode([
            'success' => true,
            'kpis' => $kpis
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener KPIs: ' . $e->getMessage()]);
    }
}
?>
