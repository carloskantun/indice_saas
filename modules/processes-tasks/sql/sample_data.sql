-- ============================================================================
-- DATOS DE EJEMPLO MÓDULO PROCESOS Y TAREAS
-- Sistema SaaS Indice - Para testing y demostración
-- ============================================================================

-- NOTA: Este script inserta datos de ejemplo para facilitar las pruebas
-- Asegúrate de tener company_id = 1 y los departamentos/empleados necesarios

-- ============================================================================
-- PROCESOS DE EJEMPLO
-- ============================================================================

INSERT INTO `processes` (`name`, `description`, `department_id`, `status`, `priority`, `estimated_duration`, `created_by`, `company_id`) VALUES
('Onboarding de Nuevo Empleado', 'Proceso completo de incorporación de nuevos empleados a la empresa, desde la firma del contrato hasta la capacitación inicial', 1, 'active', 'high', 16, 1, 1),
('Revisión Mensual de Inventario', 'Proceso sistemático de verificación y actualización del inventario físico y digital', 2, 'active', 'medium', 8, 1, 1),
('Solicitud de Vacaciones', 'Flujo para solicitar, aprobar y gestionar las vacaciones de los empleados', 1, 'active', 'low', 2, 1, 1),
('Evaluación de Desempeño Anual', 'Proceso integral de evaluación del rendimiento y desarrollo profesional', 1, 'draft', 'high', 24, 1, 1),
('Mantenimiento Preventivo Equipos', 'Rutina de mantenimiento preventivo para equipos y maquinaria', 3, 'active', 'medium', 6, 1, 1),
('Proceso de Compras', 'Flujo completo desde solicitud hasta recepción de productos/servicios', 4, 'active', 'medium', 12, 1, 1),
('Atención al Cliente', 'Proceso estandarizado de atención y resolución de consultas de clientes', 5, 'active', 'high', 4, 1, 1),
('Desarrollo de Producto', 'Metodología para el desarrollo de nuevos productos desde idea hasta lanzamiento', 6, 'draft', 'critical', 120, 1, 1);

-- ============================================================================
-- ETAPAS DE PROCESOS
-- ============================================================================

-- Etapas para "Onboarding de Nuevo Empleado" (process_id = 1)
INSERT INTO `process_steps` (`process_id`, `step_name`, `step_description`, `step_order`, `estimated_hours`, `responsible_role`) VALUES
(1, 'Preparación de Documentos', 'Preparar contrato de trabajo, manual del empleado y documentación legal necesaria', 1, 2, 'admin'),
(1, 'Configuración de Accesos', 'Crear cuentas de usuario, asignar permisos del sistema y configurar herramientas de trabajo', 2, 1, 'admin'),
(1, 'Tour de Instalaciones', 'Presentar las instalaciones, normas de seguridad y presentar al equipo de trabajo', 3, 2, 'moderator'),
(1, 'Capacitación Inicial', 'Capacitación sobre procesos de la empresa, políticas internas y herramientas específicas', 4, 8, 'moderator'),
(1, 'Seguimiento Primera Semana', 'Verificar adaptación del empleado y resolver dudas iniciales', 5, 3, 'moderator');

-- Etapas para "Revisión Mensual de Inventario" (process_id = 2)
INSERT INTO `process_steps` (`process_id`, `step_name`, `step_description`, `step_order`, `estimated_hours`, `responsible_role`) VALUES
(2, 'Planificación del Inventario', 'Definir áreas a revisar, asignar responsables y preparar documentación', 1, 1, 'moderator'),
(2, 'Conteo Físico', 'Realizar conteo físico de productos y materiales en almacén', 2, 4, 'user'),
(2, 'Verificación de Sistemas', 'Comparar conteo físico con registros del sistema de inventario', 3, 2, 'moderator'),
(2, 'Reporte de Discrepancias', 'Documentar diferencias encontradas e investigar causas', 4, 1, 'admin');

-- Etapas para "Solicitud de Vacaciones" (process_id = 3)
INSERT INTO `process_steps` (`process_id`, `step_name`, `step_description`, `step_order`, `estimated_hours`, `responsible_role`) VALUES
(3, 'Solicitud del Empleado', 'Empleado completa formulario de solicitud de vacaciones', 1, 0.25, 'user'),
(3, 'Revisión del Supervisor', 'Supervisor directo revisa y aprueba/rechaza la solicitud', 2, 0.5, 'moderator'),
(3, 'Aprobación de RRHH', 'Recursos Humanos valida disponibilidad y políticas aplicables', 3, 0.75, 'admin'),
(3, 'Notificación y Registro', 'Comunicar decisión y actualizar sistemas de control', 4, 0.5, 'admin');

