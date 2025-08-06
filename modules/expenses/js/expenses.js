/**
 * JAVASCRIPT MÓDULO GASTOS - SISTEMA SAAS INDICE
 * Funciones para function bindEvents() {
    // Los botones ahora usan data-bs-toggle nativo de Bootstrap
    // Mantenemos los event listeners para funcionalidad adicional si es necesaria
    
    // Formularios
    $('#expenseForm').on('submit', handleExpenseSubmit);
    $('#orderForm').on('submit', handleOrderSubmit);
    $('#providerForm').on('submit', handleProviderSubmit);
    $('#paymentForm').on('submit', handlePaymentSubmit);
    
    // Event listeners para botones de acción en tabla
    $(document).on('click', '.btn-edit', function() {
        const expenseId = $(this).data('id');
        editExpense(expenseId);
    });
    
    $(document).on('click', '.btn-delete', function() {
        const expenseId = $(this).data('id');
        deleteExpense(expenseId);
    });
    
    $(document).on('click', '.btn-pay', function() {
        const expenseId = $(this).data('id');
        openPaymentModal(expenseId);
    });
    
    $(document).on('click', '.btn-view', function() {
        const expenseId = $(this).data('id');
        viewExpense(expenseId);
    });

    // ============================================
    // INICIALIZACIÓN PRINCIPAL
    // ============================================
});

/**
 * Inicialización principal: gestión de modales, AJAX y interactividad
 */

$(document).ready(function() {
    // Configuración global
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            }
        }
    });

    // Inicializar componentes
    initializeSelects();
    initializeDatePickers();
    initializeDataTable();
    bindEvents();
    initializeColumnVisibility();
    initializeSortableColumns();
    initializeQuickFilters();
    initializeMultipleSelection();
    calcularTotales();
    initializeInlineEditing();
    
    // Auto-refresh cada 5 minutos
    setInterval(refreshTable, 300000);
});

// INICIALIZACIÓN DE COMPONENTES
function initializeSelects() {
    $('.select2').select2({
        language: 'es',
        placeholder: 'Seleccionar...',
        allowClear: true,
        width: '100%'
    });
}

// Eventos para modales
$(document).on('shown.bs.modal', '#expenseModal, #orderModal', function() {
    // Reinicializar Select2 cuando se abren los modales
    $(this).find('.select2').select2({
        language: 'es',
        placeholder: 'Seleccionar...',
        allowClear: true,
        width: '100%',
        dropdownParent: $(this)
    });
});

function initializeDatePickers() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        language: 'es',
        autoclose: true,
        todayHighlight: true
    });
}

function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#expensesTable')) {
        $('#expensesTable').DataTable().destroy();
    }
    
    $('#expensesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']], // Ordenar por folio descendente
        columnDefs: [
            { targets: [5], orderable: false }, // Columna acciones
            { targets: [3, 4], className: 'text-end' } // Alinear montos a la derecha
        ]
    });
}

function bindEvents() {
    // Los botones ahora usan data-bs-toggle nativo de Bootstrap
    // Mantenemos los event listeners para funcionalidad adicional si es necesaria
    
    // Formularios
    $('#expenseForm').on('submit', handleExpenseSubmit);
    $('#orderForm').on('submit', handleOrderSubmit);
    $('#providerForm').on('submit', handleProviderSubmit);
    $('#paymentForm').on('submit', handlePaymentSubmit);
    
    // Botones de acción en tabla
    $(document).on('click', '.btn-edit', function() {
        const expenseId = $(this).data('id');
        editExpense(expenseId);
    });
    
    $(document).on('click', '.btn-delete', function() {
        const expenseId = $(this).data('id');
        deleteExpense(expenseId);
    });
    
    $(document).on('click', '.btn-pay', function() {
        const expenseId = $(this).data('id');
        openPaymentModal(expenseId);
    });
    
    $(document).on('click', '.btn-view', function() {
        const expenseId = $(this).data('id');
        viewExpense(expenseId);
    });
    
    // Filtros
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
    
    $('#btnClearFilters').on('click', clearFilters);
    
    // Exportar
    $('#btnExport').on('click', exportData);
}

