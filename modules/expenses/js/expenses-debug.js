/**
 * EXPENSES.JS - VERSIÓN DEBUG SIMPLIFICADA
 * Versión mínima para diagnosticar problemas
 */

console.log('🚀 Expenses.js - Iniciando carga...');

$(document).ready(function() {
    console.log('✅ jQuery cargado correctamente');
    console.log('📊 Inicializando módulo de gastos...');
    
    try {
        // 1. Inicializar Select2
        if ($.fn.select2) {
            $('.select2').select2({
                language: 'es',
                placeholder: 'Seleccionar...',
                allowClear: true,
                width: '100%'
            });
            console.log('✅ Select2 inicializado');
        } else {
            console.error('❌ Select2 no está disponible');
        }

        // 2. Verificar Bootstrap modals
        if ($.fn.modal) {
            console.log('✅ Bootstrap modals disponibles');
        } else {
            console.error('❌ Bootstrap modals no disponibles');
        }

        // 3. Bind eventos básicos
        bindBasicEvents();
        
        // 4. Configurar eventos específicos para modales
        setupModalEvents();
        
        // 5. Inicializar funciones básicas
        initializeColumnVisibility();
        initializeQuickFilters();
        initializeSortableColumns();
        initializeMultipleSelection();
        calcularTotales();
        
        console.log('✅ Módulo de gastos inicializado correctamente');
    } catch (error) {
        console.error('❌ Error inicializando gastos:', error);
    }
});

function bindBasicEvents() {
    console.log('🔗 Binding eventos básicos...');
    
    // Formulario de gastos
    $(document).off('submit', '#expenseForm').on('submit', '#expenseForm', function(e) {
        e.preventDefault();
        console.log('📝 Formulario de gastos enviado');
        handleExpenseSubmit.call(this, e);
    });
    
    // Formulario de órdenes de compra
    $(document).off('submit', '#orderForm').on('submit', '#orderForm', function(e) {
        e.preventDefault();
        console.log('📦 Formulario de orden de compra enviado');
        handleOrderSubmit.call(this, e);
    });
    
    // Formulario de proveedores
    $(document).off('submit', '#providerForm').on('submit', '#providerForm', function(e) {
        e.preventDefault();
        console.log('🏢 Formulario de proveedor enviado');
        handleProviderSubmit.call(this, e);
    });
    
    // Formulario de pagos (modal-formulario-general)
    $(document).off('submit', '#paymentForm').on('submit', '#paymentForm', function(e) {
        e.preventDefault();
        console.log('💰 Formulario de pago enviado');
        handlePaymentSubmit.call(this, e);
    });
    
    // === BOTONES DE ACCIÓN EN TABLA ===
    
    // Botón Ver detalles
    $(document).off('click', '.btn-view').on('click', '.btn-view', function() {
        const expenseId = $(this).data('id');
        console.log('👁️ Ver detalles del gasto:', expenseId);
        viewExpense(expenseId);
    });
    
    // Botón Registrar pago (modal-formulario-general)
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
    
    console.log('✅ Eventos básicos vinculados correctamente');
}

