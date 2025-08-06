<?php
/**
 * Instalador de Tablas Faltantes - Sistema de Invitaciones
 * Crea tablas adicionales para el sistema de invitaciones y permisos
 */

require_once '../config.php';

echo "=== INSTALADOR DE TABLAS FALTANTES ===\n\n";

try {
    $db = getDB();
    echo "✅ Conexión a base de datos establecida\n\n";
    
    // Crear tabla role_permissions si no existe
    echo "🔐 Verificando tabla de permisos de roles...\n";
    $sql = "
    CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role ENUM('root', 'support', 'superadmin', 'admin', 'moderator', 'user') NOT NULL,
        permission VARCHAR(100) NOT NULL,
        resource VARCHAR(100) NOT NULL,
        company_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        UNIQUE KEY unique_role_permission (role, permission, resource, company_id),
        INDEX idx_role (role),
        INDEX idx_permission (permission),
        INDEX idx_resource (resource),
        INDEX idx_company (company_id)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'role_permissions' verificada/creada\n";
    
    // Crear tabla sessions si no existe (para gestión de sesiones)
    echo "📋 Verificando tabla de sesiones...\n";
    $sql = "
    CREATE TABLE IF NOT EXISTS user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT NOT NULL,
        company_id INT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_company (company_id),
        INDEX idx_last_activity (last_activity)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'user_sessions' verificada/creada\n";
    
    // Insertar permisos básicos por rol
    echo "🔑 Configurando permisos básicos...\n";
    
    $basic_permissions = [
        // Permisos ROOT
        ['role' => 'root', 'permission' => 'all', 'resource' => 'system'],
        ['role' => 'root', 'permission' => 'manage', 'resource' => 'plans'],
        ['role' => 'root', 'permission' => 'manage', 'resource' => 'companies'],
        ['role' => 'root', 'permission' => 'manage', 'resource' => 'users'],
        
        // Permisos SUPERADMIN
        ['role' => 'superadmin', 'permission' => 'manage', 'resource' => 'company'],
        ['role' => 'superadmin', 'permission' => 'manage', 'resource' => 'units'],
        ['role' => 'superadmin', 'permission' => 'manage', 'resource' => 'businesses'],
        ['role' => 'superadmin', 'permission' => 'manage', 'resource' => 'users'],
        ['role' => 'superadmin', 'permission' => 'invite', 'resource' => 'users'],
        
        // Permisos ADMIN
        ['role' => 'admin', 'permission' => 'manage', 'resource' => 'units'],
        ['role' => 'admin', 'permission' => 'manage', 'resource' => 'businesses'],
        ['role' => 'admin', 'permission' => 'view', 'resource' => 'users'],
        ['role' => 'admin', 'permission' => 'invite', 'resource' => 'users'],
        
        // Permisos USER
        ['role' => 'user', 'permission' => 'view', 'resource' => 'dashboard'],
        ['role' => 'user', 'permission' => 'view', 'resource' => 'profile'],
    ];
    
    foreach ($basic_permissions as $perm) {
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
        }
    }
    echo "✅ Permisos básicos configurados\n";
    
    // Verificar si existen las tablas críticas
    echo "\n🔍 Verificando integridad de tablas críticas...\n";
    
    $critical_tables = [
        'users', 'companies', 'user_companies', 'plans', 
        'units', 'businesses', 'modules', 'notifications', 
        'user_invitations', 'role_permissions'
    ];
    
    $missing_tables = [];
    foreach ($critical_tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if (!$stmt->fetch()) {
            $missing_tables[] = $table;
        } else {
            echo "✅ Tabla '$table' existe\n";
        }
    }
    
    if (!empty($missing_tables)) {
        echo "\n⚠️  TABLAS FALTANTES DETECTADAS:\n";
        foreach ($missing_tables as $table) {
            echo "❌ Tabla '$table' no existe\n";
        }
        echo "\n💡 SOLUCIÓN: Ejecutar primero 'php install_database.php'\n";
    } else {
        echo "\n✅ Todas las tablas críticas están presentes\n";
    }
    
    echo "\n🎉 INSTALACIÓN DE TABLAS FALTANTES COMPLETADA\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Sistema de permisos configurado\n";
    echo "✅ Gestión de sesiones preparada\n";
    echo "✅ Todas las tablas verificadas\n\n";
    
    if (empty($missing_tables)) {
        echo "🚀 EL SISTEMA ESTÁ LISTO PARA USAR\n\n";
        echo "📋 PRÓXIMOS PASOS:\n";
        echo "1. Acceder al login: /auth/\n";
        echo "2. Panel Root: /panel_root/\n";
        echo "3. Gestión de empresas: /companies/\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📁 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
?>
