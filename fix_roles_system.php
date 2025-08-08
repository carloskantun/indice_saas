<?php
/**
 * DIAGNÓSTICO Y REPARACIÓN DEL SISTEMA DE ROLES
 * Sistema SaaS Indice
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "🔧 DIAGNÓSTICO SISTEMA DE ROLES\n";
    echo "===============================\n\n";
    
    // 1. Verificar todas las tablas de la base de datos
    echo "1️⃣  TABLAS DE LA BASE DE DATOS:\n";
    echo "--------------------------------\n";
    $stmt = $db->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Total de tablas: " . count($allTables) . "\n";
    foreach ($allTables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "   - $table: $count registros\n";
    }
    
    echo "\n";
    
    // 2. Verificar tablas específicas del sistema de roles
    echo "2️⃣  TABLAS DEL SISTEMA DE ROLES:\n";
    echo "---------------------------------\n";
    $requiredTables = ['roles', 'role_permissions', 'user_roles', 'users', 'permissions'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count registros\n";
        } else {
            $missingTables[] = $table;
            echo "❌ $table: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 3. Si faltan tablas, mostrar qué crear
    if (!empty($missingTables)) {
        echo "3️⃣  TABLAS FALTANTES:\n";
        echo "---------------------\n";
        echo "⚠️  Faltan " . count($missingTables) . " tablas del sistema de roles:\n";
        foreach ($missingTables as $table) {
            echo "   - $table\n";
        }
        
        echo "\n🛠️  CREANDO TABLAS FALTANTES...\n";
        echo "================================\n";
        
        // Crear tabla roles si no existe
        if (in_array('roles', $missingTables)) {
            echo "📋 Creando tabla 'roles'... ";
            try {
                $sql = "CREATE TABLE `roles` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `role_name` varchar(50) NOT NULL,
                    `description` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_role_name` (`role_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $db->exec($sql);
                echo "✅\n";
                
                // Insertar roles básicos
                echo "   Insertando roles básicos... ";
                $roles = [
                    ['root', 'Administrador del sistema con acceso total'],
                    ['superadmin', 'Super administrador con acceso completo'],
                    ['admin', 'Administrador con permisos de gestión'],
                    ['moderator', 'Moderador con permisos limitados'],
                    ['user', 'Usuario básico con permisos mínimos']
                ];
                
                foreach ($roles as $role) {
                    $stmt = $db->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
                    $stmt->execute($role);
                }
                echo "✅\n";
                
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        
        // Crear tabla role_permissions si no existe
        if (in_array('role_permissions', $missingTables)) {
            echo "📋 Creando tabla 'role_permissions'... ";
            try {
                $sql = "CREATE TABLE `role_permissions` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `role_id` int(11) NOT NULL,
                    `permission_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
                    KEY `idx_role` (`role_id`),
                    KEY `idx_permission` (`permission_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $db->exec($sql);
                echo "✅\n";
                
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        
        // Crear tabla user_roles si no existe
        if (in_array('user_roles', $missingTables)) {
            echo "📋 Creando tabla 'user_roles'... ";
            try {
                $sql = "CREATE TABLE `user_roles` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `role_id` int(11) NOT NULL,
                    `company_id` int(11) DEFAULT NULL,
                    `business_id` int(11) DEFAULT NULL,
                    `assigned_by` int(11) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_user_role_company` (`user_id`, `role_id`, `company_id`, `business_id`),
                    KEY `idx_user` (`user_id`),
                    KEY `idx_role` (`role_id`),
                    KEY `idx_company` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $db->exec($sql);
                echo "✅\n";
                
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        
        // Crear tabla users si no existe (básica)
        if (in_array('users', $missingTables)) {
            echo "📋 Creando tabla 'users'... ";
            try {
                $sql = "CREATE TABLE `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(100) NOT NULL,
                    `email` varchar(255) NOT NULL,
                    `password` varchar(255) NOT NULL,
                    `first_name` varchar(100) DEFAULT NULL,
                    `last_name` varchar(100) DEFAULT NULL,
                    `status` enum('active', 'inactive', 'pending') DEFAULT 'active',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_username` (`username`),
                    UNIQUE KEY `uk_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $db->exec($sql);
                echo "✅\n";
                
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        
        // Asignar permisos de HR a los roles
        echo "\n🔐 ASIGNANDO PERMISOS HR A ROLES:\n";
        echo "=================================\n";
        
        try {
            // Obtener IDs de roles
            $stmt = $db->query("SELECT id, role_name FROM roles");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener permisos de HR
            $stmt = $db->query("SELECT id, key_name FROM permissions WHERE key_name LIKE '%employees%' OR key_name LIKE '%departments%' OR key_name LIKE '%positions%'");
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📋 Roles encontrados: " . count($roles) . "\n";
            echo "📋 Permisos HR encontrados: " . count($permissions) . "\n\n";
            
            foreach ($roles as $role) {
                echo "👤 Asignando permisos a rol: {$role['role_name']}... ";
                $assigned = 0;
                
                foreach ($permissions as $permission) {
                    // Asignar según el rol
                    $shouldAssign = false;
                    
                    switch ($role['role_name']) {
                        case 'root':
                        case 'superadmin':
                            $shouldAssign = true; // Todos los permisos
                            break;
                        case 'admin':
                            $shouldAssign = true; // Todos los permisos
                            break;
                        case 'moderator':
                            // Solo view y edit, no delete
                            $shouldAssign = !str_contains($permission['key_name'], 'delete');
                            break;
                        case 'user':
                            // Solo view
                            $shouldAssign = str_contains($permission['key_name'], 'view');
                            break;
                    }
                    
                    if ($shouldAssign) {
                        try {
                            $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                            $stmt->execute([$role['id'], $permission['id']]);
                            $assigned++;
                        } catch (Exception $e) {
                            // Ignorar duplicados
                        }
                    }
                }
                
                echo "✅ ($assigned permisos)\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error asignando permisos: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Verificación final
    echo "\n📊 VERIFICACIÓN FINAL:\n";
    echo "======================\n";
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count registros\n";
        } else {
            echo "❌ $table: NO EXISTE\n";
        }
    }
    
    // Verificar asignaciones de permisos
    try {
        $stmt = $db->query("
            SELECT r.role_name, COUNT(rp.id) as total_permisos, 
                   COUNT(CASE WHEN p.key_name LIKE '%employees%' OR p.key_name LIKE '%departments%' OR p.key_name LIKE '%positions%' THEN 1 END) as permisos_hr
            FROM roles r
            LEFT JOIN role_permissions rp ON r.id = rp.role_id
            LEFT JOIN permissions p ON rp.permission_id = p.id
            GROUP BY r.id, r.role_name
            ORDER BY r.id
        ");
        $roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n👥 RESUMEN DE PERMISOS POR ROL:\n";
        foreach ($roleStats as $stat) {
            echo "   {$stat['role_name']}: {$stat['permisos_hr']} permisos HR de {$stat['total_permisos']} totales\n";
        }
        
    } catch (Exception $e) {
        echo "\n⚠️  No se pudo verificar asignaciones: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 ¡DIAGNÓSTICO COMPLETADO!\n";
    echo "===========================\n";
    
    if (empty($missingTables)) {
        echo "✅ Sistema de roles completo\n";
        echo "🔗 El módulo HR debería funcionar correctamente\n";
    } else {
        echo "🔧 Se crearon las tablas faltantes\n";
        echo "🔄 Ejecuta el diagnóstico HR nuevamente\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL:\n";
    echo "=================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
