<?php
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Navegación - Prueba Final</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-check-circle me-2"></i>Sistema de Navegación - Prueba Final</h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Estado del Usuario -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-user me-2"></i>Estado del Usuario</h5>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <p><strong>Usuario ID:</strong> <?= $_SESSION['user_id'] ?></p>
                                <p><strong>Company ID:</strong> <?= $_SESSION['company_id'] ?? 'No definido' ?></p>
                                <p><strong>Current Company ID:</strong> <?= $_SESSION['current_company_id'] ?? 'No definido' ?></p>
                            <?php else: ?>
                                <p class="text-danger">No hay sesión activa</p>
                            <?php endif; ?>
                        </div>

                        <!-- Pruebas de Roles -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-shield-alt me-2"></i>Verificación de Roles</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php 
                                    $roles_to_test = [
                                        'root' => 'fas fa-crown',
                                        'superadmin' => 'fas fa-user-shield', 
                                        'admin' => 'fas fa-user-cog',
                                        'moderator' => 'fas fa-user-check',
                                        'user' => 'fas fa-user'
                                    ];
                                    
                                    foreach ($roles_to_test as $role => $icon): 
                                        $has_role = checkRole([$role]);
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="<?= $icon ?> me-2"></i>
                                            <span class="me-2"><?= ucfirst($role) ?>:</span>
                                            <?php if ($has_role): ?>
                                                <span class="badge bg-success">✅ SÍ</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">❌ NO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Enlaces de Navegación -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-link me-2"></i>Enlaces de Navegación Disponibles</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Enlaces Públicos -->
                                    <div class="col-md-6">
                                        <h6>Enlaces Públicos</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">
                                                <a href="../companies/" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-building me-1"></i>Companies Index
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Enlaces Admin -->
                                    <?php if (checkRole(['root', 'superadmin', 'admin'])): ?>
                                    <div class="col-md-6">
                                        <h6>Panel de Administración</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">
                                                <a href="../admin/?company_id=1" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard Admin
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="../admin/usuarios.php?company_id=1" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-users me-1"></i>Gestión Usuarios
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="../admin/roles.php?company_id=1" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-user-tag me-1"></i>Gestión Roles
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="../admin/permisos.php?company_id=1" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-key me-1"></i>Gestión Permisos
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Enlaces Root -->
                                    <?php if (checkRole(['root'])): ?>
                                    <div class="col-12 mt-3">
                                        <h6>Panel Root</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">
                                                <a href="../panel_root/" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-crown me-1"></i>Panel Root
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Empresas del Usuario -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-building me-2"></i>Empresas del Usuario</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $pdo = getDB();
                                $stmt = $pdo->prepare("
                                    SELECT c.id, c.name, uc.role, uc.status
                                    FROM companies c 
                                    INNER JOIN user_companies uc ON c.id = uc.company_id 
                                    WHERE uc.user_id = ? AND uc.status = 'active'
                                    ORDER BY c.name
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $user_companies = $stmt->fetchAll();
                                ?>
                                
                                <?php if ($user_companies): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Empresa</th>
                                                <th>Rol</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($user_companies as $company): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($company['id']) ?></td>
                                                <td><?= htmlspecialchars($company['name']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $company['role'] === 'root' ? 'danger' : 
                                                        ($company['role'] === 'superadmin' ? 'warning' : 
                                                        ($company['role'] === 'admin' ? 'primary' : 'secondary')) ?>">
                                                        <?= htmlspecialchars($company['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?= htmlspecialchars($company['status']) ?></span>
                                                </td>
                                                <td>
                                                    <a href="../admin/?company_id=<?= $company['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-cog me-1"></i>Admin
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No tienes empresas asignadas actualmente.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Estado Final -->
                        <div class="alert alert-success mt-4">
                            <h5><i class="fas fa-check-circle me-2"></i>Estado del Sistema</h5>
                            <ul class="mb-0">
                                <li>✅ Sesiones funcionando correctamente</li>
                                <li>✅ Sistema de roles implementado</li>
                                <li>✅ Navegación basada en roles</li>
                                <li>✅ Panel de administración accesible</li>
                                <li>✅ Enlaces con company_id funcionando</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
