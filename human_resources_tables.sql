-- =====================================================
-- SCRIPT SQL PARA MÓDULO HUMAN RESOURCES
-- Sistema SaaS Indice
-- =====================================================

-- =====================================================
-- SECCIÓN 1: CREAR TABLA DEPARTMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `departments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 2: CREAR TABLA POSITIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS `positions` (
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
    KEY `idx_status` (`status`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 3: CREAR TABLA EMPLOYEES
-- =====================================================
CREATE TABLE IF NOT EXISTS `employees` (
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
    KEY `idx_hire_date` (`hire_date`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 4: TRIGGER PARA NÚMERO DE EMPLEADO AUTOMÁTICO
-- =====================================================
DELIMITER $$

DROP TRIGGER IF EXISTS `generate_employee_number`$$
CREATE TRIGGER `generate_employee_number` 
BEFORE INSERT ON `employees` 
FOR EACH ROW 
BEGIN
    IF NEW.employee_number IS NULL OR NEW.employee_number = '' THEN
        DECLARE next_number INT DEFAULT 1;
        DECLARE new_employee_number VARCHAR(50);
        
        -- Buscar el último número de empleado para la empresa
        SELECT COALESCE(MAX(CAST(SUBSTRING(employee_number, 4) AS UNSIGNED)), 0) + 1 
        INTO next_number
        FROM employees 
        WHERE company_id = NEW.company_id 
        AND employee_number REGEXP '^EMP[0-9]+$';
        
        -- Generar nuevo número con formato EMP0001, EMP0002, etc.
        SET new_employee_number = CONCAT('EMP', LPAD(next_number, 4, '0'));
        
        -- Verificar que no exista (por seguridad)
        WHILE EXISTS(SELECT 1 FROM employees WHERE employee_number = new_employee_number AND company_id = NEW.company_id) DO
            SET next_number = next_number + 1;
            SET new_employee_number = CONCAT('EMP', LPAD(next_number, 4, '0'));
        END WHILE;
        
        SET NEW.employee_number = new_employee_number;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- SECCIÓN 5: DATOS DE PRUEBA PARA DEPARTAMENTOS
-- =====================================================
-- Nota: Estos datos se insertarán solo si no existe ningún departamento
-- Se debe ajustar company_id y business_id según el contexto

INSERT IGNORE INTO `departments` (`company_id`, `business_id`, `name`, `description`, `created_by`) VALUES
(1, 1, 'Recursos Humanos', 'Gestión de personal y políticas laborales', 1),
(1, 1, 'Tecnología', 'Desarrollo de software y soporte técnico', 1),
(1, 1, 'Ventas', 'Gestión comercial y atención a clientes', 1),
(1, 1, 'Administración', 'Gestión administrativa y financiera', 1),
(1, 1, 'Marketing', 'Promoción y estrategias de mercadeo', 1);

-- =====================================================
-- SECCIÓN 6: DATOS DE PRUEBA PARA POSICIONES
-- =====================================================
INSERT IGNORE INTO `positions` (`company_id`, `business_id`, `department_id`, `title`, `description`, `min_salary`, `max_salary`, `created_by`) VALUES
-- Recursos Humanos
(1, 1, 1, 'Gerente de RRHH', 'Responsable de la gestión integral de recursos humanos', 25000.00, 35000.00, 1),
(1, 1, 1, 'Especialista en RRHH', 'Apoyo en procesos de reclutamiento y capacitación', 15000.00, 22000.00, 1),

-- Tecnología  
(1, 1, 2, 'Desarrollador Senior', 'Desarrollo de aplicaciones y sistemas', 30000.00, 45000.00, 1),
(1, 1, 2, 'Desarrollador Junior', 'Apoyo en desarrollo y mantenimiento de código', 18000.00, 25000.00, 1),
(1, 1, 2, 'DevOps Engineer', 'Gestión de infraestructura y despliegues', 28000.00, 40000.00, 1),

-- Ventas
(1, 1, 3, 'Gerente de Ventas', 'Responsable del equipo comercial', 22000.00, 32000.00, 1),
(1, 1, 3, 'Ejecutivo de Ventas', 'Gestión de clientes y cierre de ventas', 12000.00, 20000.00, 1),

-- Administración
(1, 1, 4, 'Contador', 'Gestión contable y fiscal', 20000.00, 28000.00, 1),
(1, 1, 4, 'Asistente Administrativo', 'Apoyo en tareas administrativas', 10000.00, 15000.00, 1),

-- Marketing
(1, 1, 5, 'Especialista en Marketing', 'Estrategias de marketing digital', 16000.00, 24000.00, 1);

-- =====================================================
-- SECCIÓN 7: PERMISOS DEL MÓDULO
-- =====================================================
-- Nota: Este script se maneja desde add_human_resources_module.php
-- pero se incluye aquí para referencia

/*
INSERT IGNORE INTO `permissions` (`key`, `description`, `module`) VALUES
('employees.view', 'Ver empleados', 'human-resources'),
('employees.create', 'Crear empleados', 'human-resources'),
('employees.edit', 'Editar empleados', 'human-resources'),
('employees.delete', 'Eliminar empleados', 'human-resources'),
('employees.export', 'Exportar datos de empleados', 'human-resources'),
('employees.kpis', 'Ver estadísticas de empleados', 'human-resources'),
('departments.view', 'Ver departamentos', 'human-resources'),
('departments.create', 'Crear departamentos', 'human-resources'),
('departments.edit', 'Editar departamentos', 'human-resources'),
('departments.delete', 'Eliminar departamentos', 'human-resources'),
('positions.view', 'Ver posiciones', 'human-resources'),
('positions.create', 'Crear posiciones', 'human-resources'),
('positions.edit', 'Editar posiciones', 'human-resources'),
('positions.delete', 'Eliminar posiciones', 'human-resources');
*/

-- =====================================================
-- SECCIÓN 8: VERIFICACIÓN FINAL
-- =====================================================
SELECT 'TABLAS CREADAS:' as info;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('departments', 'positions', 'employees');

SELECT 'TRIGGERS CREADOS:' as info;
SHOW TRIGGERS LIKE 'employees';

SELECT 'DEPARTAMENTOS DE PRUEBA:' as info;
SELECT COUNT(*) as total_departments FROM departments;

SELECT 'POSICIONES DE PRUEBA:' as info;
SELECT COUNT(*) as total_positions FROM positions;

-- =====================================================
-- RESULTADO ESPERADO:
-- - 3 tablas creadas: departments, positions, employees
-- - 1 trigger activo: generate_employee_number
-- - 5 departamentos de prueba
-- - 10 posiciones de prueba
-- - Sistema listo para crear empleados
-- =====================================================
