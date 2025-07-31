<?php
/**
 * Script para listar usuarios y sus roles en el sistema
 * Útil para debug y administración
 */

require_once 'config.php';

try {
    $pdo = getDB();
    
    echo "=== Lista de Usuarios y Roles ===\n\n";
    
    // Obtener todos los usuarios con sus roles
    $stmt = $pdo->query("
        SELECT 
            u.id as user_id,
            u.name,
            u.email,
            u.status as user_status,
            uc.role,
            c.name as company_name,
            uc.status as role_status,
            uc.last_accessed
        FROM users u
        LEFT JOIN user_companies uc ON u.id = uc.user_id
        LEFT JOIN companies c ON uc.company_id = c.id
        ORDER BY 
            CASE uc.role
                WHEN 'root' THEN 1
                WHEN 'superadmin' THEN 2
                WHEN 'admin' THEN 3
                WHEN 'moderator' THEN 4
                WHEN 'support' THEN 5
                WHEN 'user' THEN 6
                ELSE 7
            END,
            u.name
    ");
    
    $users = $stmt->fetchAll();
    $currentUserId = null;
    $userCount = 0;
    $roleCount = [];
    
    foreach ($users as $user) {
        if ($currentUserId !== $user['user_id']) {
            if ($currentUserId !== null) {
                echo "\n";
            }
            $userCount++;
            $currentUserId = $user['user_id'];
            
            $statusIcon = $user['user_status'] === 'active' ? '🟢' : '🔴';
            echo "👤 {$user['name']} ({$user['email']}) $statusIcon\n";
        }
        
        if ($user['role']) {
            $roleIcon = getRoleIcon($user['role']);
            $roleStatus = $user['role_status'] === 'active' ? '✅' : '❌';
            $lastAccess = $user['last_accessed'] ? date('Y-m-d H:i', strtotime($user['last_accessed'])) : 'Nunca';
            
            echo "   $roleIcon {$user['role']} en '{$user['company_name']}' $roleStatus (Último acceso: $lastAccess)\n";
            
            // Contar roles
            if (!isset($roleCount[$user['role']])) {
                $roleCount[$user['role']] = 0;
            }
            $roleCount[$user['role']]++;
        } else {
            echo "   ⚪ Sin roles asignados\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 Resumen:\n";
    echo "👥 Total de usuarios: $userCount\n";
    
    if (!empty($roleCount)) {
        echo "📋 Roles activos:\n";
        foreach ($roleCount as $role => $count) {
            $icon = getRoleIcon($role);
            echo "   $icon $role: $count\n";
        }
    }
    
    // Verificar usuarios root
    $rootCount = $roleCount['root'] ?? 0;
    if ($rootCount === 0) {
        echo "\n⚠️  ¡ADVERTENCIA! No hay usuarios root en el sistema.\n";
        echo "   Ejecuta: php create_root_user.php\n";
    } elseif ($rootCount === 1) {
        echo "\n✅ Hay 1 usuario root configurado.\n";
    } else {
        echo "\n🔍 Hay $rootCount usuarios root configurados.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

function getRoleIcon($role) {
    $icons = [
        'root' => '👑',
        'superadmin' => '🔧',
        'admin' => '🛡️',
        'moderator' => '⚖️',
        'support' => '🎧',
        'user' => '👤'
    ];
    
    return $icons[$role] ?? '❓';
}
?>
