<?php
/**
 * SCRIPT PARA CREAR SOLO LAS TABLAS DE HUMAN RESOURCES
 * Sistema SaaS Indice
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "🔧 CREANDO TABLAS HUMAN RESOURCES\n";
    echo "=================================\n\n";
    
    // Verificar qué tablas ya existen
    $tables = ['departments', 'positions', 'employees'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $existingTables[] = $table;
            echo "✅ Tabla '$table' ya existe\n";
        } else {
            echo "❌ Tabla '$table' no existe, se creará\n";
        }
    }
    
    echo "\n";
    
    // Crear tabla departments si no existe
    if (!in_array('departments', $existingTables)) {
        echo "📋 Creando tabla 'departments'... ";
        $sql = "CREATE TABLE `departments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) NOT NULL,
            `business_id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `manager_id` int(11) DEFAULT NULL,
            `status` enum('active', 'inactive') DEFAULT 'active',
            `created_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_company_business` (`company_id`, `business_id`),
            KEY `idx_manager` (`manager_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅\n";
    }
    
    // Crear tabla positions si no existe
    if (!in_array('positions', $existingTables)) {
        echo "📋 Creando tabla 'positions'... ";
        $sql = "CREATE TABLE `positions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) NOT NULL,
            `business_id` int(11) NOT NULL,
            `department_id` int(11) NOT NULL,
            `title` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `min_salary` decimal(10,2) DEFAULT 0.00,
            `max_salary` decimal(10,2) DEFAULT 0.00,
            `status` enum('active', 'inactive') DEFAULT 'active',
            `created_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_company_business` (`company_id`, `business_id`),
            KEY `idx_department` (`department_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅\n";
    }
    
    // Crear tabla employees si no existe
    if (!in_array('employees', $existingTables)) {
        echo "📋 Creando tabla 'employees'... ";
        $sql = "CREATE TABLE `employees` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) NOT NULL,
            `business_id` int(11) NOT NULL,
            `unit_id` int(11) DEFAULT NULL,
            `employee_number` varchar(50) DEFAULT NULL,
            `first_name` varchar(100) NOT NULL,
            `last_name` varchar(100) NOT NULL,
            `email` varchar(255) DEFAULT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `department_id` int(11) NOT NULL,
            `position_id` int(11) NOT NULL,
            `hire_date` date NOT NULL,
            `employment_type` enum('Tiempo_Completo', 'Medio_Tiempo', 'Temporal', 'Freelance', 'Practicante') DEFAULT 'Tiempo_Completo',
            `contract_type` enum('Indefinido', 'Temporal', 'Por_Obra', 'Practicas') DEFAULT 'Indefinido',
            `salary` decimal(10,2) DEFAULT 0.00,
            `payment_frequency` enum('Semanal', 'Quincenal', 'Mensual') DEFAULT 'Mensual',
            `status` enum('Activo', 'Inactivo', 'Vacaciones', 'Licencia', 'Baja') DEFAULT 'Activo',
            `notes` text DEFAULT NULL,
            `created_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_employee_number_company` (`employee_number`, `company_id`),
            KEY `idx_company_business` (`company_id`, `business_id`),
            KEY `idx_department` (`department_id`),
            KEY `idx_position` (`position_id`),
            KEY `idx_status` (`status`),
            KEY `idx_hire_date` (`hire_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅\n";
    }
    
    // Agregar foreign keys
    echo "🔗 Agregando foreign keys... ";
    try {
        // Solo agregar si ambas tablas existen
        if (in_array('departments', $existingTables) || !in_array('departments', $existingTables)) {
            try {
                $db->exec("ALTER TABLE `positions` ADD CONSTRAINT `fk_positions_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT");
            } catch (Exception $e) {
                // FK ya existe
            }
        }
        
        if (in_array('employees', $existingTables) || !in_array('employees', $existingTables)) {
            try {
                $db->exec("ALTER TABLE `employees` ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT");
            } catch (Exception $e) {
                // FK ya existe
            }
            
            try {
                $db->exec("ALTER TABLE `employees` ADD CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE RESTRICT");
            } catch (Exception $e) {
                // FK ya existe
            }
        }
        echo "✅\n";
    } catch (Exception $e) {
        echo "⚠️  (Algunas FK ya existen)\n";
    }
    
    // Crear trigger si la tabla employees existe
    if (in_array('employees', $existingTables) || !in_array('employees', $existingTables)) {
        echo "⚙️  Creando trigger para números de empleado... ";
        try {
            $db->exec("DROP TRIGGER IF EXISTS `generate_employee_number`");
            
            $triggerSQL = "CREATE TRIGGER `generate_employee_number` 
            BEFORE INSERT ON `employees` 
            FOR EACH ROW 
            BEGIN
                IF NEW.employee_number IS NULL OR NEW.employee_number = '' THEN
                    SET NEW.employee_number = CONCAT('EMP', LPAD((
                        SELECT COALESCE(MAX(CAST(SUBSTRING(employee_number, 4) AS UNSIGNED)), 0) + 1 
                        FROM employees 
                        WHERE company_id = NEW.company_id 
                        AND employee_number REGEXP '^EMP[0-9]+\$'
                    ), 4, '0'));
                END IF;
            END";
            
            $db->exec($triggerSQL);
            echo "✅\n";
        } catch (Exception $e) {
            echo "⚠️  Error: " . $e->getMessage() . "\n";
            echo "   Puedes crear el trigger manualmente en phpMyAdmin\n";
        }
    }
    
    // Agregar datos de prueba si las tablas están vacías
    echo "\n🧪 Verificando datos de prueba...\n";
    
    // Verificar departamentos
    if (in_array('departments', $existingTables) || !in_array('departments', $existingTables)) {
        $stmt = $db->query("SELECT COUNT(*) FROM departments");
        $deptCount = $stmt->fetchColumn();
        
        if ($deptCount == 0) {
            echo "   Agregando departamentos de prueba...\n";
            
            $departments = [
                ['Recursos Humanos', 'Gestión de personal y políticas laborales'],
                ['Tecnología', 'Desarrollo de software y soporte técnico'],
                ['Ventas', 'Gestión comercial y atención a clientes'],
                ['Administración', 'Gestión administrativa y financiera'],
                ['Marketing', 'Promoción y estrategias de mercadeo']
            ];
            
            foreach ($departments as $dept) {
                $stmt = $db->prepare("INSERT INTO departments (company_id, business_id, name, description, created_by) VALUES (1, 1, ?, ?, 1)");
                $stmt->execute([$dept[0], $dept[1]]);
                echo "     ✅ {$dept[0]}\n";
            }
            
            // Agregar posiciones de prueba
            echo "   Agregando posiciones de prueba...\n";
            
            $positions = [
                [1, 'Gerente de RRHH', 'Responsable de la gestión integral de recursos humanos', 25000, 35000],
                [1, 'Especialista en RRHH', 'Apoyo en procesos de reclutamiento y capacitación', 15000, 22000],
                [2, 'Desarrollador Senior', 'Desarrollo de aplicaciones y sistemas', 30000, 45000],
                [2, 'Desarrollador Junior', 'Apoyo en desarrollo y mantenimiento de código', 18000, 25000],
                [3, 'Gerente de Ventas', 'Responsable del equipo comercial', 22000, 32000],
                [3, 'Ejecutivo de Ventas', 'Gestión de clientes y cierre de ventas', 12000, 20000],
                [4, 'Contador', 'Gestión contable y fiscal', 20000, 28000],
                [4, 'Asistente Administrativo', 'Apoyo en tareas administrativas', 10000, 15000],
                [5, 'Especialista en Marketing', 'Estrategias de marketing digital', 16000, 24000]
            ];
            
            foreach ($positions as $pos) {
                $stmt = $db->prepare("INSERT INTO positions (company_id, business_id, department_id, title, description, min_salary, max_salary, created_by) VALUES (1, 1, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$pos[0], $pos[1], $pos[2], $pos[3], $pos[4]]);
                echo "     ✅ {$pos[1]}\n";
            }
        } else {
            echo "   ⚠️  Ya existen $deptCount departamentos\n";
        }
    }
    
    // Verificación final
    echo "\n📊 ESTADO FINAL:\n";
    echo "================\n";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Tabla '$table': $count registros\n";
        } else {
            echo "❌ Tabla '$table': NO EXISTE\n";
        }
    }
    
    // Verificar trigger
    $stmt = $db->query("SHOW TRIGGERS LIKE 'employees'");
    $triggers = $stmt->fetchAll();
    echo "⚙️  Triggers activos: " . count($triggers) . "\n";
    
    echo "\n🎉 ¡PROCESO COMPLETADO!\n";
    echo "======================\n";
    echo "✅ Tablas verificadas/creadas\n";
    echo "🔗 Puedes acceder al módulo: /modules/human-resources/\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR:\n";
    echo "=========\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
