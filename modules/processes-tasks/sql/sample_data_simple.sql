-- Datos de ejemplo para el módulo Procesos y Tareas
-- Ejecutar DESPUÉS del script de instalación principal

-- Insertar algunos procesos de ejemplo
INSERT INTO processes (name, description, status, priority, estimated_duration, created_by, company_id) VALUES
('Onboarding de Nuevo Empleado', 'Proceso completo de incorporación de nuevos empleados', 'active', 'high', 16, 1, 1),
('Revisión Mensual de Inventario', 'Proceso de verificación mensual del inventario', 'active', 'medium', 8, 1, 1),
('Solicitud de Vacaciones', 'Proceso para solicitar y aprobar vacaciones', 'active', 'low', 2, 1, 1);

-- Insertar algunas etapas de proceso
INSERT INTO process_steps (process_id, step_name, step_description, step_order, estimated_hours, responsible_role) VALUES
(1, 'Preparación de Documentos', 'Preparar contrato y documentos necesarios', 1, 2, 'admin'),
(1, 'Configuración de Accesos', 'Crear cuentas y asignar permisos', 2, 1, 'admin'),
(1, 'Tour de Instalaciones', 'Mostrar instalaciones al nuevo empleado', 3, 2, 'moderator');

-- Insertar algunas tareas de ejemplo
INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, status, due_date, estimated_hours, company_id) VALUES
('Revisar expediente candidato', 'Verificar documentos completos', 1, 1, 'high', 'pending', DATE_ADD(NOW(), INTERVAL 2 DAY), 1.0, 1),
('Preparar estación de trabajo', 'Configurar equipo para nuevo empleado', 2, 1, 'medium', 'pending', DATE_ADD(NOW(), INTERVAL 3 DAY), 2.0, 1),
('Actualizar inventario oficina', 'Revisar suministros de oficina', 1, 1, 'low', 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY), 3.0, 1);

-- Insertar una plantilla de ejemplo
INSERT INTO workflow_templates (name, description, category, template_data, created_by, company_id) VALUES
('Proceso de Contratación Básico', 'Plantilla estándar para contratación', 'Recursos Humanos', 
'{"steps": [{"name": "Publicar vacante", "duration": 2}, {"name": "Revisar CVs", "duration": 4}]}', 
1, 1);

-- Verificar datos insertados
SELECT 'Datos de ejemplo insertados correctamente' as Status;
SELECT 
    'Procesos' as Tipo, COUNT(*) as Cantidad FROM processes
UNION ALL
SELECT 'Tareas', COUNT(*) FROM tasks
UNION ALL
SELECT 'Plantillas', COUNT(*) FROM workflow_templates;
