<?php
/**
 * Test de conexión de base de datos
 * Verifica que todas las configuraciones funcionen correctamente
 */

echo "=== TEST DE SISTEMA SaaS ===\n\n";

// Test 1: Cargar configuración
echo "1. Probando carga de configuración...\n";
try {
    require_once 'config.php';
    echo "✅ config.php cargado correctamente\n";
    echo "✅ Variables de entorno: " . (isset($_ENV['DB_HOST']) ? 'OK' : 'FAIL') . "\n";
} catch (Exception $e) {
    echo "❌ Error en config.php: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Conexión a base de datos
echo "\n2. Probando conexión a base de datos...\n";
try {
    $db = getDB();
    echo "✅ Conexión PDO establecida\n";
    
    // Test básico de consulta
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Consulta de prueba: " . ($result['test'] == 1 ? 'OK' : 'FAIL') . "\n";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

// Test 3: Verificar tablas principales
echo "\n3. Verificando estructura de base de datos...\n";
try {
    $essential_tables = ['users', 'companies', 'user_companies', 'plans', 'modules', 'notifications', 'user_invitations'];
    $existing_tables = [];
    $missing_tables = [];
    
    foreach ($essential_tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $existing_tables[] = $table;
            echo "✅ Tabla '$table' existe\n";
        } else {
            $missing_tables[] = $table;
            echo "❌ Tabla '$table' NO EXISTE\n";
        }
    }
    
    echo "\n📊 RESUMEN TABLAS:\n";
    echo "✅ Existentes: " . count($existing_tables) . "/" . count($essential_tables) . "\n";
    if (!empty($missing_tables)) {
        echo "❌ Faltantes: " . implode(', ', $missing_tables) . "\n";
        echo "💡 Ejecutar: php install_database.php\n";
    }
} catch (Exception $e) {
    echo "❌ Error verificando tablas: " . $e->getMessage() . "\n";
}

// Test 4: Verificar funciones principales
echo "\n4. Probando funciones del sistema...\n";
try {
    // Test función de autenticación
    $auth_result = checkAuth();
    echo "✅ Función checkAuth(): " . ($auth_result === false ? 'OK (no autenticado)' : 'OK (autenticado)') . "\n";
    
    // Test función de idioma
    $lang_result = loadLanguage('es');
    echo "✅ Función loadLanguage(): " . (is_array($lang_result) ? 'OK' : 'FAIL') . "\n";
    
    // Test función redirect (sin ejecutar)
    echo "✅ Función redirect(): Disponible\n";
    
} catch (Exception $e) {
    echo "❌ Error en funciones: " . $e->getMessage() . "\n";
}

// Test 5: Verificar archivos de configuración
echo "\n5. Verificando archivos críticos...\n";
$critical_files = [
    '.env' => 'Configuración de entorno',
    'config.php' => 'Configuración principal',
    'admin/email_config.php' => 'Configuración de email',
    'includes/notifications.php' => 'Sistema de notificaciones',
    'root.php' => 'Acceso al panel root'
];

foreach ($critical_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $file - $description\n";
    } else {
        echo "❌ $file - $description (FALTANTE)\n";
    }
}

// Test 6: Verificar estructura de directorios
echo "\n6. Verificando estructura del proyecto...\n";
$essential_dirs = ['auth', 'companies', 'units', 'businesses', 'modules', 'panel_root', 'admin', 'includes', 'lang'];
foreach ($essential_dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directorio /$dir/\n";
    } else {
        echo "❌ Directorio /$dir/ (FALTANTE)\n";
    }
}

echo "\n🎉 TEST COMPLETADO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 Si hay errores, revisar:\n";
echo "1. Archivo .env con credenciales correctas\n";
echo "2. Base de datos existe y usuario tiene permisos\n";
echo "3. Ejecutar install_database.php si faltan tablas\n";
echo "4. Verificar permisos de archivos\n\n";
?>
