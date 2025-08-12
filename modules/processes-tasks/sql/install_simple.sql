-- ============================================================================
-- MÓDULO PROCESOS Y TAREAS - INSTALACIÓN SIMPLE
-- Para ejecutar directamente en phpMyAdmin
-- ============================================================================

-- Crear tabla de procesos
CREATE TABLE IF NOT EXISTS processes (
    process_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT DEFAULT NULL,
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    estimated_duration INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_id INT NOT NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de etapas de procesos
CREATE TABLE IF NOT EXISTS process_steps (
    step_id INT PRIMARY KEY AUTO_INCREMENT,
    process_id INT NOT NULL,
    step_name VARCHAR(255) NOT NULL,
    step_description TEXT,
    step_order INT NOT NULL,
    estimated_hours INT DEFAULT NULL,
    responsible_role VARCHAR(50) DEFAULT NULL,
    required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE CASCADE,
    INDEX idx_process_id (process_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de tareas
CREATE TABLE IF NOT EXISTS tasks (
    task_id INT PRIMARY KEY AUTO_INCREMENT,
    process_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT DEFAULT NULL,
    assigned_by INT DEFAULT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'review', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATETIME DEFAULT NULL,
    estimated_hours DECIMAL(5,2) DEFAULT NULL,
    actual_hours DECIMAL(5,2) DEFAULT 0,
    completion_percentage INT DEFAULT 0,
    department_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    company_id INT NOT NULL,
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE SET NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de asignaciones de tareas
CREATE TABLE IF NOT EXISTS task_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    is_current BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de instancias de procesos
CREATE TABLE IF NOT EXISTS process_instances (
    instance_id INT PRIMARY KEY AUTO_INCREMENT,
    process_id INT NOT NULL,
    instance_name VARCHAR(255) DEFAULT NULL,
    started_by INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_completion DATETIME DEFAULT NULL,
    actual_completion DATETIME DEFAULT NULL,
    status ENUM('running', 'paused', 'completed', 'cancelled') DEFAULT 'running',
    completion_percentage INT DEFAULT 0,
    company_id INT NOT NULL,
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de historial de tareas
CREATE TABLE IF NOT EXISTS task_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    field_changed VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de plantillas
CREATE TABLE IF NOT EXISTS workflow_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT NULL,
    department_id INT DEFAULT NULL,
    template_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_id INT NOT NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de comentarios
CREATE TABLE IF NOT EXISTS task_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_internal BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de archivos adjuntos
CREATE TABLE IF NOT EXISTS task_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de reglas de automatización
CREATE TABLE IF NOT EXISTS process_automation_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_event VARCHAR(100) NOT NULL,
    conditions JSON,
    actions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_id INT NOT NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_trigger_event (trigger_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear vista básica de tareas
CREATE OR REPLACE VIEW v_tasks_basic AS
SELECT 
    t.*,
    p.name as process_name,
    u.first_name as assigned_by_name,
    CASE 
        WHEN t.due_date < NOW() AND t.status NOT IN ('completed', 'cancelled') THEN 'overdue'
        WHEN DATE(t.due_date) = CURDATE() THEN 'due_today'
        WHEN t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'due_soon'
        ELSE 'normal'
    END as due_status
FROM tasks t
LEFT JOIN processes p ON t.process_id = p.process_id
LEFT JOIN users u ON t.assigned_by = u.user_id;

-- Verificar instalación
SELECT 'Módulo Procesos y Tareas instalado correctamente' as Status;

-- Mostrar tablas creadas
SELECT 
    TABLE_NAME as 'Tabla Creada'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND (TABLE_NAME LIKE '%process%' OR TABLE_NAME LIKE '%task%')
ORDER BY TABLE_NAME;
