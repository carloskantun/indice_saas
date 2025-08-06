<?php
/**
 * Script de corrección rápida para arreglar problemas de base de datos
 * Ejecutar una sola vez para solucionar problemas de estructura
 */

require_once 'config.php';

// Solo permitir acceso a usuarios root/superadmin
if (!checkAuth() || !checkRole(['root', 'superadmin'])) {
    die('❌ Acceso denegado. Solo usuarios root/superadmin pueden ejecutar este script.');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Corrección de Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="mb-0">
                            <i class="fas fa-tools me-2"></i>Corrección de Base de Datos
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        try {
                            $db = getDB();
                            echo "<h5>📋 Verificando y corrigiendo estructura de base de datos...</h5>";
                            echo "<div class='mb-4'>";
                            
                            $fixes_applied = 0;
                            
                            // 1. Verificar y corregir tabla notifications
                            echo "<h6 class='mt-3'>🔔 Verificando tabla notifications...</h6>";
                            
                            $tables_query = $db->query("SHOW TABLES LIKE 'notifications'");
                            $notifications_exists = $tables_query->fetch();
                            
                            if ($notifications_exists) {
                                // Verificar columnas faltantes
                                $columns_query = $db->query("SHOW COLUMNS FROM notifications");
                                $columns = $columns_query->fetchAll(PDO::FETCH_COLUMN);
                                
                                $required_columns = [
                                    'user_id' => 'int(11) NOT NULL',
                                    'company_id' => 'int(11) DEFAULT NULL',
                                    'type' => 'varchar(50) DEFAULT NULL',
                                    'title' => 'varchar(255) DEFAULT NULL',
                                    'message' => 'text',
                                    'action_url' => 'varchar(255) DEFAULT NULL',
                                    'status' => "enum('pending','read','dismissed') DEFAULT 'pending'",
                                    'created_at' => 'timestamp NULL DEFAULT CURRENT_TIMESTAMP'
                                ];
                                
                                foreach ($required_columns as $column => $definition) {
                                    if (!in_array($column, $columns)) {
                                        echo "<div class='alert alert-info'>";
                                        echo "➕ Agregando columna faltante: <code>$column</code>";
                                        echo "</div>";
                                        
                                        $alter_sql = "ALTER TABLE notifications ADD COLUMN `$column` $definition";
                                        $db->exec($alter_sql);
                                        $fixes_applied++;
                                    }
                                }
                                
                                echo "<div class='alert alert-success'>";
                                echo "✅ Tabla notifications verificada y actualizada";
                                echo "</div>";
                                
                            } else {
                                echo "<div class='alert alert-warning'>";
                                echo "⚠️ Tabla notifications no existe. Creando...";
                                echo "</div>";
                                
                                $create_notifications_sql = "
                                CREATE TABLE `notifications` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `user_id` int(11) NOT NULL,
                                  `company_id` int(11) DEFAULT NULL,
                                  `type` varchar(50) DEFAULT NULL,
                                  `title` varchar(255) DEFAULT NULL,
                                  `message` text,
                                  `action_url` varchar(255) DEFAULT NULL,
                                  `status` enum('pending','read','dismissed') DEFAULT 'pending',
                                  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                                  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                  PRIMARY KEY (`id`),
                                  KEY `user_id` (`user_id`),
                                  KEY `company_id` (`company_id`),
                                  KEY `status` (`status`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                                ";
                                $db->exec($create_notifications_sql);
                                echo "<div class='alert alert-success'>";
                                echo "✅ Tabla notifications creada correctamente";
                                echo "</div>";
                                $fixes_applied++;
                            }
                            
                            // 2. Verificar tabla businesses (para el problema de creación)
                            echo "<h6 class='mt-3'>🏢 Verificando tabla businesses...</h6>";
                            
                            $business_columns_query = $db->query("SHOW COLUMNS FROM businesses");
                            $business_columns = $business_columns_query->fetchAll(PDO::FETCH_COLUMN);
                            
                            $required_business_columns = [
                                'name' => 'varchar(255) NOT NULL',
                                'description' => 'text',
                                'type_id' => 'int(11) DEFAULT NULL',
                                'unit_id' => 'int(11) DEFAULT NULL',
                                'status' => "enum('active','inactive') DEFAULT 'active'",
                                'created_by' => 'int(11) DEFAULT NULL',
                                'created_at' => 'timestamp NULL DEFAULT CURRENT_TIMESTAMP'
                            ];
                            
                            foreach ($required_business_columns as $column => $definition) {
                                if (!in_array($column, $business_columns)) {
                                    echo "<div class='alert alert-info'>";
                                    echo "➕ Agregando columna faltante en businesses: <code>$column</code>";
                                    echo "</div>";
                                    
                                    $alter_sql = "ALTER TABLE businesses ADD COLUMN `$column` $definition";
                                    $db->exec($alter_sql);
                                    $fixes_applied++;
                                }
                            }
                            
                            echo "<div class='alert alert-success'>";
                            echo "✅ Tabla businesses verificada";
                            echo "</div>";
                            
                            // 3. Verificar que user_companies tenga joined_at
                            echo "<h6 class='mt-3'>👥 Verificando tabla user_companies...</h6>";
                            
                            $uc_columns_query = $db->query("SHOW COLUMNS FROM user_companies");
                            $uc_columns = $uc_columns_query->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (!in_array('joined_at', $uc_columns)) {
                                echo "<div class='alert alert-info'>";
                                echo "➕ Agregando columna joined_at a user_companies";
                                echo "</div>";
                                
                                $alter_sql = "ALTER TABLE user_companies ADD COLUMN `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP";
                                $db->exec($alter_sql);
                                $fixes_applied++;
                            }
                            
                            echo "<div class='alert alert-success'>";
                            echo "✅ Tabla user_companies verificada";
                            echo "</div>";
                            
                            // 4. Verificar permisos para businesses
                            echo "<h6 class='mt-3'>🔐 Verificando permisos para businesses...</h6>";
                            
                            $business_perms = [
                                ['businesses.view', 'Ver negocios', 'businesses'],
                                ['businesses.create', 'Crear negocios', 'businesses'],
                                ['businesses.edit', 'Editar negocios', 'businesses'],
                                ['businesses.delete', 'Eliminar negocios', 'businesses']
                            ];
                            
                            foreach ($business_perms as $perm) {
                                $check_perm = $db->prepare("SELECT id FROM permissions WHERE key_name = ?");
                                $check_perm->execute([$perm[0]]);
                                
                                if (!$check_perm->fetch()) {
                                    $insert_perm = $db->prepare("INSERT INTO permissions (key_name, description, module) VALUES (?, ?, ?)");
                                    $insert_perm->execute($perm);
                                    echo "<div class='alert alert-info'>";
                                    echo "➕ Agregando permiso: <code>{$perm[0]}</code>";
                                    echo "</div>";
                                    $fixes_applied++;
                                }
                            }
                            
                            echo "<div class='alert alert-success'>";
                            echo "✅ Permisos para businesses verificados";
                            echo "</div>";
                            
                            // Resumen final
                            echo "<div class='mt-4 p-3 bg-light rounded'>";
                            echo "<h6><i class='fas fa-clipboard-check me-2'></i>Resumen de Correcciones:</h6>";
                            
                            if ($fixes_applied > 0) {
                                echo "<div class='text-success'>";
                                echo "<i class='fas fa-check-circle me-2'></i>";
                                echo "<strong>✅ Se aplicaron $fixes_applied correcciones</strong>";
                                echo "</div>";
                                echo "<p class='mt-2 mb-0'>La base de datos ha sido actualizada. Los errores reportados deberían estar resueltos.</p>";
                            } else {
                                echo "<div class='text-info'>";
                                echo "<i class='fas fa-info-circle me-2'></i>";
                                echo "<strong>ℹ️ No se necesitaron correcciones</strong>";
                                echo "</div>";
                                echo "<p class='mt-2 mb-0'>La estructura de la base de datos está correcta.</p>";
                            }
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<h5>❌ Error:</h5>";
                            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "</div>";
                        }
                        ?>
                        
                        <div class="mt-4 text-center">
                            <a href="companies/" class="btn btn-primary">
                                <i class="fas fa-building me-2"></i>Ir a Empresas
                            </a>
                            <a href="businesses/" class="btn btn-success">
                                <i class="fas fa-store me-2"></i>Probar Negocios
                            </a>
                            <a href="test_invitation.php" class="btn btn-warning">
                                <i class="fas fa-envelope me-2"></i>Probar Invitaciones
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
