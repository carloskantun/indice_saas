<?php
/**
 * CONFIGURAR PERMISOS HR EN SISTEMA EXISTENTE
 * Adapta el módulo HR a la estructura de permisos actual
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "🔧 CONFIGURANDO PERMISOS HR EN SISTEMA EXISTENTE\n";
    echo "================================================\n\n";
    
    // 1. Verificar estructura actual
    echo "1️⃣  VERIFICANDO ESTRUCTURA ACTUAL:\n";
    echo "-----------------------------------\n";
    
    // Verificar tablas existentes del sistema
    $systemTables = ['users', 'user_companies', 'permissions', 'role_permissions'];
    $existingTables = [];
    
    foreach ($systemTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count registros\n";
            $existingTables[] = $table;
        } else {
            echo "❌ $table: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 2. Verificar estructura de role_permissions
    echo "2️⃣  ESTRUCTURA DE ROLE_PERMISSIONS:\n";
    echo "------------------------------------\n";
    
    if (in_array('role_permissions', $existingTables)) {
        $stmt = $db->query("DESCRIBE role_permissions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 Columnas encontradas:\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
        
        // Verificar si tiene permisos de HR
        echo "\n🔍 Permisos HR existentes en role_permissions:\n";
        $stmt = $db->query("SELECT DISTINCT role FROM role_permissions WHERE permission_key LIKE '%employees%' OR permission_key LIKE '%departments%' OR permission_key LIKE '%positions%'");
        $rolesWithHR = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($rolesWithHR)) {
            echo "✅ Roles con permisos HR: " . implode(', ', $rolesWithHR) . "\n";
        } else {
            echo "❌ No hay roles con permisos HR asignados\n";
        }
    }
    
    echo "\n";
    
    // 3. Asignar permisos HR al sistema existente
    echo "3️⃣  ASIGNANDO PERMISOS HR:\n";
    echo "---------------------------\n";
    
    // Verificar qué columna usa role_permissions
    $stmt = $db->query("DESCRIBE role_permissions");
    $roleColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasPermissionKey = in_array('permission_key', $roleColumns);
    $hasPermissionId = in_array('permission_id', $roleColumns);
    $hasRole = in_array('role', $roleColumns);
    
    echo "📋 Estructura detectada:\n";
    echo "   - permission_key: " . ($hasPermissionKey ? "✅" : "❌") . "\n";
    echo "   - permission_id: " . ($hasPermissionId ? "✅" : "❌") . "\n";
    echo "   - role: " . ($hasRole ? "✅" : "❌") . "\n";
    
    // Obtener permisos HR de la tabla permissions
    $stmt = $db->query("SELECT id, key_name FROM permissions WHERE key_name LIKE '%employees%' OR key_name LIKE '%departments%' OR key_name LIKE '%positions%'");
    $hrPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Permisos HR encontrados: " . count($hrPermissions) . "\n";
    foreach ($hrPermissions as $perm) {
        echo "   - {$perm['key_name']} (ID: {$perm['id']})\n";
    }
    
    // Roles del sistema y sus niveles de acceso
    $roleHierarchy = [
        'root' => 'all',           // Todos los permisos
        'superadmin' => 'all',     // Todos los permisos
        'admin' => 'all',          // Todos los permisos
        'moderator' => 'limited',  // Sin delete
        'user' => 'view'           // Solo view
    ];
    
    echo "\n🔐 Asignando permisos según jerarquía de roles:\n";
    
    foreach ($roleHierarchy as $role => $level) {
        echo "👤 Procesando rol '$role' (nivel: $level)... ";
        $assigned = 0;
        
        foreach ($hrPermissions as $permission) {
            $shouldAssign = false;
            $permKey = $permission['key_name'];
            
            switch ($level) {
                case 'all':
                    $shouldAssign = true;
                    break;
                case 'limited':
                    $shouldAssign = !str_contains($permKey, 'delete');
                    break;
                case 'view':
                    $shouldAssign = str_contains($permKey, 'view');
                    break;
            }
            
            if ($shouldAssign) {
                try {
                    if ($hasPermissionKey && $hasRole) {
                        // Usar permission_key y role
                        $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role, permission_key) VALUES (?, ?)");
                        $stmt->execute([$role, $permKey]);
                    } elseif ($hasPermissionId && $hasRole) {
                        // Usar permission_id y role
                        $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)");
                        $stmt->execute([$role, $permission['id']]);
                    } else {
                        echo "\n❌ Estructura de role_permissions no reconocida\n";
                        break;
                    }
                    $assigned++;
                } catch (Exception $e) {
                    // Ignorar duplicados
                }
            }
        }
        
        echo "✅ ($assigned permisos)\n";
    }
    
    echo "\n";
    
    // 4. Verificar usuarios existentes y sus roles
    echo "4️⃣  USUARIOS Y ROLES ACTUALES:\n";
    echo "-------------------------------\n";
    
    if (in_array('user_companies', $existingTables)) {
        $stmt = $db->query("
            SELECT u.email, u.username, uc.role, COUNT(*) as companies
            FROM users u
            LEFT JOIN user_companies uc ON u.id = uc.user_id
            GROUP BY u.id, u.email, u.username, uc.role
            ORDER BY uc.role, u.email
        ");
        $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($userRoles)) {
            echo "👥 Usuarios por rol:\n";
            $currentRole = '';
            foreach ($userRoles as $user) {
                if ($user['role'] != $currentRole) {
                    $currentRole = $user['role'];
                    echo "\n   🏷️  {$currentRole}:\n";
                }
                $email = $user['email'] ?: $user['username'] ?: 'Sin email';
                echo "      - $email ({$user['companies']} empresa(s))\n";
            }
        } else {
            echo "⚠️  No se encontraron usuarios con roles asignados\n";
        }
    }
    
    echo "\n";
    
    // 5. Verificación final de permisos HR
    echo "5️⃣  VERIFICACIÓN FINAL:\n";
    echo "------------------------\n";
    
    $stmt = $db->query("
        SELECT role, COUNT(*) as total_permisos,
               COUNT(CASE WHEN permission_key LIKE '%employees%' OR permission_key LIKE '%departments%' OR permission_key LIKE '%positions%' THEN 1 END) as permisos_hr
        FROM role_permissions
        WHERE permission_key IS NOT NULL
        GROUP BY role
        ORDER BY 
            CASE role
                WHEN 'root' THEN 1
                WHEN 'superadmin' THEN 2
                WHEN 'admin' THEN 3
                WHEN 'moderator' THEN 4
                WHEN 'user' THEN 5
                ELSE 6
            END
    ");
    $finalStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Resumen final de permisos:\n";
    foreach ($finalStats as $stat) {
        $hrPercent = $stat['total_permisos'] > 0 ? round(($stat['permisos_hr'] / $stat['total_permisos']) * 100, 1) : 0;
        echo "   {$stat['role']}: {$stat['permisos_hr']} HR de {$stat['total_permisos']} totales ({$hrPercent}%)\n";
    }
    
    // 6. Instrucciones finales
    echo "\n🎯 INSTRUCCIONES FINALES:\n";
    echo "=========================\n";
    echo "✅ Permisos HR configurados en el sistema existente\n";
    echo "🔗 Accede al módulo: /modules/human-resources/\n";
    echo "👤 Los usuarios verán opciones según su rol asignado\n";
    echo "⚙️  Roles configurados:\n";
    echo "   - root/superadmin/admin: Acceso completo\n";
    echo "   - moderator: Sin eliminación\n";
    echo "   - user: Solo visualización\n";
    
    // Test de acceso
    echo "\n🧪 TEST DE ACCESO:\n";
    echo "==================\n";
    echo "Para probar los permisos, puedes:\n";
    echo "1. Crear un empleado desde el módulo HR\n";
    echo "2. Verificar que el trigger genere EMP0001, EMP0002...\n";
    echo "3. Probar con diferentes roles de usuario\n";
    
    echo "\n🎉 ¡CONFIGURACIÓN COMPLETADA!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR:\n";
    echo "=========\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
