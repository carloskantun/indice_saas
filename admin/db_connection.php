<?php
/*
 * Configuración de conexión para el sistema de permisos
 * Este archivo centraliza la configuración de BD para evitar problemas
 */

// Incluir configuración principal que carga las variables de entorno
require_once __DIR__ . '/../config.php';

function getPermissionsDBConnection() {
    // Usar las variables de entorno cargadas desde config.php
    if (isset($_ENV['DB_HOST']) && isset($_ENV['DB_USER']) && isset($_ENV['DB_PASS']) && isset($_ENV['DB_NAME'])) {
        return [
            'host' => $_ENV['DB_HOST'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASS'],
            'database' => $_ENV['DB_NAME']
        ];
    }
    
    return null;
}

function createPermissionsConnection() {
    $config = getPermissionsDBConnection();
    
    if (!$config) {
        throw new Exception("No se encontró configuración de base de datos válida en .env");
    }
    
    $mysqli = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($mysqli->connect_error) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    return $mysqli;
}
