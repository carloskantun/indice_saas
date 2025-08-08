/**
 * EXPENSES.JS - VERSIÓN SIMPLIFICADA PARA PRUEBAS
 */

console.log('🚀 Expenses.js SIMPLIFICADO - Iniciando carga...');

$(document).ready(function() {
    console.log('✅ jQuery cargado correctamente');
    console.log('📊 Inicializando módulo de gastos SIMPLIFICADO...');
    
    try {
        // Solo vincular eventos básicos
        bindBasicEvents();
        console.log('✅ Eventos básicos vinculados');
        
        // Configurar modales básicos
        setupBasicModals();
        console.log('✅ Modales configurados');
        
        console.log('✅ Módulo SIMPLIFICADO inicializado correctamente');
    } catch (error) {
        console.error('❌ Error inicializando gastos SIMPLIFICADO:', error);
    }
});

function bindBasicEvents() {
    console.log('🔗 Binding eventos básicos SIMPLIFICADO...');
    
    // Formulario de gastos
    $(document).off('submit', '#expenseForm').on('submit', '#expenseForm', function(e) {
        e.preventDefault();
        console.log('📝 Formulario de gastos enviado - SIMPLIFICADO');
        handleExpenseSubmit.call(this, e);
    });
    
    // Formulario de órdenes de compra
    $(document).off('submit', '#orderForm').on('submit', '#orderForm', function(e) {
        e.preventDefault();
        console.log('📦 Formulario de orden de compra enviado - SIMPLIFICADO');
        handleOrderSubmit.call(this, e);
    });
    
    // Formulario de proveedores
    $(document).off('submit', '#providerForm').on('submit', '#providerForm', function(e) {
        e.preventDefault();
        console.log('🏢 Formulario de proveedor enviado - SIMPLIFICADO');
        handleProviderSubmit.call(this, e);
    });
    
    // Formulario de pagos (modal-formulario-general)
    $(document).off('submit', '#paymentForm').on('submit', '#paymentForm', function(e) {
        e.preventDefault();
        console.log('💰 Formulario de pago enviado - SIMPLIFICADO');
        handlePaymentSubmit.call(this, e);
    });
    
    // Formulario de edición de gastos
    $(document).off('submit', '#editExpenseForm').on('submit', '#editExpenseForm', function(e) {
        e.preventDefault();
        console.log('✏️ Formulario de edición enviado - SIMPLIFICADO');
        handleEditSubmit.call(this, e);
    });
    
    // === BOTONES DE ACCIÓN EN TABLA ===
    
    // Botón Ver detalles
    $(document).off('click', '.btn-view').on('click', '.btn-view', function() {
        const expenseId = $(this).data('id');
        console.log('👁️ Ver detalles del gasto:', expenseId);
        viewExpense(expenseId);
    });
    
    // Botón Registrar pago
    $(document).off('click', '.btn-pay').on('click', '.btn-pay', function() {
        const expenseId = $(this).data('id');
        console.log('💰 Registrar pago para gasto:', expenseId);
        openPaymentModal(expenseId);
    });
    
    // Botón Editar
    $(document).off('click', '.btn-edit').on('click', '.btn-edit', function() {
        const expenseId = $(this).data('id');
        console.log('✏️ Editar gasto:', expenseId);
        editExpense(expenseId);
    });
    
    // Botón Eliminar
    $(document).off('click', '.btn-delete').on('click', '.btn-delete', function() {
        const expenseId = $(this).data('id');
        console.log('🗑️ Eliminar gasto:', expenseId);
        deleteExpense(expenseId);
    });
    
    // Botón PDF individual
    $(document).off('click', '.btn-pdf').on('click', '.btn-pdf', function() {
        const expenseId = $(this).data('id');
        console.log('📄 Generar PDF individual:', expenseId);
        generateIndividualPDF(expenseId);
    });
    
    // === BOTONES DE EXPORTACIÓN ===
    
    // Botón Exportar PDF
    $(document).off('click', '#btn-exportar-pdf').on('click', '#btn-exportar-pdf', function() {
        console.log('📄 Exportando a PDF...');
        exportToPDF();
    });
    
    // Botón Exportar CSV
    $(document).off('click', '#btn-exportar-csv').on('click', '#btn-exportar-csv', function() {
        console.log('📊 Exportando a CSV...');
        exportToCSV();
    });
    
    // === BOTÓN LIMPIAR FILTROS ===
    
    // Botón limpiar filtros
    $(document).off('click', '#btnClearFilters').on('click', '#btnClearFilters', function() {
        console.log('🧹 Limpiando filtros...');
        limpiarFiltros();
    });
    
    console.log('✅ Eventos básicos vinculados correctamente - SIMPLIFICADO');
}

