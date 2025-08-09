<?php
require_once 'config.php';

echo "🔧 ARREGLANDO TABLA EMPLOYEES\n";
echo "=============================\n\n";

$db = getDB();

try {
    // Verificar estructura actual
    echo "1️⃣ Verificando estructura actual de employees...\n";
    $stmt = $db->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_columns = [];
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
    }
    
    // Columnas que necesitamos agregar
    $needed_columns = [
        'salary_frequency' => "ALTER TABLE employees ADD COLUMN salary_frequency ENUM('Semanal', 'Quincenal', 'Mensual') DEFAULT 'Mensual' AFTER salary",
        'employee_id' => "ALTER TABLE employees ADD COLUMN employee_id INT(11) NULL AFTER id",
        'fiscal_id' => "ALTER TABLE employees ADD COLUMN fiscal_id VARCHAR(50) NULL AFTER phone"
    ];
    
    // Verificar y agregar columnas faltantes
    foreach ($needed_columns as $column_name => $sql) {
        if (!in_array($column_name, $existing_columns)) {
            echo "2️⃣ Agregando columna '$column_name'...\n";
            try {
                $db->exec($sql);
                echo "✅ Columna '$column_name' agregada exitosamente\n";
            } catch (Exception $e) {
                echo "❌ Error agregando '$column_name': " . $e->getMessage() . "\n";
            }
        } else {
            echo "⏭️ Columna '$column_name' ya existe\n";
        }
    }
    
    // Verificar estructura final
    echo "\n3️⃣ Estructura final de employees:\n";
    $stmt = $db->query("DESCRIBE employees");
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($final_columns as $column) {
        $required = ($column['Null'] === 'NO') ? '[REQUERIDO]' : '[OPCIONAL]';
        $default = $column['Default'] ? " DEFAULT: " . $column['Default'] : '';
        echo "- " . $column['Field'] . " (" . $column['Type'] . ") $required$default\n";
    }
    
    echo "\n✅ TABLA EMPLOYEES ACTUALIZADA CORRECTAMENTE\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
