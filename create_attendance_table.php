<?php
/**
 * SCRIPT PARA CREAR LA TABLA DE ASISTENCIA
 * Sistema SaaS Indice - Módulo de Recursos Humanos
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "📋 CREANDO TABLA DE ASISTENCIA\n";
    echo "==============================\n\n";
    
    // Verificar si la tabla ya existe
    $stmt = $db->query("SHOW TABLES LIKE 'employee_attendance'");
    if ($stmt->fetch()) {
        echo "✅ La tabla 'employee_attendance' ya existe\n";
        
        // Mostrar estructura actual
        echo "\n📊 Estructura actual:\n";
        $stmt = $db->query("DESCRIBE employee_attendance");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
        }
        exit();
    }
    
    echo "📋 Creando tabla 'employee_attendance'... ";
    
    $sql = "CREATE TABLE `employee_attendance` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `company_id` int(11) NOT NULL,
        `business_id` int(11) NOT NULL,
        `attendance_date` date NOT NULL,
        `status` enum('presente','ausente','tardanza','permiso','vacaciones','incapacidad') NOT NULL DEFAULT 'ausente',
        `check_in_time` time DEFAULT NULL,
        `check_out_time` time DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_employee_date` (`employee_id`, `attendance_date`),
        KEY `idx_company` (`company_id`),
        KEY `idx_business` (`business_id`),
        KEY `idx_date` (`attendance_date`),
        KEY `idx_status` (`status`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✅\n";
    
    // Agregar algunos registros de prueba para hoy
    echo "\n📝 Agregando registros de prueba para hoy... ";
    
    // Obtener empleados activos de la primera empresa
    $stmt = $db->query("SELECT id, company_id, business_id FROM employees WHERE status = 'Activo' LIMIT 5");
    $employees = $stmt->fetchAll();
    
    if (!empty($employees)) {
        $today = date('Y-m-d');
        
        foreach ($employees as $index => $employee) {
            $status = ['presente', 'presente', 'tardanza', 'presente', 'ausente'][$index % 5];
            $check_in = ($status === 'ausente') ? null : 
                       (($status === 'tardanza') ? '09:15:00' : '08:00:00');
            $check_out = ($status === 'ausente') ? null : '17:00:00';
            $notes = ($status === 'tardanza') ? 'Llegó tarde por tráfico' : 
                    (($status === 'ausente') ? 'Sin justificación' : null);
            
            $insertSql = "INSERT INTO employee_attendance 
                         (employee_id, company_id, business_id, attendance_date, status, check_in_time, check_out_time, notes) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                $employee['id'],
                $employee['company_id'],
                $employee['business_id'],
                $today,
                $status,
                $check_in,
                $check_out,
                $notes
            ]);
        }
        echo "✅ (Agregados " . count($employees) . " registros)\n";
    } else {
        echo "⚠️  No se encontraron empleados activos\n";
    }
    
    // Agregar permiso de asistencia
    echo "\n🔐 Agregando permiso de asistencia... ";
    
    $permissionSql = "INSERT IGNORE INTO permissions (key_name, description, module) 
                     VALUES ('employees.attendance', 'Gestionar asistencia y pase de lista', 'human-resources')";
    $db->exec($permissionSql);
    echo "✅\n";
    
    echo "\n📊 RESUMEN:\n";
    echo "==========\n";
    echo "✅ Tabla 'employee_attendance' creada exitosamente\n";
    echo "✅ Campos: id, employee_id, company_id, business_id, attendance_date, status, check_in_time, check_out_time, notes, created_by, timestamps\n";
    echo "✅ Estados disponibles: presente, ausente, tardanza, permiso, vacaciones, incapacidad\n";
    echo "✅ Índices optimizados para consultas rápidas\n";
    echo "✅ Restricción única por empleado/fecha\n";
    echo "✅ Registros de prueba agregados\n";
    echo "✅ Permiso 'employees.attendance' agregado\n\n";
    
    // Mostrar estructura final
    echo "📋 Estructura final de la tabla:\n";
    $stmt = $db->query("DESCRIBE employee_attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    
    echo "\n🚀 ¡Tabla de asistencia lista para usar!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📝 Detalles: " . $e->getTraceAsString() . "\n";
}
?>
