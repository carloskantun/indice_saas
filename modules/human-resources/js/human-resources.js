/**
 * MÓDULO RECURSOS HUMANOS - JavaScript
 * Sistema SaaS Indice
 */

// Variables globales
let currentEmployeeId = null;
let isEditing = false;

// Configuración de DataTables y Select2
$(document).ready(function () {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccionar...',
        allowClear: true,
        language: 'es'
    });

    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Event listeners
    initEventListeners();

    // Cargar datos iniciales
    loadDepartments();
    loadPositions();
});

// ============================================================================
// EVENT LISTENERS
// ============================================================================

function initEventListeners() {
    // Nuevo empleado
    $('#btnNewEmployee').on('click', function () {
        openEmployeeModal();
    });

    // Editar empleado
    $(document).on('click', '.edit-employee', function () {
        const employeeId = $(this).data('employee-id');
        editEmployee(employeeId);
    });

    // Ver empleado
    $(document).on('click', '.view-employee', function () {
        const employeeId = $(this).data('employee-id');
        viewEmployee(employeeId);
    });

    // Eliminar empleado
    $(document).on('click', '.delete-employee', function () {
        const employeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');
        deleteEmployee(employeeId, employeeName);
    });

    // Formulario de empleado
    $('#employeeForm').on('submit', function (e) {
        e.preventDefault();
        saveEmployee();
    });

    // KPIs
    $('#btnKPIs').on('click', function () {
        loadKPIs();
    });

    // Filtros dinámicos
    $('select[name="department_id"]').on('change', function () {
        const departmentId = $(this).val();
        loadPositionsByDepartment(departmentId);
    });

    // Departamentos
    $('#btnDepartments').on('click', function () {
        openDepartmentModal();
    });

    // Posiciones
    $('#btnPositions').on('click', function () {
        openPositionModal();
    });
}

// ============================================================================
// FUNCIONES PARA EMPLEADOS
// ============================================================================

function openEmployeeModal(employee = null) {
    isEditing = employee !== null;
    currentEmployeeId = employee ? employee.id : null;

    // Resetear formulario
    $('#employeeForm')[0].reset();

    // Configurar modal
    const modalTitle = isEditing ? 'Editar Empleado' : 'Nuevo Empleado';
    $('#employeeModal .modal-title').text(modalTitle);

    if (isEditing && employee) {
        // Llenar formulario con datos del empleado
        fillEmployeeForm(employee);
    }

    // Mostrar modal
    $('#employeeModal').modal('show');
}

function fillEmployeeForm(employee) {
    $('#employee_number').val(employee.employee_number);
    $('#first_name').val(employee.first_name);
    $('#last_name').val(employee.last_name);
    $('#email').val(employee.email);
    $('#phone').val(employee.phone);
    $('#department_id').val(employee.department_id).trigger('change');
    $('#position_id').val(employee.position_id).trigger('change');
    $('#hire_date').val(employee.hire_date);
    $('#employment_type').val(employee.employment_type);
    $('#contract_type').val(employee.contract_type);
    $('#salary').val(employee.salary);
    $('#payment_frequency').val(employee.payment_frequency);
    $('#status').val(employee.status);
}

function saveEmployee() {
    const formData = new FormData($('#employeeForm')[0]);
    const action = isEditing ? 'edit_employee' : 'create_employee';

    formData.append('action', action);
    if (isEditing) {
        formData.append('employee_id', currentEmployeeId);
    }

    // Mostrar loading
    showLoading('#employeeModal .modal-body');

    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            hideLoading('#employeeModal .modal-body');

            if (response.success) {
                showAlert('success', response.message);
                $('#employeeModal').modal('hide');
                location.reload(); // Recargar página para mostrar cambios
            } else {
                showAlert('danger', response.error || 'Error al guardar empleado');
            }
        },
        error: function () {
            hideLoading('#employeeModal .modal-body');
            showAlert('danger', 'Error de comunicación con el servidor');
        }
    });
}

