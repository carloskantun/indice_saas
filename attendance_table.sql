-- =====================================================
-- TABLA DE ASISTENCIA PARA MÓDULO DE RECURSOS HUMANOS
-- Sistema SaaS Indice
-- =====================================================

-- Crear tabla de asistencia
CREATE TABLE IF NOT EXISTS `employee_attendance` (
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
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar permiso de asistencia
INSERT IGNORE INTO `permissions` (`key_name`, `description`, `module`) 
VALUES ('employees.attendance', 'Gestionar asistencia y pase de lista', 'human-resources');

-- Datos de prueba (opcional)
-- Insertar asistencia para empleados activos del día actual
INSERT IGNORE INTO `employee_attendance` 
(employee_id, company_id, business_id, attendance_date, status, check_in_time, notes)
SELECT 
    e.id,
    e.company_id,
    e.business_id,
    CURDATE(),
    CASE 
        WHEN MOD(e.id, 4) = 0 THEN 'presente'
        WHEN MOD(e.id, 4) = 1 THEN 'tardanza'
        WHEN MOD(e.id, 4) = 2 THEN 'ausente'
        ELSE 'presente'
    END as status,
    CASE 
        WHEN MOD(e.id, 4) = 0 THEN '08:00:00'
        WHEN MOD(e.id, 4) = 1 THEN '09:15:00'
        WHEN MOD(e.id, 4) = 2 THEN NULL
        ELSE '08:30:00'
    END as check_in_time,
    CASE 
        WHEN MOD(e.id, 4) = 1 THEN 'Llegó tarde por tráfico'
        WHEN MOD(e.id, 4) = 2 THEN 'Sin justificación'
        ELSE NULL
    END as notes
FROM employees e 
WHERE e.status = 'Activo'
LIMIT 10;

-- Verificar que la tabla se creó correctamente
SELECT 'Tabla employee_attendance creada:' as info;
SELECT COUNT(*) as total_records FROM employee_attendance;

SELECT 'Permiso agregado:' as info;
SELECT * FROM permissions WHERE key_name = 'employees.attendance';
