-- ============================================================================
-- MÓDULO PROCESOS Y TAREAS - SCRIPT DE INSTALACIÓN
-- Sistema SaaS Indice - Versión 1.0.0
-- ============================================================================

-- Verificar que las tablas base existan
-- (departments, employees deben existir del módulo de recursos humanos)

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
    `assigned_to` INT DEFAULT NULL COMMENT 'Empleado asignado (employee_id)',
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
    `assigned_to` INT NOT NULL COMMENT 'Empleado asignado (employee_id)',
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
-- VISTAS ÚTILES PARA REPORTES
-- ============================================================================

-- Vista de tareas con información completa
-- NOTA: Ajustada para compatibilidad con estructura existente del sistema
CREATE OR REPLACE VIEW `v_tasks_complete` AS
SELECT 
    t.*,
    p.name as process_name,
    p.status as process_status,
    -- Verificar si existe tabla departments en el sistema
    COALESCE(d.name, 'Sin Departamento') as department_name,
    -- Usar empleados del módulo de recursos humanos si existe
    CASE 
        WHEN e.employee_id IS NOT NULL THEN CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, ''))
        ELSE 'No Asignado'
    END as assigned_name,
    COALESCE(e.email, '') as assigned_email,
    -- Usuario que asignó desde tabla users
    CASE 
        WHEN u.user_id IS NOT NULL THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
        ELSE 'Sistema'
    END as assigned_by_name,
    -- Nombre de la empresa
    COALESCE(c.name, 'Sistema') as company_name,
    -- Estado de vencimiento
    CASE 
        WHEN t.due_date < NOW() AND t.status NOT IN ('completed', 'cancelled') THEN 'overdue'
        WHEN DATE(t.due_date) = CURDATE() THEN 'due_today'
        WHEN t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'due_soon'
        ELSE 'normal'
    END as due_status,
    -- Tiempo de completado
    CASE
        WHEN t.status = 'completed' AND t.due_date IS NOT NULL THEN
            CASE WHEN t.completed_at <= t.due_date THEN 'on_time' ELSE 'late' END
        ELSE NULL
    END as completion_timing
FROM tasks t
LEFT JOIN processes p ON t.process_id = p.process_id
-- Join condicional con departments si existe
LEFT JOIN departments d ON t.department_id = d.department_id
-- Join condicional con employees si existe la tabla del módulo HR
LEFT JOIN employees e ON t.assigned_to = e.employee_id
-- Join con users del sistema principal
LEFT JOIN users u ON t.assigned_by = u.user_id
-- Join con companies del sistema multi-tenant
LEFT JOIN companies c ON t.company_id = c.company_id;

-- Vista de procesos con estadísticas
-- NOTA: Ajustada para compatibilidad con estructura existente
CREATE OR REPLACE VIEW `v_processes_stats` AS
SELECT 
    p.*,
    -- Departamento si existe
    COALESCE(d.name, 'Sin Departamento') as department_name,
    -- Usuario creador
    CASE 
        WHEN u.user_id IS NOT NULL THEN CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
        ELSE 'Sistema'
    END as creator_name,
    -- Empresa
    COALESCE(c.name, 'Sistema') as company_name,
    -- Estadísticas de tareas
    COALESCE(task_stats.total_tasks, 0) as total_tasks,
    COALESCE(task_stats.completed_tasks, 0) as completed_tasks,
    COALESCE(task_stats.pending_tasks, 0) as pending_tasks,
    COALESCE(task_stats.overdue_tasks, 0) as overdue_tasks,
    -- Porcentaje de completado
    CASE 
        WHEN COALESCE(task_stats.total_tasks, 0) > 0 THEN
            ROUND((COALESCE(task_stats.completed_tasks, 0) * 100.0) / task_stats.total_tasks, 1)
        ELSE 0
    END as completion_percentage
FROM processes p
LEFT JOIN departments d ON p.department_id = d.department_id
LEFT JOIN users u ON p.created_by = u.user_id
LEFT JOIN companies c ON p.company_id = c.company_id
LEFT JOIN (
    SELECT 
        process_id,
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status IN ('pending', 'in_progress', 'review') THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN due_date < NOW() AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks
    GROUP BY process_id
) task_stats ON p.process_id = task_stats.process_id;