-- ============================================================================
-- TAREAS DE EJEMPLO
-- ============================================================================

INSERT INTO `tasks` (`process_id`, `title`, `description`, `assigned_to`, `assigned_by`, `priority`, `status`, `due_date`, `estimated_hours`, `actual_hours`, `completion_percentage`, `department_id`, `company_id`) VALUES

-- Tareas del proceso de Onboarding (process_id = 1)
(1, 'Revisar expediente del candidato Juan Pérez', 'Verificar que todos los documentos estén completos y correctos antes del primer día de trabajo', 1, 1, 'high', 'completed', '2024-12-15 17:00:00', 1.0, 0.75, 100, 1, 1),
(1, 'Preparar estación de trabajo - Área de Ventas', 'Configurar computadora, teléfono, material de oficina y credenciales de acceso', 2, 1, 'medium', 'in_progress', '2024-12-16 09:00:00', 2.0, 1.2, 60, 3, 1),
(1, 'Programar sesión de capacitación inicial', 'Coordinar con instructores y preparar material de capacitación para nuevo empleado', 3, 1, 'medium', 'pending', '2024-12-17 14:00:00', 1.5, 0, 0, 1, 1),

-- Tareas del proceso de Inventario (process_id = 2)
(2, 'Verificar stock de productos categoría A', 'Contar físicamente productos de alta rotación en almacén principal', 4, 1, 'medium', 'pending', '2024-12-18 15:00:00', 4.0, 0, 0, 2, 1),
(2, 'Actualizar sistema de inventario', 'Registrar ajustes encontrados durante el conteo físico', 5, 1, 'high', 'pending', '2024-12-19 12:00:00', 2.0, 0, 0, 2, 1),

-- Tareas independientes (sin proceso)
(NULL, 'Actualizar inventario de suministros de oficina', 'Revisar y actualizar stock de papel, tóners, material de escritorio', 3, 1, 'low', 'pending', '2024-12-20 17:00:00', 3.0, 0, 0, 4, 1),
(NULL, 'Revisión de equipos de seguridad', 'Inspeccionar extintores, botiquines y sistemas de emergencia', 6, 1, 'high', 'in_progress', '2024-12-16 16:00:00', 2.5, 1.0, 40, 3, 1),
(NULL, 'Preparar reporte mensual de ventas', 'Compilar datos de ventas del mes y generar reporte ejecutivo', 7, 1, 'medium', 'review', '2024-12-21 10:00:00', 4.0, 3.5, 90, 5, 1),
(NULL, 'Contactar proveedores para cotizaciones', 'Solicitar cotizaciones para renovación de contrato de limpieza', 8, 1, 'low', 'pending', '2024-12-25 15:00:00', 2.0, 0, 0, 4, 1),
(NULL, 'Actualizar sitio web corporativo', 'Subir nuevas imágenes de productos y actualizar información de contacto', 9, 1, 'medium', 'pending', '2024-12-22 18:00:00', 6.0, 0, 0, 6, 1),

-- Tareas vencidas (para testing de alertas)
(NULL, 'Revisar pólizas de seguros', 'Verificar vigencia y cobertura de pólizas corporativas', 2, 1, 'critical', 'pending', '2024-12-10 17:00:00', 3.0, 0, 0, 4, 1),
(NULL, 'Backup de servidores', 'Realizar backup completo de bases de datos y archivos críticos', 10, 1, 'critical', 'in_progress', '2024-12-12 23:59:00', 4.0, 2.0, 50, 3, 1),

-- Tareas para hoy (testing)
(NULL, 'Reunión de seguimiento de proyectos', 'Revisar avance de proyectos activos con líderes de equipo', 1, 1, 'high', 'pending', CONCAT(CURDATE(), ' 15:00:00'), 2.0, 0, 0, 1, 1),
(NULL, 'Atender consultas de clientes pendientes', 'Responder emails y llamadas de clientes que están en espera', 11, 1, 'high', 'in_progress', CONCAT(CURDATE(), ' 18:00:00'), 3.0, 1.5, 50, 5, 1);

