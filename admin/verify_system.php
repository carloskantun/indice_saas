<?php
/**
 * Script de verificación del sistema admin
 * Usar para verificar que todo esté instalado correctamente
 */

require_once '../config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Verificación Sistema Admin</title>";
echo "<style>
    body{font-family:Arial,sans-serif;max-width:900px;margin:50px auto;padding:20px;background:#f5f5f5;}
    .card{background:white;padding:20px;margin:15px 0;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
    .success{color:#28a745;}.error{color:#dc3545;}.warning{color:#ffc107;}.info{color:#17a2b8;}
    .status{padding:5px 10px;border-radius:5px;display:inline-block;margin:5px 0;}
    .status.ok{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
    .status.fail{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
    .status.warning{background:#fff3cd;color:#856404;border:1px solid #ffeaa7;}
    table{width:100%;border-collapse:collapse;margin:10px 0;}
    th,td{padding:8px;border:1px solid #ddd;text-align:left;}
    th{background:#f8f9fa;}
    .btn{background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:5px;}
    .btn:hover{background:#0056b3;color:white;}
</style></head><body>";

echo "<h1>🔍 Verificación del Sistema de Gestión de Usuarios Admin</h1>";

try {
    $pdo = getDB();
    echo "<div class='card'>";
    echo "<h2>✅ Conexión a Base de Datos</h2>";
    echo "<div class='status ok'>Conexión establecida correctamente</div>";
    echo "</div>";
    
    // Verificar tablas requeridas
    echo "<div class='card'>";
    echo "<h2>🗄️ Verificación de Tablas</h2>";
    
    $requiredTables = [
        'invitations' => 'Gestión de invitaciones de usuarios',
        'user_companies' => 'Relación usuarios-empresas con roles',
        'user_units' => 'Relación usuarios-unidades',
        'user_businesses' => 'Relación usuarios-negocios',
        'permissions' => 'Definición de permisos del sistema',
        'role_permissions' => 'Asignación de permisos por rol',
        'companies' => 'Tabla de empresas (requerida)',
        'users' => 'Tabla de usuarios (requerida)'
    ];
    
    $tablesStatus = [];
    foreach ($requiredTables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        $tablesStatus[$table] = [
            'exists' => $exists !== false,
            'description' => $description
        ];
    }
    
    echo "<table>";
    echo "<tr><th>Tabla</th><th>Estado</th><th>Descripción</th></tr>";
    foreach ($tablesStatus as $table => $status) {
        $statusClass = $status['exists'] ? 'ok' : 'fail';
        $statusText = $status['exists'] ? 'Existe' : 'No existe';
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td><div class='status $statusClass'>$statusText</div></td>";
        echo "<td>{$status['description']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Verificar estructura de tabla invitations
    if ($tablesStatus['invitations']['exists']) {
        echo "<div class='card'>";
        echo "<h2>📧 Estructura Tabla Invitations</h2>";
        
        $stmt = $pdo->query("DESCRIBE invitations");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por Defecto</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    // Verificar permisos
    if ($tablesStatus['permissions']['exists']) {
        echo "<div class='card'>";
        echo "<h2>🔑 Permisos del Sistema</h2>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM permissions");
        $totalPermissions = $stmt->fetch()['total'];
        
        if ($totalPermissions > 0) {
            echo "<div class='status ok'>$totalPermissions permisos configurados</div>";
            
            $stmt = $pdo->query("SELECT module, COUNT(*) as count FROM permissions GROUP BY module ORDER BY module");
            $permissionsByModule = $stmt->fetchAll();
            
            echo "<h4>Permisos por módulo:</h4>";
            echo "<ul>";
            foreach ($permissionsByModule as $module) {
                echo "<li><strong>{$module['module']}:</strong> {$module['count']} permisos</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='status warning'>No se encontraron permisos configurados</div>";
        }
        echo "</div>";
    }
    
    // Verificar roles y permisos
    if ($tablesStatus['role_permissions']['exists']) {
        echo "<div class='card'>";
        echo "<h2>👥 Roles y Permisos</h2>";
        
        $stmt = $pdo->query("
            SELECT r.role, COUNT(*) as permission_count 
            FROM role_permissions r 
            GROUP BY r.role 
            ORDER BY 
                CASE r.role
                    WHEN 'superadmin' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'moderator' THEN 3
                    WHEN 'user' THEN 4
                    ELSE 5
                END
        ");
        $rolePermissions = $stmt->fetchAll();
        
        if (count($rolePermissions) > 0) {
            echo "<table>";
            echo "<tr><th>Rol</th><th>Permisos Asignados</th></tr>";
            foreach ($rolePermissions as $role) {
                echo "<tr>";
                echo "<td><strong>{$role['role']}</strong></td>";
                echo "<td>{$role['permission_count']} permisos</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='status warning'>No se encontraron asignaciones de permisos</div>";
        }
        echo "</div>";
    }
    
    // Verificar usuarios con roles
    if ($tablesStatus['user_companies']['exists'] && $tablesStatus['users']['exists']) {
        echo "<div class='card'>";
        echo "<h2>👤 Usuarios con Roles</h2>";
        
        $stmt = $pdo->query("
            SELECT u.name, u.email, uc.role, uc.status, c.name as company_name
            FROM users u
            JOIN user_companies uc ON u.id = uc.user_id
            JOIN companies c ON uc.company_id = c.id
            ORDER BY 
                CASE uc.role
                    WHEN 'superadmin' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'moderator' THEN 3
                    WHEN 'user' THEN 4
                    ELSE 5
                END,
                u.name
        ");
        $usersWithRoles = $stmt->fetchAll();
        
        if (count($usersWithRoles) > 0) {
            echo "<table>";
            echo "<tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Empresa</th></tr>";
            foreach ($usersWithRoles as $user) {
                $statusClass = $user['status'] === 'active' ? 'ok' : 'warning';
                echo "<tr>";
                echo "<td>{$user['name']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td><strong>{$user['role']}</strong></td>";
                echo "<td><div class='status $statusClass'>{$user['status']}</div></td>";
                echo "<td>{$user['company_name']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='status warning'>No se encontraron usuarios con roles asignados</div>";
            echo "<p><strong>Importante:</strong> Para acceder al sistema admin necesitas al menos un usuario con rol 'superadmin' o 'admin'.</p>";
        }
        echo "</div>";
    }
    
    // Verificar invitaciones
    if ($tablesStatus['invitations']['exists']) {
        echo "<div class='card'>";
        echo "<h2>📮 Invitaciones</h2>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM invitations");
        $totalInvitations = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM invitations 
            GROUP BY status
        ");
        $invitationsByStatus = $stmt->fetchAll();
        
        echo "<p><strong>Total de invitaciones:</strong> $totalInvitations</p>";
        
        if (count($invitationsByStatus) > 0) {
            echo "<table>";
            echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
            foreach ($invitationsByStatus as $status) {
                echo "<tr>";
                echo "<td>{$status['status']}</td>";
                echo "<td>{$status['count']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    
    // Verificar archivos del sistema
    echo "<div class='card'>";
    echo "<h2>📁 Archivos del Sistema</h2>";
    
    $requiredFiles = [
        'index.php' => 'Interfaz principal del sistema',
        'controller.php' => 'Controlador backend',
        'accept_invitation.php' => 'Página de aceptación de invitaciones',
        'modals/invite_user_modal.php' => 'Modal de invitación',
        'modals/edit_user_modal.php' => 'Modal de edición',
        'js/admin_users.js' => 'JavaScript principal',
        'email_config.php' => 'Configuración de email'
    ];
    
    echo "<table>";
    echo "<tr><th>Archivo</th><th>Estado</th><th>Descripción</th></tr>";
    foreach ($requiredFiles as $file => $description) {
        $exists = file_exists(__DIR__ . '/' . $file);
        $statusClass = $exists ? 'ok' : 'fail';
        $statusText = $exists ? 'Existe' : 'No existe';
        echo "<tr>";
        echo "<td><strong>$file</strong></td>";
        echo "<td><div class='status $statusClass'>$statusText</div></td>";
        echo "<td>$description</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Resumen y acciones
    echo "<div class='card'>";
    echo "<h2>🎯 Acciones Disponibles</h2>";
    
    $allTablesExist = true;
    foreach (['invitations', 'user_companies', 'permissions'] as $table) {
        if (!$tablesStatus[$table]['exists']) {
            $allTablesExist = false;
            break;
        }
    }
    
    if ($allTablesExist) {
        echo "<div class='status ok'>✅ Sistema correctamente instalado</div>";
        echo "<p><strong>Puedes acceder a:</strong></p>";
        echo "<a href='index.php' class='btn'>🚀 Ir al Sistema Admin</a>";
        echo "<a href='../index.php' class='btn' style='background:#28a745;'>🏠 Panel Principal</a>";
    } else {
        echo "<div class='status fail'>❌ Sistema no instalado completamente</div>";
        echo "<p><strong>Ejecuta la instalación:</strong></p>";
        echo "<a href='install_admin_tables.php' class='btn' style='background:#dc3545;'>🔧 Instalar Tablas</a>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='card'>";
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<div class='status fail'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
}

echo "<div style='text-align:center;margin-top:30px;color:#666;'>";
echo "<small>Verificación realizada el " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

echo "</body></html>";
?>
