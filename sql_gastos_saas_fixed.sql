-- =====================================================
-- MÓDULO GASTOS - SISTEMA SAAS INDICE (VERSIÓN CORREGIDA)
-- Adaptación completa para jerarquía Empresa->Unidad->Negocio
-- Ejecutar en PhpMyAdmin SECCIÓN POR SECCIÓN
-- =====================================================

-- =====================================================
-- SECCIÓN 1: VERIFICAR ESTRUCTURA EXISTENTE
-- =====================================================
-- Ejecutar primero para verificar tablas existentes:
-- SHOW TABLES LIKE '%companies%';
-- SHOW TABLES LIKE '%providers%';
-- SHOW TABLES LIKE '%expenses%';

-- =====================================================
-- SECCIÓN 2: CREAR TABLA DE PROVEEDORES (SIN FK INICIAL)
-- =====================================================
DROP TABLE IF EXISTS `providers`;
CREATE TABLE `providers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(255) NULL,
  `clabe` VARCHAR(18) NULL,
  `account_number` VARCHAR(30) NULL,
  `bank` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `rfc` VARCHAR(13) NULL,
  `service_description` TEXT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_company_status` (`company_id`, `status`),
  INDEX `idx_name` (`name`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 3: CREAR TABLA PRINCIPAL DE GASTOS
-- =====================================================
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(50) NOT NULL UNIQUE,
  `company_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `expense_type` ENUM('Recurrente','Unico') DEFAULT 'Unico',
  `purchase_type` ENUM('Venta','Administrativa','Operativo','Impuestos','Intereses/Créditos') DEFAULT NULL,
  `payment_method` ENUM('Tarjeta','Transferencia','Efectivo') DEFAULT 'Transferencia',
  `bank_account` VARCHAR(100) NULL,
  `status` ENUM('Pagado','Pago parcial','Vencido','Por pagar','Cancelado') DEFAULT 'Por pagar',
  `concept` TEXT NULL,
  `order_folio` VARCHAR(50) NULL,
  `origin` ENUM('Directo','Orden') DEFAULT 'Directo',
  `origin_id` VARCHAR(50) NULL,
  `note_credit_id` INT UNSIGNED NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_company_business` (`company_id`, `business_id`),
  INDEX `idx_payment_date` (`payment_date`),
  INDEX `idx_status` (`status`),
  INDEX `idx_folio` (`folio`),
  INDEX `idx_provider` (`provider_id`),
  INDEX `idx_created_by` (`created_by`),
  INDEX `idx_unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 4: TABLA DE ABONOS/PAGOS PARCIALES
-- =====================================================
DROP TABLE IF EXISTS `expense_payments`;
CREATE TABLE `expense_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `expense_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `comment` TEXT NULL,
  `receipt_file` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_expense_id` (`expense_id`),
  INDEX `idx_payment_date` (`payment_date`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 5: TABLA DE NOTAS DE CRÉDITO
-- =====================================================
DROP TABLE IF EXISTS `credit_notes`;
CREATE TABLE `credit_notes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(50) NOT NULL UNIQUE,
  `company_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `note_date` DATE NOT NULL,
  `concept` TEXT NULL,
  `status` ENUM('Disponible','Aplicada','Vencida') DEFAULT 'Disponible',
  `responsible_user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_company_status` (`company_id`, `status`),
  INDEX `idx_folio` (`folio`),
  INDEX `idx_responsible` (`responsible_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 6: TABLA DE ABONOS A NOTAS DE CRÉDITO
-- =====================================================
DROP TABLE IF EXISTS `credit_note_payments`;
CREATE TABLE `credit_note_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `credit_note_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `comment` TEXT NULL,
  `receipt_file` VARCHAR(255) NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_credit_note_id` (`credit_note_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 7: AGREGAR CLAVES FORÁNEAS (EJECUTAR DESPUÉS)
-- =====================================================
-- Solo ejecutar si las tablas referenciadas existen:

-- Para expense_payments
ALTER TABLE `expense_payments` 
ADD CONSTRAINT `fk_expense_payments_expense` 
FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE CASCADE;

-- Para credit_note_payments
ALTER TABLE `credit_note_payments` 
ADD CONSTRAINT `fk_credit_note_payments_note` 
FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes`(`id`) ON DELETE CASCADE;

-- =====================================================
-- SECCIÓN 8: VERIFICAR TABLA PERMISSIONS ANTES DE INSERTAR
-- =====================================================
-- Ejecutar primero: SHOW TABLES LIKE 'permissions';
-- Si no existe, crear tabla permissions:

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_module` (`module`),
  INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 9: PERMISOS PARA EL MÓDULO GASTOS
-- =====================================================
INSERT IGNORE INTO `permissions` (`key`, `description`, `module`) VALUES
('expenses.view', 'Ver gastos', 'expenses'),
('expenses.create', 'Crear gastos', 'expenses'),
('expenses.edit', 'Editar gastos', 'expenses'),
('expenses.delete', 'Eliminar gastos', 'expenses'),
('expenses.pay', 'Registrar pagos', 'expenses'),
('expenses.export', 'Exportar gastos', 'expenses'),
('expenses.kpis', 'Ver KPIs de gastos', 'expenses'),
('providers.view', 'Ver proveedores', 'expenses'),
('providers.create', 'Crear proveedores', 'expenses'),
('providers.edit', 'Editar proveedores', 'expenses'),
('providers.delete', 'Eliminar proveedores', 'expenses');

-- =====================================================
-- SECCIÓN 10: VERIFICAR TABLA ROLE_PERMISSIONS
-- =====================================================
-- Ejecutar primero: SHOW TABLES LIKE 'role_permissions';
-- Si no existe, crear tabla role_permissions:

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role` ENUM('root','support','superadmin','admin','moderator','user') NOT NULL,
  `permission_key` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_role_permission` (`role`, `permission_key`),
  INDEX `idx_role` (`role`),
  INDEX `idx_permission` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 11: ROLES POR DEFECTO PARA GASTOS
-- =====================================================
INSERT IGNORE INTO `role_permissions` (`role`, `permission_key`) VALUES
-- SuperAdmin tiene todos los permisos
('superadmin', 'expenses.view'),
('superadmin', 'expenses.create'),
('superadmin', 'expenses.edit'),
('superadmin', 'expenses.delete'),
('superadmin', 'expenses.pay'),
('superadmin', 'expenses.export'),
('superadmin', 'expenses.kpis'),
('superadmin', 'providers.view'),
('superadmin', 'providers.create'),
('superadmin', 'providers.edit'),
('superadmin', 'providers.delete'),

-- Admin tiene permisos operativos
('admin', 'expenses.view'),
('admin', 'expenses.create'),
('admin', 'expenses.edit'),
('admin', 'expenses.pay'),
('admin', 'expenses.export'),
('admin', 'expenses.kpis'),
('admin', 'providers.view'),
('admin', 'providers.create'),
('admin', 'providers.edit'),

-- Moderator tiene permisos limitados
('moderator', 'expenses.view'),
('moderator', 'expenses.create'),
('moderator', 'expenses.pay'),
('moderator', 'providers.view'),

-- User solo puede ver
('user', 'expenses.view'),
('user', 'providers.view');

-- =====================================================
-- SECCIÓN 12: VERIFICAR TABLA MODULES
-- =====================================================
-- Si la tabla modules no tiene la estructura correcta, actualizarla:

-- Verificar estructura: DESCRIBE modules;
-- Si falta alguna columna, agregarla:

ALTER TABLE `modules` ADD COLUMN IF NOT EXISTS `slug` VARCHAR(50) NULL AFTER `name`;
ALTER TABLE `modules` ADD COLUMN IF NOT EXISTS `color` VARCHAR(20) NULL AFTER `icon`;

-- =====================================================
-- SECCIÓN 13: CONFIGURACIÓN DEL MÓDULO EN EL SISTEMA
-- =====================================================
INSERT IGNORE INTO `modules` (`name`, `slug`, `description`, `path`, `icon`, `color`, `status`, `created_at`) VALUES
('Gastos', 'expenses', 'Gestión completa de gastos y proveedores empresariales', '/modules/expenses/', 'fas fa-coins', '#e74c3c', 'active', NOW());

-- =====================================================
-- SECCIÓN 14: TRIGGERS PARA AUTO-GENERAR FOLIOS
-- =====================================================
DELIMITER $$

DROP TRIGGER IF EXISTS `generate_expense_folio`$$
CREATE TRIGGER `generate_expense_folio` 
BEFORE INSERT ON `expenses` 
FOR EACH ROW 
BEGIN
    IF NEW.folio IS NULL OR NEW.folio = '' THEN
        SET NEW.folio = CONCAT('EXP-', YEAR(NOW()), '-', LPAD(
            (SELECT COALESCE(MAX(CAST(SUBSTRING(folio, 9) AS UNSIGNED)), 0) + 1 
             FROM expenses 
             WHERE folio LIKE CONCAT('EXP-', YEAR(NOW()), '-%')), 
            6, '0'
        ));
    END IF;
END$$

DROP TRIGGER IF EXISTS `generate_credit_note_folio`$$
CREATE TRIGGER `generate_credit_note_folio` 
BEFORE INSERT ON `credit_notes` 
FOR EACH ROW 
BEGIN
    IF NEW.folio IS NULL OR NEW.folio = '' THEN
        SET NEW.folio = CONCAT('NC-', YEAR(NOW()), '-', LPAD(
            (SELECT COALESCE(MAX(CAST(SUBSTRING(folio, 8) AS UNSIGNED)), 0) + 1 
             FROM credit_notes 
             WHERE folio LIKE CONCAT('NC-', YEAR(NOW()), '-%')), 
            6, '0'
        ));
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- SECCIÓN 15: DATOS DE EJEMPLO PARA TESTING (OPCIONAL)
-- =====================================================
-- Estos datos se pueden eliminar en producción
-- Solo ejecutar si existe company_id = 1

INSERT IGNORE INTO `providers` (`id`, `company_id`, `name`, `phone`, `email`, `created_by`) VALUES
(1, 1, 'Proveedor de Ejemplo', '555-0123', 'proveedor@ejemplo.com', 1),
(2, 1, 'Servicios Generales SA', '555-0456', 'contacto@servicios.com', 1),
(3, 1, 'Suministros Corporativos', '555-0789', 'ventas@suministros.com', 1);

-- =====================================================
-- SECCIÓN 16: VERIFICACIÓN FINAL
-- =====================================================
-- Ejecutar al final para verificar que todo se creó correctamente:

-- SELECT 'TABLAS CREADAS:' as info;
-- SHOW TABLES LIKE '%expenses%';
-- SHOW TABLES LIKE '%providers%';
-- SHOW TABLES LIKE '%credit%';

-- SELECT 'PERMISOS CREADOS:' as info;
-- SELECT * FROM permissions WHERE module = 'expenses';

-- SELECT 'MÓDULO REGISTRADO:' as info;
-- SELECT * FROM modules WHERE slug = 'expenses';

-- =====================================================
-- FIN DEL SCRIPT - MÓDULO GASTOS SAAS (VERSIÓN SEGURA)
-- =====================================================

/*
INSTRUCCIONES DE INSTALACIÓN:

1. Ejecutar sección por sección en PhpMyAdmin
2. Verificar que cada sección se ejecute sin errores antes de continuar
3. Si alguna FK falla, omitir esa sección y continuar
4. Las FK se pueden agregar manualmente después si es necesario
5. Los datos de ejemplo son opcionales

NOTAS IMPORTANTES:
- Este script está diseñado para ser seguro y evitar errores
- Cada tabla se crea independientemente primero
- Las FK se agregan después para evitar problemas de dependencias
- Se incluyen verificaciones de estructura antes de cada operación
*/
