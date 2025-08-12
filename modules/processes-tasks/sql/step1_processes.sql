-- PASO 1: Tabla de procesos
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
    company_id INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