-- ============================================================================
-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- ============================================================================

-- Trigger para registrar cambios en tareas
DELIMITER $$
CREATE TRIGGER `tr_task_history_update` 
AFTER UPDATE ON `tasks`
FOR EACH ROW
BEGIN
    -- Registrar cambio de estado
    IF OLD.status != NEW.status THEN
        INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
        VALUES (NEW.task_id, 'status', OLD.status, NEW.status, NEW.assigned_by, 'Cambio automático de estado');
    END IF;
    
    -- Registrar cambio de progreso significativo (>= 10%)
    IF ABS(OLD.completion_percentage - NEW.completion_percentage) >= 10 THEN
        INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
        VALUES (NEW.task_id, 'completion_percentage', OLD.completion_percentage, NEW.completion_percentage, NEW.assigned_by, 'Actualización de progreso');
    END IF;
    
    -- Registrar cambio de asignación
    IF OLD.assigned_to != NEW.assigned_to THEN
        INSERT INTO task_history (task_id, field_changed, old_value, new_value, changed_by, notes)
        VALUES (NEW.task_id, 'assigned_to', OLD.assigned_to, NEW.assigned_to, NEW.assigned_by, 'Reasignación de tarea');
        
        -- Actualizar tabla de asignaciones
        UPDATE task_assignments SET is_current = FALSE WHERE task_id = NEW.task_id;
        INSERT INTO task_assignments (task_id, assigned_to, assigned_by, reason, is_current)
        VALUES (NEW.task_id, NEW.assigned_to, NEW.assigned_by, 'Reasignación', TRUE);
    END IF;
END$$
DELIMITER ;

-- Trigger para actualizar fecha de completado
DELIMITER $$
CREATE TRIGGER `tr_task_completion_date`
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
CREATE INDEX `idx_tasks_company_status` ON `tasks` (`company_id`, `status`);
CREATE INDEX `idx_tasks_assigned_status` ON `tasks` (`assigned_to`, `status`);
CREATE INDEX `idx_tasks_due_date_status` ON `tasks` (`due_date`, `status`);
CREATE INDEX `idx_processes_company_status` ON `processes` (`company_id`, `status`);

-- Índices para ordenamiento frecuente
CREATE INDEX `idx_tasks_created_desc` ON `tasks` (`created_at` DESC);
CREATE INDEX `idx_tasks_due_asc` ON `tasks` (`due_date` ASC);
CREATE INDEX `idx_processes_created_desc` ON `processes` (`created_at` DESC);

-- ============================================================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- ============================================================================

-- Agregar comentarios a las tablas para documentación
ALTER TABLE `processes` COMMENT = 'Definición de procesos operativos del negocio';
ALTER TABLE `process_steps` COMMENT = 'Etapas que componen cada proceso';
ALTER TABLE `tasks` COMMENT = 'Tareas individuales, pueden ser parte de un proceso o independientes';
ALTER TABLE `task_assignments` COMMENT = 'Historial completo de asignaciones de tareas';
ALTER TABLE `process_instances` COMMENT = 'Instancias ejecutándose de procesos (workflow instances)';
ALTER TABLE `task_history` COMMENT = 'Auditoría completa de cambios en tareas';
ALTER TABLE `workflow_templates` COMMENT = 'Plantillas reutilizables de procesos y flujos';
ALTER TABLE `task_comments` COMMENT = 'Sistema de comentarios colaborativo en tareas';
ALTER TABLE `task_attachments` COMMENT = 'Archivos adjuntos y documentación de tareas';
ALTER TABLE `process_automation_rules` COMMENT = 'Reglas de automatización y triggers de negocio';

-- ============================================================================
-- VERIFICACIÓN DE INSTALACIÓN
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

-- Mensaje de confirmación
SELECT 'Módulo Procesos y Tareas instalado correctamente' as Status,
       NOW() as Installed_At,
       '1.0.0' as Version;

-- ============================================================================
-- FIN DEL SCRIPT DE INSTALACIÓN
-- ============================================================================
