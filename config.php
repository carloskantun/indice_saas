<?php
/**
 * Configuración principal del sistema SaaS
 * Indice SaaS - Sistema modular para múltiples empresas
 */

// Leer configuración desde .env
function loadEnv($file = __DIR__ . '/.env') {
    if (!file_exists($file)) return;
    $vars = parse_ini_file($file);
    foreach ($vars as $key => $value) {
        $_ENV[$key] = $value;
    }
}
loadEnv();

// Rutas base del sistema
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para cargar idioma
function loadLanguage($lang = 'es') {
    $langFile = BASE_PATH . "/lang/{$lang}.php";
    if (file_exists($langFile)) {
        return include $langFile;
    }
    return [];
}

// Cargar idioma español por defecto
$lang = loadLanguage('es');

// Función para verificar autenticación
function checkAuth() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para verificar permisos por rol
function checkRole($requiredRoles = []) {
    if (!checkAuth()) {
        return false;
    }
    
    if (empty($requiredRoles)) {
        return true;
    }
    
    // Para el rol 'root' verificamos en la base de datos
    if (in_array('root', $requiredRoles)) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT uc.role 
                FROM user_companies uc 
                WHERE uc.user_id = ? AND uc.role = 'root' AND uc.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            
            if ($result && $result['role'] === 'root') {
                $_SESSION['current_role'] = 'root'; // Actualizar sesión
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error checking root role: " . $e->getMessage());
        }
    }
    
    // Para otros roles, usar el rol actual de la sesión
    $userRole = $_SESSION['current_role'] ?? 'user';
    return in_array($userRole, $requiredRoles);
}

// Función para obtener roles del usuario en todas las empresas
function getUserRoles($userId = null) {
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return [];
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT uc.role, c.name as company_name, uc.company_id
            FROM user_companies uc 
            INNER JOIN companies c ON uc.company_id = c.id
            WHERE uc.user_id = ? AND uc.status = 'active'
            ORDER BY 
                CASE uc.role
                    WHEN 'root' THEN 1
                    WHEN 'superadmin' THEN 2
                    WHEN 'admin' THEN 3
                    WHEN 'moderator' THEN 4
                    WHEN 'support' THEN 5
                    ELSE 6
                END
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting user roles: " . $e->getMessage());
        return [];
    }
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . BASE_URL . ltrim($url, '/'));
    exit();
}

// Conexión PDO
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("DB Connection Error: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Incluir configuración de email para sistema admin
if (file_exists(__DIR__ . '/admin/email_config.php')) {
    require_once __DIR__ . '/admin/email_config.php';
}
?>
