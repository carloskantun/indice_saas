-- Script para arreglar las tablas de gastos
-- Ejecutar en PhpMyAdmin paso a paso

-- =====================================================
-- PASO 1: VERIFICAR ESTADO ACTUAL
-- =====================================================
SHOW TABLES LIKE '%expenses%';
SHOW TABLES LIKE '%providers%';

-- =====================================================
-- PASO 2: CREAR/ACTUALIZAR TABLA PROVIDERS
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
  `created_by` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_company_status` (`company_id`, `status`),
  INDEX `idx_name` (`name`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 3: CREAR/ACTUALIZAR TABLA EXPENSES
-- =====================================================
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(50) NULL,
  `company_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `expense_type` ENUM('Recurrente','Unico') DEFAULT 'Unico',
  `purchase_type` ENUM('Venta','Administrativa','Operativo','Impuestos','Intereses/Créditos') DEFAULT NULL,
  `payment_method` ENUM('Tarjeta','Transferencia','Efectivo','Cheque') DEFAULT 'Transferencia',
  `bank_account` VARCHAR(100) NULL,
  `status` ENUM('Pagado','Pago parcial','Vencido','Por pagar','Cancelado') DEFAULT 'Por pagar',
  `concept` TEXT NULL,
  `order_folio` VARCHAR(50) NULL,
  `origin` ENUM('Directo','Orden','Requisicion') DEFAULT 'Directo',
  `origin_id` VARCHAR(50) NULL,
  `note_credit_id` INT UNSIGNED NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_company_business` (`company_id`, `business_id`),
  INDEX `idx_payment_date` (`payment_date`),
  INDEX `idx_status` (`status`),
  INDEX `idx_folio` (`folio`),
  INDEX `idx_provider` (`provider_id`),
  INDEX `idx_created_by` (`created_by`),
  INDEX `idx_unit_id` (`unit_id`),
  INDEX `idx_order_folio` (`order_folio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 4: CREAR TABLA EXPENSE_PAYMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS `expense_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `expense_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `comment` TEXT NULL,
  `receipt_file` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_expense_id` (`expense_id`),
  INDEX `idx_payment_date` (`payment_date`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 5: INSERTAR DATOS DE PRUEBA
-- =====================================================

-- Insertar proveedor de prueba
INSERT IGNORE INTO `providers` 
(`id`, `company_id`, `name`, `status`, `created_by`) 
VALUES 
(1, 1, 'Proveedor de Prueba', 'active', 1),
(2, 1, 'Amazon México', 'active', 1),
(3, 1, 'Office Depot', 'active', 1);

-- Insertar gasto de prueba
INSERT IGNORE INTO `expenses` 
(`id`, `folio`, `company_id`, `unit_id`, `business_id`, `provider_id`, `amount`, `payment_date`, `concept`, `status`, `created_by`) 
VALUES 
(1, 'EXP000001', 1, 1, 1, 1, 1500.00, CURDATE(), 'Gasto de prueba para verificar funcionamiento', 'Por pagar', 1);

-- Insertar orden de compra de prueba
INSERT IGNORE INTO `expenses` 
(`id`, `folio`, `company_id`, `unit_id`, `business_id`, `provider_id`, `amount`, `payment_date`, `concept`, `status`, `origin`, `order_folio`, `created_by`) 
VALUES 
(2, 'ORD000001', 1, 1, 1, 2, 2500.00, CURDATE(), 'Orden de compra de prueba', 'Por pagar', 'Orden', 'ORD000001', 1);

-- =====================================================
-- PASO 6: VERIFICACIÓN FINAL
-- =====================================================
SELECT 'PROVIDERS' AS tabla, COUNT(*) AS registros FROM providers WHERE company_id = 1
UNION ALL
SELECT 'EXPENSES' AS tabla, COUNT(*) AS registros FROM expenses WHERE company_id = 1;

-- Mostrar estructura de expenses
DESCRIBE expenses;
