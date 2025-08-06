<?php
/**
 * Companies - Versión simplificada y robusta
 */

require_once '../config.php';
require_once '../components/language_selector.php';

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
    
    // Conectar a DB (necesario antes del manejo de cambio de empresa)
    $db = getDB();
    
    // Manejar cambio de empresa
    if (isset($_GET['switch_company']) && !empty($_GET['switch_company'])) {
        $new_company_id = (int)$_GET['switch_company'];
        
        // Verificar que el usuario tiene acceso a esta empresa
        $stmt = $db->prepare("
            SELECT c.id, c.name, uc.role 
            FROM companies c 
            INNER JOIN user_companies uc ON c.id = uc.company_id 
            WHERE c.id = ? AND uc.user_id = ? AND uc.status = 'active'
        ");
        $stmt->execute([$new_company_id, $user_id]);
        $company_access = $stmt->fetch();
        
        if ($company_access) {
            $_SESSION['current_company_id'] = $new_company_id;
            $_SESSION['company_id'] = $new_company_id;
            $_SESSION['current_role'] = $company_access['role'];
            
            // Actualizar last_accessed
            $stmt = $db->prepare("UPDATE user_companies SET last_accessed = NOW() WHERE user_id = ? AND company_id = ?");
            $stmt->execute([$user_id, $new_company_id]);
            
            // Redirigir para limpiar la URL
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    // Obtener company_id actual para enlaces de admin
    $current_company_id = $_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? null;

    // Obtener el rol más alto del usuario (superadmin si tiene alguna empresa con ese rol)
    $stmt = $db->prepare("
        SELECT uc.role 
        FROM user_companies uc 
        WHERE uc.user_id = ? 
        ORDER BY 
            CASE uc.role 
                WHEN 'superadmin' THEN 1 
                WHEN 'admin' THEN 2 
                WHEN 'manager' THEN 3 
                WHEN 'user' THEN 4 
                ELSE 5 
            END 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $userRole = $stmt->fetchColumn();
    
    // Establecer rol en sesión para uso en navbar
    $_SESSION['user_role'] = $userRole ?: 'user';

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
<html lang="<?php echo getCurrentLanguage(); ?>">
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
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-building me-1"></i>Empresas
                        </a>
                    </li>
                    <?php 
                    // Verificar si el usuario tiene permisos de administración
                    if (checkRole(['root', 'superadmin', 'admin'])): 
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cogs me-1"></i>Administración
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (checkRole(['root', 'superadmin', 'admin'])): ?>
                            <li><a class="dropdown-item" href="../admin/<?php echo $current_company_id ? '?company_id='.$current_company_id : ''; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../admin/usuarios.php<?php echo $current_company_id ? '?company_id='.$current_company_id : ''; ?>">
                                <i class="fas fa-user-shield me-2"></i>Gestión de Usuarios
                            </a></li>
                            <li><a class="dropdown-item" href="../admin/permisos.php<?php echo $current_company_id ? '?company_id='.$current_company_id : ''; ?>">
                                <i class="fas fa-key me-2"></i>Gestión de Permisos
                            </a></li>
                            <li><a class="dropdown-item" href="../admin/roles.php<?php echo $current_company_id ? '?company_id='.$current_company_id : ''; ?>">
                                <i class="fas fa-users me-2"></i>Gestión de Roles
                            </a></li>
                            <?php endif; ?>
                            
                            <?php if (checkRole(['root'])): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../panel_root/index.php">
                                <i class="fas fa-crown me-2"></i>Panel Root
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Notificaciones (si están disponibles) -->
                    <?php if (file_exists('../components/navbar_notifications_safe.php')): ?>
                        <?php include '../components/navbar_notifications_safe.php'; ?>
                    <?php endif; ?>
                    
                    <!-- Selector de Idioma -->
                    <li class="nav-item me-2">
                        <?php echo renderLanguageSelectorNavbar(); ?>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../profile.php">
                                <i class="fas fa-user-edit me-2"></i><?php echo $lang['profile'] ?? 'Mi Perfil'; ?>
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
                        <h1 class="h3 mb-1"><?php echo $lang['companies_list'] ?? $lang['companies']; ?></h1>
                        <p class="text-muted mb-0"><?php echo $lang['manage_companies_desc'] ?? 'Gestiona tus empresas y accede a sus módulos'; ?></p>
                    </div>
                    <div class="btn-group" role="group">
                        <?php if (checkRole(['root', 'superadmin', 'admin'])): ?>
                        <a href="../admin/<?php echo $current_company_id ? '?company_id='.$current_company_id : ''; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i><?php echo $lang['dashboard'] ?? 'Dashboard'; ?> Admin
                        </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
                            <i class="fas fa-plus me-2"></i><?php echo $lang['create_company'] ?? 'Nueva Empresa'; ?>
                        </button>
                        <?php if ($current_company_id && checkRole(['admin', 'superadmin'])): ?>
                        <a href="invitations.php" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i><?php echo $lang['manage_invitations'] ?? 'Gestionar Invitaciones'; ?>
                        </a>
                        <?php endif; ?>
                        <a href="/notifications/" class="btn btn-outline-info">
                            <i class="fas fa-bell me-2"></i><?php echo $lang['notifications']; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de Empresa Activa -->
        <?php if (count($companies) > 1): ?>
        <div class="row mb-4">
            <div class="col">
                <div class="card border-info">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-building me-2 text-info"></i>Empresa Activa para Administración
                                </h6>
                                <?php 
                                $current_company_name = 'Ninguna seleccionada';
                                if ($current_company_id) {
                                    foreach ($companies as $comp) {
                                        if ($comp['id'] == $current_company_id) {
                                            $current_company_name = $comp['name'];
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <small class="text-muted">Actual: <strong><?= htmlspecialchars($current_company_name) ?></strong></small>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2 justify-content-md-end">
                                    <select class="form-select form-select-sm" style="max-width: 300px;" onchange="switchCompany(this.value)">
                                        <option value="">Seleccionar empresa...</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?= $company['id'] ?>" 
                                                    <?= $company['id'] == $current_company_id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($company['name']) ?> 
                                                <span class="text-muted">(<?= ucfirst($company['role']) ?>)</span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($current_company_id): ?>
                                    <a href="../admin/?company_id=<?= $current_company_id ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Error Alert from Session -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Error Alert -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <pre><?php echo htmlspecialchars($error); ?></pre>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Herramientas de Sistema (solo para administradores) -->
        <?php if (checkRole(['root', 'superadmin'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tools me-2"></i>Herramientas de Sistema
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="check_tables.php" class="btn btn-outline-info btn-sm w-100">
                                    <i class="fas fa-table me-2"></i>Verificar Tablas
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="../database_analysis.php" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Análisis DB
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="../test_invitation.php" class="btn btn-outline-warning btn-sm w-100">
                                    <i class="fas fa-envelope-open me-2"></i>Test Invitaciones
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="?debug=1" class="btn btn-outline-dark btn-sm w-100">
                                    <i class="fas fa-bug me-2"></i>Debug Info
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                        <div class="card company-card h-100 shadow-sm" data-company-id="<?= $company['id'] ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                        <?php if ($company['id'] == $current_company_id): ?>
                                            <i class="fas fa-check-circle text-success ms-2" title="Empresa activa para administración"></i>
                                        <?php endif; ?>
                                    </h5>
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
                                
                                <div class="d-grid gap-2">
                                    <a href="../units/?company_id=<?php echo $company['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-right me-2"></i>Acceder
                                    </a>
                                    <?php if (checkRole(['root', 'superadmin', 'admin']) && $company['id'] != $current_company_id): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="switchCompany(<?= $company['id'] ?>)">
                                        <i class="fas fa-cog me-1"></i>Establecer para Admin
                                    </button>
                                    <?php elseif ($company['id'] == $current_company_id && checkRole(['root', 'superadmin', 'admin'])): ?>
                                    <a href="../admin/?company_id=<?= $company['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard Admin
                                    </a>
                                    <?php endif; ?>
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

    <!-- Modal para crear nueva empresa -->
    <div class="modal fade" id="createCompanyModal" tabindex="-1" aria-labelledby="createCompanyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCompanyModalLabel">
                        <i class="fas fa-building me-2"></i>Crear Nueva Empresa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createCompanyForm" action="controller.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_company">
                        
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nombre de la Empresa *</label>
                            <input type="text" class="form-control" id="company_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="company_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_status" class="form-label">Estado</label>
                            <select class="form-select" id="company_status" name="status">
                                <option value="active">Activa</option>
                                <option value="inactive">Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Invitar Usuario -->
    <div class="modal fade" id="inviteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Invitar Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="inviteUserForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="inviteEmail" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email del Usuario
                            </label>
                            <input type="email" class="form-control" id="inviteEmail" name="email" required>
                            <div class="form-text">Se enviará una invitación a este correo electrónico</div>
                        </div>
                        <div class="mb-3">
                            <label for="inviteNivel" class="form-label">
                                <i class="fas fa-user-shield me-1"></i>Nivel de Acceso
                            </label>
                            <select class="form-select" id="inviteNivel" name="nivel" required>
                                <option value="">Seleccionar nivel</option>
                                <option value="1">Usuario (Nivel 1) - Acceso básico</option>
                                <option value="2">Supervisor (Nivel 2) - Gestión de usuarios</option>
                                <?php if (checkRole(['admin', 'superadmin'])): ?>
                                <option value="3">Administrador (Nivel 3) - Control total</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información:</strong> Si el envío por email falla, el usuario podrá aceptar la invitación desde su panel de notificaciones.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Invitación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Manejar envío de invitación
    document.getElementById('inviteUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Mostrar loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
        
        const formData = new FormData(form);
        
        fetch('invite_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                const alertClass = data.email_sent ? 'alert-success' : 'alert-warning';
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
                alertDiv.style.top = '20px';
                alertDiv.style.right = '20px';
                alertDiv.style.zIndex = '9999';
                alertDiv.innerHTML = `
                    ${data.message}
                    ${data.email_error ? '<br><small>' + data.email_error + '</small>' : ''}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Cerrar modal y resetear form
                bootstrap.Modal.getInstance(document.getElementById('inviteUserModal')).hide();
                form.reset();
                
                // Auto-remove alert después de 5 segundos
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        })
        .finally(() => {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
    
    function switchCompany(companyId) {
        if (companyId && companyId !== '') {
            // Mostrar confirmación
            const companyName = document.querySelector(`option[value="${companyId}"]`).textContent;
            if (confirm(`¿Establecer "${companyName.trim()}" como empresa activa para administración?`)) {
                // Mostrar loading
                const loadingToast = document.createElement('div');
                loadingToast.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 alert alert-info';
                loadingToast.style.zIndex = '9999';
                loadingToast.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cambiando empresa...';
                document.body.appendChild(loadingToast);
                
                // Redirigir
                setTimeout(() => {
                    window.location.href = '?switch_company=' + companyId;
                }, 500);
            }
        }
    }
    
    // Agregar indicador visual de empresa activa
    document.addEventListener('DOMContentLoaded', function() {
        const currentCompanyId = '<?= $current_company_id ?>';
        if (currentCompanyId) {
            // Resaltar la empresa activa en las cards
            const activeCard = document.querySelector('[data-company-id="' + currentCompanyId + '"]');
            if (activeCard) {
                activeCard.classList.add('border-success');
                activeCard.style.boxShadow = '0 0 0 2px rgba(25, 135, 84, 0.25)';
            }
        }
        
        // Agregar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>
