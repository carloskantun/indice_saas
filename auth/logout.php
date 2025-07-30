<?php
require_once '../config.php';

// Destruir sesión
session_destroy();

// Redirigir al login con mensaje
redirect('auth/?logout=1');
?>
