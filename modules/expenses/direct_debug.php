<?php
/**
 * Debug directo de proveedores en modales
 */

// Incluir configuración y verificar autenticación
require_once '../../config.php';

if (!checkAuth()) {
    die("❌ Error: Usuario no autenticado");
}

echo "<h1>🔍 Debug Proveedores en Modales</h1>";

// Variables de sesión
$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;

echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h2>📋 Variables de Sesión</h2>";
echo "<p><strong>Company ID:</strong> " . ($company_id ?? 'NULL') . "</p>";
echo "<p><strong>Business ID:</strong> " . ($business_id ?? 'NULL') . "</p>";
echo "<p><strong>Usuario ID:</strong> " . ($_SESSION['user_id'] ?? 'NULL') . "</p>";
echo "<p><strong>Usuario:</strong> " . ($_SESSION['username'] ?? 'NULL') . "</p>";
echo "</div>";

// Test de conexión a base de datos
try {
    $db = getDB();
    echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h2>✅ Conexión a Base de Datos</h2>";
    echo "<p>Conexión exitosa</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #ffeaea; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h2>❌ Error de Conexión a Base de Datos</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

// Test directo de consulta de proveedores
if (!$company_id) {
    echo "<div style='background: #ffeaea; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h2>❌ Error: No Company ID</h2>";
    echo "<p>No se encontró company_id en la sesión</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h2>🔍 Consultando Proveedores</h2>";
    
    $providers_sql = "SELECT id, name, status, company_id FROM providers WHERE company_id = ? ORDER BY name";
    echo "<p><strong>SQL:</strong> <code>$providers_sql</code></p>";
    echo "<p><strong>Parámetro:</strong> company_id = $company_id</p>";
    
    try {
        $stmt = $db->prepare($providers_sql);
        $stmt->execute([$company_id]);
        $all_providers = $stmt->fetchAll();
        
        echo "<h3>📊 Resultados (Todos los proveedores):</h3>";
        echo "<p>Total encontrados: " . count($all_providers) . "</p>";
        
        if (count($all_providers) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nombre</th><th>Estado</th><th>Company ID</th></tr>";
            foreach ($all_providers as $provider) {
                $style = $provider['status'] === 'active' ? 'background: #d4edda;' : 'background: #f8d7da;';
                echo "<tr style='$style'>";
                echo "<td>" . htmlspecialchars($provider['id']) . "</td>";
                echo "<td>" . htmlspecialchars($provider['name']) . "</td>";
                echo "<td>" . htmlspecialchars($provider['status']) . "</td>";
                echo "<td>" . htmlspecialchars($provider['company_id']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Filtrar solo activos (como en modals.php)
        $active_providers_sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
        $stmt2 = $db->prepare($active_providers_sql);
        $stmt2->execute([$company_id]);
        $active_providers = $stmt2->fetchAll();
        
        echo "<h3>✅ Proveedores Activos (Para Modales):</h3>";
        echo "<p>Total activos: " . count($active_providers) . "</p>";
        
        if (count($active_providers) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #d4edda;'><th>ID</th><th>Nombre</th></tr>";
            foreach ($active_providers as $provider) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($provider['id']) . "</td>";
                echo "<td>" . htmlspecialchars($provider['name']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>🏗️ HTML para Select (Simulación):</h3>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
            echo htmlspecialchars('<select name="provider_id">') . "\n";
            echo htmlspecialchars('    <option value="">Sin proveedor</option>') . "\n";
            foreach ($active_providers as $provider) {
                echo htmlspecialchars('    <option value="' . $provider['id'] . '">' . $provider['name'] . '</option>') . "\n";
            }
            echo htmlspecialchars('</select>');
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ No hay proveedores activos para mostrar en los modales</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

// Test del archivo modals.php
echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h2>🔧 Test del Archivo modals.php</h2>";

if (file_exists('modals.php')) {
    echo "<p>✅ Archivo modals.php encontrado</p>";
    
    // Simular lo que hace modals.php
    ob_start();
    $modal_providers = [];
    if ($company_id) {
        $providers_sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
        $stmt = $db->prepare($providers_sql);
        $stmt->execute([$company_id]);
        $modal_providers = $stmt->fetchAll();
    }
    
    echo "<p>Proveedores cargados en \$modal_providers: " . count($modal_providers) . "</p>";
    
    if (count($modal_providers) > 0) {
        echo "<h4>Lista de \$modal_providers:</h4>";
        echo "<ul>";
        foreach ($modal_providers as $p) {
            echo "<li>ID: {$p['id']}, Nombre: {$p['name']}</li>";
        }
        echo "</ul>";
    }
    
} else {
    echo "<p>❌ Archivo modals.php no encontrado</p>";
}
echo "</div>";

echo "<hr>";
echo "<p><a href='index.php'>← Volver al módulo</a> | <a href='test_modals.php'>Test Modales</a></p>";
?>
