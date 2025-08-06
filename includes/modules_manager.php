<?php
/**
 * Sistema de Módulos con Control de Planes
 * Integración simple con el panel root existente
 */

function getAvailableModules() {
    return [
        'gastos' => [
            'name' => 'Gestión de Gastos',
            'description' => 'Control de ingresos y egresos',
            'icon' => 'fas fa-dollar-sign',
            'status' => 'active',
            'path' => 'modules/gastos.php',
            'plan_level' => 1, // Disponible desde plan Free
            'color' => 'success'
        ],
        'mantenimiento' => [
            'name' => 'Mantenimiento',
            'description' => 'Control de servicios técnicos',
            'icon' => 'fas fa-tools',
            'status' => 'development',
            'path' => 'modules/mantenimiento/',
            'plan_level' => 2, // Requiere plan Starter o superior
            'color' => 'warning'
        ],
        'servicio_cliente' => [
            'name' => 'Servicio al Cliente',
            'description' => 'Gestión de tickets y soporte',
            'icon' => 'fas fa-headset',
            'status' => 'development',
            'path' => 'modules/servicio_cliente/',
            'plan_level' => 2,
            'color' => 'info'
        ],
        'inventario' => [
            'name' => 'Inventario',
            'description' => 'Control de stock y productos',
            'icon' => 'fas fa-boxes',
            'status' => 'planned',
            'path' => 'modules/inventario/',
            'plan_level' => 3, // Requiere plan Pro o superior
            'color' => 'primary'
        ],
        'ventas' => [
            'name' => 'Ventas',
            'description' => 'Facturación y gestión comercial',
            'icon' => 'fas fa-chart-line',
            'status' => 'planned',
            'path' => 'modules/ventas/',
            'plan_level' => 3,
            'color' => 'danger'
        ],
        'analytics' => [
            'name' => 'Analytics Avanzado',
            'description' => 'Métricas y reportes avanzados',
            'icon' => 'fas fa-chart-pie',
            'status' => 'planned',
            'path' => 'modules/analytics/',
            'plan_level' => 4, // Solo Enterprise
            'color' => 'dark'
        ]
    ];
}

function getCompanyPlanLevel($company_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT p.name, p.price_monthly,
                   CASE 
                       WHEN p.price_monthly = 0 THEN 1
                       WHEN p.price_monthly <= 25 THEN 2
                       WHEN p.price_monthly <= 75 THEN 3
                       ELSE 4
                   END as plan_level
            FROM companies c 
            JOIN plans p ON c.plan_id = p.id 
            WHERE c.id = ? AND p.is_active = 1
        ");
        $stmt->execute([$company_id]);
        $plan = $stmt->fetch();
        
        return $plan ? $plan['plan_level'] : 1;
        
    } catch (Exception $e) {
        error_log("Error obteniendo nivel de plan: " . $e->getMessage());
        return 1; // Plan gratuito por defecto
    }
}

function getModulesForCompany($company_id) {
    $allModules = getAvailableModules();
    $companyPlanLevel = getCompanyPlanLevel($company_id);
    $availableModules = [];
    
    foreach ($allModules as $key => $module) {
        // Solo incluir módulos que el plan permite
        if ($module['plan_level'] <= $companyPlanLevel) {
            $availableModules[$key] = $module;
        } else {
            // Marcar como bloqueado
            $module['blocked'] = true;
            $module['upgrade_required'] = true;
            $availableModules[$key] = $module;
        }
    }
    
    return $availableModules;
}

function canAccessModule($company_id, $module_key) {
    $allModules = getAvailableModules();
    $companyPlanLevel = getCompanyPlanLevel($company_id);
    
    if (!isset($allModules[$module_key])) {
        return false;
    }
    
    return $allModules[$module_key]['plan_level'] <= $companyPlanLevel;
}

function getModuleAccessInfo($company_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.name as plan_name, p.price_monthly,
               CASE 
                   WHEN p.price_monthly = 0 THEN 'Free'
                   WHEN p.price_monthly <= 25 THEN 'Starter'
                   WHEN p.price_monthly <= 75 THEN 'Pro'
                   ELSE 'Enterprise'
               END as plan_type
        FROM companies c 
        JOIN plans p ON c.plan_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$company_id]);
    
    return $stmt->fetch();
}
