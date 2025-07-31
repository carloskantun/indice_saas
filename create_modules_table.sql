CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL UNIQUE,
  `description` text,
  `icon` varchar(100) DEFAULT 'fas fa-puzzle-piece',
  `color` varchar(7) DEFAULT '#3498db',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar módulos predefinidos del sistema
INSERT INTO `modules` (`name`, `slug`, `description`, `icon`, `color`, `status`) VALUES
('Gastos', 'gastos', 'Gestión y control de gastos empresariales', 'fas fa-coins', '#e74c3c', 'active'),
('Mantenimiento', 'mantenimiento', 'Control de mantenimiento de equipos y vehículos', 'fas fa-tools', '#f39c12', 'active'),
('Servicio al Cliente', 'servicio_cliente', 'Gestión de tickets y atención al cliente', 'fas fa-headset', '#3498db', 'active'),
('Usuarios', 'usuarios', 'Gestión de usuarios y permisos', 'fas fa-users', '#9b59b6', 'active'),
('KPIs', 'kpis', 'Indicadores clave de rendimiento', 'fas fa-chart-line', '#27ae60', 'active'),
('Compras', 'compras', 'Gestión de compras y proveedores', 'fas fa-shopping-cart', '#34495e', 'active'),
('Lavandería', 'lavanderia', 'Control de servicios de lavandería', 'fas fa-tshirt', '#1abc9c', 'active'),
('Transfers', 'transfers', 'Gestión de servicios de transporte', 'fas fa-bus', '#e67e22', 'active');

-- También necesitamos crear la tabla plan_modules si no existe
CREATE TABLE IF NOT EXISTS `plan_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_module_unique` (`plan_id`, `module_id`),
  KEY `plan_id` (`plan_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `fk_plan_modules_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_modules_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
