<?php
require_once 'config.php';

echo "🔧 ARREGLANDO TABLA POSITIONS\n";
echo "=============================\n\n";

$db = getDB();

// Verificar estructura actual de positions
echo "📊 Estructura actual de positions:\n";
try {
    $stmt = $db->query("DESCRIBE positions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasNameColumn = false;
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        if ($column['Field'] === 'name') {
            $hasNameColumn = true;
        }
    }
    
    if (!$hasNameColumn) {
        echo "\n❌ Columna 'name' no existe. Agregando...\n";
        
        // Agregar columna name si no existe
        $db->exec("ALTER TABLE positions ADD COLUMN name VARCHAR(100) NOT NULL AFTER id");
        echo "✅ Columna 'name' agregada\n";
        
        // Si hay datos sin nombre, llenar con un valor por defecto
        $stmt = $db->query("SELECT COUNT(*) FROM positions WHERE name IS NULL OR name = ''");
        $emptyNames = $stmt->fetchColumn();
        
        if ($emptyNames > 0) {
            $db->exec("UPDATE positions SET name = CONCAT('Posición ', id) WHERE name IS NULL OR name = ''");
            echo "✅ Nombres por defecto asignados a $emptyNames posiciones\n";
        }
    } else {
        echo "\n✅ Columna 'name' ya existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Verificar/crear posiciones básicas
echo "\n🏗️ Verificando posiciones básicas:\n";
try {
    $basicPositions = [
        ['name' => 'Gerente General', 'department_id' => 4],
        ['name' => 'Desarrollador', 'department_id' => 2],
        ['name' => 'Vendedor', 'department_id' => 3],
        ['name' => 'Analista HR', 'department_id' => 1],
        ['name' => 'Especialista Marketing', 'department_id' => 5]
    ];
    
    $business_id = 1;
    $company_id = 1;
    $unit_id = 2;
    $created_by = 1;
    
    foreach ($basicPositions as $position) {
        // Verificar si ya existe
        $stmt = $db->prepare("
            SELECT id FROM positions 
            WHERE name = ? AND company_id = ? AND business_id = ?
        ");
        $stmt->execute([$position['name'], $company_id, $business_id]);
        
        if (!$stmt->fetch()) {
            // No existe, crear
            $stmt = $db->prepare("
                INSERT INTO positions (name, department_id, company_id, business_id, unit_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $position['name'], 
                $position['department_id'], 
                $company_id, 
                $business_id, 
                $unit_id, 
                $created_by
            ]);
            echo "✅ Posición creada: " . $position['name'] . "\n";
        } else {
            echo "⏭️ Ya existe: " . $position['name'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error creando posiciones: " . $e->getMessage() . "\n";
}

// Test final
echo "\n🧪 TEST FINAL:\n";
try {
    $stmt = $db->prepare("
        SELECT p.id, p.name as position_name, d.name as department_name
        FROM positions p
        LEFT JOIN departments d ON p.department_id = d.id
        WHERE p.company_id = ? AND p.business_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([1, 1]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($positions)) {
        echo "❌ Sin posiciones disponibles\n";
    } else {
        echo "✅ Posiciones disponibles:\n";
        foreach ($positions as $pos) {
            echo "- ID: " . $pos['id'] . " | " . $pos['position_name'] . 
                 " (Depto: " . $pos['department_name'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error en test final: " . $e->getMessage() . "\n";
}

echo "\n✅ PROCESO COMPLETADO\n";
?>
