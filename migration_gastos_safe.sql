-- =====================================================
-- MIGRACIÓN GASTOS SIMPLIFICADA - EVITA PROBLEMAS DE PERMISOS
-- Script seguro para usuarios con permisos limitados
-- EJECUTAR PASO A PASO EN PhpMyAdmin
-- =====================================================

-- =====================================================
-- PASO 1: DESHABILITAR VERIFICACIÓN FK TEMPORALMENTE
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- =====================================================
-- PASO 2: ELIMINAR TABLAS EXISTENTES (SI EXISTEN)
-- =====================================================
DROP TABLE IF EXISTS `expense_payments`;
DROP TABLE IF EXISTS `credit_note_payments`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `credit_notes`;
DROP TABLE IF EXISTS `providers`;

-- =====================================================
-- PASO 3: CREAR TABLA PROVIDERS
-- =====================================================
CREATE TABLE `providers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clabe` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_status` (`company_id`,`status`),
  KEY `idx_name` (`name`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 4: CREAR TABLA EXPENSES
-- =====================================================
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `business_id` int(10) unsigned NOT NULL,
  `provider_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `expense_type` enum('Recurrente','Unico') COLLATE utf8mb4_unicode_ci DEFAULT 'Unico',
  `purchase_type` enum('Venta','Administrativa','Operativo','Impuestos','Intereses/Créditos') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('Tarjeta','Transferencia','Efectivo') COLLATE utf8mb4_unicode_ci DEFAULT 'Transferencia',
  `bank_account` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pagado','Pago parcial','Vencido','Por pagar','Cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'Por pagar',
  `concept` text COLLATE utf8mb4_unicode_ci,
  `order_folio` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin` enum('Directo','Orden') COLLATE utf8mb4_unicode_ci DEFAULT 'Directo',
  `origin_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note_credit_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`),
  KEY `idx_company_business` (`company_id`,`business_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_status` (`status`),
  KEY `idx_folio` (`folio`),
  KEY `idx_provider` (`provider_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 5: CREAR TABLA EXPENSE_PAYMENTS
-- =====================================================
CREATE TABLE `expense_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `expense_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `receipt_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_expense_id` (`expense_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 6: CREAR TABLA CREDIT_NOTES
-- =====================================================
CREATE TABLE `credit_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note_date` date NOT NULL,
  `concept` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Disponible','Aplicada','Vencida') COLLATE utf8mb4_unicode_ci DEFAULT 'Disponible',
  `responsible_user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`),
  KEY `idx_company_status` (`company_id`,`status`),
  KEY `idx_folio` (`folio`),
  KEY `idx_responsible` (`responsible_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 7: CREAR TABLA CREDIT_NOTE_PAYMENTS
-- =====================================================
CREATE TABLE `credit_note_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `receipt_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_credit_note_id` (`credit_note_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 8: AGREGAR FOREIGN KEYS
-- =====================================================
ALTER TABLE `expense_payments` 
ADD CONSTRAINT `fk_expense_payments_expense` 
FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE;

ALTER TABLE `credit_note_payments` 
ADD CONSTRAINT `fk_credit_note_payments_note` 
FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE;

-- =====================================================
-- PASO 9: RESTAURAR VERIFICACIÓN FK
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- PASO 10: INSERTAR PERMISOS (USANDO ESTRUCTURA ACTUAL)
-- =====================================================
INSERT IGNORE INTO `permissions` (`key_name`, `description`, `module`) VALUES
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
-- PASO 11: CONFIGURAR MÓDULO
-- =====================================================
INSERT INTO `modules` (`name`, `slug`, `description`, `url`, `icon`, `color`, `status`, `created_at`) 
VALUES ('Gastos', 'expenses', 'Gestión completa de gastos y proveedores empresariales', '/modules/expenses/', 'fas fa-coins', '#e74c3c', 'active', NOW())
ON DUPLICATE KEY UPDATE 
description = 'Gestión completa de gastos y proveedores empresariales',
url = '/modules/expenses/',
icon = 'fas fa-coins',
color = '#e74c3c';

-- =====================================================
-- PASO 12: ASIGNAR PERMISOS A SUPERADMIN
-- =====================================================
-- Obtener IDs de permisos y asignar a superadmin
INSERT IGNORE INTO `role_permissions` (`role`, `permission_id`) 
SELECT 'superadmin', id FROM `permissions` WHERE `module` = 'expenses';

-- =====================================================
-- PASO 13: TRIGGERS PARA FOLIOS AUTOMÁTICOS
-- =====================================================
DELIMITER $$

DROP TRIGGER IF EXISTS `generate_expense_folio`$$
CREATE TRIGGER `generate_expense_folio` 
BEFORE INSERT ON `expenses` 
FOR EACH ROW 
BEGIN
    DECLARE next_num INT;
    
    IF NEW.folio IS NULL OR NEW.folio = '' THEN
        SELECT COALESCE(MAX(CAST(SUBSTRING(folio, 9) AS UNSIGNED)), 0) + 1 
        INTO next_num
        FROM expenses 
        WHERE folio LIKE CONCAT('EXP-', YEAR(NOW()), '-%');
        
        SET NEW.folio = CONCAT('EXP-', YEAR(NOW()), '-', LPAD(next_num, 6, '0'));
    END IF;
END$$

DROP TRIGGER IF EXISTS `generate_credit_note_folio`$$
CREATE TRIGGER `generate_credit_note_folio` 
BEFORE INSERT ON `credit_notes` 
FOR EACH ROW 
BEGIN
    DECLARE next_num INT;
    
    IF NEW.folio IS NULL OR NEW.folio = '' THEN
        SELECT COALESCE(MAX(CAST(SUBSTRING(folio, 8) AS UNSIGNED)), 0) + 1 
        INTO next_num
        FROM credit_notes 
        WHERE folio LIKE CONCAT('NC-', YEAR(NOW()), '-%');
        
        SET NEW.folio = CONCAT('NC-', YEAR(NOW()), '-', LPAD(next_num, 6, '0'));
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- PASO 14: DATOS DE PRUEBA
-- =====================================================
INSERT IGNORE INTO `providers` (`company_id`, `name`, `phone`, `email`, `created_by`) VALUES
(1, 'Proveedor de Ejemplo', '555-0123', 'proveedor@ejemplo.com', 1),
(1, 'Servicios Generales SA', '555-0456', 'contacto@servicios.com', 1),
(1, 'Suministros Corporativos', '555-0789', 'ventas@suministros.com', 1);

-- =====================================================
-- PASO 15: VERIFICACIÓN FINAL
-- =====================================================
SELECT 'VERIFICACIÓN COMPLETADA' as status;

SELECT 'TABLAS CREADAS:' as info;
SHOW TABLES LIKE '%expenses%';
SHOW TABLES LIKE '%providers%';
SHOW TABLES LIKE '%credit%';

SELECT 'PERMISOS GASTOS:' as info;
SELECT COUNT(*) as total_permisos FROM permissions WHERE module = 'expenses';

SELECT 'MÓDULO REGISTRADO:' as info;
SELECT name, slug, status FROM modules WHERE slug = 'expenses';

SELECT 'DATOS DE PRUEBA:' as info;
SELECT COUNT(*) as total_providers FROM providers;

-- =====================================================
-- RESULTADO ESPERADO:
-- - 5 tablas creadas: providers, expenses, expense_payments, credit_notes, credit_note_payments
-- - 11 permisos para el módulo expenses
-- - 1 módulo registrado como 'expenses'
-- - 3 proveedores de prueba
-- - Triggers activos para folios automáticos
-- =====================================================
