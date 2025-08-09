<?php
/**
 * TEST DE VERIFICACIÓN - TABLA USERS Y PERFIL
 * Verifica que la tabla users tenga todas las columnas necesarias
 */

require_once 'config.php';

echo "<h2>🔍 TEST DE VERIFICACIÓN - SISTEMA DE PERFILES</h2>";
echo "<hr>";

try {
    $db = getDB();
    
    // 1. Verificar estructura de tabla users
    echo "<h3>1. Estructura de tabla 'users':</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $expected_columns = [
        'id', 'name', 'first_name', 'last_name', 'email', 'password', 'status',
        'phone', 'birth_date', 'gender', 'fiscal_id', 'address', 'city', 'state',
        'country', 'postal_code', 'bio', 'timezone', 'language', 'notifications_email',
        'notifications_sms', 'avatar', 'last_login', 'login_attempts', 'created_at', 'updated_at'
    ];
    
    $existing_columns = [];
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
        $existing_columns[] = $column['Field'];
    }
    echo "</table><br>";
    
    // 2. Verificar columnas faltantes
    echo "<h3>2. Análisis de columnas:</h3>";
    $missing_columns = array_diff($expected_columns, $existing_columns);
    $extra_columns = array_diff($existing_columns, $expected_columns);
    
    if (!empty($missing_columns)) {
        echo "<div style='color: red;'><strong>❌ Columnas FALTANTES:</strong><br>";
        foreach ($missing_columns as $col) {
            echo "- {$col}<br>";
        }
        echo "</div><br>";
    } else {
        echo "<div style='color: green;'>✅ Todas las columnas necesarias están presentes</div><br>";
    }
    
    if (!empty($extra_columns)) {
        echo "<div style='color: blue;'><strong>ℹ️ Columnas ADICIONALES:</strong><br>";
        foreach ($extra_columns as $col) {
            echo "- {$col}<br>";
        }
        echo "</div><br>";
    }
    
    // 3. Verificar datos de usuario existente
    echo "<h3>3. Test con usuario existente:</h3>";
    
    // Buscar usuario de prueba
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['carlosadmin@indiceapp.com']);
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_user) {
        echo "<div style='color: green;'>✅ Usuario de prueba encontrado: {$test_user['name']} ({$test_user['email']})</div>";
        
        echo "<h4>Datos actuales del usuario:</h4>";
        echo "<ul>";
        foreach ($test_user as $key => $value) {
            if ($key !== 'password') { // No mostrar password por seguridad
                $display_value = $value ?: '<em>NULL</em>';
                echo "<li><strong>{$key}:</strong> {$display_value}</li>";
            }
        }
        echo "</ul>";
        
    } else {
        echo "<div style='color: orange;'>⚠️ No se encontró usuario de prueba con email 'carlosadmin@indiceapp.com'</div>";
        
        // Mostrar primeros usuarios disponibles
        $stmt = $db->query("SELECT id, name, email, created_at FROM users LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($users) {
            echo "<h4>Usuarios disponibles:</h4>";
            foreach ($users as $user) {
                echo "- ID: {$user['id']}, Nombre: {$user['name']}, Email: {$user['email']}<br>";
            }
        }
    }
    
    // 4. Verificar funciones de configuración
    echo "<h3>4. Verificación de funciones:</h3>";
    if (function_exists('checkAuth')) {
        echo "✅ Función checkAuth() disponible<br>";
    } else {
        echo "❌ Función checkAuth() NO disponible<br>";
    }
    
    if (function_exists('getDB')) {
        echo "✅ Función getDB() disponible<br>";
    } else {
        echo "❌ Función getDB() NO disponible<br>";
    }
    
    if (function_exists('redirect')) {
        echo "✅ Función redirect() disponible<br>";
    } else {
        echo "❌ Función redirect() NO disponible<br>";
    }
    
    // 5. Estado de sesión
    echo "<h3>5. Estado de sesión:</h3>";
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ Sesión activa<br>";
        if (isset($_SESSION['user_id'])) {
            echo "✅ Usuario logueado: ID = {$_SESSION['user_id']}<br>";
        } else {
            echo "⚠️ No hay usuario logueado en sesión<br>";
        }
    } else {
        echo "❌ Sesión no iniciada<br>";
    }
    
    echo "<hr>";
    echo "<h3>📋 CONCLUSIÓN:</h3>";
    
    if (empty($missing_columns)) {
        echo "<div style='color: green; font-weight: bold;'>✅ El sistema de perfiles está listo para usar</div>";
        echo "<p>Todas las columnas necesarias están presentes en la tabla users.</p>";
        echo "<p><strong>Próximos pasos:</strong></p>";
        echo "<ul>";
        echo "<li>Acceder a <code>profile.php</code> desde el navegador</li>";
        echo "<li>Probar la actualización de datos del perfil</li>";
        echo "<li>Verificar que se guarden correctamente los cambios</li>";
        echo "</ul>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Es necesario ejecutar el script de actualización</div>";
        echo "<p>Faltan columnas en la tabla users. Ejecute <code>setup_user_profile.php</code> primero.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</div>";
}
?>
