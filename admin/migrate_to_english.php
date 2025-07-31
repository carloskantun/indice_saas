<?php
/**
 * Script de Migración - Actualizar Columnas a Inglés
 * Migra las columnas de la tabla de invitaciones de español a inglés
 */

require_once '../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Migración de Columnas a Inglés</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
    </style></head><body>";
    
    echo "<h1>🔄 Migración de Columnas a Inglés</h1>";
    
    // Verificar si existe la tabla antigua con nombres en español
    $checkOldTable = $pdo->query("SHOW TABLES LIKE 'invitaciones'");
    $oldTableExists = $checkOldTable->rowCount() > 0;
    
    // Verificar si existe la tabla nueva con nombres en inglés
    $checkNewTable = $pdo->query("SHOW TABLES LIKE 'invitations'");
    $newTableExists = $checkNewTable->rowCount() > 0;
    
    echo "<h2>📊 Estado Actual de las Tablas</h2>";
    echo "<ul>";
    echo "<li>Tabla 'invitaciones' (español): " . ($oldTableExists ? "<span class='warning'>Existe</span>" : "<span class='success'>No existe</span>") . "</li>";
    echo "<li>Tabla 'invitations' (inglés): " . ($newTableExists ? "<span class='success'>Existe</span>" : "<span class='error'>No existe</span>") . "</li>";
    echo "</ul>";
    
    if ($oldTableExists && !$newTableExists) {
        echo "<h2>🚀 Migrando Tabla de Español a Inglés</h2>";
        
        // Crear nueva tabla con nombres en inglés
        $createNewTableSQL = "
        CREATE TABLE invitations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            company_id INT NOT NULL,
            unit_id INT DEFAULT NULL,
            business_id INT DEFAULT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'user',
            token VARCHAR(64) NOT NULL UNIQUE,
            status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
            sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expiration_date TIMESTAMP NULL,
            sent_by INT NOT NULL,
            INDEX idx_token (token),
            INDEX idx_email (email),
            INDEX idx_company (company_id),
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createNewTableSQL);
        echo "<div class='success'>✅ Tabla 'invitations' creada con columnas en inglés</div>";
        
        // Migrar datos
        $migrateDataSQL = "
        INSERT INTO invitations (
            id, email, company_id, unit_id, business_id, role, token, 
            status, sent_date, expiration_date, sent_by
        )
        SELECT 
            id, email, empresa_id, unidad_id, negocio_id, rol, token,
            CASE 
                WHEN status = 'pendiente' THEN 'pending'
                WHEN status = 'aceptada' THEN 'accepted'
                WHEN status = 'expirada' THEN 'expired'
                ELSE status
            END as status,
            fecha_envio, fecha_expiracion, enviado_por
        FROM invitaciones";
        
        $pdo->exec($migrateDataSQL);
        echo "<div class='success'>✅ Datos migrados de 'invitaciones' a 'invitations'</div>";
        
        // Contar registros migrados
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM invitations");
        $count = $countStmt->fetch()['count'];
        echo "<div class='info'>ℹ️ Total de registros migrados: $count</div>";
        
        // Crear trigger para la nueva tabla
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS set_invitation_expiration");
            
            $triggerSQL = "
            CREATE TRIGGER set_invitation_expiration 
            BEFORE INSERT ON invitations
            FOR EACH ROW
            BEGIN
                IF NEW.expiration_date IS NULL THEN
                    SET NEW.expiration_date = DATE_ADD(NOW(), INTERVAL 48 HOUR);
                END IF;
            END";
            
            $pdo->exec($triggerSQL);
            echo "<div class='success'>✅ Trigger recreado para tabla 'invitations'</div>";
        } catch (PDOException $e) {
            echo "<div class='warning'>⚠️ No se pudo crear el trigger: " . $e->getMessage() . "</div>";
        }
        
        echo "<h2>⚠️ Pasos Manuales Requeridos</h2>";
        echo "<div class='warning'>";
        echo "<p>Después de verificar que todo funciona correctamente:</p>";
        echo "<ol>";
        echo "<li>Haz un backup de la tabla antigua: <code>mysqldump [database] invitaciones > backup_invitaciones.sql</code></li>";
        echo "<li>Verifica que todos los datos estén correctos en la nueva tabla</li>";
        echo "<li>Elimina la tabla antigua: <code>DROP TABLE invitaciones;</code></li>";
        echo "</ol>";
        echo "</div>";
        
    } elseif ($oldTableExists && $newTableExists) {
        echo "<h2>⚠️ Ambas Tablas Existen</h2>";
        echo "<div class='warning'>Se encontraron ambas tablas. Verifica si necesitas migrar datos adicionales.</div>";
        
        // Comparar conteos
        $oldCountStmt = $pdo->query("SELECT COUNT(*) as count FROM invitaciones");
        $oldCount = $oldCountStmt->fetch()['count'];
        
        $newCountStmt = $pdo->query("SELECT COUNT(*) as count FROM invitations");
        $newCount = $newCountStmt->fetch()['count'];
        
        echo "<ul>";
        echo "<li>Registros en 'invitaciones': $oldCount</li>";
        echo "<li>Registros en 'invitations': $newCount</li>";
        echo "</ul>";
        
        if ($oldCount > $newCount) {
            echo "<div class='warning'>⚠️ La tabla antigua tiene más registros. Considera migrar los datos faltantes.</div>";
        } elseif ($newCount >= $oldCount) {
            echo "<div class='success'>✅ La migración parece completa. Puedes considerar eliminar la tabla antigua.</div>";
        }
        
    } elseif (!$oldTableExists && $newTableExists) {
        echo "<h2>✅ Migración Ya Completada</h2>";
        echo "<div class='success'>La tabla con nombres en inglés ya existe y la antigua no está presente.</div>";
        
        // Verificar estructura
        $stmt = $pdo->query("DESCRIBE invitations");
        $columns = $stmt->fetchAll();
        
        echo "<h3>📋 Estructura Actual de 'invitations'</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<h2>❌ Ninguna Tabla Encontrada</h2>";
        echo "<div class='error'>No se encontró ninguna tabla de invitaciones. Ejecuta el script de instalación primero.</div>";
        echo "<a href='install_admin_tables.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Instalar Tablas</a>";
    }
    
    echo "<br><br>";
    echo "<a href='test_installation.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Test de Instalación</a>";
    echo "<a href='index.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ir al Sistema Admin</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
