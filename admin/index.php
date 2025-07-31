<?php
require_once '../config.php';

// Verificar autenticación y permisos
if (!checkRole(['superadmin', 'admin'])) {
    header('Location: ../auth/');
    exit();
}

$pdo = getDB();
$current_company = null;

// Para superadmin, la empresa es opcional
// Para admin, verificar que tenga una empresa asignada
if (checkRole(['superadmin'])) {
    // Superadmin puede trabajar sin empresa específica o seleccionar una
    if (isset($_SESSION['current_company_id']) && !empty($_SESSION['current_company_id'])) {
        $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$_SESSION['current_company_id']]);
        $current_company = $stmt->fetch();
    }
} else {
    // Para admin, es obligatorio tener una empresa
    if (!isset($_SESSION['current_company_id']) || empty($_SESSION['current_company_id'])) {
        header('Location: ../companies/');
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->execute([$_SESSION['current_company_id']]);
    $current_company = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['admin_user_management']; ?> - Índice Producción</title>
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
                            <i class="fas fa-users-cog"></i> <?php echo $lang['admin_panel']; ?>
                        </h4>
                        <small class="text-white-50">
                            <?php 
                            if ($current_company && isset($current_company['name'])) {
                                echo htmlspecialchars($current_company['name']);
                            } else {
                                echo checkRole(['superadmin']) ? 'Sistema Global' : 'Sin empresa seleccionada';
                            }
                            ?>
                        </small>
                    </div>
                    
                    <?php if (checkRole(['superadmin'])): ?>
                    <!-- Selector de Empresa para Superadmin -->
                    <div class="mb-3">
                        <select class="form-select form-select-sm" id="companySelector" onchange="changeCompany(this.value)">
                            <option value="">Todas las empresas</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE status = 'active' ORDER BY name");
                            $stmt->execute();
                            $companies = $stmt->fetchAll();
                            foreach ($companies as $company) {
                                $selected = (isset($_SESSION['current_company_id']) && $_SESSION['current_company_id'] == $company['id']) ? 'selected' : '';
                                echo "<option value='{$company['id']}' $selected>" . htmlspecialchars($company['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#users" data-tab="users">
                            <i class="fas fa-users me-2"></i> <?php echo $lang['users']; ?>
                        </a>
                        <a class="nav-link" href="#invitations" data-tab="invitations">
                            <i class="fas fa-envelope me-2"></i> <?php echo $lang['invitations']; ?>
                        </a>
                        <a class="nav-link" href="#roles" data-tab="roles">
                            <i class="fas fa-user-shield me-2"></i> <?php echo $lang['roles']; ?>
                        </a>
                        <div class="mt-4 pt-4 border-top border-white-50">
                            <a class="nav-link" href="../">
                                <i class="fas fa-arrow-left me-2"></i> <?php echo $lang['back_to_main']; ?>
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0">
                                <i class="fas fa-users-cog text-primary"></i> 
                                <?php echo $lang['admin_user_management']; ?>
                            </h2>
                            <p class="text-muted mb-0">
                                <?php if ($current_company && isset($current_company['name'])): ?>
                                    <?php echo $lang['manage_users_company']; ?>: 
                                    <strong><?php echo htmlspecialchars($current_company['name']); ?></strong>
                                <?php else: ?>
                                    <?php echo checkRole(['superadmin']) ? 'Administración Global del Sistema' : 'Gestión de Usuarios'; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <button class="btn btn-gradient" onclick="showInviteModal()">
                                <i class="fas fa-user-plus me-2"></i> <?php echo $lang['invite_user']; ?>
                            </button>
                        </div>
                    </div>

                    <?php if (checkRole(['superadmin']) && (!$current_company || !isset($current_company['name']))): ?>
                    <!-- Mensaje para superadmin sin empresa seleccionada -->
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        <strong>Modo Administración Global:</strong> 
                        Selecciona una empresa específica desde el menú lateral para gestionar usuarios y enviar invitaciones de esa empresa.
                    </div>
                    <?php endif; ?>

                    <!-- Users Tab -->
                    <div id="users-tab" class="tab-content active">
                        <div class="card fade-in-up">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i> <?php echo $lang['users_list']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-modern" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th><?php echo $lang['name']; ?></th>
                                                <th><?php echo $lang['email']; ?></th>
                                                <th><?php echo $lang['role']; ?></th>
                                                <th><?php echo $lang['status']; ?></th>
                                                <th><?php echo $lang['joined_date']; ?></th>
                                                <th><?php echo $lang['actions']; ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <!-- Los usuarios se cargarán aquí vía AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invitations Tab -->
                    <div id="invitations-tab" class="tab-content">
                        <div class="card fade-in-up">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="fas fa-envelope me-2"></i> <?php echo $lang['pending_invitations']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="invitationsContainer">
                                    <!-- Las invitaciones se cargarán aquí vía AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Tab -->
                    <div id="roles-tab" class="tab-content">
                        <div class="card fade-in-up">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-shield me-2"></i> <?php echo $lang['role_management']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h6 class="card-title text-primary">
                                                    <i class="fas fa-crown"></i> <?php echo $lang['superadmin']; ?>
                                                </h6>
                                                <p class="card-text small"><?php echo $lang['superadmin_desc']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <h6 class="card-title text-success">
                                                    <i class="fas fa-user-shield"></i> <?php echo $lang['admin']; ?>
                                                </h6>
                                                <p class="card-text small"><?php echo $lang['admin_desc']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-warning">
                                            <div class="card-body">
                                                <h6 class="card-title text-warning">
                                                    <i class="fas fa-user-edit"></i> <?php echo $lang['moderator']; ?>
                                                </h6>
                                                <p class="card-text small"><?php echo $lang['moderator_desc']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <h6 class="card-title text-info">
                                                    <i class="fas fa-user"></i> <?php echo $lang['user']; ?>
                                                </h6>
                                                <p class="card-text small"><?php echo $lang['user_desc']; ?></p>
                                            </div>
                                        </div>
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
    // Función para cambiar empresa (solo para superadmin)
    function changeCompany(companyId) {
        // Hacer request para cambiar la empresa activa
        fetch('../companies/controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=switch_company&company_id=' + companyId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para actualizar el contexto
                window.location.reload();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Error al cambiar empresa',
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Error de conexión',
                icon: 'error'
            });
        });
    }
    </script>
</body>
</html>
