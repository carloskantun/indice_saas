<?php
/**
 * Debug de permisos para el módulo de gastos
 */

require_once '../../config.php';

// Verificar autenticación
if (!checkAuth()) {
    echo "❌ Usuario no autenticado<br>";
    exit;
}

echo "<h3>🔍 Debug de Permisos - Módulo Gastos</h3>";

// Mostrar información de sesión
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>📋 Información de Sesión:</h4>";
echo "• User ID: " . ($_SESSION['user_id'] ?? 'No definido') . "<br>";
echo "• User Email: " . ($_SESSION['user_email'] ?? 'No definido') . "<br>";
echo "• Rol Actual: " . ($_SESSION['current_role'] ?? 'No definido') . "<br>";
echo "• Company ID: " . ($_SESSION['company_id'] ?? 'No definido') . "<br>";
echo "• Business ID: " . ($_SESSION['business_id'] ?? 'No definido') . "<br>";
echo "• Unit ID: " . ($_SESSION['unit_id'] ?? 'No definido') . "<br>";
echo "</div>";

// Función para verificar permisos (copia de controller.php)
function hasPermission($permission) {
    if (!checkAuth()) {
        return false;
    }
    
    $role = $_SESSION['current_role'] ?? 'user';
    if (in_array($role, ['root', 'superadmin'])) {
        return true;
    }
    
    $permission_map = [
        'admin' => [
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.pay', 
            'expenses.export', 'expenses.kpis', 'expenses.delete',
            'providers.view', 'providers.create', 'providers.edit', 'providers.delete'
        ],
        'moderator' => [
            'expenses.view', 'expenses.create', 'expenses.pay', 
            'providers.view', 'providers.create'
        ],
        'user' => [
            'expenses.view', 'providers.view'
        ]
    ];
    
    $allowed_permissions = $permission_map[$role] ?? [];
    return in_array($permission, $allowed_permissions);
}

// Lista de permisos a verificar
$permissions_to_check = [
    'expenses.view' => 'Ver gastos',
    'expenses.create' => 'Crear gastos',
    'expenses.edit' => 'Editar gastos',
    'expenses.pay' => 'Registrar pagos',
    'expenses.export' => 'Exportar gastos',
    'expenses.kpis' => 'Ver KPIs',
    'expenses.delete' => 'Eliminar gastos',
    'providers.view' => 'Ver proveedores',
    'providers.create' => 'Crear proveedores',
    'providers.edit' => 'Editar proveedores',
    'providers.delete' => 'Eliminar proveedores'
];

echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>🔐 Permisos del Usuario:</h4>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #d1ecf1;'><th style='padding: 8px; border: 1px solid #bee5eb; text-align: left;'>Permiso</th><th style='padding: 8px; border: 1px solid #bee5eb; text-align: left;'>Descripción</th><th style='padding: 8px; border: 1px solid #bee5eb; text-align: center;'>Estado</th></tr>";

foreach ($permissions_to_check as $permission => $description) {
    $has_permission = hasPermission($permission);
    $status_icon = $has_permission ? '✅' : '❌';
    $status_text = $has_permission ? 'PERMITIDO' : 'DENEGADO';
    $row_color = $has_permission ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background: $row_color;'>";
    echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><code>$permission</code></td>";
    echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>$description</td>";
    echo "<td style='padding: 8px; border: 1px solid #dee2e6; text-align: center;'>$status_icon $status_text</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Verificar acceso a base de datos
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>🗄️ Verificación de Base de Datos:</h4>";

try {
    $db = getDB();
    echo "✅ Conexión a base de datos: <strong>EXITOSA</strong><br>";
    
    // Verificar si el usuario existe en user_companies
    $stmt = $db->prepare("
        SELECT uc.role, c.name as company_name, u.name as user_name, u.email
        FROM user_companies uc
        JOIN users u ON uc.user_id = u.id
        JOIN companies c ON uc.company_id = c.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetchAll();
    
    if ($user_data) {
        echo "✅ Usuario encontrado en user_companies:<br>";
        foreach ($user_data as $data) {
            echo "  • Empresa: <strong>" . htmlspecialchars($data['company_name']) . "</strong><br>";
            echo "  • Rol: <strong>" . htmlspecialchars($data['role']) . "</strong><br>";
            echo "  • Email: <strong>" . htmlspecialchars($data['email']) . "</strong><br>";
        }
    } else {
        echo "❌ Usuario NO encontrado en user_companies<br>";
    }
    
    // Verificar contexto de empresa/negocio
    if ($_SESSION['company_id'] && $_SESSION['business_id']) {
        $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        $company_name = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT name FROM businesses WHERE id = ?");
        $stmt->execute([$_SESSION['business_id']]);
        $business_name = $stmt->fetchColumn();
        
        echo "✅ Contexto de empresa: <strong>" . htmlspecialchars($company_name) . "</strong><br>";
        echo "✅ Contexto de negocio: <strong>" . htmlspecialchars($business_name) . "</strong><br>";
    } else {
        echo "❌ Falta contexto de empresa/negocio<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "<br>";
}

echo "</div>";

echo "<div style='background: #f1f3f4; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>💡 Recomendaciones:</h4>";

$role = $_SESSION['current_role'] ?? 'user';
if ($role === 'user') {
    echo "• Tu rol actual es '<strong>user</strong>' que solo permite ver gastos y proveedores<br>";
    echo "• Para crear proveedores necesitas rol '<strong>moderator</strong>' o superior<br>";
    echo "• Para acceso completo necesitas rol '<strong>admin</strong>' o superior<br>";
} elseif ($role === 'moderator') {
    echo "• Tu rol actual es '<strong>moderator</strong>' que permite crear gastos y proveedores<br>";
    echo "• Para funciones administrativas avanzadas necesitas rol '<strong>admin</strong>'<br>";
} elseif ($role === 'admin') {
    echo "• Tu rol actual es '<strong>admin</strong>' con acceso completo al módulo<br>";
} else {
    echo "• Tu rol actual es '<strong>$role</strong>'<br>";
}

echo "</div>";

echo "<p><a href='index.php' style='color: #007bff;'>← Volver al módulo de gastos</a></p>";
?>