-- ============================================================================
-- ASIGNACIONES INICIALES DE TAREAS
-- ============================================================================

INSERT INTO `task_assignments` (`task_id`, `assigned_to`, `assigned_by`, `reason`, `is_current`) VALUES
(1, 1, 1, 'Asignación inicial - Responsable de RRHH', TRUE),
(2, 2, 1, 'Asignación inicial - Técnico de IT', TRUE),
(3, 3, 1, 'Asignación inicial - Coordinador de capacitación', TRUE),
(4, 4, 1, 'Asignación inicial - Encargado de almacén', TRUE),
(5, 5, 1, 'Asignación inicial - Supervisor de inventario', TRUE),
(6, 3, 1, 'Asignación inicial - Administrador de oficina', TRUE),
(7, 6, 1, 'Asignación inicial - Responsable de seguridad', TRUE),
(8, 7, 1, 'Asignación inicial - Analista de ventas', TRUE),
(9, 8, 1, 'Asignación inicial - Coordinador de compras', TRUE),
(10, 9, 1, 'Asignación inicial - Desarrollador web', TRUE),
(11, 2, 1, 'Asignación inicial - Administrador de seguros', TRUE),
(12, 10, 1, 'Asignación inicial - Administrador de sistemas', TRUE),
(13, 1, 1, 'Asignación inicial - Gerente general', TRUE),
(14, 11, 1, 'Asignación inicial - Ejecutivo de atención al cliente', TRUE);

-- ============================================================================
-- PLANTILLAS DE FLUJOS DE TRABAJO
-- ============================================================================

INSERT INTO `workflow_templates` (`name`, `description`, `category`, `department_id`, `template_data`, `created_by`, `company_id`) VALUES
('Proceso de Contratación Estándar', 'Plantilla completa para el proceso de contratación de personal en cualquier departamento', 'Recursos Humanos', 1, JSON_OBJECT(
    'steps', JSON_ARRAY(
        JSON_OBJECT('name', 'Publicación de vacante', 'description', 'Crear y publicar oferta de trabajo en portales', 'duration', 2, 'responsible', 'admin'),
        JSON_OBJECT('name', 'Revisión de CVs', 'description', 'Filtrar y seleccionar candidatos potenciales', 'duration', 4, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Entrevistas técnicas', 'description', 'Realizar entrevistas con candidatos preseleccionados', 'duration', 6, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Verificación de referencias', 'description', 'Contactar referencias laborales y académicas', 'duration', 2, 'responsible', 'admin'),
        JSON_OBJECT('name', 'Oferta de trabajo', 'description', 'Elaborar y presentar oferta formal al candidato seleccionado', 'duration', 1, 'responsible', 'admin')
    ),
    'estimated_total_hours', 15,
    'priority', 'high'
), 1, 1),

('Mantenimiento Preventivo de Equipos', 'Plantilla estándar para rutinas de mantenimiento preventivo de maquinaria y equipos', 'Operaciones', 3, JSON_OBJECT(
    'steps', JSON_ARRAY(
        JSON_OBJECT('name', 'Inspección visual preliminar', 'description', 'Revisar estado general del equipo', 'duration', 1, 'responsible', 'user'),
        JSON_OBJECT('name', 'Lubricación y limpieza', 'description', 'Aplicar lubricantes y limpiar componentes críticos', 'duration', 2, 'responsible', 'user'),
        JSON_OBJECT('name', 'Calibración de instrumentos', 'description', 'Verificar y ajustar calibración de medidores', 'duration', 3, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Pruebas de funcionamiento', 'description', 'Ejecutar pruebas operativas completas', 'duration', 2, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Documentación y reporte', 'description', 'Registrar hallazgos y generar reporte de mantenimiento', 'duration', 1, 'responsible', 'admin')
    ),
    'estimated_total_hours', 9,
    'priority', 'medium'
), 1, 1),

