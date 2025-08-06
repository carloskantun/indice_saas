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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['company_users']; ?> - <?php echo htmlspecialchars($current_company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .btn-gradient {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .table-modern {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .table-modern th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
        }
        .table-modern td {
            border: none;
            vertical-align: middle;
            padding: 15px;
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
        <div class="row">
            <div class="col">
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="fas fa-users text-primary me-2"></i>
                            <?php echo $lang['company_users']; ?>
                        </h1>
                        <p class="text-muted mb-0">
                            Gestiona los usuarios asignados a <?php echo htmlspecialchars($current_company['name']); ?>
                        </p>
                    </div>
                    <div>
                        <button class="btn btn-gradient" onclick="showInviteModal()">
                            <i class="fas fa-user-plus me-2"></i><?php echo $lang['invite_user']; ?>
                        </button>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-modern" id="usersTable">
                                <thead>
                                    <tr>
                                        <th><?php echo $lang['user_name']; ?></th>
                                        <th><?php echo $lang['user_email']; ?></th>
                                        <th><?php echo $lang['user_role']; ?></th>
                                        <th><?php echo $lang['working_unit']; ?></th>
                                        <th><?php echo $lang['working_business']; ?></th>
                                        <th><?php echo $lang['status']; ?></th>
                                        <th><?php echo $lang['last_access']; ?></th>
                                        <th><?php echo $lang['invitation_status']; ?></th>
                                        <th><?php echo $lang['actions']; ?></th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <?php include 'modals/invite_user_modal.php'; ?>
    <?php include 'modals/edit_user_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/admin_users.js"></script>
    
    <script>
    // Global variables
    window.currentCompanyId = <?php echo $company_id; ?>;
    
    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Users page loaded, company ID:', window.currentCompanyId);
        loadUsers();
        
        if (typeof loadUnits === 'function') {
            loadUnits();
        } else {
            console.warn('loadUnits function not available');
        }
    });
    </script>
</body>
</html>
