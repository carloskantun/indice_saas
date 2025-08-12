-- PASO 2: Tabla de tareas
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
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
