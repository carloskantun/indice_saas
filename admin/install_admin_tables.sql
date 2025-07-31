-- =====================================================
-- SCRIPT SQL MANUAL PARA SISTEMA DE GESTIÓN DE USUARIOS ADMIN
-- Ejecutar en el orden indicado en tu base de datos MySQL
-- =====================================================

-- 1. CREAR TABLA INVITATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    company_id INT NOT NULL,
    unit_id INT DEFAULT NULL,
    business_id INT DEFAULT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiration_date TIMESTAMP NULL,
    sent_by INT NOT NULL,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. CREAR TABLA USER_COMPANIES (SI NO EXISTE)
-- =====================================================
CREATE TABLE IF NOT EXISTS user_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    role ENUM('superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_company (user_id, company_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 3. CREAR TABLA USER_UNITS
-- =====================================================
CREATE TABLE IF NOT EXISTS user_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    unit_id INT NOT NULL,
    role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_unit (user_id, unit_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);

-- 4. CREAR TABLA USER_BUSINESSES
-- =====================================================
CREATE TABLE IF NOT EXISTS user_businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_business (user_id, business_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- 5. CREAR TABLA PERMISSIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    module VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. CREAR TABLA ROLE_PERMISSIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 7. CREAR TABLAS UNITS Y BUSINESSES (SI NO EXISTEN)
-- =====================================================
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    unit_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);

-- 8. CREAR TRIGGER PARA FECHA DE EXPIRACIÓN
-- =====================================================
-- Primero eliminar si existe
DROP TRIGGER IF EXISTS set_invitation_expiration;

-- Crear el trigger
DELIMITER $$
CREATE TRIGGER set_invitation_expiration 
BEFORE INSERT ON invitations
FOR EACH ROW
BEGIN
    IF NEW.expiration_date IS NULL THEN
        SET NEW.expiration_date = DATE_ADD(NOW(), INTERVAL 48 HOUR);
    END IF;
END$$
DELIMITER ;

-- 9. INSERTAR PERMISOS BÁSICOS
-- =====================================================
INSERT IGNORE INTO permissions (key_name, description, module) VALUES
('expenses.view', 'View expenses', 'expenses'),
('expenses.create', 'Create expenses', 'expenses'),
('expenses.edit', 'Edit expenses', 'expenses'),
('expenses.delete', 'Delete expenses', 'expenses'),
('users.view', 'View users', 'users'),
('users.invite', 'Invite users', 'users'),
('users.edit', 'Edit users', 'users'),
('users.suspend', 'Suspend users', 'users'),
('reports.view', 'View reports', 'reports'),
('settings.view', 'View settings', 'settings'),
('settings.edit', 'Edit settings', 'settings');

-- 10. ASIGNAR PERMISOS A ROLES
-- =====================================================
-- Superadmin: todos los permisos
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'superadmin', id FROM permissions;

-- Admin: permisos limitados
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'admin', id FROM permissions 
WHERE key_name IN (
    'expenses.view', 'expenses.create', 'expenses.edit',
    'users.view', 'users.invite',
    'reports.view', 'settings.view'
);

-- Moderator: permisos básicos
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'moderator', id FROM permissions 
WHERE key_name IN (
    'expenses.view', 'expenses.create', 'expenses.edit',
    'users.view', 'reports.view'
);

-- User: solo lectura básica
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'user', id FROM permissions 
WHERE key_name IN ('expenses.view', 'expenses.create');

-- =====================================================
-- CONFIGURACIÓN INICIAL DE USUARIO SUPERADMIN
-- =====================================================

-- IMPORTANTE: Reemplaza los valores según tu configuración:
-- - 'tu_email@ejemplo.com' por tu email real
-- - 1 por el ID real de tu empresa

-- Ejemplo de asignación de superadmin:
/*
INSERT IGNORE INTO user_companies (user_id, company_id, role, status) 
SELECT u.id, 1, 'superadmin', 'active'
FROM users u 
WHERE u.email = 'tu_email@ejemplo.com';
*/

-- =====================================================
-- CONSULTAS DE VERIFICACIÓN
-- =====================================================

-- Verificar que las tablas se crearon:
-- SHOW TABLES LIKE '%invitaciones%';
-- SHOW TABLES LIKE '%user_companies%';
-- SHOW TABLES LIKE '%permissions%';

-- Ver estructura de tabla invitaciones:
-- DESCRIBE invitaciones;

-- Ver permisos creados:
-- SELECT * FROM permissions;

-- Ver asignaciones de permisos por rol:
-- SELECT r.role, p.key_name, p.description 
-- FROM role_permissions r 
-- JOIN permissions p ON r.permission_id = p.id 
-- ORDER BY r.role, p.module;

-- Ver usuarios con roles:
-- SELECT u.name, u.email, uc.role, c.name as company_name
-- FROM users u
-- JOIN user_companies uc ON u.id = uc.user_id
-- JOIN companies c ON uc.company_id = c.id
-- WHERE uc.status = 'active';

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
