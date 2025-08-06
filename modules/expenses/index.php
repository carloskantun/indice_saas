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
$proveedor_id = $_GET['proveedor_id'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$estatus = $_GET['estatus'] ?? '';
$origen = $_GET['origen'] ?? '';
$orden = $_GET['orden'] ?? 'payment_date';
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
    COALESCE(e.folio, e.order_folio, CONCAT('EXP-', e.id)) AS folio, 
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
LEFT JOIN providers p ON e.provider_id = p.id AND p.company_id = ?
LEFT JOIN units u ON e.unit_id = u.id AND u.company_id = ?
$where_clause
ORDER BY $order_column $direction";

// Agregar company_id para los LEFT JOIN
$params = array_merge([$company_id, $company_id], $params);

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
    <!-- Contenedor de alertas -->
    <div id="alertContainer"></div>
    
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
                <button type="button" class="btn btn-success" id="btnNewExpense" data-bs-toggle="modal" data-bs-target="#expenseModal">
                    <i class="fas fa-plus me-2"></i>Nuevo Gasto
                </button>
                <button type="button" class="btn btn-primary" id="btnNewOrder" data-bs-toggle="modal" data-bs-target="#orderModal">
                    <i class="fas fa-file-invoice me-2"></i>Nueva Orden de Compra
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('providers.view')): ?>
                <button type="button" class="btn btn-info" id="btnNewProvider" data-bs-toggle="modal" data-bs-target="#providerModal">
                    <i class="fas fa-building me-2"></i>Proveedores
                </button>
                <?php endif; ?>
                
                <?php if (hasPermission('expenses.kpis')): ?>
                <button type="button" class="btn btn-warning" id="btnKPIs" data-bs-toggle="modal" data-bs-target="#kpisModal">
                    <i class="fas fa-chart-pie me-2"></i>KPIs
                </button>
                <?php endif; ?>
                
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-columns me-2"></i>Columnas
                    </button>
                    <ul class="dropdown-menu">
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="folio" checked> Folio</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="provider" checked> Proveedor</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="amount" checked> Monto</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="payment_date" checked> Fecha</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="unidad" checked> Unidad</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="tipo" checked> Tipo</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="tipo_compra" checked> Tipo Compra</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="medio" checked> Método Pago</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="cuenta" checked> Cuenta</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="concepto" checked> Concepto</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="status" checked> Estatus</label></li>
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
                    <select name="proveedor_id" class="form-select select2">
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
                        <option value="Por pagar" <?php echo $estatus === 'Por pagar' ? 'selected' : ''; ?>>Por pagar</option>
                        <option value="Pago parcial" <?php echo $estatus === 'Pago parcial' ? 'selected' : ''; ?>>Pago parcial</option>
                        <option value="Pagado" <?php echo $estatus === 'Pagado' ? 'selected' : ''; ?>>Pagado</option>
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
            
            <!-- Filtros Rápidos -->
            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-warning quick-filter" data-origen="" data-estatus="Por pagar">Por pagar</button>
                <button type="button" class="btn btn-sm btn-outline-danger quick-filter" data-origen="" data-estatus="Vencido">Vencidos</button>
                <button type="button" class="btn btn-sm btn-outline-success quick-filter" data-origen="" data-estatus="Pagado">Pagados</button>
                <button type="button" class="btn btn-sm btn-outline-info quick-filter" data-origen="Orden" data-estatus="Por pagar">Órdenes pendientes</button>
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
                                'provider' => 'Proveedor',
                                'amount' => 'Monto',
                                'payment_date' => 'Fecha',
                                'unidad' => 'Unidad',
                                'tipo' => 'Tipo',
                                'tipo_compra' => 'Tipo Compra',
                                'medio' => 'Método Pago',
                                'cuenta' => 'Cuenta',
                                'concepto' => 'Concepto',
                                'status' => 'Estatus',
                                'abonado' => 'Pagado',
                                'saldo' => 'Pendiente',
                                'comprobante' => 'Comprobante',
                                'accion' => 'Acciones'
                            ];
                            
                            foreach ($cols as $c => $label):
                                // Solo columnas ordenables
                                if (in_array($c, ['folio', 'provider', 'amount', 'payment_date', 'status'])):
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
                            <?php else: ?>
                            <th class="col-<?php echo $c; ?>">
                                <?php echo $label; ?>
                            </th>
                            <?php endif; ?>
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
                                <button class="btn btn-primary" id="btnCreateFirst" data-bs-toggle="modal" data-bs-target="#expenseModal">
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
                            
                            <td class="col-provider"><?php echo htmlspecialchars($expense['proveedor']); ?></td>
                            
                            <td class="col-amount monto">$<?php echo number_format($expense['amount'], 2); ?></td>
                            
                            <td class="col-payment_date"><?php echo htmlspecialchars($expense['payment_date']); ?></td>
                            
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
                                    <?php foreach (['Transferencia', 'Efectivo', 'Tarjeta'] as $op): ?>
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
                            
                            <td class="col-status">
                                <?php if (in_array($current_role, ['superadmin', 'admin'])): ?>
                                <select class="form-select form-select-sm editable-campo" data-id="<?= $expense['id']; ?>" data-campo="status">
                                    <?php foreach (['Por pagar', 'Pago parcial', 'Pagado', 'Vencido', 'Cancelado'] as $op): ?>
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
                                    
                                    <button class="btn btn-outline-secondary btn-sm btn-pdf" data-id="<?php echo $expense['id']; ?>" title="Generar PDF">
                                        <i class="fas fa-file-pdf"></i>
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
<script src="js/expenses-debug.js"></script>

</body>
</html>
