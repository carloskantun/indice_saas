<?php
/**
 * DIAGNÓSTICO COMPLETO DEL MÓDULO HUMAN RESOURCES
 * Sistema SaaS Indice
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "📊 DIAGNÓSTICO HUMAN RESOURCES\n";
    echo "==============================\n\n";
    
    // 1. Verificar módulo registrado
    echo "1️⃣  MÓDULO REGISTRADO:\n";
    echo "---------------------\n";
    $stmt = $db->query("SELECT * FROM modules WHERE slug = 'human-resources'");
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($module) {
        echo "✅ Módulo encontrado:\n";
        echo "   ID: {$module['id']}\n";
        echo "   Nombre: {$module['name']}\n";
        echo "   Slug: {$module['slug']}\n";
        echo "   Estado: {$module['status']}\n";
        echo "   Icono: {$module['icon']}\n";
        echo "   Color: {$module['color']}\n";
        echo "   Orden: {$module['order']}\n";
    } else {
        echo "❌ Módulo NO encontrado\n";
    }
    
    echo "\n";
    
    // 2. Verificar permisos
    echo "2️⃣  PERMISOS DEL MÓDULO:\n";
    echo "------------------------\n";
    
    // Primero verificar estructura de la tabla permissions
    $stmt = $db->query("DESCRIBE permissions");
    $permColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "📋 Estructura de tabla permissions:\n";
    foreach ($permColumns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Buscar permisos relacionados con HR (por nombre)
    $stmt = $db->query("SELECT * FROM permissions WHERE key_name LIKE '%employees%' OR key_name LIKE '%departments%' OR key_name LIKE '%positions%' ORDER BY key_name");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($permissions) {
        echo "✅ Permisos encontrados (" . count($permissions) . "):\n";
        foreach ($permissions as $perm) {
            $desc = isset($perm['description']) ? $perm['description'] : (isset($perm['permission_name']) ? $perm['permission_name'] : 'Sin descripción');
            echo "   - {$perm['key_name']}: $desc\n";
        }
    } else {
        echo "❌ No se encontraron permisos\n";
    }
    
    echo "\n";
    
    // 3. Verificar asignación de permisos a roles
    echo "3️⃣  ASIGNACIÓN A ROLES:\n";
    echo "-----------------------\n";
    
    // Primero verificar si existe la tabla roles
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'roles'");
        if (!$stmt->fetch()) {
            echo "❌ Tabla 'roles' no existe\n";
            
            // Verificar qué tablas relacionadas con roles existen
            echo "🔍 Verificando tablas del sistema de roles...\n";
            $roleTables = ['roles', 'role_permissions', 'user_roles', 'users'];
            foreach ($roleTables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->fetch()) {
                    $stmt = $db->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    echo "   ✅ $table: $count registros\n";
                } else {
                    echo "   ❌ $table: NO EXISTE\n";
                }
            }
        } else {
            // Si existe la tabla roles, hacer la consulta normal
            $stmt = $db->query("
                SELECT r.role_name, COUNT(rp.permission_id) as permisos_asignados
                FROM roles r
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                LEFT JOIN permissions p ON rp.permission_id = p.id
                WHERE (p.key_name LIKE '%employees%' OR p.key_name LIKE '%departments%' OR p.key_name LIKE '%positions%' OR p.key_name IS NULL)
                GROUP BY r.id, r.role_name
                ORDER BY r.id
            ");
            $rolePerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rolePerms as $role) {
                if ($role['permisos_asignados'] > 0) {
                    echo "✅ {$role['role_name']}: {$role['permisos_asignados']} permisos\n";
                } else {
                    echo "❌ {$role['role_name']}: 0 permisos\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Error verificando roles: " . $e->getMessage() . "\n";
        
        // Mostrar estructura de la base de datos
        echo "🔍 Listando todas las tablas de la base de datos...\n";
        try {
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "📋 Tablas existentes (" . count($tables) . "):\n";
            foreach ($tables as $table) {
                echo "   - $table\n";
            }
        } catch (Exception $e2) {
            echo "   Error listando tablas: " . $e2->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // 4. Verificar tablas
    echo "4️⃣  TABLAS DE LA BASE DE DATOS:\n";
    echo "--------------------------------\n";
    $tables = ['departments', 'positions', 'employees'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count registros\n";
        } else {
            echo "❌ $table: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 5. Verificar estructura de tablas
    echo "5️⃣  ESTRUCTURA DE TABLAS:\n";
    echo "-------------------------\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "📋 $table (" . count($columns) . " columnas):\n";
            foreach ($columns as $col) {
                echo "   - {$col['Field']} ({$col['Type']})\n";
            }
            echo "\n";
        } catch (Exception $e) {
            echo "❌ $table: Error al obtener estructura\n\n";
        }
    }
    
    // 6. Verificar triggers
    echo "6️⃣  TRIGGERS:\n";
    echo "-------------\n";
    try {
        $stmt = $db->query("SHOW TRIGGERS LIKE 'employees'");
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($triggers) {
            foreach ($triggers as $trigger) {
                echo "✅ {$trigger['Trigger']}\n";
                echo "   Tabla: {$trigger['Table']}\n";
                echo "   Evento: {$trigger['Event']}\n";
                echo "   Timing: {$trigger['Timing']}\n";
            }
        } else {
            echo "❌ No hay triggers para la tabla employees\n";
        }
    } catch (Exception $e) {
        echo "❌ Error verificando triggers: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 7. Verificar archivos del módulo
    echo "7️⃣  ARCHIVOS DEL MÓDULO:\n";
    echo "------------------------\n";
    $moduleFiles = [
        'modules/human-resources/index.php',
        'modules/human-resources/controller.php',
        'modules/human-resources/config.php',
        'modules/human-resources/modals.php',
        'modules/human-resources/css/style.css',
        'modules/human-resources/js/script.js'
    ];
    
    foreach ($moduleFiles as $file) {
        if (file_exists($file)) {
            $size = filesize($file);
            echo "✅ $file (" . number_format($size) . " bytes)\n";
        } else {
            echo "❌ $file: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 8. Resumen final
    echo "📋 RESUMEN:\n";
    echo "===========\n";
    
    $issues = [];
    
    if (!$module) $issues[] = "Módulo no registrado";
    if (!$permissions) $issues[] = "Sin permisos";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            $issues[] = "Tabla $table no existe";
        }
    }
    
    foreach ($moduleFiles as $file) {
        if (!file_exists($file)) {
            $issues[] = "Archivo $file no existe";
        }
    }
    
    if (empty($issues)) {
        echo "🎉 ¡TODO PERFECTO!\n";
        echo "El módulo Human Resources está completamente instalado y listo para usar.\n";
        echo "\n🔗 Accede en: /modules/human-resources/\n";
    } else {
        echo "⚠️  PROBLEMAS ENCONTRADOS:\n";
        foreach ($issues as $issue) {
            echo "   - $issue\n";
        }
        echo "\n💡 Ejecuta los scripts de instalación para corregir estos problemas.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR EN DIAGNÓSTICO:\n";
    echo "========================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
