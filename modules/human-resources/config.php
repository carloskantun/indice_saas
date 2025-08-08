<?php
/**
 * CONFIGURACIÓN MÓDULO RECURSOS HUMANOS
 * Configuraciones específicas del módulo de recursos humanos
 */

// Configuración del módulo
return [
    'module' => [
        'name' => 'Recursos Humanos',
        'version' => '1.0.0',
        'description' => 'Sistema de gestión de empleados y recursos humanos',
        'icon' => 'fas fa-users',
        'color' => '#3498db'
    ],
    
    'permissions' => [
        'employees.view' => 'Ver empleados',
        'employees.create' => 'Crear empleados',
        'employees.edit' => 'Editar empleados',
        'employees.delete' => 'Eliminar empleados',
        'employees.export' => 'Exportar datos',
        'employees.kpis' => 'Ver estadísticas',
        'departments.view' => 'Ver departamentos',
        'departments.create' => 'Crear departamentos',
        'departments.edit' => 'Editar departamentos',
        'departments.delete' => 'Eliminar departamentos',
        'positions.view' => 'Ver puestos',
        'positions.create' => 'Crear puestos',
        'positions.edit' => 'Editar puestos',
        'positions.delete' => 'Eliminar puestos'
    ],
    
    'settings' => [
        'items_per_page' => 25,
        'max_file_size' => '10MB',
        'allowed_file_types' => ['pdf', 'jpg', 'png', 'docx'],
        'currency' => 'MXN',
        'currency_symbol' => '$',
        'date_format' => 'Y-m-d',
        'decimal_places' => 2
    ],
    
    'employment_types' => [
        'Tiempo_Completo' => 'Tiempo Completo',
        'Medio_Tiempo' => 'Medio Tiempo',
        'Temporal' => 'Temporal',
        'Freelance' => 'Freelance',
        'Practicante' => 'Practicante'
    ],
    
    'contract_types' => [
        'Indefinido' => 'Indefinido',
        'Temporal' => 'Temporal',
        'Por_Obra' => 'Por Obra',
        'Practicas' => 'Prácticas'
    ],
    
    'status_types' => [
        'Activo' => 'Activo',
        'Inactivo' => 'Inactivo',
        'Vacaciones' => 'Vacaciones',
        'Licencia' => 'Licencia',
        'Baja' => 'Baja'
    ],
    
    'payment_frequencies' => [
        'Semanal' => 'Semanal',
        'Quincenal' => 'Quincenal',
        'Mensual' => 'Mensual'
    ],
    
    'education_levels' => [
        'Primaria' => 'Primaria',
        'Secundaria' => 'Secundaria',
        'Preparatoria' => 'Preparatoria',
        'Tecnico' => 'Técnico',
        'Licenciatura' => 'Licenciatura',
        'Maestria' => 'Maestría',
        'Doctorado' => 'Doctorado'
    ],
    
    'notification_settings' => [
        'employee_birthday' => true,
        'contract_expiry' => true,
        'vacation_requests' => true,
        'salary_changes' => true
    ]
];
?>
