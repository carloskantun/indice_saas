<?php
/**
 * INSTALADOR COMPLETO MÓDULO HUMAN RESOURCES
 * Sistema SaaS Indice
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "🚀 INSTALANDO MÓDULO HUMAN RESOURCES\n";
    echo "====================================\n\n";
    
    // =====================================================
    // PASO 1: CREAR TABLAS
    // =====================================================
    echo "📋 PASO 1: Creando tablas...\n";
    
    // Tabla departments
    echo "   Creando tabla 'departments'... ";
    $sql = "CREATE TABLE IF NOT EXISTS `departments` (
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
    
    // Tabla positions
    echo "   Creando tabla 'positions'... ";
    $sql = "CREATE TABLE IF NOT EXISTS `positions` (
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
    
    // Tabla employees
    echo "   Creando tabla 'employees'... ";
    $sql = "CREATE TABLE IF NOT EXISTS `employees` (
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
    
    // Foreign Keys (intentar agregar, ignorar errores si ya existen)
    echo "   Agregando foreign keys... ";
    try {
        $db->exec("ALTER TABLE `positions` ADD FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT");
    } catch (Exception $e) {
        // FK ya existe, continuar
    }
    try {
        $db->exec("ALTER TABLE `employees` ADD FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT");
    } catch (Exception $e) {
        // FK ya existe, continuar
    }
    try {
        $db->exec("ALTER TABLE `employees` ADD FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE RESTRICT");
    } catch (Exception $e) {
        // FK ya existe, continuar
    }
    echo "✅\n";
    
    // =====================================================
    // PASO 2: CREAR TRIGGER
    // =====================================================
    echo "   Creando trigger para números de empleado... ";
    
    try {
        // Primero eliminar el trigger si existe
        $db->exec("DROP TRIGGER IF EXISTS `generate_employee_number`");
        
        // Luego crear el nuevo trigger con sintaxis corregida
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
        echo "⚠️  (Omitiendo trigger: " . $e->getMessage() . ")\n";
        echo "   El trigger se puede crear manualmente más tarde\n";
    }
    
    // =====================================================
    // PASO 3: REGISTRAR MÓDULO
    // =====================================================
    echo "\n📦 PASO 2: Registrando módulo en el sistema...\n";
    
    // Verificar si ya existe el módulo
    $stmt = $db->prepare("SELECT id FROM modules WHERE slug = 'human-resources' OR name = 'Recursos Humanos' OR name = 'Empleados'");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "   ⚠️  Módulo ya existe con ID: {$existing['id']}, actualizando...\n";
        
        $sql = "UPDATE modules SET 
                name = 'Recursos Humanos',
                slug = 'human-resources',
                description = 'Gestión completa de empleados, departamentos y posiciones',
                url = '/modules/human-resources/',
                icon = 'fas fa-users',
                color = '#3498db',
                status = 'active',
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$existing['id']]);
        $moduleId = $existing['id'];
        
    } else {
        echo "   ➕ Creando nuevo módulo...\n";
        
        $sql = "INSERT INTO modules (name, slug, description, url, icon, color, status, created_at) 
                VALUES ('Recursos Humanos', 'human-resources', 'Gestión completa de empleados, departamentos y posiciones', '/modules/human-resources/', 'fas fa-users', '#3498db', 'active', NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute();
        $moduleId = $db->lastInsertId();
    }
    
    echo "   ✅ Módulo registrado con ID: $moduleId\n";
    
    // =====================================================
    // PASO 4: REGISTRAR PERMISOS
    // =====================================================
    echo "\n🔐 PASO 3: Registrando permisos...\n";
    
    // Verificar estructura de tabla permissions
    $stmt = $db->query("DESCRIBE permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $keyColumn = in_array('key', $columns) ? 'key' : 'key_name';
    echo "   Usando columna: $keyColumn\n";
    
    $permissions = [
        'employees.view' => 'Ver empleados',
        'employees.create' => 'Crear empleados',
        'employees.edit' => 'Editar empleados',
        'employees.delete' => 'Eliminar empleados',
        'employees.export' => 'Exportar datos de empleados',
        'employees.kpis' => 'Ver estadísticas de empleados',
        'departments.view' => 'Ver departamentos',
        'departments.create' => 'Crear departamentos',
        'departments.edit' => 'Editar departamentos',
        'departments.delete' => 'Eliminar departamentos',
        'positions.view' => 'Ver posiciones',
        'positions.create' => 'Crear posiciones',
        'positions.edit' => 'Editar posiciones',
        'positions.delete' => 'Eliminar posiciones'
    ];
    
    foreach ($permissions as $key => $description) {
        $stmt = $db->prepare("INSERT IGNORE INTO permissions ($keyColumn, description, module) VALUES (?, ?, 'human-resources')");
        $result = $stmt->execute([$key, $description]);
        echo "   ✅ $key\n";
    }
    
    // =====================================================
    // PASO 5: ASIGNAR PERMISOS A ROLES
    // =====================================================
    echo "\n👥 PASO 4: Asignando permisos a roles...\n";
    
    // Verificar estructura de tabla role_permissions
    $stmt = $db->query("DESCRIBE role_permissions");
    $roleColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $permissionColumn = in_array('permission_key', $roleColumns) ? 'permission_key' : 'permission_id';
    echo "   Usando columna: $permissionColumn\n";
    
    // Roles y sus permisos
    $rolePermissions = [
        'root' => array_keys($permissions),
        'superadmin' => array_keys($permissions),
        'admin' => array_keys($permissions),
        'moderator' => ['employees.view', 'employees.create', 'employees.edit', 'departments.view', 'positions.view'],
        'user' => ['employees.view', 'departments.view', 'positions.view']
    ];
    
    foreach ($rolePermissions as $role => $perms) {
        echo "   Asignando permisos a rol: $role\n";
        foreach ($perms as $perm) {
            if ($permissionColumn === 'permission_key') {
                $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role, permission_key) VALUES (?, ?)");
                $result = $stmt->execute([$role, $perm]);
            } else {
                // Buscar ID del permiso
                $stmt = $db->prepare("SELECT id FROM permissions WHERE $keyColumn = ?");
                $stmt->execute([$perm]);
                $permId = $stmt->fetchColumn();
                
                if ($permId) {
                    $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)");
                    $result = $stmt->execute([$role, $permId]);
                }
            }
        }
    }
    
    // =====================================================
    // PASO 6: DATOS DE PRUEBA (OPCIONAL)
    // =====================================================
    echo "\n🧪 PASO 5: Agregando datos de prueba...\n";
    
    // Verificar si ya existen departamentos
    $stmt = $db->query("SELECT COUNT(*) FROM departments");
    $existingDepts = $stmt->fetchColumn();
    
    if ($existingDepts == 0) {
        echo "   Agregando departamentos de prueba...\n";
        
        // Usar company_id y business_id = 1 por defecto (ajustar según necesidad)
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
        
        echo "   Agregando posiciones de prueba...\n";
        
        $positions = [
            [1, 'Gerente de RRHH', 'Responsable de la gestión integral de recursos humanos', 25000, 35000],
            [1, 'Especialista en RRHH', 'Apoyo en procesos de reclutamiento y capacitación', 15000, 22000],
            [2, 'Desarrollador Senior', 'Desarrollo de aplicaciones y sistemas', 30000, 45000],
            [2, 'Desarrollador Junior', 'Apoyo en desarrollo y mantenimiento de código', 18000, 25000],
            [2, 'DevOps Engineer', 'Gestión de infraestructura y despliegues', 28000, 40000],
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
        echo "   ⚠️  Ya existen $existingDepts departamentos, omitiendo datos de prueba\n";
    }
    
    // =====================================================
    // VERIFICACIÓN FINAL
    // =====================================================
    echo "\n📊 VERIFICACIÓN FINAL:\n";
    echo "====================\n";
    
    // Verificar tablas
    $tables = ['departments', 'positions', 'employees'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "📋 Tabla '$table': $count registros\n";
    }
    
    // Verificar módulo
    $stmt = $db->prepare("SELECT name, slug, status FROM modules WHERE slug = 'human-resources'");
    $stmt->execute();
    $module = $stmt->fetch();
    if ($module) {
        echo "📦 Módulo: {$module['name']} ({$module['slug']}) - {$module['status']}\n";
    }
    
    // Verificar permisos
    $stmt = $db->prepare("SELECT COUNT(*) FROM permissions WHERE module = 'human-resources'");
    $stmt->execute();
    $permCount = $stmt->fetchColumn();
    echo "🔐 Permisos: $permCount registrados\n";
    
    // Verificar trigger
    $stmt = $db->query("SHOW TRIGGERS LIKE 'employees'");
    $triggers = $stmt->fetchAll();
    echo "⚙️  Triggers: " . count($triggers) . " activos\n";
    
    echo "\n🎉 ¡INSTALACIÓN COMPLETA!\n";
    echo "========================\n";
    echo "✅ Módulo Human Resources instalado correctamente\n";
    echo "🔗 URL: /modules/human-resources/\n";
    echo "🎨 Icono: fas fa-users\n";
    echo "🎨 Color: #3498db\n";
    echo "📱 Estado: Activo\n\n";
    echo "🚀 Puedes acceder al módulo desde el panel de módulos\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR DURANTE LA INSTALACIÓN:\n";
    echo "================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
?>
