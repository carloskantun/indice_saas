<?php
/**
 * MÓDULO GASTOS - SISTEMA SAAS INDICE
 * Vista principal del módulo de gastos adaptado al sistema SaaS
 * Basado en indice-produccion/gastos.php
 */

require_once '../../config.php';

// Verificar autenticación y permisos
if (!checkAuth()) {
    redirect('auth/');
}

// Función para verificar permisos específicos (temporal hasta implementar sistema completo)
function hasPermission($permission) {
    if (!checkAuth()) {
        return false;
    }
    
    // Si es root o superadmin, tiene todos los permisos
    $role = $_SESSION['current_role'] ?? 'user';
    if (in_array($role, ['root', 'superadmin'])) {
        return true;
    }
    
    // TODO: Implementar verificación real de permisos contra BD
    // Por ahora, permitir a admin y moderator ver gastos
    $permission_map = [
        'admin' => ['expenses.view', 'expenses.create', 'expenses.edit', 'expenses.pay', 'expenses.export', 'expenses.kpis', 'providers.view', 'providers.create', 'providers.edit'],
        'moderator' => ['expenses.view', 'expenses.create', 'expenses.pay', 'providers.view'],
        'user' => ['expenses.view', 'providers.view']
    ];
    
    return in_array($permission, $permission_map[$role] ?? []);
}

