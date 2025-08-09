<?php
require_once 'config.php';

echo "🔍 VERIFICANDO ESTRUCTURA REAL DE TABLA USERS\n";
echo "==============================================\n\n";

$db = getDB();

try {
    // 1. Verificar estructura de la tabla users
    echo "1️⃣ Estructura de tabla 'users':\n";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $user_columns = [];
    foreach ($columns as $column) {
        $user_columns[] = $column['Field'];
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    echo "\n";
    
    // 2. Ver algunos usuarios de ejemplo
    echo "2️⃣ Usuarios existentes (primeros 5):\n";
    $stmt = $db->query("SELECT * FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "- ID: " . $user['id'];
        
        // Mostrar nombre según estructura disponible
        if (isset($user['name'])) {
            echo " | Nombre: " . $user['name'];
        }
        if (isset($user['first_name'])) {
            echo " | Nombre: " . $user['first_name'] . " " . ($user['last_name'] ?? '');
        }
        if (isset($user['username'])) {
            echo " | Username: " . $user['username'];
        }
        if (isset($user['email'])) {
            echo " | Email: " . $user['email'];
        }
        
        echo "\n";
    }
    echo "\n";
    
    // 3. Buscar específicamente carlosadmin@indiceapp.com
    echo "3️⃣ Buscando carlosadmin@indiceapp.com:\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['carlosadmin@indiceapp.com']);
    $carlos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($carlos) {
        echo "✅ Usuario encontrado:\n";
        foreach ($carlos as $key => $value) {
            echo "- $key: $value\n";
        }
    } else {
        echo "❌ Usuario NO encontrado\n";
        
        // Buscar usuarios similares
        echo "\n🔍 Buscando usuarios con 'carlos' en email:\n";
        $stmt = $db->prepare("SELECT * FROM users WHERE email LIKE ?");
        $stmt->execute(['%carlos%']);
        $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($similar as $user) {
            echo "- ID: " . $user['id'] . " | Email: " . $user['email'] . "\n";
        }
    }
    
    echo "\n4️⃣ Generar funciones corregidas:\n";
    echo "==================================\n";
    
    // Generar detectExistingUser corregida según estructura real
    $name_field = 'name'; // Por defecto
    if (in_array('first_name', $user_columns)) {
        $name_field = 'first_name';
    }
    
    echo "Columna de nombre detectada: '$name_field'\n";
    echo "Estructura necesaria para detectExistingUser():\n\n";
    
    $corrected_function = "function detectExistingUser(\$email) {\n";
    $corrected_function .= "    \$db = getDB();\n";
    $corrected_function .= "    try {\n";
    $corrected_function .= "        \$stmt = \$db->prepare(\"\n";
    
    if (in_array('first_name', $user_columns) && in_array('last_name', $user_columns)) {
        $corrected_function .= "            SELECT u.id, u.first_name, u.last_name, u.email,\n";
        $corrected_function .= "                   u.created_at, u.status as user_status,\n";
        $corrected_function .= "                   COUNT(uc.company_id) as companies_count\n";
    } elseif (in_array('name', $user_columns)) {
        $corrected_function .= "            SELECT u.id, u.name, u.email,\n";
        $corrected_function .= "                   u.created_at, u.status as user_status,\n";
        $corrected_function .= "                   COUNT(uc.company_id) as companies_count\n";
    }
    
    $corrected_function .= "            FROM users u\n";
    $corrected_function .= "            LEFT JOIN user_companies uc ON u.id = uc.user_id\n";
    $corrected_function .= "            WHERE u.email = ?\n";
    $corrected_function .= "            GROUP BY u.id\n";
    $corrected_function .= "        \");\n";
    $corrected_function .= "        // ... resto del código\n";
    $corrected_function .= "    }\n";
    $corrected_function .= "}\n";
    
    echo $corrected_function;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
