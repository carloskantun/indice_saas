<?php
/**
 * Script para crear la tabla 'plans' en la base de datos
 * Ejecutar una sola vez para inicializar la estructura de planes SaaS
 */

require_once 'config.php';

try {
    $pdo = getDB();
    
    // Crear tabla plans
    $sql = "
    CREATE TABLE IF NOT EXISTS plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        modules_included JSON,
        users_max INT NOT NULL DEFAULT 1,
        units_max INT NOT NULL DEFAULT 1,
        businesses_max INT NOT NULL DEFAULT 1,
        storage_max_mb INT NOT NULL DEFAULT 100,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Tabla 'plans' creada exitosamente.\n";
    
    // Agregar columna plan_id a la tabla companies
    $sql_alter = "
    ALTER TABLE companies 
    ADD COLUMN plan_id INT DEFAULT 1,
    ADD FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
    ";
    
    try {
        $pdo->exec($sql_alter);
        echo "✅ Columna 'plan_id' agregada a tabla 'companies'.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Columna 'plan_id' ya existe en tabla 'companies'.\n";
        } else {
            echo "⚠️ Advertencia al agregar columna 'plan_id': " . $e->getMessage() . "\n";
        }
    }
    
    $pdo->exec($sql);
    echo "✅ Tabla 'plans' creada exitosamente.\n";
    
    // Insertar planes predefinidos
    $plans = [
        [
            'name' => 'Free',
            'description' => 'Plan gratuito para empresas pequeñas',
            'price_monthly' => 0.00,
            'modules_included' => json_encode(['gastos', 'mantenimiento']),
            'users_max' => 3,
            'units_max' => 1,
            'businesses_max' => 1,
            'storage_max_mb' => 100,
            'is_active' => true
        ],
        [
            'name' => 'Starter',
            'description' => 'Plan inicial para empresas en crecimiento',
            'price_monthly' => 25.00,
            'modules_included' => json_encode(['gastos', 'mantenimiento', 'servicio_cliente', 'compras', 'lavanderia']),
            'users_max' => 10,
            'units_max' => 5,
            'businesses_max' => 10,
            'storage_max_mb' => 500,
            'is_active' => true
        ],
        [
            'name' => 'Pro',
            'description' => 'Plan profesional con todas las funciones',
            'price_monthly' => 75.00,
            'modules_included' => json_encode(['gastos', 'mantenimiento', 'servicio_cliente', 'compras', 'lavanderia', 'transfers', 'kpis', 'reportes']),
            'users_max' => 25,
            'units_max' => 10,
            'businesses_max' => 25,
            'storage_max_mb' => 2000,
            'is_active' => true
        ],
        [
            'name' => 'Enterprise',
            'description' => 'Plan empresarial sin límites',
            'price_monthly' => 200.00,
            'modules_included' => json_encode(['gastos', 'mantenimiento', 'servicio_cliente', 'compras', 'lavanderia', 'transfers', 'kpis', 'reportes', 'integraciones', 'api']),
            'users_max' => -1, // -1 = ilimitado
            'units_max' => -1,
            'businesses_max' => -1,
            'storage_max_mb' => -1,
            'is_active' => true
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO plans (name, description, price_monthly, modules_included, users_max, units_max, businesses_max, storage_max_mb, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($plans as $plan) {
        $stmt->execute([
            $plan['name'],
            $plan['description'],
            $plan['price_monthly'],
            $plan['modules_included'],
            $plan['users_max'],
            $plan['units_max'],
            $plan['businesses_max'],
            $plan['storage_max_mb'],
            $plan['is_active']
        ]);
    }
    
    echo "✅ Planes predefinidos insertados exitosamente.\n";
    
    // Crear usuario root global si no existe
    try {
        // Verificar si ya existe un usuario root global
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email 
            FROM users u 
            INNER JOIN user_companies uc ON u.id = uc.user_id 
            WHERE uc.role = 'root' 
            LIMIT 1
        ");
        $rootUser = $stmt->fetch();
        
        if (!$rootUser) {
            // Crear usuario root
            $rootEmail = 'root@indiceapp.com';
            $rootPassword = password_hash('root123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute(['Root Administrator', $rootEmail, $rootPassword]);
            $rootUserId = $pdo->lastInsertId();
            
            // Crear empresa "Sistema" para el usuario root
            $stmt = $pdo->prepare("INSERT INTO companies (name, description, status, created_by) VALUES (?, ?, 'active', ?)");
            $stmt->execute(['Sistema', 'Empresa del sistema para administración', $rootUserId]);
            $systemCompanyId = $pdo->lastInsertId();
            
            // Asignar rol root al usuario en la empresa del sistema
            $stmt = $pdo->prepare("INSERT INTO user_companies (user_id, company_id, role, status) VALUES (?, ?, 'root', 'active')");
            $stmt->execute([$rootUserId, $systemCompanyId]);
            
            echo "✅ Usuario root creado exitosamente.\n";
            echo "📧 Email: $rootEmail\n";
            echo "🔐 Password: root123\n";
            echo "⚠️ Cambia estas credenciales en producción.\n";
        } else {
            echo "ℹ️ Usuario root ya existe: {$rootUser['name']} ({$rootUser['email']})\n";
        }
    } catch (PDOException $e) {
        echo "⚠️ Advertencia al crear usuario root: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
