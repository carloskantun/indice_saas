<?php
require_once 'config.php';

// Verificar autenticación
if (!checkAuth()) {
    die("❌ No estás autenticado");
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$business_id = $_GET['business_id'] ?? 1;

echo "🔍 DEBUG: ROL DEL USUARIO\n";
echo "========================\n\n";

echo "👤 Usuario en sesión:\n";
echo "- ID: " . $_SESSION['user_id'] . "\n";
echo "- Nombre: " . $_SESSION['user_name'] . "\n";
echo "- Email: " . ($_SESSION['user_email'] ?? 'No disponible') . "\n\n";

echo "🏢 Información del negocio:\n";
echo "- Business ID solicitado: $business_id\n\n";

// Verificar rol del usuario para este negocio
try {
    $stmt = $db->prepare("
        SELECT b.name as business_name, u.name as unit_name, c.name as company_name, 
               c.id as company_id, u.id as unit_id, uc.role, uc.id as user_company_id
        FROM businesses b 
        INNER JOIN units u ON b.unit_id = u.id
        INNER JOIN companies c ON u.company_id = c.id
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? AND b.id = ?
    ");
    $stmt->execute([$user_id, $business_id]);
    $businessData = $stmt->fetch();
    
    if ($businessData) {
        echo "✅ Acceso encontrado:\n";
        echo "- Empresa: " . $businessData['company_name'] . " (ID: " . $businessData['company_id'] . ")\n";
        echo "- Unidad: " . $businessData['unit_name'] . " (ID: " . $businessData['unit_id'] . ")\n";
        echo "- Negocio: " . $businessData['business_name'] . "\n";
        echo "- Tu rol: **" . $businessData['role'] . "**\n";
        echo "- User-Company ID: " . $businessData['user_company_id'] . "\n\n";
        
        // Verificar acceso a HR
        $role = $businessData['role'];
        $hasAccess = in_array($role, ['root', 'superadmin', 'admin', 'moderator']);
        
        echo "🔐 Acceso a Human Resources:\n";
        echo "- Rol requerido: admin, moderator, superadmin o root\n";
        echo "- Tu rol: $role\n";
        echo "- ¿Tienes acceso?: " . ($hasAccess ? "✅ SÍ" : "❌ NO") . "\n\n";
        
    } else {
        echo "❌ No se encontró acceso para business_id=$business_id\n\n";
        
        // Mostrar todos los accesos del usuario
        echo "📋 Todos tus accesos:\n";
        $stmt = $db->prepare("
            SELECT c.name as company_name, uc.role, uc.company_id
            FROM user_companies uc
            INNER JOIN companies c ON uc.company_id = c.id
            WHERE uc.user_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$user_id]);
        $allAccess = $stmt->fetchAll();
        
        foreach ($allAccess as $access) {
            echo "- " . $access['company_name'] . " (ID: " . $access['company_id'] . ") - Rol: " . $access['role'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n📊 Módulos disponibles en BD:\n";
try {
    $stmt = $db->prepare("SELECT name, slug, status FROM modules ORDER BY name");
    $stmt->execute();
    $modules = $stmt->fetchAll();
    
    foreach ($modules as $module) {
        echo "- " . $module['name'] . " (" . $module['slug'] . ") - Estado: " . $module['status'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo módulos: " . $e->getMessage() . "\n";
}
?>
