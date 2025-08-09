<?php
require_once '../../config.php';
require_once 'includes/invitation_functions.php';

echo "🧪 TEST DE DETECCIÓN DE USUARIO CORREGIDO\n";
echo "==========================================\n\n";

if (!checkAuth()) {
    echo "❌ No autorizado\n";
    exit;
}

echo "📧 Probando detección con carlosadmin@indiceapp.com:\n";
echo "---------------------------------------------------\n";

$test_email = 'carlosadmin@indiceapp.com';
$detection = detectExistingUser($test_email);

if ($detection['exists']) {
    echo "✅ USUARIO DETECTADO EXITOSAMENTE!\n";
    echo "- User ID: " . $detection['user_id'] . "\n";
    echo "- Nombre: " . $detection['name'] . "\n";
    echo "- Email: " . $detection['email'] . "\n";
    echo "- Estado: " . $detection['status'] . "\n";
    echo "- Empresas vinculadas: " . $detection['companies_count'] . "\n\n";
    
    // Test de vinculación
    echo "🔗 Probando vinculación a empresa:\n";
    echo "----------------------------------\n";
    
    $link_result = linkExistingUserToCompany($detection['user_id'], 1, 'admin', ['human-resources']);
    
    if ($link_result) {
        echo "✅ Vinculación exitosa\n";
    } else {
        echo "❌ Error en vinculación\n";
    }
    
} else {
    echo "❌ Usuario NO detectado\n";
    if (isset($detection['error'])) {
        echo "Error: " . $detection['error'] . "\n";
    }
}

echo "\n🧪 TEST COMPLETO DE CREACIÓN CON INVITACIÓN:\n";
echo "=============================================\n";

// Simular datos del formulario
$test_data = [
    'first_name' => 'Carlos',
    'last_name' => 'Test',
    'email' => 'carlosadmin@indiceapp.com',
    'phone' => '555-0000',
    'department_id' => 1,
    'position_id' => 1,
    'salary' => 50000,
    'hire_date' => date('Y-m-d'),
    'salary_frequency' => 'Mensual',
    'assigned_modules' => ['human-resources'],
    'system_role' => 'admin'
];

echo "Datos de prueba:\n";
foreach ($test_data as $key => $value) {
    echo "- $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
}

echo "\nProceso completo:\n";
echo "1. Detectar usuario existente...\n";
$existing = detectExistingUser($test_data['email']);

if ($existing['exists']) {
    echo "   ✅ Usuario encontrado: " . $existing['name'] . "\n";
    echo "2. Vincular a empresa...\n";
    $link = linkExistingUserToCompany($existing['user_id'], 1, $test_data['system_role']);
    echo "   " . ($link ? "✅" : "❌") . " Vinculación " . ($link ? "exitosa" : "fallida") . "\n";
} else {
    echo "   ❌ Usuario no encontrado, se crearía invitación\n";
}

echo "\n🎉 TEST COMPLETADO\n";
echo "==================\n";
echo "El sistema de detección ahora debería funcionar correctamente.\n";
echo "Puedes probar crear un empleado en el módulo HR.\n";
?>
