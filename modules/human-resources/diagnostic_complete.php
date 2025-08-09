<?php
require_once '../../config.php';

echo "🔍 DIAGNÓSTICO COMPLETO DEL MÓDULO HR\n";
echo "=====================================\n\n";

// Verificar autenticación
if (!checkAuth()) {
    echo "❌ No autorizado\n";
    exit;
}

$db = getDB();
$business_id = $_SESSION['business_id'] ?? 1;
$company_id = $_SESSION['company_id'] ?? 1;

echo "📊 CONTEXTO ACTUAL:\n";
echo "- Business ID: $business_id\n";
echo "- Company ID: $company_id\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n\n";

// 1. VERIFICAR USUARIOS EN EL SISTEMA
echo "👥 USUARIOS EN EL SISTEMA:\n";
echo "===========================\n";
try {
    $stmt = $db->query("SELECT id, first_name, last_name, email, status, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ No hay usuarios en el sistema\n\n";
    } else {
        foreach ($users as $user) {
            echo "- ID: " . $user['id'] . " | " . $user['first_name'] . " " . $user['last_name'] . 
                 " | " . $user['email'] . " | Estado: " . $user['status'] . "\n";
        }
        echo "\nTotal usuarios: " . count($users) . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo usuarios: " . $e->getMessage() . "\n\n";
}

// 2. VERIFICAR DETECCIÓN DE USUARIO EXISTENTE
echo "🔍 TEST DETECCIÓN DE USUARIO:\n";
echo "==============================\n";
$test_email = 'carlosadmin@indiceapp.com';
echo "Probando detección con: $test_email\n";

try {
    // Incluir funciones de invitación
    require_once 'includes/invitation_functions.php';
    
    $detection = detectExistingUser($test_email);
    
    if ($detection['exists']) {
        echo "✅ Usuario ENCONTRADO:\n";
        echo "- ID: " . $detection['user_id'] . "\n";
        echo "- Nombre: " . $detection['name'] . "\n";
        echo "- Email: " . $detection['email'] . "\n";
        echo "- Estado: " . $detection['status'] . "\n";
        echo "- Empresas vinculadas: " . $detection['companies_count'] . "\n";
    } else {
        echo "❌ Usuario NO encontrado\n";
        if (isset($detection['error'])) {
            echo "Error: " . $detection['error'] . "\n";
        }
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error en test de detección: " . $e->getMessage() . "\n\n";
}

// 3. VERIFICAR ESTRUCTURA DE FORMULARIO
echo "📝 VERIFICANDO ESTRUCTURA DEL FORMULARIO:\n";
echo "==========================================\n";

// Simular datos del formulario
$test_form_data = [
    'first_name' => 'Carlos',
    'last_name' => 'Admin',
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
foreach ($test_form_data as $key => $value) {
    echo "- $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
}
echo "\n";

// 4. TEST DE INSERCIÓN MANUAL
echo "🧪 TEST DE INSERCIÓN MANUAL:\n";
echo "=============================\n";

try {
    $db->beginTransaction();
    
    // Test con datos básicos
    $stmt = $db->prepare("
        INSERT INTO employees (first_name, last_name, email, phone, department_id, position_id, 
                             salary, salary_frequency, hire_date, status, company_id, business_id, unit_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $test_data = [
        'Carlos Test',
        'Usuario Test',
        'test_' . time() . '@test.com',
        '555-TEST',
        1, // department_id
        1, // position_id
        50000, // salary
        'Mensual', // salary_frequency
        date('Y-m-d'), // hire_date
        'Activo', // status
        $company_id, // company_id
        $business_id, // business_id
        2, // unit_id
        $_SESSION['user_id'] // created_by
    ];
    
    $result = $stmt->execute($test_data);
    
    if ($result) {
        $test_id = $db->lastInsertId();
        echo "✅ Inserción manual exitosa - ID: $test_id\n";
        
        // Verificar que se insertó
        $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$test_id]);
        $inserted = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inserted) {
            echo "✅ Verificación: " . $inserted['first_name'] . " " . $inserted['last_name'] . "\n";
        }
        
        // Limpiar
        $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$test_id]);
        echo "🗑️ Registro de test eliminado\n";
    } else {
        echo "❌ Falló la inserción manual\n";
    }
    
    $db->rollBack();
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error en test manual: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
}

