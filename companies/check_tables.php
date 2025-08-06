<?php
/**
 * Script para verificar y crear tablas faltantes para el sistema de invitaciones
 */

require_once '../config.php';

// Solo permitir acceso a usuarios root
if (!checkAuth() || !checkRole(['root'])) {
    die('Acceso denegado. Solo usuarios root pueden ejecutar este script.');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Verificación de Tablas - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-table me-2"></i>Verificación de Tablas del Sistema
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        try {
                            $db = getDB();
                            
                            echo "<h5>📋 Verificando estructura de tablas...</h5>";
                            echo "<div class='mb-4'>";
                            
                            // Verificar si existe user_companies
                            $tables_query = $db->query("SHOW TABLES LIKE 'user_companies'");
                            $user_companies_exists = $tables_query->fetch();
                            
                            if (!$user_companies_exists) {
                                echo "<div class='alert alert-warning'>";
                                echo "<h6><i class='fas fa-exclamation-triangle me-2'></i>Tabla user_companies no existe</h6>";
                                echo "<p>Esta tabla es necesaria para el sistema de invitaciones. ¿Crear ahora?</p>";
                                
                                if (isset($_POST['create_user_companies'])) {
                                    $create_sql = "
                                    CREATE TABLE `user_companies` (
                                      `id` int(11) NOT NULL AUTO_INCREMENT,
                                      `user_id` int(11) NOT NULL,
                                      `company_id` int(11) NOT NULL,
                                      `role` varchar(50) NOT NULL DEFAULT 'user',
                                      `status` enum('active','inactive','suspended') DEFAULT 'active',
                                      `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                                      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                                      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `uk_user_company` (`user_id`,`company_id`),
                                      KEY `idx_user_id` (`user_id`),
                                      KEY `idx_company_id` (`company_id`),
                                      KEY `idx_status` (`status`)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                                    ";
                                    
                                    try {
                                        $db->exec($create_sql);
                                        echo "<div class='alert alert-success mt-3'>✅ Tabla user_companies creada exitosamente</div>";
                                        
                                        // Crear relaciones existentes basadas en la tabla companies
                                        $existing_relations = $db->query("
                                            SELECT created_by, id FROM companies WHERE created_by IS NOT NULL
                                        ")->fetchAll();
                                        
                                        if (!empty($existing_relations)) {
                                            $insert_stmt = $db->prepare("
                                                INSERT IGNORE INTO user_companies (user_id, company_id, role, status)
                                                VALUES (?, ?, 'admin', 'active')
                                            ");
                                            
                                            foreach ($existing_relations as $relation) {
                                                $insert_stmt->execute([$relation['created_by'], $relation['id']]);
                                            }
                                            
                                            echo "<div class='alert alert-info mt-2'>ℹ️ Se han migrado " . count($existing_relations) . " relaciones existentes</div>";
                                        }
                                        
                                    } catch (Exception $e) {
                                        echo "<div class='alert alert-danger mt-3'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                    }
                                } else {
                                    echo "<form method='POST' class='mt-3'>";
                                    echo "<button type='submit' name='create_user_companies' class='btn btn-warning'>";
                                    echo "<i class='fas fa-plus me-2'></i>Crear tabla user_companies";
                                    echo "</button>";
                                    echo "</form>";
                                }
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-success'>";
                                echo "<i class='fas fa-check me-2'></i>Tabla user_companies: ✅ Existe";
                                echo "</div>";
                            }
                            
                            // Verificar estructura de user_invitations
                            echo "<h5 class='mt-4'>📧 Verificando user_invitations...</h5>";
                            
                            $invitations_query = $db->query("SHOW TABLES LIKE 'user_invitations'");
                            $invitations_exists = $invitations_query->fetch();
                            
                            if (!$invitations_exists) {
                                echo "<div class='alert alert-danger'>";
                                echo "<h6><i class='fas fa-times me-2'></i>Tabla user_invitations no existe</h6>";
                                echo "<p>Esta tabla es crítica para el sistema de invitaciones.</p>";
                                
                                if (isset($_POST['create_user_invitations'])) {
                                    $create_invitations_sql = "
                                    CREATE TABLE `user_invitations` (
                                      `id` int(11) NOT NULL AUTO_INCREMENT,
                                      `email` varchar(255) NOT NULL,
                                      `company_id` int(11) NOT NULL,
                                      `role` varchar(50) DEFAULT 'user',
                                      `token` varchar(255) NOT NULL,
                                      `status` enum('pending','accepted','rejected','expired') DEFAULT 'pending',
                                      `sent_by` int(11) DEFAULT NULL,
                                      `sent_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                                      `expiration_date` timestamp NULL DEFAULT NULL,
                                      `accepted_date` timestamp NULL DEFAULT NULL,
                                      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                                      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `uk_email_company_pending` (`email`,`company_id`,`status`),
                                      UNIQUE KEY `uk_token` (`token`),
                                      KEY `idx_email` (`email`),
                                      KEY `idx_company_id` (`company_id`),
                                      KEY `idx_status` (`status`),
                                      KEY `idx_token` (`token`)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                                    ";
                                    
                                    try {
                                        $db->exec($create_invitations_sql);
                                        echo "<div class='alert alert-success mt-3'>✅ Tabla user_invitations creada exitosamente</div>";
                                    } catch (Exception $e) {
                                        echo "<div class='alert alert-danger mt-3'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                    }
                                } else {
                                    echo "<form method='POST' class='mt-3'>";
                                    echo "<button type='submit' name='create_user_invitations' class='btn btn-danger'>";
                                    echo "<i class='fas fa-plus me-2'></i>Crear tabla user_invitations";
                                    echo "</button>";
                                    echo "</form>";
                                }
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-success'>";
                                echo "<i class='fas fa-check me-2'></i>Tabla user_invitations: ✅ Existe";
                                
                                // Verificar estructura de columnas
                                $columns = $db->query("SHOW COLUMNS FROM user_invitations")->fetchAll();
                                $column_names = array_column($columns, 'Field');
                                
                                $required_columns = ['email', 'company_id', 'role', 'token', 'status', 'sent_by'];
                                $missing_columns = array_diff($required_columns, $column_names);
                                
                                if (!empty($missing_columns)) {
                                    echo "<div class='mt-2'>";
                                    echo "<small class='text-warning'>⚠️ Columnas faltantes: " . implode(', ', $missing_columns) . "</small>";
                                    echo "</div>";
                                }
                                echo "</div>";
                            }
                            
                            // Estado final
                            echo "<div class='mt-4 p-3 bg-light rounded'>";
                            echo "<h6><i class='fas fa-clipboard-check me-2'></i>Estado del Sistema:</h6>";
                            
                            if ($user_companies_exists && $invitations_exists) {
                                echo "<div class='text-success'>";
                                echo "<i class='fas fa-check-circle me-2'></i>";
                                echo "<strong>✅ Sistema de invitaciones listo para funcionar</strong>";
                                echo "</div>";
                                echo "<p class='mt-2 mb-0'>Todas las tablas necesarias están presentes. Las invitaciones deberían funcionar correctamente.</p>";
                            } else {
                                echo "<div class='text-warning'>";
                                echo "<i class='fas fa-exclamation-triangle me-2'></i>";
                                echo "<strong>⚠️ Sistema parcialmente configurado</strong>";
                                echo "</div>";
                                echo "<p class='mt-2 mb-0'>Algunas tablas faltan. Crea las tablas faltantes para que el sistema funcione completamente.</p>";
                            }
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<h5>Error de conexión:</h5>";
                            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "</div>";
                        }
                        ?>
                        
                        <div class="mt-4 text-center">
                            <a href="../companies/" class="btn btn-primary">
                                <i class="fas fa-building me-2"></i>Probar Invitaciones
                            </a>
                            <a href="../database_analysis.php" class="btn btn-secondary">
                                <i class="fas fa-chart-bar me-2"></i>Análisis Completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
