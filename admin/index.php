<?php
require_once '../config.php';

// Verificar autenticación y permisos de administración
if (!checkRole(['root', 'superadmin', 'admin'])) {
    redirect('/companies/');
}

$pdo = getDB();
$current_company = null;
$company_id = null;

// Obtener la empresa seleccionada
if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
    $company_id = (int)$_GET['company_id'];
    
    // Verificar que el usuario tiene permisos superadmin en esta empresa
    $stmt = $pdo->prepare("
        SELECT c.*, uc.role 
        FROM companies c 
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE c.id = ? AND uc.user_id = ? AND uc.role = 'superadmin' AND uc.status = 'active'
    ");
    $stmt->execute([$company_id, $_SESSION['user_id']]);
    $current_company = $stmt->fetch();
    
    if (!$current_company) {
        redirect('/companies/');
    }
    
    // Establecer la empresa actual en la sesión
    $_SESSION['current_company_id'] = $company_id;
} else {
    redirect('/companies/');
}

// Obtener estadísticas de la empresa
$stats = [];
try {
    // Total de usuarios
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_companies WHERE company_id = ? AND status = 'active'");
    $stmt->execute([$company_id]);
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Invitaciones pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_invitations WHERE company_id = ? AND status = 'pending'");
    $stmt->execute([$company_id]);
    $stats['pending_invitations'] = $stmt->fetchColumn();
    
    // Total de unidades
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM units WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $stats['total_units'] = $stmt->fetchColumn();
    
    // Total de negocios
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM businesses WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $stats['total_businesses'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Error getting stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['admin_company']; ?> - <?php echo htmlspecialchars($current_company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
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
        .role-badge {
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 5px 12px;
        }
        .status-badge {
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 5px 12px;
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
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            background: transparent;
            color: #6c757d;
            margin-right: 5px;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .invitation-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .invitation-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-xl-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-cogs"></i> <?php echo $lang['admin_company']; ?>
                        </h4>
                        <small class="text-white-50">
                            <?php echo htmlspecialchars($current_company['name']); ?>
                        </small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#dashboard" data-tab="dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="usuarios.php?company_id=<?php echo $company_id; ?>">
                            <i class="fas fa-users me-2"></i> <?php echo $lang['company_users']; ?>
                        </a>
                        <a class="nav-link" href="roles.php?company_id=<?php echo $company_id; ?>">
                            <i class="fas fa-user-shield me-2"></i> <?php echo $lang['company_roles']; ?>
                        </a>
                        <a class="nav-link" href="permissions_management.php">
                            <i class="fas fa-key me-2"></i> <?php echo $lang['company_permissions']; ?>
                        </a>
                        
                        <div class="mt-4 pt-4 border-top border-white-50">
                            <a class="nav-link" href="../companies/">
                                <i class="fas fa-arrow-left me-2"></i> Volver a Empresas
                            </a>
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo $lang['logout']; ?>
                            </a>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-xl-10">
                <div class="main-content p-4">
                    
                    <!-- Dashboard Tab -->
                    <div id="dashboard-tab" class="tab-content active">
                        <div class="row mb-4">
                            <div class="col">
                                <h2 class="h4 mb-1"><?php echo $lang['admin_company']; ?></h2>
                                <p class="text-muted mb-0">
                                    Gestión integral de <?php echo htmlspecialchars($current_company['name']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center fade-in-up">
                                    <div class="card-body">
                                        <i class="fas fa-users text-primary" style="font-size: 2rem;"></i>
                                        <h3 class="mt-2"><?php echo $stats['total_users'] ?? 0; ?></h3>
                                        <p class="text-muted mb-0"><?php echo $lang['company_users']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center fade-in-up">
                                    <div class="card-body">
                                        <i class="fas fa-envelope text-warning" style="font-size: 2rem;"></i>
                                        <h3 class="mt-2"><?php echo $stats['pending_invitations'] ?? 0; ?></h3>
                                        <p class="text-muted mb-0"><?php echo $lang['pending_invitations']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center fade-in-up">
                                    <div class="card-body">
                                        <i class="fas fa-building text-info" style="font-size: 2rem;"></i>
                                        <h3 class="mt-2"><?php echo $stats['total_units'] ?? 0; ?></h3>
                                        <p class="text-muted mb-0">Unidades</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center fade-in-up">
                                    <div class="card-body">
                                        <i class="fas fa-briefcase text-success" style="font-size: 2rem;"></i>
                                        <h3 class="mt-2"><?php echo $stats['total_businesses'] ?? 0; ?></h3>
                                        <p class="text-muted mb-0">Negocios</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card fade-in-up">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-gradient" onclick="showInviteModal()">
                                                <i class="fas fa-user-plus me-2"></i><?php echo $lang['invite_user']; ?>
                                            </button>
                                            <a href="javascript:void(0)" onclick="showComingSoon()" class="btn btn-outline-primary">
                                                <i class="fas fa-users me-2"></i>Ver Todos los Usuarios
                                            </a>
                                            <a href="permissions_management.php" class="btn btn-outline-success">
                                                <i class="fas fa-key me-2"></i>Gestión de Permisos
                                            </a>
                                            <a href="email_settings.php" class="btn btn-outline-warning">
                                                <i class="fas fa-envelope me-2"></i>Configurar Email
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card fade-in-up">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Actividad Reciente</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">Próximamente: Historial de acciones recientes en la empresa</p>
                                    </div>
                                </div>
                            </div>
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
    
    // Global function for invite modal
    function showInviteModal() {
        console.log('Opening invite modal...');
        const modal = new bootstrap.Modal(document.getElementById('inviteUserModal'));
        modal.show();
    }
    
    // Function for coming soon features
    function showComingSoon() {
        Swal.fire({
            title: 'Próximamente',
            text: 'Esta funcionalidad estará disponible pronto. Usa el nuevo sistema de "Gestión de Permisos" por ahora.',
            icon: 'info',
            confirmButtonText: 'Entendido'
        });
    }
    
    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Admin dashboard loaded, company ID:', window.currentCompanyId);
        
        // Load units for invite modal
        if (typeof loadUnits === 'function') {
            loadUnits();
        } else {
            console.warn('loadUnits function not found');
        }
    });
    </script>
</body>
</html>
