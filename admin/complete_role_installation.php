<?php
/**
 * Completar Instalación de Roles - Sistema SaaS
 * Finaliza la configuración de roles y permisos del sistema
 */

require_once '../config.php';

echo "=== COMPLETAR INSTALACIÓN DE ROLES ===\n\n";

try {
    $db = getDB();
    echo "✅ Conexión a base de datos establecida\n\n";
    
    // Verificar que las tablas principales existan
    echo "🔍 Verificando tablas principales...\n";
    $required_tables = ['users', 'companies', 'user_companies', 'plans', 'role_permissions'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if (!$stmt->fetch()) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "❌ ERROR: Faltan tablas críticas: " . implode(', ', $missing_tables) . "\n";
        echo "💡 Ejecutar primero: php install_database.php y php admin/install_missing_table.php\n";
        exit(1);
    }
    echo "✅ Todas las tablas principales están presentes\n\n";
    
    // Verificar usuario root
    echo "👑 Verificando usuario root...\n";
    $stmt = $db->prepare("SELECT id, email, role FROM users WHERE role = 'root' LIMIT 1");
    $stmt->execute();
    $root_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$root_user) {
        echo "⚠️  No se encontró usuario root, creando...\n";
        $stmt = $db->prepare("
            INSERT INTO users (email, password, role, status, created_at) 
            VALUES (?, ?, 'root', 'active', NOW())
        ");
        $root_password = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt->execute(['root@system.com', $root_password]);
        $root_id = $db->lastInsertId();
        echo "✅ Usuario root creado: root@system.com / admin123\n";
    } else {
        echo "✅ Usuario root encontrado: " . $root_user['email'] . "\n";
        $root_id = $root_user['id'];
    }
    
    // Configurar permisos avanzados de roles
    echo "\n🔐 Configurando permisos avanzados...\n";
    
    $advanced_permissions = [
        // ROOT - Acceso total al sistema
        ['role' => 'root', 'permission' => 'create', 'resource' => 'companies'],
        ['role' => 'root', 'permission' => 'delete', 'resource' => 'companies'],
        ['role' => 'root', 'permission' => 'manage', 'resource' => 'system_config'],
        ['role' => 'root', 'permission' => 'view', 'resource' => 'logs'],
        ['role' => 'root', 'permission' => 'manage', 'resource' => 'backups'],
        
        // SUPPORT - Soporte técnico
        ['role' => 'support', 'permission' => 'view', 'resource' => 'companies'],
        ['role' => 'support', 'permission' => 'view', 'resource' => 'users'],
        ['role' => 'support', 'permission' => 'view', 'resource' => 'logs'],
        ['role' => 'support', 'permission' => 'edit', 'resource' => 'user_status'],
        
        // SUPERADMIN - Administrador de empresa
        ['role' => 'superadmin', 'permission' => 'create', 'resource' => 'users'],
        ['role' => 'superadmin', 'permission' => 'edit', 'resource' => 'users'],
        ['role' => 'superadmin', 'permission' => 'delete', 'resource' => 'users'],
        ['role' => 'superadmin', 'permission' => 'manage', 'resource' => 'invitations'],
        ['role' => 'superadmin', 'permission' => 'view', 'resource' => 'reports'],
        
        // ADMIN - Administrador de área
        ['role' => 'admin', 'permission' => 'create', 'resource' => 'invitations'],
        ['role' => 'admin', 'permission' => 'edit', 'resource' => 'unit_users'],
        ['role' => 'admin', 'permission' => 'view', 'resource' => 'area_reports'],
        
        // MODERATOR - Moderador
        ['role' => 'moderator', 'permission' => 'view', 'resource' => 'users'],
        ['role' => 'moderator', 'permission' => 'edit', 'resource' => 'content'],
        ['role' => 'moderator', 'permission' => 'moderate', 'resource' => 'comments'],
        
        // USER - Usuario final
        ['role' => 'user', 'permission' => 'edit', 'resource' => 'own_profile'],
        ['role' => 'user', 'permission' => 'view', 'resource' => 'assigned_content'],
        ['role' => 'user', 'permission' => 'create', 'resource' => 'user_content']
    ];
    
    $permissions_added = 0;
    foreach ($advanced_permissions as $perm) {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM role_permissions 
            WHERE role = ? AND permission = ? AND resource = ? AND company_id IS NULL
        ");
        $stmt->execute([$perm['role'], $perm['permission'], $perm['resource']]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("
                INSERT INTO role_permissions (role, permission, resource) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$perm['role'], $perm['permission'], $perm['resource']]);
            $permissions_added++;
        }
    }
    echo "✅ Permisos avanzados configurados: $permissions_added nuevos\n";
    
    // Crear empresa demo si no existe
    echo "\n🏢 Verificando empresa demo...\n";
    $stmt = $db->prepare("SELECT id FROM companies WHERE name = 'Empresa Demo' LIMIT 1");
    $stmt->execute();
    $demo_company = $stmt->fetch();
    
    if (!$demo_company) {
        echo "🆕 Creando empresa demo...\n";
        $stmt = $db->prepare("
            INSERT INTO companies (name, description, plan_id, status, created_at) 
            VALUES (?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            'Empresa Demo',
            'Empresa de demostración para pruebas del sistema',
            1 // Plan básico
        ]);
        $demo_company_id = $db->lastInsertId();
        echo "✅ Empresa demo creada con ID: $demo_company_id\n";
    } else {
        echo "✅ Empresa demo ya existe\n";
        $demo_company_id = $demo_company['id'];
    }
    
    // Crear usuario admin demo si no existe
    echo "\n👤 Verificando usuario admin demo...\n";
    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin@demo.com' LIMIT 1");
    $stmt->execute();
    $demo_admin = $stmt->fetch();
    
    if (!$demo_admin) {
        echo "🆕 Creando usuario admin demo...\n";
        $stmt = $db->prepare("
            INSERT INTO users (email, password, role, status, created_at) 
            VALUES (?, ?, 'superadmin', 'active', NOW())
        ");
        $demo_password = password_hash('demo123', PASSWORD_BCRYPT);
        $stmt->execute(['admin@demo.com', $demo_password]);
        $demo_admin_id = $db->lastInsertId();
        
        // Asociar admin con empresa demo
        $stmt = $db->prepare("
            INSERT INTO user_companies (user_id, company_id, role, status, created_at) 
            VALUES (?, ?, 'superadmin', 'active', NOW())
        ");
        $stmt->execute([$demo_admin_id, $demo_company_id]);
        
        echo "✅ Usuario admin demo creado: admin@demo.com / demo123\n";
    } else {
        echo "✅ Usuario admin demo ya existe\n";
    }
    
    // Resumen de la instalación
    echo "\n📊 RESUMEN DE LA INSTALACIÓN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Contar usuarios por rol
    $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $user_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "👥 USUARIOS POR ROL:\n";
    foreach ($user_counts as $role => $count) {
        echo "   • $role: $count usuario(s)\n";
    }
    
    // Contar empresas
    $stmt = $db->query("SELECT COUNT(*) FROM companies");
    $company_count = $stmt->fetchColumn();
    echo "\n🏢 EMPRESAS: $company_count empresa(s)\n";
    
    // Contar permisos
    $stmt = $db->query("SELECT COUNT(*) FROM role_permissions");
    $permission_count = $stmt->fetchColumn();
    echo "\n🔐 PERMISOS: $permission_count permisos configurados\n";
    
    echo "\n🎉 INSTALACIÓN DE ROLES COMPLETADA EXITOSAMENTE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Sistema de roles completamente configurado\n";
    echo "✅ Permisos granulares establecidos\n";
    echo "✅ Usuarios y empresa demo listos\n\n";
    
    echo "🔑 CREDENCIALES DE ACCESO:\n";
    echo "   Root: root@system.com / admin123\n";
    echo "   Demo Admin: admin@demo.com / demo123\n\n";
    
    echo "🚀 EL SISTEMA SAAS ESTÁ COMPLETAMENTE LISTO\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📁 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
?>