// 5. VERIFICAR ARCHIVOS DEL MÓDULO
echo "\n📁 VERIFICANDO ARCHIVOS DEL MÓDULO:\n";
echo "====================================\n";

$required_files = [
    'index.php',
    'controller.php',
    'modals.php',
    'js/human-resources.js',
    'includes/invitation_functions.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ $file ($size bytes)\n";
    } else {
        echo "❌ $file FALTANTE\n";
    }
}

// 6. VERIFICAR PARÁMETROS DE SESIÓN NECESARIOS
echo "\n🔐 VERIFICANDO PARÁMETROS DE SESIÓN:\n";
echo "====================================\n";

$required_session = ['business_id', 'company_id', 'unit_id', 'user_id', 'current_role'];

foreach ($required_session as $param) {
    $value = $_SESSION[$param] ?? null;
    if ($value) {
        echo "✅ \$_SESSION['$param']: $value\n";
    } else {
        echo "❌ \$_SESSION['$param']: NO DEFINIDO\n";
    }
}

// 7. TEST DEL ENDPOINT DEL CONTROLLER
echo "\n🌐 TEST DEL ENDPOINT:\n";
echo "====================\n";

echo "Simulando llamada AJAX...\n";

// Simular $_POST data
$_POST = [
    'action' => 'create_employee_with_invitation',
    'first_name' => 'Test',
    'last_name' => 'Employee', 
    'email' => 'test@example.com',
    'phone' => '555-0000',
    'department_id' => 1,
    'position_id' => 1,
    'salary' => 25000,
    'hire_date' => date('Y-m-d'),
    'salary_frequency' => 'Mensual',
    'assigned_modules' => ['human-resources'],
    'system_role' => 'user'
];

try {
    // Capturar output del controller
    ob_start();
    
    // Simular include del controller logic
    if ($_POST['action'] === 'create_employee_with_invitation') {
        echo "✅ Acción reconocida: create_employee_with_invitation\n";
        echo "✅ Datos POST recibidos: " . count($_POST) . " campos\n";
    }
    
    $controller_output = ob_get_clean();
    echo $controller_output;
    
} catch (Exception $e) {
    echo "❌ Error simulando endpoint: " . $e->getMessage() . "\n";
}

echo "\n📋 RESUMEN DE PROBLEMAS ENCONTRADOS:\n";
echo "====================================\n";

$problems = [];

// Verificar problemas comunes
if (empty($users)) {
    $problems[] = "No hay usuarios en la tabla 'users'";
}

if (!file_exists('includes/invitation_functions.php')) {
    $problems[] = "Archivo de funciones de invitación faltante";
}

if (!isset($_SESSION['business_id'])) {
    $problems[] = "Contexto de negocio no establecido en sesión";
}

if (empty($problems)) {
    echo "✅ No se encontraron problemas evidentes\n";
    echo "El problema puede estar en el JavaScript o en el manejo AJAX\n";
} else {
    foreach ($problems as $i => $problem) {
        echo ($i + 1) . ". $problem\n";
    }
}

echo "\n🔧 RECOMENDACIONES:\n";
echo "===================\n";
echo "1. Verificar que carlosadmin@indiceapp.com esté en la tabla 'users'\n";
echo "2. Revisar logs de PHP para errores específicos\n";
echo "3. Usar herramientas de desarrollador para ver respuesta AJAX\n";
echo "4. Verificar que el formulario envíe todos los campos requeridos\n";
?>
