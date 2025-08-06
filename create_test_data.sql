-- Script para insertar datos de prueba
-- Ejecutar después de corregir las inconsistencias

-- Insertar algunos gastos de prueba
INSERT INTO `expenses` 
(`folio`, `company_id`, `unit_id`, `business_id`, `provider_id`, `amount`, `payment_date`, `concept`, `status`, `origin`, `created_by`) 
VALUES 
('EXP000001', 1, 2, 1, 1, 1500.00, CURDATE(), 'Compra de material de oficina', 'Por pagar', 'Directo', 1),
('EXP000002', 1, 2, 1, 2, 850.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Servicios de limpieza', 'Pagado', 'Directo', 1),
('EXP000003', 1, 2, 1, 3, 2200.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Equipos de cómputo', 'Pago parcial', 'Directo', 1);

-- Insertar órdenes de compra de prueba
INSERT INTO `expenses` 
(`folio`, `company_id`, `unit_id`, `business_id`, `provider_id`, `amount`, `payment_date`, `concept`, `status`, `origin`, `order_folio`, `created_by`) 
VALUES 
('ORD000001', 1, 2, 1, 1, 3500.00, CURDATE(), 'Orden de compra - Mobiliario de oficina', 'Por pagar', 'Orden', 'ORD000001', 1),
('ORD000002', 1, 2, 1, 2, 1200.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Orden de compra - Suministros varios', 'Por pagar', 'Orden', 'ORD000002', 1);

-- Insertar algunos pagos parciales
INSERT INTO `expense_payments` 
(`expense_id`, `amount`, `payment_date`, `comment`, `created_by`) 
VALUES 
(3, 1000.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Pago parcial del 50%', 1);

-- Verificar que se insertaron correctamente
SELECT 'GASTOS INSERTADOS' AS resultado, COUNT(*) AS total FROM expenses WHERE company_id = 1;
SELECT 'PAGOS INSERTADOS' AS resultado, COUNT(*) AS total FROM expense_payments;

-- Mostrar resumen por origen
SELECT origin, COUNT(*) as cantidad, SUM(amount) as total_monto 
FROM expenses 
WHERE company_id = 1 
GROUP BY origin;