// MANEJO DE PROVEEDORES
// Esta función ya no es necesaria porque cargamos proveedores en PHP
/*
function loadProviders() {
    $.get('controller.php?action=get_providers')
        .done(function(data) {
            const providers = JSON.parse(data);
            const selects = $('#provider_id, #edit_provider_id, #order_provider_id');
            
            selects.empty().append('<option value="">Sin proveedor</option>');
            
            providers.forEach(function(provider) {
                selects.append(`<option value="${provider.id}">${provider.name}</option>`);
            });
            
            selects.trigger('change');
        })
        .fail(function(xhr) {
            console.error('Error cargando proveedores:', xhr.responseText);
        });
}
*/

function openNewProviderModal() {
    $('#providerModal').modal('show');
    $('#providerForm')[0].reset();
    $('#providerModalLabel').text('Nuevo Proveedor');
    $('#provider_id_hidden').val('');
}

function handleProviderSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const isEdit = $('#provider_id_hidden').val() !== '';
    
    formData.append('action', isEdit ? 'edit_provider' : 'create_provider');
    if (isEdit) {
        formData.append('provider_id', $('#provider_id_hidden').val());
    }
    
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
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#providerModal').modal('hide');
            // Recargar la página para actualizar los proveedores en todos los selects
            location.reload();
        } else {
            showAlert(result.error || 'Error al guardar proveedor', 'danger');
        }
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

// MANEJO DE GASTOS
function openNewExpenseModal() {
    $('#expenseModal').modal('show');
    $('#expenseForm')[0].reset();
    $('#expenseModalLabel').text('Nuevo Gasto');
    $('#expense_id_hidden').val('');
    $('#payment_date').val(new Date().toISOString().split('T')[0]);
}

function openNewOrderModal() {
    $('#orderModal').modal('show');
    $('#orderForm')[0].reset();
    $('#orderModalLabel').text('Nueva Orden de Compra');
    $('#order_payment_date').val(new Date().toISOString().split('T')[0]);
    
    // Manejar campos recurrentes
    $('#order_expense_type').on('change', function() {
        const isRecurrent = $(this).val() === 'Recurrente';
        $('#camposRecurrente').toggleClass('d-none', !isRecurrent);
    });
    
    loadProviders(); // Cargar proveedores en el select de orden
}

function handleOrderSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_order');
    
    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('.btn-submit').prop('disabled', true).text('Creando...');
        }
    })
    .done(function(response) {
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message + ' (Folio: ' + result.order_folio + ')', 'success');
            $('#orderModal').modal('hide');
            refreshTable();
        } else {
            showAlert(result.error || 'Error al crear orden', 'danger');
        }
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Crear Orden');
    });
}

function editExpense(expenseId) {
    $.get(`controller.php?action=get_expense&expense_id=${expenseId}`)
        .done(function(data) {
            const result = JSON.parse(data);
            const expense = result.expense;
            
            // Llenar formulario
            $('#expense_id_hidden').val(expense.id);
            $('#edit_provider_id').val(expense.provider_id).trigger('change');
            $('#edit_amount').val(expense.amount);
            $('#edit_payment_date').val(expense.payment_date);
            $('#edit_expense_type').val(expense.expense_type);
            $('#edit_purchase_type').val(expense.purchase_type);
            $('#edit_payment_method').val(expense.payment_method);
            $('#edit_bank_account').val(expense.bank_account);
            $('#edit_concept').val(expense.concept);
            $('#edit_order_folio').val(expense.order_folio);
            $('#edit_origin').val(expense.origin);
            
            $('#expenseModalLabel').text('Editar Gasto');
            $('#expenseModal').modal('show');
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error al cargar gasto';
            showAlert(error, 'danger');
        });
}

function handleExpenseSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const isEdit = $('#expense_id_hidden').val() !== '';
    
    formData.append('action', isEdit ? 'edit_expense' : 'create_expense');
    if (isEdit) {
        formData.append('expense_id', $('#expense_id_hidden').val());
    }
    
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
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#expenseModal').modal('hide');
            refreshTable();
        } else {
            showAlert(result.error || 'Error al guardar gasto', 'danger');
        }
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Guardar');
    });
}

