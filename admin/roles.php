<?php
require_once '../config.php';

// Verificar autenticación y permisos de administración
if (!checkRole(['root', 'superadmin', 'admin'])) {
    redirect('/companies/');
}

$pdo = getDB();
$current_company = null;
$company_id = null;

// Para usuarios root, mostrar selector de empresa; para otros, usar empresa activa o requerir selección
if (checkRole(['root'])) {
    // Usuario root puede seleccionar cualquier empresa
    if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
        $company_id = (int)$_GET['company_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? AND status = 'active'");
        $stmt->execute([$company_id]);
        $current_company = $stmt->fetch();
        
        if ($current_company) {
            $_SESSION['current_company_id'] = $company_id;
            $_SESSION['current_company_name'] = $current_company['name'];
        }
    }
} else {
    // Para superadmin/admin, obtener empresa de la sesión o parámetro
    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : ($_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? null);
    
    if ($company_id) {
        // Verificar que el usuario tiene permisos en esta empresa
        $stmt = $pdo->prepare("
            SELECT c.*, uc.role 
            FROM companies c 
            INNER JOIN user_companies uc ON c.id = uc.company_id 
            WHERE c.id = ? AND uc.user_id = ? AND uc.role IN ('superadmin', 'admin') AND uc.status = 'active'
        ");
        $stmt->execute([$company_id, $_SESSION['user_id']]);
        $current_company = $stmt->fetch();
        
        if (!$current_company) {
            redirect('/companies/');
        }
        
        $_SESSION['current_company_id'] = $company_id;
        $_SESSION['current_company_name'] = $current_company['name'];
    } else {
        // No hay empresa seleccionada, redirigir a companies
        redirect('/companies/');
    }
}

// Obtener estadísticas de roles
$roleStats = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            role,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active
        FROM user_companies 
        WHERE company_id = ? 
        GROUP BY role 
        ORDER BY 
            CASE role
                WHEN 'superadmin' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'moderator' THEN 3
                ELSE 4
            END
    ");
    $stmt->execute([$company_id]);
    $roleStats = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error getting role stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['company_roles']; ?> - <?php echo htmlspecialchars($current_company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .role-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="./">
                <i class="fas fa-cogs me-2"></i>Admin Panel
            </a>
            <span class="navbar-text text-white">
                <?php echo htmlspecialchars($current_company['name']); ?>
            </span>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../companies/">
                    <i class="fas fa-arrow-left me-1"></i>Volver a Empresas
                </a>
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-1">
                    <i class="fas fa-user-shield text-primary me-2"></i>
                    <?php echo $lang['company_roles']; ?>
                </h1>
                <p class="text-muted mb-0">
                    Roles disponibles en <?php echo htmlspecialchars($current_company['name']); ?>
                </p>
            </div>
        </div>

        <!-- Available Roles -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-crown role-icon text-primary"></i>
                        <h4 class="card-title text-primary">SuperAdmin</h4>
                        <p class="card-text">
                            Control total sobre la empresa. Puede gestionar usuarios, roles, permisos 
                            y todas las configuraciones del sistema.
                        </p>
                        <div class="row">
                            <?php 
                            $superadminStats = array_filter($roleStats, fn($r) => $r['role'] === 'superadmin');
                            $superadminStats = reset($superadminStats);
                            ?>
                            <div class="col-6">
                                <strong><?php echo $superadminStats['total'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Total</small>
                            </div>
                            <div class="col-6">
                                <strong><?php echo $superadminStats['active'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Activos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-success h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield role-icon text-success"></i>
                        <h4 class="card-title text-success">Admin</h4>
                        <p class="card-text">
                            Gestión avanzada de la empresa. Puede manejar usuarios y la mayoría 
                            de configuraciones, excepto roles de SuperAdmin.
                        </p>
                        <div class="row">
                            <?php 
                            $adminStats = array_filter($roleStats, fn($r) => $r['role'] === 'admin');
                            $adminStats = reset($adminStats);
                            ?>
                            <div class="col-6">
                                <strong><?php echo $adminStats['total'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Total</small>
                            </div>
                            <div class="col-6">
                                <strong><?php echo $adminStats['active'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Activos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card border-warning h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-edit role-icon text-warning"></i>
                        <h4 class="card-title text-warning">Moderador</h4>
                        <p class="card-text">
                            Permisos de edición en módulos específicos y supervisión de operaciones. 
                            Puede gestionar contenido pero no usuarios.
                        </p>
                        <div class="row">
                            <?php 
                            $moderatorStats = array_filter($roleStats, fn($r) => $r['role'] === 'moderator');
                            $moderatorStats = reset($moderatorStats);
                            ?>
                            <div class="col-6">
                                <strong><?php echo $moderatorStats['total'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Total</small>
                            </div>
                            <div class="col-6">
                                <strong><?php echo $moderatorStats['active'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Activos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-info h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user role-icon text-info"></i>
                        <h4 class="card-title text-info">Usuario</h4>
                        <p class="card-text">
                            Acceso básico a los módulos asignados. Permisos de lectura y 
                            creación limitada según la configuración de la empresa.
                        </p>
                        <div class="row">
                            <?php 
                            $userStats = array_filter($roleStats, fn($r) => $r['role'] === 'user');
                            $userStats = reset($userStats);
                            ?>
                            <div class="col-6">
                                <strong><?php echo $userStats['total'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Total</small>
                            </div>
                            <div class="col-6">
                                <strong><?php echo $userStats['active'] ?? 0; ?></strong>
                                <small class="d-block text-muted">Activos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Future Features -->
        <div class="row mt-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-roadmap me-2"></i>Funcionalidades Futuras
                        </h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Próximamente:</strong> Creación de roles personalizados con permisos específicos
                        </div>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Definir roles personalizados por empresa</li>
                            <li><i class="fas fa-check text-success me-2"></i>Asignar permisos granulares por módulo</li>
                            <li><i class="fas fa-check text-success me-2"></i>Plantillas de roles predefinidas</li>
                            <li><i class="fas fa-check text-success me-2"></i>Herencia de permisos entre roles</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
