<?php
/**
 * Test mínimo para probar modales sin conflictos
 */

require_once '../../config.php';

if (!checkAuth()) {
    die("Error de autenticación");
}

$company_id = $_SESSION['company_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mínimo - Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        /* Fix para Select2 en modales */
        .select2-container {
            z-index: 10000 !important;
        }
        .select2-dropdown {
            z-index: 10001 !important;
        }
        .modal .select2-container {
            z-index: 10005 !important;
        }
        .modal .select2-dropdown {
            z-index: 10006 !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 Test Mínimo - Modal Sin Conflictos</h1>
        
        <div class="alert alert-info">
            <strong>Company ID:</strong> <?php echo $company_id; ?>
        </div>
        
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
            Abrir Modal de Prueba
        </button>
        
        <div id="log" class="mt-3 p-3 bg-light" style="height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;"></div>
    </div>

    <!-- Modal de prueba -->
    <div class="modal fade" id="testModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" id="test-provider" name="provider_id">
                            <option value="">Sin proveedor</option>
                            <?php
                            if ($company_id) {
                                $db = getDB();
                                $sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
                                $stmt = $db->prepare($sql);
                                $stmt->execute([$company_id]);
                                $providers = $stmt->fetchAll();
                                
                                foreach ($providers as $provider) {
                                    echo "<option value='{$provider['id']}'>" . htmlspecialchars($provider['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    
    <script>
        function addLog(message) {
            const timestamp = new Date().toLocaleTimeString();
            const logDiv = document.getElementById('log');
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `[${timestamp}] ${message}`;
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        $(document).ready(function() {
            addLog('🚀 Iniciando test mínimo');
            
            // NO inicializar Select2 automáticamente
            addLog('⏸️ Saltando inicialización automática de Select2');
            
            // Eventos del modal
            $('#testModal').on('show.bs.modal', function() {
                addLog('🎯 Abriendo modal...');
            });
            
            $('#testModal').on('shown.bs.modal', function() {
                addLog('✨ Modal abierto exitosamente');
                
                // AHORA inicializar Select2
                addLog('🔄 Inicializando Select2 en modal...');
                try {
                    $('#test-provider').select2({
                        language: 'es',
                        placeholder: 'Seleccionar proveedor...',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('#testModal')
                    });
                    addLog('✅ Select2 inicializado correctamente');
                    
                    // Contar opciones
                    const options = $('#test-provider option');
                    const validOptions = $('#test-provider option[value!=""]');
                    addLog(`📊 ${options.length} opciones total, ${validOptions.length} proveedores válidos`);
                    
                } catch (error) {
                    addLog('❌ Error inicializando Select2: ' + error.message);
                }
            });
            
            $('#testModal').on('hidden.bs.modal', function() {
                addLog('🚪 Modal cerrado');
                
                // Destruir Select2 al cerrar
                if ($('#test-provider').hasClass('select2-hidden-accessible')) {
                    $('#test-provider').select2('destroy');
                    addLog('🧹 Select2 destruido');
                }
            });
            
            // Capturar errores JavaScript
            window.onerror = function(msg, url, line, col, error) {
                addLog('❌ Error JS: ' + msg + ' (línea ' + line + ')');
                return false;
            };
            
            addLog('✅ Test mínimo configurado');
        });
    </script>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary">← Volver</a>
        <a href="select2_test.php" class="btn btn-outline-info">Test Completo</a>
    </div>
</body>
</html>
