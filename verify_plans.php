<?php
/**
 * Script para verificar estructura específica de la tabla plans
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Verificación Tabla Plans</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background-color:#f2f2f2;}</style></head><body>";

echo "<h1>🔍 Verificación Detallada de Tabla 'plans'</h1>";

try {
    $pdo = getDB();
    echo "<div class='info'>✅ Conexión a base de datos establecida</div><br>";
    
    // Mostrar estructura actual de la tabla plans
    echo "<h2>📋 Estructura Actual de la Tabla 'plans'</h2>";
    
    $stmt = $pdo->query("DESCRIBE plans");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    $columnNames = [];
    foreach ($columns as $column) {
        $columnNames[] = $column['Field'];
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar columnas necesarias
    echo "<h2>🔧 Verificación de Columnas Necesarias</h2>";
    
    $requiredColumns = [
        'monthly_price' => 'DECIMAL(10,2) DEFAULT 0.00',
        'annual_price' => 'DECIMAL(10,2) DEFAULT 0.00', 
        'status' => "ENUM('active', 'inactive') DEFAULT 'active'"
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $columnName => $columnDefinition) {
        if (in_array($columnName, $columnNames)) {
            echo "<div class='success'>✅ Columna '$columnName' existe</div>";
        } else {
            echo "<div class='warning'>⚠️ Columna '$columnName' no existe</div>";
            $missingColumns[$columnName] = $columnDefinition;
        }
    }
    
    // Agregar columnas faltantes
    if (!empty($missingColumns)) {
        echo "<h3>🛠️ Agregando Columnas Faltantes</h3>";
        
        foreach ($missingColumns as $columnName => $columnDefinition) {
            try {
                $sql = "ALTER TABLE plans ADD COLUMN $columnName $columnDefinition";
                $pdo->exec($sql);
                echo "<div class='success'>✅ Columna '$columnName' agregada exitosamente</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error agregando '$columnName': " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    // Mostrar datos actuales de los planes
    echo "<h2>📊 Datos Actuales de Planes</h2>";
    
    $stmt = $pdo->query("SELECT * FROM plans LIMIT 10");
    $plans = $stmt->fetchAll();
    
    if (!empty($plans)) {
        echo "<table>";
        echo "<tr>";
        foreach (array_keys($plans[0]) as $header) {
            if (!is_numeric($header)) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
        }
        echo "</tr>";
        
        foreach ($plans as $plan) {
            echo "<tr>";
            foreach ($plan as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No hay planes en la base de datos</div>";
    }
    
    // Actualizar precios si están en 0
    echo "<h2>💰 Verificación de Precios</h2>";
    
    if (in_array('monthly_price', $columnNames)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plans WHERE monthly_price = 0 OR monthly_price IS NULL");
        $plansWithoutPrice = $stmt->fetch()['count'];
        
        if ($plansWithoutPrice > 0) {
            echo "<div class='warning'>⚠️ Encontrados $plansWithoutPrice planes sin precio configurado</div>";
            
            // Actualizar precios automáticamente
            $updatePricesSQL = "
                UPDATE plans SET 
                    monthly_price = CASE 
                        WHEN LOWER(name) LIKE '%básico%' OR LOWER(name) LIKE '%basic%' THEN 29.99
                        WHEN LOWER(name) LIKE '%estándar%' OR LOWER(name) LIKE '%standard%' THEN 59.99
                        WHEN LOWER(name) LIKE '%premium%' OR LOWER(name) LIKE '%pro%' THEN 99.99
                        WHEN LOWER(name) LIKE '%enterprise%' OR LOWER(name) LIKE '%empresarial%' THEN 199.99
                        ELSE 49.99
                    END,
                    annual_price = CASE 
                        WHEN LOWER(name) LIKE '%básico%' OR LOWER(name) LIKE '%basic%' THEN 299.99
                        WHEN LOWER(name) LIKE '%estándar%' OR LOWER(name) LIKE '%standard%' THEN 599.99
                        WHEN LOWER(name) LIKE '%premium%' OR LOWER(name) LIKE '%pro%' THEN 999.99
                        WHEN LOWER(name) LIKE '%enterprise%' OR LOWER(name) LIKE '%empresarial%' THEN 1999.99
                        ELSE 499.99
                    END
                WHERE monthly_price = 0 OR monthly_price IS NULL";
            
            $pdo->exec($updatePricesSQL);
            echo "<div class='success'>✅ Precios actualizados automáticamente</div>";
            
            // Mostrar precios actualizados
            $stmt = $pdo->query("SELECT name, monthly_price, annual_price FROM plans");
            $updatedPlans = $stmt->fetchAll();
            
            echo "<h3>📋 Precios Actualizados:</h3>";
            echo "<table>";
            echo "<tr><th>Plan</th><th>Precio Mensual</th><th>Precio Anual</th></tr>";
            foreach ($updatedPlans as $plan) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($plan['name']) . "</td>";
                echo "<td>$" . number_format($plan['monthly_price'], 2) . "</td>";
                echo "<td>$" . number_format($plan['annual_price'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<div class='success'>✅ Todos los planes tienen precios configurados</div>";
        }
    }
    
    // Migrar is_active a status si es necesario
    if (in_array('is_active', $columnNames) && in_array('status', $columnNames)) {
        echo "<h2>🔄 Migración de is_active a status</h2>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plans WHERE status IS NULL OR status = ''");
        $needsMigration = $stmt->fetch()['count'];
        
        if ($needsMigration > 0) {
            $pdo->exec("UPDATE plans SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END WHERE status IS NULL OR status = ''");
            echo "<div class='success'>✅ Migración de is_active a status completada</div>";
        } else {
            echo "<div class='success'>✅ No se requiere migración</div>";
        }
    }
    
    echo "<br><h2>🎉 Verificación Completada</h2>";
    echo "<div class='success'>✅ Tabla 'plans' verificada y corregida</div>";
    
    echo "<br><a href='panel_root/index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ir al Dashboard</a>";
    echo "<a href='fix_database.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Corrección Completa</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