function setupBasicModals() {
    console.log('🎯 Configurando modales básicos...');
    
    $('.modal').on('shown.bs.modal', function() {
        const modalId = this.id;
        const $modal = $(this);
        console.log('✨ Modal abierto:', modalId);
        
        // Configurar Select2 básico
        $modal.find('select').each(function() {
            const $select = $(this);
            if (!$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Seleccionar...'
                });
                console.log('✅ Select2 configurado en modal');
            }
        });
        
        // Configurar drag & drop para archivos
        setupFileDragDrop($modal);
    });
}

function setupFileDragDrop($modal) {
    console.log('📁 Configurando drag & drop para archivos...');
    
    const fileInputs = $modal.find('input[type="file"]');
    
    fileInputs.each(function() {
        const $input = $(this);
        const $modalContent = $modal.find('.modal-content');
        
        // Eventos de drag & drop
        $modalContent.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });
        
        $modalContent.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });
        
        $modalContent.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                // Crear nuevo DataTransfer para combinar archivos
                const dt = new DataTransfer();
                
                // Agregar archivos existentes
                for (let i = 0; i < $input[0].files.length; i++) {
                    dt.items.add($input[0].files[i]);
                }
                
                // Agregar nuevos archivos
                for (let i = 0; i < files.length; i++) {
                    dt.items.add(files[i]);
                }
                
                $input[0].files = dt.files;
                updateFilePreview($input);
                console.log('📁 Archivos añadidos via drag & drop:', files.length);
            }
        });
        
        // Preview al seleccionar archivos
        $input.on('change', function() {
            updateFilePreview($(this));
        });
    });
}

function updateFilePreview($input) {
    const files = $input[0].files;
    const $preview = $input.siblings('#file-preview');
    
    if (!$preview.length) {
        $input.after('<div id="file-preview" class="mt-2"></div>');
    }
    
    let html = '';
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const size = (file.size / 1024 / 1024).toFixed(2);
        const icon = file.type.includes('image') ? 'fa-image' : 'fa-file-pdf';
        
        html += `
            <div class="d-flex align-items-center mb-1 p-2 border rounded">
                <i class="fas ${icon} me-2 text-primary"></i>
                <span class="flex-grow-1">${file.name}</span>
                <small class="text-muted">${size} MB</small>
            </div>
        `;
    }
    
    $('#file-preview').html(html);
}

function handleEditSubmit(e) {
    console.log('✏️ Procesando edición de gasto - SIMPLIFICADO...');
    
    const formData = new FormData(this);
    formData.append('action', 'edit_expense');
    
    // Log de datos del formulario
    console.log('📋 Datos de edición:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            console.log('📤 Enviando edición...');
            $('.btn-submit').prop('disabled', true).text('Actualizando...');
        }
    })
    .done(function(response) {
        console.log('✅ Respuesta edición:', response);
        try {
            const result = JSON.parse(response);
            if (result.success) {
                alert('✅ Gasto actualizado exitosamente: ' + result.message);
                $('#editExpenseModal').modal('hide');
                location.reload();
            } else {
                alert('❌ Error: ' + (result.error || 'Error al actualizar gasto'));
            }
        } catch (parseError) {
            console.error('❌ Error parseando respuesta edición:', parseError);
            console.log('📝 Respuesta raw:', response);
            alert('❌ Error en el formato de respuesta del servidor');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error en solicitud edición:', xhr);
        console.log('📝 Status:', xhr.status);
        console.log('📝 Response text:', xhr.responseText);
        alert('❌ Error del servidor: ' + xhr.status);
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Actualizar');
    });
}

