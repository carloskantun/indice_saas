<?php
require_once 'config.php';

echo "🔧 EXPANDIENDO TABLA USERS PARA PERFIL COMPLETO\n";
echo "===============================================\n\n";

$db = getDB();

try {
    echo "1️⃣ Verificando estructura actual de users...\n";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_columns = [];
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
    }
    
    echo "Columnas existentes: " . implode(', ', $existing_columns) . "\n\n";
    
    echo "2️⃣ Agregando nuevas columnas...\n";
    
    // Columnas para información personal completa
    $new_columns = [
        'first_name' => "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL AFTER name",
        'last_name' => "ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email",
        'birth_date' => "ALTER TABLE users ADD COLUMN birth_date DATE NULL AFTER phone",
        'gender' => "ALTER TABLE users ADD COLUMN gender ENUM('M', 'F', 'Otro', 'Prefiero no decir') NULL AFTER birth_date",
        'fiscal_id' => "ALTER TABLE users ADD COLUMN fiscal_id VARCHAR(50) NULL AFTER gender",
        
        // Dirección
        'address' => "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER fiscal_id",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address",
        'state' => "ALTER TABLE users ADD COLUMN state VARCHAR(100) NULL AFTER city",
        'country' => "ALTER TABLE users ADD COLUMN country VARCHAR(100) DEFAULT 'México' AFTER state",
        'postal_code' => "ALTER TABLE users ADD COLUMN postal_code VARCHAR(10) NULL AFTER country",
        
        // Información adicional
        'bio' => "ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER postal_code",
        'timezone' => "ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'America/Mexico_City' AFTER bio",
        'language' => "ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'es' AFTER timezone",
        'notifications_email' => "ALTER TABLE users ADD COLUMN notifications_email BOOLEAN DEFAULT TRUE AFTER language",
        'notifications_sms' => "ALTER TABLE users ADD COLUMN notifications_sms BOOLEAN DEFAULT FALSE AFTER notifications_email",
        
        // Avatar y metadata
        'avatar' => "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER notifications_sms",
        'last_login' => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER avatar",
        'login_attempts' => "ALTER TABLE users ADD COLUMN login_attempts INT(11) DEFAULT 0 AFTER last_login"
    ];
    
    $added_count = 0;
    foreach ($new_columns as $column_name => $sql) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $db->exec($sql);
                echo "✅ Agregada: $column_name\n";
                $added_count++;
            } catch (Exception $e) {
                echo "❌ Error agregando $column_name: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⏭️ Ya existe: $column_name\n";
        }
    }
    
    echo "\n3️⃣ Actualizando datos existentes...\n";
    
    // Separar nombres existentes en first_name y last_name
    if ($added_count > 0) {
        $stmt = $db->query("SELECT id, name FROM users WHERE (first_name IS NULL OR first_name = '') AND name IS NOT NULL");
        $users_to_update = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users_to_update as $user) {
            $name_parts = explode(' ', trim($user['name']), 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            $update_stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
            $update_stmt->execute([$first_name, $last_name, $user['id']]);
            
            echo "✅ Actualizado: " . $user['name'] . " → $first_name $last_name\n";
        }
    }
    
    echo "\n4️⃣ Estructura final de users:\n";
    $stmt = $db->query("DESCRIBE users");
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($final_columns as $column) {
        $required = ($column['Null'] === 'NO') ? '[REQUERIDO]' : '[OPCIONAL]';
        $default = $column['Default'] ? " DEFAULT: " . $column['Default'] : '';
        echo "- " . $column['Field'] . " (" . $column['Type'] . ") $required$default\n";
    }
    
    echo "\n✅ TABLA USERS EXPANDIDA EXITOSAMENTE\n";
    echo "Total columnas agregadas: $added_count\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
