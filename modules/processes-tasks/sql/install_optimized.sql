-- ============================================================================
-- MÓDULO PROCESOS Y TAREAS - SCRIPT DE INSTALACIÓN OPTIMIZADO
-- Sistema SaaS Indice - Compatible con estructura existente
-- ============================================================================

-- ============================================================================
-- VERIFICACIÓN DE PREREQUISITOS
-- ============================================================================

-- Verificar que existen las tablas necesarias del sistema principal
SELECT 'Verificando prerequisitos del sistema...' as Status;

-- Verificar tabla users
SELECT CASE 
    WHEN COUNT(*) > 0 THEN 'OK - Tabla users encontrada'
    ELSE 'ERROR - Tabla users no encontrada'
END as users_check
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users';

-- Verificar tabla companies  
SELECT CASE 
    WHEN COUNT(*) > 0 THEN 'OK - Tabla companies encontrada'
    ELSE 'ERROR - Tabla companies no encontrada'
END as companies_check
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies';

-- ============================================================================
-- TABLA PRINCIPAL DE PROCESOS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `processes` (
    `process_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre del proceso',
    `description` TEXT COMMENT 'Descripción detallada del proceso',
    `department_id` INT DEFAULT NULL COMMENT 'Departamento propietario',
    `status` ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft' COMMENT 'Estado del proceso',
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium' COMMENT 'Prioridad del proceso',
    `estimated_duration` INT DEFAULT NULL COMMENT 'Duración estimada en horas',
    `created_by` INT NOT NULL COMMENT 'Usuario que creó el proceso',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `company_id` INT NOT NULL COMMENT 'Empresa (multi-tenant)',
    
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_department_id` (`department_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Definición de procesos operativos';

-- ============================================================================
-- ETAPAS DE CADA PROCESO
-- ============================================================================
CREATE TABLE IF NOT EXISTS `process_steps` (
    `step_id` INT PRIMARY KEY AUTO_INCREMENT,
    `process_id` INT NOT NULL COMMENT 'Proceso al que pertenece',
    `step_name` VARCHAR(255) NOT NULL COMMENT 'Nombre de la etapa',
    `step_description` TEXT COMMENT 'Descripción de la etapa',
    `step_order` INT NOT NULL COMMENT 'Orden de la etapa en el proceso',
    `estimated_hours` INT DEFAULT NULL COMMENT 'Horas estimadas para esta etapa',
    `responsible_role` VARCHAR(50) DEFAULT NULL COMMENT 'Rol responsable de la etapa',
    `required` BOOLEAN DEFAULT TRUE COMMENT 'Si la etapa es obligatoria',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`process_id`) REFERENCES `processes`(`process_id`) ON DELETE CASCADE,
    INDEX `idx_process_id` (`process_id`),
    INDEX `idx_step_order` (`step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Etapas de cada proceso';

-- ============================================================================
-- TABLA PRINCIPAL DE TAREAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tasks` (
    `task_id` INT PRIMARY KEY AUTO_INCREMENT,
    `process_id` INT DEFAULT NULL COMMENT 'Proceso relacionado (puede ser independiente)',
    `title` VARCHAR(255) NOT NULL COMMENT 'Título de la tarea',
    `description` TEXT COMMENT 'Descripción detallada',
    `assigned_to` INT DEFAULT NULL COMMENT 'Empleado asignado (employee_id o user_id)',
    `assigned_by` INT DEFAULT NULL COMMENT 'Usuario que asignó (user_id)',
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium' COMMENT 'Prioridad de la tarea',
    `status` ENUM('pending', 'in_progress', 'review', 'completed', 'cancelled') DEFAULT 'pending' COMMENT 'Estado de la tarea',
    `due_date` DATETIME DEFAULT NULL COMMENT 'Fecha límite',
    `estimated_hours` DECIMAL(5,2) DEFAULT NULL COMMENT 'Horas estimadas',
    `actual_hours` DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas reales trabajadas',
    `completion_percentage` INT DEFAULT 0 COMMENT 'Porcentaje de completado (0-100)',
    `department_id` INT DEFAULT NULL COMMENT 'Departamento responsable',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL COMMENT 'Fecha de completado',
    `company_id` INT NOT NULL COMMENT 'Empresa (multi-tenant)',
    
    FOREIGN KEY (`process_id`) REFERENCES `processes`(`process_id`) ON DELETE SET NULL,
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_process_id` (`process_id`),
    INDEX `idx_assigned_to` (`assigned_to`),
    INDEX `idx_assigned_by` (`assigned_by`),
    INDEX `idx_department_id` (`department_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_due_date` (`due_date`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_completion_percentage` (`completion_percentage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas individuales';

-- ============================================================================
-- HISTORIAL DE ASIGNACIONES DE TAREAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `task_assignments` (
    `assignment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_id` INT NOT NULL COMMENT 'Tarea asignada',
    `assigned_to` INT NOT NULL COMMENT 'Empleado asignado (employee_id o user_id)',
    `assigned_by` INT NOT NULL COMMENT 'Usuario que asignó (user_id)',
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reason` TEXT COMMENT 'Motivo de la asignación',
    `is_current` BOOLEAN DEFAULT TRUE COMMENT 'Si es la asignación actual',
    
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
    INDEX `idx_task_id` (`task_id`),
    INDEX `idx_assigned_to` (`assigned_to`),
    INDEX `idx_assigned_by` (`assigned_by`),
    INDEX `idx_is_current` (`is_current`),
    INDEX `idx_assigned_at` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de asignaciones de tareas';

-- ============================================================================
-- INSTANCIAS DE PROCESOS EJECUTÁNDOSE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `process_instances` (
    `instance_id` INT PRIMARY KEY AUTO_INCREMENT,
    `process_id` INT NOT NULL COMMENT 'Proceso base',
    `instance_name` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre de la instancia',
    `started_by` INT NOT NULL COMMENT 'Usuario que inició',
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expected_completion` DATETIME DEFAULT NULL COMMENT 'Fecha esperada de completado',
    `actual_completion` DATETIME DEFAULT NULL COMMENT 'Fecha real de completado',
    `status` ENUM('running', 'paused', 'completed', 'cancelled') DEFAULT 'running' COMMENT 'Estado de la instancia',
    `completion_percentage` INT DEFAULT 0 COMMENT 'Progreso general (0-100)',
    `company_id` INT NOT NULL COMMENT 'Empresa (multi-tenant)',
    
    FOREIGN KEY (`process_id`) REFERENCES `processes`(`process_id`) ON DELETE CASCADE,
    INDEX `idx_process_id` (`process_id`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_started_by` (`started_by`),
    INDEX `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Instancias ejecutándose de procesos';

-- ============================================================================
-- HISTORIAL DE CAMBIOS EN TAREAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `task_history` (
    `history_id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_id` INT NOT NULL COMMENT 'Tarea modificada',
    `field_changed` VARCHAR(50) NOT NULL COMMENT 'Campo que cambió',
    `old_value` TEXT COMMENT 'Valor anterior',
    `new_value` TEXT COMMENT 'Valor nuevo',
    `changed_by` INT NOT NULL COMMENT 'Usuario que hizo el cambio',
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT COMMENT 'Notas adicionales sobre el cambio',
    
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
    INDEX `idx_task_id` (`task_id`),
    INDEX `idx_changed_by` (`changed_by`),
    INDEX `idx_changed_at` (`changed_at`),
    INDEX `idx_field_changed` (`field_changed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de cambios en tareas';

-- ============================================================================
-- PLANTILLAS DE FLUJOS DE TRABAJO
-- ============================================================================
CREATE TABLE IF NOT EXISTS `workflow_templates` (
    `template_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre de la plantilla',
    `description` TEXT COMMENT 'Descripción de la plantilla',
    `category` VARCHAR(100) DEFAULT NULL COMMENT 'Categoría de la plantilla',
    `department_id` INT DEFAULT NULL COMMENT 'Departamento específico (NULL = global)',
    `template_data` JSON COMMENT 'Estructura del flujo en JSON',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Si la plantilla está activa',
    `created_by` INT NOT NULL COMMENT 'Usuario creador',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `company_id` INT NOT NULL COMMENT 'Empresa (multi-tenant)',
    
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_department_id` (`department_id`),
    INDEX `idx_category` (`category`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Plantillas de flujos de trabajo';

-- ============================================================================
-- COMENTARIOS EN TAREAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `task_comments` (
    `comment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_id` INT NOT NULL COMMENT 'Tarea comentada',
    `user_id` INT NOT NULL COMMENT 'Usuario que comentó',
    `comment` TEXT NOT NULL COMMENT 'Contenido del comentario',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_internal` BOOLEAN DEFAULT FALSE COMMENT 'Si es solo visible para el equipo',
    
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
    INDEX `idx_task_id` (`task_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_is_internal` (`is_internal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comentarios en tareas';

-- ============================================================================
-- ARCHIVOS ADJUNTOS EN TAREAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `task_attachments` (
    `attachment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_id` INT NOT NULL COMMENT 'Tarea con archivo',
    `filename` VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo en servidor',
    `original_name` VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    `file_size` INT DEFAULT NULL COMMENT 'Tamaño en bytes',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT 'Tipo MIME del archivo',
    `uploaded_by` INT NOT NULL COMMENT 'Usuario que subió el archivo',
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE,
    INDEX `idx_task_id` (`task_id`),
    INDEX `idx_uploaded_by` (`uploaded_by`),
    INDEX `idx_uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archivos adjuntos en tareas';

-- ============================================================================
-- CONFIGURACIÓN DE AUTOMATIZACIÓN
-- ============================================================================
CREATE TABLE IF NOT EXISTS `process_automation_rules` (
    `rule_id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre de la regla',
    `description` TEXT COMMENT 'Descripción de la regla',
    `trigger_event` VARCHAR(100) NOT NULL COMMENT 'Evento que dispara la regla',
    `conditions` JSON COMMENT 'Condiciones para ejecutar la regla',
    `actions` JSON COMMENT 'Acciones a ejecutar',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Si la regla está activa',
    `created_by` INT NOT NULL COMMENT 'Usuario creador',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `company_id` INT NOT NULL COMMENT 'Empresa (multi-tenant)',
    
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_trigger_event` (`trigger_event`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reglas de automatización de procesos';

-- ============================================================================
-- VISTAS DINÁMICAS SEGÚN ESTRUCTURA EXISTENTE
-- ============================================================================

-- Vista básica de tareas (funciona sin dependencias externas)
CREATE OR REPLACE VIEW `v_tasks_basic` AS
SELECT 
    t.*,
    p.name as process_name,
    p.status as process_status,
    u.first_name as assigned_by_name,
    c.name as company_name,
    CASE 
        WHEN t.due_date < NOW() AND t.status NOT IN ('completed', 'cancelled') THEN 'overdue'
        WHEN DATE(t.due_date) = CURDATE() THEN 'due_today'
        WHEN t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'due_soon'
        ELSE 'normal'
    END as due_status
FROM tasks t
LEFT JOIN processes p ON t.process_id = p.process_id
LEFT JOIN users u ON t.assigned_by = u.user_id
LEFT JOIN companies c ON t.company_id = c.company_id;

-- Vista básica de procesos (sin dependencias externas)
CREATE OR REPLACE VIEW `v_processes_basic` AS
SELECT 
    p.*,
    u.first_name as creator_name,
    c.name as company_name,
    COUNT(t.task_id) as total_tasks,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
FROM processes p
LEFT JOIN users u ON p.created_by = u.user_id
LEFT JOIN companies c ON p.company_id = c.company_id
LEFT JOIN tasks t ON p.process_id = t.process_id
GROUP BY p.process_id;

-- ============================================================================
-- CREACIÓN CONDICIONAL DE VISTAS AVANZADAS
-- ============================================================================

-- Verificar si existe tabla departments para crear vistas avanzadas
SET @departments_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments'
);

-- Verificar si existe tabla employees para crear vistas avanzadas  
SET @employees_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees'
);

-- Solo crear vistas completas si existen las tablas necesarias
SET @sql_vista_completa = CASE 
    WHEN @departments_exists > 0 AND @employees_exists > 0 THEN
        'CREATE OR REPLACE VIEW v_tasks_complete AS
         SELECT 
             t.*,
             p.name as process_name,
             p.status as process_status,
             d.name as department_name,
             CONCAT(COALESCE(e.first_name, \"\"), \" \", COALESCE(e.last_name, \"\")) as assigned_name,
             e.email as assigned_email,
             CONCAT(COALESCE(u.first_name, \"\"), \" \", COALESCE(u.last_name, \"\")) as assigned_by_name,
             c.name as company_name,
             CASE 
                 WHEN t.due_date < NOW() AND t.status NOT IN (\"completed\", \"cancelled\") THEN \"overdue\"
                 WHEN DATE(t.due_date) = CURDATE() THEN \"due_today\"
                 WHEN t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) THEN \"due_soon\"
                 ELSE \"normal\"
             END as due_status,
             CASE
                 WHEN t.status = \"completed\" AND t.due_date IS NOT NULL THEN
                     CASE WHEN t.completed_at <= t.due_date THEN \"on_time\" ELSE \"late\" END
                 ELSE NULL
             END as completion_timing
         FROM tasks t
         LEFT JOIN processes p ON t.process_id = p.process_id
         LEFT JOIN departments d ON t.department_id = d.department_id
         LEFT JOIN employees e ON t.assigned_to = e.employee_id
         LEFT JOIN users u ON t.assigned_by = u.user_id
         LEFT JOIN companies c ON t.company_id = c.company_id'
    ELSE 
        'SELECT "Vista completa no creada - faltan tablas departments o employees" as info'
END;

-- Ejecutar la creación de vista si es posible
PREPARE stmt FROM @sql_vista_completa;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- ============================================================================

-- Trigger para registrar cambios en tareas
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_task_history_update` 
AFTER UPDATE ON `tasks`
FOR EACH ROW
BEGIN
    -- Registrar cambio de estado
    IF OLD.status != NEW.status THEN
        INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
        VALUES (NEW.task_id, 'status', OLD.status, NEW.status, COALESCE(NEW.assigned_by, 1), 'Cambio automático de estado');
    END IF;
    
    -- Registrar cambio de progreso significativo (>= 10%)
    IF ABS(OLD.completion_percentage - NEW.completion_percentage) >= 10 THEN
        INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
        VALUES (NEW.task_id, 'completion_percentage', OLD.completion_percentage, NEW.completion_percentage, COALESCE(NEW.assigned_by, 1), 'Actualización de progreso');
    END IF;
    
    -- Registrar cambio de asignación
    IF COALESCE(OLD.assigned_to, 0) != COALESCE(NEW.assigned_to, 0) THEN
        INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
        VALUES (NEW.task_id, 'assigned_to', OLD.assigned_to, NEW.assigned_to, COALESCE(NEW.assigned_by, 1), 'Reasignación de tarea');
        
        -- Actualizar tabla de asignaciones
        UPDATE task_assignments SET is_current = FALSE WHERE task_id = NEW.task_id AND is_current = TRUE;
        
        IF NEW.assigned_to IS NOT NULL THEN
            INSERT INTO task_assignments (task_id, assigned_to, assigned_by, reason, is_current)
            VALUES (NEW.task_id, NEW.assigned_to, COALESCE(NEW.assigned_by, 1), 'Reasignación automática', TRUE);
        END IF;
    END IF;
END$$
DELIMITER ;

-- Trigger para actualizar fecha de completado
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_task_completion_date`
BEFORE UPDATE ON `tasks`
FOR EACH ROW
BEGIN
    -- Si se marca como completada, establecer fecha de completado
    IF OLD.status != 'completed' AND NEW.status = 'completed' THEN
        SET NEW.completed_at = NOW();
        SET NEW.completion_percentage = 100;
    END IF;
    
    -- Si se desmarca como completada, limpiar fecha de completado
    IF OLD.status = 'completed' AND NEW.status != 'completed' THEN
        SET NEW.completed_at = NULL;
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX IF NOT EXISTS `idx_tasks_company_status` ON `tasks` (`company_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_tasks_assigned_status` ON `tasks` (`assigned_to`, `status`);
CREATE INDEX IF NOT EXISTS `idx_tasks_due_date_status` ON `tasks` (`due_date`, `status`);
CREATE INDEX IF NOT EXISTS `idx_processes_company_status` ON `processes` (`company_id`, `status`);

-- Índices para ordenamiento frecuente
CREATE INDEX IF NOT EXISTS `idx_tasks_created_desc` ON `tasks` (`created_at` DESC);
CREATE INDEX IF NOT EXISTS `idx_tasks_due_asc` ON `tasks` (`due_date` ASC);
CREATE INDEX IF NOT EXISTS `idx_processes_created_desc` ON `processes` (`created_at` DESC);

-- ============================================================================
-- VERIFICACIÓN FINAL DE INSTALACIÓN
-- ============================================================================

-- Verificar que todas las tablas se crearon correctamente
SELECT 
    TABLE_NAME,
    TABLE_COMMENT,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN (
    'processes', 'process_steps', 'tasks', 'task_assignments', 
    'process_instances', 'task_history', 'workflow_templates',
    'task_comments', 'task_attachments', 'process_automation_rules'
)
ORDER BY TABLE_NAME;

-- Verificar vistas creadas
SELECT 
    TABLE_NAME,
    TABLE_TYPE
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'v_%'
AND TABLE_NAME IN ('v_tasks_basic', 'v_processes_basic', 'v_tasks_complete')
ORDER BY TABLE_NAME;

-- Verificar triggers
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
AND TRIGGER_NAME IN ('tr_task_history_update', 'tr_task_completion_date');

-- Mensaje de confirmación final
SELECT 
    'Módulo Procesos y Tareas instalado correctamente' as Status,
    NOW() as Installed_At,
    '1.0.0' as Version,
    DATABASE() as Database_Name,
    CASE 
        WHEN @departments_exists > 0 AND @employees_exists > 0 THEN 'Vistas completas creadas'
        ELSE 'Solo vistas básicas - instalar módulo HR para vistas completas'
    END as Vista_Status;

-- ============================================================================
-- FIN DEL SCRIPT DE INSTALACIÓN OPTIMIZADO
-- ============================================================================
