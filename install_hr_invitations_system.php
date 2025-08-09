<?php
/**
 * INSTALADOR DEL SISTEMA DE INVITACIONES - MÓDULO HR
 * Crea las tablas necesarias para el sistema de invitaciones
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "🚀 INSTALANDO SISTEMA DE INVITACIONES\n";
    echo "=====================================\n\n";
    
    // =====================================================
    // TABLA: invitations
    // =====================================================
    echo "📋 Creando tabla 'invitations'... ";
    
    $sql = "CREATE TABLE IF NOT EXISTS `invitations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `company_id` int(11) NOT NULL,
        `business_id` int(11) NOT NULL,
        `unit_id` int(11) DEFAULT NULL,
        `email` varchar(255) NOT NULL,
        `first_name` varchar(100) NOT NULL,
        `last_name` varchar(100) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `fiscal_id` varchar(50) DEFAULT NULL,
        `department_id` int(11) DEFAULT NULL,
        `position_id` int(11) DEFAULT NULL,
        `salary` decimal(10,2) DEFAULT 0.00,
        `role` enum('user', 'moderator', 'admin', 'superadmin') DEFAULT 'user',
        `permissions` longtext DEFAULT NULL COMMENT 'JSON array of permissions',
        `modules` longtext DEFAULT NULL COMMENT 'JSON array of assigned modules',
        `token` varchar(64) NOT NULL UNIQUE,
        `expires_at` datetime NOT NULL,
        `status` enum('pending', 'accepted', 'expired', 'cancelled') DEFAULT 'pending',
        `invited_by` int(11) NOT NULL,
        `accepted_at` datetime DEFAULT NULL,
        `accepted_by_user_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_token` (`token`),
        KEY `idx_email_status` (`email`, `status`),
        KEY `idx_company_business` (`company_id`, `business_id`),
        KEY `idx_expires` (`expires_at`),
        KEY `idx_invited_by` (`invited_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistema de invitaciones para nuevos usuarios'";
    
    $db->exec($sql);
    echo "✅\n";
    
    // =====================================================
    // TABLA: user_companies (si no existe)
    // =====================================================
    echo "📋 Verificando tabla 'user_companies'... ";
    
    $stmt = $db->query("SHOW TABLES LIKE 'user_companies'");
    if (!$stmt->fetch()) {
        echo "creando... ";
        $sql = "CREATE TABLE `user_companies` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `role` enum('user', 'moderator', 'admin', 'superadmin') DEFAULT 'user',
            `status` enum('active', 'inactive', 'suspended') DEFAULT 'active',
            `assigned_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_user_company` (`user_id`, `company_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_company_id` (`company_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
    }
    echo "✅\n";
    
    // =====================================================
    // TABLA: user_units (si no existe)
    // =====================================================
    echo "📋 Verificando tabla 'user_units'... ";
    
    $stmt = $db->query("SHOW TABLES LIKE 'user_units'");
    if (!$stmt->fetch()) {
        echo "creando... ";
        $sql = "CREATE TABLE `user_units` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `unit_id` int(11) NOT NULL,
            `assigned_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_user_unit` (`user_id`, `unit_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_unit_id` (`unit_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
    }
    echo "✅\n";
    
    // =====================================================
    // TABLA: user_businesses (si no existe)
    // =====================================================
    echo "📋 Verificando tabla 'user_businesses'... ";
    
    $stmt = $db->query("SHOW TABLES LIKE 'user_businesses'");
    if (!$stmt->fetch()) {
        echo "creando... ";
        $sql = "CREATE TABLE `user_businesses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `business_id` int(11) NOT NULL,
            `assigned_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_user_business` (`user_id`, `business_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_business_id` (`business_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
    }
    echo "✅\n";
    
    // =====================================================
    // AGREGAR CAMPOS A TABLA employees (si no existen)
    // =====================================================
    echo "📋 Verificando campos adicionales en 'employees'... ";
    
    try {
        // Verificar si existe el campo fiscal_id
        $stmt = $db->query("SHOW COLUMNS FROM employees LIKE 'fiscal_id'");
        if (!$stmt->fetch()) {
            echo "agregando fiscal_id... ";
            $db->exec("ALTER TABLE employees ADD COLUMN fiscal_id VARCHAR(50) DEFAULT NULL AFTER phone");
        }
        
        // Verificar si existe el campo employee_id en expenses (para referencia)
        $stmt = $db->query("SHOW TABLES LIKE 'expenses'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE 'employee_id'");
            if (!$stmt->fetch()) {
                echo "agregando employee_id a expenses... ";
                $db->exec("ALTER TABLE expenses ADD COLUMN employee_id INT(11) DEFAULT NULL AFTER provider_id");
                $db->exec("ALTER TABLE expenses ADD COLUMN recurring_days INT DEFAULT NULL AFTER status");
                $db->exec("ALTER TABLE expenses ADD COLUMN next_recurring DATE DEFAULT NULL AFTER recurring_days");
            }
        }
        
    } catch (Exception $e) {
        echo "⚠️  Error al agregar campos: " . $e->getMessage() . "\n";
    }
    
    echo "✅\n";
    
    // =====================================================
    // DATOS DE PRUEBA PARA INVITACIONES
    // =====================================================
    echo "📋 Verificando datos de prueba... ";
    
    // Verificar si ya existen invitaciones
    $stmt = $db->query("SELECT COUNT(*) FROM invitations");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "creando invitación de prueba... ";
        
        // Crear una invitación de prueba simplificada
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $sql = "INSERT INTO invitations (
            company_id, business_id, unit_id, email, role, 
            token, expiration_date, status, sent_by, sent_date
        ) VALUES (1, 1, 2, 'test@ejemplo.com', 'user', ?, ?, 'pending', 1, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$token, $expires_at]);
        
        echo "Token de prueba: " . $token . "
";
    }
    
    echo "✅\n";
    
    // =====================================================
    // VERIFICACIÓN FINAL
    // =====================================================
    echo "\n📊 VERIFICACIÓN FINAL:\n";
    echo "=====================\n";
    
    $tables_to_check = ['invitations', 'user_companies', 'user_units', 'user_businesses'];
    
    foreach ($tables_to_check as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count registros\n";
        } else {
            echo "❌ $table: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // Verificar estructura de employees
    echo "📋 Campos de tabla employees:\n";
    $stmt = $db->query("DESCRIBE employees");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['fiscal_id', 'employee_number', 'email'])) {
            echo "   ✅ {$col['Field']} ({$col['Type']})\n";
        }
    }
    
    echo "\n🎉 INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "=====================================\n\n";
    
    echo "📝 SIGUIENTE PASO:\n";
    echo "- Ejecutar: php add_human_resources_module.php\n";
    echo "- Verificar en el navegador que el módulo HR sea accesible\n\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la instalación: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
