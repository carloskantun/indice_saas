<?php
/**
 * Test específico para Select2 en modales
 */

require_once '../../config.php';

if (!checkAuth()) {
    die("Error de autenticación");
}

$company_id = $_SESSION['company_id'] ?? null;
?>
<!DOCTYPE html>                    // Reinicializar Select2 con configuración específica para modales
                    try {
                        $select.select2({
                            language: 'es',
                            placeholder: 'Seleccionar...',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: $modal,
                            escapeMarkup: function (markup) {
                                return markup;
                            }
                        });
                        addLog(`   └─ ✅ Select2 reinicializado: ${selectName}`, 'success');g="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Select2 en Modales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .debug-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .provider-count {
            font-weight: bold;
            color: #28a745;
        }
        
        /* Fix para Select2 en modales de Bootstrap */
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
        
        /* Evitar que el modal se cierre al hacer click en Select2 */
        .select2-container--open .select2-dropdown {
            z-index: 10007 !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 Test Select2 en Modales</h1>
        
        <div class="debug-box">
            <h5>📊 Estado Inicial</h5>
            <p><strong>Company ID:</strong> <?php echo $company_id; ?></p>
            <div id="initial-debug"></div>
        </div>
        
        <!-- Botones para abrir modales -->
        <div class="mb-4">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fas fa-plus me-2"></i>Modal Gasto
            </button>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#orderModal">
                <i class="fas fa-file-invoice me-2"></i>Modal Orden
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editExpenseModal">
                <i class="fas fa-edit me-2"></i>Modal Edición
            </button>
        </div>
        
        <!-- Log de eventos -->
        <div class="debug-box">
            <h5>📝 Log de Eventos</h5>
            <div id="event-log" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;"></div>
            <button class="btn btn-sm btn-outline-secondary mt-2" onclick="clearLog()">Limpiar Log</button>
        </div>
    </div>

    <!-- Variables para modals.php -->
    <?php 
    $company_id_for_modals = $company_id;
    $business_id_for_modals = $_SESSION['business_id'] ?? null;
    ?>
    
    <!-- Incluir modales -->
    <?php include 'modals.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        let logCount = 0;
        
        function addLog(message, type = 'info') {
            logCount++;
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                'info': '#007bff',
                'success': '#28a745', 
                'warning': '#ffc107',
                'error': '#dc3545'
            };
            
            const logDiv = document.getElementById('event-log');
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `<span style="color: #666;">[${timestamp}]</span> <span style="color: ${colors[type] || '#000'};">${message}</span>`;
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('event-log').innerHTML = '';
            logCount = 0;
        }
        
        $(document).ready(function() {
            addLog('🚀 Iniciando test de Select2 en modales', 'info');
            
            // Debug inicial
            let initialDebug = 'Modales encontrados: ';
            $('.modal').each(function() {
                initialDebug += this.id + ' ';
            });
            $('#initial-debug').html('<p>' + initialDebug + '</p>');
            addLog('📊 ' + initialDebug, 'info');
            
            // Verificar proveedores en HTML inicial
            $('select[name*="provider"]').each(function() {
                const selectName = $(this).attr('name') || 'unknown';
                const options = $(this).find('option');
                const validOptions = $(this).find('option[value!=""]');
                const modalId = $(this).closest('.modal').attr('id') || 'no-modal';
                
                addLog(`📋 ${modalId} - ${selectName}: ${options.length} opciones, ${validOptions.length} proveedores`, 'info');
                
                if (validOptions.length > 0) {
                    validOptions.each(function() {
                        addLog(`   └─ ${$(this).val()}: ${$(this).text()}`, 'success');
                    });
                } else {
                    addLog(`   └─ ⚠️ Sin proveedores válidos`, 'warning');
                }
            });
            
            // Inicializar Select2 inicial
            try {
                $('.select2').select2({
                    language: 'es',
                    placeholder: 'Seleccionar...',
                    allowClear: true,
                    width: '100%'
                });
                addLog('✅ Select2 inicializado correctamente', 'success');
            } catch (error) {
                addLog('❌ Error inicializando Select2: ' + error.message, 'error');
            }
            
            // Evitar que el modal se cierre al interactuar con Select2
            $(document).on('click', '.select2-container', function(e) {
                e.stopPropagation();
                addLog('🛡️ Click en Select2 container - evento detenido', 'info');
            });
            
            $(document).on('click', '.select2-dropdown', function(e) {
                e.stopPropagation();
                addLog('🛡️ Click en Select2 dropdown - evento detenido', 'info');
            });
            
            // Configurar modales para que no se cierren con clicks en Select2
            $('.modal').on('click', function(e) {
                if ($(e.target).closest('.select2-container').length || 
                    $(e.target).closest('.select2-dropdown').length) {
                    e.stopPropagation();
                    addLog('🛡️ Click en modal sobre Select2 - evento detenido', 'warning');
                    return false;
                }
            });
            
            // Eventos de modales
            $('.modal').on('show.bs.modal', function() {
                const modalId = this.id;
                addLog(`🎯 Abriendo modal: ${modalId}`, 'info');
            });
            
            $('.modal').on('shown.bs.modal', function() {
                const modalId = this.id;
                addLog(`✨ Modal abierto: ${modalId}`, 'success');
                
                // Reinicializar Select2 en este modal
                $(this).find('.select2').each(function() {
                    const $select = $(this);
                    const selectName = $select.attr('name') || 'unknown';
                    const currentValue = $select.val();
                    
                    addLog(`🔄 Reinicializando Select2: ${selectName}`, 'info');
                    
                    // Destruir Select2 si existe
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                        addLog(`   └─ Select2 destruido para ${selectName}`, 'info');
                    }
                    
                    // Reinicializar Select2
                    try {
                        $select.select2({
                            language: 'es',
                            placeholder: 'Seleccionar...',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: $(this).closest('.modal')
                        });
                        addLog(`   └─ ✅ Select2 reinicializado: ${selectName}`, 'success');
                        
                        // Restaurar valor si existía
                        if (currentValue) {
                            $select.val(currentValue).trigger('change');
                            addLog(`   └─ Valor restaurado: ${currentValue}`, 'info');
                        }
                        
                        // Contar opciones
                        const options = $select.find('option');
                        const validOptions = $select.find('option[value!=""]');
                        addLog(`   └─ 📊 ${options.length} opciones, ${validOptions.length} válidas`, 'info');
                        
                    } catch (error) {
                        addLog(`   └─ ❌ Error reinicializando ${selectName}: ${error.message}`, 'error');
                    }
                }.bind(this));
            });
            
            $('.modal').on('hidden.bs.modal', function() {
                const modalId = this.id;
                addLog(`🚪 Modal cerrado: ${modalId}`, 'info');
            });
            
            addLog('🎉 Test inicializado completamente', 'success');
        });
    </script>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary">← Volver</a>
        <a href="simple_test.php" class="btn btn-outline-info">Test Simple</a>
        <a href="direct_debug.php" class="btn btn-outline-primary">Debug Directo</a>
    </div>
</body>
</html>
