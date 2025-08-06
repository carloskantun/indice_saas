<?php
function checkPlanRestrictions($company_id, $restriction_type, $count_to_add = 1) {
    try {
        $db = getDB();
        
        // Obtener el plan de la empresa
        $stmt = $db->prepare("
            SELECT p.* 
            FROM companies c 
            JOIN plans p ON c.plan_id = p.id 
            WHERE c.id = ? AND p.is_active = 1
        ");
        $stmt->execute([$company_id]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            return ['allowed' => false, 'message' => 'Plan no encontrado o inactivo'];
        }
        
        $current_count = 0;
        $limit = 0;
        $restriction_name = '';
        
        switch ($restriction_type) {
            case 'users':
                // Contar usuarios actuales en la empresa
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_companies WHERE company_id = ?");
                $stmt->execute([$company_id]);
                $current_count = $stmt->fetchColumn();
                $limit = $plan['users_max'];
                $restriction_name = 'usuarios';
                break;
                
            case 'businesses':
                // Contar negocios actuales en la empresa (a través de units)
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM businesses b 
                    INNER JOIN units u ON b.unit_id = u.id 
                    WHERE u.company_id = ?
                ");
                $stmt->execute([$company_id]);
                $current_count = $stmt->fetchColumn();
                $limit = $plan['businesses_max'];
                $restriction_name = 'negocios';
                break;
                
            case 'units':
                // Contar unidades actuales en la empresa
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM units u 
                    WHERE u.company_id = ?
                ");
                $stmt->execute([$company_id]);
                $current_count = $stmt->fetchColumn();
                $limit = $plan['units_max'];
                $restriction_name = 'unidades';
                break;
                
            case 'storage':
                // Verificar límite de almacenamiento (en MB)
                // Por ahora retornamos true, se puede implementar después
                return ['allowed' => true, 'message' => ''];
                
            default:
                return ['allowed' => false, 'message' => 'Tipo de restricción no válido'];
        }
        
        // Si el límite es 0 o -1, significa sin límite
        if ($limit <= 0) {
            return ['allowed' => true, 'message' => ''];
        }
        
        // Verificar si excede el límite
        if (($current_count + $count_to_add) > $limit) {
            $message = sprintf(
                'Has alcanzado el límite de %s para tu plan (%d/%d). Por favor, actualiza tu plan para continuar.',
                $restriction_name,
                $current_count,
                $limit
            );
            return ['allowed' => false, 'message' => $message];
        }
        
        return ['allowed' => true, 'message' => ''];
        
    } catch (Exception $e) {
        error_log("Error verificando restricciones de plan: " . $e->getMessage());
        return ['allowed' => false, 'message' => 'Error interno del sistema'];
    }
}

function getPlanLimits($company_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT p.name, p.users_max, p.businesses_max, p.units_max, p.storage_max_mb
            FROM companies c 
            JOIN plans p ON c.plan_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$company_id]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            return null;
        }
        
        // Obtener conteos actuales
        $stmt = $db->prepare("SELECT COUNT(*) FROM user_companies WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $current_users = $stmt->fetchColumn();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM businesses b 
            JOIN units u ON b.id = u.business_id 
            WHERE u.company_id = ?
        ");
        $stmt->execute([$company_id]);
        $current_businesses = $stmt->fetchColumn();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM units u 
            WHERE u.company_id = ?
        ");
        $stmt->execute([$company_id]);
        $current_units = $stmt->fetchColumn();
        
        return [
            'plan_name' => $plan['name'],
            'users' => [
                'current' => $current_users,
                'max' => $plan['users_max'],
                'unlimited' => $plan['users_max'] <= 0
            ],
            'businesses' => [
                'current' => $current_businesses,
                'max' => $plan['businesses_max'],
                'unlimited' => $plan['businesses_max'] <= 0
            ],
            'units' => [
                'current' => $current_units,
                'max' => $plan['units_max'],
                'unlimited' => $plan['units_max'] <= 0
            ],
            'storage' => [
                'max_mb' => $plan['storage_max_mb'],
                'unlimited' => $plan['storage_max_mb'] <= 0
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error obteniendo límites de plan: " . $e->getMessage());
        return null;
    }
}

function canAccessModule($user_id, $company_id, $module_name) {
    try {
        $db = getDB();
        
        // Obtener nivel del usuario y plan de la empresa
        $stmt = $db->prepare("
            SELECT uc.nivel, p.* 
            FROM user_companies uc
            JOIN companies c ON uc.company_id = c.id
            JOIN plans p ON c.plan_id = p.id
            WHERE uc.user_id = ? AND uc.company_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$user_id, $company_id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        $user_level = $result['nivel'];
        
        // Definir módulos por plan y nivel
        $module_permissions = [
            'gastos' => ['required_level' => 1, 'required_plan' => 'all'],
            'businesses' => ['required_level' => 2, 'required_plan' => 'all'],
            'companies' => ['required_level' => 3, 'required_plan' => 'all'],
            'units' => ['required_level' => 1, 'required_plan' => 'all'],
            'reports' => ['required_level' => 2, 'required_plan' => 'premium'],
            'analytics' => ['required_level' => 2, 'required_plan' => 'premium'],
            'exports' => ['required_level' => 1, 'required_plan' => 'premium']
        ];
        
        if (!isset($module_permissions[$module_name])) {
            return false; // Módulo no definido
        }
        
        $permission = $module_permissions[$module_name];
        
        // Verificar nivel del usuario
        if ($user_level < $permission['required_level']) {
            return false;
        }
        
        // Verificar plan requerido
        if ($permission['required_plan'] !== 'all') {
            $plan_features = json_decode($result['features'], true) ?: [];
            if ($permission['required_plan'] === 'premium' && !in_array('premium_modules', $plan_features)) {
                return false;
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error verificando acceso a módulo: " . $e->getMessage());
        return false;
    }
}

function displayPlanUsage($company_id) {
    $limits = getPlanLimits($company_id);
    if (!$limits) {
        return '<div class="alert alert-warning">No se pudo cargar información del plan</div>';
    }
    
    $html = '<div class="card mt-3">';
    $html .= '<div class="card-header"><i class="fas fa-chart-pie"></i> Uso del Plan: ' . htmlspecialchars($limits['plan_name']) . '</div>';
    $html .= '<div class="card-body">';
    
    // Usuarios
    $users_percent = $limits['users']['unlimited'] ? 0 : ($limits['users']['current'] / max($limits['users']['max'], 1)) * 100;
    $users_class = $users_percent > 80 ? 'bg-danger' : ($users_percent > 60 ? 'bg-warning' : 'bg-success');
    
    $html .= '<div class="mb-3">';
    $html .= '<div class="d-flex justify-content-between">';
    $html .= '<span>Usuarios</span>';
    $html .= '<span>' . $limits['users']['current'] . '/' . ($limits['users']['unlimited'] ? '∞' : $limits['users']['max']) . '</span>';
    $html .= '</div>';
    if (!$limits['users']['unlimited']) {
        $html .= '<div class="progress" style="height: 8px;">';
        $html .= '<div class="progress-bar ' . $users_class . '" style="width: ' . min($users_percent, 100) . '%"></div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    // Negocios
    $businesses_percent = $limits['businesses']['unlimited'] ? 0 : ($limits['businesses']['current'] / max($limits['businesses']['max'], 1)) * 100;
    $businesses_class = $businesses_percent > 80 ? 'bg-danger' : ($businesses_percent > 60 ? 'bg-warning' : 'bg-success');
    
    $html .= '<div class="mb-3">';
    $html .= '<div class="d-flex justify-content-between">';
    $html .= '<span>Negocios</span>';
    $html .= '<span>' . $limits['businesses']['current'] . '/' . ($limits['businesses']['unlimited'] ? '∞' : $limits['businesses']['max']) . '</span>';
    $html .= '</div>';
    if (!$limits['businesses']['unlimited']) {
        $html .= '<div class="progress" style="height: 8px;">';
        $html .= '<div class="progress-bar ' . $businesses_class . '" style="width: ' . min($businesses_percent, 100) . '%"></div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    // Unidades
    $units_percent = $limits['units']['unlimited'] ? 0 : ($limits['units']['current'] / max($limits['units']['max'], 1)) * 100;
    $units_class = $units_percent > 80 ? 'bg-danger' : ($units_percent > 60 ? 'bg-warning' : 'bg-success');
    
    $html .= '<div class="mb-3">';
    $html .= '<div class="d-flex justify-content-between">';
    $html .= '<span>Unidades</span>';
    $html .= '<span>' . $limits['units']['current'] . '/' . ($limits['units']['unlimited'] ? '∞' : $limits['units']['max']) . '</span>';
    $html .= '</div>';
    if (!$limits['units']['unlimited']) {
        $html .= '<div class="progress" style="height: 8px;">';
        $html .= '<div class="progress-bar ' . $units_class . '" style="width: ' . min($units_percent, 100) . '%"></div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    $html .= '</div></div>';
    
    return $html;
}
