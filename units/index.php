<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    redirect('auth/');
}

$company_id = $_GET['company_id'] ?? null;
if (!$company_id) {
    redirect('companies/');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Verificar que el usuario tiene acceso a esta empresa
try {
    $stmt = $db->prepare("SELECT c.name, uc.role FROM companies c INNER JOIN user_companies uc ON c.id = uc.company_id WHERE uc.user_id = ? AND c.id = ?");
    $stmt->execute([$user_id, $company_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        redirect('companies/');
    }
    
    // Actualizar último acceso
    $stmt = $db->prepare("UPDATE user_companies SET last_accessed = NOW() WHERE user_id = ? AND company_id = ?");
    $stmt->execute([$user_id, $company_id]);
    
    // Guardar en sesión
    $_SESSION['company_id'] = $company_id;
    $_SESSION['current_role'] = $company['role'];
    
} catch (Exception $e) {
    redirect('companies/');
}

// Obtener unidades de la empresa
try {
    $stmt = $db->prepare("
        SELECT u.*,
               (SELECT COUNT(*) FROM businesses WHERE unit_id = u.id) as businesses_count
        FROM units u 
        WHERE u.company_id = ? 
        ORDER BY u.created_at DESC
    ");
    $stmt->execute([$company_id]);
    $units = $stmt->fetchAll();
} catch (Exception $e) {
    $units = [];
    $error = 'Error al cargar las unidades';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['units']; ?> - <?php echo htmlspecialchars($company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../companies/">
                <i class="fas fa-building me-2"></i><?php echo $lang['app_name']; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="fas fa-arrow-right mx-2"></i><?php echo htmlspecialchars($company['name']); ?>
                        </span>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../companies/">
                                <i class="fas fa-building me-2"></i><?php echo $lang['companies']; ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i><?php echo $lang['logout']; ?>
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../companies/"><?php echo $lang['companies']; ?></a></li>
                                <li class="breadcrumb-item active"><?php echo $lang['units']; ?></li>
                            </ol>
                        </nav>
                        <h1 class="h3 mb-1"><?php echo $lang['units']; ?></h1>
                        <p class="text-muted mb-0">Gestiona las unidades de negocio de tu empresa</p>
                    </div>
                    <div>
                        <?php if (in_array($company['role'], ['admin', 'superadmin', 'root'])): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['new_unit']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($units)): ?>
            <!-- Estado vacío -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-sitemap text-muted mb-3" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mb-3"><?php echo $lang['no_units']; ?></h4>
                            <p class="text-muted mb-4">Crea tu primera unidad de negocio para comenzar a organizar tus operaciones</p>
                            <?php if (in_array($company['role'], ['admin', 'superadmin', 'root'])): ?>
                                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                                    <i class="fas fa-plus me-2"></i><?php echo $lang['new_unit']; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Grilla de unidades -->
            <div class="row">
                <?php foreach ($units as $unit): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-sitemap text-white"></i>
                                    </div>
                                    <?php if (in_array($company['role'], ['admin', 'superadmin', 'root'])): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editUnit(<?php echo $unit['id']; ?>)">
                                                    <i class="fas fa-edit me-2"></i><?php echo $lang['edit']; ?>
                                                </a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteUnit(<?php echo $unit['id']; ?>)">
                                                    <i class="fas fa-trash me-2"></i><?php echo $lang['delete']; ?>
                                                </a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($unit['name']); ?></h5>
                                <?php if (!empty($unit['description'])): ?>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($unit['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="badge bg-info"><?php echo $unit['businesses_count']; ?> negocios</span>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($unit['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="../businesses/?unit_id=<?php echo $unit['id']; ?>" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-eye me-2"></i><?php echo $lang['view']; ?> <?php echo $lang['businesses']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Unidad -->
    <div class="modal fade" id="createUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $lang['new_unit']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createUnitForm">
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="unit_name" class="form-label"><?php echo $lang['unit_name']; ?> *</label>
                            <input type="text" class="form-control" id="unit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="unit_description" class="form-label"><?php echo $lang['unit_description']; ?></label>
                            <textarea class="form-control" id="unit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['cancel']; ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo $lang['create']; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/units.js"></script>
</body>
</html>
