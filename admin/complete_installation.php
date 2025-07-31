<?php
/**
 * Script simplificado para completar la instalación
 * Evita problemas con triggers y sintaxis compleja
 */

require_once '../config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Completar Instalación Admin</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}</style></head><body>";

echo "<h1>🔧 Completar Instalación del Sistema Admin</h1>";

try {
    $pdo = getDB();
    echo "<div class='info'>✅ Conexión a base de datos establecida</div><br>";
    
    // 1. Verificar/crear trigger para fecha de expiración
    echo "<h2>⚡ Configurando fecha de expiración automática</h2>";
    
    // Eliminar trigger si existe
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS set_invitation_expiration");
        echo "<div class='info'>🗑️ Trigger anterior eliminado (si existía)</div>";
    } catch (PDOException $e) {
        // Ignorar errores
    }
    
    // Crear trigger sin IF NOT EXISTS
    $triggerSQL = "
    CREATE TRIGGER set_invitation_expiration 
    BEFORE INSERT ON invitations
    FOR EACH ROW
    BEGIN
        IF NEW.fecha_expiracion IS NULL THEN
            SET NEW.fecha_expiracion = DATE_ADD(NOW(), INTERVAL 48 HOUR);
        END IF;
    END";
    
    try {
        $pdo->exec($triggerSQL);
        echo "<div class='success'>✅ Trigger para fecha de expiración creado</div>";
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ Trigger no creado: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='info'>💡 No es crítico, las fechas se pueden manejar manualmente</div>";
    }
    
    // 2. Verificar que los permisos se insertaron correctamente
    echo "<h2>🔑 Verificando permisos</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM permissions");
    $totalPermissions = $stmt->fetch()['total'];
    
    if ($totalPermissions == 0) {
        echo "<div class='warning'>⚠️ No se encontraron permisos, insertando...</div>";
        
        $basicPermissions = [
            ['gastos.ver', 'Ver gastos', 'gastos'],
            ['gastos.crear', 'Crear gastos', 'gastos'],
            ['gastos.editar', 'Editar gastos', 'gastos'],
            ['gastos.eliminar', 'Eliminar gastos', 'gastos'],
            ['usuarios.ver', 'Ver usuarios', 'usuarios'],
            ['usuarios.invitar', 'Invitar usuarios', 'usuarios'],
            ['usuarios.editar', 'Editar usuarios', 'usuarios'],
            ['usuarios.suspender', 'Suspender usuarios', 'usuarios'],
            ['reportes.ver', 'Ver reportes', 'reportes'],
            ['configuracion.ver', 'Ver configuración', 'configuracion'],
            ['configuracion.editar', 'Editar configuración', 'configuracion']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (key_name, description, module) VALUES (?, ?, ?)");
        $insertedCount = 0;
        
        foreach ($basicPermissions as $permission) {
            $stmt->execute($permission);
            if ($stmt->rowCount() > 0) {
                $insertedCount++;
            }
        }
        
        echo "<div class='success'>✅ $insertedCount permisos insertados</div>";
    } else {
        echo "<div class='success'>✅ $totalPermissions permisos ya configurados</div>";
    }
    
    // 3. Verificar asignaciones de roles
    echo "<h2>👥 Verificando asignaciones de roles</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM role_permissions");
    $totalRolePermissions = $stmt->fetch()['total'];
    
    if ($totalRolePermissions == 0) {
        echo "<div class='warning'>⚠️ No se encontraron asignaciones rol-permiso, creando...</div>";
        
        // Obtener IDs de permisos
        $stmt = $pdo->query("SELECT id, key_name FROM permissions");
        $permissions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $rolePermissions = [
            'superadmin' => array_keys($permissions), // Todos los permisos
            'admin' => [
                $permissions['gastos.ver'] ?? null,
                $permissions['gastos.crear'] ?? null,
                $permissions['gastos.editar'] ?? null,
                $permissions['usuarios.ver'] ?? null,
                $permissions['usuarios.invitar'] ?? null,
                $permissions['reportes.ver'] ?? null,
                $permissions['configuracion.ver'] ?? null
            ],
            'moderator' => [
                $permissions['gastos.ver'] ?? null,
                $permissions['gastos.crear'] ?? null,
                $permissions['gastos.editar'] ?? null,
                $permissions['usuarios.ver'] ?? null,
                $permissions['reportes.ver'] ?? null
            ],
            'user' => [
                $permissions['gastos.ver'] ?? null,
                $permissions['gastos.crear'] ?? null
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)");
        $assignedCount = 0;
        
        foreach ($rolePermissions as $role => $permissionIds) {
            foreach ($permissionIds as $permissionId) {
                if ($permissionId) {
                    $stmt->execute([$role, $permissionId]);
                    if ($stmt->rowCount() > 0) {
                        $assignedCount++;
                    }
                }
            }
        }
        
        echo "<div class='success'>✅ $assignedCount asignaciones rol-permiso creadas</div>";
    } else {
        echo "<div class='success'>✅ $totalRolePermissions asignaciones ya configuradas</div>";
    }
    
    // 4. Verificar usuarios con roles
    echo "<h2>👤 Verificando usuarios administradores</h2>";
    
    $stmt = $pdo->query("
        SELECT u.name, u.email, uc.role, c.name as company_name
        FROM users u
        JOIN user_companies uc ON u.id = uc.user_id
        JOIN companies c ON uc.company_id = c.id
        WHERE uc.role IN ('superadmin', 'admin') AND uc.status = 'active'
        ORDER BY 
            CASE uc.role
                WHEN 'superadmin' THEN 1
                WHEN 'admin' THEN 2
                ELSE 3
            END
    ");
    $adminUsers = $stmt->fetchAll();
    
    if (count($adminUsers) > 0) {
        echo "<div class='success'>✅ " . count($adminUsers) . " usuarios administradores encontrados</div>";
        echo "<table border='1' style='width:100%;border-collapse:collapse;margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'><th>Usuario</th><th>Email</th><th>Rol</th><th>Empresa</th></tr>";
        foreach ($adminUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td><strong>{$user['role']}</strong></td>";
            echo "<td>{$user['company_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No se encontraron usuarios administradores</div>";
        echo "<div class='warning'>⚠️ Necesitas asignar rol superadmin/admin a tu usuario</div>";
        echo "<p><strong>Ejecuta esta consulta SQL (reemplaza con tus datos):</strong></p>";
        echo "<code style='background:#f5f5f5;padding:10px;display:block;margin:10px 0;'>";
        echo "INSERT IGNORE INTO user_companies (user_id, company_id, role, status)<br>";
        echo "SELECT u.id, 1, 'superadmin', 'active'<br>";
        echo "FROM users u<br>";
        echo "WHERE u.email = 'tu_email@ejemplo.com';";
        echo "</code>";
    }
    
    echo "<br><h2>🎉 Instalación Completada</h2>";
    echo "<div class='success'>✅ El sistema admin está listo para usar</div>";
    
    if (count($adminUsers) > 0) {
        echo "<p><strong>Puedes acceder a:</strong></p>";
        echo "<a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>🚀 Ir al Sistema Admin</a>";
    } else {
        echo "<p><strong>Primero asigna rol de administrador y luego:</strong></p>";
        echo "<a href='index.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>🚀 Intentar Acceder</a>";
    }
    
    echo "<a href='verify_system.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>🔍 Verificar Sistema</a>";
    echo "<a href='../index.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🏠 Panel Principal</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
