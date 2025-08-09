<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    die("❌ No autorizado");
}

$business_id = $_GET['business_id'] ?? 1;
$db = getDB();
$user_id = $_SESSION['user_id'];

echo "🔍 DEBUG MÓDULOS PANEL\n";
echo "====================\n\n";

// Verificar contexto del negocio
try {
    $stmt = $db->prepare("
        SELECT b.name as business_name, u.name as unit_name, c.name as company_name, 
               c.id as company_id, u.id as unit_id, uc.role 
        FROM businesses b 
        INNER JOIN units u ON b.unit_id = u.id
        INNER JOIN companies c ON u.company_id = c.id
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? AND b.id = ?
    ");
    $stmt->execute([$user_id, $business_id]);
    $businessData = $stmt->fetch();
    
    if ($businessData) {
        echo "✅ Contexto del negocio:\n";
        echo "- Business ID: $business_id\n";
        echo "- Empresa: " . $businessData['company_name'] . "\n";
        echo "- Unidad: " . $businessData['unit_name'] . "\n";
        echo "- Negocio: " . $businessData['business_name'] . "\n";
        echo "- Tu rol: " . $businessData['role'] . "\n\n";
    } else {
        echo "❌ No se encontró contexto para business_id=$business_id\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo contexto: " . $e->getMessage() . "\n\n";
    exit;
}

// Función hasModuleAccess (copia del index.php)
function hasModuleAccess($moduleSlug, $userRole) {
    // Superadmin y root tienen acceso a todo
    if (in_array($userRole, ['root', 'superadmin', 'superadministrador'])) {
        return true;
    }
    
    // Configuración de acceso por módulo y rol
    $moduleAccess = [
        'expenses' => ['admin', 'moderator', 'user'],
        'human-resources' => ['admin', 'moderator'], // Admin y moderator tienen acceso completo
        'mantenimiento' => ['admin', 'moderator', 'user'],
        'inventario' => ['admin', 'moderator', 'user'],
        'ventas' => ['admin', 'moderator', 'user'],
        'servicio_cliente' => ['admin', 'moderator', 'user']
    ];
    
    $allowedRoles = $moduleAccess[$moduleSlug] ?? ['admin'];
    return in_array($userRole, $allowedRoles);
}

// Función getBootstrapColor (copia del index.php)
function getBootstrapColor($hexColor) {
    $colorMap = [
        '#3498db' => 'primary',
        '#2ecc71' => 'success', 
        '#e74c3c' => 'danger',
        '#f39c12' => 'warning',
        '#9b59b6' => 'info',
        '#34495e' => 'secondary',
        '#1abc9c' => 'info'
    ];
    
    return $colorMap[$hexColor] ?? 'primary';
}

// Obtener módulos desde la base de datos
echo "📊 MÓDULOS DESDE BASE DE DATOS:\n";
echo "-------------------------------\n";

try {
    $stmt = $db->prepare("
        SELECT id, name, slug, description, url, icon, color, status
        FROM modules 
        WHERE status = 'active' 
        AND slug IN ('expenses', 'human-resources', 'mantenimiento', 'inventario', 'ventas', 'servicio_cliente')
        ORDER BY name ASC
    ");
    $stmt->execute();
    $modulesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total módulos activos en BD: " . count($modulesList) . "\n\n";
    
    foreach ($modulesList as $module) {
        echo "🔹 Módulo: " . $module['name'] . "\n";
        echo "   - Slug: " . $module['slug'] . "\n";
        echo "   - URL: " . $module['url'] . "\n";
        echo "   - Icono: " . $module['icon'] . "\n";
        echo "   - Color: " . $module['color'] . "\n";
        echo "   - Estado: " . $module['status'] . "\n";
        
        // Verificar acceso
        $hasAccess = hasModuleAccess($module['slug'], $businessData['role']);
        echo "   - ¿Tienes acceso?: " . ($hasAccess ? "✅ SÍ" : "❌ NO") . "\n";
        
        if ($module['slug'] === 'human-resources') {
            echo "   ⭐ ESTE ES EL MÓDULO HR ⭐\n";
        }
        echo "\n";
    }
    
    // Simular el proceso de conversión del index.php
    echo "🔄 CONVERSIÓN A FORMATO PANEL:\n";
    echo "-----------------------------\n";
    
    $availableModules = [];
    foreach ($modulesList as $module) {
        $hasAccess = hasModuleAccess($module['slug'], $businessData['role']);
        
        if ($hasAccess) {
            // Limpiar URL para evitar duplicación de /modules/
            $cleanUrl = $module['url'];
            if (strpos($cleanUrl, '/modules/') === 0) {
                $cleanUrl = substr($cleanUrl, 9); // Remover '/modules/' del inicio
                echo "   🔧 URL limpiada: " . $module['url'] . " → " . $cleanUrl . "\n";
            }
            $cleanUrl = ltrim($cleanUrl, '/');
            
            $convertedModule = [
                'id' => $module['slug'],
                'name' => $module['name'],
                'description' => $module['description'],
                'icon' => $module['icon'] ?: 'fas fa-puzzle-piece',
                'color' => getBootstrapColor($module['color']),
                'url' => $cleanUrl,
                'active' => true
            ];
            
            $availableModules[] = $convertedModule;
            
            echo "✅ Convertido: " . $module['name'] . "\n";
            echo "   - Color convertido: " . $module['color'] . " → " . $convertedModule['color'] . "\n";
            echo "   - URL final: " . $convertedModule['url'] . "\n";
        } else {
            echo "❌ Sin acceso: " . $module['name'] . " (rol: " . $businessData['role'] . ")\n";
        }
    }
    
    echo "\n📋 MÓDULOS FINALES PARA EL PANEL:\n";
    echo "---------------------------------\n";
    echo "Total módulos disponibles: " . count($availableModules) . "\n\n";
    
    foreach ($availableModules as $i => $module) {
        echo ($i + 1) . ". " . $module['name'] . "\n";
        echo "   - ID: " . $module['id'] . "\n";
        echo "   - Descripción: " . $module['description'] . "\n";
        echo "   - Icono: " . $module['icon'] . "\n";
        echo "   - Color: " . $module['color'] . "\n";
        echo "   - URL: " . $module['url'] . "\n";
        echo "   - Activo: " . ($module['active'] ? 'Sí' : 'No') . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error obteniendo módulos: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
