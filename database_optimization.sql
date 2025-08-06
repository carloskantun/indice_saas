-- =====================================================
-- OPTIMIZACIÓN DE BASE DE DATOS INDICE SAAS
-- Mejoras para Escalabilidad y Rendimiento
-- =====================================================

-- 1. AGREGAR ÍNDICES FALTANTES PARA MEJOR RENDIMIENTO
-- =====================================================

-- Índices para user_invitations (tabla crítica para notificaciones)
ALTER TABLE `user_invitations` ADD INDEX `idx_email_status` (`email`, `status`);
ALTER TABLE `user_invitations` ADD INDEX `idx_company_status` (`company_id`, `status`);
ALTER TABLE `user_invitations` ADD INDEX `idx_token` (`token`);
ALTER TABLE `user_invitations` ADD INDEX `idx_created_at` (`created_at`);

-- Índices para companies
ALTER TABLE `companies` ADD INDEX `idx_status_created` (`status`, `created_at`);
ALTER TABLE `companies` ADD INDEX `idx_created_by` (`created_by`);

-- Índices para users (si no existen)
-- ALTER TABLE `users` ADD INDEX `idx_email` (`email`);
-- ALTER TABLE `users` ADD INDEX `idx_status` (`status`);

-- Índices para notifications
ALTER TABLE `notifications` ADD INDEX `idx_user_status` (`user_id`, `status`);
ALTER TABLE `notifications` ADD INDEX `idx_company_type` (`company_id`, `type`);
ALTER TABLE `notifications` ADD INDEX `idx_created_at` (`created_at`);

-- Índices para role_permissions
ALTER TABLE `role_permissions` ADD INDEX `idx_role` (`role`);

-- 2. TABLA DE AUDITORÍA PARA TRACKING DE CAMBIOS
-- =====================================================

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(64) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `user_id` INT UNSIGNED NULL,
  `company_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_table_record` (`table_name`, `record_id`),
  INDEX `idx_user_company` (`user_id`, `company_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA DE SESIONES PARA MEJOR MANEJO
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `company_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `data` TEXT NULL,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_company` (`user_id`, `company_id`),
  INDEX `idx_last_activity` (`last_activity`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE LOGS DE SEGURIDAD
-- =====================================================

CREATE TABLE IF NOT EXISTS `security_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` ENUM('login_success', 'login_failed', 'logout', 'password_change', 'permission_denied', 'suspicious_activity') NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `email` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `details` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_ip_address` (`ip_address`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA DE CONFIGURACIONES DEL SISTEMA
-- =====================================================

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(128) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `type` ENUM('string', 'integer', 'boolean', 'json', 'float') DEFAULT 'string',
  `description` TEXT NULL,
  `is_public` BOOLEAN DEFAULT FALSE,
  `updated_by` INT UNSIGNED NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_name` (`key_name`),
  INDEX `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. INSERTAR CONFIGURACIONES BÁSICAS
-- =====================================================

INSERT IGNORE INTO `system_settings` (`key_name`, `value`, `type`, `description`, `is_public`) VALUES
('app_name', 'Indice SaaS', 'string', 'Nombre de la aplicación', TRUE),
('app_version', '2.0.0', 'string', 'Versión actual del sistema', TRUE),
('maintenance_mode', 'false', 'boolean', 'Modo de mantenimiento', FALSE),
('max_upload_size', '10485760', 'integer', 'Tamaño máximo de archivos en bytes (10MB)', FALSE),
('session_timeout', '7200', 'integer', 'Timeout de sesión en segundos (2 horas)', FALSE),
('max_login_attempts', '5', 'integer', 'Máximo intentos de login por IP/hora', FALSE),
('enable_2fa', 'false', 'boolean', 'Habilitar autenticación de dos factores', FALSE),
('default_language', 'es', 'string', 'Idioma por defecto del sistema', TRUE),
('enable_notifications', 'true', 'boolean', 'Habilitar sistema de notificaciones', TRUE),
('smtp_enabled', 'false', 'boolean', 'Habilitar envío de emails por SMTP', FALSE);

-- 7. OPTIMIZAR TABLA NOTIFICATIONS
-- =====================================================

-- Agregar campos faltantes si no existen
ALTER TABLE `notifications` 
ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NOT NULL DEFAULT '',
ADD COLUMN IF NOT EXISTS `message` TEXT NULL,
ADD COLUMN IF NOT EXISTS `type` VARCHAR(50) NOT NULL DEFAULT 'general',
ADD COLUMN IF NOT EXISTS `user_id` INT UNSIGNED NULL,
ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL,
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'read', 'archived') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS `icon` VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS `color` VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS `action_url` TEXT NULL,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 8. TABLA DE CACHE PARA OPTIMIZACIÓN
-- =====================================================

CREATE TABLE IF NOT EXISTS `cache_entries` (
  `cache_key` VARCHAR(255) NOT NULL,
  `value` LONGTEXT NOT NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_key`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. OPTIMIZACIÓN DE TABLAS EXISTENTES
-- =====================================================

-- Agregar columnas faltantes a user_invitations si no existen
ALTER TABLE `user_invitations` 
ADD COLUMN IF NOT EXISTS `token` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NOT NULL,
ADD COLUMN IF NOT EXISTS `role` VARCHAR(50) DEFAULT 'user',
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS `sent_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `expiration_date` TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS `accepted_date` TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 10. LIMPIEZA Y MANTENIMIENTO
-- =====================================================

-- Limpiar notificaciones antiguas (más de 6 meses)
DELETE FROM `notifications` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- Limpiar cache expirado
DELETE FROM `cache_entries` WHERE `expires_at` IS NOT NULL AND `expires_at` < NOW();

-- Limpiar invitaciones expiradas (más de 30 días)
UPDATE `user_invitations` 
SET `status` = 'expired' 
WHERE `status` = 'pending' 
AND `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 11. CREAR VISTAS ÚTILES PARA REPORTES
-- =====================================================

CREATE OR REPLACE VIEW `v_user_company_summary` AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    u.email,
    c.id as company_id,
    c.name as company_name,
    p.name as plan_name,
    uc.role,
    uc.status as user_status,
    c.status as company_status
FROM users u
LEFT JOIN user_companies uc ON u.id = uc.user_id
LEFT JOIN companies c ON uc.company_id = c.id
LEFT JOIN plans p ON c.plan_id = p.id;

CREATE OR REPLACE VIEW `v_pending_invitations` AS
SELECT 
    ui.id,
    ui.email,
    ui.token,
    ui.role,
    c.name as company_name,
    u.name as sent_by_name,
    ui.created_at,
    ui.expiration_date,
    DATEDIFF(ui.expiration_date, NOW()) as days_until_expiry
FROM user_invitations ui
JOIN companies c ON ui.company_id = c.id
LEFT JOIN users u ON ui.sent_by = u.id
WHERE ui.status = 'pending';

-- 12. COMENTARIOS FINALES
-- =====================================================

/*
RESUMEN DE OPTIMIZACIONES APLICADAS:

✅ Índices estratégicos para mejor rendimiento
✅ Tabla de auditoría para tracking completo
✅ Sistema de sesiones mejorado
✅ Logs de seguridad
✅ Configuraciones centralizadas
✅ Cache interno para optimización
✅ Vistas para reportes rápidos
✅ Limpieza automática de datos antiguos

PRÓXIMOS PASOS RECOMENDADOS:
1. Implementar triggers para auditoría automática
2. Configurar particionado para tablas grandes
3. Implementar replicación para alta disponibilidad
4. Monitorear performance con herramientas específicas

NOTA: Ejecutar este script en horario de bajo tráfico
*/