function editEmployee(employeeId) {
    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: {
            action: 'get_employee',
            employee_id: employeeId
        },
        success: function (response) {
            if (response.success) {
                openEmployeeModal(response.employee);
            } else {
                showAlert('danger', response.error || 'Error al cargar empleado');
            }
        },
        error: function () {
            showAlert('danger', 'Error de comunicación con el servidor');
        }
    });
}

function viewEmployee(employeeId) {
    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: {
            action: 'get_employee',
            employee_id: employeeId
        },
        success: function (response) {
            if (response.success) {
                showEmployeeDetails(response.employee);
            } else {
                showAlert('danger', response.error || 'Error al cargar empleado');
            }
        },
        error: function () {
            showAlert('danger', 'Error de comunicación con el servidor');
        }
    });
}

function showEmployeeDetails(employee) {
    const modalHtml = `
        <div class="modal fade" id="employeeDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user me-2"></i>Detalles del Empleado
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th>Número:</th><td>${employee.employee_number || 'N/A'}</td></tr>
                                    <tr><th>Nombre:</th><td>${employee.first_name} ${employee.last_name}</td></tr>
                                    <tr><th>Email:</th><td>${employee.email || 'N/A'}</td></tr>
                                    <tr><th>Teléfono:</th><td>${employee.phone || 'N/A'}</td></tr>
                                    <tr><th>Fecha Ingreso:</th><td>${employee.hire_date || 'N/A'}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th>Departamento:</th><td>${employee.department_name || 'Sin asignar'}</td></tr>
                                    <tr><th>Posición:</th><td>${employee.position_title || 'Sin asignar'}</td></tr>
                                    <tr><th>Tipo Empleo:</th><td>${employee.employment_type}</td></tr>
                                    <tr><th>Tipo Contrato:</th><td>${employee.contract_type}</td></tr>
                                    <tr><th>Salario:</th><td>$${parseFloat(employee.salary).toLocaleString()}</td></tr>
                                    <tr><th>Frecuencia Pago:</th><td>${employee.payment_frequency}</td></tr>
                                    <tr><th>Estatus:</th><td><span class="badge bg-${getStatusColor(employee.status)}">${employee.status}</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    $('#employeeDetailsModal').remove();

    // Agregar y mostrar nuevo modal
    $('body').append(modalHtml);
    $('#employeeDetailsModal').modal('show');
}

function deleteEmployee(employeeId, employeeName) {
    Swal.fire({
        title: '¿Dar de baja empleado?',
        text: `¿Estás seguro de dar de baja a ${employeeName}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, dar de baja',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'controller.php',
                type: 'POST',
                data: {
                    action: 'delete_employee',
                    employee_id: employeeId
                },
                success: function (response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        location.reload();
                    } else {
                        showAlert('danger', response.error || 'Error al dar de baja empleado');
                    }
                },
                error: function () {
                    showAlert('danger', 'Error de comunicación con el servidor');
                }
            });
        }
    });
}

// ============================================================================
// FUNCIONES PARA DEPARTAMENTOS Y POSICIONES
// ============================================================================

function loadDepartments() {
    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: { action: 'get_departments' },
        success: function (response) {
            if (response.success) {
                updateDepartmentSelects(response.departments);
            }
        },
        error: function () {
            console.error('Error al cargar departamentos');
        }
    });
}

function loadPositions() {
    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: { action: 'get_positions' },
        success: function (response) {
            if (response.success) {
                updatePositionSelects(response.positions);
            }
        },
        error: function () {
            console.error('Error al cargar posiciones');
        }
    });
}

function loadPositionsByDepartment(departmentId) {
    if (!departmentId) {
        loadPositions();
        return;
    }

    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: {
            action: 'get_positions',
            department_id: departmentId
        },
        success: function (response) {
            if (response.success) {
                updatePositionSelects(response.positions, '#position_id');
            }
        },
        error: function () {
            console.error('Error al cargar posiciones por departamento');
        }
    });
}