if (!hasPermission('expenses.view')) {
    redirect('dashboard.php?error=access_denied');
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;
$unit_id = $_SESSION['unit_id'] ?? null;

if (!$company_id || !$business_id || !$unit_id) {
    redirect('companies/?error=context_required');
}

// Filtros
$proveedor_id = $_GET['proveedor_id'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$estatus = $_GET['estatus'] ?? '';
$origen = $_GET['origen'] ?? '';
$orden = $_GET['orden'] ?? 'payment_date';
$dir = $_GET['dir'] ?? 'DESC';

// Construir WHERE clause
$where_conditions = ["e.company_id = ? AND e.business_id = ?"];
$params = [$company_id, $business_id];

if ($proveedor_id) {
    $where_conditions[] = "e.provider_id = ?";
    $params[] = $proveedor_id;
}

if ($fecha_inicio) {
    $where_conditions[] = "e.payment_date >= ?";
    $params[] = $fecha_inicio;
}

if ($fecha_fin) {
    $where_conditions[] = "e.payment_date <= ?";
    $params[] = $fecha_fin;
}

if ($estatus) {
    $where_conditions[] = "e.status = ?";
    $params[] = $estatus;
}

if ($origen) {
    $where_conditions[] = "e.origin = ?";
    $params[] = $origen;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Mapeo de ordenamiento
$order_map = [
    'folio' => 'e.folio',
    'provider' => 'p.name',
    'amount' => 'e.amount',
    'payment_date' => 'e.payment_date',
    'status' => 'e.status'
];
$order_column = $order_map[$orden] ?? 'e.payment_date';
$direction = $dir === 'ASC' ? 'ASC' : 'DESC';

// Consulta principal
$sql = "SELECT 
    e.id, 
    e.folio, 
    COALESCE(p.name, 'Sin proveedor') AS provider_name,
    e.amount, 
    e.payment_date, 
    u.name AS unit_name,
    b.name AS business_name,
    e.expense_type,
    e.purchase_type,
    e.payment_method,
    e.bank_account, 
    e.concept, 
    e.status, 
    e.origin,
    e.order_folio,
    COALESCE((SELECT SUM(ep.amount) FROM expense_payments ep WHERE ep.expense_id = e.id), 0) AS paid_amount,
    (e.amount - COALESCE((SELECT SUM(ep.amount) FROM expense_payments ep WHERE ep.expense_id = e.id), 0)) AS pending_amount
FROM expenses e
LEFT JOIN providers p ON e.provider_id = p.id
LEFT JOIN units u ON e.unit_id = u.id
LEFT JOIN businesses b ON e.business_id = b.id
$where_clause
ORDER BY $order_column $direction";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// KPIs del mes y año actual
$current_month_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                     WHERE company_id = ? AND business_id = ? 
                     AND MONTH(payment_date) = MONTH(CURDATE()) 
                     AND YEAR(payment_date) = YEAR(CURDATE())";
$stmt = $db->prepare($current_month_sql);
$stmt->execute([$company_id, $business_id]);
$kpi_month = $stmt->fetchColumn();

$current_year_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                    WHERE company_id = ? AND business_id = ? 
                    AND YEAR(payment_date) = YEAR(CURDATE())";
$stmt = $db->prepare($current_year_sql);
$stmt->execute([$company_id, $business_id]);
$kpi_year = $stmt->fetchColumn();

// Obtener proveedores para filtro
$providers_sql = "SELECT id, name FROM providers 
                  WHERE company_id = ? AND status = 'active' 
                  ORDER BY name";
$stmt = $db->prepare($providers_sql);
$stmt->execute([$company_id]);
$providers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos - <?php echo htmlspecialchars($_SESSION['business_name'] ?? 'SaaS'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="css/expenses.css" rel="stylesheet">
    <style>
        .expense-row.overdue { background-color: #ffebee; }
        .expense-row.paid { background-color: #e8f5e8; }
        .expense-row.partial { background-color: #fff3e0; }
        .kpi-card { border-left: 4px solid #007bff; }
        .btn-group-toggle .btn { margin: 2px; }
        .table-responsive { max-height: 600px; overflow-y: auto; }
        .sticky-header th { position: sticky; top: 0; background: white; z-index: 10; }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="/companies/" class="text-white">
                        <?php echo htmlspecialchars($_SESSION['company_name'] ?? 'Empresa'); ?>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/units/" class="text-white">
                        <?php echo htmlspecialchars($_SESSION['unit_name'] ?? 'Unidad'); ?>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/businesses/" class="text-white">
                        <?php echo htmlspecialchars($_SESSION['business_name'] ?? 'Negocio'); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active text-light">Gastos</li>
            </ol>
        </nav>
        
        <!-- Botones de navegación -->
        <div class="ms-auto">
            <a href="/modules/" class="btn btn-outline-light me-2">
                <i class="fas fa-th-large"></i> Módulos
            </a>
            <a href="/dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <!-- KPIs Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="card-body text-center">
                    <h5 class="card-title">Gastos del Mes</h5>
                    <h3 class="text-primary">$<?php echo number_format($kpi_month, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="card-body text-center">
                    <h5 class="card-title">Gastos del Año</h5>
                    <h3 class="text-success">$<?php echo number_format($kpi_year, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex align-items-center justify-content-end">
            <div class="btn-group" role="group">
                <?php if (hasPermission('expenses.create')): ?>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNewExpense">
                        <i class="fas fa-plus"></i> Nuevo Gasto
                    </button>
                <?php endif; ?>
                
                <?php if (hasPermission('providers.view')): ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalProviders">
                        <i class="fas fa-truck"></i> Proveedores
                    </button>
                <?php endif; ?>
                
                <?php if (hasPermission('expenses.kpis')): ?>
                    <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalKPIs">
                        <i class="fas fa-chart-pie"></i> KPIs
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor_id" class="form-select">
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?php echo $provider['id']; ?>" 
                                    <?php echo $proveedor_id == $provider['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($provider['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estatus</label>
                    <select name="estatus" class="form-select">
                        <option value="">Todos</option>
                        <option value="Por pagar" <?php echo $estatus === 'Por pagar' ? 'selected' : ''; ?>>Por pagar</option>
                        <option value="Pagado" <?php echo $estatus === 'Pagado' ? 'selected' : ''; ?>>Pagado</option>
                        <option value="Pago parcial" <?php echo $estatus === 'Pago parcial' ? 'selected' : ''; ?>>Pago parcial</option>
                        <option value="Vencido" <?php echo $estatus === 'Vencido' ? 'selected' : ''; ?>>Vencido</option>
                        <option value="Cancelado" <?php echo $estatus === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Origen</label>
                    <select name="origen" class="form-select">
                        <option value="">Todos</option>
                        <option value="Directo" <?php echo $origen === 'Directo' ? 'selected' : ''; ?>>Directo</option>
                        <option value="Orden" <?php echo $origen === 'Orden' ? 'selected' : ''; ?>>Orden</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtros Rápidos -->
    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group">
            <a href="?estatus=Por pagar" class="btn btn-outline-warning">Pendientes</a>
            <a href="?estatus=Vencido" class="btn btn-outline-danger">Vencidos</a>
            <a href="?estatus=Pagado" class="btn btn-outline-success">Pagados</a>
            <a href="?origen=Orden&estatus=Por pagar" class="btn btn-outline-info">Órdenes pendientes</a>
            <a href="?" class="btn btn-outline-secondary">Limpiar</a>
        </div>
        
        <?php if (hasPermission('expenses.export')): ?>
        <div class="float-end">
            <div class="btn-group btn-group-sm">
                <a href="export.php?format=csv&<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-outline-success" target="_blank">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <a href="export.php?format=pdf&<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-outline-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabla de Gastos -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="sticky-header">
                        <tr>
                            <th>
                                <a href="?orden=folio&dir=<?php echo $orden === 'folio' && $dir === 'ASC' ? 'DESC' : 'ASC'; ?>&<?php echo http_build_query(array_merge($_GET, ['orden' => 'folio'])); ?>" 
                                   class="text-decoration-none">
                                    Folio
                                    <?php if ($orden === 'folio'): ?>
                                        <i class="fas fa-sort-<?php echo $dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?orden=provider&dir=<?php echo $orden === 'provider' && $dir === 'ASC' ? 'DESC' : 'ASC'; ?>&<?php echo http_build_query(array_merge($_GET, ['orden' => 'provider'])); ?>" 
                                   class="text-decoration-none">
                                    Proveedor
                                    <?php if ($orden === 'provider'): ?>
                                        <i class="fas fa-sort-<?php echo $dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?orden=amount&dir=<?php echo $orden === 'amount' && $dir === 'ASC' ? 'DESC' : 'ASC'; ?>&<?php echo http_build_query(array_merge($_GET, ['orden' => 'amount'])); ?>" 
                                   class="text-decoration-none">
                                    Monto
                                    <?php if ($orden === 'amount'): ?>
                                        <i class="fas fa-sort-<?php echo $dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?orden=payment_date&dir=<?php echo $orden === 'payment_date' && $dir === 'ASC' ? 'DESC' : 'ASC'; ?>&<?php echo http_build_query(array_merge($_GET, ['orden' => 'payment_date'])); ?>" 
                                   class="text-decoration-none">
                                    Fecha
                                    <?php if ($orden === 'payment_date'): ?>
                                        <i class="fas fa-sort-<?php echo $dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Tipo</th>
                            <th>Método Pago</th>
                            <th>
                                <a href="?orden=status&dir=<?php echo $orden === 'status' && $dir === 'ASC' ? 'DESC' : 'ASC'; ?>&<?php echo http_build_query(array_merge($_GET, ['orden' => 'status'])); ?>" 
                                   class="text-decoration-none">
                                    Estatus
                                    <?php if ($orden === 'status'): ?>
                                        <i class="fas fa-sort-<?php echo $dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Pagado</th>
                            <th>Pendiente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No se encontraron gastos con los filtros aplicados</p>
                                        <?php if (hasPermission('expenses.create')): ?>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewExpense">
                                                <i class="fas fa-plus"></i> Crear primer gasto
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <?php
                                $row_class = '';
                                if ($expense['status'] === 'Vencido') $row_class = 'expense-row overdue';
                                elseif ($expense['status'] === 'Pagado') $row_class = 'expense-row paid';
                                elseif ($expense['status'] === 'Pago parcial') $row_class = 'expense-row partial';
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($expense['folio']); ?></strong>
                                        <?php if ($expense['origin'] === 'Orden' && $expense['order_folio']): ?>
                                            <br><small class="text-muted">Orden: <?php echo htmlspecialchars($expense['order_folio']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['provider_name']); ?></td>
                                    <td class="text-end">
                                        <strong>$<?php echo number_format($expense['amount'], 2); ?></strong>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($expense['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($expense['expense_type'] ?? 'N/A'); ?></span>
                                        <?php if ($expense['purchase_type']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($expense['purchase_type']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['payment_method']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'Pagado' => 'success',
                                            'Pago parcial' => 'warning',
                                            'Vencido' => 'danger',
                                            'Por pagar' => 'info',
                                            'Cancelado' => 'secondary'
                                        ];
                                        $class = $status_class[$expense['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo htmlspecialchars($expense['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($expense['paid_amount'] > 0): ?>
                                            <span class="text-success">$<?php echo number_format($expense['paid_amount'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">$0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($expense['pending_amount'] > 0): ?>
                                            <span class="text-danger">$<?php echo number_format($expense['pending_amount'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">$0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    onclick="viewExpense(<?php echo $expense['id']; ?>)"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (hasPermission('expenses.pay') && $expense['pending_amount'] > 0): ?>
                                                <button class="btn btn-outline-success btn-sm" 
                                                        onclick="addPayment(<?php echo $expense['id']; ?>)"
                                                        title="Registrar pago">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission('expenses.edit')): ?>
                                                <button class="btn btn-outline-warning btn-sm" 
                                                        onclick="editExpense(<?php echo $expense['id']; ?>)"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission('expenses.delete')): ?>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteExpense(<?php echo $expense['id']; ?>)"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Resumen de totales si hay datos -->
    <?php if (!empty($expenses)): ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h6>Total Gastos</h6>
                                <h4 class="text-primary">$<?php echo number_format(array_sum(array_column($expenses, 'amount')), 2); ?></h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Pagado</h6>
                                <h4 class="text-success">$<?php echo number_format(array_sum(array_column($expenses, 'paid_amount')), 2); ?></h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Pendiente</h6>
                                <h4 class="text-danger">$<?php echo number_format(array_sum(array_column($expenses, 'pending_amount')), 2); ?></h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Registros</h6>
                                <h4 class="text-info"><?php echo count($expenses); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modales (se crearán en archivos separados) -->
<!-- Modal Nuevo Gasto -->
<div class="modal fade" id="modalNewExpense" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-center text-muted">
                    <i class="fas fa-tools fa-2x"></i><br>
                    Funcionalidad en desarrollo
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal KPIs -->
<div class="modal fade" id="modalKPIs" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KPIs de Gastos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-center text-muted">
                    <i class="fas fa-chart-pie fa-2x"></i><br>
                    Funcionalidad en desarrollo
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Proveedores -->
<div class="modal fade" id="modalProviders" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestión de Proveedores</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-center text-muted">
                    <i class="fas fa-truck fa-2x"></i><br>
                    Funcionalidad en desarrollo
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Incluir modales -->
<?php include 'modals.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/expenses.js"></script>

</body>
</html>
