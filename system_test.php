<?php
/**
 * Test de Sistema - Verificación de Translation System
 * Prueba los componentes críticos del sistema de traducciones
 */

// No mostrar errores directamente al usuario
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='" . getCurrentLanguage() . "'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Sistema Test - " . ($lang['app_name'] ?? 'Índice SaaS') . "</title>\n";
echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>\n";
echo "</head>\n";
echo "<body class='bg-light'>\n";
echo "<div class='container mt-5'>\n";
echo "    <div class='row justify-content-center'>\n";
echo "        <div class='col-md-8'>\n";
echo "            <div class='card shadow'>\n";
echo "                <div class='card-header bg-primary text-white'>\n";
echo "                    <h3 class='mb-0'><i class='fas fa-cogs me-2'></i>Test del Sistema</h3>\n";
echo "                </div>\n";
echo "                <div class='card-body'>\n";

// Test 1: Language System
echo "                    <h5 class='text-primary'>1. Sistema de Idiomas</h5>\n";
echo "                    <p><strong>Idioma actual:</strong> " . getCurrentLanguage() . "</p>\n";
echo "                    <p><strong>Idiomas disponibles:</strong> " . implode(', ', array_keys(AVAILABLE_LANGUAGES)) . "</p>\n";
echo "                    <p><strong>App Name:</strong> " . ($lang['app_name'] ?? 'NO DEFINIDO') . "</p>\n";
echo "                    <p><strong>Login text:</strong> " . ($lang['login'] ?? 'NO DEFINIDO') . "</p>\n";
echo "                    <p><strong>Notifications text:</strong> " . ($lang['notifications'] ?? 'NO DEFINIDO') . "</p>\n";

// Test 2: Language Selector
echo "                    <h5 class='text-primary mt-4'>2. Selector de Idioma</h5>\n";
echo "                    <div class='mb-3'>\n";
if (function_exists('renderLanguageSelector')) {
    echo "                        <p>Versión Normal:</p>\n";
    echo "                        " . renderLanguageSelector() . "\n";
    if (function_exists('renderLanguageSelectorNavbar')) {
        echo "                        <p class='mt-3'>Versión Navbar:</p>\n";
        echo "                        <div class='bg-primary p-2 rounded'>\n";
        echo "                            " . renderLanguageSelectorNavbar() . "\n";
        echo "                        </div>\n";
    }
    if (function_exists('renderLanguageSelectorMini')) {
        echo "                        <p class='mt-3'>Versión Mini:</p>\n";
        echo "                        " . renderLanguageSelectorMini() . "\n";
    }
} else {
    echo "                        <div class='alert alert-warning'>Función renderLanguageSelector no está disponible</div>\n";
}
echo "                    </div>\n";

// Test 3: Session Info
echo "                    <h5 class='text-primary mt-4'>3. Información de Sesión</h5>\n";
echo "                    <p><strong>Sesión activa:</strong> " . (session_status() == PHP_SESSION_ACTIVE ? 'Sí' : 'No') . "</p>\n";
echo "                    <p><strong>Usuario logueado:</strong> " . (isset($_SESSION['user_id']) ? 'Sí (ID: ' . $_SESSION['user_id'] . ')' : 'No') . "</p>\n";

// Test 4: Database Connection
echo "                    <h5 class='text-primary mt-4'>4. Conexión a Base de Datos</h5>\n";
try {
    $db = getDB();
    echo "                    <p class='text-success'><i class='fas fa-check'></i> Conexión exitosa</p>\n";
} catch (Exception $e) {
    echo "                    <p class='text-danger'><i class='fas fa-times'></i> Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 5: Components
echo "                    <h5 class='text-primary mt-4'>5. Componentes</h5>\n";
$components = [
    'components/language_selector.php',
    'components/navbar_notifications_safe.php'
];

foreach ($components as $component) {
    if (file_exists($component)) {
        echo "                    <p class='text-success'><i class='fas fa-check'></i> $component existe</p>\n";
    } else {
        echo "                    <p class='text-danger'><i class='fas fa-times'></i> $component NO existe</p>\n";
    }
}

// Test 6: Translations Check
echo "                    <h5 class='text-primary mt-4'>6. Verificación de Traducciones</h5>\n";
$critical_keys = ['app_name', 'login', 'notifications', 'companies', 'dashboard'];
foreach ($critical_keys as $key) {
    if (isset($lang[$key])) {
        echo "                    <p class='text-success'><i class='fas fa-check'></i> '$key': " . htmlspecialchars($lang[$key]) . "</p>\n";
    } else {
        echo "                    <p class='text-danger'><i class='fas fa-times'></i> '$key': NO DEFINIDO</p>\n";
    }
}

echo "                </div>\n";
echo "                <div class='card-footer'>\n";
echo "                    <a href='auth/' class='btn btn-primary'>Ir al Login</a>\n";
echo "                    <a href='companies/' class='btn btn-secondary'>Ir a Empresas</a>\n";
echo "                    <a href='?lang=" . (getCurrentLanguage() == 'es' ? 'en' : 'es') . "' class='btn btn-outline-primary'>Cambiar Idioma</a>\n";
echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>\n";
echo "</body>\n";
echo "</html>\n";
