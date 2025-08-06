<?php
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Completo - Selector de Empresas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h2><i class="fas fa-check-circle me-2"></i>Sistema Completo - Selector de Empresas Implementado</h2>
            </div>
            <div class="card-body">
                
                <!-- Funcionalidades Implementadas -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-list-check me-2"></i>Funcionalidades Implementadas</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-light">
                                        <h6><i class="fas fa-building me-2"></i>Selector de Empresas</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li>✅ Selector dropdown en header</li>
                                            <li>✅ Cambio dinámico de empresa activa</li>
                                            <li>✅ Indicadores visuales de empresa activa</li>
                                            <li>✅ Botones "Establecer para Admin" en cards</li>
                                            <li>✅ Actualización automática de last_accessed</li>
                                            <li>✅ Confirmación antes de cambiar</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-light">
                                        <h6><i class="fas fa-cog me-2"></i>Sistema de Administración</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li>✅ Enlaces dinámicos con company_id</li>
                                            <li>✅ Dashboard Admin directo</li>
                                            <li>✅ Navegación basada en roles</li>
                                            <li>✅ Fallback de company_id</li>
                                            <li>✅ Verificación de permisos</li>
                                            <li>✅ Variables de sesión consistentes</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado Actual -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Estado Actual del Sistema</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Usuario:</strong> <?= $_SESSION['user_name'] ?? 'No definido' ?> (ID: <?= $_SESSION['user_id'] ?? 'N/A' ?>)</p>
                            <p><strong>Empresa Activa:</strong> 
                                <?php 
                                $current_company_id = $_SESSION['current_company_id'] ?? $_SESSION['company_id'] ?? null;
                                if ($current_company_id) {
                                    $pdo = getDB();
                                    $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
                                    $stmt->execute([$current_company_id]);
                                    $company_name = $stmt->fetchColumn();
                                    echo htmlspecialchars($company_name) . " (ID: $current_company_id)";
                                } else {
                                    echo 'Ninguna seleccionada';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Rol Actual:</strong> <?= $_SESSION['current_role'] ?? $_SESSION['user_role'] ?? 'No definido' ?></p>
                            <p><strong>Permisos Admin:</strong> 
                                <?php if (checkRole(['root', 'superadmin', 'admin'])): ?>
                                    <span class="badge bg-success">SÍ</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">NO</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empresas Disponibles -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-building me-2"></i>Empresas Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_SESSION['user_id'])) {
                            $pdo = getDB();
                            $stmt = $pdo->prepare("
                                SELECT c.id, c.name, uc.role, uc.last_accessed
                                FROM companies c 
                                INNER JOIN user_companies uc ON c.id = uc.company_id 
                                WHERE uc.user_id = ? AND uc.status = 'active'
                                ORDER BY c.name
                            ");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user_companies = $stmt->fetchAll();
                        ?>
                        
                        <div class="row">
                            <?php foreach ($user_companies as $company): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 <?= $company['id'] == $current_company_id ? 'border-success' : 'border-light' ?>">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">
                                            <?= htmlspecialchars($company['name']) ?>
                                            <?php if ($company['id'] == $current_company_id): ?>
                                                <i class="fas fa-check-circle text-success ms-1"></i>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="card-text">
                                            <span class="badge bg-primary"><?= htmlspecialchars($company['role']) ?></span>
                                        </p>
                                        <div class="d-grid gap-2">
                                            <?php if ($company['id'] != $current_company_id): ?>
                                            <a href="../companies/?switch_company=<?= $company['id'] ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check me-1"></i>Activar
                                            </a>
                                            <?php else: ?>
                                            <span class="badge bg-success">ACTIVA</span>
                                            <?php endif; ?>
                                            
                                            <?php if (checkRole(['root', 'superadmin', 'admin'])): ?>
                                            <a href="../admin/?company_id=<?= $company['id'] ?>" 
                                               class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-cog me-1"></i>Admin
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Links de Prueba -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-link me-2"></i>Enlaces de Prueba</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Páginas Principales</h6>
                                <div class="d-grid gap-2">
                                    <a href="../companies/" class="btn btn-outline-primary">
                                        <i class="fas fa-building me-2"></i>Lista de Empresas
                                    </a>
                                    <a href="../admin/test_selector_empresas.php" class="btn btn-outline-info">
                                        <i class="fas fa-exchange-alt me-2"></i>Test Selector
                                    </a>
                                </div>
                            </div>
                            
                            <?php if ($current_company_id && checkRole(['root', 'superadmin', 'admin'])): ?>
                            <div class="col-md-4">
                                <h6>Panel de Administración</h6>
                                <div class="d-grid gap-2">
                                    <a href="../admin/?company_id=<?= $current_company_id ?>" class="btn btn-outline-success" target="_blank">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                                    </a>
                                    <a href="../admin/usuarios.php?company_id=<?= $current_company_id ?>" class="btn btn-outline-success" target="_blank">
                                        <i class="fas fa-users me-2"></i>Gestión Usuarios
                                    </a>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>Herramientas</h6>
                                <div class="d-grid gap-2">
                                    <a href="../admin/direct_links.php" class="btn btn-outline-warning">
                                        <i class="fas fa-link me-2"></i>Enlaces Directos
                                    </a>
                                    <a href="../admin/test_enlaces_corregidos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-vial me-2"></i>Test Enlaces
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Cambios -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5><i class="fas fa-code me-2"></i>Cambios Implementados en el Código</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>companies/index.php</h6>
                                <ul class="small">
                                    <li>✅ Lógica de cambio de empresa (?switch_company)</li>
                                    <li>✅ Selector dropdown en header</li>
                                    <li>✅ Indicadores visuales en cards</li>
                                    <li>✅ Botones "Establecer para Admin"</li>
                                    <li>✅ JavaScript para UX mejorada</li>
                                    <li>✅ Actualización de last_accessed</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Variables de Sesión</h6>
                                <ul class="small">
                                    <li>✅ $_SESSION['current_company_id']</li>
                                    <li>✅ $_SESSION['company_id'] (fallback)</li>
                                    <li>✅ $_SESSION['current_role']</li>
                                    <li>✅ Sincronización automática</li>
                                    <li>✅ Persistencia entre páginas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado Final -->
                <div class="alert alert-success">
                    <h5><i class="fas fa-trophy me-2"></i>¡Sistema Completamente Funcional!</h5>
                    <p class="mb-0">
                        El sistema ahora permite cambiar dinámicamente entre empresas y todos los enlaces 
                        de administración se adaptan automáticamente a la empresa seleccionada. 
                        <strong>Problema resuelto completamente.</strong>
                    </p>
                </div>

                <!-- Navegación -->
                <div class="text-center">
                    <a href="../companies/" class="btn btn-primary btn-lg">
                        <i class="fas fa-play me-2"></i>Probar el Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
