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
        
    // 4. Inicializar funciones básicas
    initializeColumnVisibility();
    initializeQuickFilters();
    initializeSortableColumns();
    initializeMultipleSelection();
    calcularTotales();
    
    console.log('✅ Módulo de gastos inicializado correctamente');    } catch (error) {
        console.error('❌ Error inicializando gastos:', error);
    }
});

function bindBasicEvents() {
    console.log('🔗 Binding eventos básicos...');
    
    // Ver detalles del gasto
    $(document).on('click', '.btn-view', function() {
        const id = $(this).data('id');
        console.log('�️ Ver gasto ID:', id);
        viewExpense(id);
    });
    
    // Generar PDF
    $(document).on('click', '.btn-pdf', function() {
        const id = $(this).data('id');
        console.log('📄 Generar PDF del gasto ID:', id);
        generatePDF(id);
    });
    
    // Editar gasto
    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        console.log('✏️ Editar gasto ID:', id);
        editExpense(id);
    });
    
    // Eliminar gasto
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        console.log('�️ Eliminar gasto ID:', id);
        deleteExpense(id);
    });
    
    // Registrar pago
    $(document).on('click', '.btn-pay', function() {
        const id = $(this).data('id');
        console.log('� Registrar pago gasto ID:', id);
        showPaymentModal(id);
    });
    
    // Nuevo gasto
    $('.btn-nuevo-gasto').on('click', function() {
        console.log('➕ Nuevo gasto');
        $('#expenseModal').modal('show');
    });
    
    // Nueva orden
    $('.btn-nueva-orden').on('click', function() {
        console.log('➕ Nueva orden');
        $('#orderModal').modal('show');
    });
    
    // Nuevo proveedor
    $('.btn-nuevo-proveedor').on('click', function() {
        console.log('➕ Nuevo proveedor');
        $('#providerModal').modal('show');
    });
    
    // Ver KPIs
    $('.btn-kpis').on('click', function() {
        console.log('📊 Ver KPIs');
        showKPIsModal();
    });
    
    // Forms submit
    $('#expenseForm').on('submit', function(e) {
        e.preventDefault();
        console.log('� Guardando gasto...');
        saveExpense();
    });
    
    $('#orderForm').on('submit', function(e) {
        e.preventDefault();
        console.log('💾 Guardando orden...');
        saveOrder();
    });
    
    $('#providerForm').on('submit', function(e) {
        e.preventDefault();
        console.log('💾 Guardando proveedor...');
        saveProvider();
    });
    
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        console.log('💾 Registrando pago...');
        savePayment();
    });
    
    console.log('✅ Eventos básicos vinculados');
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
    showAlert('Función de edición en desarrollo', 'info');
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

console.log('✅ Expenses.js - Carga completada');
