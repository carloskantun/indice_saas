<?php
require_once 'config.php';

echo "🔧 ARREGLANDO TABLA POSITIONS (SIMPLE)\n";
echo "======================================\n\n";

$db = getDB();

try {
    // Step 1: Verificar estructura actual
    echo "1️⃣ Verificando estructura de positions...\n";
    $stmt = $db->query("DESCRIBE positions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasNameColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'name') {
            $hasNameColumn = true;
            break;
        }
    }
    
    // Step 2: Agregar columna name si no existe
    if (!$hasNameColumn) {
        echo "2️⃣ Agregando columna 'name'...\n";
        $db->exec("ALTER TABLE positions ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT 'Sin nombre' AFTER id");
        echo "✅ Columna 'name' agregada\n";
    } else {
        echo "2️⃣ Columna 'name' ya existe\n";
    }
    
    // Step 3: Verificar datos
    echo "3️⃣ Verificando posiciones existentes...\n";
    $stmt = $db->query("SELECT COUNT(*) FROM positions");
    $count = $stmt->fetchColumn();
    echo "Total posiciones: $count\n";
    
    // Step 4: Agregar posiciones básicas si están vacías
    if ($count == 0) {
        echo "4️⃣ Creando posiciones básicas...\n";
        
        $basicPositions = [
            "INSERT INTO positions (name, department_id, company_id, business_id, unit_id, created_by) VALUES ('Gerente General', 4, 1, 1, 2, 1)",
            "INSERT INTO positions (name, department_id, company_id, business_id, unit_id, created_by) VALUES ('Desarrollador', 2, 1, 1, 2, 1)",
            "INSERT INTO positions (name, department_id, company_id, business_id, unit_id, created_by) VALUES ('Vendedor', 3, 1, 1, 2, 1)",
            "INSERT INTO positions (name, department_id, company_id, business_id, unit_id, created_by) VALUES ('Analista HR', 1, 1, 1, 2, 1)",
            "INSERT INTO positions (name, department_id, company_id, business_id, unit_id, created_by) VALUES ('Especialista Marketing', 5, 1, 1, 2, 1)"
        ];
        
        foreach ($basicPositions as $sql) {
            try {
                $db->exec($sql);
                echo "✅ Posición creada\n";
            } catch (Exception $e) {
                echo "⚠️ Error creando posición: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "4️⃣ Ya existen posiciones, saltando creación\n";
    }
    
    // Step 5: Test final
    echo "5️⃣ Test final - listando posiciones:\n";
    $stmt = $db->query("SELECT id, name FROM positions ORDER BY id");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($positions as $pos) {
        echo "- ID: " . $pos['id'] . " | " . $pos['name'] . "\n";
    }
    
    echo "\n✅ PROCESO COMPLETADO - TABLA POSITIONS ARREGLADA\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
