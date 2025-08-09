<?php
require_once 'config.php';

echo "🏗️ EXPANDIENDO TABLA USERS PARA PERFILES COMPLETOS\n";
echo "===================================================\n\n";

$db = getDB();

try {
    echo "1️⃣ Verificando estructura actual de users...\n";
    $stmt = $db->query("DESCRIBE users");
    $current_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_fields = [];
    foreach ($current_columns as $column) {
        $existing_fields[] = $column['Field'];
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    echo "\n";
    
    echo "2️⃣ Agregando campos adicionales para perfil completo...\n";
    
    // Campos adicionales para perfil de usuario completo
    $additional_fields = [
        'first_name' => "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL AFTER name",
        'last_name' => "ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email",
        'address' => "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address",
        'state' => "ALTER TABLE users ADD COLUMN state VARCHAR(100) NULL AFTER city",
        'country' => "ALTER TABLE users ADD COLUMN country VARCHAR(100) NULL AFTER state",
        'postal_code' => "ALTER TABLE users ADD COLUMN postal_code VARCHAR(20) NULL AFTER country",
        'fiscal_id' => "ALTER TABLE users ADD COLUMN fiscal_id VARCHAR(50) NULL AFTER postal_code",
        'birth_date' => "ALTER TABLE users ADD COLUMN birth_date DATE NULL AFTER fiscal_id",
        'gender' => "ALTER TABLE users ADD COLUMN gender ENUM('Masculino','Femenino','Otro','Prefiero no decir') NULL AFTER birth_date",
        'profile_picture' => "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER gender",
        'bio' => "ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER profile_picture",
        'timezone' => "ALTER TABLE users ADD COLUMN timezone VARCHAR(100) DEFAULT 'America/Mexico_City' AFTER bio",
        'language' => "ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'es' AFTER timezone",
        'notification_email' => "ALTER TABLE users ADD COLUMN notification_email BOOLEAN DEFAULT TRUE AFTER language",
        'notification_sms' => "ALTER TABLE users ADD COLUMN notification_sms BOOLEAN DEFAULT FALSE AFTER notification_email",
        'two_factor_enabled' => "ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE AFTER notification_sms",
        'last_login' => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER two_factor_enabled",
        'login_count' => "ALTER TABLE users ADD COLUMN login_count INT(11) DEFAULT 0 AFTER last_login"
    ];
    
    $added_count = 0;
    $skipped_count = 0;
    
    foreach ($additional_fields as $field_name => $sql) {
        if (!in_array($field_name, $existing_fields)) {
            try {
                $db->exec($sql);
                echo "✅ Campo '$field_name' agregado\n";
                $added_count++;
            } catch (Exception $e) {
                echo "❌ Error agregando '$field_name': " . $e->getMessage() . "\n";
            }
        } else {
            echo "⏭️ Campo '$field_name' ya existe\n";
            $skipped_count++;
        }
    }
    
    echo "\n3️⃣ Actualizando campo 'name' con datos de first_name y last_name existentes...\n";
    
    // Si hay usuarios sin first_name/last_name, dividir el campo 'name'
    $stmt = $db->query("
        SELECT id, name, first_name, last_name 
        FROM users 
        WHERE (first_name IS NULL OR first_name = '') 
        AND name IS NOT NULL AND name != ''
    ");
    $users_to_update = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users_to_update as $user) {
        $name_parts = explode(' ', trim($user['name']), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        
        $stmt = $db->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ? 
            WHERE id = ?
        ");
        $stmt->execute([$first_name, $last_name, $user['id']]);
        
        echo "✅ Usuario ID {$user['id']}: '$first_name' '$last_name'\n";
    }
    
    echo "\n4️⃣ Estructura final de tabla users:\n";
    $stmt = $db->query("DESCRIBE users");
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($final_columns as $column) {
        $null_info = ($column['Null'] === 'NO') ? '[REQUERIDO]' : '[OPCIONAL]';
        $default = $column['Default'] ? " DEFAULT: " . $column['Default'] : '';
        echo "- " . $column['Field'] . " (" . $column['Type'] . ") $null_info$default\n";
    }
    
    echo "\n📊 RESUMEN:\n";
    echo "- Campos agregados: $added_count\n";
    echo "- Campos ya existentes: $skipped_count\n";
    echo "- Usuarios actualizados: " . count($users_to_update) . "\n";
    
    echo "\n✅ TABLA USERS EXPANDIDA EXITOSAMENTE\n";
    echo "Ahora puedes crear el archivo profile.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
