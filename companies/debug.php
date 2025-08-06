<?php
/**
 * Versión simplificada de companies para depuración
 */

// Incluir configuración principal
require_once '../config.php';

// Mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DEBUG - Companies</h1>";

try {
    echo "<p>1. Incluyendo config.php...</p>";
    echo "<p>✅ Config incluido</p>";
    
    echo "<p>2. Verificando autenticación...</p>";
    if (!function_exists('checkAuth')) {
        echo "<p>❌ Función checkAuth no existe</p>";
        exit;
    }
    
    if (!checkAuth()) {
        echo "<p>❌ Usuario no autenticado, redirigiendo...</p>";
        redirect('auth/');
        exit;
    }
    echo "<p>✅ Usuario autenticado</p>";
    
    echo "<p>3. Verificando sesión...</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p>4. Conectando a base de datos...</p>";
    $db = getDB();
    echo "<p>✅ Conexión a DB exitosa</p>";
    
    $user_id = $_SESSION['user_id'];
    echo "<p>User ID: $user_id</p>";
    
    echo "<p>5. Consultando empresas...</p>";
    $stmt = $db->prepare("
        SELECT c.*, uc.role, uc.created_at as joined_at
        FROM companies c 
        INNER JOIN user_companies uc ON c.id = uc.company_id 
        WHERE uc.user_id = ? 
        ORDER BY uc.last_accessed DESC, c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $companies = $stmt->fetchAll();
    
    echo "<p>✅ Consulta exitosa, empresas encontradas: " . count($companies) . "</p>";
    
    if (count($companies) > 0) {
        echo "<h3>Empresas del usuario:</h3>";
        echo "<ul>";
        foreach ($companies as $company) {
            echo "<li>" . htmlspecialchars($company['name']) . " (Rol: " . $company['role'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No se encontraron empresas para este usuario</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>📍 Archivo: " . $e->getFile() . "</p>";
    echo "<p>📍 Línea: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='index.php'>🔙 Volver a companies normal</a></p>";
?>
