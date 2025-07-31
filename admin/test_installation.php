<?php
/**
 * Test de Instalación - Verificar Tablas con Nombres en Inglés
 * Verifica que todas las tablas usen nombres en inglés para consistencia
 */

require_once '../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Test de Instalación - Tablas en Inglés</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style></head><body>";
    
    echo "<h1>🔍 Test de Instalación - Verificación de Tablas en Inglés</h1>";
    
    // Lista de tablas esperadas con nombres en inglés
    $expectedTables = [
        'invitations' => 'Tabla de invitaciones de usuarios',
        'permissions' => 'Tabla de permisos del sistema',
        'role_permissions' => 'Tabla de relación roles-permisos',
        'user_businesses' => 'Tabla de relación usuarios-negocios',
        'user_companies' => 'Tabla de relación usuarios-empresas',
        'user_units' => 'Tabla de relación usuarios-unidades'
    ];
    
    echo "<h2>📊 Verificando Existencia de Tablas</h2>";
    echo "<table>";
    echo "<tr><th>Tabla</th><th>Descripción</th><th>Estado</th><th>Registros</th></tr>";
    
    $allTablesExist = true;
    
    foreach ($expectedTables as $tableName => $description) {
        echo "<tr>";
        echo "<td><strong>$tableName</strong></td>";
        echo "<td>$description</td>";
        
        try {
            // Verificar si la tabla existe
            $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
            if ($stmt->rowCount() > 0) {
                echo "<td class='success'>✅ Existe</td>";
                
                // Contar registros
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $tableName");
                $count = $countStmt->fetch()['count'];
                echo "<td class='info'>$count registros</td>";
            } else {
                echo "<td class='error'>❌ No existe</td>";
                echo "<td class='error'>N/A</td>";
                $allTablesExist = false;
            }
        } catch (PDOException $e) {
            echo "<td class='error'>❌ Error: " . $e->getMessage() . "</td>";
            echo "<td class='error'>N/A</td>";
            $allTablesExist = false;
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar estructura específica de tabla invitations
    echo "<h2>📧 Verificando Estructura de Tabla 'invitations'</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE invitations");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por Defecto</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<div class='success'>✅ Estructura de tabla 'invitations' verificada</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error verificando estructura de 'invitations': " . $e->getMessage() . "</div>";
    }
    
    // Verificar trigger de expiración
    echo "<h2>⚡ Verificando Trigger de Expiración</h2>";
    try {
        $stmt = $pdo->query("SHOW TRIGGERS LIKE 'invitations'");
        $triggers = $stmt->fetchAll();
        
        if (count($triggers) > 0) {
            echo "<table>";
            echo "<tr><th>Trigger</th><th>Evento</th><th>Tabla</th><th>Timing</th></tr>";
            foreach ($triggers as $trigger) {
                echo "<tr>";
                echo "<td>" . $trigger['Trigger'] . "</td>";
                echo "<td>" . $trigger['Event'] . "</td>";
                echo "<td>" . $trigger['Table'] . "</td>";
                echo "<td>" . $trigger['Timing'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<div class='success'>✅ Trigger de expiración configurado</div>";
        } else {
            echo "<div class='warning'>⚠️ No se encontraron triggers para la tabla 'invitations'</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error verificando triggers: " . $e->getMessage() . "</div>";
    }
    
    // Verificar tablas obsoletas (nombres en español)
    echo "<h2>🗑️ Verificando Tablas Obsoletas (Nombres en Español)</h2>";
    $obsoleteTables = ['invitaciones']; // Tabla antigua que debería haberse migrado
    
    echo "<table>";
    echo "<tr><th>Tabla Obsoleta</th><th>Estado</th><th>Acción Recomendada</th></tr>";
    
    foreach ($obsoleteTables as $obsoleteTable) {
        echo "<tr>";
        echo "<td><strong>$obsoleteTable</strong></td>";
        
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$obsoleteTable'");
            if ($stmt->rowCount() > 0) {
                echo "<td class='warning'>⚠️ Aún existe</td>";
                echo "<td class='warning'>Considerar migrar datos y eliminar</td>";
            } else {
                echo "<td class='success'>✅ No existe</td>";
                echo "<td class='success'>Correctamente migrada</td>";
            }
        } catch (PDOException $e) {
            echo "<td class='error'>❌ Error verificando</td>";
            echo "<td class='error'>Revisar manualmente</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Resumen final
    echo "<h2>📋 Resumen del Test</h2>";
    if ($allTablesExist) {
        echo "<div class='success'>✅ Todas las tablas con nombres en inglés están presentes</div>";
        echo "<div class='info'>ℹ️ Sistema preparado para funcionamiento multiidioma</div>";
        echo "<div class='info'>ℹ️ Nomenclatura de base de datos estandarizada</div>";
    } else {
        echo "<div class='error'>❌ Faltan algunas tablas con nombres en inglés</div>";
        echo "<div class='warning'>⚠️ Ejecutar scripts de instalación de tablas</div>";
    }
    
    echo "<br><br>";
    echo "<a href='install_admin_tables.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Instalar/Actualizar Tablas</a>";
    echo "<a href='index.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ir al Sistema Admin</a>";
    echo "<a href='verify_system.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Verificación Completa</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
