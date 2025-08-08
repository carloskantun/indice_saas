<?php
// Test de conexión y funcionalidad básica
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Backend Expenses</h1>";

// Verificar inclusión de archivos
$config_path = '../../config.php';
if (file_exists($config_path)) {
    echo "✅ Config.php encontrado<br>";
    include_once $config_path;
} else {
    echo "❌ Config.php NO encontrado en: $config_path<br>";
}

// Verificar conexión a base de datos
if (isset($db)) {
    echo "✅ Conexión a base de datos disponible<br>";
    try {
        $stmt = $db->query("SELECT 1");
        echo "✅ Base de datos responde correctamente<br>";
    } catch (Exception $e) {
        echo "❌ Error en base de datos: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Variable \$db no definida<br>";
}

// Verificar sesión
session_start();
if (isset($_SESSION['company_id'])) {
    echo "✅ Sesión activa - Company ID: " . $_SESSION['company_id'] . "<br>";
} else {
    echo "❌ No hay sesión activa<br>";
    echo "Variables de sesión disponibles:<br>";
    print_r($_SESSION);
}

// Test de POST simulado
echo "<h2>Test de Endpoints</h2>";
$_POST['action'] = 'create_provider';
$_POST['name'] = 'Test Provider';
$_POST['email'] = 'test@test.com';

// Incluir el controller para probar
$controller_path = 'controller.php';
if (file_exists($controller_path)) {
    echo "✅ Controller.php encontrado<br>";
    
    // Capturar la salida
    ob_start();
    try {
        include $controller_path;
    } catch (Exception $e) {
        echo "❌ Error en controller: " . $e->getMessage() . "<br>";
    }
    $output = ob_get_clean();
    
    echo "<h3>Salida del Controller:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "❌ Controller.php NO encontrado<br>";
}
?>
