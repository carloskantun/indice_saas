<?php
/**
 * Punto de entrada principal del sistema SaaS
 * Indice SaaS - Sistema modular para múltiples empresas
 */

require_once 'config.php';

// Si el usuario está autenticado, redirigir a empresas
if (checkAuth()) {
    redirect('companies/');
} else {
    // Si no está autenticado, redirigir al login
    redirect('auth/');
}
?>
