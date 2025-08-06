-- =====================================================
-- MÓDULO GASTOS - SISTEMA SAAS INDICE
-- Adaptación completa para jerarquía Empresa->Unidad->Negocio
-- Ejecutar en PhpMyAdmin línea por línea
-- =====================================================

-- 1. TABLA PRINCIPAL DE GASTOS (ADAPTADA AL SAAS)
-- =====================================================
CREATE TABLE IF NOT EXISTS `expenses` (
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
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA DE ABONOS/PAGOS PARCIALES
-- =====================================================
CREATE TABLE IF NOT EXISTS `expense_payments` (
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
  
  FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA DE PROVEEDORES (VINCULADA A EMPRESAS)
-- =====================================================
CREATE TABLE IF NOT EXISTS `providers` (
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
  
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE NOTAS DE CRÉDITO (OPCIONAL - PARA FUNCIONALIDAD COMPLETA)
-- =====================================================
CREATE TABLE IF NOT EXISTS `credit_notes` (
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
  
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA DE ABONOS A NOTAS DE CRÉDITO
-- =====================================================
CREATE TABLE IF NOT EXISTS `credit_note_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `credit_note_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `comment` TEXT NULL,
  `receipt_file` VARCHAR(255) NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_credit_note_id` (`credit_note_id`),
  
  FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PERMISOS PARA EL MÓDULO GASTOS
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

-- 7. ROLES POR DEFECTO PARA GASTOS
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

-- 8. CONFIGURACIÓN DEL MÓDULO EN EL SISTEMA
-- =====================================================
INSERT IGNORE INTO `modules` (`name`, `slug`, `description`, `icon`, `color`, `status`, `created_at`) VALUES
('Gastos', 'expenses', 'Gestión completa de gastos y proveedores empresariales', 'fas fa-coins', '#e74c3c', 'active', NOW());

-- 9. TRIGGERS PARA AUTO-GENERAR FOLIOS
-- =====================================================
DELIMITER $$

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

-- 10. DATOS DE EJEMPLO PARA TESTING (OPCIONAL)
-- =====================================================
-- Estos datos se pueden eliminar en producción

INSERT IGNORE INTO `providers` (`id`, `company_id`, `name`, `phone`, `email`, `created_by`) VALUES
(1, 1, 'Proveedor de Ejemplo', '555-0123', 'proveedor@ejemplo.com', 1),
(2, 1, 'Servicios Generales SA', '555-0456', 'contacto@servicios.com', 1),
(3, 1, 'Suministros Corporativos', '555-0789', 'ventas@suministros.com', 1);

-- =====================================================
-- FIN DEL SCRIPT - MÓDULO GASTOS SAAS
-- =====================================================
