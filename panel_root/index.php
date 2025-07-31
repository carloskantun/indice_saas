<?php
/**
 * Panel Root - Dashboard Principal Mejorado
 * Vista principal del panel de administración root con estadísticas avanzadas
 */

require_once '../config.php';

// Verificar autenticación y permisos de root
if (!checkRole(['root'])) {
    header('Location: ' . BASE_URL . 'auth/index.php?error=access_denied');
    exit();
}

$pdo = getDB();

// Obtener estadísticas generales del sistema
try {
    // Estadísticas básicas
    $stats = [];
    
    // Verificar qué columnas existen en cada tabla para compatibilidad
    $stmt = $pdo->query("DESCRIBE plans");
    $planColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasStatusPlans = in_array('status', $planColumns);
    $hasIsActivePlans = in_array('is_active', $planColumns);
    $hasMonthlyPrice = in_array('monthly_price', $planColumns);
    
    // Total de planes (compatible con ambas estructuras)
    if ($hasStatusPlans) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM plans");
    } else if ($hasIsActivePlans) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM plans");
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(*) as active FROM plans");
    }
    $planStats = $stmt->fetch();
    $stats['plans'] = $planStats;
    
    // Verificar columnas de companies
    $stmt = $pdo->query("DESCRIBE companies");
    $companyColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasStatusCompanies = in_array('status', $companyColumns);
    
    // Total de empresas
    if ($hasStatusCompanies) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM companies");
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(*) as active FROM companies");
    }
    $companyStats = $stmt->fetch();
    $stats['companies'] = $companyStats;
    
    // Verificar si existe tabla users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->fetch();
    
    if ($usersTableExists) {
        // Verificar columnas de users
        $stmt = $pdo->query("DESCRIBE users");
        $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasStatusUsers = in_array('status', $userColumns);
        
        // Total de usuarios
        if ($hasStatusUsers) {
            $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM users");
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(*) as active FROM users");
        }
        $userStats = $stmt->fetch();
        $stats['users'] = $userStats;
    } else {
        $stats['users'] = ['total' => 0, 'active' => 0];
    }
    
    // Verificar si existe tabla modules
    $stmt = $pdo->query("SHOW TABLES LIKE 'modules'");
    $modulesTableExists = $stmt->fetch();
    
    if ($modulesTableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM modules");
        $moduleStats = $stmt->fetch();
        $stats['modules'] = $moduleStats;
    } else {
        $stats['modules'] = ['total' => 0, 'active' => 0];
    }
    
    // Ingresos estimados (adaptativo según estructura)
    if ($hasMonthlyPrice) {
        $revenueQuery = "
            SELECT 
                SUM(p.monthly_price) as monthly_revenue,
                SUM(p.monthly_price * 12) as yearly_revenue,
                COUNT(c.id) as paying_companies
            FROM companies c 
            INNER JOIN plans p ON c.plan_id = p.id 
            WHERE 1=1";
        
        // Agregar condiciones según columnas disponibles
        if ($hasStatusCompanies && $hasStatusPlans) {
            $revenueQuery .= " AND c.status = 'active' AND p.status = 'active'";
        } else if ($hasIsActivePlans) {
            $revenueQuery .= " AND p.is_active = 1";
        }
        
        $stmt = $pdo->query($revenueQuery);
        $revenueStats = $stmt->fetch();
    } else {
        // Si no hay columna monthly_price, usar valores por defecto
        $revenueStats = ['monthly_revenue' => 0, 'yearly_revenue' => 0, 'paying_companies' => 0];
    }
    $stats['revenue'] = $revenueStats;
    
    // Plan más utilizado (adaptativo)
    if ($hasMonthlyPrice) {
        $planQuery = "
            SELECT p.name, p.monthly_price, COUNT(c.id) as companies_count
            FROM plans p
            LEFT JOIN companies c ON p.id = c.plan_id";
        
        $whereConditions = [];
        if ($hasStatusCompanies) {
            $whereConditions[] = "c.status = 'active'";
        }
        if ($hasStatusPlans) {
            $whereConditions[] = "p.status = 'active'";
        } else if ($hasIsActivePlans) {
            $whereConditions[] = "p.is_active = 1";
        }
        
        if (!empty($whereConditions)) {
            $planQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $planQuery .= " GROUP BY p.id ORDER BY companies_count DESC LIMIT 1";
        
        $stmt = $pdo->query($planQuery);
        $mostUsedPlan = $stmt->fetch();
    } else {
        // Si no hay monthly_price, usar consulta básica
        $planQuery = "
            SELECT p.name, 0 as monthly_price, COUNT(c.id) as companies_count
            FROM plans p
            LEFT JOIN companies c ON p.id = c.plan_id";
        
        $whereConditions = [];
        if ($hasStatusCompanies) {
            $whereConditions[] = "c.status = 'active'";
        }
        if ($hasStatusPlans) {
            $whereConditions[] = "p.status = 'active'";
        } else if ($hasIsActivePlans) {
            $whereConditions[] = "p.is_active = 1";
        }
        
        if (!empty($whereConditions)) {
            $planQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $planQuery .= " GROUP BY p.id ORDER BY companies_count DESC LIMIT 1";
        
        $stmt = $pdo->query($planQuery);
        $mostUsedPlan = $stmt->fetch();
    }
    $stats['most_used_plan'] = $mostUsedPlan;
    
    // Módulo más popular (solo si existe la tabla)
    if ($modulesTableExists) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'plan_modules'");
        $planModulesExists = $stmt->fetch();
        
        if ($planModulesExists) {
            $moduleQuery = "
                SELECT m.name, m.icon, m.color, COUNT(DISTINCT pm.plan_id) as plans_count,
                       COUNT(DISTINCT c.id) as companies_with_access
                FROM modules m
                LEFT JOIN plan_modules pm ON m.id = pm.module_id
                LEFT JOIN companies c ON pm.plan_id = c.plan_id";
            
            $whereConditions = ["m.status = 'active'"];
            if ($hasStatusCompanies) {
                $whereConditions[] = "c.status = 'active'";
            }
            
            $moduleQuery .= " WHERE " . implode(" AND ", $whereConditions);
            $moduleQuery .= " GROUP BY m.id ORDER BY plans_count DESC, companies_with_access DESC LIMIT 1";
            
            $stmt = $pdo->query($moduleQuery);
            $mostActiveModule = $stmt->fetch();
            $stats['most_active_module'] = $mostActiveModule;
        } else {
            $stats['most_active_module'] = ['name' => 'N/A', 'icon' => 'fas fa-puzzle-piece', 'color' => '#3498db'];
        }
    } else {
        $stats['most_active_module'] = ['name' => 'N/A', 'icon' => 'fas fa-puzzle-piece', 'color' => '#3498db'];
    }
    
    // Empresas recientes
    $companiesQuery = "
        SELECT c.*, p.name as plan_name
        FROM companies c
        LEFT JOIN plans p ON c.plan_id = p.id
        WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY c.created_at DESC
        LIMIT 5";
    
    $stmt = $pdo->query($companiesQuery);
    $recentCompanies = $stmt->fetchAll();
    
    // Usuarios recientes (solo si existe la tabla)
    if ($usersTableExists) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_companies'");
        $userCompaniesExists = $stmt->fetch();
        
        if ($userCompaniesExists) {
            $usersQuery = "
                SELECT u.*, COUNT(uc.company_id) as companies_count
                FROM users u
                LEFT JOIN user_companies uc ON u.id = uc.user_id";
            
            $whereConditions = ["u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"];
            if (in_array('status', $stmt->fetchAll(PDO::FETCH_COLUMN))) {
                $whereConditions[] = "uc.status = 'active'";
            }
            
            $usersQuery .= " WHERE " . implode(" AND ", $whereConditions);
            $usersQuery .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT 5";
            
            $stmt = $pdo->query($usersQuery);
            $recentUsers = $stmt->fetchAll();
        } else {
            $recentUsers = [];
        }
    } else {
        $recentUsers = [];
    }
    
    // Distribución de usuarios por rol (solo si existen las tablas)
    if ($usersTableExists && isset($userCompaniesExists) && $userCompaniesExists) {
        $rolesQuery = "
            SELECT uc.role, COUNT(DISTINCT uc.user_id) as count
            FROM user_companies uc
            WHERE 1=1";
        
        $stmt = $pdo->query("DESCRIBE user_companies");
        $ucColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('status', $ucColumns)) {
            $rolesQuery .= " AND uc.status = 'active'";
        }
        
        $rolesQuery .= " GROUP BY uc.role ORDER BY count DESC";
        
        $stmt = $pdo->query($rolesQuery);
        $usersByRole = $stmt->fetchAll();
    } else {
        $usersByRole = [];
    }
    
    // Ingresos por plan
    if ($hasMonthlyPrice) {
        $revenueByPlanQuery = "
            SELECT p.name, p.monthly_price, 
                   COUNT(c.id) as companies_count,
                   (p.monthly_price * COUNT(c.id)) as total_revenue
            FROM plans p
            LEFT JOIN companies c ON p.id = c.plan_id";
        
        $whereConditions = [];
        if ($hasStatusCompanies) {
            $whereConditions[] = "c.status = 'active'";
        }
        if ($hasStatusPlans) {
            $whereConditions[] = "p.status = 'active'";
        } else if ($hasIsActivePlans) {
            $whereConditions[] = "p.is_active = 1";
        }
        
        if (!empty($whereConditions)) {
            $revenueByPlanQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $revenueByPlanQuery .= " GROUP BY p.id ORDER BY total_revenue DESC";
        
        $stmt = $pdo->query($revenueByPlanQuery);
        $revenueByPlan = $stmt->fetchAll();
    } else {
        // Si no hay monthly_price, usar datos vacíos
        $revenueByPlan = [];
    }
    
    // Crecimiento del mes actual
    $growthQuery = "
        SELECT 
            COUNT(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 END) as this_month,
            COUNT(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN 1 END) as last_month
        FROM companies";
    
    if ($hasStatusCompanies) {
        $growthQuery .= " WHERE status = 'active'";
    }
    
    $stmt = $pdo->query($growthQuery);
    $growthStats = $stmt->fetch();
    
    $growth_percentage = 0;
    if ($growthStats['last_month'] > 0) {
        $growth_percentage = round((($growthStats['this_month'] - $growthStats['last_month']) / $growthStats['last_month']) * 100, 1);
    }
    
} catch (PDOException $e) {
    $error_message = "Error al cargar estadísticas: " . $e->getMessage();
    // Valores por defecto en caso de error
    $stats = [
        'plans' => ['total' => 0, 'active' => 0],
        'companies' => ['total' => 0, 'active' => 0],
        'users' => ['total' => 0, 'active' => 0],
        'modules' => ['total' => 0, 'active' => 0],
        'revenue' => ['monthly_revenue' => 0, 'yearly_revenue' => 0, 'paying_companies' => 0],
        'most_used_plan' => ['name' => 'N/A', 'companies_count' => 0],
        'most_active_module' => ['name' => 'N/A', 'icon' => 'fas fa-puzzle-piece', 'color' => '#3498db']
    ];
    $recentCompanies = [];
    $recentUsers = [];
    $usersByRole = [];
    $revenueByPlan = [];
    $growth_percentage = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Global - Sistema SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #343a40;
            min-height: calc(100vh - 56px);
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #495057;
            color: #fff;
        }
        .stat-card {
            transition: transform 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .revenue-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .growth-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .growth-positive {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        .growth-negative {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .recent-item {
            border-left: 3px solid #007bff;
            padding-left: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-crown text-warning"></i>
                Panel Root
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                        <?= $_SESSION['username'] ?? 'Root' ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <nav class="nav flex-column px-3">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="plans.php">
                            <i class="fas fa-layer-group"></i> Planes
                        </a>
                        <a class="nav-link" href="companies.php">
                            <i class="fas fa-building"></i> Empresas
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Usuarios
                        </a>
                        <a class="nav-link" href="modules.php">
                            <i class="fas fa-puzzle-piece"></i> Módulos
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="text-gradient">
                                <i class="fas fa-chart-line"></i>
                                Dashboard Global
                            </h1>
                            <p class="text-muted mb-0">Vista general del sistema</p>
                        </div>
                        <div>
                            <a href="../fix_database.php" class="btn btn-warning me-2">
                                <i class="fas fa-database"></i> Corregir BD
                            </a>
                            <button type="button" class="btn btn-primary" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error_message) ?>
                            <br><small>Haz clic en "Corregir BD" para solucionar problemas de base de datos.</small>
                        </div>
                    <?php endif; ?>

                    <!-- Métricas principales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body revenue-card text-white">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white-50 mb-2">Ingresos Mensuales</h6>
                                            <h3 class="mb-0">$<?= number_format($stats['revenue']['monthly_revenue'] ?? 0, 2) ?></h3>
                                            <small class="text-white-75">
                                                <span class="growth-indicator <?= $growth_percentage >= 0 ? 'growth-positive' : 'growth-negative' ?>">
                                                    <i class="fas fa-arrow-<?= $growth_percentage >= 0 ? 'up' : 'down' ?> me-1"></i>
                                                    <?= abs($growth_percentage) ?>%
                                                </span>
                                                este mes
                                            </small>
                                        </div>
                                        <i class="fas fa-dollar-sign fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body bg-primary text-white">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white-50 mb-2">Total Empresas</h6>
                                            <h3 class="mb-0"><?= $stats['companies']['total'] ?></h3>
                                            <small class="text-white-75">
                                                <?= $stats['companies']['active'] ?> activas
                                            </small>
                                        </div>
                                        <i class="fas fa-building fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body bg-info text-white">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white-50 mb-2">Total Usuarios</h6>
                                            <h3 class="mb-0"><?= $stats['users']['total'] ?></h3>
                                            <small class="text-white-75">
                                                <?= $stats['users']['active'] ?> activos
                                            </small>
                                        </div>
                                        <i class="fas fa-users fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100">
                                <div class="card-body bg-warning text-white">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white-50 mb-2">Módulos Activos</h6>
                                            <h3 class="mb-0"><?= $stats['modules']['active'] ?></h3>
                                            <small class="text-white-75">
                                                de <?= $stats['modules']['total'] ?> totales
                                            </small>
                                        </div>
                                        <i class="fas fa-puzzle-piece fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información destacada -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-trophy text-warning"></i>
                                        Plan Más Utilizado
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($stats['most_used_plan'] && !empty($stats['most_used_plan']['name'])): ?>
                                        <h4 class="text-primary"><?= htmlspecialchars($stats['most_used_plan']['name']) ?></h4>
                                        <p class="text-muted mb-2">
                                            <?= $stats['most_used_plan']['companies_count'] ?> empresas
                                        </p>
                                        <h5 class="text-success">$<?= number_format($stats['most_used_plan']['monthly_price'] ?? 0, 2) ?>/mes</h5>
                                    <?php else: ?>
                                        <p class="text-muted">Sin datos disponibles</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-star text-warning"></i>
                                        Módulo Más Activo
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($stats['most_active_module'] && $stats['most_active_module']['name'] != 'N/A'): ?>
                                        <div class="module-icon mx-auto mb-3" style="width: 50px; height: 50px; background: <?= $stats['most_active_module']['color'] ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                            <i class="<?= $stats['most_active_module']['icon'] ?>"></i>
                                        </div>
                                        <h5><?= htmlspecialchars($stats['most_active_module']['name']) ?></h5>
                                        <p class="text-muted mb-0">
                                            <?= $stats['most_active_module']['companies_with_access'] ?? 0 ?> empresas con acceso
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">Sin datos disponibles</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-bar text-info"></i>
                                        Ingresos Estimados
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <h4 class="text-success mb-1">
                                        $<?= number_format($stats['revenue']['yearly_revenue'] ?? 0, 2) ?>
                                    </h4>
                                    <p class="text-muted mb-2">Anual estimado</p>
                                    <small class="text-info">
                                        <?= $stats['revenue']['paying_companies'] ?? 0 ?> empresas pagando
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos y estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-pie"></i>
                                        Ingresos por Plan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-chart"></i>
                                        Usuarios por Rol
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="usersChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad reciente -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-building"></i>
                                        Empresas Recientes
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recentCompanies)): ?>
                                        <?php foreach ($recentCompanies as $company): ?>
                                            <div class="recent-item mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= htmlspecialchars($company['name']) ?></strong>
                                                        <br><small class="text-muted">
                                                            Plan: <?= htmlspecialchars($company['plan_name'] ?? 'Sin plan') ?>
                                                        </small>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($company['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted text-center">No hay empresas recientes</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-plus"></i>
                                        Usuarios Recientes
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recentUsers)): ?>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <div class="recent-item mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                        <br><small class="text-muted">
                                                            <?= $user['companies_count'] ?? 0 ?> empresa(s)
                                                        </small>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted text-center">No hay usuarios recientes</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.js"></script>
    <script>
        // Datos para gráficos
        const revenueData = <?= json_encode($revenueByPlan ?: []) ?>;
        const usersData = <?= json_encode($usersByRole ?: []) ?>;

        // Configurar gráfico de ingresos por plan
        if (revenueData.length > 0) {
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'doughnut',
                data: {
                    labels: revenueData.map(item => item.name),
                    datasets: [{
                        data: revenueData.map(item => item.total_revenue),
                        backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545', 
                            '#17a2b8', '#6f42c1', '#fd7e14', '#20c997'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': $' + context.parsed.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('revenueChart').parentElement.innerHTML = 
                '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-muted">Sin datos para mostrar</p></div>';
        }

        // Configurar gráfico de usuarios por rol
        if (usersData.length > 0) {
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            new Chart(usersCtx, {
                type: 'bar',
                data: {
                    labels: usersData.map(item => {
                        const roleNames = {
                            'root': 'Root',
                            'support': 'Soporte',
                            'superadmin': 'Superadmin',
                            'admin': 'Admin',
                            'moderator': 'Moderador',
                            'user': 'Usuario'
                        };
                        return roleNames[item.role] || item.role;
                    }),
                    datasets: [{
                        label: 'Usuarios',
                        data: usersData.map(item => item.count),
                        backgroundColor: '#007bff',
                        borderColor: '#0056b3',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('usersChart').parentElement.innerHTML = 
                '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-muted">Sin datos para mostrar</p></div>';
        }

        // Función para actualizar dashboard
        function refreshDashboard() {
            window.location.reload();
        }

        // Auto-refresh cada 5 minutos
        setInterval(refreshDashboard, 300000);
    </script>
</body>
</html>
