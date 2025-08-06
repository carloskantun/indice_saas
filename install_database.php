<?php
/**
 * Script de Instalación de Base de Datos - Indice SaaS
 * Crea todas las tablas necesarias para el funcionamiento del sistema
 */

require_once 'config.php';

echo "=== INSTALADOR DE BASE DE DATOS INDICE SAAS ===\n\n";

try {
    $db = getDB();
    echo "✅ Conexión a base de datos establecida\n\n";
    
    // Crear tabla users
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        avatar VARCHAR(255),
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'users' creada\n";
    
    // Crear tabla companies
    $sql = "
    CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        plan_id INT DEFAULT 1,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_plan (plan_id),
        INDEX idx_status (status)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'companies' creada\n";
    
    // Crear tabla plans
    $sql = "
    CREATE TABLE IF NOT EXISTS plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        max_users INT DEFAULT 10,
        max_companies INT DEFAULT 1,
        max_units INT DEFAULT 5,
        max_businesses INT DEFAULT 20,
        features JSON,
        price DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✅ Tabla 'plans' creada\n";
    
    // Crear tabla user_companies (relación usuarios-empresas)
    $sql = "
    CREATE TABLE IF NOT EXISTS user_companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        company_id INT NOT NULL,
        role ENUM('root', 'support', 'superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_accessed TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_company (user_id, company_id),
        INDEX idx_user (user_id),
        INDEX idx_company (company_id),
        INDEX idx_role (role)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'user_companies' creada\n";
    
    // Crear tabla units
    $sql = "
    CREATE TABLE IF NOT EXISTS units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        manager_id INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_company (company_id),
        INDEX idx_manager (manager_id),
        INDEX idx_status (status)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'units' creada\n";
    
    // Crear tabla businesses
    $sql = "
    CREATE TABLE IF NOT EXISTS businesses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unit_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        address TEXT,
        manager_id INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
        FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_unit (unit_id),
        INDEX idx_manager (manager_id),
        INDEX idx_status (status)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'businesses' creada\n";
    
    // Crear tabla modules
    $sql = "
    CREATE TABLE IF NOT EXISTS modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        path VARCHAR(255) NOT NULL,
        icon VARCHAR(50) DEFAULT 'fas fa-cube',
        status ENUM('active', 'inactive') DEFAULT 'active',
        required_role ENUM('root', 'support', 'superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_role (required_role)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'modules' creada\n";
    
    // Crear tabla notifications
    $sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        data JSON,
        status ENUM('pending', 'unread', 'read', 'completed') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_company_user (company_id, user_id),
        INDEX idx_status (status),
        INDEX idx_type (type),
        INDEX idx_created (created_at)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'notifications' creada\n";
    
    // Crear tabla user_invitations
    $sql = "
    CREATE TABLE IF NOT EXISTS user_invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        role ENUM('superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
        token VARCHAR(100) UNIQUE NOT NULL,
        status ENUM('pending', 'accepted', 'expired', 'cancelled') DEFAULT 'pending',
        sent_by INT NOT NULL,
        sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        accepted_at TIMESTAMP NULL,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_company (company_id)
    )";
    $db->exec($sql);
    echo "✅ Tabla 'user_invitations' creada\n";
    
    // Insertar plan básico por defecto
    $stmt = $db->prepare("SELECT COUNT(*) FROM plans WHERE id = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $sql = "
        INSERT INTO plans (id, name, description, max_users, max_companies, max_units, max_businesses, features, price) 
        VALUES (1, 'Plan Básico', 'Plan básico para empresas pequeñas', 10, 1, 5, 20, '{}', 0.00)
        ";
        $db->exec($sql);
        echo "✅ Plan básico por defecto creado\n";
    }
    
    // Insertar módulos básicos
    $modules = [
        ['name' => 'Dashboard', 'path' => '/dashboard/', 'icon' => 'fas fa-tachometer-alt'],
        ['name' => 'Usuarios', 'path' => '/admin/usuarios.php', 'icon' => 'fas fa-users'],
        ['name' => 'Empresas', 'path' => '/companies/', 'icon' => 'fas fa-building'],
        ['name' => 'Unidades', 'path' => '/units/', 'icon' => 'fas fa-industry'],
        ['name' => 'Negocios', 'path' => '/businesses/', 'icon' => 'fas fa-store']
    ];
    
    foreach ($modules as $module) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM modules WHERE path = ?");
        $stmt->execute([$module['path']]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO modules (name, path, icon) VALUES (?, ?, ?)");
            $stmt->execute([$module['name'], $module['path'], $module['icon']]);
        }
    }
    echo "✅ Módulos básicos creados\n";
    
    echo "\n🎉 INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Todas las tablas han sido creadas\n";
    echo "✅ Plan básico configurado\n";
    echo "✅ Módulos básicos instalados\n\n";
    echo "📋 PRÓXIMOS PASOS:\n";
    echo "1. Ejecutar: php panel_root/create_plans_table.php\n";
    echo "2. Acceder al sistema con las credenciales root\n";
    echo "3. Configurar empresas y usuarios desde el panel\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📁 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
?>