('Atención de Reclamos de Clientes', 'Proceso estandarizado para gestionar y resolver reclamos de clientes de manera eficiente', 'Atención al Cliente', 5, JSON_OBJECT(
    'steps', JSON_ARRAY(
        JSON_OBJECT('name', 'Recepción del reclamo', 'description', 'Registrar y clasificar el reclamo del cliente', 'duration', 0.5, 'responsible', 'user'),
        JSON_OBJECT('name', 'Investigación inicial', 'description', 'Investigar los hechos y recopilar información relevante', 'duration', 2, 'responsible', 'user'),
        JSON_OBJECT('name', 'Propuesta de solución', 'description', 'Desarrollar propuesta de solución basada en hallazgos', 'duration', 1, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Aprobación de la solución', 'description', 'Obtener aprobación de supervisor para implementar solución', 'duration', 0.5, 'responsible', 'admin'),
        JSON_OBJECT('name', 'Implementación y seguimiento', 'description', 'Ejecutar solución y verificar satisfacción del cliente', 'duration', 2, 'responsible', 'moderator')
    ),
    'estimated_total_hours', 6,
    'priority', 'high'
), 1, 1),

('Desarrollo de Campaña de Marketing', 'Template para crear y ejecutar campañas de marketing desde la conceptualización hasta la medición de resultados', 'Marketing', 6, JSON_OBJECT(
    'steps', JSON_ARRAY(
        JSON_OBJECT('name', 'Briefing y objetivos', 'description', 'Definir objetivos, público objetivo y mensaje clave', 'duration', 4, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Desarrollo creativo', 'description', 'Crear contenido visual y textual para la campaña', 'duration', 16, 'responsible', 'user'),
        JSON_OBJECT('name', 'Revisión y aprobación', 'description', 'Revisar materiales y obtener aprobaciones necesarias', 'duration', 2, 'responsible', 'admin'),
        JSON_OBJECT('name', 'Implementación', 'description', 'Lanzar campaña en canales seleccionados', 'duration', 4, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Monitoreo y optimización', 'description', 'Seguir métricas y optimizar durante la ejecución', 'duration', 8, 'responsible', 'moderator'),
        JSON_OBJECT('name', 'Análisis de resultados', 'description', 'Evaluar efectividad y generar reporte final', 'duration', 4, 'responsible', 'admin')
    ),
    'estimated_total_hours', 38,
    'priority', 'medium'
), 1, 1);

-- ============================================================================
-- COMENTARIOS EN TAREAS
-- ============================================================================

INSERT INTO `task_comments` (`task_id`, `user_id`, `comment`, `is_internal`) VALUES
(1, 1, 'Expediente completo y documentación en orden. Candidato puede iniciar el lunes.', FALSE),
(2, 2, 'Equipo configurado al 60%. Faltan instalar algunas aplicaciones específicas del área.', TRUE),
(2, 1, 'Perfecto, ¿cuándo estimas tener todo listo?', TRUE),
(2, 2, 'Mañana por la mañana debería estar todo operativo.', TRUE),
(7, 6, 'Revisión completada. Se encontraron 2 extintores próximos a vencer, ya solicitamos reposición.', FALSE),
(8, 7, 'Datos compilados, trabajando en el análisis de tendencias para el reporte ejecutivo.', FALSE),
(12, 10, 'Backup iniciado. Estimado de finalización: 2 horas adicionales.', TRUE);

-- ============================================================================
-- HISTORIAL DE CAMBIOS (SIMULADO)
-- ============================================================================

INSERT INTO `task_history` (`task_id`, `field_changed`, `old_value`, `new_value`, `changed_by`, `notes`) VALUES
(1, 'status', 'pending', 'in_progress', 1, 'Inicio de revisión de expediente'),
(1, 'completion_percentage', '0', '50', 1, 'Avance en revisión de documentos'),
(1, 'status', 'in_progress', 'completed', 1, 'Expediente verificado completamente'),
(2, 'status', 'pending', 'in_progress', 2, 'Iniciando configuración de estación'),
(2, 'completion_percentage', '0', '30', 2, 'Hardware configurado'),
(2, 'completion_percentage', '30', '60', 2, 'Software base instalado'),
(7, 'status', 'pending', 'in_progress', 6, 'Iniciando inspección de equipos'),
(7, 'completion_percentage', '0', '40', 6, 'Revisión de extintores completada'),
(8, 'status', 'pending', 'in_progress', 7, 'Iniciando compilación de datos'),
(8, 'completion_percentage', '0', '70', 7, 'Datos recopilados, falta análisis'),
(8, 'status', 'in_progress', 'review', 7, 'Reporte listo para revisión'),
(12, 'status', 'pending', 'in_progress', 10, 'Backup iniciado');

-- ============================================================================
-- INSTANCIAS DE PROCESOS (EJEMPLOS)
-- ============================================================================

INSERT INTO `process_instances` (`process_id`, `instance_name`, `started_by`, `expected_completion`, `status`, `completion_percentage`, `company_id`) VALUES
(1, 'Onboarding - Juan Pérez', 1, '2024-12-20 17:00:00', 'running', 35, 1),
(1, 'Onboarding - María González', 1, '2024-12-25 17:00:00', 'running', 10, 1),
(2, 'Inventario Diciembre 2024', 1, '2024-12-19 18:00:00', 'running', 15, 1),
(3, 'Vacaciones - Carlos Mendoza', 1, '2024-12-16 12:00:00', 'completed', 100, 1),
(3, 'Vacaciones - Ana Silva', 1, '2024-12-18 12:00:00', 'running', 75, 1);

-- ============================================================================
-- REGLAS DE AUTOMATIZACIÓN (EJEMPLOS)
-- ============================================================================

INSERT INTO `process_automation_rules` (`name`, `description`, `trigger_event`, `conditions`, `actions`, `created_by`, `company_id`) VALUES
('Auto-escalación tareas vencidas', 'Escalar automáticamente tareas que han vencido sin completarse', 'task_overdue', JSON_OBJECT(
    'overdue_hours', 24,
    'exclude_statuses', JSON_ARRAY('completed', 'cancelled')
), JSON_OBJECT(
    'notify_supervisor', true,
    'change_priority', 'high',
    'add_comment', 'Tarea escalada automáticamente por vencimiento'
), 1, 1),

('Notificación deadline próximo', 'Notificar cuando una tarea está próxima a vencer', 'task_due_soon', JSON_OBJECT(
    'hours_before_due', 48,
    'notify_types', JSON_ARRAY('email', 'system')
), JSON_OBJECT(
    'send_notification', true,
    'notification_template', 'task_due_reminder'
), 1, 1),

('Auto-completar proceso', 'Marcar proceso como completado cuando todas sus tareas están terminadas', 'all_tasks_completed', JSON_OBJECT(
    'process_status', JSON_ARRAY('active', 'running')
), JSON_OBJECT(
    'change_process_status', 'completed',
    'notify_creator', true,
    'generate_report', true
), 1, 1);

-- ============================================================================
-- VERIFICACIÓN DE DATOS INSERTADOS
-- ============================================================================

-- Mostrar resumen de datos insertados
SELECT 'Procesos creados' as Tipo, COUNT(*) as Cantidad FROM processes WHERE company_id = 1
UNION ALL
SELECT 'Etapas de procesos', COUNT(*) FROM process_steps ps INNER JOIN processes p ON ps.process_id = p.process_id WHERE p.company_id = 1
UNION ALL
SELECT 'Tareas creadas', COUNT(*) FROM tasks WHERE company_id = 1
UNION ALL
SELECT 'Asignaciones registradas', COUNT(*) FROM task_assignments ta INNER JOIN tasks t ON ta.task_id = t.task_id WHERE t.company_id = 1
UNION ALL
SELECT 'Plantillas disponibles', COUNT(*) FROM workflow_templates WHERE company_id = 1
UNION ALL
SELECT 'Comentarios en tareas', COUNT(*) FROM task_comments tc INNER JOIN tasks t ON tc.task_id = t.task_id WHERE t.company_id = 1
UNION ALL
SELECT 'Registros de historial', COUNT(*) FROM task_history th INNER JOIN tasks t ON th.task_id = t.task_id WHERE t.company_id = 1
UNION ALL
SELECT 'Instancias de procesos', COUNT(*) FROM process_instances WHERE company_id = 1
UNION ALL
SELECT 'Reglas de automatización', COUNT(*) FROM process_automation_rules WHERE company_id = 1;

-- Mostrar estado de tareas por prioridad y status
SELECT 
    priority as Prioridad,
    status as Estado,
    COUNT(*) as Cantidad
FROM tasks 
WHERE company_id = 1 
GROUP BY priority, status 
ORDER BY 
    FIELD(priority, 'critical', 'high', 'medium', 'low'),
    FIELD(status, 'overdue', 'pending', 'in_progress', 'review', 'completed', 'cancelled');

SELECT 'Datos de ejemplo insertados correctamente' as Status,
       NOW() as Inserted_At;

-- ============================================================================
-- FIN DE DATOS DE EJEMPLO
-- ============================================================================