function updateDepartmentSelects(departments) {
    const selects = $('select[name="department_id"], #department_id');

    selects.each(function () {
        const currentValue = $(this).val();
        const isFilter = $(this).attr('name') === 'department_id';

        $(this).empty();

        if (isFilter) {
            $(this).append('<option value="">Todos los departamentos</option>');
        } else {
            $(this).append('<option value="">Seleccionar departamento</option>');
        }

        departments.forEach(dept => {
            const selected = currentValue == dept.id ? 'selected' : '';
            $(this).append(`<option value="${dept.id}" ${selected}>${dept.name}</option>`);
        });

        $(this).trigger('change');
    });
}

function updatePositionSelects(positions, selector = 'select[name="position_id"], #position_id') {
    const selects = $(selector);

    selects.each(function () {
        const currentValue = $(this).val();
        const isFilter = $(this).attr('name') === 'position_id';

        $(this).empty();

        if (isFilter) {
            $(this).append('<option value="">Todas las posiciones</option>');
        } else {
            $(this).append('<option value="">Seleccionar posición</option>');
        }

        positions.forEach(pos => {
            const selected = currentValue == pos.id ? 'selected' : '';
            $(this).append(`<option value="${pos.id}" ${selected}>${pos.title}</option>`);
        });

        $(this).trigger('change');
    });
}

function openDepartmentModal() {
    // TODO: Implementar modal de gestión de departamentos
    showAlert('info', 'Gestión de departamentos en desarrollo');
}

function openPositionModal() {
    // TODO: Implementar modal de gestión de posiciones
    showAlert('info', 'Gestión de posiciones en desarrollo');
}

// ============================================================================
// FUNCIONES PARA KPIs
// ============================================================================

function loadKPIs() {
    showLoading('#kpisModal .modal-body');

    $.ajax({
        url: 'controller.php',
        type: 'GET',
        data: { action: 'get_kpis' },
        success: function (response) {
            hideLoading('#kpisModal .modal-body');

            if (response.success) {
                renderKPIs(response.kpis);
            } else {
                showAlert('danger', response.error || 'Error al cargar KPIs');
            }
        },
        error: function () {
            hideLoading('#kpisModal .modal-body');
            showAlert('danger', 'Error de comunicación con el servidor');
        }
    });
}

function renderKPIs(kpis) {
    const kpisHtml = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card kpi-card primary">
                    <div class="kpi-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="kpi-value">${kpis.total_employees}</div>
                    <div class="kpi-label">Empleados Activos</div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card kpi-card success">
                    <div class="kpi-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="kpi-value">${kpis.new_employees_month}</div>
                    <div class="kpi-label">Nuevos este Mes</div>
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <div class="card kpi-card warning">
                    <div class="kpi-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="kpi-value">$${parseFloat(kpis.total_payroll).toLocaleString()}</div>
                    <div class="kpi-label">Nómina Mensual Total</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-building me-2"></i>Por Departamento</h6>
                <div class="list-group">
                    ${kpis.department_distribution.map(dept => `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            ${dept.name || 'Sin asignar'}
                            <span class="badge bg-primary rounded-pill">${dept.count}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-chart-pie me-2"></i>Por Estatus</h6>
                <div class="list-group">
                    ${kpis.status_distribution.map(status => `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            ${status.status}
                            <span class="badge bg-${getStatusColor(status.status)} rounded-pill">${status.count}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;

    $('#kpisContent').html(kpisHtml);
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

function getStatusColor(status) {
    const colors = {
        'Activo': 'success',
        'Inactivo': 'secondary',
        'Vacaciones': 'warning',
        'Licencia': 'info',
        'Baja': 'danger'
    };
    return colors[status] || 'secondary';
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show alert-hr" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    $('#alertContainer').prepend(alertHtml);

    // Auto-hide después de 5 segundos
    setTimeout(() => {
        $('#alertContainer .alert:first-child').fadeOut(() => {
            $(this).remove();
        });
    }, 5000);
}

function showLoading(container) {
    $(container).addClass('loading');
    $(container).append('<div class="text-center loading-spinner"><div class="spinner-border" role="status"></div></div>');
}

function hideLoading(container) {
    $(container).removeClass('loading');
    $(container).find('.loading-spinner').remove();
}

// Función para formatear números
function formatNumber(number, decimals = 2) {
    return parseFloat(number).toLocaleString('es-MX', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}
