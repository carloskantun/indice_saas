<?php
require_once '../../config.php';

echo "🔍 DEBUG HR CONTROLLER - ERRORES\n";
echo "================================\n\n";

// Verificar autenticación
if (!checkAuth()) {
    echo "❌ No autorizado\n";
    exit;
}

$business_id = $_SESSION['business_id'] ?? null;
$company_id = $_SESSION['company_id'] ?? null;
$unit_id = $_SESSION['unit_id'] ?? null;

echo "📊 Contexto actual:\n";
echo "- Business ID: $business_id\n";
echo "- Company ID: $company_id\n";
echo "- Unit ID: $unit_id\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n\n";

$db = getDB();

// Verificar estructura de tabla employees
echo "🗄️ ESTRUCTURA TABLA EMPLOYEES:\n";
echo "------------------------------\n";
try {
    $stmt = $db->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ") " . 
             ($column['Null'] === 'NO' ? '[REQUERIDO]' : '[OPCIONAL]') . 
             ($column['Default'] ? " DEFAULT: " . $column['Default'] : '') . "\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error describiendo tabla: " . $e->getMessage() . "\n\n";
}

// Verificar empleados existentes
echo "👥 EMPLEADOS EXISTENTES:\n";
echo "------------------------\n";
try {
    $stmt = $db->prepare("
        SELECT id, first_name, last_name, email, company_id, business_id, unit_id, created_at
        FROM employees 
        WHERE company_id = ? AND business_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$company_id, $business_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo "Sin empleados registrados\n\n";
    } else {
        foreach ($employees as $emp) {
            echo "- ID: " . $emp['id'] . " | " . $emp['first_name'] . " " . $emp['last_name'] . 
                 " | " . $emp['email'] . " | Creado: " . $emp['created_at'] . "\n";
        }
        echo "\nTotal: " . count($employees) . " empleados\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error obteniendo empleados: " . $e->getMessage() . "\n\n";
}

// Verificar departamentos y posiciones
echo "🏢 DEPARTAMENTOS DISPONIBLES:\n";
echo "-----------------------------\n";
try {
    $stmt = $db->prepare("
        SELECT id, name FROM departments 
        WHERE company_id = ? AND business_id = ?
    ");
    $stmt->execute([$company_id, $business_id]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($departments)) {
        echo "❌ Sin departamentos - ESTO PUEDE CAUSAR ERRORES\n\n";
    } else {
        foreach ($departments as $dept) {
            echo "- ID: " . $dept['id'] . " | " . $dept['name'] . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo departamentos: " . $e->getMessage() . "\n\n";
}

echo "💼 POSICIONES DISPONIBLES:\n";
echo "--------------------------\n";
try {
    $stmt = $db->prepare("
        SELECT id, name FROM positions 
        WHERE company_id = ? AND business_id = ?
    ");
    $stmt->execute([$company_id, $business_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($positions)) {
        echo "❌ Sin posiciones - ESTO PUEDE CAUSAR ERRORES\n\n";
    } else {
        foreach ($positions as $pos) {
            echo "- ID: " . $pos['id'] . " | " . $pos['name'] . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo posiciones: " . $e->getMessage() . "\n\n";
}

// Verificar tabla invitations
echo "📧 ESTRUCTURA TABLA INVITATIONS:\n";
echo "--------------------------------\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'invitations'");
    if ($stmt->fetch()) {
        $stmt = $db->query("DESCRIBE invitations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "❌ Tabla invitations NO EXISTE\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error verificando invitations: " . $e->getMessage() . "\n\n";
}

// Test básico de inserción
echo "🧪 TEST DE INSERCIÓN BÁSICA:\n";
echo "-----------------------------\n";
try {
    // Intentar inserción con datos mínimos
    $test_data = [
        'first_name' => 'Test',
        'last_name' => 'Usuario',
        'email' => 'test_' . time() . '@example.com',
        'phone' => '1234567890',
        'department_id' => 1,
        'position_id' => 1,
        'salary' => 50000,
        'hire_date' => date('Y-m-d'),
        'status' => 'active',
        'company_id' => $company_id,
        'business_id' => $business_id,
        'unit_id' => $unit_id,
        'created_by' => $_SESSION['user_id']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO employees (first_name, last_name, email, phone, department_id, position_id, 
                             salary, hire_date, status, company_id, business_id, unit_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $test_data['first_name'], $test_data['last_name'], $test_data['email'], $test_data['phone'],
        $test_data['department_id'], $test_data['position_id'], $test_data['salary'], 
        $test_data['hire_date'], $test_data['status'], $test_data['company_id'], 
        $test_data['business_id'], $test_data['unit_id'], $test_data['created_by']
    ]);
    
    if ($result) {
        $test_id = $db->lastInsertId();
        echo "✅ Inserción exitosa - ID: $test_id\n";
        
        // Limpiar el test
        $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$test_id]);
        echo "🗑️ Registro de test eliminado\n\n";
    } else {
        echo "❌ Falló la inserción\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error en test de inserción: " . $e->getMessage() . "\n";
    echo "SQL Error Info: " . print_r($stmt->errorInfo(), true) . "\n\n";
}

echo "📝 RECOMENDACIONES:\n";
echo "-------------------\n";
echo "1. Revisa los logs de PHP en el servidor\n";
echo "2. Verifica que existan departamentos y posiciones\n";
echo "3. Confirma que la tabla invitations existe\n";
echo "4. Verifica permisos de escritura en BD\n";
?>
