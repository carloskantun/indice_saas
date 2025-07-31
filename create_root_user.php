<?php
/**
 * Script para crear usuario root en el sistema
 * Ejecutar cuando necesites crear un nuevo usuario root
 */

require_once 'config.php';

try {
    $pdo = getDB();
    
    echo "=== Configurador de Usuario Root ===\n\n";
    
    // Verificar si ya existe un usuario root
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, c.name as company_name
        FROM users u 
        INNER JOIN user_companies uc ON u.id = uc.user_id 
        INNER JOIN companies c ON uc.company_id = c.id
        WHERE uc.role = 'root' AND uc.status = 'active'
    ");
    $rootUsers = $stmt->fetchAll();
    
    if (!empty($rootUsers)) {
        echo "🔍 Usuarios root existentes:\n";
        foreach ($rootUsers as $user) {
            echo "  • {$user['name']} ({$user['email']}) - Empresa: {$user['company_name']}\n";
        }
        echo "\n";
    }
    
    // Solicitar datos del nuevo usuario root
    echo "📝 Crear nuevo usuario root:\n";
    echo "Nombre completo: ";
    $name = trim(fgets(STDIN));
    
    echo "Email: ";
    $email = trim(fgets(STDIN));
    
    echo "Password: ";
    $password = trim(fgets(STDIN));
    
    if (empty($name) || empty($email) || empty($password)) {
        echo "❌ Todos los campos son requeridos.\n";
        exit(1);
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "❌ Email no válido.\n";
        exit(1);
    }
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "❌ Ya existe un usuario con ese email.\n";
        exit(1);
    }
    
    // Crear usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (?, ?, ?, 'active')");
    $stmt->execute([$name, $email, $hashedPassword]);
    $userId = $pdo->lastInsertId();
    
    echo "✅ Usuario creado con ID: $userId\n";
    
    // Buscar empresa "Sistema" o crearla
    $stmt = $pdo->query("SELECT id FROM companies WHERE name = 'Sistema' LIMIT 1");
    $systemCompany = $stmt->fetch();
    
    if (!$systemCompany) {
        // Crear empresa Sistema
        $stmt = $pdo->prepare("INSERT INTO companies (name, description, status, created_by) VALUES (?, ?, 'active', ?)");
        $stmt->execute(['Sistema', 'Empresa del sistema para administración global', $userId]);
        $companyId = $pdo->lastInsertId();
        echo "✅ Empresa 'Sistema' creada con ID: $companyId\n";
    } else {
        $companyId = $systemCompany['id'];
        echo "ℹ️ Usando empresa 'Sistema' existente (ID: $companyId)\n";
    }
    
    // Asignar rol root
    $stmt = $pdo->prepare("INSERT INTO user_companies (user_id, company_id, role, status) VALUES (?, ?, 'root', 'active')");
    $stmt->execute([$userId, $companyId]);
    
    echo "✅ Rol 'root' asignado exitosamente\n\n";
    
    echo "🎉 Usuario root configurado correctamente!\n";
    echo "📧 Email: $email\n";
    echo "🔐 Password: $password\n";
    echo "🏢 Empresa: Sistema\n";
    echo "👑 Rol: root\n\n";
    
    echo "🔗 Acceso al panel: " . BASE_URL . "root.php\n";
    echo "⚠️ Guarda estas credenciales en un lugar seguro.\n";
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
