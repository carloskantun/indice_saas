<?php
/**
 * CREAR TRIGGER PARA NUMERACIÓN AUTOMÁTICA DE EMPLEADOS
 * Sistema SaaS Indice
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "⚙️  CREANDO TRIGGER PARA EMPLEADOS\n";
    echo "==================================\n\n";
    
    // Eliminar trigger existente si existe
    echo "🗑️  Eliminando trigger existente... ";
    try {
        $db->exec("DROP TRIGGER IF EXISTS generate_employee_number");
        echo "✅\n";
    } catch (Exception $e) {
        echo "⚠️  No existía\n";
    }
    
    // Crear nuevo trigger con sintaxis corregida
    echo "📝 Creando nuevo trigger... ";
    
    $triggerSQL = "
    CREATE TRIGGER generate_employee_number 
    BEFORE INSERT ON employees 
    FOR EACH ROW 
    BEGIN
        DECLARE next_number INT DEFAULT 1;
        DECLARE new_employee_number VARCHAR(50);
        
        IF NEW.employee_number IS NULL OR NEW.employee_number = '' THEN
            -- Buscar el siguiente número disponible
            SELECT COALESCE(MAX(CAST(SUBSTRING(employee_number, 4) AS UNSIGNED)), 0) + 1 
            INTO next_number
            FROM employees 
            WHERE company_id = NEW.company_id 
            AND employee_number REGEXP '^EMP[0-9]+$';
            
            -- Generar el nuevo número
            SET new_employee_number = CONCAT('EMP', LPAD(next_number, 4, '0'));
            
            -- Verificar que no exista (por seguridad)
            WHILE EXISTS(SELECT 1 FROM employees WHERE employee_number = new_employee_number AND company_id = NEW.company_id) DO
                SET next_number = next_number + 1;
                SET new_employee_number = CONCAT('EMP', LPAD(next_number, 4, '0'));
            END WHILE;
            
            -- Asignar el número generado
            SET NEW.employee_number = new_employee_number;
        END IF;
    END
    ";
    
    try {
        $db->exec($triggerSQL);
        echo "✅\n";
        
        // Verificar que se creó correctamente
        echo "🔍 Verificando trigger creado... ";
        $stmt = $db->query("SHOW TRIGGERS WHERE `Table` = 'employees'");
        $triggers = $stmt->fetchAll();
        
        if (count($triggers) > 0) {
            echo "✅\n";
            echo "\n📋 TRIGGER CREADO:\n";
            foreach ($triggers as $trigger) {
                echo "   Nombre: {$trigger['Trigger']}\n";
                echo "   Tabla: {$trigger['Table']}\n";
                echo "   Evento: {$trigger['Event']}\n";
                echo "   Timing: {$trigger['Timing']}\n";
            }
        } else {
            echo "❌\n";
        }
        
    } catch (Exception $e) {
        echo "❌\n";
        echo "Error: " . $e->getMessage() . "\n";
        
        // Intentar versión simplificada del trigger
        echo "\n🔄 Intentando versión simplificada... ";
        
        $simpleTriggerSQL = "
        CREATE TRIGGER generate_employee_number 
        BEFORE INSERT ON employees 
        FOR EACH ROW 
        BEGIN
            IF NEW.employee_number IS NULL OR NEW.employee_number = '' THEN
                SET NEW.employee_number = CONCAT('EMP', LPAD((
                    SELECT COALESCE(MAX(CAST(SUBSTRING(employee_number, 4) AS UNSIGNED)), 0) + 1 
                    FROM employees 
                    WHERE company_id = NEW.company_id 
                    AND employee_number REGEXP '^EMP[0-9]+$'
                ), 4, '0'));
            END IF;
        END
        ";
        
        try {
            $db->exec($simpleTriggerSQL);
            echo "✅\n";
        } catch (Exception $e2) {
            echo "❌\n";
            echo "Error en versión simple: " . $e2->getMessage() . "\n";
        }
    }
    
    echo "\n🧪 PROBANDO EL TRIGGER:\n";
    echo "=======================\n";
    
    // Insertar empleado de prueba para verificar el trigger
    echo "🔬 Insertando empleado de prueba... ";
    try {
        $stmt = $db->prepare("
            INSERT INTO employees (
                company_id, business_id, first_name, last_name, 
                department_id, position_id, hire_date, created_by
            ) VALUES (1, 1, 'Prueba', 'Trigger', 1, 1, CURDATE(), 1)
        ");
        $stmt->execute();
        
        // Verificar el número generado
        $stmt = $db->query("SELECT employee_number FROM employees WHERE first_name = 'Prueba' AND last_name = 'Trigger'");
        $result = $stmt->fetch();
        
        if ($result && $result['employee_number']) {
            echo "✅\n";
            echo "   Número generado: {$result['employee_number']}\n";
            
            // Eliminar el empleado de prueba
            $db->exec("DELETE FROM employees WHERE first_name = 'Prueba' AND last_name = 'Trigger'");
            echo "   🗑️  Empleado de prueba eliminado\n";
        } else {
            echo "❌ No se generó número automáticamente\n";
        }
        
    } catch (Exception $e) {
        echo "❌\n";
        echo "Error en prueba: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 ¡PROCESO COMPLETADO!\n";
    echo "======================\n";
    echo "✅ El trigger para numeración automática está configurado\n";
    echo "📋 Los nuevos empleados tendrán números como: EMP0001, EMP0002, etc.\n";
    echo "🔗 Puedes probar creando un empleado en: /modules/human-resources/\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL:\n";
    echo "=================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
