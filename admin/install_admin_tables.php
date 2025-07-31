<?php
/**
 * Script para crear las tablas necesarias para gestión de usuarios admin
 */

require_once '../config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Creación Tablas Admin</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}</style></head><body>";

echo "<h1>🔧 Creación de Tablas para Gestión de Usuarios Admin</h1>";

try {
    $pdo = getDB();
    echo "<div class='info'>✅ Conexión a base de datos establecida</div><br>";
    
    // 1. Crear tabla invitations
    echo "<h2>📧 Creando tabla 'invitations'</h2>";
    
    $createInvitationsSQL = "
    CREATE TABLE IF NOT EXISTS invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        company_id INT NOT NULL,
        unit_id INT DEFAULT NULL,
        business_id INT DEFAULT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'user',
        token VARCHAR(64) NOT NULL UNIQUE,
        status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
        sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expiration_date TIMESTAMP NULL,
        sent_by INT NOT NULL,
        INDEX idx_token (token),
        INDEX idx_email (email),
        INDEX idx_company (company_id),
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($createInvitationsSQL);
    echo "<div class='success'>✅ Tabla 'invitations' creada</div>";
    
    // 2. Verificar/crear tabla usuarios_x_empresa (user_companies)
    echo "<h2>🏢 Verificando tabla 'user_companies'</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_companies'");
    if (!$stmt->fetch()) {
        $createUserCompaniesSQL = "
        CREATE TABLE user_companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            company_id INT NOT NULL,
            role ENUM('superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_company (user_id, company_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createUserCompaniesSQL);
        echo "<div class='success'>✅ Tabla 'user_companies' creada</div>";
    } else {
        echo "<div class='info'>ℹ️ Tabla 'user_companies' ya existe</div>";
    }
    
    // 3. Crear tabla usuarios_x_unidad
    echo "<h2>🏭 Creando tabla 'user_units'</h2>";
    
    $createUserUnitsSQL = "
    CREATE TABLE IF NOT EXISTS user_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        unit_id INT NOT NULL,
        role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_unit (user_id, unit_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($createUserUnitsSQL);
    echo "<div class='success'>✅ Tabla 'user_units' creada</div>";
    
    // 4. Crear tabla usuarios_x_negocio
    echo "<h2>🏪 Creando tabla 'user_businesses'</h2>";
    
    $createUserBusinessesSQL = "
    CREATE TABLE IF NOT EXISTS user_businesses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        business_id INT NOT NULL,
        role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_business (user_id, business_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($createUserBusinessesSQL);
    echo "<div class='success'>✅ Tabla 'user_businesses' creada</div>";
    
    // 5. Crear tabla permisos
    echo "<h2>🔑 Creando tabla 'permissions'</h2>";
    
    $createPermissionsSQL = "
    CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(100) NOT NULL UNIQUE,
        description VARCHAR(255),
        module VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createPermissionsSQL);
    echo "<div class='success'>✅ Tabla 'permissions' creada</div>";
    
    // 6. Crear tabla role_permissions
    echo "<h2>👥 Creando tabla 'role_permissions'</h2>";
    
    $createRolePermissionsSQL = "
    CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        permission_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_role_permission (role, permission_id),
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($createRolePermissionsSQL);
    echo "<div class='success'>✅ Tabla 'role_permissions' creada</div>";
    
    // 7. Insertar permisos básicos
    echo "<h2>📋 Insertando permisos básicos</h2>";
    
    $basicPermissions = [
        ['expenses.view', 'View expenses', 'expenses'],
        ['expenses.create', 'Create expenses', 'expenses'],
        ['expenses.edit', 'Edit expenses', 'expenses'],
        ['expenses.delete', 'Delete expenses', 'expenses'],
        ['users.view', 'View users', 'users'],
        ['users.invite', 'Invite users', 'users'],
        ['users.edit', 'Edit users', 'users'],
        ['users.suspend', 'Suspend users', 'users'],
        ['reports.view', 'View reports', 'reports'],
        ['settings.view', 'View settings', 'settings'],
        ['settings.edit', 'Edit settings', 'settings']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (key_name, description, module) VALUES (?, ?, ?)");
    $insertedCount = 0;
    
    foreach ($basicPermissions as $permission) {
        $result = $stmt->execute($permission);
        if ($stmt->rowCount() > 0) {
            $insertedCount++;
        }
    }
    
    echo "<div class='success'>✅ $insertedCount permisos insertados</div>";
    
    // 8. Asignar permisos a roles
    echo "<h2>🔗 Asignando permisos a roles</h2>";
    
    // Obtener IDs de permisos
    $stmt = $pdo->query("SELECT id, key_name FROM permissions");
    $permissions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
        // Definir asignaciones por rol
    $rolePermissions = [
        'superadmin' => array_values($permissions), // Todos los permisos
        'admin' => [
            $permissions['expenses.view'] ?? null,
            $permissions['expenses.create'] ?? null,
            $permissions['expenses.edit'] ?? null,
            $permissions['users.view'] ?? null,
            $permissions['users.invite'] ?? null,
            $permissions['reports.view'] ?? null,
            $permissions['settings.view'] ?? null
        ],
        'moderator' => [
            $permissions['expenses.view'] ?? null,
            $permissions['expenses.create'] ?? null,
            $permissions['expenses.edit'] ?? null,
            $permissions['users.view'] ?? null,
            $permissions['reports.view'] ?? null
        ],
        'user' => [
            $permissions['expenses.view'] ?? null,
            $permissions['expenses.create'] ?? null
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (?, ?)");
    $assignedCount = 0;
    
    foreach ($rolePermissions as $role => $permissionIds) {
        foreach ($permissionIds as $permissionId) {
            if ($permissionId) {
                $result = $stmt->execute([$role, $permissionId]);
                if ($stmt->rowCount() > 0) {
                    $assignedCount++;
                }
            }
        }
    }
    
    echo "<div class='success'>✅ $assignedCount asignaciones rol-permiso creadas</div>";
    
    // 9. Verificar si existen tablas units y businesses
    echo "<h2>🏗️ Verificando tablas relacionadas</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'units'");
    if (!$stmt->fetch()) {
        $createUnitsSQL = "
        CREATE TABLE units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            company_id INT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createUnitsSQL);
        echo "<div class='success'>✅ Tabla 'units' creada</div>";
    } else {
        echo "<div class='info'>ℹ️ Tabla 'units' ya existe</div>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'businesses'");
    if (!$stmt->fetch()) {
        $createBusinessesSQL = "
        CREATE TABLE businesses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            unit_id INT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createBusinessesSQL);
        echo "<div class='success'>✅ Tabla 'businesses' creada</div>";
    } else {
        echo "<div class='info'>ℹ️ Tabla 'businesses' ya existe</div>";
    }
    
    // Crear trigger para establecer fecha de expiración automáticamente
    echo "<h2>⚡ Creando trigger para invitations</h2>";
    
    // Primero eliminar el trigger si existe
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS set_invitation_expiration");
    } catch (PDOException $e) {
        // Ignorar si no existe
    }
    
    // Crear el trigger
    $triggerSQL = "
    CREATE TRIGGER set_invitation_expiration 
    BEFORE INSERT ON invitations
    FOR EACH ROW
    BEGIN
        IF NEW.expiration_date IS NULL THEN
            SET NEW.expiration_date = DATE_ADD(NOW(), INTERVAL 48 HOUR);
        END IF;
    END";
    
    try {
        $pdo->exec($triggerSQL);
        echo "<div class='success'>✅ Trigger para fecha de expiración creado</div>";
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ Trigger no creado (puede que ya exista): " . $e->getMessage() . "</div>";
    }
    
    echo "<br><h2>🎉 Creación de Tablas Completada</h2>";
    echo "<div class='success'>✅ Todas las tablas necesarias han sido creadas</div>";
    echo "<div class='info'>ℹ️ Sistema de permisos configurado</div>";
    echo "<div class='info'>ℹ️ Tablas de relaciones usuario-empresa-unidad-negocio listas</div>";
    
    echo "<br><a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ir al Sistema Admin</a>";
    echo "<a href='../index.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Volver al Panel Principal</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
