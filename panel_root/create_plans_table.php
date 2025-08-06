<?php
/**
 * Creador de Planes SaaS y Usuario Root
 * Ejecutar después de install_database.php
 */

require_once '../config.php';

echo "=== CREADOR DE PLANES SAAS Y USUARIO ROOT ===\n\n";

try {
    $db = getDB();
    echo "✅ Conexión a base de datos establecida\n\n";
    
    // Crear planes SaaS
    echo "📦 Creando planes SaaS...\n";
    
    $plans = [
        [
            'id' => 1,
            'name' => 'Plan Básico',
            'description' => 'Perfecto para empresas pequeñas',
            'max_users' => 10,
            'max_companies' => 1,
            'max_units' => 5,
            'max_businesses' => 20,
            'features' => json_encode(['dashboard', 'usuarios', 'empresas']),
            'price' => 0.00
        ],
        [
            'id' => 2,
            'name' => 'Plan Profesional',
            'description' => 'Para empresas en crecimiento',
            'max_users' => 50,
            'max_companies' => 3,
            'max_units' => 15,
            'max_businesses' => 100,
            'features' => json_encode(['dashboard', 'usuarios', 'empresas', 'reportes', 'api']),
            'price' => 29.99
        ],
        [
            'id' => 3,
            'name' => 'Plan Enterprise',
            'description' => 'Para grandes organizaciones',
            'max_users' => 500,
            'max_companies' => 10,
            'max_units' => 50,
            'max_businesses' => 1000,
            'features' => json_encode(['todas_las_funciones', 'soporte_prioritario', 'integraciones']),
            'price' => 99.99
        ]
    ];
    
    foreach ($plans as $plan) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM plans WHERE id = ?");
        $stmt->execute([$plan['id']]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("
                INSERT INTO plans (id, name, description, max_users, max_companies, max_units, max_businesses, features, price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $plan['id'], $plan['name'], $plan['description'], 
                $plan['max_users'], $plan['max_companies'], $plan['max_units'], 
                $plan['max_businesses'], $plan['features'], $plan['price']
            ]);
            echo "✅ Plan '{$plan['name']}' creado\n";
        } else {
            echo "⚠️  Plan '{$plan['name']}' ya existe\n";
        }
    }
    
    echo "\n👤 Creando usuario root...\n";
    
    // Verificar si ya existe usuario root
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'root@indiceapp.com'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        // Crear usuario root
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, status) 
            VALUES ('Root User', 'root@indiceapp.com', ?, 'active')
        ");
        $stmt->execute([password_hash('root123', PASSWORD_DEFAULT)]);
        $root_user_id = $db->lastInsertId();
        echo "✅ Usuario root creado con ID: $root_user_id\n";
        
        // Crear empresa para root (opcional)
        $stmt = $db->prepare("
            INSERT INTO companies (name, description, plan_id, status) 
            VALUES ('Indice SaaS Admin', 'Empresa administrativa del sistema', 3, 'active')
        ");
        $stmt->execute();
        $admin_company_id = $db->lastInsertId();
        echo "✅ Empresa administrativa creada con ID: $admin_company_id\n";
        
        // Asignar usuario root a empresa administrativa
        $stmt = $db->prepare("
            INSERT INTO user_companies (user_id, company_id, role, status) 
            VALUES (?, ?, 'root', 'active')
        ");
        $stmt->execute([$root_user_id, $admin_company_id]);
        echo "✅ Usuario root asignado a empresa administrativa\n";
        
    } else {
        echo "⚠️  Usuario root ya existe\n";
    }
    
    echo "\n👨‍💼 Creando usuario admin de ejemplo...\n";
    
    // Verificar si ya existe usuario admin
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@indiceapp.com'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        // Crear usuario admin
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, status) 
            VALUES ('Admin Example', 'admin@indiceapp.com', ?, 'active')
        ");
        $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
        $admin_user_id = $db->lastInsertId();
        echo "✅ Usuario admin creado con ID: $admin_user_id\n";
        
        // Crear empresa de ejemplo
        $stmt = $db->prepare("
            INSERT INTO companies (name, description, plan_id, status) 
            VALUES ('Empresa Ejemplo', 'Empresa de demostración del sistema', 2, 'active')
        ");
        $stmt->execute();
        $example_company_id = $db->lastInsertId();
        echo "✅ Empresa ejemplo creada con ID: $example_company_id\n";
        
        // Asignar usuario admin a empresa ejemplo
        $stmt = $db->prepare("
            INSERT INTO user_companies (user_id, company_id, role, status) 
            VALUES (?, ?, 'superadmin', 'active')
        ");
        $stmt->execute([$admin_user_id, $example_company_id]);
        echo "✅ Usuario admin asignado a empresa ejemplo\n";
        
    } else {
        echo "⚠️  Usuario admin ya existe\n";
    }
    
    echo "\n🎉 CONFIGURACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Planes SaaS configurados\n";
    echo "✅ Usuario root creado\n";
    echo "✅ Usuario admin de ejemplo creado\n\n";
    echo "🔑 CREDENCIALES DE ACCESO:\n";
    echo "┌─────────────────────────────────────────────┐\n";
    echo "│ ROOT (Acceso total al sistema)             │\n";
    echo "│ Email: root@indiceapp.com                   │\n";
    echo "│ Password: root123                           │\n";
    echo "├─────────────────────────────────────────────┤\n";
    echo "│ ADMIN (Gestión empresarial)                │\n";
    echo "│ Email: admin@indiceapp.com                  │\n";
    echo "│ Password: admin123                          │\n";
    echo "└─────────────────────────────────────────────┘\n\n";
    echo "⚠️  IMPORTANTE: Cambiar estas credenciales en producción\n\n";
    echo "📋 PRÓXIMOS PASOS:\n";
    echo "1. Acceder al sistema: http://tu-dominio.com/\n";
    echo "2. Panel Root: http://tu-dominio.com/panel_root/\n";
    echo "3. Configurar empresas y usuarios adicionales\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📁 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}
?>
