<?php
/**
 * SCRIPT PARA AGREGAR MÓDULO HUMAN RESOURCES
 * Sistema SaaS Indice
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "=== MÓDULOS REGISTRADOS ACTUALMENTE ===\n";
    $stmt = $db->query('SELECT id, name, slug, description, url, icon, color, status FROM modules ORDER BY name');
    $modules = $stmt->fetchAll();
    
    foreach ($modules as $module) {
        echo "ID: {$module['id']} | {$module['name']} | {$module['slug']} | {$module['status']}\n";
    }
    
    echo "\n=== AGREGANDO MÓDULO HUMAN RESOURCES ===\n";
    
    // Verificar si ya existe el módulo
    $stmt = $db->prepare("SELECT id FROM modules WHERE slug = 'human-resources' OR name = 'Recursos Humanos' OR name = 'Empleados'");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "⚠️  El módulo ya existe con ID: {$existing['id']}\n";
        echo "Actualizando módulo existente...\n";
        
        $sql = "UPDATE modules SET 
                name = 'Recursos Humanos',
                slug = 'human-resources',
                description = 'Gestión completa de empleados, departamentos y posiciones',
                url = '/modules/human-resources/',
                icon = 'fas fa-users',
                color = '#3498db',
                status = 'active',
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$existing['id']]);
        
        if ($result) {
            echo "✅ Módulo actualizado exitosamente\n";
        } else {
            echo "❌ Error al actualizar módulo\n";
        }
        
    } else {
        echo "Creando nuevo módulo...\n";
        
        $sql = "INSERT INTO modules (name, slug, description, url, icon, color, status, created_at) 
                VALUES ('Recursos Humanos', 'human-resources', 'Gestión completa de empleados, departamentos y posiciones', '/modules/human-resources/', 'fas fa-users', '#3498db', 'active', NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute();
        
        if ($result) {
            $moduleId = $db->lastInsertId();
            echo "✅ Módulo creado exitosamente con ID: $moduleId\n";
        } else {
            echo "❌ Error al crear módulo\n";
        }
    }
    
    echo "\n=== AGREGANDO PERMISOS PARA HUMAN RESOURCES ===\n";
    
    // Permisos para el módulo
    $permissions = [
        'employees.view' => 'Ver empleados',
        'employees.create' => 'Crear empleados',
        'employees.edit' => 'Editar empleados',
        'employees.delete' => 'Eliminar empleados',
        'employees.export' => 'Exportar datos de empleados',
        'employees.kpis' => 'Ver estadísticas de empleados',
        'departments.view' => 'Ver departamentos',
        'departments.create' => 'Crear departamentos',
        'departments.edit' => 'Editar departamentos',
        'departments.delete' => 'Eliminar departamentos',
        'positions.view' => 'Ver posiciones',
        'positions.create' => 'Crear posiciones',
        'positions.edit' => 'Editar posiciones',
        'positions.delete' => 'Eliminar posiciones'
    ];
    
    // Verificar estructura de tabla permissions
    $stmt = $db->query("DESCRIBE permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $keyColumn = in_array('key', $columns) ? 'key' : 'key_name';
    echo "Usando columna: $keyColumn\n";
    
    foreach ($permissions as $key => $description) {
        $stmt = $db->prepare("INSERT IGNORE INTO permissions ($keyColumn, description, module) VALUES (?, ?, 'human-resources')");
        $result = $stmt->execute([$key, $description]);
        
        if ($result) {
            echo "✅ Permiso agregado: $key\n";
        } else {
            echo "⚠️  Permiso ya existe o error: $key\n";
        }
    }
    
    echo "\n=== ASIGNANDO PERMISOS A ROLES ===\n";
    
    // Verificar estructura de tabla role_permissions
    $stmt = $db->query("DESCRIBE role_permissions");
    $roleColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $permissionColumn = in_array('permission_key', $roleColumns) ? 'permission_key' : 'permission_id';
    echo "Usando columna de permisos: $permissionColumn\n";
    
    // Roles y sus permisos
    $rolePermissions = [
        'root' => array_keys($permissions),
        'superadmin' => array_keys($permissions),
        'admin' => array_keys($permissions),
        'moderator' => ['employees.view', 'employees.create', 'employees.edit', 'departments.view', 'positions.view'],
        'user' => ['employees.view', 'departments.view', 'positions.view']
    ];
    
    foreach ($rolePermissions as $role => $perms) {
        foreach ($perms as $perm) {
            if ($permissionColumn === 'permission_key') {
                $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role, permission_key) VALUES (?, ?)");
                $result = $stmt->execute([$role, $perm]);
            } else {
                // Buscar ID del permiso
                $stmt = $db->prepare("SELECT id FROM permissions WHERE $keyColumn = ?");
                $stmt->execute([$perm]);
                $permId = $stmt->fetchColumn();
                
                if ($permId) {
                    $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)");
                    $result = $stmt->execute([$role, $permId]);
                }
            }
            
            if ($result ?? false) {
                echo "✅ Permiso $perm asignado a rol $role\n";
            }
        }
    }
    
    echo "\n=== MÓDULOS FINALES ===\n";
    $stmt = $db->query('SELECT id, name, slug, status FROM modules ORDER BY name');
    $modules = $stmt->fetchAll();
    
    foreach ($modules as $module) {
        echo "ID: {$module['id']} | {$module['name']} | {$module['slug']} | {$module['status']}\n";
    }
    
    echo "\n✅ ¡Módulo Human Resources agregado exitosamente!\n";
    echo "🔗 URL: /modules/human-resources/\n";
    echo "🎨 Icono: fas fa-users\n";
    echo "🎨 Color: #3498db\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
