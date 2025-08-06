<?php
/**
 * MÓDULO GASTOS COMPLETO - SISTEMA SAAS INDICE
 * Vista principal con todas las funcionalidades del sistema original
 * Incluye: columnas reordenables, edición en línea, órdenes de compra, totales dinámicos
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
        'admin' => ['expenses.view', 'expenses.create', 'expenses.edit', 'expenses.pay', 'expenses.export', 'expenses.kpis', 'providers.view', 'providers.create', 'providers.edit'],
        'moderator' => ['expenses.view', 'expenses.create', 'expenses.pay', 'providers.view'],
        'user' => ['expenses.view', 'providers.view']
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
$proveedor_id = $_GET['proveedor'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$estatus = $_GET['estatus'] ?? '';
$origen = $_GET['origen'] ?? '';
$orden = $_GET['orden'] ?? 'fecha';
$dir = strtoupper($_GET['dir'] ?? 'DESC');

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

// Mapeo de columnas para ordenamiento
$order_map = [
    'folio' => 'e.folio',
    'proveedor' => 'p.name',
    'monto' => 'e.amount',
    'fecha' => 'e.payment_date',
    'unidad' => 'u.name',
    'tipo' => 'e.expense_type',
    'tipo_compra' => 'e.purchase_type',
    'medio' => 'e.payment_method',
    'cuenta' => 'e.bank_account',
    'concepto' => 'e.concept',
    'estatus' => 'e.status'
];

$order_column = $order_map[$orden] ?? 'e.payment_date';
$dir = $dir === 'ASC' ? 'ASC' : 'DESC';

// Consulta principal
$sql = "SELECT 
    e.id, 
    e.folio, 
    COALESCE(p.name, 'Sin proveedor') AS proveedor, 
    e.amount, 
    e.payment_date, 
    u.name AS unidad, 
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
$where_clause
ORDER BY $order_column $dir";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// KPIs
$kpi_month_sql = "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ? AND business_id = ? AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
$stmt = $db->prepare($kpi_month_sql);
$stmt->execute([$company_id, $business_id]);
$kpi_month = $stmt->fetchColumn();

$kpi_year_sql = "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ? AND business_id = ? AND YEAR(payment_date) = YEAR(CURDATE())";
$stmt = $db->prepare($kpi_year_sql);
$stmt->execute([$company_id, $business_id]);
$kpi_year = $stmt->fetchColumn();

// Proveedores para filtros
$providers_sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
$stmt = $db->prepare($providers_sql);
$stmt->execute([$company_id]);
$providers = $stmt->fetchAll();

// Unidades para filtros
$units_sql = "SELECT id, name FROM units WHERE company_id = ? ORDER BY name";
$stmt = $db->prepare($units_sql);
$stmt->execute([$company_id]);
$units = $stmt->fetchAll();
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
    <link href="css/expenses.css" rel="stylesheet">
    <style>
        .expense-row.overdue { background-color: #ffebee; }
        .expense-row.paid { background-color: #e8f5e8; }
        .expense-row.partial { background-color: #fff3e0; }
        .sortable-header { cursor: move; }
        .btn-group-toggle .btn { margin: 2px; }
        .table-responsive { max-height: 70vh; overflow-y: auto; }
        .sticky-header th { position: sticky; top: 0; background: white; z-index: 10; }
        .editable-campo { width: 100%; min-width: 100px; }
        
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
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- Header con KPIs -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-receipt me-2"></i>Gestión de Gastos</h1>
            <p class="text-muted">Administra gastos, órdenes de compra y proveedores</p>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Gastos del Mes</h5>
                    <h3 class="text-primary">$<?php echo number_format($kpi_month, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Gastos del Año</h5>
                    <h3 class="text-success">$<?php echo number_format($kpi_year, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php if (hasPermission('expenses.create')): ?>
                <button type="button" class="btn btn-success" id="btnNewExpense">
                    <i class="fas fa-plus me-2"></i>Nuevo Gasto
                </button>
                <button type="button" class="btn btn-primary" id="btnNewOrder">
                    <i class="fas fa-file-invoice me-2"></i>Nueva Orden de Compra
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('providers.view')): ?>
                <button type="button" class="btn btn-info" id="btnNewProvider">
                    <i class="fas fa-building me-2"></i>Proveedores
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('expenses.kpis')): ?>
                <button type="button" class="btn btn-warning" id="btnKPIs">
                    <i class="fas fa-chart-pie me-2"></i>KPIs
                </button>
                <?php endif; ?>
                
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-columns me-2"></i>Columnas
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="folio" checked> Folio</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="proveedor" checked> Proveedor</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="monto" checked> Monto</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="fecha" checked> Fecha</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="unidad" checked> Unidad</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="tipo" checked> Tipo</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="tipo_compra" checked> Tipo Compra</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="medio" checked> Método Pago</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="cuenta" checked> Cuenta</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="concepto" checked> Concepto</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="estatus" checked> Estatus</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="abonado" checked> Pagado</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="saldo" checked> Pendiente</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="comprobante" checked> Comprobante</label></li>
                    </ul>
                </div>
                
                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                <button type="button" class="btn btn-outline-danger d-none" id="btnDeleteSelected">
                    <i class="fas fa-trash me-2"></i>Eliminar Seleccionados
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card filters-section mb-4">
        <div class="card-body">
            <form method="GET" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor" class="form-select select2">
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($providers as $provider): ?>
                        <option value="<?php echo $provider['id']; ?>" <?php echo $proveedor_id == $provider['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($provider['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Estatus</label>
                    <select name="estatus" class="form-select">
                        <option value="">Todos</option>
                        <option value="Pendiente" <?php echo $estatus === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="Pago parcial" <?php echo $estatus === 'Pago parcial' ? 'selected' : ''; ?>>Pago parcial</option>
                        <option value="Pagado" <?php echo $estatus === 'Pagado' ? 'selected' : ''; ?>>Pagado</option>
                        <option value="Cancelado" <?php echo $estatus === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Origen</label>
                    <select name="origen" class="form-select">
                        <option value="">Todos</option>
                        <option value="Directo" <?php echo $origen === 'Directo' ? 'selected' : ''; ?>>Directo</option>
                        <option value="Orden" <?php echo $origen === 'Orden' ? 'selected' : ''; ?>>Orden</option>
                        <option value="Requisicion" <?php echo $origen === 'Requisicion' ? 'selected' : ''; ?>>Requisición</option>
                    </select>
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- Filtros Rápidos -->
            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-warning quick-filter" data-origen="" data-estatus="Pendiente">Pendientes</button>
                <button type="button" class="btn btn-sm btn-outline-danger quick-filter" data-origen="" data-estatus="Vencido">Vencidos</button>
                <button type="button" class="btn btn-sm btn-outline-success quick-filter" data-origen="" data-estatus="Pagado">Pagados</button>
                <button type="button" class="btn btn-sm btn-outline-info quick-filter" data-origen="Orden" data-estatus="Pendiente">Órdenes pendientes</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearFilters">Limpiar</button>
            </div>
        </div>
    </div>

    <!-- Tabla de Gastos -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="sticky-header">
                        <tr id="columnas-reordenables">
                            <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                            <th class="col-seleccion">
                                <input type="checkbox" id="seleccionar-todos">
                            </th>
                            <?php endif; ?>
                            
                            <?php
                            $cols = [
                                'folio' => 'Folio',
                                'proveedor' => 'Proveedor',
                                'monto' => 'Monto',
                                'fecha' => 'Fecha',
                                'unidad' => 'Unidad',
                                'tipo' => 'Tipo',
                                'tipo_compra' => 'Tipo Compra',
                                'medio' => 'Método Pago',
                                'cuenta' => 'Cuenta',
                                'concepto' => 'Concepto',
                                'estatus' => 'Estatus',
                                'abonado' => 'Pagado',
                                'saldo' => 'Pendiente',
                                'comprobante' => 'Comprobante',
                                'accion' => 'Acciones'
                            ];
                            
                            foreach ($cols as $c => $label):
                                $params = $_GET;
                                $params['orden'] = $c;
                                $params['dir'] = ($orden === $c && $dir === 'ASC') ? 'DESC' : 'ASC';
                                $url = '?' . http_build_query($params);
                                $icon = ($orden === $c) ? ($dir === 'DESC' ? '▼' : '▲') : '';
                            ?>
                            <th class="col-<?php echo $c; ?> sortable-header">
                                <a href="<?php echo htmlspecialchars($url); ?>" style="text-decoration:none;color:inherit;">
                                    <?php echo $label . ' ' . $icon; ?>
                                </a>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="15" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron gastos con los filtros aplicados</p>
                                <?php if (hasPermission('expenses.create')): ?>
                                <button class="btn btn-primary" id="btnCreateFirst">
                                    <i class="fas fa-plus me-2"></i>Crear primer gasto
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($expenses as $expense): ?>
                        <tr class="expense-row <?php echo $expense['status'] === 'Pagado' ? 'paid' : ($expense['status'] === 'Pago parcial' ? 'partial' : ''); ?>">
                            <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                            <td class="col-seleccion">
                                <input type="checkbox" class="seleccionar-gasto" value="<?php echo $expense['id']; ?>">
                            </td>
                            <?php endif; ?>
                            
                            <td class="col-folio"><?php echo htmlspecialchars($expense['folio']); ?></td>
                            
                            <td class="col-proveedor"><?php echo htmlspecialchars($expense['proveedor']); ?></td>
                            
                            <td class="col-monto monto">$<?php echo number_format($expense['amount'], 2); ?></td>
                            
                            <td class="col-fecha"><?php echo htmlspecialchars($expense['payment_date']); ?></td>
                            
                            <td class="col-unidad"><?php echo htmlspecialchars($expense['unidad']); ?></td>
                            
                            <td class="col-tipo">
                                <?php
                                $origen = $expense['origin'];
                                $tipo = $expense['expense_type'];
                                $estatus = $expense['status'];
                                
                                if ($origen === 'Orden') {
                                    if ($estatus === 'Pagado') {
                                        echo "Orden ($tipo) → Gasto";
                                    } else {
                                        echo "Orden ($tipo)";
                                    }
                                } else {
                                    echo "Gasto ($tipo)";
                                }
                                ?>
                            </td>
                            
                            <td class="col-tipo_compra">
                                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                <select class="form-select form-select-sm editable-campo" data-id="<?= $expense['id']; ?>" data-campo="purchase_type">
                                    <?php foreach (['Venta', 'Administrativa', 'Operativo', 'Impuestos', 'Intereses/Créditos'] as $op): ?>
                                        <option value="<?= $op ?>" <?= $expense['purchase_type'] === $op ? 'selected' : '' ?>><?= $op ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?= htmlspecialchars($expense['purchase_type']) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-medio">
                                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                <select class="form-select form-select-sm editable-campo" data-id="<?= $expense['id']; ?>" data-campo="payment_method">
                                    <?php foreach (['Transferencia', 'Efectivo', 'Cheque', 'Tarjeta'] as $op): ?>
                                        <option value="<?= $op ?>" <?= $expense['payment_method'] === $op ? 'selected' : '' ?>><?= $op ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?= htmlspecialchars($expense['payment_method']) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-cuenta">
                                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                <input type="text" class="form-control form-control-sm editable-campo" data-id="<?= $expense['id']; ?>" data-campo="bank_account" value="<?= htmlspecialchars($expense['bank_account']) ?>">
                                <?php else: ?>
                                <?= htmlspecialchars($expense['bank_account']) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-concepto">
                                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                <input type="text" class="form-control form-control-sm editable-campo" data-id="<?= $expense['id']; ?>" data-campo="concept" value="<?= htmlspecialchars($expense['concept']) ?>">
                                <?php else: ?>
                                <?= htmlspecialchars($expense['concept']) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-estatus">
                                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                <select class="form-select form-select-sm editable-campo" data-id="<?= $expense['id']; ?>" data-campo="status">
                                    <?php foreach (['Pendiente', 'Pago parcial', 'Pagado', 'Cancelado'] as $op): ?>
                                        <option value="<?= $op ?>" <?= $expense['status'] === $op ? 'selected' : '' ?>><?= $op ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="badge <?php echo $expense['status'] === 'Pagado' ? 'bg-success' : ($expense['status'] === 'Pago parcial' ? 'bg-warning' : 'bg-danger'); ?>">
                                    <?= htmlspecialchars($expense['status']) ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-abonado abono">$<?php echo number_format($expense['paid_amount'], 2); ?></td>
                            
                            <td class="col-saldo saldo">$<?php echo number_format($expense['pending_amount'], 2); ?></td>
                            
                            <td class="col-comprobante">
                                <span class="text-muted">Sin archivo</span>
                            </td>
                            
                            <td class="col-accion">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-primary btn-sm btn-view" data-id="<?php echo $expense['id']; ?>" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($expense['pending_amount'] > 0 && hasPermission('expenses.pay')): ?>
                                    <button class="btn btn-outline-success btn-sm btn-pay" data-id="<?php echo $expense['id']; ?>" title="Registrar pago">
                                        <i class="fas fa-money-bill"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('expenses.edit')): ?>
                                    <button class="btn btn-outline-warning btn-sm btn-edit" data-id="<?php echo $expense['id']; ?>" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                    <button class="btn btn-outline-danger btn-sm btn-delete" data-id="<?php echo $expense['id']; ?>" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot id="tfoot-dinamico"></tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Resumen de seleccionados -->
    <div id="resumen-seleccionados" class="alert alert-light border-top border-dark fixed-bottom shadow-sm py-2 px-4 d-flex justify-content-between align-items-center d-none">
        <div>
            <strong>Totales Seleccionados:</strong>
            Monto: <span id="sel-monto">$0.00</span> —
            Pagado: <span id="sel-abono">$0.00</span> —
            Pendiente: <span id="sel-saldo">$0.00</span>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-danger me-2" id="btn-exportar-pdf">Exportar PDF</button>
            <button class="btn btn-sm btn-outline-success" id="btn-exportar-csv">Exportar CSV</button>
        </div>
    </div>
</div>

<!-- Incluir modales -->
<?php include 'modals.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/expenses.js"></script>

<script>
// Funcionalidad de columnas (mostrar/ocultar)
document.addEventListener('DOMContentLoaded', function() {
    const KEY = 'gastos_columnas';
    
    function save() {
        const c = {};
        document.querySelectorAll('.col-toggle').forEach(cb => {
            c[cb.dataset.col] = cb.checked;
        });
        localStorage.setItem(KEY, JSON.stringify(c));
    }
    
    function restore() {
        const c = JSON.parse(localStorage.getItem(KEY) || '{}');
        document.querySelectorAll('.col-toggle').forEach(cb => {
            if (c.hasOwnProperty(cb.dataset.col)) {
                cb.checked = c[cb.dataset.col];
            }
            document.querySelectorAll('.col-' + cb.dataset.col).forEach(el => {
                el.style.display = cb.checked ? '' : 'none';
                if (c.hasOwnProperty(cb.dataset.col)) {
                    el.style.display = c[cb.dataset.col] ? '' : 'none';
                }
            });
        });
    }
    
    restore();
    
    document.querySelectorAll('.col-toggle').forEach(cb => 
        cb.addEventListener('change', function() {
            document.querySelectorAll('.col-' + this.dataset.col).forEach(el => {
                el.style.display = this.checked ? '' : 'none';
            });
            save();
        })
    );
});

// Funcionalidad de columnas reordenables
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Sortable !== 'undefined') {
        const columnas = document.getElementById('columnas-reordenables');
        const tabla = document.querySelector('table');
        
        Sortable.create(columnas, {
            animation: 150,
            onEnd: () => {
                let order = [];
                columnas.querySelectorAll('th').forEach(th => order.push(th.className));
                localStorage.setItem('orden_columnas_gastos', JSON.stringify(order));
                
                let filas = tabla.querySelectorAll('tbody tr');
                filas.forEach(tr => {
                    let celdas = Array.from(tr.children);
                    let nuevo = [];
                    order.forEach(cls => {
                        let cel = celdas.find(td => td.classList.contains(cls));
                        if (cel) nuevo.push(cel);
                    });
                    nuevo.forEach(td => tr.appendChild(td));
                });
            }
        });
        
        // Restaurar orden guardado
        let saved = JSON.parse(localStorage.getItem('orden_columnas_gastos') || '[]');
        if (saved.length > 0) {
            let ths = Array.from(columnas.children);
            let nuevo = [];
            saved.forEach(cls => {
                let th = ths.find(el => el.classList.contains(cls));
                if (th) nuevo.push(th);
            });
            nuevo.forEach(th => columnas.appendChild(th));
            
            let filas = tabla.querySelectorAll('tbody tr');
            filas.forEach(tr => {
                let celdas = Array.from(tr.children);
                let nuevo = [];
                saved.forEach(cls => {
                    let cel = celdas.find(td => td.classList.contains(cls));
                    if (cel) nuevo.push(cel);
                });
                nuevo.forEach(td => tr.appendChild(td));
            });
        }
    }
});

// Edición en línea
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('editable-campo')) {
        const id = e.target.dataset.id;
        const campo = e.target.dataset.campo;
        const valor = e.target.value;
        
        fetch('controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_field&expense_id=${id}&field=${campo}&value=${encodeURIComponent(valor)}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error al actualizar: ' + (data.error || 'Error desconocido'));
                location.reload(); // Recargar para restaurar valor original
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }
});

// Filtros rápidos
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quick-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('filterForm');
            if (!form) return;
            
            // Limpiar todos los campos
            form.querySelectorAll('select, input').forEach(el => {
                el.value = '';
            });
            
            // Asignar filtros del botón
            const est = this.dataset.estatus || '';
            const ori = this.dataset.origen || '';
            form.querySelector('[name="estatus"]').value = est;
            form.querySelector('[name="origen"]').value = ori;
            
            form.submit();
        });
    });
});

// Totales dinámicos
function calcularTotales() {
    const tabla = document.querySelector('table');
    const cuerpo = tabla.querySelector('tbody');
    const filas = cuerpo.querySelectorAll('tr');
    
    let totalMonto = 0, totalAbono = 0, totalSaldo = 0;
    
    filas.forEach(tr => {
        const monto = parseFloat(tr.querySelector('.col-monto')?.textContent.replace(/[$,]/g, '') || 0);
        const abonado = parseFloat(tr.querySelector('.col-abonado')?.textContent.replace(/[$,]/g, '') || 0);
        const saldo = parseFloat(tr.querySelector('.col-saldo')?.textContent.replace(/[$,]/g, '') || 0);
        
        totalMonto += monto;
        totalAbono += abonado;
        totalSaldo += saldo;
    });
    
    const columnas = tabla.querySelectorAll('thead th');
    const tfoot = document.getElementById('tfoot-dinamico');
    const fila = document.createElement('tr');
    
    columnas.forEach(th => {
        const td = document.createElement('td');
        const clase = th.className;
        
        if (clase.includes('col-monto')) {
            td.innerHTML = `<strong>$${totalMonto.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong>`;
        } else if (clase.includes('col-abonado')) {
            td.innerHTML = `<strong>$${totalAbono.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong>`;
        } else if (clase.includes('col-saldo')) {
            td.innerHTML = `<strong>$${totalSaldo.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong>`;
        } else if (clase.includes('col-folio')) {
            td.innerHTML = '<strong>Totales:</strong>';
        } else {
            td.innerHTML = '';
        }
        
        fila.appendChild(td);
    });
    
    tfoot.innerHTML = '';
    tfoot.appendChild(fila);
}

document.addEventListener('DOMContentLoaded', calcularTotales);

// Selección múltiple
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.seleccionar-gasto');
    const btnEliminar = document.getElementById('btnDeleteSelected');
    const chkTodos = document.getElementById('seleccionar-todos');
    
    function actualizarBoton() {
        const algunoMarcado = Array.from(checkboxes).some(cb => cb.checked);
        if (btnEliminar) {
            btnEliminar.classList.toggle('d-none', !algunoMarcado);
        }
        
        // Actualizar resumen
        const resumen = document.getElementById('resumen-seleccionados');
        if (algunoMarcado) {
            let totalMonto = 0, totalAbono = 0, totalSaldo = 0;
            
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const row = cb.closest('tr');
                    totalMonto += parseFloat(row.querySelector('.col-monto')?.textContent.replace(/[$,]/g, '') || 0);
                    totalAbono += parseFloat(row.querySelector('.col-abonado')?.textContent.replace(/[$,]/g, '') || 0);
                    totalSaldo += parseFloat(row.querySelector('.col-saldo')?.textContent.replace(/[$,]/g, '') || 0);
                }
            });
            
            document.getElementById('sel-monto').textContent = totalMonto.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
            document.getElementById('sel-abono').textContent = totalAbono.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
            document.getElementById('sel-saldo').textContent = totalSaldo.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
            
            resumen.classList.remove('d-none');
        } else {
            resumen.classList.add('d-none');
        }
    }
    
    checkboxes.forEach(cb => cb.addEventListener('change', actualizarBoton));
    
    if (chkTodos) {
        chkTodos.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = chkTodos.checked);
            actualizarBoton();
        });
    }
    
    // Eliminar seleccionados
    if (btnEliminar) {
        btnEliminar.addEventListener('click', function() {
            const ids = Array.from(document.querySelectorAll('.seleccionar-gasto'))
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (!ids.length) return;
            
            if (!confirm('¿Está seguro de eliminar los gastos seleccionados?')) return;
            
            const params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));
            
            fetch('controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=delete_multiple&' + params.toString()
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (res.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error de conexión');
            });
        });
    }
});
</script>

</body>
</html>
