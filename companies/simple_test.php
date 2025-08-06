<?php
// Test simple para companies
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuración principal
require_once '../config.php';

echo "=== SIMPLE COMPANIES TEST ===\n\n";

// 1. Config
echo "1. Cargando config... ";
echo "✅ OK\n";

// 2. Verificar variables
echo "2. Verificando variables globales... ";
if (isset($lang) && is_array($lang)) {
    echo "✅ \$lang OK (keys: " . count($lang) . ")\n";
} else {
    echo "❌ \$lang no está definido o no es array\n";
    var_dump($lang);
}

// 3. Session  
echo "3. Verificando sesión... ";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sesión activa\n";
    echo "   User ID: " . ($_SESSION['user_id'] ?? 'No definido') . "\n";
    echo "   User Name: " . ($_SESSION['user_name'] ?? 'No definido') . "\n";
    echo "   Current Role: " . ($_SESSION['current_role'] ?? 'No definido') . "\n";
    echo "   Current Company ID: " . ($_SESSION['current_company_id'] ?? 'No definido') . "\n";
    echo "   Current Company Name: " . ($_SESSION['current_company_name'] ?? 'No definido') . "\n";
    
    // Mostrar todas las claves de sesión para debug
    echo "   Claves de sesión disponibles: " . implode(', ', array_keys($_SESSION)) . "\n";
} else {
    echo "❌ No hay sesión activa\n";
}

// 4. Auth
echo "4. Verificando autenticación... ";
if (function_exists('checkAuth')) {
    if (checkAuth()) {
        echo "✅ Usuario autenticado\n";
    } else {
        echo "❌ Usuario NO autenticado\n";
    }
} else {
    echo "❌ Función checkAuth no existe\n";
}

// 5. DB
echo "5. Verificando base de datos... ";
try {
    $db = getDB();
    echo "✅ Conexión exitosa\n";
    
    // Test query
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Tablas encontradas: " . count($tables) . "\n";
    
    if (in_array('companies', $tables)) {
        echo "   ✅ Tabla companies existe\n";
    } else {
        echo "   ❌ Tabla companies NO existe\n";
    }
    
    if (in_array('user_companies', $tables)) {
        echo "   ✅ Tabla user_companies existe\n";
    } else {
        echo "   ❌ Tabla user_companies NO existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";
echo "<br><a href='index.php'>Ir a companies normal</a>\n";
echo "<br><a href='debug.php'>Ir a debug completo</a>\n";
?>
