-- PASO 3: Tablas auxiliares
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
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    is_current BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    field_changed VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
