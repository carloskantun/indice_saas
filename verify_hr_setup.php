<?php
/**
 * VERIFICACIÓN COMPLETA DEL MÓDULO HR
 * Verifica que todo esté correctamente configurado
 */

require_once 'config.php';

echo "🔍 VERIFICACIÓN MÓDULO HUMAN RESOURCES\n";
echo "=====================================\n\n";

try {
    $db = getDB();
    
    // 1. Verificar módulo en BD
    echo "1️⃣  MÓDULO EN BASE DE DATOS:\n";
    echo "----------------------------\n";
    
    $stmt = $db->query("SELECT * FROM modules WHERE slug = 'human-resources' OR name LIKE '%Recursos Humanos%'");
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($module) {
        echo "✅ Módulo encontrado:\n";
        echo "   ID: {$module['id']}\n";
        echo "   Nombre: {$module['name']}\n";
        echo "   Slug: {$module['slug']}\n";
        echo "   URL: {$module['url']}\n";
        echo "   Estado: {$module['status']}\n";
        echo "   Icono: {$module['icon']}\n";
        echo "   Color: {$module['color']}\n";
    } else {
        echo "❌ Módulo NO encontrado en BD\n";
        echo "   💡 Ejecutar: php add_human_resources_module.php\n";
    }
    
    echo "\n";
    
    // 2. Verificar tablas HR
    echo "2️⃣  TABLAS DEL MÓDULO:\n";
    echo "----------------------\n";
    
    $hr_tables = ['departments', 'positions', 'employees', 'invitations', 'user_companies'];
    foreach ($hr_tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   ✅ $table: $count registros\n";
        } else {
            echo "   ❌ $table: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 3. Verificar permisos
    echo "3️⃣  PERMISOS DEL MÓDULO:\n";
    echo "------------------------\n";
    
    // Verificar qué campo usa la tabla permissions
    $stmt = $db->query("DESCRIBE permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $keyField = 'key_name'; // Por defecto
    
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['key', 'key_name', 'permission_key'])) {
            $keyField = $col['Field'];
            break;
        }
    }
    
    echo "   Campo clave: $keyField\n";
    
    $stmt = $db->query("SELECT $keyField, description FROM permissions WHERE $keyField LIKE '%employees%' OR $keyField LIKE '%departments%' OR $keyField LIKE '%hr.%' ORDER BY $keyField");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($permissions) {
        echo "   ✅ Permisos encontrados (" . count($permissions) . "):\n";
        foreach ($permissions as $perm) {
            echo "      - {$perm[$keyField]}: {$perm['description']}\n";
        }
    } else {
        echo "   ❌ No hay permisos registrados\n";
    }
    
    echo "\n";
    
    // 4. Verificar archivos físicos
    echo "4️⃣  ARCHIVOS DEL MÓDULO:\n";
    echo "------------------------\n";
    
    $files_to_check = [
        'modules/human-resources/index.php',
        'modules/human-resources/controller.php',
        'modules/human-resources/modals.php',
        'modules/human-resources/js/human-resources.js',
        'modules/human-resources/includes/invitation_system.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $size = number_format(filesize($file));
            echo "   ✅ $file ($size bytes)\n";
        } else {
            echo "   ❌ $file: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 5. Verificar configuración del sistema
    echo "5️⃣  CONFIGURACIÓN DEL SISTEMA:\n";
    echo "-------------------------------\n";
    
    echo "   Base de datos: " . DB_NAME . "\n";
    echo "   Host: " . DB_HOST . "\n";
    
    // Verificar que el sistema principal funcione
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "   Usuarios totales: $user_count\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM companies");
    $company_count = $stmt->fetchColumn();
    echo "   Empresas totales: $company_count\n";
    
    echo "\n";
    
    // 6. Prueba de acceso
    echo "6️⃣  PRUEBA DE ACCESO:\n";
    echo "---------------------\n";
    
    $url_to_test = "https://app.indiceapp.com/modules/human-resources/";
    echo "   URL del módulo: $url_to_test\n";
    
    // Verificar si está en un servidor web
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "   ✅ Ejecutándose en servidor web\n";
        echo "   Host actual: {$_SERVER['HTTP_HOST']}\n";
    } else {
        echo "   💡 Ejecutándose en línea de comandos\n";
    }
    
    echo "\n";
    
    // 7. Próximos pasos
    echo "7️⃣  PRÓXIMOS PASOS:\n";
    echo "-------------------\n";
    
    if (!$module) {
        echo "   🔧 1. Ejecutar: php add_human_resources_module.php\n";
    }
    
    if (!file_exists('modules/human-resources/index.php')) {
        echo "   🔧 2. Subir archivos del módulo al servidor\n";
    }
    
    echo "   🌐 3. Probar acceso desde navegador\n";
    echo "   👤 4. Crear empleado de prueba con invitación\n";
    echo "   ⚙️  5. Verificar sistema de permisos\n";
    
    echo "\n🎉 VERIFICACIÓN COMPLETADA\n";
    echo "==========================\n";
    
} catch (Exception $e) {
    echo "❌ Error durante verificación: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
