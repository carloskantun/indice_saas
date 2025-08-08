<?php
/**
 * Test simple para verificar modales
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
    <title>Test Simple - Modales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 Test Simple - Modales</h1>
        
        <div class="alert alert-info">
            <strong>Company ID:</strong> <?php echo $company_id ?? 'NULL'; ?>
        </div>
        
        <!-- Botones para abrir modales -->
        <div class="mb-4">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#expenseModal">
                Abrir Modal Gasto
            </button>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#orderModal">
                Abrir Modal Orden
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editExpenseModal">
                Abrir Modal Edición
            </button>
        </div>
        
        <!-- Test directo de consulta de proveedores -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>📊 Consulta Directa de Proveedores</h5>
            </div>
            <div class="card-body">
                <?php
                if ($company_id) {
                    $db = getDB();
                    $sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$company_id]);
                    $providers = $stmt->fetchAll();
                    
                    echo "<p><strong>Proveedores encontrados:</strong> " . count($providers) . "</p>";
                    
                    if (count($providers) > 0) {
                        echo "<ul>";
                        foreach ($providers as $p) {
                            echo "<li>ID: {$p['id']} - {$p['name']}</li>";
                        }
                        echo "</ul>";
                        
                        echo "<h6>HTML de ejemplo para select:</h6>";
                        echo "<select class='form-select'>";
                        echo "<option value=''>Sin proveedor</option>";
                        foreach ($providers as $p) {
                            echo "<option value='{$p['id']}'>{$p['name']}</option>";
                        }
                        echo "</select>";
                    } else {
                        echo "<p style='color: red;'>❌ No se encontraron proveedores activos</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ No hay company_id</p>";
                }
                ?>
            </div>
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
    
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                language: 'es',
                placeholder: 'Seleccionar...',
                allowClear: true,
                width: '100%'
            });
            
            // Debug cuando se abren los modales
            $('.modal').on('shown.bs.modal', function() {
                const modalId = this.id;
                console.log('Modal abierto:', modalId);
                
                // Buscar select de proveedores
                const select = $(this).find('select[name="provider_id"]');
                if (select.length > 0) {
                    const options = select.find('option');
                    console.log(`Select encontrado en ${modalId}:`, options.length, 'opciones');
                    
                    // Mostrar todas las opciones
                    options.each(function(i, option) {
                        console.log(`  Opción ${i}:`, $(option).val(), '-', $(option).text());
                    });
                    
                    // Verificar si hay opciones con valor
                    const validOptions = select.find('option[value!=""]');
                    if (validOptions.length === 0) {
                        console.log('⚠️ No hay opciones válidas de proveedores');
                    } else {
                        console.log('✅', validOptions.length, 'proveedores disponibles');
                    }
                } else {
                    console.log('❌ No se encontró select de proveedores en', modalId);
                }
            });
        });
    </script>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary">← Volver</a>
        <a href="direct_debug.php" class="btn btn-outline-info">Debug Directo</a>
    </div>
</body>
</html>
