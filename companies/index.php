<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    redirect('auth/');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Obtener empresas del usuario
try {
    $stmt = $db->prepare("
        SELECT c.*, uc.role, uc.created_at as joined_at,
               (SELECT COUNT(*) FROM units WHERE company_id = c.id) as units_count
        FROM companies c 
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? 
        ORDER BY uc.last_accessed DESC, c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $companies = $stmt->fetchAll();
} catch (Exception $e) {
    $companies = [];
    $error = 'Error al cargar las empresas';
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
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-building me-2"></i><?php echo $lang['app_name']; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
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
                        <h1 class="h3 mb-1"><?php echo $lang['companies_list']; ?></h1>
                        <p class="text-muted mb-0">Gestiona tus empresas y accede a sus módulos</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
                            <i class="fas fa-plus me-2"></i><?php echo $lang['new_company']; ?>
                        </button>
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

        <?php if (empty($companies)): ?>
            <!-- Estado vacío -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-building text-muted mb-3" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mb-3"><?php echo $lang['no_companies']; ?></h4>
                            <p class="text-muted mb-4"><?php echo $lang['create_first_company']; ?></p>
                            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['new_company']; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Tabla de empresas -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo $lang['company_name']; ?></th>
                                            <th><?php echo $lang['date']; ?></th>
                                            <th><?php echo $lang['units']; ?></th>
                                            <th><?php echo $lang['role']; ?></th>
                                            <th class="text-center"><?php echo $lang['actions']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-building text-white"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($company['name']); ?></h6>
                                                            <?php if (!empty($company['description'])): ?>
                                                                <small class="text-muted"><?php echo htmlspecialchars($company['description']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y', strtotime($company['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $company['units_count']; ?> unidades</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $lang[$company['role']]; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../units/?company_id=<?php echo $company['id']; ?>" 
                                                           class="btn btn-outline-primary" title="<?php echo $lang['view']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (in_array($company['role'], ['admin', 'superadmin', 'root'])): ?>
                                                            <button type="button" class="btn btn-outline-secondary" 
                                                                    onclick="editCompany(<?php echo $company['id']; ?>)" 
                                                                    title="<?php echo $lang['edit']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="deleteCompany(<?php echo $company['id']; ?>)" 
                                                                    title="<?php echo $lang['delete']; ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Empresa -->
    <div class="modal fade" id="createCompanyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $lang['new_company']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createCompanyForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="company_name" class="form-label"><?php echo $lang['company_name']; ?> *</label>
                            <input type="text" class="form-control" id="company_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="company_description" class="form-label"><?php echo $lang['company_description']; ?></label>
                            <textarea class="form-control" id="company_description" name="description" rows="3"></textarea>
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
    <script src="js/companies.js"></script>
</body>
</html>
