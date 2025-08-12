<?php
/**
 * MÓDULO PROCESOS Y TAREAS - SISTEMA SAAS INDICE
 * Vista principal con funcionalidades de gestión de procesos operativos y tareas
 * Basado en la plantilla del módulo de gastos y recursos humanos
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

// Obtener información de la empresa actual
$company_info = getCurrentCompany();
if (!$company_info) {
    redirect('companies/');
}

// Obtener estadísticas principales
$stats = [
    'active_processes' => 0,
    'pending_tasks' => 0,
    'overdue_tasks' => 0,
    'my_tasks' => 0,
    'completion_rate' => 0,
    'avg_completion_time' => 0
];

// Si tiene permisos, obtener estadísticas reales
if (hasPermission('processes.view')) {
    try {
        // Procesos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM processes WHERE company_id = ? AND status = 'active'");
        $stmt->execute([$company_info['company_id']]);
        $stats['active_processes'] = $stmt->fetchColumn();
        
        // Tareas pendientes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND status IN ('pending', 'in_progress')");
        $stmt->execute([$company_info['company_id']]);
        $stats['pending_tasks'] = $stmt->fetchColumn();
        
        // Tareas vencidas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND status NOT IN ('completed', 'cancelled') AND due_date < NOW()");
        $stmt->execute([$company_info['company_id']]);
        $stats['overdue_tasks'] = $stmt->fetchColumn();
        
        // Mis tareas (si es empleado)
        if (isset($_SESSION['employee_id'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND assigned_to = ? AND status NOT IN ('completed', 'cancelled')");
            $stmt->execute([$company_info['company_id'], $_SESSION['employee_id']]);
            $stats['my_tasks'] = $stmt->fetchColumn();
        }
        
        // Tasa de completado (últimos 30 días)
        $stmt = $pdo->prepare("
            SELECT 
                (SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as completion_rate
            FROM tasks 
            WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$company_info['company_id']]);
        $result = $stmt->fetch();
        $stats['completion_rate'] = round($result['completion_rate'] ?? 0, 1);
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas de procesos: " . $e->getMessage());
    }
}

$page_title = __('processes_tasks');
$current_module = 'processes-tasks';
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('processes_tasks') ?> - <?= $company_info['name'] ?></title>
    
    <!-- CSS Framework y estilos base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
    
    <!-- Estilos del sistema -->
    <link href="../../css/style.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
    <link href="css/processes-tasks.css" rel="stylesheet">
    
    <!-- Meta tags para PWA -->
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../../">
                <i class="fas fa-cube me-2"></i>
                <?= __('app_name') ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-building me-1"></i>
                    <?= htmlspecialchars($company_info['name']) ?>
                </span>
                <a class="nav-link" href="../../" title="<?= __('dashboard') ?>">
                    <i class="fas fa-home"></i>
                </a>
                <a class="nav-link" href="../../auth/logout.php" title="<?= __('logout') ?>">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header con estadísticas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            <?= __('processes_tasks') ?>
                        </h1>
                        <p class="text-muted mb-0"><?= __('manage_operational_flows_and_tasks') ?></p>
                    </div>
                </div>
                
                <!-- KPIs Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fs-4 fw-bold"><?= number_format($stats['active_processes']) ?></div>
                                        <div class="small"><?= __('active_processes') ?></div>
                                    </div>
                                    <i class="fas fa-cogs fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fs-4 fw-bold"><?= number_format($stats['pending_tasks']) ?></div>
                                        <div class="small"><?= __('pending_tasks') ?></div>
                                    </div>
                                    <i class="fas fa-tasks fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-<?= $stats['overdue_tasks'] > 0 ? 'danger' : 'warning' ?> text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fs-4 fw-bold"><?= number_format($stats['overdue_tasks']) ?></div>
                                        <div class="small"><?= __('overdue_tasks') ?></div>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fs-4 fw-bold"><?= $stats['completion_rate'] ?>%</div>
                                        <div class="small"><?= __('completion_rate') ?></div>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas principales -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs nav-tabs-custom border-bottom-0" id="mainTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="processes-tab" data-bs-toggle="tab" data-bs-target="#processes" type="button" role="tab">
                                    <i class="fas fa-cogs me-2"></i><?= __('processes') ?>
                                    <span class="badge bg-primary ms-2"><?= $stats['active_processes'] ?></span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                                    <i class="fas fa-tasks me-2"></i><?= __('tasks') ?>
                                    <span class="badge bg-success ms-2"><?= $stats['pending_tasks'] ?></span>
                                </button>
                            </li>
                            <?php if (hasPermission('reports.view')): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                                    <i class="fas fa-chart-bar me-2"></i><?= __('reports') ?>
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if (hasPermission('templates.view')): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                                    <i class="fas fa-copy me-2"></i><?= __('templates') ?>
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <!-- Tab content -->
                        <div class="tab-content" id="mainTabsContent">
                            <!-- Procesos Tab -->
                            <div class="tab-pane fade show active" id="processes" role="tabpanel">
                                <div class="p-4">
                                    <!-- Toolbar de Procesos -->
                                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3 gap-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if (hasPermission('processes.create')): ?>
                                            <button class="btn btn-primary" onclick="openNewProcessModal()">
                                                <i class="fas fa-plus me-2"></i><?= __('new_process') ?>
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="openTemplateModal()">
                                                <i class="fas fa-copy me-2"></i><?= __('create_from_template') ?>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('processes.export')): ?>
                                            <button class="btn btn-outline-secondary" onclick="exportProcesses()">
                                                <i class="fas fa-download me-2"></i><?= __('export') ?>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex flex-wrap gap-2">
                                            <div class="btn-group">
                                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-columns me-2"></i><?= __('columns') ?>
                                                </button>
                                                <ul class="dropdown-menu" id="processColumnsDropdown">
                                                    <!-- Se llena dinámicamente con JS -->
                                                </ul>
                                            </div>
                                            <button class="btn btn-outline-secondary" onclick="toggleProcessFilters()">
                                                <i class="fas fa-filter me-2"></i><?= __('filters') ?>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Filtros de Procesos (ocultos por defecto) -->
                                    <div class="card bg-light mb-3 d-none" id="processFilters">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label"><?= __('process_status') ?></label>
                                                    <select class="form-select" id="filterProcessStatus">
                                                        <option value=""><?= __('all') ?></option>
                                                        <option value="draft"><?= __('draft') ?></option>
                                                        <option value="active"><?= __('active') ?></option>
                                                        <option value="paused"><?= __('paused') ?></option>
                                                        <option value="completed"><?= __('completed') ?></option>
                                                        <option value="cancelled"><?= __('cancelled') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?= __('priority') ?></label>
                                                    <select class="form-select" id="filterProcessPriority">
                                                        <option value=""><?= __('all') ?></option>
                                                        <option value="low"><?= __('low_priority') ?></option>
                                                        <option value="medium"><?= __('medium_priority') ?></option>
                                                        <option value="high"><?= __('high_priority') ?></option>
                                                        <option value="critical"><?= __('critical_priority') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?= __('department') ?></label>
                                                    <select class="form-select" id="filterProcessDepartment">
                                                        <option value=""><?= __('all_departments') ?></option>
                                                        <!-- Se llena dinámicamente -->
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?= __('date_range') ?></label>
                                                    <input type="text" class="form-control" id="filterProcessDate" placeholder="<?= __('select_date_range') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabla de Procesos -->
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="processesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="sortable" data-column="name">
                                                        <i class="fas fa-grip-vertical text-muted me-2 drag-handle"></i>
                                                        <?= __('process_name') ?>
                                                        <i class="fas fa-sort ms-1"></i>
                                                    </th>
                                                    <th class="sortable" data-column="department"><?= __('department') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="status"><?= __('status') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="priority"><?= __('priority') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="progress"><?= __('progress') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="tasks"><?= __('tasks') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="created_at"><?= __('created') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="text-center"><?= __('actions') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Se llena dinámicamente con AJAX -->
                                                <tr>
                                                    <td colspan="8" class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden"><?= __('loading') ?>...</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Paginación -->
                                    <nav>
                                        <ul class="pagination justify-content-center" id="processesPagination">
                                            <!-- Se llena dinámicamente -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>

                            <!-- Tareas Tab -->
                            <div class="tab-pane fade" id="tasks" role="tabpanel">
                                <div class="p-4">
                                    <!-- Toolbar de Tareas -->
                                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3 gap-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if (hasPermission('tasks.create')): ?>
                                            <button class="btn btn-success" onclick="openNewTaskModal()">
                                                <i class="fas fa-plus me-2"></i><?= __('new_task') ?>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('tasks.assign')): ?>
                                            <button class="btn btn-outline-success" onclick="openBulkAssignModal()" disabled id="btnBulkAssign">
                                                <i class="fas fa-users me-2"></i><?= __('assign_multiple') ?>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-primary" onclick="showMyTasks()">
                                                <i class="fas fa-user me-2"></i><?= __('my_tasks') ?>
                                            </button>
                                            <?php if (hasPermission('tasks.export')): ?>
                                            <button class="btn btn-outline-secondary" onclick="exportTasks()">
                                                <i class="fas fa-download me-2"></i><?= __('export') ?>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex flex-wrap gap-2">
                                            <div class="btn-group">
                                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-columns me-2"></i><?= __('columns') ?>
                                                </button>
                                                <ul class="dropdown-menu" id="taskColumnsDropdown">
                                                    <!-- Se llena dinámicamente con JS -->
                                                </ul>
                                            </div>
                                            <button class="btn btn-outline-secondary" onclick="toggleTaskFilters()">
                                                <i class="fas fa-filter me-2"></i><?= __('filters') ?>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Filtros de Tareas (ocultos por defecto) -->
                                    <div class="card bg-light mb-3 d-none" id="taskFilters">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-2">
                                                    <label class="form-label"><?= __('task_status') ?></label>
                                                    <select class="form-select" id="filterTaskStatus">
                                                        <option value=""><?= __('all') ?></option>
                                                        <option value="pending"><?= __('pending') ?></option>
                                                        <option value="in_progress"><?= __('in_progress') ?></option>
                                                        <option value="review"><?= __('review') ?></option>
                                                        <option value="completed"><?= __('completed') ?></option>
                                                        <option value="cancelled"><?= __('cancelled') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label"><?= __('priority') ?></label>
                                                    <select class="form-select" id="filterTaskPriority">
                                                        <option value=""><?= __('all') ?></option>
                                                        <option value="low"><?= __('low_priority') ?></option>
                                                        <option value="medium"><?= __('medium_priority') ?></option>
                                                        <option value="high"><?= __('high_priority') ?></option>
                                                        <option value="critical"><?= __('critical_priority') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label"><?= __('assigned_to') ?></label>
                                                    <select class="form-select" id="filterTaskAssigned">
                                                        <option value=""><?= __('all') ?></option>
                                                        <!-- Se llena dinámicamente -->
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label"><?= __('department') ?></label>
                                                    <select class="form-select" id="filterTaskDepartment">
                                                        <option value=""><?= __('all_departments') ?></option>
                                                        <!-- Se llena dinámicamente -->
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label"><?= __('due_date') ?></label>
                                                    <select class="form-select" id="filterTaskDue">
                                                        <option value=""><?= __('all') ?></option>
                                                        <option value="overdue"><?= __('overdue') ?></option>
                                                        <option value="today"><?= __('due_today') ?></option>
                                                        <option value="week"><?= __('due_this_week') ?></option>
                                                        <option value="month"><?= __('due_this_month') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label"><?= __('search') ?></label>
                                                    <input type="text" class="form-control" id="filterTaskSearch" placeholder="<?= __('search_tasks') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabla de Tareas -->
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="tasksTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center">
                                                        <input type="checkbox" class="form-check-input" id="selectAllTasks">
                                                    </th>
                                                    <th class="sortable" data-column="title">
                                                        <i class="fas fa-grip-vertical text-muted me-2 drag-handle"></i>
                                                        <?= __('task_title') ?>
                                                        <i class="fas fa-sort ms-1"></i>
                                                    </th>
                                                    <th class="sortable" data-column="process"><?= __('process') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="assigned_to"><?= __('assigned_to') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="status"><?= __('status') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="priority"><?= __('priority') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="due_date"><?= __('due_date') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="sortable" data-column="progress"><?= __('progress') ?> <i class="fas fa-sort ms-1"></i></th>
                                                    <th class="text-center"><?= __('actions') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Se llena dinámicamente con AJAX -->
                                                <tr>
                                                    <td colspan="9" class="text-center py-4">
                                                        <div class="spinner-border text-success" role="status">
                                                            <span class="visually-hidden"><?= __('loading') ?>...</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Paginación -->
                                    <nav>
                                        <ul class="pagination justify-content-center" id="tasksPagination">
                                            <!-- Se llena dinámicamente -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>

                            <!-- Reportes Tab -->
                            <?php if (hasPermission('reports.view')): ?>
                            <div class="tab-pane fade" id="reports" role="tabpanel">
                                <div class="p-4">
                                    <div class="row g-4">
                                        <!-- Próximamente - Reportes avanzados -->
                                        <div class="col-12 text-center py-5">
                                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                            <h4 class="text-muted"><?= __('advanced_reports_coming_soon') ?></h4>
                                            <p class="text-muted"><?= __('detailed_analytics_and_productivity_reports') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Plantillas Tab -->
                            <?php if (hasPermission('templates.view')): ?>
                            <div class="tab-pane fade" id="templates" role="tabpanel">
                                <div class="p-4">
                                    <div class="row g-4">
                                        <!-- Próximamente - Gestión de plantillas -->
                                        <div class="col-12 text-center py-5">
                                            <i class="fas fa-copy fa-3x text-muted mb-3"></i>
                                            <h4 class="text-muted"><?= __('workflow_templates_coming_soon') ?></h4>
                                            <p class="text-muted"><?= __('reusable_process_and_task_templates') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir modales -->
    <?php require_once 'modals.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Scripts del módulo -->
    <script src="js/processes-tasks.js"></script>
    
    <script>
        // Configuración global del módulo
        window.ProcessesTasksConfig = {
            permissions: {
                processes: {
                    view: <?= hasPermission('processes.view') ? 'true' : 'false' ?>,
                    create: <?= hasPermission('processes.create') ? 'true' : 'false' ?>,
                    edit: <?= hasPermission('processes.edit') ? 'true' : 'false' ?>,
                    delete: <?= hasPermission('processes.delete') ? 'true' : 'false' ?>,
                    export: <?= hasPermission('processes.export') ? 'true' : 'false' ?>
                },
                tasks: {
                    view: <?= hasPermission('tasks.view') ? 'true' : 'false' ?>,
                    create: <?= hasPermission('tasks.create') ? 'true' : 'false' ?>,
                    edit: <?= hasPermission('tasks.edit') ? 'true' : 'false' ?>,
                    delete: <?= hasPermission('tasks.delete') ? 'true' : 'false' ?>,
                    assign: <?= hasPermission('tasks.assign') ? 'true' : 'false' ?>,
                    complete: <?= hasPermission('tasks.complete') ? 'true' : 'false' ?>
                }
            },
            currentEmployeeId: <?= $_SESSION['employee_id'] ?? 'null' ?>,
            language: '<?= getCurrentLanguage() ?>',
            apiUrl: 'controller.php'
        };

        // Inicializar módulo al cargar la página
        $(document).ready(function() {
            ProcessesTasksApp.init();
        });
    </script>
</body>
</html>