function setupModalEvents() {
    console.log('🎯 Configurando eventos de modales...');
    
    // Evitar que el modal se cierre al interactuar con Select2
    $(document).on('click', '.select2-container', function(e) {
        e.stopPropagation();
        console.log('🛡️ Click en Select2 container - evento detenido');
    });
    
    $(document).on('click', '.select2-dropdown', function(e) {
        e.stopPropagation();
        console.log('🛡️ Click en Select2 dropdown - evento detenido');
    });
    
    // Eventos principales de modales
    $('.modal').on('show.bs.modal', function() {
        const modalId = this.id;
        console.log('🎯 Abriendo modal:', modalId);
    });
    
    $('.modal').on('shown.bs.modal', function() {
        const modalId = this.id;
        const $modal = $(this);
        console.log('✨ Modal abierto:', modalId);
        
        // Configurar todos los selects en este modal
        $modal.find('select').each(function() {
            const $select = $(this);
            const selectName = $select.attr('name') || 'unknown';
            
            console.log(`🔄 Configurando select: ${selectName}`);
            
            // Si ya tiene Select2, destruirlo
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
                console.log(`   └─ Select2 destruido para ${selectName}`);
            }
            
            // Inicializar Select2 con configuración para modales
            try {
                $select.select2({
                    language: 'es',
                    placeholder: 'Seleccionar...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $modal,
                    escapeMarkup: function(markup) {
                        return markup;
                    }
                });
                
                console.log(`   └─ ✅ Select2 inicializado para ${selectName}`);
                
                // Contar opciones
                const options = $select.find('option');
                const validOptions = $select.find('option[value!=""]');
                console.log(`   └─ 📊 ${options.length} opciones, ${validOptions.length} válidas`);
                
            } catch (error) {
                console.error(`   └─ ❌ Error inicializando ${selectName}:`, error);
            }
        });
        
        console.log('✅ Modal configurado completamente:', modalId);
    });
    
    $('.modal').on('hidden.bs.modal', function() {
        const modalId = this.id;
        console.log('🚪 Modal cerrado:', modalId);
    });
    
    // Funcionalidad específica para órdenes recurrentes
    $(document).on('change', '#order_expense_type', function() {
        const camposRecurrente = document.getElementById('campos_recurrente');
        if (camposRecurrente) {
            if (this.value === 'Recurrente') {
                camposRecurrente.style.display = 'block';
                console.log('📅 Campos recurrentes mostrados');
            } else {
                camposRecurrente.style.display = 'none';
                console.log('📅 Campos recurrentes ocultos');
            }
        }
    });
}

function initializeColumnVisibility() {
    console.log('👁️ Inicializando visibilidad de columnas...');
    
    $('.col-toggle').on('change', function() {
        const column = $(this).data('col');
        const isVisible = $(this).is(':checked');
        
        console.log('👁️ Toggle columna:', column, isVisible ? 'visible' : 'oculta');
        
        if (isVisible) {
            $(`.col-${column}`).show();
        } else {
            $(`.col-${column}`).hide();
        }
        
        // Guardar preferencia
        localStorage.setItem(`expense_col_${column}`, isVisible);
    });
    
    // Cargar preferencias guardadas
    $('.col-toggle').each(function() {
        const column = $(this).data('col');
        const saved = localStorage.getItem(`expense_col_${column}`);
        
        if (saved !== null) {
            const isVisible = saved === 'true';
            $(this).prop('checked', isVisible);
            
            if (isVisible) {
                $(`.col-${column}`).show();
            } else {
                $(`.col-${column}`).hide();
            }
        }
    });
    
    console.log('✅ Visibilidad de columnas configurada');
}

function initializeQuickFilters() {
    console.log('🔍 Inicializando filtros rápidos...');
    
    $('.quick-filter').on('click', function() {
        const origen = $(this).data('origen');
        const estatus = $(this).data('estatus');
        
        console.log('🔍 Filtro rápido:', { origen, estatus });
        
        $('select[name="origen"]').val(origen);
        $('select[name="estatus"]').val(estatus);
        
        $('#filterForm').submit();
    });
    
    $('#btnClearFilters').on('click', function() {
        console.log('🧹 Limpiar filtros');
        $('#filterForm')[0].reset();
        $('#filterForm').submit();
    });
    
    console.log('✅ Filtros rápidos configurados');
}

