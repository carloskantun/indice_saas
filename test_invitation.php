<?php
/**
 * Test de Invitaciones - Herramienta de prueba
 */

require_once 'config.php';

// Solo permitir acceso a usuarios root
if (!checkAuth() || !checkRole(['root', 'superadmin'])) {
    die('Acceso denegado. Solo usuarios root pueden ejecutar este script.');
}

header('Content-Type: text/html; charset=UTF-8');

// Procesar acciones
$message = '';
$message_type = '';

if ($_POST) {
    try {
        $db = getDB();
        
        if (isset($_POST['create_test_invitation'])) {
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            $company_id = (int)$_POST['company_id'];
            
            if (!$email) {
                throw new Exception('Email inválido');
            }
            
            // Verificar que la empresa existe
            $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();
            
            if (!$company) {
                throw new Exception('Empresa no encontrada');
            }
            
            // Generar token único
            $token = bin2hex(random_bytes(32));
            
            // Insertar invitación
            $stmt = $db->prepare("
                INSERT INTO user_invitations (email, company_id, token, sent_by, expiration_date)
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
            ");
            $stmt->execute([$email, $company_id, $token, $_SESSION['user_id']]);
            
            $message = "✅ Invitación creada exitosamente para {$email} a la empresa '{$company['name']}'";
            $message_type = 'success';
            
        } elseif (isset($_POST['clear_test_data'])) {
            // Limpiar invitaciones de prueba
            $stmt = $db->prepare("DELETE FROM user_invitations WHERE email LIKE '%test%' OR email LIKE '%prueba%'");
            $stmt->execute();
            $affected = $stmt->rowCount();
            
            $message = "🗑️ Se eliminaron {$affected} invitaciones de prueba";
            $message_type = 'info';
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Obtener datos para el formulario
try {
    $db = getDB();
    
    // Obtener empresas
    $companies = $db->query("SELECT id, name FROM companies ORDER BY name")->fetchAll();
    
    // Obtener invitaciones recientes
    $invitations = $db->query("
        SELECT 
            ui.*, 
            c.name as company_name,
            u.name as sent_by_name
        FROM user_invitations ui
        LEFT JOIN companies c ON ui.company_id = c.id
        LEFT JOIN users u ON ui.sent_by = u.id
        ORDER BY ui.created_at DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (Exception $e) {
    $companies = [];
    $invitations = [];
    if (empty($message)) {
        $message = "Error de conexión: " . $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test de Invitaciones - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                
                <!-- Header -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-flask me-2"></i>Test de Sistema de Invitaciones
                        </h3>
                        <small>Herramienta para probar el flujo completo de invitaciones</small>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Crear Invitación de Prueba -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-plus-circle me-2"></i>Crear Invitación de Prueba
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="test@ejemplo.com" required>
                                        <div class="form-text">
                                            💡 Usa un email con 'test' o 'prueba' para poder limpiarlo después
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="company_id" class="form-label">
                                            <i class="fas fa-building me-1"></i>Empresa
                                        </label>
                                        <select class="form-select" id="company_id" name="company_id" required>
                                            <option value="">Seleccionar empresa...</option>
                                            <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>">
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="create_test_invitation" class="btn btn-success w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Crear Invitación
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Sistema -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Estado del Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    // Verificar estado de las tablas
                                    $db = getDB();
                                    
                                    $tables_status = [];
                                    
                                    // Verificar user_invitations
                                    try {
                                        $count = $db->query("SELECT COUNT(*) FROM user_invitations")->fetchColumn();
                                        $tables_status['user_invitations'] = "✅ {$count} invitaciones";
                                    } catch (Exception $e) {
                                        $tables_status['user_invitations'] = "❌ No existe";
                                    }
                                    
                                    // Verificar user_companies
                                    try {
                                        $count = $db->query("SELECT COUNT(*) FROM user_companies")->fetchColumn();
                                        $tables_status['user_companies'] = "✅ {$count} relaciones";
                                    } catch (Exception $e) {
                                        $tables_status['user_companies'] = "❌ No existe";
                                    }
                                    
                                    // Verificar companies
                                    try {
                                        $count = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
                                        $tables_status['companies'] = "✅ {$count} empresas";
                                    } catch (Exception $e) {
                                        $tables_status['companies'] = "❌ No existe";
                                    }
                                    
                                    ?>
                                    <h6>📊 Estado de Tablas:</h6>
                                    <ul class="list-unstyled">
                                        <?php foreach ($tables_status as $table => $status): ?>
                                        <li><code><?php echo $table; ?></code>: <?php echo $status; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>🔗 Enlaces de Prueba:</h6>
                                    <div class="d-grid gap-2">
                                        <a href="companies/check_tables.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-table me-2"></i>Verificar Tablas
                                        </a>
                                        <a href="companies/accept_invitation.php?token=test" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-link me-2"></i>Test Accept Page
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <button type="submit" name="clear_test_data" class="btn btn-outline-danger btn-sm w-100">
                                                <i class="fas fa-trash me-2"></i>Limpiar Test Data
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <?php
                                } catch (Exception $e) {
                                    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invitaciones Recientes -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Invitaciones Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($invitations)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>No hay invitaciones registradas</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Empresa</th>
                                            <th>Estado</th>
                                            <th>Enviado por</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invitations as $invitation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($invitation['email']); ?></td>
                                            <td><?php echo htmlspecialchars($invitation['company_name']); ?></td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'pending' => 'warning',
                                                    'accepted' => 'success',
                                                    'rejected' => 'danger',
                                                    'expired' => 'secondary'
                                                ];
                                                $color = $status_colors[$invitation['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($invitation['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($invitation['sent_by_name'] ?? 'Sistema'); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($invitation['created_at'])); ?></td>
                                            <td>
                                                <?php if ($invitation['status'] === 'pending'): ?>
                                                <a href="companies/accept_invitation.php?token=<?php echo $invitation['token']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i> Test
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Navegación -->
                <div class="mt-4 text-center">
                    <a href="companies/" class="btn btn-primary">
                        <i class="fas fa-building me-2"></i>Volver a Empresas
                    </a>
                    <a href="database_analysis.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar me-2"></i>Análisis DB
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
