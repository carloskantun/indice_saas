<?php
/**
 * Debug específico para proveedores en modales
 */

require_once '../../config.php';

if (!checkAuth()) {
    echo "❌ Usuario no autenticado<br>";
    exit;
}

echo "<h3>🔍 Debug - Proveedores en Modales</h3>";

$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;

echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>📋 Variables de Sesión:</h4>";
echo "• Company ID: " . ($company_id ?: 'NULL') . "<br>";
echo "• Business ID: " . ($business_id ?: 'NULL') . "<br>";
echo "</div>";

try {
    $db = getDB();
    
    // Simular exactamente el código del modal
    echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🔍 Consulta de Proveedores (igual que en modals.php):</h4>";
    
    $providers_sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
    echo "<strong>SQL:</strong> <code>$providers_sql</code><br>";
    echo "<strong>Parámetro:</strong> company_id = $company_id<br><br>";
    
    $stmt = $db->prepare($providers_sql);
    $stmt->execute([$company_id]);
    $modal_providers = $stmt->fetchAll();
    
    echo "<strong>Resultados encontrados:</strong> " . count($modal_providers) . "<br>";
    
    if (empty($modal_providers)) {
        echo "❌ <strong>PROBLEMA:</strong> No se encontraron proveedores con la consulta del modal<br>";
        
        // Verificar qué proveedores existen realmente
        echo "<br><strong>🔧 Verificando proveedores en base de datos:</strong><br>";
        
        $all_providers_sql = "SELECT id, name, company_id, status FROM providers ORDER BY company_id, name";
        $stmt2 = $db->prepare($all_providers_sql);
        $stmt2->execute();
        $all_providers = $stmt2->fetchAll();
        
        echo "Total de proveedores en BD: " . count($all_providers) . "<br>";
        
        if ($all_providers) {
            echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr style='background: #d1ecf1;'>";
            echo "<th style='border: 1px solid #bee5eb; padding: 5px;'>ID</th>";
            echo "<th style='border: 1px solid #bee5eb; padding: 5px;'>Nombre</th>";
            echo "<th style='border: 1px solid #bee5eb; padding: 5px;'>Company ID</th>";
            echo "<th style='border: 1px solid #bee5eb; padding: 5px;'>Status</th>";
            echo "<th style='border: 1px solid #bee5eb; padding: 5px;'>¿Coincide?</th>";
            echo "</tr>";
            
            foreach ($all_providers as $provider) {
                $matches = ($provider['company_id'] == $company_id && $provider['status'] == 'active');
                $bg_color = $matches ? '#d4edda' : '#f8d7da';
                
                echo "<tr style='background: $bg_color;'>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . $provider['id'] . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . htmlspecialchars($provider['name']) . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . $provider['company_id'] . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . $provider['status'] . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 5px;'>" . ($matches ? '✅ SÍ' : '❌ NO') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "✅ <strong>PROVEEDORES ENCONTRADOS:</strong><br>";
        foreach ($modal_providers as $provider) {
            echo "• ID: {$provider['id']} - Nombre: " . htmlspecialchars($provider['name']) . "<br>";
        }
    }
    
    echo "</div>";
    
    // Verificar el HTML que se genera
    echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🎯 HTML Generado para Modal:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px;'>";
    echo htmlspecialchars('
<select class="form-select select2" id="provider_id" name="provider_id">
    <option value="">Sin proveedor</option>');
    
    foreach ($modal_providers as $provider) {
        echo htmlspecialchars('
    <option value="' . $provider['id'] . '">' . $provider['name'] . '</option>');
    }
    echo htmlspecialchars('
</select>');
    echo "</pre>";
    echo "</div>";
    
    // Test en vivo de generación
    echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🧪 Test en Vivo - Select de Proveedores:</h4>";
    echo '<select class="form-select" style="width: 300px;">';
    echo '<option value="">Sin proveedor</option>';
    foreach ($modal_providers as $provider) {
        echo '<option value="' . $provider['id'] . '">' . htmlspecialchars($provider['name']) . '</option>';
    }
    echo '</select>';
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='color: #007bff; margin-right: 20px;'>← Volver al módulo de gastos</a>";
echo "<a href='fix_providers.php' style='color: #007bff;'>🔧 Reparar proveedores</a>";
echo "</div>";
?>
