<?php
/**
 * CONFIGURACIÓN MÓDULO GASTOS
 * Configuraciones específicas del módulo de gastos
 */

// Configuración del módulo
return [
    'module' => [
        'name' => 'Gastos',
        'version' => '1.0.0',
        'description' => 'Sistema de gestión de gastos y proveedores',
        'icon' => 'fas fa-receipt',
        'color' => '#667eea'
    ],
    
    'permissions' => [
        'expenses.view' => 'Ver gastos',
        'expenses.create' => 'Crear gastos',
        'expenses.edit' => 'Editar gastos',
        'expenses.delete' => 'Eliminar gastos',
        'expenses.pay' => 'Registrar pagos',
        'expenses.export' => 'Exportar datos',
        'expenses.kpis' => 'Ver estadísticas',
        'providers.view' => 'Ver proveedores',
        'providers.create' => 'Crear proveedores',
        'providers.edit' => 'Editar proveedores',
        'providers.delete' => 'Eliminar proveedores'
    ],
    
    'settings' => [
        'items_per_page' => 25,
        'max_file_size' => '10MB',
        'allowed_file_types' => ['pdf', 'jpg', 'png', 'xlsx'],
        'currency' => 'MXN',
        'currency_symbol' => '$',
        'date_format' => 'Y-m-d',
        'decimal_places' => 2
    ],
    
    'expense_types' => [
        'Unico' => 'Único',
        'Recurrente' => 'Recurrente', 
        'Credito' => 'Crédito'
    ],
    
    'purchase_types' => [
        'Contado' => 'Contado',
        'Credito' => 'Crédito',
        'Anticipado' => 'Anticipado'
    ],
    
    'payment_methods' => [
        'Transferencia' => 'Transferencia Bancaria',
        'Efectivo' => 'Efectivo',
        'Cheque' => 'Cheque',
        'Tarjeta' => 'Tarjeta de Crédito/Débito'
    ],
    
    'origins' => [
        'Directo' => 'Directo',
        'Orden' => 'Orden de Compra',
        'Requisicion' => 'Requisición'
    ],
    
    'status_list' => [
        'Pendiente' => 'Pendiente',
        'Pago parcial' => 'Pago Parcial',
        'Pagado' => 'Pagado',
        'Cancelado' => 'Cancelado'
    ],
    
    'notifications' => [
        'payment_reminder_days' => 7,
        'overdue_alert_days' => 30,
        'enable_email_notifications' => true,
        'enable_system_notifications' => true
    ],
    
    'export' => [
        'max_records' => 10000,
        'formats' => ['csv', 'xlsx', 'pdf'],
        'include_payments' => true,
        'include_providers' => true
    ],
    
    'kpis' => [
        'default_period_days' => 30,
        'chart_colors' => [
            '#667eea', '#764ba2', '#f093fb', '#f5576c',
            '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
            '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3'
        ]
    ]
];
?>
