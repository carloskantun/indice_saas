<?php
/**
 * Script para crear/reparar tabla de proveedores y datos de prueba
 */

require_once '../../config.php';

if (!checkAuth()) {
    echo "❌ Usuario no autenticado<br>";
    exit;
}

echo "<h3>🔧 Reparación de Proveedores</h3>";

$company_id = $_SESSION['company_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

try {
    $db = getDB();
    
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'providers'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "⚠️ Tabla 'providers' no existe. Creándola...<br>";
        
        $create_sql = "
        CREATE TABLE providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            email VARCHAR(255),
            address TEXT,
            rfc VARCHAR(20),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_company_status (company_id, status)
        )";
        
        $db->exec($create_sql);
        echo "✅ Tabla 'providers' creada exitosamente<br>";
        echo "</div>";
    } else {
        echo "✅ Tabla 'providers' ya existe<br>";
    }
    
    // Verificar si hay proveedores para la empresa
    $stmt = $db->prepare("SELECT COUNT(*) FROM providers WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $provider_count = $stmt->fetchColumn();
    
    echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>📊 Estado Actual:</h4>";
    echo "• Proveedores en tu empresa (ID: $company_id): <strong>$provider_count</strong><br>";
    
    if ($provider_count == 0) {
        echo "<br>⚠️ No tienes proveedores. Creando proveedores de ejemplo...<br><br>";
        
        // Crear proveedores de ejemplo
        $example_providers = [
            ['name' => 'Proveedor General', 'email' => 'general@empresa.com', 'phone' => '555-0001'],
            ['name' => 'Materiales y Suministros SA', 'email' => 'ventas@materiales.com', 'phone' => '555-0002'],
            ['name' => 'Servicios Técnicos Especializados', 'email' => 'info@servicios.com', 'phone' => '555-0003'],
            ['name' => 'Distribuidora de Oficina', 'email' => 'pedidos@oficina.com', 'phone' => '555-0004'],
            ['name' => 'Transportes y Logística', 'email' => 'operaciones@transporte.com', 'phone' => '555-0005']
        ];
        
        $insert_sql = "
            INSERT INTO providers (company_id, name, email, phone, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, 'active', ?, NOW())
        ";
        $stmt = $db->prepare($insert_sql);
        
        foreach ($example_providers as $provider) {
            $stmt->execute([
                $company_id,
                $provider['name'],
                $provider['email'],
                $provider['phone'],
                $user_id
            ]);
            echo "✅ Creado: " . $provider['name'] . "<br>";
        }
        
        echo "<br><strong>🎉 Se crearon " . count($example_providers) . " proveedores de ejemplo</strong><br>";
    }
    echo "</div>";
    
    // Mostrar proveedores actuales
    $stmt = $db->prepare("
        SELECT id, name, email, phone, status, created_at
        FROM providers 
        WHERE company_id = ? 
        ORDER BY name
    ");
    $stmt->execute([$company_id]);
    $providers = $stmt->fetchAll();
    
    if ($providers) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>📋 Proveedores Disponibles:</h4>";
        echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #c3e6cb;'>";
        echo "<th style='border: 1px solid #b8dacc; padding: 8px;'>ID</th>";
        echo "<th style='border: 1px solid #b8dacc; padding: 8px;'>Nombre</th>";
        echo "<th style='border: 1px solid #b8dacc; padding: 8px;'>Email</th>";
        echo "<th style='border: 1px solid #b8dacc; padding: 8px;'>Teléfono</th>";
        echo "<th style='border: 1px solid #b8dacc; padding: 8px;'>Status</th>";
        echo "</tr>";
        
        foreach ($providers as $provider) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . $provider['id'] . "</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>" . htmlspecialchars($provider['name']) . "</strong></td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($provider['email']) . "</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($provider['phone']) . "</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . $provider['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    // Test rápido de creación
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🧪 Crear Proveedor de Prueba:</h4>";
    echo '<form method="POST" style="margin-top: 10px;">';
    echo '<div style="margin-bottom: 10px;">';
    echo '<input type="text" name="test_name" placeholder="Nombre del proveedor" required style="margin-right: 10px; padding: 8px; width: 200px;">';
    echo '<input type="email" name="test_email" placeholder="Email (opcional)" style="margin-right: 10px; padding: 8px; width: 200px;">';
    echo '</div>';
    echo '<div style="margin-bottom: 10px;">';
    echo '<input type="text" name="test_phone" placeholder="Teléfono (opcional)" style="margin-right: 10px; padding: 8px; width: 150px;">';
    echo '<button type="submit" name="create_test" style="padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 3px;">Crear Proveedor</button>';
    echo '</div>';
    echo '</form>';
    
    // Procesar creación de prueba
    if (isset($_POST['create_test']) && !empty($_POST['test_name'])) {
        $stmt = $db->prepare("
            INSERT INTO providers (company_id, name, email, phone, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, 'active', ?, NOW())
        ");
        
        $result = $stmt->execute([
            $company_id,
            $_POST['test_name'],
            $_POST['test_email'] ?: null,
            $_POST['test_phone'] ?: null,
            $user_id
        ]);
        
        if ($result) {
            echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 3px; margin-top: 10px;'>";
            echo "✅ Proveedor '<strong>" . htmlspecialchars($_POST['test_name']) . "</strong>' creado exitosamente!";
            echo "</div>";
            echo "<script>setTimeout(() => window.location.reload(), 1500);</script>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 3px; margin-top: 10px;'>";
            echo "❌ Error al crear el proveedor";
            echo "</div>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='color: #007bff; margin-right: 20px;'>← Volver al módulo de gastos</a>";
echo "<a href='debug_providers.php' style='color: #007bff;'>🔍 Debug proveedores</a>";
echo "</div>";
?>
