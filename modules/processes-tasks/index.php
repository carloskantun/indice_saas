<?php
/**
 * MÓDULO PROCESOS Y TAREAS - SISTEMA SAAS INDICE
 * Vista principal simplificada
 */

session_start();
require_once '../../config.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// Obtener company_id - ajustar según su sistema
$company_id = 1; // Por defecto

// Intentar obtener company_id de diferentes fuentes
if (isset($_SESSION['company_id'])) {
    $company_id = $_SESSION['company_id'];
} elseif (isset($_SESSION['current_company_id'])) {
    $company_id = $_SESSION['current_company_id'];
} else {
    // Obtener desde la base de datos usando getDB()
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

// Función para verificar permisos
function hasPermission($permission) {
    global $user_role;
    
    $permission_map = [
        'root' => ['read', 'write', 'delete', 'admin'],
        'superadmin' => ['read', 'write', 'delete', 'admin'],
        'admin' => ['read', 'write', 'delete'],
        'moderator' => ['read', 'write'],
        'user' => ['read']
    ];
    
    return in_array($permission, $permission_map[$user_role] ?? []);
}

// Verificar permisos básicos
if (!hasPermission('read')) {
    die('No tiene permisos para acceder a este módulo');
}

// Obtener estadísticas básicas
$stats = [
    'active_processes' => 0,
    'pending_tasks' => 0,
    'overdue_tasks' => 0,
    'completed_today' => 0
];

try {
    $pdo = getDB();
    
    // Contar procesos activos
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM processes WHERE company_id = ? AND status = 'active'");
    $stmt->execute([$company_id]);
    $stats['active_processes'] = $stmt->fetch()['count'] ?? 0;
    
    // Contar tareas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE company_id = ? AND status IN ('pending', 'in_progress')");
    $stmt->execute([$company_id]);
    $stats['pending_tasks'] = $stmt->fetch()['count'] ?? 0;
    
    // Contar tareas vencidas
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE company_id = ? AND due_date < NOW() AND status NOT IN ('completed', 'cancelled')");
    $stmt->execute([$company_id]);
    $stats['overdue_tasks'] = $stmt->fetch()['count'] ?? 0;
    
    // Contar completadas hoy
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE company_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $stats['completed_today'] = $stmt->fetch()['count'] ?? 0;
    
} catch (Exception $e) {
    // En caso de error, mantener valores en 0
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesos y Tareas - Sistema Indice</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- CSS específico del módulo -->
    <link href="css/processes-tasks.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Header del módulo -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-cogs text-primary me-2"></i>
                            Procesos y Tareas
                        </h1>
                        <p class="text-muted mb-0">Gestión de flujos operativos y asignaciones</p>
                    </div>
                    <div>
                        <a href="../../" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Volver al Dashboard
                        </a>
                    </div>
                </div>

                <!-- KPIs del módulo -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-3 bg-primary bg-opacity-10 me-3">
                                        <i class="fas fa-project-diagram text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo number_format($stats['active_processes']); ?></h5>
                                        <p class="text-muted mb-0 small">Procesos Activos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-3 bg-warning bg-opacity-10 me-3">
                                        <i class="fas fa-tasks text-warning fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo number_format($stats['pending_tasks']); ?></h5>
                                        <p class="text-muted mb-0 small">Tareas Pendientes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-3 bg-danger bg-opacity-10 me-3">
                                        <i class="fas fa-exclamation-triangle text-danger fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo number_format($stats['overdue_tasks']); ?></h5>
                                        <p class="text-muted mb-0 small">Tareas Vencidas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-3 bg-success bg-opacity-10 me-3">
                                        <i class="fas fa-check-circle text-success fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo number_format($stats['completed_today']); ?></h5>
                                        <p class="text-muted mb-0 small">Completadas Hoy</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navegación principal -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs" id="mainTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="processes-tab" data-bs-toggle="tab" data-bs-target="#processes" type="button" role="tab">
                                    <i class="fas fa-project-diagram me-2"></i>Procesos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                                    <i class="fas fa-tasks me-2"></i>Tareas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                                    <i class="fas fa-chart-bar me-2"></i>Reportes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                                    <i class="fas fa-copy me-2"></i>Plantillas
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content" id="mainTabsContent">
                            <!-- Tab de Procesos -->
                            <div class="tab-pane fade show active" id="processes" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Gestión de Procesos</h5>
                                    <?php if (hasPermission('write')): ?>
                                    <div>
                                        <button class="btn btn-primary me-2" onclick="newProcess()">
                                            <i class="fas fa-plus me-1"></i>Nuevo Proceso
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="newFromTemplate()">
                                            <i class="fas fa-copy me-1"></i>Desde Plantilla
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="processesTable">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Estado</th>
                                                <th>Prioridad</th>
                                                <th>Duración Est.</th>
                                                <th>Creado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-box-open fs-1 d-block mb-2 opacity-50"></i>
                                                    No hay procesos registrados
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab de Tareas -->
                            <div class="tab-pane fade" id="tasks" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Gestión de Tareas</h5>
                                    <?php if (hasPermission('write')): ?>
                                    <div>
                                        <button class="btn btn-primary me-2" onclick="newTask()">
                                            <i class="fas fa-plus me-1"></i>Nueva Tarea
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="showMyTasks()">
                                            <i class="fas fa-user me-1"></i>Mis Tareas
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tasksTable">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Proceso</th>
                                                <th>Asignado</th>
                                                <th>Estado</th>
                                                <th>Prioridad</th>
                                                <th>Vencimiento</th>
                                                <th>Progreso</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="fas fa-clipboard-list fs-1 d-block mb-2 opacity-50"></i>
                                                    No hay tareas registradas
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab de Reportes -->
                            <div class="tab-pane fade" id="reports" role="tabpanel">
                                <h5 class="mb-3">Reportes y Análisis</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-chart-pie fs-1 text-primary mb-3"></i>
                                                <h6>Productividad por Empleado</h6>
                                                <p class="text-muted small">Análisis de rendimiento individual</p>
                                                <button class="btn btn-outline-primary btn-sm">Ver Reporte</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-chart-line fs-1 text-success mb-3"></i>
                                                <h6>Eficiencia de Procesos</h6>
                                                <p class="text-muted small">Métricas de tiempo y completado</p>
                                                <button class="btn btn-outline-success btn-sm">Ver Reporte</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab de Plantillas -->
                            <div class="tab-pane fade" id="templates" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Plantillas de Flujos</h5>
                                    <?php if (hasPermission('write')): ?>
                                    <button class="btn btn-primary" onclick="newTemplate()">
                                        <i class="fas fa-plus me-1"></i>Nueva Plantilla
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 text-center text-muted py-5">
                                        <i class="fas fa-copy fs-1 d-block mb-2 opacity-50"></i>
                                        No hay plantillas disponibles
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Configuración global
        const MODULE_CONFIG = {
            company_id: <?php echo $company_id; ?>,
            user_id: <?php echo $user_id; ?>,
            user_role: '<?php echo $user_role; ?>',
            can_write: <?php echo hasPermission('write') ? 'true' : 'false'; ?>,
            can_delete: <?php echo hasPermission('delete') ? 'true' : 'false'; ?>
        };

        // Funciones básicas
        function newProcess() {
            Swal.fire('Info', 'Función de nuevo proceso en desarrollo', 'info');
        }

        function newFromTemplate() {
            Swal.fire('Info', 'Función de plantillas en desarrollo', 'info');
        }

        function newTask() {
            Swal.fire('Info', 'Función de nueva tarea en desarrollo', 'info');
        }

        function showMyTasks() {
            Swal.fire('Info', 'Función de mis tareas en desarrollo', 'info');
        }

        function newTemplate() {
            Swal.fire('Info', 'Función de nueva plantilla en desarrollo', 'info');
        }

        // Cargar datos al inicializar
        $(document).ready(function() {
            console.log('Módulo Procesos y Tareas cargado correctamente');
            console.log('Company ID:', MODULE_CONFIG.company_id);
            console.log('User Role:', MODULE_CONFIG.user_role);
        });
    </script>
</body>
</html>
