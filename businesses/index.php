<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    redirect('auth/');
}

$unit_id = $_GET['unit_id'] ?? null;
if (!$unit_id) {
    redirect('companies/');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Verificar que el usuario tiene acceso a esta unidad
try {
    $stmt = $db->prepare("
        SELECT u.name as unit_name, c.name as company_name, c.id as company_id, uc.role 
        FROM units u 
        INNER JOIN companies c ON u.company_id = c.id
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? AND u.id = ?
    ");
    $stmt->execute([$user_id, $unit_id]);
    $unitData = $stmt->fetch();
    
    if (!$unitData) {
        redirect('companies/');
    }
    
    // Guardar en sesión
    $_SESSION['unit_id'] = $unit_id;
    $_SESSION['company_id'] = $unitData['company_id'];
    $_SESSION['current_role'] = $unitData['role'];
    
} catch (Exception $e) {
    redirect('companies/');
}

// Obtener negocios de la unidad
try {
    $stmt = $db->prepare("
        SELECT b.*, bt.name as type_name
        FROM businesses b 
        LEFT JOIN business_types bt ON b.type_id = bt.id
        WHERE b.unit_id = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$unit_id]);
    $businesses = $stmt->fetchAll();
} catch (Exception $e) {
    $businesses = [];
    $error = 'Error al cargar los negocios';
}

// Obtener tipos de negocio
try {
    $stmt = $db->prepare("SELECT * FROM business_types ORDER BY name");
    $stmt->execute();
    $businessTypes = $stmt->fetchAll();
} catch (Exception $e) {
    $businessTypes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['businesses']; ?> - <?php echo htmlspecialchars($unitData['unit_name']); ?></title>
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
                            <i class="fas fa-arrow-right mx-2"></i><?php echo htmlspecialchars($unitData['company_name']); ?>
                            <i class="fas fa-arrow-right mx-2"></i><?php echo htmlspecialchars($unitData['unit_name']); ?>
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
                            <li><a class="dropdown-item" href="../units/?company_id=<?php echo $unitData['company_id']; ?>">
                                <i class="fas fa-sitemap me-2"></i><?php echo $lang['units']; ?>
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
                                <li class="breadcrumb-item"><a href="../units/?company_id=<?php echo $unitData['company_id']; ?>"><?php echo $lang['units']; ?></a></li>
                                <li class="breadcrumb-item active"><?php echo $lang['businesses']; ?></li>
                            </ol>
                        </nav>
                        <h1 class="h3 mb-1"><?php echo $lang['businesses']; ?></h1>
                        <p class="text-muted mb-0">Gestiona los negocios de la unidad "<?php echo htmlspecialchars($unitData['unit_name']); ?>"</p>
                    </div>
                    <div>
                        <?php if (in_array($unitData['role'], ['admin', 'superadmin', 'root'])): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBusinessModal">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['new_business']; ?>
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

        <?php if (empty($businesses)): ?>
            <!-- Estado vacío -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-store text-muted mb-3" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mb-3"><?php echo $lang['no_businesses']; ?></h4>
                            <p class="text-muted mb-4">Crea tu primer negocio para comenzar a gestionar sus operaciones y módulos</p>
                            <?php if (in_array($unitData['role'], ['admin', 'superadmin', 'root'])): ?>
                                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createBusinessModal">
                                    <i class="fas fa-plus me-2"></i><?php echo $lang['new_business']; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Grilla de negocios -->
            <div class="row">
                <?php foreach ($businesses as $business): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm business-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-store text-white"></i>
                                    </div>
                                    <?php if (in_array($unitData['role'], ['admin', 'superadmin', 'root'])): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editBusiness(<?php echo $business['id']; ?>)">
                                                    <i class="fas fa-edit me-2"></i><?php echo $lang['edit']; ?>
                                                </a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteBusiness(<?php echo $business['id']; ?>)">
                                                    <i class="fas fa-trash me-2"></i><?php echo $lang['delete']; ?>
                                                </a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($business['name']); ?></h5>
                                <?php if (!empty($business['description'])): ?>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($business['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <?php if ($business['type_name']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($business['type_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Sin tipo</span>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($business['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <div class="mt-3">
                                    <span class="badge bg-<?php echo $business['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $business['status'] === 'active' ? $lang['active'] : $lang['inactive']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="../modules/?business_id=<?php echo $business['id']; ?>" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-th-large me-2"></i><?php echo $lang['view']; ?> <?php echo $lang['modules']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Negocio -->
    <div class="modal fade" id="createBusinessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $lang['new_business']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createBusinessForm">
                    <input type="hidden" name="unit_id" value="<?php echo $unit_id; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="business_name" class="form-label"><?php echo $lang['business_name']; ?> *</label>
                            <input type="text" class="form-control" id="business_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="business_type" class="form-label"><?php echo $lang['business_type']; ?></label>
                            <select class="form-select" id="business_type" name="type_id">
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($businessTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="business_description" class="form-label"><?php echo $lang['business_description']; ?></label>
                            <textarea class="form-control" id="business_description" name="description" rows="3"></textarea>
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
    <script src="js/businesses.js"></script>
</body>
</html>