function deleteExpense(expenseId) {
    if (!confirm('¿Está seguro de eliminar este gasto? Esta acción no se puede deshacer.')) {
        return;
    }
    
    $.post('controller.php', {
        action: 'delete_expense',
        expense_id: expenseId
    })
    .done(function(response) {
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            refreshTable();
        } else {
            showAlert(result.error || 'Error al eliminar gasto', 'danger');
        }
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    });
}

function viewExpense(expenseId) {
    $.get(`controller.php?action=get_expense&expense_id=${expenseId}`)
        .done(function(data) {
            const result = JSON.parse(data);
            const expense = result.expense;
            const payments = result.payments;
            
            // Llenar modal de vista
            $('#viewExpenseModal .modal-body').html(generateExpenseView(expense, payments, result.pending_amount));
            $('#viewExpenseModal').modal('show');
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error al cargar gasto';
            showAlert(error, 'danger');
        });
}

function generateExpenseView(expense, payments, pendingAmount) {
    const statusBadge = getStatusBadge(expense.status);
    const formatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-receipt me-2"></i>Información del Gasto</h6>
                <table class="table table-sm">
                    <tr><td><strong>Folio:</strong></td><td>${expense.folio || 'N/A'}</td></tr>
                    <tr><td><strong>Proveedor:</strong></td><td>${expense.provider_name || 'Sin proveedor'}</td></tr>
                    <tr><td><strong>Monto:</strong></td><td>${formatter.format(expense.amount)}</td></tr>
                    <tr><td><strong>Fecha:</strong></td><td>${formatDate(expense.payment_date)}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td>${expense.expense_type}</td></tr>
                    <tr><td><strong>Estatus:</strong></td><td>${statusBadge}</td></tr>
                    <tr><td><strong>Concepto:</strong></td><td>${expense.concept}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-credit-card me-2"></i>Información de Pago</h6>
                <table class="table table-sm">
                    <tr><td><strong>Método:</strong></td><td>${expense.payment_method}</td></tr>
                    <tr><td><strong>Cuenta:</strong></td><td>${expense.bank_account || 'N/A'}</td></tr>
                    <tr><td><strong>Origen:</strong></td><td>${expense.origin}</td></tr>
                    <tr><td><strong>Orden:</strong></td><td>${expense.order_folio || 'N/A'}</td></tr>
                    <tr><td><strong>Unidad:</strong></td><td>${expense.unit_name}</td></tr>
                    <tr><td><strong>Negocio:</strong></td><td>${expense.business_name}</td></tr>
                </table>
            </div>
        </div>
    `;
    
    if (payments.length > 0) {
        html += `
            <hr>
            <h6><i class="fas fa-list me-2"></i>Historial de Pagos</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Comentario</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        payments.forEach(payment => {
            html += `
                <tr>
                    <td>${formatDate(payment.payment_date)}</td>
                    <td>${formatter.format(payment.amount)}</td>
                    <td>${payment.comment || '-'}</td>
                    <td>${payment.created_by_name}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    if (pendingAmount > 0) {
        html += `
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Monto pendiente:</strong> ${formatter.format(pendingAmount)}
            </div>
        `;
    }
    
    return html;
}

// MANEJO DE PAGOS
function openPaymentModal(expenseId) {
    $.get(`controller.php?action=get_expense&expense_id=${expenseId}`)
        .done(function(data) {
            const result = JSON.parse(data);
            const expense = result.expense;
            const pendingAmount = result.pending_amount;
            
            if (pendingAmount <= 0) {
                showAlert('Este gasto ya está completamente pagado', 'info');
                return;
            }
            
            $('#payment_expense_id').val(expenseId);
            $('#payment_amount').attr('max', pendingAmount).val(pendingAmount);
            $('#payment_date').val(new Date().toISOString().split('T')[0]);
            $('#payment_comment').val('');
            
            $('#paymentModalLabel').text(`Registrar Pago - ${expense.folio}`);
            $('#pendingAmountSpan').text(new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(pendingAmount));
            
            $('#paymentModal').modal('show');
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error al cargar gasto';
            showAlert(error, 'danger');
        });
}

function handlePaymentSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_payment');
    
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
        const result = JSON.parse(response);
        if (result.success) {
            showAlert(result.message, 'success');
            $('#paymentModal').modal('hide');
            refreshTable();
        } else {
            showAlert(result.error || 'Error al registrar pago', 'danger');
        }
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error del servidor';
        showAlert(error, 'danger');
    })
    .always(function() {
        $('.btn-submit').prop('disabled', false).text('Registrar Pago');
    });
}

// MANEJO DE KPIs
function openKPIsModal() {
    const fechaInicio = $('#filter_fecha_inicio').val() || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const fechaFin = $('#filter_fecha_fin').val() || new Date().toISOString().split('T')[0];
    const unitId = $('#filter_unit_id').val() || '';
    
    $.get('controller.php', {
        action: 'get_kpis',
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        unit_id: unitId
    })
    .done(function(data) {
        const kpis = JSON.parse(data);
        generateKPIsContent(kpis);
        $('#kpisModal').modal('show');
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON ? xhr.responseJSON.error : 'Error al cargar KPIs';
        showAlert(error, 'danger');
    });
}

function generateKPIsContent(kpis) {
    const formatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h2>${formatter.format(kpis.total)}</h2>
                        <p class="mb-0">Total del Período</p>
                        <small>${kpis.period.start} - ${kpis.period.end}</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4>${formatter.format(kpis.payments.paid)}</h4>
                        <p class="mb-0">Pagado</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4>${formatter.format(kpis.payments.pending)}</h4>
                        <p class="mb-0">Pendiente</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6>Por Tipo de Gasto</h6>
                <canvas id="typeChart" width="400" height="300"></canvas>
            </div>
            <div class="col-md-6">
                <h6>Por Estatus</h6>
                <canvas id="statusChart" width="400" height="300"></canvas>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <h6>Top 10 Proveedores</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr><th>Proveedor</th><th>Total</th></tr>
                        </thead>
                        <tbody>
    `;
    
    kpis.by_provider.forEach(provider => {
        html += `<tr><td>${provider.provider || 'Sin proveedor'}</td><td>${formatter.format(provider.total)}</td></tr>`;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    $('#kpisModal .modal-body').html(html);
    
    // Generar gráficos
    setTimeout(() => {
        generateChart('typeChart', kpis.by_type, 'expense_type', 'total');
        generateChart('statusChart', kpis.by_status, 'status', 'total');
    }, 100);
}

function generateChart(canvasId, data, labelKey, valueKey) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    const labels = data.map(item => item[labelKey]);
    const values = data.map(item => parseFloat(item[valueKey]));
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6c757d'];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// UTILIDADES
function refreshTable() {
    location.reload(); // Recarga simple por ahora
}

function applyFilters() {
    const form = $('#filterForm');
    const params = new URLSearchParams(window.location.search);
    
    // Actualizar parámetros URL
    form.find('input, select').each(function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        
        if (value) {
            params.set(name, value);
        } else {
            params.delete(name);
        }
    });
    
    // Recargar página con filtros
    window.location.search = params.toString();
}

function clearFilters() {
    window.location.href = window.location.pathname;
}

function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open(`controller.php?${params.toString()}`);
}

function showAlert(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertContainer').html(alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

function getStatusBadge(status) {
    const badges = {
        'Pendiente': 'badge bg-warning text-dark',
        'Pago parcial': 'badge bg-info',
        'Pagado': 'badge bg-success',
        'Cancelado': 'badge bg-danger'
    };
    
    return `<span class="${badges[status] || 'badge bg-secondary'}">${status}</span>`;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX');
}

// ===== FUNCIONALIDADES ADICIONALES =====

// Funcionalidad de columnas (mostrar/ocultar)
function initializeColumnVisibility() {
    const KEY = 'gastos_columnas';
    
    function save() {
        const c = {};
        document.querySelectorAll('.col-toggle').forEach(cb => {
            c[cb.dataset.col] = cb.checked;
        });
        localStorage.setItem(KEY, JSON.stringify(c));
    }
    
    function restore() {
        const c = JSON.parse(localStorage.getItem(KEY) || '{}');
        document.querySelectorAll('.col-toggle').forEach(cb => {
            if (c.hasOwnProperty(cb.dataset.col)) {
                cb.checked = c[cb.dataset.col];
            }
            document.querySelectorAll('.col-' + cb.dataset.col).forEach(el => {
                el.style.display = cb.checked ? '' : 'none';
                if (c.hasOwnProperty(cb.dataset.col)) {
                    el.style.display = c[cb.dataset.col] ? '' : 'none';
                }
            });
        });
    }
    
    restore();
    
    document.querySelectorAll('.col-toggle').forEach(cb => 
        cb.addEventListener('change', function() {
            document.querySelectorAll('.col-' + this.dataset.col).forEach(el => {
                el.style.display = this.checked ? '' : 'none';
            });
            save();
        })
    );
}

// Funcionalidad de columnas reordenables
function initializeSortableColumns() {
    if (typeof Sortable !== 'undefined') {
        const columnas = document.getElementById('columnas-reordenables');
        const tabla = document.querySelector('table');
        
        if (!columnas || !tabla) return;
        
        Sortable.create(columnas, {
            animation: 150,
            onEnd: () => {
                let order = [];
                columnas.querySelectorAll('th').forEach(th => order.push(th.className));
                localStorage.setItem('orden_columnas_gastos', JSON.stringify(order));
                
                let filas = tabla.querySelectorAll('tbody tr');
                filas.forEach(tr => {
                    let celdas = Array.from(tr.children);
                    let nuevo = [];
                    order.forEach(cls => {
                        let cel = celdas.find(td => td.classList.contains(cls));
                        if (cel) nuevo.push(cel);
                    });
                    nuevo.forEach(td => tr.appendChild(td));
                });
            }
        });
        
        // Restaurar orden guardado
        let saved = JSON.parse(localStorage.getItem('orden_columnas_gastos') || '[]');
        if (saved.length > 0) {
            let ths = Array.from(columnas.children);
            let nuevo = [];
            saved.forEach(cls => {
                let th = ths.find(el => el.classList.contains(cls));
                if (th) nuevo.push(th);
            });
            nuevo.forEach(th => columnas.appendChild(th));
            
            let filas = tabla.querySelectorAll('tbody tr');
            filas.forEach(tr => {
                let celdas = Array.from(tr.children);
                let nuevo = [];
                saved.forEach(cls => {
                    let cel = celdas.find(td => td.classList.contains(cls));
                    if (cel) nuevo.push(cel);
                });
                nuevo.forEach(td => tr.appendChild(td));
            });
        }
    }
}

// Edición en línea
function initializeInlineEditing() {
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('editable-campo')) {
            const id = e.target.dataset.id;
            const campo = e.target.dataset.campo;
            const valor = e.target.value;
            
            fetch('controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_field&expense_id=${id}&field=${campo}&value=${encodeURIComponent(valor)}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar: ' + (data.error || 'Error desconocido'));
                    location.reload(); // Recargar para restaurar valor original
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
    });
}

// Filtros rápidos
function initializeQuickFilters() {
    document.querySelectorAll('.quick-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('filterForm');
            if (!form) return;
            
            // Limpiar todos los campos
            form.querySelectorAll('select, input').forEach(el => {
                el.value = '';
            });
            
            // Asignar filtros del botón
            const est = this.dataset.estatus || '';
            const ori = this.dataset.origen || '';
            form.querySelector('[name="estatus"]').value = est;
            form.querySelector('[name="origen"]').value = ori;
            
            form.submit();
        });
    });
    
    // Botón limpiar filtros
    const btnClear = document.getElementById('btnClearFilters');
    if (btnClear) {
        btnClear.addEventListener('click', function() {
            const form = document.getElementById('filterForm');
            if (form) {
                form.reset();
                window.location.href = window.location.pathname;
            }
        });
    }
}

