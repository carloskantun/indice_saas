<?php
/**
 * Debug específico para tabla de proveedores
 */

require_once '../../config.php';

if (!checkAuth()) {
    echo "❌ Usuario no autenticado<br>";
    exit;
}

echo "<h3>🔍 Debug de Tabla Proveedores</h3>";

$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;

echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>📋 Contexto Actual:</h4>";
echo "• Company ID: " . $company_id . "<br>";
echo "• Business ID: " . $business_id . "<br>";
echo "</div>";

try {
    $db = getDB();
    
    // Verificar si la tabla providers existe
    echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🗄️ Verificación de Tabla Providers:</h4>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'providers'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✅ Tabla 'providers' existe<br>";
        
        // Verificar estructura
        $stmt = $db->query("DESCRIBE providers");
        $columns = $stmt->fetchAll();
        echo "<strong>Estructura:</strong><br>";
        foreach ($columns as $col) {
            echo "  • " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
        
        // Contar registros totales
        $stmt = $db->query("SELECT COUNT(*) FROM providers");
        $total = $stmt->fetchColumn();
        echo "<br><strong>Total de proveedores:</strong> " . $total . "<br>";
        
        // Contar por company_id
        if ($company_id) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM providers WHERE company_id = ?");
            $stmt->execute([$company_id]);
            $company_total = $stmt->fetchColumn();
            echo "<strong>Proveedores de tu empresa:</strong> " . $company_total . "<br>";
            
            // Contar activos
            $stmt = $db->prepare("SELECT COUNT(*) FROM providers WHERE company_id = ? AND status = 'active'");
            $stmt->execute([$company_id]);
            $active_total = $stmt->fetchColumn();
            echo "<strong>Proveedores activos:</strong> " . $active_total . "<br>";
        }
        
        // Mostrar algunos ejemplos
        $stmt = $db->prepare("SELECT id, name, status, company_id FROM providers ORDER BY id DESC LIMIT 5");
        $stmt->execute();
        $examples = $stmt->fetchAll();
        
        if ($examples) {
            echo "<br><strong>Últimos proveedores:</strong><br>";
            echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr style='background: #d1ecf1;'><th style='border: 1px solid #bee5eb; padding: 5px;'>ID</th><th style='border: 1px solid #bee5eb; padding: 5px;'>Nombre</th><th style='border: 1px solid #bee5eb; padding: 5px;'>Status</th><th style='border: 1px solid #bee5eb; padding: 5px;'>Company ID</th></tr>";
            foreach ($examples as $provider) {
                $bg = $provider['company_id'] == $company_id ? '#d4edda' : '#fff';
                echo "<tr style='background: $bg;'>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . $provider['id'] . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . htmlspecialchars($provider['name']) . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . $provider['status'] . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . $provider['company_id'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ Tabla 'providers' NO existe<br>";
        echo "<strong>Necesitas crear la tabla providers</strong><br>";
        
        // Sugerir creación
        echo "<br><div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
        echo "<strong>💡 Script para crear tabla:</strong><br>";
        echo "<code>CREATE TABLE providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    rfc VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_status (company_id, status),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);</code>";
        echo "</div>";
    }
    echo "</div>";
    
    // Verificar funcionamiento del modal
    echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🔧 Test de Creación de Proveedor:</h4>";
    
    if ($table_exists) {
        echo '<form method="POST" action="controller.php" style="margin-top: 10px;">';
        echo '<input type="hidden" name="action" value="create_provider">';
        echo '<input type="text" name="name" placeholder="Nombre del proveedor" required style="margin-right: 10px; padding: 5px;">';
        echo '<input type="email" name="email" placeholder="Email (opcional)" style="margin-right: 10px; padding: 5px;">';
        echo '<button type="submit" style="padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px;">Crear Proveedor de Prueba</button>';
        echo '</form>';
    } else {
        echo "⚠️ Primero necesitas crear la tabla providers";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<p><a href='index.php' style='color: #007bff;'>← Volver al módulo de gastos</a></p>";
?>
