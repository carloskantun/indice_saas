<?php
/**
 * Configuración principal del sistema SaaS
 * Indice SaaS - Sistema modular para múltiples empresas
 */

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'corazon_indicesaas');
define('DB_USER', 'corazon_caribe');
define('DB_PASS', 'Kantun.01*');

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
    
    $userRole = $_SESSION['current_role'] ?? 'user';
    return in_array($userRole, $requiredRoles);
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . BASE_URL . ltrim($url, '/'));
    exit();
}

// Conexión a base de datos usando PDO
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    return $pdo;
}
?>
