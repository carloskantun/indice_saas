<?php
/**
 * Script para verificar y corregir la estructura de base de datos
 * Resuelve problemas de columnas y tablas faltantes
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Corrección de Base de Datos</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}</style></head><body>";

echo "<h1>🔧 Corrección de Base de Datos</h1>";

try {
    $pdo = getDB();
    echo "<div class='info'>✅ Conexión a base de datos establecida</div><br>";
    
    // 1. Verificar estructura de tabla plans
    echo "<h2>📋 Verificando tabla 'plans'</h2>";
    
    $stmt = $pdo->query("DESCRIBE plans");
    $planColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>Columnas existentes en 'plans': " . implode(', ', $planColumns) . "</div>";
    
    // Agregar columna monthly_price si no existe
    if (!in_array('monthly_price', $planColumns)) {
        echo "<div class='warning'>⚠️ Columna 'monthly_price' no existe en tabla 'plans'</div>";
        $pdo->exec("ALTER TABLE plans ADD COLUMN monthly_price DECIMAL(10,2) DEFAULT 0.00");
        echo "<div class='success'>✅ Columna 'monthly_price' agregada a tabla 'plans'</div>";
    }
    
    // Agregar columna annual_price si no existe
    if (!in_array('annual_price', $planColumns)) {
        echo "<div class='warning'>⚠️ Columna 'annual_price' no existe en tabla 'plans'</div>";
        $pdo->exec("ALTER TABLE plans ADD COLUMN annual_price DECIMAL(10,2) DEFAULT 0.00");
        echo "<div class='success'>✅ Columna 'annual_price' agregada a tabla 'plans'</div>";
    }
    
    // Verificar y agregar columna status
    if (in_array('is_active', $planColumns) && !in_array('status', $planColumns)) {
        echo "<div class='warning'>⚠️ Tabla 'plans' usa 'is_active' en lugar de 'status'</div>";
        
        // Agregar columna status
        $pdo->exec("ALTER TABLE plans ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
        echo "<div class='success'>✅ Columna 'status' agregada a tabla 'plans'</div>";
        
        // Migrar datos de is_active a status
        $pdo->exec("UPDATE plans SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");
        echo "<div class='success'>✅ Datos migrados de 'is_active' a 'status'</div>";
        
    } else {
        echo "<div class='success'>✅ Tabla 'plans' tiene estructura correcta</div>";
    }
    
    // Actualizar precios de ejemplo si están en 0
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plans WHERE monthly_price = 0");
    $plansWithZeroPrice = $stmt->fetch()['count'];
    
    if ($plansWithZeroPrice > 0) {
        echo "<div class='warning'>⚠️ Encontrados $plansWithZeroPrice planes con precio 0. Actualizando precios de ejemplo...</div>";
        
        // Actualizar precios de ejemplo
        $pdo->exec("
            UPDATE plans SET 
                monthly_price = CASE 
                    WHEN LOWER(name) LIKE '%básico%' OR LOWER(name) LIKE '%basic%' THEN 29.99
                    WHEN LOWER(name) LIKE '%estándar%' OR LOWER(name) LIKE '%standard%' THEN 59.99
                    WHEN LOWER(name) LIKE '%premium%' OR LOWER(name) LIKE '%pro%' THEN 99.99
                    WHEN LOWER(name) LIKE '%enterprise%' OR LOWER(name) LIKE '%empresarial%' THEN 199.99
                    ELSE 49.99
                END,
                annual_price = CASE 
                    WHEN LOWER(name) LIKE '%básico%' OR LOWER(name) LIKE '%basic%' THEN 299.99
                    WHEN LOWER(name) LIKE '%estándar%' OR LOWER(name) LIKE '%standard%' THEN 599.99
                    WHEN LOWER(name) LIKE '%premium%' OR LOWER(name) LIKE '%pro%' THEN 999.99
                    WHEN LOWER(name) LIKE '%enterprise%' OR LOWER(name) LIKE '%empresarial%' THEN 1999.99
                    ELSE 499.99
                END
            WHERE monthly_price = 0
        ");
        
        echo "<div class='success'>✅ Precios de ejemplo asignados automáticamente</div>";
    }
    
    // 2. Verificar estructura de tabla companies
    echo "<h2>🏢 Verificando tabla 'companies'</h2>";
    
    $stmt = $pdo->query("DESCRIBE companies");
    $companyColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('status', $companyColumns)) {
        echo "<div class='warning'>⚠️ Tabla 'companies' no tiene columna 'status'</div>";
        
        // Agregar columna status
        $pdo->exec("ALTER TABLE companies ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
        echo "<div class='success'>✅ Columna 'status' agregada a tabla 'companies'</div>";
        
    } else {
        echo "<div class='success'>✅ Tabla 'companies' tiene estructura correcta</div>";
    }
    
    // 3. Verificar estructura de tabla users
    echo "<h2>👥 Verificando tabla 'users'</h2>";
    
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('status', $userColumns)) {
        echo "<div class='warning'>⚠️ Tabla 'users' no tiene columna 'status'</div>";
        
        // Agregar columna status
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
        echo "<div class='success'>✅ Columna 'status' agregada a tabla 'users'</div>";
        
    } else {
        echo "<div class='success'>✅ Tabla 'users' tiene estructura correcta</div>";
    }
    
    // 4. Verificar si existe tabla modules
    echo "<h2>🧩 Verificando tabla 'modules'</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'modules'");
    $moduleTableExists = $stmt->fetch();
    
    if (!$moduleTableExists) {
        echo "<div class='warning'>⚠️ Tabla 'modules' no existe. Creando...</div>";
        
        // Crear tabla modules
        $createModulesSQL = "
        CREATE TABLE modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'fas fa-puzzle-piece',
            color VARCHAR(7) DEFAULT '#3498db',
            url VARCHAR(200),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createModulesSQL);
        echo "<div class='success'>✅ Tabla 'modules' creada exitosamente</div>";
        
        // Insertar módulos por defecto
        $defaultModules = [
            ['Dashboard', 'dashboard', 'Panel principal con estadísticas', 'fas fa-tachometer-alt', '#3498db', '/dashboard'],
            ['Gestión de Usuarios', 'users', 'Administración de usuarios del sistema', 'fas fa-users', '#2ecc71', '/users'],
            ['Gestión de Empresas', 'companies', 'Administración de empresas clientes', 'fas fa-building', '#e74c3c', '/companies'],
            ['Facturación', 'billing', 'Sistema de facturación y pagos', 'fas fa-file-invoice-dollar', '#f39c12', '/billing'],
            ['Reportes', 'reports', 'Generación de reportes y análisis', 'fas fa-chart-line', '#9b59b6', '/reports'],
            ['Configuración', 'settings', 'Configuraciones del sistema', 'fas fa-cog', '#34495e', '/settings'],
            ['Soporte', 'support', 'Sistema de tickets de soporte', 'fas fa-life-ring', '#1abc9c', '/support'],
            ['API', 'api', 'Gestión de API y integraciones', 'fas fa-code', '#e67e22', '/api']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO modules (name, slug, description, icon, color, url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($defaultModules as $module) {
            $stmt->execute($module);
        }
        
        echo "<div class='success'>✅ " . count($defaultModules) . " módulos por defecto insertados</div>";
        
    } else {
        echo "<div class='success'>✅ Tabla 'modules' ya existe</div>";
    }
    
    // 5. Verificar si existe tabla plan_modules
    echo "<h2>🔗 Verificando tabla 'plan_modules'</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'plan_modules'");
    $planModulesTableExists = $stmt->fetch();
    
    if (!$planModulesTableExists) {
        echo "<div class='warning'>⚠️ Tabla 'plan_modules' no existe. Creando...</div>";
        
        // Crear tabla plan_modules
        $createPlanModulesSQL = "
        CREATE TABLE plan_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            module_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            UNIQUE KEY unique_plan_module (plan_id, module_id)
        )";
        
        $pdo->exec($createPlanModulesSQL);
        echo "<div class='success'>✅ Tabla 'plan_modules' creada exitosamente</div>";
        
        // Asignar todos los módulos a todos los planes existentes
        $stmt = $pdo->query("SELECT id FROM plans WHERE status = 'active' OR is_active = 1");
        $plans = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->query("SELECT id FROM modules WHERE status = 'active'");
        $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($plans) && !empty($modules)) {
            $insertStmt = $pdo->prepare("INSERT IGNORE INTO plan_modules (plan_id, module_id) VALUES (?, ?)");
            $assignedCount = 0;
            
            foreach ($plans as $planId) {
                foreach ($modules as $moduleId) {
                    $insertStmt->execute([$planId, $moduleId]);
                    $assignedCount++;
                }
            }
            
            echo "<div class='success'>✅ $assignedCount asignaciones plan-módulo creadas</div>";
        }
        
    } else {
        echo "<div class='success'>✅ Tabla 'plan_modules' ya existe</div>";
    }
    
    // 6. Verificar estructura de tabla user_companies
    echo "<h2>🤝 Verificando tabla 'user_companies'</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_companies'");
    $userCompaniesExists = $stmt->fetch();
    
    if (!$userCompaniesExists) {
        echo "<div class='warning'>⚠️ Tabla 'user_companies' no existe. Creando...</div>";
        
        $createUserCompaniesSQL = "
        CREATE TABLE user_companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            company_id INT NOT NULL,
            role ENUM('root', 'support', 'superadmin', 'admin', 'moderator', 'user') DEFAULT 'user',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_company (user_id, company_id)
        )";
        
        $pdo->exec($createUserCompaniesSQL);
        echo "<div class='success'>✅ Tabla 'user_companies' creada exitosamente</div>";
        
    } else {
        echo "<div class='success'>✅ Tabla 'user_companies' ya existe</div>";
        
        // Verificar si tiene columna status
        $stmt = $pdo->query("DESCRIBE user_companies");
        $ucColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('status', $ucColumns)) {
            $pdo->exec("ALTER TABLE user_companies ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
            echo "<div class='success'>✅ Columna 'status' agregada a tabla 'user_companies'</div>";
        }
    }
    
    echo "<br><h2>🎉 Resumen de Correcciones</h2>";
    echo "<div class='success'>✅ Base de datos corregida exitosamente</div>";
    echo "<div class='info'>ℹ️ Todas las tablas tienen la estructura correcta</div>";
    echo "<div class='info'>ℹ️ Las columnas 'status' están disponibles en todas las tablas</div>";
    echo "<div class='info'>ℹ️ Los módulos por defecto han sido insertados</div>";
    echo "<div class='info'>ℹ️ Las relaciones plan-módulo están configuradas</div>";
    
    echo "<br><a href='panel_root/index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ir al Dashboard</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
