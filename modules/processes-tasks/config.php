<?php
/**
 * CONFIGURACIÓN MÓDULO PROCESOS Y TAREAS
 * Configuraciones específicas del módulo
 */

// Configuración específica del módulo
$module_config = [
    'name' => 'processes-tasks',
    'version' => '1.0.0',
    'author' => 'Sistema SaaS Indice',
    'description' => 'Módulo de gestión de procesos operativos y tareas',
    
    // Permisos específicos del módulo
    'permissions' => [
        'processes.view' => 'Ver procesos',
        'processes.create' => 'Crear procesos',
        'processes.edit' => 'Editar procesos',
        'processes.delete' => 'Eliminar procesos',
        'processes.export' => 'Exportar procesos',
        'processes.kpis' => 'Ver KPIs de procesos',
        'processes.start' => 'Iniciar procesos',
        'processes.pause' => 'Pausar procesos',
        
        'tasks.view' => 'Ver tareas',
        'tasks.create' => 'Crear tareas',
        'tasks.edit' => 'Editar tareas',
        'tasks.delete' => 'Eliminar tareas',
        'tasks.assign' => 'Asignar tareas',
        'tasks.complete' => 'Completar tareas',
        'tasks.export' => 'Exportar tareas',
        'tasks.kpis' => 'Ver KPIs de tareas',
        'tasks.reassign' => 'Reasignar tareas',
        
        'workflows.view' => 'Ver flujos de trabajo',
        'workflows.create' => 'Crear flujos de trabajo',
        'workflows.edit' => 'Editar flujos de trabajo',
        'workflows.delete' => 'Eliminar flujos de trabajo',
        
        'templates.view' => 'Ver plantillas',
        'templates.create' => 'Crear plantillas',
        'templates.edit' => 'Editar plantillas',
        'templates.delete' => 'Eliminar plantillas',
        
        'reports.view' => 'Ver reportes',
        'reports.export' => 'Exportar reportes',
        
        'automation.configure' => 'Configurar automatización',
    ],
    
    // Configuración de estados
    'process_statuses' => [
        'draft' => 'Borrador',
        'active' => 'Activo',
        'paused' => 'Pausado',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado'
    ],
    
    'task_statuses' => [
        'pending' => 'Pendiente',
        'in_progress' => 'En Progreso',
        'review' => 'En Revisión',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado'
    ],
    
    'priorities' => [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Crítica'
    ],
    
    // Configuración de colores para estados
    'status_colors' => [
        'draft' => '#6b7280',
        'active' => '#10b981',
        'paused' => '#f59e0b',
        'completed' => '#059669',
        'cancelled' => '#6b7280',
        'pending' => '#6b7280',
        'in_progress' => '#3b82f6',
        'review' => '#8b5cf6'
    ],
    
    'priority_colors' => [
        'low' => '#10b981',
        'medium' => '#f59e0b',
        'high' => '#ef4444',
        'critical' => '#991b1b'
    ],
    
    // Configuración de exportación
    'export_formats' => ['csv', 'pdf', 'excel'],
    
    // Configuración de notificaciones
    'notifications' => [
        'task_assigned' => true,
        'task_completed' => true,
        'task_overdue' => true,
        'process_started' => true,
        'process_completed' => true
    ],
    
    // Configuración de automatización
    'automation' => [
        'auto_assign_tasks' => false,
        'escalate_overdue_tasks' => true,
        'notify_on_deadline' => true,
        'auto_complete_process' => false
    ],
    
    // Configuración de paginación
    'pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
        'options' => [10, 25, 50, 100]
    ]
];

// Hacer disponible la configuración globalmente
if (!isset($GLOBALS['modules_config'])) {
    $GLOBALS['modules_config'] = [];
}
$GLOBALS['modules_config']['processes-tasks'] = $module_config;

// Función para obtener configuración del módulo
function getProcessesTasksConfig($key = null) {
    global $module_config;
    
    if ($key === null) {
        return $module_config;
    }
    
    return $module_config[$key] ?? null;
}

// Función para verificar si una funcionalidad está habilitada
function isProcessesTasksFeatureEnabled($feature) {
    $config = getProcessesTasksConfig();
    return $config[$feature] ?? false;
}

// Función para obtener color de estado
function getProcessesTasksStatusColor($status, $type = 'status') {
    $config = getProcessesTasksConfig();
    $colors = $config[$type . '_colors'] ?? [];
    return $colors[$status] ?? '#6b7280';
}

// Función para obtener texto localizado de estado
function getProcessesTasksStatusText($status, $type = 'task') {
    $config = getProcessesTasksConfig();
    $statuses = $config[$type . '_statuses'] ?? [];
    return $statuses[$status] ?? $status;
}

// Función para obtener texto localizado de prioridad
function getProcessesTasksPriorityText($priority) {
    $config = getProcessesTasksConfig();
    $priorities = $config['priorities'] ?? [];
    return $priorities[$priority] ?? $priority;
}
?>