// Totales dinámicos
function calcularTotales() {
    const tabla = document.querySelector('table');
    if (!tabla) return;
    
    const cuerpo = tabla.querySelector('tbody');
    const filas = cuerpo.querySelectorAll('tr');
    
    let totalMonto = 0, totalAbono = 0, totalSaldo = 0;
    
    filas.forEach(tr => {
        const monto = parseFloat(tr.querySelector('.col-amount')?.textContent.replace(/[$,]/g, '') || 0);
        const abonado = parseFloat(tr.querySelector('.col-abonado')?.textContent.replace(/[$,]/g, '') || 0);
        const saldo = parseFloat(tr.querySelector('.col-saldo')?.textContent.replace(/[$,]/g, '') || 0);
        
        totalMonto += monto;
        totalAbono += abonado;
        totalSaldo += saldo;
    });
    
    const columnas = tabla.querySelectorAll('thead th');
    const tfoot = document.getElementById('tfoot-dinamico');
    if (!tfoot) return;
    
    const fila = document.createElement('tr');
    
    columnas.forEach(th => {
        const td = document.createElement('td');
        const clase = th.className;
        
        if (clase.includes('col-amount')) {
            td.innerHTML = `<strong>$${totalMonto.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong>`;
        } else if (clase.includes('col-abonado')) {
            td.innerHTML = `<strong>$${totalAbono.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong>`;
        } else if (clase.includes('col-saldo')) {
            td.innerHTML = `<strong>$${totalSaldo.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong>`;
        } else if (clase.includes('col-folio')) {
            td.innerHTML = '<strong>Totales:</strong>';
        } else {
            td.innerHTML = '';
        }
        
        fila.appendChild(td);
    });
    
    tfoot.innerHTML = '';
    tfoot.appendChild(fila);
}

