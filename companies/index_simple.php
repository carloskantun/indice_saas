<?php
/**
 * Companies - Versión simplificada y robusta
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Variables por defecto
$companies = [];
$error = null;
$user_name = 'Usuario';

try {
    // Cargar configuración
    require_once '../config.php';

    // Verificar autenticación básica
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: ../auth/');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Usuario';

    // Conectar a DB
    $db = getDB();

    // Obtener empresas del usuario
    $stmt = $db->prepare("
        SELECT c.*, uc.role, uc.created_at as joined_at
        FROM companies c 
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? 
        ORDER BY c.name
    ");
    $stmt->execute([$user_id]);
    $companies = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error en companies: " . $e->getMessage());
    $error = "Error al cargar las empresas: " . $e->getMessage();
    
    if (isset($_GET['debug'])) {
        $error .= "\nArchivo: " . $e->getFile() . "\nLínea: " . $e->getLine();
    }
}

// Variables de idioma por defecto si no están definidas
if (!isset($lang) || !is_array($lang)) {
    $lang = [
        'app_name' => 'Indice SaaS',
        'companies' => 'Empresas',
        'companies_list' => 'Lista de Empresas',
        'new_company' => 'Nueva Empresa',
        'logout' => 'Cerrar Sesión',
        'no_companies' => 'No tienes empresas asignadas',
        'create_first_company' => 'Crear primera empresa'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['companies']; ?> - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .company-card { transition: transform 0.2s; }
        .company-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-building me-2"></i><?php echo $lang['app_name']; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($user_name); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i><?php echo $lang['logout']; ?>
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-1"><?php echo $lang['companies_list']; ?></h1>
                <p class="text-muted">Gestiona tus empresas y accede a sus módulos</p>
            </div>
        </div>

        <!-- Error Alert -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <pre><?php echo htmlspecialchars($error); ?></pre>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Debug Info -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="alert alert-info">
                <h5>🔍 Información de Debug</h5>
                <p><strong>User ID:</strong> <?php echo $user_id ?? 'No definido'; ?></p>
                <p><strong>Sesión activa:</strong> <?php echo session_status() == PHP_SESSION_ACTIVE ? 'Sí' : 'No'; ?></p>
                <p><strong>Empresas encontradas:</strong> <?php echo count($companies); ?></p>
                <?php if (!empty($_SESSION)): ?>
                    <details>
                        <summary>Variables de sesión</summary>
                        <pre><?php print_r($_SESSION); ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Companies Grid -->
        <?php if (empty($companies)): ?>
            <!-- Empty State -->
            <div class="row">
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-building fa-4x text-muted"></i>
                        </div>
                        <h4 class="text-muted mb-3"><?php echo $lang['no_companies']; ?></h4>
                        <p class="text-muted mb-4">Comienza creando tu primera empresa para acceder a los módulos del sistema.</p>
                        <button type="button" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i><?php echo $lang['create_first_company']; ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Companies Grid -->
            <div class="row">
                <?php foreach ($companies as $company): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card company-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($company['name']); ?></h5>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($company['role']); ?></span>
                                </div>
                                
                                <?php if (!empty($company['description'])): ?>
                                    <p class="card-text text-muted small mb-3">
                                        <?php echo htmlspecialchars($company['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="small text-muted mb-3">
                                    <i class="fas fa-calendar me-1"></i>
                                    Miembro desde: <?php echo date('d/m/Y', strtotime($company['joined_at'])); ?>
                                </div>
                                
                                <div class="d-grid">
                                    <a href="../units/?company_id=<?php echo $company['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-right me-2"></i>Acceder
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Debug Links -->
        <div class="mt-5 pt-4 border-top">
            <div class="row">
                <div class="col">
                    <h6 class="text-muted">🔧 Herramientas de Diagnóstico</h6>
                    <div class="btn-group" role="group">
                        <a href="?debug=1" class="btn btn-sm btn-outline-info">Debug Info</a>
                        <a href="simple_test.php" class="btn btn-sm btn-outline-secondary">Test Simple</a>
                        <a href="debug.php" class="btn btn-sm btn-outline-warning">Debug Completo</a>
                        <a href="../fix_data.php" class="btn btn-sm btn-outline-success">Reparar Datos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