// Funciones de manejo de formularios
function handleExpenseSubmit(e) {
    console.log('💰 Procesando nuevo gasto...');
    
    const formData = new FormData(this);
    formData.append('action', 'create_expense');
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('.btn-submit').prop('disabled', true).text('Guardando...');
        }
    })
    .done(function(response) {
        console.log('✅ Gasto creado:', response);
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#expenseModal').modal('hide');
            location.reload();
        } else {
            showAlert(result.error || 'Error al crear gasto', 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error creando gasto:', xhr.responseText);
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

function handleOrderSubmit(e) {
    console.log('📋 Procesando nueva orden...');
    
    const formData = new FormData(this);
    formData.append('action', 'create_order');
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('.btn-submit').prop('disabled', true).text('Guardando...');
        }
    })
    .done(function(response) {
        console.log('✅ Orden creada:', response);
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#orderModal').modal('hide');
            location.reload();
        } else {
            showAlert(result.error || 'Error al crear orden', 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error creando orden:', xhr.responseText);
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

function handleProviderSubmit(e) {
    console.log('🏢 Procesando proveedor...');
    
    const formData = new FormData(this);
    formData.append('action', 'create_provider');
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('.btn-submit').prop('disabled', true).text('Guardando...');
        }
    })
    .done(function(response) {
        console.log('✅ Proveedor creado:', response);
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#providerModal').modal('hide');
            location.reload();
        } else {
            showAlert(result.error || 'Error al crear proveedor', 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error creando proveedor:', xhr.responseText);
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

// ============================================
// FUNCIONES DEL MODAL-FORMULARIO-GENERAL (PAGOS)
// ============================================

function openPaymentModal(expenseId) {
    console.log('💰 Abriendo modal de pago para gasto:', expenseId);
    
    // Limpiar formulario anterior
    $('#paymentForm')[0].reset();
    $('#payment_expense_id').val(expenseId);
    
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split('T')[0];
    $('#payment_date').val(today);
    
    // Cargar datos del gasto para mostrar en el modal
    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: { action: 'get_expense', expense_id: expenseId }
    })
    .done(function(response) {
        console.log('📊 Respuesta cruda:', response);
        
        let result;
        try {
            result = typeof response === 'string' ? JSON.parse(response) : response;
        } catch (e) {
            console.error('❌ Error parseando respuesta:', e);
            showAlert('Error en formato de respuesta del servidor', 'danger');
            return;
        }
        
        if (result.success && result.data) {
            const expense = result.data;
            console.log('📊 Datos del gasto:', expense);
            
            // Calcular el saldo pendiente
            const totalAmount = parseFloat(expense.amount || 0);
            const paidAmount = parseFloat(expense.paid_amount || 0);
            const pending = totalAmount - paidAmount;
            
            // Actualizar información en el modal
            $('#pendingAmountSpan').text('$' + new Intl.NumberFormat('es-MX').format(pending));
            
            // Establecer el monto máximo que se puede pagar
            $('#payment_amount').attr('max', pending);
            $('#payment_amount').attr('placeholder', `Máximo: $${new Intl.NumberFormat('es-MX').format(pending)}`);
            
            console.log('✅ Modal de pago configurado - Pendiente: $' + pending);
        } else {
            console.error('❌ Error cargando datos del gasto:', response.error);
            showAlert('Error cargando datos del gasto', 'danger');
            return;
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error en solicitud de datos del gasto:', xhr);
        showAlert('Error del servidor al cargar datos', 'danger');
        return;
    });
    
    // Abrir el modal
    $('#paymentModal').modal('show');
}

function handlePaymentSubmit(e) {
    console.log('💰 Procesando pago/abono...');
    
    const formData = new FormData(this);
    formData.append('action', 'register_payment');
    
    // Validaciones básicas
    const amount = parseFloat(formData.get('amount'));
    const maxAmount = parseFloat($('#payment_amount').attr('max'));
    
    if (!amount || amount <= 0) {
        showAlert('El monto debe ser mayor a 0', 'warning');
        return;
    }
    
    if (amount > maxAmount) {
        showAlert(`El monto no puede exceder $${new Intl.NumberFormat('es-MX').format(maxAmount)}`, 'warning');
        return;
    }
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('.btn-submit').prop('disabled', true).text('Registrando...');
        }
    })
    .done(function(response) {
        console.log('✅ Pago registrado:', response);
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#paymentModal').modal('hide');
            location.reload(); // Recargar para mostrar el pago actualizado
        } else {
            showAlert(result.error || 'Error al registrar pago', 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error registrando pago:', xhr.responseText);
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Registrar Pago');
    });
}

function updateField(expenseId, field, value) {
    console.log('🔄 Actualizando campo:', { expenseId, field, value });
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: {
            action: 'update_field',
            expense_id: expenseId,
            field: field,
            value: value
        }
    })
    .done(function(response) {
        console.log('✅ Campo actualizado:', response);
        const result = JSON.parse(response);
        if (result.success) {
            showAlert('Campo actualizado', 'success');
        } else {
            showAlert(result.error || 'Error al actualizar', 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error actualizando campo:', xhr.responseText);
        showAlert('Error del servidor', 'danger');
    });
}

function editExpense(expenseId) {
    console.log('✏️ Editando gasto:', expenseId);
    
    // Cargar datos del gasto
    $.ajax({
        url: 'controller.php',
        method: 'GET',
        data: { action: 'get_expense', expense_id: expenseId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const expense = response.expense;
                
                // Llenar el modal de edición
                $('#edit_expense_id').val(expense.id);
                $('#edit_provider_id').val(expense.provider_id).trigger('change');
                $('#edit_amount').val(expense.amount);
                $('#edit_payment_date').val(expense.payment_date);
                $('#edit_expense_type').val(expense.expense_type);
                $('#edit_purchase_type').val(expense.purchase_type);
                $('#edit_payment_method').val(expense.payment_method);
                $('#edit_bank_account').val(expense.bank_account);
                $('#edit_origin').val(expense.origin);
                $('#edit_concept').val(expense.concept);
                $('#edit_order_folio').val(expense.order_folio);
                
                // Cargar proveedores en el modal de edición
                loadProvidersInModal('#edit_provider_id');
                
                // Mostrar modal
                $('#editExpenseModal').modal('show');
            } else {
                showAlert('Error al cargar datos del gasto: ' + response.error, 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            showAlert('Error del servidor al cargar gasto', 'danger');
        }
    });
}

function viewExpense(expenseId) {
    console.log('👁️ Viendo gasto:', expenseId);
    
    // Cargar datos del gasto para vista detallada
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
            console.error('❌ Error parseando respuesta ver gasto:', e);
            showAlert('Error en formato de respuesta del servidor', 'danger');
            return;
        }
        
        if (result.success && result.data) {
            const expense = result.data;
            
            // Crear contenido del modal
            const modalContent = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información Básica</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Folio:</strong></td><td>${expense.folio || 'N/A'}</td></tr>
                            <tr><td><strong>Proveedor:</strong></td><td>${expense.provider_name || 'Sin proveedor'}</td></tr>
                            <tr><td><strong>Monto:</strong></td><td>$${parseFloat(expense.amount || 0).toLocaleString('es-MX')}</td></tr>
                            <tr><td><strong>Fecha de Pago:</strong></td><td>${expense.payment_date}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge bg-${expense.status === 'Pagado' ? 'success' : 'warning'}">${expense.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Detalles</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Tipo:</strong></td><td>${expense.expense_type}</td></tr>
                            <tr><td><strong>Tipo Compra:</strong></td><td>${expense.purchase_type || 'N/A'}</td></tr>
                            <tr><td><strong>Método Pago:</strong></td><td>${expense.payment_method}</td></tr>
                            <tr><td><strong>Cuenta:</strong></td><td>${expense.bank_account || 'N/A'}</td></tr>
                            <tr><td><strong>Origen:</strong></td><td>${expense.origin}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Concepto</h6>
                        <p class="border rounded p-2">${expense.concept}</p>
                    </div>
                </div>
                ${expense.order_folio ? `
                <div class="row mt-2">
                    <div class="col-12">
                        <h6>Folio de Orden</h6>
                        <p><strong>${expense.order_folio}</strong></p>
                    </div>
                </div>` : ''}
            `;
            
            // Insertar contenido en el modal
            $('#viewExpenseModal .modal-body').html(modalContent);
            $('#viewExpenseModal').modal('show');
            
        } else {
            console.error('❌ Error:', result.error || 'Respuesta sin datos');
            showAlert('Error al cargar datos del gasto: ' + (result.error || 'Datos no encontrados'), 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error AJAX ver gasto:', xhr);
        showAlert('Error del servidor al cargar gasto', 'danger');
    });
}

function saveExpense() {
    console.log('💾 Guardando gasto...');
    
    const formData = new FormData(document.getElementById('expenseForm'));
    formData.append('action', 'create_expense');
    
    $.ajax({
        url: 'controller.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Gasto creado exitosamente', 'success');
                $('#expenseModal').modal('hide');
                location.reload();
            } else {
                showAlert('Error al crear gasto: ' + response.error, 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            showAlert('Error del servidor al crear gasto', 'danger');
        }
    });
}

function saveOrder() {
    console.log('💾 Guardando orden...');
    
    const formData = new FormData(document.getElementById('orderForm'));
    formData.append('action', 'create_order');
    
    $.ajax({
        url: 'controller.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const ordersCount = response.orders ? response.orders.length : 1;
                showAlert(response.message, 'success');
                $('#orderModal').modal('hide');
                location.reload();
            } else {
                showAlert('Error al crear orden: ' + response.error, 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            showAlert('Error del servidor al crear orden', 'danger');
        }
    });
}

function saveProvider() {
    console.log('💾 Guardando proveedor...');
    
    const formData = new FormData(document.getElementById('providerForm'));
    formData.append('action', 'create_provider');
    
    $.ajax({
        url: 'controller.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Proveedor creado exitosamente', 'success');
                $('#providerModal').modal('hide');
                
                // Recargar proveedores en los selects
                loadProvidersInModal('#provider_id');
                loadProvidersInModal('#edit_provider_id');
                loadProvidersInModal('#order_provider_id');
                
                location.reload();
            } else {
                showAlert('Error al crear proveedor: ' + response.error, 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            showAlert('Error del servidor al crear proveedor', 'danger');
        }
    });
}

function showPaymentModal(expenseId) {
    console.log('💰 Mostrando modal de pago para gasto:', expenseId);
    
    // Cargar datos del gasto para el pago
    $.ajax({
        url: 'controller.php',
        method: 'GET',
        data: { action: 'get_expense', expense_id: expenseId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const expense = response.expense;
                const pendingAmount = parseFloat(expense.amount) - parseFloat(expense.paid_amount || 0);
                
                $('#payment_expense_id').val(expense.id);
                $('#pendingAmountSpan').text('$' + pendingAmount.toLocaleString('es-MX'));
                $('#payment_amount').attr('max', pendingAmount);
                $('#payment_date').val(new Date().toISOString().split('T')[0]);
                
                $('#paymentModal').modal('show');
            } else {
                showAlert('Error al cargar datos del gasto: ' + response.error, 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            showAlert('Error del servidor al cargar gasto', 'danger');
        }
    });
}

function savePayment() {
    console.log('💾 Guardando pago...');
    
    const formData = new FormData(document.getElementById('paymentForm'));
    formData.append('action', 'register_payment');
    
    $.ajax({
        url: 'controller.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Pago registrado exitosamente', 'success');
                $('#paymentModal').modal('hide');
                location.reload();
            } else {
                showAlert('Error al registrar pago: ' + response.error, 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            showAlert('Error del servidor al registrar pago', 'danger');
        }
    });
}

function loadProvidersInModal(selector) {
    console.log('📋 Los proveedores ya están en el HTML del modal, no es necesario cargar via AJAX');
    console.log('🔧 Selector:', selector);
    
    // Los proveedores ya vienen en el HTML desde modals.php
    // Solo necesitamos reinicializar Select2 (esto se hace en el evento shown.bs.modal)
    const select = $(selector);
    const options = select.find('option');
    const validOptions = select.find('option[value!=""]');
    
    console.log(`📊 ${selector}: ${options.length} opciones total, ${validOptions.length} proveedores válidos`);
    
    if (validOptions.length === 0) {
        console.warn(`⚠️ ${selector} - No tiene proveedores válidos en el HTML`);
    } else {
        console.log('✅ Proveedores disponibles en', selector);
        validOptions.each(function() {
            console.log(`   - ${$(this).val()}: ${$(this).text()}`);
        });
    }
}

function deleteExpense(expenseId) {
    console.log('🗑️ Eliminando gasto:', expenseId);
    
    if (confirm('¿Estás seguro de que quieres eliminar este gasto?')) {
        $.ajax({
            url: 'controller.php',
            type: 'POST',
            data: {
                action: 'delete_expense',
                expense_id: expenseId
            }
        })
        .done(function(response) {
            console.log('✅ Gasto eliminado:', response);
            const result = JSON.parse(response);
            if (result.success) {
                showAlert(result.message, 'success');
                location.reload();
            } else {
                showAlert(result.error || 'Error al eliminar', 'danger');
            }
        })
        .fail(function(xhr) {
            console.error('❌ Error eliminando gasto:', xhr.responseText);
            showAlert('Error del servidor', 'danger');
        });
    }
}

function showAlert(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'danger' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').prepend(alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        $('.alert').first().alert('close');
    }, 5000);
}

// ===== FUNCIONALIDADES ADICIONALES =====

function initializeSortableColumns() {
    console.log('🔄 Inicializando columnas reordenables...');
    
    if (typeof Sortable !== 'undefined') {
        const headerRow = document.getElementById('columnas-reordenables');
        
        if (headerRow) {
            Sortable.create(headerRow, {
                animation: 150,
                onEnd: function(evt) {
                    console.log('🔄 Columnas reordenadas');
                    
                    // Obtener el nuevo orden de columnas
                    const newOrder = Array.from(headerRow.children).map(th => {
                        // Obtener las clases de la columna (ej: "col-folio")
                        const classes = Array.from(th.classList);
                        return classes.find(cls => cls.startsWith('col-'));
                    });
                    
                    console.log('📋 Nuevo orden:', newOrder);
                    
                    // Reordenar las celdas en todas las filas del tbody
                    const tbody = document.querySelector('tbody');
                    if (tbody) {
                        const rows = tbody.querySelectorAll('tr');
                        
                        rows.forEach(row => {
                            const cells = Array.from(row.children);
                            const newCells = [];
                            
                            // Reordenar celdas según el nuevo orden de headers
                            newOrder.forEach(colClass => {
                                const cell = cells.find(td => td.classList.contains(colClass));
                                if (cell) {
                                    newCells.push(cell);
                                }
                            });
                            
                            // Aplicar el nuevo orden
                            newCells.forEach(cell => row.appendChild(cell));
                        });
                    }
                    
                    // También reordenar el tfoot si existe
                    const tfoot = document.querySelector('tfoot tr');
                    if (tfoot) {
                        const cells = Array.from(tfoot.children);
                        const newCells = [];
                        
                        newOrder.forEach(colClass => {
                            const cell = cells.find(td => td.classList.contains(colClass));
                            if (cell) {
                                newCells.push(cell);
                            }
                        });
                        
                        newCells.forEach(cell => tfoot.appendChild(cell));
                    }
                    
                    showAlert('Columnas reordenadas correctamente', 'success');
                }
            });
            console.log('✅ Sortable inicializado');
        } else {
            console.warn('⚠️ No se encontró elemento columnas-reordenables');
        }
    } else {
        console.error('❌ SortableJS no está disponible');
    }
}

function initializeMultipleSelection() {
    console.log('☑️ Inicializando selección múltiple...');
    
    // Checkbox principal (seleccionar todos)
    $('#seleccionar-todos').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.seleccionar-gasto').prop('checked', isChecked);
        updateSelectedSummary();
        toggleDeleteButton();
    });
    
    // Checkboxes individuales
    $(document).on('change', '.seleccionar-gasto', function() {
        updateSelectedSummary();
        toggleDeleteButton();
        
        // Actualizar estado del checkbox principal
        const total = $('.seleccionar-gasto').length;
        const checked = $('.seleccionar-gasto:checked').length;
        
        $('#seleccionar-todos').prop('indeterminate', checked > 0 && checked < total);
        $('#seleccionar-todos').prop('checked', checked === total);
    });
    
    // Botón eliminar seleccionados
    $('#btnDeleteSelected').on('click', function() {
        const selected = $('.seleccionar-gasto:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selected.length === 0) {
            showAlert('No hay gastos seleccionados', 'warning');
            return;
        }
        
        if (confirm(`¿Está seguro de eliminar ${selected.length} gasto(s)? Esta acción no se puede deshacer.`)) {
            deleteMultipleExpenses(selected);
        }
    });
    
    console.log('✅ Selección múltiple configurada');
}

function updateSelectedSummary() {
    const selected = $('.seleccionar-gasto:checked');
    const resumen = $('#resumen-seleccionados');
    
    if (selected.length === 0) {
        resumen.addClass('d-none');
        return;
    }
    
    let totalMonto = 0;
    let totalAbono = 0;
    let totalSaldo = 0;
    
    selected.each(function() {
        const row = $(this).closest('tr');
        const monto = parseFloat(row.find('.monto').text().replace(/[$,]/g, '')) || 0;
        const abono = parseFloat(row.find('.abono').text().replace(/[$,]/g, '')) || 0;
        const saldo = parseFloat(row.find('.saldo').text().replace(/[$,]/g, '')) || 0;
        
        totalMonto += monto;
        totalAbono += abono;
        totalSaldo += saldo;
    });
    
    $('#sel-monto').text('$' + totalMonto.toLocaleString('es-MX', {minimumFractionDigits: 2}));
    $('#sel-abono').text('$' + totalAbono.toLocaleString('es-MX', {minimumFractionDigits: 2}));
    $('#sel-saldo').text('$' + totalSaldo.toLocaleString('es-MX', {minimumFractionDigits: 2}));
    
    resumen.removeClass('d-none');
}

function toggleDeleteButton() {
    const selected = $('.seleccionar-gasto:checked').length;
    const deleteBtn = $('#btnDeleteSelected');
    
    if (selected > 0) {
        deleteBtn.removeClass('d-none');
    } else {
        deleteBtn.addClass('d-none');
    }
}

function deleteMultipleExpenses(expenseIds) {
    console.log('🗑️ Eliminando múltiples gastos:', expenseIds);
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: {
            action: 'delete_multiple',
            ids: expenseIds
        }
    })
    .done(function(response) {
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(`${expenseIds.length} gasto(s) eliminado(s) exitosamente`, 'success');
            location.reload();
        } else {
            showAlert(result.error || 'Error al eliminar gastos', 'danger');
        }
    })
    .fail(function(xhr) {
        console.error('❌ Error eliminando gastos:', xhr.responseText);
        showAlert('Error del servidor', 'danger');
    });
}

function calcularTotales() {
    console.log('🧮 Calculando totales...');
    
    const tabla = document.querySelector('table tbody');
    if (!tabla) {
        console.warn('⚠️ No se encontró tabla para calcular totales');
        return;
    }
    
    const filas = tabla.querySelectorAll('tr.expense-row');
    let totalMonto = 0;
    let totalAbono = 0;
    let totalSaldo = 0;
    
    filas.forEach(fila => {
        // Buscar celdas por clase específica
        const montoCell = fila.querySelector('.col-amount');
        const abonoCell = fila.querySelector('.col-abonado');
        const saldoCell = fila.querySelector('.col-saldo');
        
        if (montoCell) {
            const monto = parseFloat(montoCell.textContent.replace(/[$,]/g, '')) || 0;
            totalMonto += monto;
        }
        
        if (abonoCell) {
            const abono = parseFloat(abonoCell.textContent.replace(/[$,]/g, '')) || 0;
            totalAbono += abono;
        }
        
        if (saldoCell) {
            const saldo = parseFloat(saldoCell.textContent.replace(/[$,]/g, '')) || 0;
            totalSaldo += saldo;
        }
    });
    
    // Crear o actualizar el footer con totales
    let tfoot = document.getElementById('tfoot-dinamico');
    if (!tfoot) {
        console.warn('⚠️ No se encontró tfoot-dinamico');
        return;
    }
    
    const formatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    
    // Contar checkboxes (si existe columna de selección)
    const hasCheckbox = document.querySelector('.col-seleccion') ? 1 : 0;
    
    tfoot.innerHTML = `
        <tr class="table-info">
            ${hasCheckbox ? '<th class="col-seleccion"></th>' : ''}
            <th class="col-folio">TOTALES</th>
            <th class="col-provider"></th>
            <th class="col-amount text-end">${formatter.format(totalMonto)}</th>
            <th class="col-payment_date"></th>
            <th class="col-unidad"></th>
            <th class="col-tipo"></th>
            <th class="col-tipo_compra"></th>
            <th class="col-medio"></th>
            <th class="col-cuenta"></th>
            <th class="col-concepto"></th>
            <th class="col-status"></th>
            <th class="col-abonado text-end">${formatter.format(totalAbono)}</th>
            <th class="col-saldo text-end">${formatter.format(totalSaldo)}</th>
            <th class="col-comprobante"></th>
            <th class="col-accion"></th>
        </tr>
    `;
    
    console.log('✅ Totales calculados:', { totalMonto, totalAbono, totalSaldo });
}

// Función para generar PDF
function generatePDF(expenseId) {
    console.log('📄 Generando PDF para gasto ID:', expenseId);
    
    // Abrir en nueva ventana
    const url = `controller.php?action=generate_pdf&expense_id=${expenseId}`;
    window.open(url, '_blank');
}

// Función para mostrar KPIs
function showKPIsModal() {
    console.log('📊 Cargando KPIs...');
    
    const modal = new bootstrap.Modal(document.getElementById('kpisModal'));
    modal.show();
    
    // Cargar contenido de KPIs
    loadKPIsContent();
}

function loadKPIsContent() {
    const modalBody = document.querySelector('#kpisModal .modal-body');
    
    // Mostrar loader
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando estadísticas...</p>
        </div>
    `;
    
    $.ajax({
        url: 'controller.php',
        method: 'GET',
        data: { action: 'get_kpis' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderKPIs(response.kpis);
            } else {
                showAlert('Error al cargar KPIs: ' + response.error, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los KPIs. Por favor, intente nuevamente.
                </div>
            `;
        }
    });
}

function renderKPIs(kpis) {
    const modalBody = document.querySelector('#kpisModal .modal-body');
    
    const html = `
        <div class="row">
            <!-- Tarjetas de resumen -->
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Este Mes
                        </h5>
                        <h3>$${new Intl.NumberFormat('es-MX').format(kpis.total_mes)}</h3>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line me-2"></i>
                            Este Año
                        </h5>
                        <h3>$${new Intl.NumberFormat('es-MX').format(kpis.total_ano)}</h3>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-clock me-2"></i>
                            Pendientes
                        </h5>
                        <h3>$${new Intl.NumberFormat('es-MX').format(kpis.pendientes)}</h3>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar me-2"></i>
                            Promedio Mensual
                        </h5>
                        <h3>$${new Intl.NumberFormat('es-MX').format(kpis.promedio_mensual)}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Gráfico por status -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-pie me-2"></i>Por Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    ${kpis.por_status.map(item => `
                                        <tr>
                                            <td>${item.status}</td>
                                            <td class="text-end"><strong>${item.count}</strong></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top proveedores -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-users me-2"></i>Top 5 Proveedores</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    ${kpis.top_proveedores.map(item => `
                                        <tr>
                                            <td>${item.name || 'Sin proveedor'}</td>
                                            <td class="text-end">
                                                <strong>$${new Intl.NumberFormat('es-MX').format(item.total)}</strong>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Gastos por tipo -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-bar me-2"></i>Por Tipo de Gasto</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${kpis.por_tipo.map(item => `
                                        <tr>
                                            <td>${item.expense_type}</td>
                                            <td class="text-end">
                                                <strong>$${new Intl.NumberFormat('es-MX').format(item.total)}</strong>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = html;
}

    
    console.log('🏁 Diagnóstico completado');
}

// ============================================
// FUNCIONES DE EXPORTACIÓN
// ============================================

function exportToPDF() {
    console.log('📄 Iniciando exportación a PDF...');
    
    // Recoger filtros actuales
    const filters = {
        fecha_inicio: $('#filtro_fecha_inicio').val(),
        fecha_fin: $('#filtro_fecha_fin').val(),
        proveedor: $('#filtro_proveedor').val(),
        tipo: $('#filtro_tipo').val(),
        estado: $('#filtro_estado').val(),
        metodo_pago: $('#filtro_metodo_pago').val()
    };
    
    // Crear formulario para envío
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export-pdf.php';
    form.target = '_blank';
    
    // Agregar filtros como campos ocultos
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filters[key];
            form.appendChild(input);
        }
    });
    
    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    console.log('✅ Solicitud de PDF enviada');
}

function exportToCSV() {
    console.log('📊 Iniciando exportación a CSV...');
    
    // Recoger filtros actuales
    const filters = {
        fecha_inicio: $('#filtro_fecha_inicio').val(),
        fecha_fin: $('#filtro_fecha_fin').val(),
        proveedor: $('#filtro_proveedor').val(),
        tipo: $('#filtro_tipo').val(),
        estado: $('#filtro_estado').val(),
        metodo_pago: $('#filtro_metodo_pago').val()
    };
    
    // Crear URL con parámetros
    const params = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            params.append(key, filters[key]);
        }
    });
    
    // Abrir en nueva ventana
    const url = `export-csv.php?${params.toString()}`;
    window.open(url, '_blank');
    
    console.log('✅ Solicitud de CSV enviada');
}

console.log('✅ Expenses.js - Carga completada');// Función de diagnóstico para verificar que los eventos estén vinculados
window.diagnosticarEventos = function() {
    console.log('🔍 DIAGNÓSTICO DE EVENTOS:');
    
    // Verificar que los formularios existen
    const forms = ['#expenseForm', '#orderForm', '#providerForm'];
    forms.forEach(formId => {
        const form = $(formId);
        if (form.length > 0) {
            console.log(`✅ Formulario ${formId} encontrado`);
            
            // Verificar eventos vinculados
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
    
    // Verificar funciones críticas
    const functions = ['handleExpenseSubmit', 'handleOrderSubmit', 'handleProviderSubmit'];
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ Función ${funcName} disponible`);
        } else {
            console.log(`❌ Función ${funcName} NO disponible`);
        }
    });
    
    console.log('🏁 Diagnóstico completado');
};
