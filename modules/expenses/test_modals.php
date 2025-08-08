<?php
/**
 * Test rápido de modales con proveedores
 */

require_once '../../config.php';

if (!checkAuth()) {
    echo "❌ Usuario no autenticado<br>";
    exit;
}

$company_id = $_SESSION['company_id'] ?? null;
$business_id = $_SESSION['business_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Modales con Proveedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🧪 Test - Modales con Proveedores</h2>
        
        <div class="alert alert-info">
            <strong>Contexto:</strong> Company ID: <?php echo $company_id; ?>, Business ID: <?php echo $business_id; ?>
        </div>
        
        <div class="mb-3">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fas fa-plus me-2"></i>Test Modal Gasto
            </button>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#orderModal">
                <i class="fas fa-file-invoice me-2"></i>Test Modal Orden
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editExpenseModal">
                <i class="fas fa-edit me-2"></i>Test Modal Edición
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>🔍 Debug Info</h5>
            </div>
            <div class="card-body">
                <div id="debug-info">
                    Cargando información de debug...
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir modales -->
    <?php include 'modals.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                language: 'es',
                placeholder: 'Seleccionar...',
                allowClear: true,
                width: '100%'
            });
            
            // Debug info
            updateDebugInfo();
            
            // Eventos de modales para debug
            $('#expenseModal, #orderModal, #editExpenseModal').on('shown.bs.modal', function() {
                const modalId = this.id;
                console.log('Modal abierto:', modalId);
                
                // Contar opciones en select de proveedores
                const select = $(this).find('select[name="provider_id"]');
                const optionsCount = select.find('option').length;
                const validOptions = select.find('option[value!=""]').length;
                
                console.log('Opciones en select:', optionsCount, 'válidas:', validOptions);
                
                // Mostrar opciones
                select.find('option').each(function(i, option) {
                    console.log(`Opción ${i}:`, $(option).val(), '-', $(option).text());
                });
                
                updateDebugInfo();
            });
        });
        
        function updateDebugInfo() {
            let debugHtml = '<h6>📊 Estado Actual:</h6>';
            
            // Verificar cada modal
            const modals = ['expenseModal', 'orderModal', 'editExpenseModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const select = modal.querySelector('select[name="provider_id"]');
                    if (select) {
                        const options = select.querySelectorAll('option');
                        const validOptions = select.querySelectorAll('option[value!=""]');
                        
                        debugHtml += `<div class="mb-2">`;
                        debugHtml += `<strong>${modalId}:</strong> `;
                        debugHtml += `${options.length} opciones total, `;
                        debugHtml += `${validOptions.length} proveedores válidos`;
                        
                        if (validOptions.length > 0) {
                            debugHtml += ` <span class="badge bg-success">✅ OK</span>`;
                        } else {
                            debugHtml += ` <span class="badge bg-danger">❌ SIN PROVEEDORES</span>`;
                        }
                        debugHtml += `</div>`;
                    }
                }
            });
            
            // Mostrar info de la página
            debugHtml += '<hr><h6>🔧 Variables PHP:</h6>';
            debugHtml += `<div>Company ID: <?php echo $company_id; ?></div>`;
            debugHtml += `<div>Business ID: <?php echo $business_id; ?></div>`;
            <?php if (isset($modal_providers)): ?>
            debugHtml += `<div>Proveedores encontrados: <?php echo count($modal_providers); ?></div>`;
            <?php else: ?>
            debugHtml += `<div style="color: red;">Variable $modal_providers no definida</div>`;
            <?php endif; ?>
            
            document.getElementById('debug-info').innerHTML = debugHtml;
        }
    </script>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary">← Volver al módulo</a>
        <a href="debug_modals.php" class="btn btn-outline-info">🔍 Debug modales</a>
    </div>
</body>
</html>