// Selección múltiple
function initializeMultipleSelection() {
    const checkboxes = document.querySelectorAll('.seleccionar-gasto');
    const btnEliminar = document.getElementById('btnDeleteSelected');
    const chkTodos = document.getElementById('seleccionar-todos');
    
    function actualizarBoton() {
        const algunoMarcado = Array.from(checkboxes).some(cb => cb.checked);
        if (btnEliminar) {
            btnEliminar.classList.toggle('d-none', !algunoMarcado);
        }
        
        // Actualizar resumen
        const resumen = document.getElementById('resumen-seleccionados');
        if (algunoMarcado) {
            let totalMonto = 0, totalAbono = 0, totalSaldo = 0;
            
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const row = cb.closest('tr');
                    totalMonto += parseFloat(row.querySelector('.col-amount')?.textContent.replace(/[$,]/g, '') || 0);
                    totalAbono += parseFloat(row.querySelector('.col-abonado')?.textContent.replace(/[$,]/g, '') || 0);
                    totalSaldo += parseFloat(row.querySelector('.col-saldo')?.textContent.replace(/[$,]/g, '') || 0);
                }
            });
            
            document.getElementById('sel-monto').textContent = totalMonto.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
            document.getElementById('sel-abono').textContent = totalAbono.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
            document.getElementById('sel-saldo').textContent = totalSaldo.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
            
            resumen.classList.remove('d-none');
        } else {
            resumen.classList.add('d-none');
        }
    }
    
    checkboxes.forEach(cb => cb.addEventListener('change', actualizarBoton));
    
    if (chkTodos) {
        chkTodos.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = chkTodos.checked);
            actualizarBoton();
        });
    }
    
    // Eliminar seleccionados
    if (btnEliminar) {
        btnEliminar.addEventListener('click', function() {
            const ids = Array.from(document.querySelectorAll('.seleccionar-gasto'))
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (!ids.length) return;
            
            if (!confirm('¿Está seguro de eliminar los gastos seleccionados?')) return;
            
            const params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));
            
            fetch('controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=delete_multiple&' + params.toString()
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (res.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error de conexión');
            });
        });
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(amount);
}
