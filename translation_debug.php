<?php
/**
 * Diagnóstico específico para el problema de traducciones
 */

require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Traducciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-section { margin-bottom: 2rem; }
        .key-found { color: #28a745; }
        .key-missing { color: #dc3545; }
        .language-info { background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🔍 Diagnóstico de Traducciones</h1>
                
                <div class="debug-section">
                    <h3>📋 Información General</h3>
                    <div class="language-info">
                        <p><strong>Idioma actual:</strong> <?php echo getCurrentLanguage(); ?></p>
                        <p><strong>Idiomas disponibles:</strong> <?php echo implode(', ', array_keys(AVAILABLE_LANGUAGES)); ?></p>
                        <p><strong>Archivo de idioma cargado:</strong> lang/<?php echo getCurrentLanguage(); ?>.php</p>
                        <p><strong>Total de traducciones cargadas:</strong> <?php echo count($lang); ?></p>
                    </div>
                </div>

                <div class="debug-section">
                    <h3>🎯 Claves Críticas</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Claves que causan problemas:</h5>
                            <ul class="list-group">
                                <?php
                                $critical_keys = ['notifications', 'notification', 'no_notifications', 'app_name', 'companies'];
                                foreach ($critical_keys as $key): ?>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>'<?php echo $key; ?>'</span>
                                        <?php if (isset($lang[$key])): ?>
                                            <span class="key-found">✓ <?php echo htmlspecialchars($lang[$key]); ?></span>
                                        <?php else: ?>
                                            <span class="key-missing">✗ NO DEFINIDO</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Búsqueda de 'notifications':</h5>
                            <div class="card">
                                <div class="card-body">
                                    <?php
                                    $notification_keys = array_filter(array_keys($lang), function($key) {
                                        return strpos($key, 'notif') !== false;
                                    });
                                    ?>
                                    <p><strong>Claves encontradas con 'notif':</strong></p>
                                    <?php if (!empty($notification_keys)): ?>
                                        <ul>
                                            <?php foreach ($notification_keys as $key): ?>
                                                <li><code><?php echo $key; ?></code> = "<?php echo htmlspecialchars($lang[$key]); ?>"</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-danger">⚠️ No se encontraron claves relacionadas con notificaciones</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="debug-section">
                    <h3>🧪 Test Directo</h3>
                    <div class="alert alert-info">
                        <h5>Simulación del error en companies/index.php línea 247:</h5>
                        <code>echo $lang['notifications'];</code>
                        <br><br>
                        <strong>Resultado:</strong> 
                        <?php if (isset($lang['notifications'])): ?>
                            <span class="key-found">"<?php echo $lang['notifications']; ?>"</span>
                        <?php else: ?>
                            <span class="key-missing">❌ Warning: Undefined array key "notifications"</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="debug-section">
                    <h3>📝 Archivo de Idioma Actual</h3>
                    <div class="row">
                        <div class="col-12">
                            <details>
                                <summary class="btn btn-outline-secondary">Ver todas las traducciones cargadas (<?php echo count($lang); ?> entradas)</summary>
                                <div class="mt-3" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Clave</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lang as $key => $value): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($key); ?></code></td>
                                                    <td><?php echo htmlspecialchars($value); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="debug-section">
                    <h3>🔄 Acciones</h3>
                    <div class="btn-group" role="group">
                        <a href="?lang=es" class="btn btn-primary">Probar en Español</a>
                        <a href="?lang=en" class="btn btn-success">Probar en Inglés</a>
                        <a href="companies/" class="btn btn-warning">Ir a Companies (test real)</a>
                        <a href="system_test.php" class="btn btn-info">Test Completo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
