<?php
/**
 * Script de Optimización de Base de Datos
 * Aplica mejoras de rendimiento y escalabilidad de manera segura
 */

require_once 'config.php';

// Solo permitir acceso a usuarios root
if (!checkAuth() || !checkRole(['root'])) {
    die('Acceso denegado. Solo usuarios root pueden ejecutar optimizaciones.');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Optimización de Base de Datos - <?php echo $lang['app_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .step-success { color: #28a745; }
        .step-error { color: #dc3545; }
        .step-warning { color: #ffc107; }
        .sql-output { background: #f8f9fa; font-family: monospace; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-database me-2"></i>Optimización de Base de Datos
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!isset($_POST['execute'])): ?>
                        <!-- Formulario de confirmación -->
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>⚠️ Importante</h5>
                            <p>Esta operación aplicará optimizaciones a la base de datos incluyendo:</p>
                            <ul>
                                <li>Creación de índices para mejor rendimiento</li>
                                <li>Nuevas tablas para auditoría y cache</li>
                                <li>Optimización de tabla de notificaciones</li>
                                <li>Configuraciones del sistema</li>
                            </ul>
                            <p class="mb-0"><strong>Se recomienda realizar un backup antes de continuar.</strong></p>
                        </div>
                        
                        <form method="POST">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="backup_confirm" required>
                                <label class="form-check-label" for="backup_confirm">
                                    <strong>Confirmo que he realizado un backup de la base de datos</strong>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="risk_confirm" required>
                                <label class="form-check-label" for="risk_confirm">
                                    Entiendo los riesgos y acepto la responsabilidad de estas modificaciones
                                </label>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="execute" value="1" class="btn btn-danger">
                                    <i class="fas fa-play me-2"></i>Ejecutar Optimizaciones
                                </button>
                                <a href="database_analysis.php" class="btn btn-secondary">
                                    <i class="fas fa-chart-bar me-2"></i>Ver Análisis Completo
                                </a>
                            </div>
                        </form>
                        
                        <?php else: ?>
                        <!-- Ejecutar optimizaciones -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-cogs me-2"></i>Ejecutando Optimizaciones...</h5>
                            <p class="mb-0">Por favor espere mientras se aplican las mejoras...</p>
                        </div>
                        
                        <?php
                        try {
                            $db = getDB();
                            $steps = [];
                            
                            // Paso 1: Crear índices para user_invitations
                            echo "<h5>📊 Paso 1: Optimizando índices</h5>";
                            echo "<div class='sql-output p-3 rounded mb-3'>";
                            
                            $indexes = [
                                "ALTER TABLE `user_invitations` ADD INDEX `idx_email_status` (`email`, `status`)",
                                "ALTER TABLE `user_invitations` ADD INDEX `idx_company_status` (`company_id`, `status`)",
                                "ALTER TABLE `user_invitations` ADD INDEX `idx_created_at` (`created_at`)",
                                "ALTER TABLE `companies` ADD INDEX `idx_status_created` (`status`, `created_at`)",
                                "ALTER TABLE `notifications` ADD INDEX `idx_user_status` (`user_id`, `status`)",
                                "ALTER TABLE `notifications` ADD INDEX `idx_created_at` (`created_at`)"
                            ];
                            
                            foreach ($indexes as $index_sql) {
                                try {
                                    $db->exec($index_sql);
                                    echo "<div class='step-success'>✅ " . htmlspecialchars($index_sql) . "</div>";
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                                        echo "<div class='step-warning'>⚠️ Índice ya existe: " . htmlspecialchars($index_sql) . "</div>";
                                    } else {
                                        echo "<div class='step-error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                    }
                                }
                            }
                            echo "</div>";
                            
                            // Paso 2: Crear tabla de auditoría
                            echo "<h5>📝 Paso 2: Creando tabla de auditoría</h5>";
                            echo "<div class='sql-output p-3 rounded mb-3'>";
                            
                            $audit_sql = "
                            CREATE TABLE IF NOT EXISTS `audit_log` (
                              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                              `table_name` VARCHAR(64) NOT NULL,
                              `record_id` INT UNSIGNED NOT NULL,
                              `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
                              `old_values` JSON NULL,
                              `new_values` JSON NULL,
                              `user_id` INT UNSIGNED NULL,
                              `company_id` INT UNSIGNED NULL,
                              `ip_address` VARCHAR(45) NULL,
                              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (`id`),
                              INDEX `idx_table_record` (`table_name`, `record_id`),
                              INDEX `idx_created_at` (`created_at`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                            ";
                            
                            try {
                                $db->exec($audit_sql);
                                echo "<div class='step-success'>✅ Tabla audit_log creada correctamente</div>";
                            } catch (Exception $e) {
                                echo "<div class='step-error'>❌ Error creando audit_log: " . htmlspecialchars($e->getMessage()) . "</div>";
                            }
                            echo "</div>";
                            
                            // Paso 3: Crear tabla de configuraciones
                            echo "<h5>⚙️ Paso 3: Creando sistema de configuraciones</h5>";
                            echo "<div class='sql-output p-3 rounded mb-3'>";
                            
                            $settings_sql = "
                            CREATE TABLE IF NOT EXISTS `system_settings` (
                              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                              `key_name` VARCHAR(128) NOT NULL UNIQUE,
                              `value` TEXT NULL,
                              `type` ENUM('string', 'integer', 'boolean', 'json', 'float') DEFAULT 'string',
                              `description` TEXT NULL,
                              `is_public` BOOLEAN DEFAULT FALSE,
                              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (`id`),
                              UNIQUE KEY `uk_key_name` (`key_name`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                            ";
                            
                            try {
                                $db->exec($settings_sql);
                                echo "<div class='step-success'>✅ Tabla system_settings creada correctamente</div>";
                                
                                // Insertar configuraciones básicas
                                $config_inserts = [
                                    "INSERT IGNORE INTO `system_settings` (`key_name`, `value`, `type`, `description`, `is_public`) VALUES ('app_name', 'Indice SaaS', 'string', 'Nombre de la aplicación', 1)",
                                    "INSERT IGNORE INTO `system_settings` (`key_name`, `value`, `type`, `description`, `is_public`) VALUES ('app_version', '2.0.0', 'string', 'Versión actual', 1)",
                                    "INSERT IGNORE INTO `system_settings` (`key_name`, `value`, `type`, `description`, `is_public`) VALUES ('maintenance_mode', 'false', 'boolean', 'Modo mantenimiento', 0)",
                                    "INSERT IGNORE INTO `system_settings` (`key_name`, `value`, `type`, `description`, `is_public`) VALUES ('default_language', 'es', 'string', 'Idioma por defecto', 1)"
                                ];
                                
                                foreach ($config_inserts as $insert_sql) {
                                    try {
                                        $db->exec($insert_sql);
                                        echo "<div class='step-success'>✅ Configuración insertada</div>";
                                    } catch (Exception $e) {
                                        echo "<div class='step-warning'>⚠️ Configuración ya existe</div>";
                                    }
                                }
                                
                            } catch (Exception $e) {
                                echo "<div class='step-error'>❌ Error creando system_settings: " . htmlspecialchars($e->getMessage()) . "</div>";
                            }
                            echo "</div>";
                            
                            // Paso 4: Optimizar tabla notifications
                            echo "<h5>🔔 Paso 4: Optimizando tabla de notificaciones</h5>";
                            echo "<div class='sql-output p-3 rounded mb-3'>";
                            
                            $notification_columns = [
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NOT NULL DEFAULT ''",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `message` TEXT NULL",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `type` VARCHAR(50) NOT NULL DEFAULT 'general'",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `user_id` INT UNSIGNED NULL",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'read', 'archived') DEFAULT 'pending'",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `icon` VARCHAR(50) NULL",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `color` VARCHAR(20) NULL",
                                "ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `action_url` TEXT NULL"
                            ];
                            
                            foreach ($notification_columns as $column_sql) {
                                try {
                                    $db->exec($column_sql);
                                    echo "<div class='step-success'>✅ " . htmlspecialchars($column_sql) . "</div>";
                                } catch (Exception $e) {
                                    echo "<div class='step-warning'>⚠️ Columna ya existe o error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                }
                            }
                            echo "</div>";
                            
                            // Paso 5: Resumen final
                            echo "<div class='alert alert-success mt-4'>";
                            echo "<h5><i class='fas fa-check-circle me-2'></i>¡Optimización Completada!</h5>";
                            echo "<p>Las siguientes mejoras han sido aplicadas:</p>";
                            echo "<ul>";
                            echo "<li>✅ Índices optimizados para mejor rendimiento</li>";
                            echo "<li>✅ Sistema de auditoría implementado</li>";
                            echo "<li>✅ Configuraciones centralizadas</li>";
                            echo "<li>✅ Tabla de notificaciones mejorada</li>";
                            echo "</ul>";
                            echo "<p class='mb-0'><strong>Tu sistema SaaS ahora está optimizado para mejor rendimiento y escalabilidad.</strong></p>";
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<h5>Error durante la optimización:</h5>";
                            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "</div>";
                        }
                        ?>
                        
                        <div class="mt-4 text-center">
                            <a href="database_analysis.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar me-2"></i>Ver Análisis Actualizado
                            </a>
                            <a href="companies/" class="btn btn-success">
                                <i class="fas fa-building me-2"></i>Probar Sistema
                            </a>
                        </div>
                        
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