// Funciones de manejo de formularios (copiadas del archivo original)
function handleExpenseSubmit(e) {
    console.log('💰 Procesando nuevo gasto - SIMPLIFICADO...');
    
    const formData = new FormData(this);
    formData.append('action', 'create_expense');
    
    // Log de datos del formulario
    console.log('📋 Datos del formulario:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            console.log('📤 Enviando solicitud...');
            $('.btn-submit').prop('disabled', true).text('Guardando...');
        }
    })
    .done(function(response) {
        console.log('✅ Respuesta recibida:', response);
        try {
            const result = JSON.parse(response);
            if (result.success) {
                alert('✅ Gasto creado exitosamente: ' + result.message);
                $('#expenseModal').modal('hide');
                location.reload();
            } else {
                alert('❌ Error: ' + (result.error || 'Error al crear gasto'));
            }
        } catch (parseError) {
            console.error('❌ Error parseando respuesta:', parseError);
            console.log('📝 Respuesta raw:', response);
            alert('❌ Error en el formato de respuesta del servidor');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error en solicitud:', xhr);
        console.log('📝 Status:', xhr.status);
        console.log('📝 Response text:', xhr.responseText);
        alert('❌ Error del servidor: ' + xhr.status);
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

function handleOrderSubmit(e) {
    console.log('📋 Procesando nueva orden - SIMPLIFICADO...');
    
    const formData = new FormData(this);
    formData.append('action', 'create_order');
    
    console.log('📋 Datos del formulario:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            console.log('📤 Enviando orden...');
            $('.btn-submit').prop('disabled', true).text('Guardando...');
        }
    })
    .done(function(response) {
        console.log('✅ Respuesta orden:', response);
        try {
            const result = JSON.parse(response);
            if (result.success) {
                alert('✅ Orden creada exitosamente: ' + result.message);
                $('#orderModal').modal('hide');
                location.reload();
            } else {
                alert('❌ Error: ' + (result.error || 'Error al crear orden'));
            }
        } catch (parseError) {
            console.error('❌ Error parseando respuesta orden:', parseError);
            alert('❌ Error en el formato de respuesta del servidor');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error en solicitud orden:', xhr);
        alert('❌ Error del servidor: ' + xhr.status);
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Crear Orden');
    });
}

function handleProviderSubmit(e) {
    console.log('🏢 Procesando proveedor - SIMPLIFICADO...');
    
    const formData = new FormData(this);
    formData.append('action', 'create_provider');
    
    console.log('📋 Datos del formulario:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            console.log('📤 Enviando proveedor...');
            $('.btn-submit').prop('disabled', true).text('Guardando...');
        }
    })
    .done(function(response) {
        console.log('✅ Respuesta proveedor:', response);
        try {
            const result = JSON.parse(response);
            if (result.success) {
                alert('✅ Proveedor creado exitosamente: ' + result.message);
                $('#providerModal').modal('hide');
                location.reload();
            } else {
                alert('❌ Error: ' + (result.error || 'Error al crear proveedor'));
            }
        } catch (parseError) {
            console.error('❌ Error parseando respuesta proveedor:', parseError);
            alert('❌ Error en el formato de respuesta del servidor');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error en solicitud proveedor:', xhr);
        alert('❌ Error del servidor: ' + xhr.status);
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

// Función de diagnóstico
window.diagnosticarEventos = function() {
    console.log('🔍 DIAGNÓSTICO DE EVENTOS - SIMPLIFICADO:');
    
    const forms = ['#expenseForm', '#orderForm', '#providerForm'];
    forms.forEach(formId => {
        const form = $(formId);
        if (form.length > 0) {
            console.log(`✅ Formulario ${formId} encontrado`);
            
            const events = $._data(form[0], 'events');
            if (events && events.submit) {
                console.log(`✅ Evento submit vinculado a ${formId}`);
            } else {
                console.log(`❌ Evento submit NO vinculado a ${formId}`);
            }
        } else {
            console.log(`❌ Formulario ${formId} NO encontrado`);
        }
    });
    
    console.log('🏁 Diagnóstico SIMPLIFICADO completado');
};

// ============================================
// FUNCIONES DE BOTONES DE ACCIÓN
// ============================================

function viewExpense(expenseId) {
    console.log('👁️ Viendo gasto:', expenseId);
    
    $.ajax({
        url: 'controller.php',
        method: 'GET',
        data: { action: 'get_expense', expense_id: expenseId }
    })
    .done(function(response) {
        console.log('📊 Respuesta ver gasto:', response);
        
        let result;
        try {
            result = typeof response === 'string' ? JSON.parse(response) : response;
        } catch (e) {
            console.error('❌ Error parseando respuesta:', e);
            alert('Error en formato de respuesta del servidor');
            return;
        }
        
        if (result.success && result.data) {
            const expense = result.data;
            
            // Crear contenido simple para el modal
            const content = `
                <div class="row">
                    <div class="col-12">
                        <h6>Información del Gasto</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Folio:</strong></td><td>${expense.folio || 'N/A'}</td></tr>
                            <tr><td><strong>Proveedor:</strong></td><td>${expense.provider_name || 'Sin proveedor'}</td></tr>
                            <tr><td><strong>Monto:</strong></td><td>$${parseFloat(expense.amount || 0).toLocaleString('es-MX')}</td></tr>
                            <tr><td><strong>Fecha:</strong></td><td>${expense.payment_date}</td></tr>
                            <tr><td><strong>Concepto:</strong></td><td>${expense.concept}</td></tr>
                        </table>
                    </div>
                </div>
            `;
            
            // Crear modal dinámico
            const modalHtml = `
                <div class="modal fade" id="viewExpenseModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalles del Gasto</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">${content}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal anterior si existe
            $('#viewExpenseModal').remove();
            
            // Agregar nuevo modal y mostrarlo
            $('body').append(modalHtml);
            $('#viewExpenseModal').modal('show');
            
        } else {
            alert('Error al cargar datos del gasto: ' + (result.error || 'Datos no encontrados'));
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error AJAX:', xhr);
        alert('Error del servidor al cargar gasto');
    });
}

function editExpense(expenseId) {
    console.log('✏️ Editando gasto:', expenseId);
    
    // Cargar datos del gasto para edición
    $.ajax({
        url: 'controller.php',
        method: 'GET',
        data: { action: 'get_expense', expense_id: expenseId }
    })
    .done(function(response) {
        console.log('📊 Datos para edición:', response);
        
        let result;
        try {
            result = typeof response === 'string' ? JSON.parse(response) : response;
        } catch (e) {
            console.error('❌ Error parseando respuesta:', e);
            alert('Error en respuesta del servidor');
            return;
        }
        
        if (result.success && result.data) {
            const expense = result.data;
            
            // Llenar el formulario de edición con los datos existentes
            $('#edit_expense_id').val(expense.id);
            $('#edit_provider_id').val(expense.provider_id).trigger('change');
            $('#edit_amount').val(expense.amount);
            $('#edit_payment_date').val(expense.payment_date);
            $('#edit_expense_type').val(expense.expense_type);
            $('#edit_purchase_type').val(expense.purchase_type);
            $('#edit_payment_method').val(expense.payment_method);
            $('#edit_bank_account').val(expense.bank_account);
            $('#edit_concept').val(expense.concept);
            $('#edit_order_folio').val(expense.order_folio);
            
            // Mostrar el modal de edición
            $('#editExpenseModal').modal('show');
            
            console.log('✅ Modal de edición configurado');
        } else {
            alert('Error al cargar datos del gasto: ' + (result.error || 'Datos no encontrados'));
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error AJAX editar:', xhr);
        alert('Error del servidor al cargar datos para edición');
    });
}

function deleteExpense(expenseId) {
    console.log('🗑️ Eliminando gasto:', expenseId);
    
    if (confirm('¿Está seguro de eliminar este gasto?')) {
        $.ajax({
            url: 'controller.php',
            method: 'POST',
            data: { action: 'delete_expense', expense_id: expenseId }
        })
        .done(function(response) {
            console.log('✅ Respuesta eliminación:', response);
            
            let result;
            try {
                result = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error('❌ Error parseando respuesta:', e);
                alert('Error en respuesta del servidor');
                return;
            }
            
            if (result.success) {
                alert('✅ Gasto eliminado correctamente');
                location.reload();
            } else {
                alert('❌ Error: ' + (result.error || 'Error al eliminar gasto'));
            }
        })
        .fail(function(xhr) {
            console.error('❌ Error AJAX eliminar:', xhr);
            alert('❌ Error del servidor al eliminar');
        });
    }
}

function openPaymentModal(expenseId) {
    console.log('💰 Abriendo modal de pago para gasto:', expenseId);
    
    // Limpiar formulario anterior
    $('#paymentForm')[0].reset();
    $('#payment_expense_id').val(expenseId);
    
    // Establecer fecha actual
    const today = new Date().toISOString().split('T')[0];
    $('#payment_date').val(today);
    
    // Cargar datos del gasto
    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: { action: 'get_expense', expense_id: expenseId }
    })
    .done(function(response) {
        let result;
        try {
            result = typeof response === 'string' ? JSON.parse(response) : response;
        } catch (e) {
            console.error('❌ Error parseando respuesta:', e);
            alert('Error en respuesta del servidor');
            return;
        }
        
        if (result.success && result.data) {
            const expense = result.data;
            
            // Calcular saldo pendiente
            const totalAmount = parseFloat(expense.amount || 0);
            const paidAmount = parseFloat(expense.paid_amount || 0);
            const pending = totalAmount - paidAmount;
            
            // Actualizar información en el modal
            $('#pendingAmountSpan').text('$' + new Intl.NumberFormat('es-MX').format(pending));
            $('#payment_amount').attr('max', pending);
            $('#payment_amount').attr('placeholder', `Máximo: $${new Intl.NumberFormat('es-MX').format(pending)}`);
            
            console.log('✅ Modal de pago configurado - Pendiente: $' + pending);
        } else {
            alert('Error cargando datos del gasto');
            return;
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error cargando datos:', xhr);
        alert('Error del servidor al cargar datos');
        return;
    });
    
    // Abrir el modal
    $('#paymentModal').modal('show');
}

// ============================================
// FUNCIONES DE EXPORTACIÓN
// ============================================

function exportToPDF() {
    console.log('📄 Iniciando exportación a PDF...');
    
    // Verificar si existe el archivo de exportación
    window.open('export-pdf.php', '_blank');
    console.log('✅ Solicitud de PDF enviada');
}

function exportToCSV() {
    console.log('📊 Iniciando exportación a CSV...');
    
    // Verificar si existe el archivo de exportación
    window.open('export-csv.php', '_blank');
    console.log('✅ Solicitud de CSV enviada');
}

// ============================================
// FUNCIONES DE FILTROS
// ============================================

function limpiarFiltros() {
    console.log('🧹 Limpiando todos los filtros...');
    
    // Limpiar campos de fecha
    $('input[name="fecha_inicio"]').val('');
    $('input[name="fecha_fin"]').val('');
    
    // Limpiar selects
    $('select[name="proveedor_id"]').val('').trigger('change');
    $('select[name="estatus"]').val('').trigger('change');
    $('select[name="origen"]').val('').trigger('change');
    
    // Limpiar filtros rápidos (botones de estado)
    $('.quick-filter').removeClass('active');
    
    // Recargar la página para aplicar filtros limpios
    location.reload();
    
    console.log('✅ Filtros limpiados');
}

function generateIndividualPDF(expenseId) {
    console.log('📄 Generando PDF individual para gasto:', expenseId);
    
    // Abrir PDF en nueva ventana
    const url = `individual-pdf.php?expense_id=${expenseId}`;
    window.open(url, '_blank');
    
    console.log('✅ Solicitud de PDF individual enviada');
}

console.log('✅ Expenses.js SIMPLIFICADO - Carga completada');
