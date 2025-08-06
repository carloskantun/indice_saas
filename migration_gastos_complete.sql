-- =====================================================
-- MIGRACIÓN GASTOS: corazon_orderdecompras → corazon_indicesaas
-- Script para migrar el módulo de gastos completo al sistema SaaS
-- EJECUTAR PASO A PASO EN PhpMyAdmin
-- =====================================================

-- =====================================================
-- PASO 1: VERIFICAR ESTADO ACTUAL
-- =====================================================
-- Ejecutar para ver qué tablas ya existen:
SHOW TABLES LIKE '%expenses%';
SHOW TABLES LIKE '%providers%';

-- Ver estructura de tablas existentes:
DESCRIBE expenses;
DESCRIBE providers;

-- =====================================================
-- PASO 2: ELIMINAR RESTRICCIONES FK QUE CAUSAN CONFLICTO
-- =====================================================
-- Verificar FK existentes:
SELECT 
    CONSTRAINT_NAME, 
    TABLE_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME IN ('expenses', 'providers', 'expense_payments') 
AND TABLE_SCHEMA = DATABASE();

-- Eliminar FK que impiden DROP (ejecutar solo las que existan):
-- ALTER TABLE expense_payments DROP FOREIGN KEY fk_expense_payments_expense;
-- ALTER TABLE [otra_tabla] DROP FOREIGN KEY [nombre_fk];

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- PASO 3: RECREAR TABLAS CON ESTRUCTURA COMPLETA
-- =====================================================

-- TABLA PROVIDERS (Adaptada de proveedores)
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

-- TABLA EXPENSES (Adaptada de gastos)
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

-- TABLA EXPENSE_PAYMENTS (Adaptada de abonos_gastos)
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

-- TABLA CREDIT_NOTES (Adaptada de notas_credito)
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

-- TABLA CREDIT_NOTE_PAYMENTS (Adaptada de abonos_notas_credito)
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
-- PASO 4: RESTAURAR FOREIGN KEYS
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- FK para expense_payments
ALTER TABLE `expense_payments` 
ADD CONSTRAINT `fk_expense_payments_expense` 
FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE CASCADE;

-- FK para credit_note_payments
ALTER TABLE `credit_note_payments` 
ADD CONSTRAINT `fk_credit_note_payments_note` 
FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes`(`id`) ON DELETE CASCADE;

-- =====================================================
-- PASO 5: INSERTAR/ACTUALIZAR PERMISOS
-- =====================================================

-- Verificar si la tabla permissions tiene la estructura correcta
-- Si permissions.key no existe, pero permissions.key_name sí:
-- ALTER TABLE permissions CHANGE key_name `key` VARCHAR(100) NOT NULL;

-- Insertar permisos (usar key_name si esa es la columna correcta)
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
-- PASO 6: CONFIGURAR MÓDULO EN EL SISTEMA
-- =====================================================

-- Verificar estructura de modules
DESCRIBE modules;

-- Insertar/actualizar módulo de gastos
INSERT INTO `modules` (`name`, `slug`, `description`, `url`, `icon`, `color`, `status`, `created_at`) 
VALUES ('Gastos', 'expenses', 'Gestión completa de gastos y proveedores empresariales', '/modules/expenses/', 'fas fa-coins', '#e74c3c', 'active', NOW())
ON DUPLICATE KEY UPDATE 
description = 'Gestión completa de gastos y proveedores empresariales',
url = '/modules/expenses/',
icon = 'fas fa-coins',
color = '#e74c3c';

-- =====================================================
-- PASO 7: CONFIGURAR ROLE_PERMISSIONS
-- =====================================================

-- Verificar estructura de role_permissions
DESCRIBE role_permissions;

-- Si usa permission_id en lugar de permission_key, necesitamos los IDs:
-- SELECT id, key_name FROM permissions WHERE module = 'expenses';

-- Insertar permisos por rol (ajustar según estructura real)
-- Para structure con permission_id, usar los IDs obtenidos arriba
-- Para estructura con permission_key, usar las keys directamente

-- EJEMPLO para estructura con permission_key:
-- INSERT IGNORE INTO `role_permissions` (`role`, `permission_key`) VALUES
-- ('superadmin', 'expenses.view'),
-- ('superadmin', 'expenses.create'),
-- etc...

-- =====================================================
-- PASO 8: TRIGGERS PARA FOLIOS AUTOMÁTICOS
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
-- PASO 9: DATOS DE PRUEBA (OPCIONAL)
-- =====================================================

-- Insertar proveedores de ejemplo para company_id = 1
INSERT IGNORE INTO `providers` (`company_id`, `name`, `phone`, `email`, `created_by`) VALUES
(1, 'Proveedor de Ejemplo', '555-0123', 'proveedor@ejemplo.com', 1),
(1, 'Servicios Generales SA', '555-0456', 'contacto@servicios.com', 1),
(1, 'Suministros Corporativos', '555-0789', 'ventas@suministros.com', 1);

-- =====================================================
-- PASO 10: VERIFICACIÓN FINAL
-- =====================================================

-- Verificar tablas creadas
SELECT 'TABLAS CREADAS:' as verificacion;
SHOW TABLES LIKE '%expenses%';
SHOW TABLES LIKE '%providers%';
SHOW TABLES LIKE '%credit%';

-- Verificar permisos
SELECT 'PERMISOS:' as verificacion;
SELECT id, key_name, description FROM permissions WHERE module = 'expenses';

-- Verificar módulo
SELECT 'MÓDULO:' as verificacion;
SELECT * FROM modules WHERE slug = 'expenses' OR name LIKE '%gastos%';

-- Verificar triggers
SELECT 'TRIGGERS:' as verificacion;
SHOW TRIGGERS LIKE 'expenses';

-- =====================================================
-- NOTAS IMPORTANTES PARA LA MIGRACIÓN
-- =====================================================

/*
MAPEO DE TABLAS ORIGINAL → SAAS:

1. proveedores → providers
   - Agregar company_id (predeterminado = 1)
   - Mantener toda la estructura existente

2. gastos → expenses  
   - Agregar company_id, unit_id, business_id
   - tipo_gasto → expense_type
   - tipo_compra → purchase_type
   - medio_pago → payment_method
   - cuenta_bancaria → bank_account
   - estatus → status
   - folio_orden → order_folio
   - origen → origin

3. abonos_gastos → expense_payments
   - gasto_id → expense_id
   - archivo_comprobante → receipt_file
   - Agregar created_by

4. unidades_negocio → Usar units y businesses existentes

PRÓXIMO PASO:
- Ejecutar este script paso a paso
- Crear /modules/expenses/ con los archivos PHP
- Migrar funcionalidades desde indice-produccion/gastos.php
*/

-- =====================================================
-- FIN DEL SCRIPT DE MIGRACIÓN
-- =====================================================
