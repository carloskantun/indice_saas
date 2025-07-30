<?php
require_once 'config.php';

try {
    $db = getDB();
    
    echo "Iniciando creación de tablas del sistema SaaS...\n\n";
    
    // Tabla de usuarios
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✓ Tabla 'users' creada\n";
    
    // Tabla de empresas
    $sql = "CREATE TABLE IF NOT EXISTS companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "✓ Tabla 'companies' creada\n";
    
    // Tabla de relación usuario-empresa (con roles)
    $sql = "CREATE TABLE IF NOT EXISTS user_companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        company_id INT NOT NULL,
        role ENUM('root', 'support', 'superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_company (user_id, company_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "✓ Tabla 'user_companies' creada\n";
    
    // Tabla de unidades de negocio
    $sql = "CREATE TABLE IF NOT EXISTS units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        company_id INT NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "✓ Tabla 'units' creada\n";
    
    // Tabla de tipos de negocio
    $sql = "CREATE TABLE IF NOT EXISTS business_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✓ Tabla 'business_types' creada\n";
    
    // Tabla de negocios
    $sql = "CREATE TABLE IF NOT EXISTS businesses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        type_id INT NULL,
        unit_id INT NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (type_id) REFERENCES business_types(id) ON DELETE SET NULL,
        FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "✓ Tabla 'businesses' creada\n";
    
    // Insertar tipos de negocio predeterminados
    $businessTypes = [
        ['Restaurante', 'Negocio de comida y bebidas'],
        ['Tienda de Retail', 'Venta al por menor'],
        ['Servicios Profesionales', 'Consultoría, asesoría, etc.'],
        ['E-commerce', 'Comercio electrónico'],
        ['Manufactura', 'Producción y fabricación'],
        ['Tecnología', 'Software, hardware, IT'],
        ['Salud', 'Servicios médicos y de salud'],
        ['Educación', 'Servicios educativos'],
        ['Inmobiliario', 'Bienes raíces'],
        ['Transporte', 'Logística y transporte'],
        ['Otro', 'Otros tipos de negocio']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO business_types (name, description) VALUES (?, ?)");
    foreach ($businessTypes as $type) {
        $stmt->execute($type);
    }
    echo "✓ Tipos de negocio predeterminados insertados\n";
    
    // Crear usuario administrador por defecto (opcional)
    $adminEmail = 'admin@indiceapp.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT IGNORE INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute(['Administrador', $adminEmail, $adminPassword]);
    
    if ($db->lastInsertId()) {
        echo "✓ Usuario administrador creado (email: $adminEmail, password: admin123)\n";
    } else {
        echo "✓ Usuario administrador ya existe\n";
    }
    
    echo "\n¡Base de datos inicializada correctamente!\n";
    echo "\nPuedes acceder al sistema con:\n";
    echo "Email: $adminEmail\n";
    echo "Password: admin123\n\n";
    echo "No olvides cambiar estas credenciales en un entorno de producción.\n";
    
} catch (Exception $e) {
    echo "Error al crear las tablas: " . $e->getMessage() . "\n";
    die();
}
?>
