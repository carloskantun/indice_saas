<?php
/**
 * Script de debug para verificar problemas con gastos y proveedores
 */

require_once '../../config.php';

// Verificar autenticación
if (!checkAuth()) {
    die('No autenticado');
}

echo "<h2>Debug del módulo de gastos</h2>";

// Verificar sesión
echo "<h3>1. Datos de sesión:</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "<br>";
echo "Company ID: " . ($_SESSION['company_id'] ?? 'NO DEFINIDO') . "<br>";
echo "Business ID: " . ($_SESSION['business_id'] ?? 'NO DEFINIDO') . "<br>";
echo "Unit ID: " . ($_SESSION['unit_id'] ?? 'NO DEFINIDO') . "<br>";
echo "Rol: " . ($_SESSION['current_role'] ?? 'NO DEFINIDO') . "<br>";

// Verificar conexión a base de datos
echo "<h3>2. Conexión a base de datos:</h3>";
try {
    $db = getDB();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    die();
}

// Verificar tablas
echo "<h3>3. Verificar tablas:</h3>";
try {
    $tables_to_check = ['providers', 'expenses', 'expense_payments'];
    foreach ($tables_to_check as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$table' existe<br>";
            
            // Contar registros
            $count_stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $count_stmt->fetchColumn();
            echo "&nbsp;&nbsp;&nbsp;→ $count registros<br>";
        } else {
            echo "❌ Tabla '$table' NO existe<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error verificando tablas: " . $e->getMessage() . "<br>";
}

// Verificar proveedores
echo "<h3>4. Verificar proveedores:</h3>";
try {
    $company_id = $_SESSION['company_id'] ?? 1;
    $stmt = $db->prepare("SELECT id, name, status FROM providers WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $providers = $stmt->fetchAll();
    
    if (empty($providers)) {
        echo "❌ No hay proveedores para company_id = $company_id<br>";
        
        // Crear proveedor de prueba
        echo "Creando proveedor de prueba...<br>";
        $insert_stmt = $db->prepare("INSERT INTO providers (company_id, name, status, created_by) VALUES (?, 'Proveedor de Prueba', 'active', ?)");
        $result = $insert_stmt->execute([$company_id, $_SESSION['user_id'] ?? 1]);
        if ($result) {
            echo "✅ Proveedor de prueba creado<br>";
        } else {
            echo "❌ Error creando proveedor de prueba<br>";
        }
    } else {
        echo "✅ Encontrados " . count($providers) . " proveedores:<br>";
        foreach ($providers as $provider) {
            echo "&nbsp;&nbsp;&nbsp;→ ID: {$provider['id']}, Nombre: {$provider['name']}, Status: {$provider['status']}<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error verificando proveedores: " . $e->getMessage() . "<br>";
}

// Verificar gastos
echo "<h3>5. Verificar gastos:</h3>";
try {
    $company_id = $_SESSION['company_id'] ?? 1;
    $business_id = $_SESSION['business_id'] ?? 1;
    
    $stmt = $db->prepare("SELECT id, folio, amount, status, origin FROM expenses WHERE company_id = ? AND business_id = ? LIMIT 5");
    $stmt->execute([$company_id, $business_id]);
    $expenses = $stmt->fetchAll();
    
    if (empty($expenses)) {
        echo "❌ No hay gastos para company_id = $company_id, business_id = $business_id<br>";
    } else {
        echo "✅ Encontrados " . count($expenses) . " gastos (mostrando primeros 5):<br>";
        foreach ($expenses as $expense) {
            echo "&nbsp;&nbsp;&nbsp;→ ID: {$expense['id']}, Folio: {$expense['folio']}, Monto: {$expense['amount']}, Status: {$expense['status']}, Origen: {$expense['origin']}<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error verificando gastos: " . $e->getMessage() . "<br>";
}

// Verificar estructura de la tabla expenses
echo "<h3>6. Estructura de tabla expenses:</h3>";
try {
    $stmt = $db->query("DESCRIBE expenses");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Error verificando estructura: " . $e->getMessage() . "<br>";
}

echo "<h3>7. Test de permisos:</h3>";
function hasPermission($permission) {
    if (!checkAuth()) {
        return false;
    }
    
    $role = $_SESSION['current_role'] ?? 'user';
    if (in_array($role, ['root', 'superadmin'])) {
        return true;
    }
    
    $permission_map = [
        'admin' => ['expenses.view', 'expenses.create', 'expenses.edit', 'expenses.pay', 'expenses.export', 'expenses.kpis', 'providers.view', 'providers.create', 'providers.edit'],
        'moderator' => ['expenses.view', 'expenses.create', 'expenses.pay', 'providers.view'],
        'user' => ['expenses.view', 'providers.view']
    ];
    
    return in_array($permission, $permission_map[$role] ?? []);
}

$permissions_to_test = ['expenses.view', 'expenses.create', 'providers.view', 'providers.create'];
foreach ($permissions_to_test as $perm) {
    $has = hasPermission($perm) ? '✅' : '❌';
    echo "$has $perm<br>";
}

?>
