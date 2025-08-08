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

console.log('✅ Expenses.js SIMPLIFICADO - Carga completada');
