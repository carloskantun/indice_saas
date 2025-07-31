<?php
/**
 * Página de acceso al Panel Root
 * Redirección hacia el panel de administración para usuarios root
 */

require_once 'config.php';

// Verificar si el usuario está autenticado
if (!checkAuth()) {
    redirect('auth/index.php');
}

// Verificar si tiene permisos de root
if (!checkRole(['root'])) {
    // Si no es root, redirigir al dashboard general o mostrar error
    if (checkAuth()) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Acceso Denegado - {$lang['app_name']}</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
            <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
        </head>
        <body class='bg-light'>
            <div class='container mt-5'>
                <div class='row justify-content-center'>
                    <div class='col-md-6'>
                        <div class='card border-danger'>
                            <div class='card-body text-center'>
                                <i class='fas fa-exclamation-triangle text-danger fa-3x mb-3'></i>
                                <h3 class='text-danger'>{$lang['access_denied']}</h3>
                                <p class='text-muted'>No tienes permisos para acceder al panel de administración.</p>
                                <p class='text-muted'>Solo los usuarios con rol <strong>root</strong> pueden acceder a esta sección.</p>
                                <div class='mt-4'>
                                    <a href='" . BASE_URL . "' class='btn btn-primary'>
                                        <i class='fas fa-home'></i> Ir al Dashboard
                                    </a>
                                    <a href='" . BASE_URL . "auth/logout.php' class='btn btn-outline-secondary'>
                                        <i class='fas fa-sign-out-alt'></i> Cerrar Sesión
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    } else {
        redirect('auth/index.php?error=unauthorized');
    }
    exit();
}

// Si llega aquí, es un usuario root válido
redirect('panel_root/index.php');
?>
