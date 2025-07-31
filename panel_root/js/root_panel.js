/**
 * Panel Root - JavaScript para gestión de planes SaaS
 * Maneja todas las interacciones del frontend
 */

// Variables globales
let currentPlanId = null;

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Configurar eventos de formularios
    setupFormEvents();
});

/**
 * Configurar eventos de formularios
 */
function setupFormEvents() {
    // Formulario agregar plan
    const addForm = document.getElementById('addPlanForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitPlan('create');
        });
    }

    // Formulario editar plan
    const editForm = document.getElementById('editPlanForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitPlan('update');
        });
    }

    // Limpiar formularios al cerrar modales
    const addModal = document.getElementById('addPlanModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function() {
            clearForm('add');
        });
    }

    const editModal = document.getElementById('editPlanModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function() {
            clearForm('edit');
        });
    }
}

/**
 * Enviar formulario de plan (crear o actualizar)
 */
async function submitPlan(action) {
    const formId = action === 'create' ? 'addPlanForm' : 'editPlanForm';
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    // Agregar la acción
    formData.append('action', action === 'create' ? 'create_plan' : 'update_plan');

    // Mostrar loading
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitButton.disabled = true;

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: result.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Recargar la página para mostrar los cambios
                window.location.reload();
            });
        } else {
            // Mostrar mensaje de error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error de conexión. Intente nuevamente.'
        });
    } finally {
        // Restaurar botón
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

/**
 * Editar un plan
 */
async function editPlan(planId) {
    currentPlanId = planId;

    try {
        const response = await fetch(`controller.php?action=get_plan&id=${planId}`);
        const result = await response.json();

        if (result.success) {
            const plan = result.plan;
            
            // Llenar formulario de edición
            document.getElementById('edit_id').value = plan.id;
            document.getElementById('edit_name').value = plan.name || '';
            document.getElementById('edit_description').value = plan.description || '';
            document.getElementById('edit_price_monthly').value = plan.price_monthly || 0;
            document.getElementById('edit_users_max').value = plan.users_max || 1;
            document.getElementById('edit_units_max').value = plan.units_max || 1;
            document.getElementById('edit_businesses_max').value = plan.businesses_max || 1;
            document.getElementById('edit_storage_max_mb').value = plan.storage_max_mb || 100;
            document.getElementById('edit_is_active').checked = plan.is_active == 1;

            // Limpiar módulos y marcar los incluidos
            clearAllModules('edit');
            if (plan.modules_included && Array.isArray(plan.modules_included)) {
                plan.modules_included.forEach(module => {
                    const checkbox = document.getElementById(`edit_module_${module}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            // Mostrar modal
            const editModal = new bootstrap.Modal(document.getElementById('editPlanModal'));
            editModal.show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar los datos del plan.'
        });
    }
}

/**
 * Eliminar un plan
 */
async function deletePlan(planId, companiesCount) {
    // Verificar si el plan está en uso
    if (companiesCount > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Plan en uso',
            text: `Este plan está siendo utilizado por ${companiesCount} empresa(s) y no puede ser eliminado.`,
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Confirmar eliminación
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar plan?',
        text: '¿Está seguro de que desea eliminar este plan? Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_plan');
            formData.append('id', planId);

            const response = await fetch('controller.php', {
                method: 'POST',
                body: formData
            });

            const deleteResult = await response.json();

            if (deleteResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: deleteResult.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: deleteResult.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al eliminar el plan.'
            });
        }
    }
}

/**
 * Seleccionar todos los módulos
 */
function selectAllModules(formType) {
    const checkboxes = document.querySelectorAll(`#${formType}PlanForm input[name="modules_included[]"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

/**
 * Limpiar selección de módulos
 */
function clearAllModules(formType) {
    const checkboxes = document.querySelectorAll(`#${formType}PlanForm input[name="modules_included[]"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

/**
 * Limpiar formulario
 */
function clearForm(formType) {
    const form = document.getElementById(`${formType}PlanForm`);
    if (form) {
        form.reset();
        
        // Limpiar checkboxes específicamente
        clearAllModules(formType);
        
        // Resetear valores por defecto para agregar
        if (formType === 'add') {
            document.getElementById('add_users_max').value = 1;
            document.getElementById('add_units_max').value = 1;
            document.getElementById('add_businesses_max').value = 1;
            document.getElementById('add_storage_max_mb').value = 100;
            document.getElementById('add_is_active').checked = true;
        }
    }
}

/**
 * Formatear números para mostrar
 */
function formatNumber(number) {
    if (number == -1) {
        return 'Ilimitado';
    }
    return new Intl.NumberFormat('es-MX').format(number);
}

/**
 * Formatear precio
 */
function formatPrice(price) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

/**
 * Validar formulario antes de enviar
 */
function validateForm(formType) {
    const form = document.getElementById(`${formType}PlanForm`);
    const name = form.querySelector('[name="name"]').value.trim();
    const price = parseFloat(form.querySelector('[name="price_monthly"]').value);

    if (!name) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'El nombre del plan es requerido.'
        });
        return false;
    }

    if (isNaN(price) || price < 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Precio inválido',
            text: 'El precio debe ser un número válido mayor o igual a 0.'
        });
        return false;
    }

    return true;
}

/**
 * Mostrar/ocultar spinner de carga
 */
function toggleLoading(show, button) {
    if (show) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
    } else {
        button.disabled = false;
        button.innerHTML = button.getAttribute('data-original-text') || 'Guardar';
    }
}

// ===== FUNCIONES PARA GESTIÓN DE EMPRESAS =====

/**
 * Ver detalles completos de una empresa
 */
async function viewCompany(companyId) {
    try {
        const response = await fetch(`controller.php?action=get_company_details&id=${companyId}`);
        const result = await response.json();

        if (result.success) {
            const company = result.company;
            const users = result.users;
            const units = result.units;
            const businesses = result.businesses;
            
            // Llenar información básica
            document.getElementById('view_company_id').textContent = company.id;
            document.getElementById('view_company_name').textContent = company.name;
            document.getElementById('view_company_description').textContent = company.description || 'Sin descripción';
            document.getElementById('view_company_created_at').textContent = formatDate(company.created_at);
            document.getElementById('view_company_created_by').textContent = company.created_by_name || 'N/A';
            
            // Badges de estado y plan
            const statusBadge = document.getElementById('view_company_status_badge');
            statusBadge.innerHTML = company.status === 'active' 
                ? '<span class="badge bg-success"><i class="fas fa-check"></i> Activa</span>'
                : '<span class="badge bg-danger"><i class="fas fa-times"></i> Inactiva</span>';
                
            const planBadge = document.getElementById('view_company_plan_badge');
            planBadge.innerHTML = company.plan_name 
                ? `<span class="badge bg-primary">${company.plan_name} - $${parseFloat(company.price_monthly).toFixed(2)}/mes</span>`
                : '<span class="badge bg-secondary">Sin plan</span>';
            
            // Contadores
            document.getElementById('view_users_count').textContent = users.length;
            document.getElementById('view_units_count').textContent = units.length;
            document.getElementById('view_businesses_count').textContent = businesses.length;
            document.getElementById('view_modules_count').textContent = company.modules_array.length;
            
            // Límites del plan
            const planLimitsDiv = document.getElementById('view_plan_limits');
            if (company.plan_name) {
                planLimitsDiv.innerHTML = `
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <small class="text-muted">Usuarios</small>
                            <div>${company.users_max == -1 ? 'Ilimitado' : company.users_max}</div>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted">Unidades</small>
                            <div>${company.units_max == -1 ? 'Ilimitado' : company.units_max}</div>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted">Negocios</small>
                            <div>${company.businesses_max == -1 ? 'Ilimitado' : company.businesses_max}</div>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted">Storage</small>
                            <div>${company.storage_max_mb == -1 ? 'Ilimitado' : company.storage_max_mb + ' MB'}</div>
                        </div>
                    </div>
                `;
            } else {
                planLimitsDiv.innerHTML = '<p class="text-muted">Sin plan asignado</p>';
            }
            
            // Módulos incluidos
            const modulesDiv = document.getElementById('view_plan_modules');
            if (company.modules_array.length > 0) {
                const moduleNames = {
                    'gastos': 'Gestión de Gastos',
                    'mantenimiento': 'Mantenimiento',
                    'servicio_cliente': 'Servicio al Cliente',
                    'compras': 'Compras',
                    'lavanderia': 'Lavandería',
                    'transfers': 'Transfers',
                    'kpis': 'KPIs y Métricas',
                    'reportes': 'Reportes',
                    'integraciones': 'Integraciones',
                    'api': 'API Access'
                };
                
                modulesDiv.innerHTML = company.modules_array.map(module => 
                    `<span class="badge bg-light text-dark me-1 mb-1">${moduleNames[module] || module}</span>`
                ).join('');
            } else {
                modulesDiv.innerHTML = '<p class="text-muted">Sin módulos incluidos</p>';
            }
            
            // Tabla de usuarios
            const usersTable = document.getElementById('view_company_users');
            if (users.length > 0) {
                usersTable.innerHTML = users.map(user => `
                    <tr>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td><span class="badge bg-info">${user.role}</span></td>
                        <td>${user.status === 'active' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'}</td>
                        <td>${user.last_accessed ? formatDate(user.last_accessed) : 'Nunca'}</td>
                    </tr>
                `).join('');
            } else {
                usersTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin usuarios</td></tr>';
            }
            
            // Lista de unidades
            const unitsDiv = document.getElementById('view_company_units');
            if (units.length > 0) {
                unitsDiv.innerHTML = units.map(unit => `
                    <div class="border-bottom pb-2 mb-2">
                        <strong>${unit.name}</strong>
                        ${unit.status === 'active' ? '<span class="badge bg-success ms-2">Activa</span>' : '<span class="badge bg-danger ms-2">Inactiva</span>'}
                        ${unit.description ? `<br><small class="text-muted">${unit.description}</small>` : ''}
                    </div>
                `).join('');
            } else {
                unitsDiv.innerHTML = '<p class="text-muted">Sin unidades</p>';
            }
            
            // Lista de negocios
            const businessesDiv = document.getElementById('view_company_businesses');
            if (businesses.length > 0) {
                businessesDiv.innerHTML = businesses.map(business => `
                    <div class="border-bottom pb-2 mb-2">
                        <strong>${business.name}</strong>
                        ${business.status === 'active' ? '<span class="badge bg-success ms-2">Activo</span>' : '<span class="badge bg-danger ms-2">Inactivo</span>'}
                        <br><small class="text-muted">Unidad: ${business.unit_name}</small>
                        ${business.description ? `<br><small class="text-muted">${business.description}</small>` : ''}
                    </div>
                `).join('');
            } else {
                businessesDiv.innerHTML = '<p class="text-muted">Sin negocios</p>';
            }
            
            // Mostrar modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewCompanyModal'));
            viewModal.show();
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar los detalles de la empresa.'
        });
    }
}

/**
 * Editar una empresa
 */
async function editCompany(companyId) {
    try {
        const response = await fetch(`controller.php?action=get_company&id=${companyId}`);
        const result = await response.json();

        if (result.success) {
            const company = result.company;
            
            // Llenar formulario de edición
            document.getElementById('edit_company_id').value = company.id;
            document.getElementById('edit_company_name').value = company.name || '';
            document.getElementById('edit_company_description').value = company.description || '';
            document.getElementById('edit_company_status').value = company.status || 'active';

            // Información del plan actual
            const currentPlanDiv = document.getElementById('current_plan_info');
            if (company.plan_name) {
                currentPlanDiv.innerHTML = `
                    <h6>${company.plan_name}</h6>
                    <p class="mb-1"><strong>Precio:</strong> $${parseFloat(company.price_monthly).toFixed(2)}/mes</p>
                    <p class="mb-0"><small class="text-muted">ID del plan: ${company.plan_id}</small></p>
                `;
            } else {
                currentPlanDiv.innerHTML = '<p class="text-muted">Sin plan asignado</p>';
            }

            // Cargar estadísticas de uso
            loadCompanyUsageStats(companyId);

            // Mostrar modal
            const editModal = new bootstrap.Modal(document.getElementById('editCompanyModal'));
            editModal.show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar los datos de la empresa.'
        });
    }
}

/**
 * Cargar estadísticas de uso de la empresa
 */
async function loadCompanyUsageStats(companyId) {
    try {
        const response = await fetch(`controller.php?action=get_company_usage&id=${companyId}`);
        const result = await response.json();

        if (result.success) {
            const usage = result.usage;
            const statsDiv = document.getElementById('usage_stats');
            
            statsDiv.innerHTML = `
                <div class="col-3 text-center">
                    <i class="fas fa-users text-primary"></i>
                    <div><strong>${usage.users_count}</strong></div>
                    <small>Usuarios</small>
                </div>
                <div class="col-3 text-center">
                    <i class="fas fa-building text-info"></i>
                    <div><strong>${usage.units_count}</strong></div>
                    <small>Unidades</small>
                </div>
                <div class="col-3 text-center">
                    <i class="fas fa-store text-success"></i>
                    <div><strong>${usage.businesses_count}</strong></div>
                    <small>Negocios</small>
                </div>
                <div class="col-3 text-center">
                    <i class="fas fa-chart-line text-warning"></i>
                    <div><strong>100%</strong></div>
                    <small>Activo</small>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading usage stats:', error);
    }
}

/**
 * Cambiar plan de empresa
 */
async function changePlan(companyId) {
    try {
        // Obtener datos de la empresa
        const response = await fetch(`controller.php?action=get_company&id=${companyId}`);
        const result = await response.json();

        if (result.success) {
            const company = result.company;
            
            // Llenar modal
            document.getElementById('change_plan_company_id').value = company.id;
            document.getElementById('change_plan_company_name').textContent = company.name;
            
            // Plan actual
            const currentPlanDiv = document.getElementById('current_plan_details');
            if (company.plan_name) {
                currentPlanDiv.innerHTML = `
                    <h6>${company.plan_name}</h6>
                    <p class="mb-1"><strong>Precio:</strong> $${parseFloat(company.price_monthly).toFixed(2)}/mes</p>
                    <p class="mb-0"><small class="text-muted">Plan actual</small></p>
                `;
            } else {
                currentPlanDiv.innerHTML = '<p class="text-muted">Sin plan asignado</p>';
            }
            
            // Limpiar selector de nuevo plan
            document.getElementById('new_plan_id').value = '';
            document.getElementById('new_plan_details').innerHTML = '<p class="text-muted">Selecciona un plan para ver los detalles</p>';
            document.getElementById('plan_validation_alerts').innerHTML = '';
            
            // Mostrar modal
            const changePlanModal = new bootstrap.Modal(document.getElementById('changePlanModal'));
            changePlanModal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar los datos de la empresa.'
        });
    }
}

/**
 * Alternar estado de empresa (activa/inactiva)
 */
async function toggleCompanyStatus(companyId, newStatus) {
    const action = newStatus === 'active' ? 'activar' : 'desactivar';
    
    const result = await Swal.fire({
        icon: 'warning',
        title: `¿${action.charAt(0).toUpperCase() + action.slice(1)} empresa?`,
        text: `¿Está seguro de que desea ${action} esta empresa?`,
        showCancelButton: true,
        confirmButtonColor: newStatus === 'active' ? '#28a745' : '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${action}`,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_company_status');
            formData.append('id', companyId);
            formData.append('status', newStatus);

            const response = await fetch('controller.php', {
                method: 'POST',
                body: formData
            });

            const toggleResult = await response.json();

            if (toggleResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: toggleResult.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: toggleResult.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cambiar el estado de la empresa.'
            });
        }
    }
}

/**
 * Eliminar empresa
 */
async function deleteCompany(companyId, usersCount) {
    // Verificar si la empresa tiene usuarios
    if (usersCount > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empresa con usuarios',
            text: `Esta empresa tiene ${usersCount} usuario(s) y no puede ser eliminada.`,
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Confirmar eliminación
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar empresa?',
        text: '¿Está seguro de que desea eliminar esta empresa? Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_company');
            formData.append('id', companyId);

            const response = await fetch('controller.php', {
                method: 'POST',
                body: formData
            });

            const deleteResult = await response.json();

            if (deleteResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminada',
                    text: deleteResult.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: deleteResult.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al eliminar la empresa.'
            });
        }
    }
}

/**
 * Enviar formulario de edición de empresa
 */
document.addEventListener('DOMContentLoaded', function() {
    const editCompanyForm = document.getElementById('editCompanyForm');
    if (editCompanyForm) {
        editCompanyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_company');

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitButton.disabled = true;

            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Intente nuevamente.'
                });
            } finally {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });
    }

    // Formulario de cambio de plan
    const changePlanForm = document.getElementById('changePlanForm');
    if (changePlanForm) {
        changePlanForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'change_company_plan');

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cambiando...';
            submitButton.disabled = true;

            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Intente nuevamente.'
                });
            } finally {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });
    }
});

/**
 * Funciones de utilidad
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function refreshData() {
    window.location.reload();
}

function editCompanyFromView() {
    const companyId = document.getElementById('view_company_id').textContent;
    // Cerrar modal de vista
    bootstrap.Modal.getInstance(document.getElementById('viewCompanyModal')).hide();
    // Abrir modal de edición
    setTimeout(() => editCompany(companyId), 300);
}

function openChangePlanModal() {
    const companyId = document.getElementById('edit_company_id').value;
    // Cerrar modal de edición
    bootstrap.Modal.getInstance(document.getElementById('editCompanyModal')).hide();
    // Abrir modal de cambio de plan
    setTimeout(() => changePlan(companyId), 300);
}

// ==================== FUNCIONES DE GESTIÓN DE USUARIOS ====================

/**
 * Ver detalles de un usuario
 */
async function viewUser(userId) {
    try {
        const response = await fetch(`controller.php?action=get_user&id=${userId}`);
        const result = await response.json();

        if (result.success) {
            const user = result.user;
            const companies = result.companies;

            // Llenar información básica
            document.getElementById('viewUserId').textContent = user.id;
            document.getElementById('viewUserName').textContent = user.name;
            document.getElementById('viewUserEmail').textContent = user.email;
            document.getElementById('viewUserCreatedAt').textContent = new Date(user.created_at).toLocaleDateString();
            document.getElementById('viewUserLastAccess').textContent = user.last_access ? 
                new Date(user.last_access).toLocaleDateString() : 'Nunca';
            document.getElementById('viewUserCompaniesCount').textContent = user.companies_count;

            // Avatar
            const avatar = document.getElementById('viewUserAvatar');
            avatar.textContent = user.name.substring(0, 2).toUpperCase();

            // Estado
            const statusBadge = document.getElementById('viewUserStatus');
            statusBadge.className = `badge fs-6 ${user.status === 'active' ? 'bg-success' : 'bg-danger'}`;
            statusBadge.innerHTML = `<i class="fas ${user.status === 'active' ? 'fa-check' : 'fa-times'}"></i> ${user.status === 'active' ? 'Activo' : 'Inactivo'}`;

            // Tabla de empresas
            const companiesTable = document.getElementById('viewUserCompaniesTable');
            companiesTable.innerHTML = '';

            if (companies && companies.length > 0) {
                companies.forEach(company => {
                    const row = `
                        <tr>
                            <td>
                                <i class="fas fa-building text-primary me-2"></i>
                                ${company.company_name}
                            </td>
                            <td>
                                <span class="role-badge role-${company.role}">
                                    ${availableRoles[company.role] || company.role}
                                </span>
                            </td>
                            <td>
                                <span class="badge ${company.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                    ${company.status === 'active' ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>${new Date(company.assigned_at).toLocaleDateString()}</td>
                            <td>${company.last_accessed ? new Date(company.last_accessed).toLocaleDateString() : 'Nunca'}</td>
                        </tr>
                    `;
                    companiesTable.innerHTML += row;
                });

                // Estadísticas
                const activeCompanies = companies.filter(c => c.status === 'active').length;
                const adminRoles = companies.filter(c => ['superadmin', 'admin'].includes(c.role)).length;
                const recentAccess = companies.filter(c => {
                    if (!c.last_accessed) return false;
                    const lastAccess = new Date(c.last_accessed);
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
                    return lastAccess >= sevenDaysAgo;
                }).length;

                document.getElementById('viewUserActiveCompanies').textContent = activeCompanies;
                document.getElementById('viewUserTotalRoles').textContent = companies.length;
                document.getElementById('viewUserAdminRoles').textContent = adminRoles;
                document.getElementById('viewUserRecentAccess').textContent = recentAccess;
            } else {
                companiesTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin asignaciones de empresa</td></tr>';
                document.getElementById('viewUserActiveCompanies').textContent = '0';
                document.getElementById('viewUserTotalRoles').textContent = '0';
                document.getElementById('viewUserAdminRoles').textContent = '0';
                document.getElementById('viewUserRecentAccess').textContent = '0';
            }

            // Actividad reciente (simulada por ahora)
            document.getElementById('viewUserRecentActivity').innerHTML = `
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-sign-in-alt text-success me-2"></i>
                    <small>Último acceso: ${user.last_access ? new Date(user.last_access).toLocaleString() : 'Nunca'}</small>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user-plus text-info me-2"></i>
                    <small>Usuario creado: ${new Date(user.created_at).toLocaleString()}</small>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-building text-primary me-2"></i>
                    <small>Asignado a ${user.companies_count} empresa(s)</small>
                </div>
            `;

            new bootstrap.Modal(document.getElementById('viewUserModal')).show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar datos del usuario'
        });
    }
}

/**
 * Editar usuario
 */
async function editUser(userId) {
    try {
        const response = await fetch(`controller.php?action=get_user&id=${userId}`);
        const result = await response.json();

        if (result.success) {
            const user = result.user;
            const companies = result.companies;

            // Llenar formulario
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUserName').value = user.name;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserStatus').value = user.status;
            document.getElementById('editUserIdDisplay').textContent = user.id;
            document.getElementById('editUserCreatedAt').textContent = new Date(user.created_at).toLocaleDateString();

            // Avatar
            const avatar = document.getElementById('editUserAvatar');
            avatar.textContent = user.name.substring(0, 2).toUpperCase();

            // Información de empresas actuales
            const currentCompaniesInfo = document.getElementById('currentCompaniesInfo');
            if (companies && companies.length > 0) {
                currentCompaniesInfo.innerHTML = `
                    <div class="row">
                        ${companies.map(company => `
                            <div class="col-md-6 mb-2">
                                <div class="card card-body border">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${company.company_name}</strong><br>
                                            <span class="role-badge role-${company.role}">
                                                ${availableRoles[company.role] || company.role}
                                            </span>
                                        </div>
                                        <span class="badge ${company.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                            ${company.status === 'active' ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                currentCompaniesInfo.innerHTML = '<p class="text-muted">Este usuario no tiene asignaciones de empresa.</p>';
            }

            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar datos del usuario'
        });
    }
}

/**
 * Gestionar roles de usuario
 */
async function manageUserRoles(userId) {
    try {
        const response = await fetch(`controller.php?action=get_user&id=${userId}`);
        const result = await response.json();

        if (result.success) {
            const user = result.user;
            const companies = result.companies;

            // Configurar información del usuario
            document.getElementById('manageRolesUserId').value = user.id;
            document.getElementById('manageRolesUserName').textContent = user.name;
            document.getElementById('manageRolesUserEmail').textContent = user.email;
            
            const avatar = document.getElementById('manageRolesUserAvatar');
            avatar.textContent = user.name.substring(0, 2).toUpperCase();

            // Resumen de roles actuales
            const roleSummary = document.getElementById('currentRoleSummary');
            const roleCount = {};
            companies.forEach(company => {
                roleCount[company.role] = (roleCount[company.role] || 0) + 1;
            });

            roleSummary.innerHTML = Object.entries(roleCount).map(([role, count]) => `
                <div class="col-md-3 text-center">
                    <span class="role-badge role-${role}">${availableRoles[role] || role}</span>
                    <div class="mt-1"><strong>${count}</strong> empresa(s)</div>
                </div>
            `).join('');

            // Tabla de roles por empresa
            const rolesTable = document.getElementById('companyRolesTable');
            rolesTable.innerHTML = '';

            companies.forEach(company => {
                const row = document.createElement('tr');
                row.setAttribute('data-company-id', company.company_id);
                row.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-building text-primary me-2"></i>
                            <span>${company.company_name}</span>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge role-${company.role}">
                            ${availableRoles[company.role] || company.role}
                        </span>
                    </td>
                    <td>
                        <select class="form-select form-select-sm new-role-select">
                            <option value="superadmin" ${company.role === 'superadmin' ? 'selected' : ''}>Superadministrador</option>
                            <option value="admin" ${company.role === 'admin' ? 'selected' : ''}>Administrador</option>
                            <option value="moderator" ${company.role === 'moderator' ? 'selected' : ''}>Moderador</option>
                            <option value="user" ${company.role === 'user' ? 'selected' : ''}>Usuario</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm status-select">
                            <option value="active" ${company.status === 'active' ? 'selected' : ''}>Activo</option>
                            <option value="inactive" ${company.status === 'inactive' ? 'selected' : ''}>Inactivo</option>
                        </select>
                    </td>
                    <td class="assigned-date">${new Date(company.assigned_at).toLocaleDateString()}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeCompanyRole(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                rolesTable.appendChild(row);
            });

            // Llenar selector de empresas disponibles
            const companySelect = document.getElementById('newCompanySelect');
            companySelect.innerHTML = '<option value="">Seleccione una empresa</option>';
            
            if (typeof availableCompanies !== 'undefined') {
                const assignedCompanyIds = companies.map(c => c.company_id);
                availableCompanies.forEach(company => {
                    if (!assignedCompanyIds.includes(company.id.toString())) {
                        companySelect.innerHTML += `<option value="${company.id}">${company.name}</option>`;
                    }
                });
            }

            new bootstrap.Modal(document.getElementById('manageUserRolesModal')).show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar datos del usuario'
        });
    }
}

/**
 * Cambiar estado de usuario
 */
async function toggleUserStatus(userId, newStatus) {
    const statusText = newStatus === 'active' ? 'activar' : 'desactivar';
    
    const confirmation = await Swal.fire({
        title: `¿${statusText.charAt(0).toUpperCase() + statusText.slice(1)} usuario?`,
        text: `¿Está seguro que desea ${statusText} este usuario?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'active' ? '#28a745' : '#6c757d',
        cancelButtonColor: '#dc3545',
        confirmButtonText: `Sí, ${statusText}`,
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        try {
            const response = await fetch('controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_user_status',
                    user_id: userId,
                    status: newStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Estado actualizado',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al actualizar estado del usuario'
            });
        }
    }
}

/**
 * Eliminar usuario
 */
async function deleteUser(userId, companiesCount) {
    let warningText = '¿Está seguro que desea eliminar este usuario?';
    if (companiesCount > 0) {
        warningText += ` Este usuario tiene asignaciones en ${companiesCount} empresa(s). Se eliminarán todas las relaciones.`;
    }

    const confirmation = await Swal.fire({
        title: 'Eliminar Usuario',
        text: warningText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('id', userId);

            const response = await fetch('controller.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al eliminar usuario'
            });
        }
    }
}

/**
 * Mostrar/ocultar cambio de contraseña
 */
function togglePasswordChange() {
    const section = document.getElementById('passwordChangeSection');
    const isVisible = section.style.display !== 'none';
    section.style.display = isVisible ? 'none' : 'block';
    
    // Limpiar campos si se oculta
    if (isVisible) {
        document.getElementById('editUserPassword').value = '';
        document.getElementById('editUserPasswordConfirm').value = '';
    }
}

/**
 * Mostrar/ocultar contraseña
 */
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/**
 * Ver empresas actuales del usuario
 */
function viewUserCompanies() {
    // Esta función puede expandirse para mostrar un modal específico o detalles
    console.log('Ver empresas del usuario');
}

/**
 * Editar usuario desde modal de vista
 */
function editUserFromView() {
    const userId = document.getElementById('viewUserId').textContent;
    bootstrap.Modal.getInstance(document.getElementById('viewUserModal')).hide();
    setTimeout(() => editUser(userId), 300);
}

/**
 * Gestionar roles desde modal de vista
 */
function manageUserRolesFromView() {
    const userId = document.getElementById('viewUserId').textContent;
    bootstrap.Modal.getInstance(document.getElementById('viewUserModal')).hide();
    setTimeout(() => manageUserRoles(userId), 300);
}

/**
 * Agregar nueva asignación de empresa
 */
function addNewCompanyRole() {
    const section = document.getElementById('newCompanyAssignmentSection');
    const isVisible = section.style.display !== 'none';
    section.style.display = isVisible ? 'none' : 'block';
}

/**
 * Remover rol de empresa
 */
function removeCompanyRole(button) {
    const row = button.closest('tr');
    row.remove();
}

/**
 * Resetear cambios en roles
 */
function resetAllChanges() {
    const userId = document.getElementById('manageRolesUserId').value;
    manageUserRoles(userId);
}

/**
 * Guardar todos los cambios de roles
 */
async function saveAllRoleChanges() {
    const userId = document.getElementById('manageRolesUserId').value;
    const rows = document.querySelectorAll('#companyRolesTable tr');
    const roleUpdates = [];

    rows.forEach(row => {
        const companyId = row.getAttribute('data-company-id');
        const newRole = row.querySelector('.new-role-select').value;
        const status = row.querySelector('.status-select').value;
        
        roleUpdates.push({
            company_id: companyId,
            role: newRole,
            status: status
        });
    });

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_user_roles',
                user_id: userId,
                role_updates: roleUpdates
            })
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Roles actualizados',
                text: result.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                bootstrap.Modal.getInstance(document.getElementById('manageUserRolesModal')).hide();
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar cambios de roles'
        });
    }
}

// ==================== EVENTOS DE FORMULARIOS DE USUARIOS ====================

// Llenar empresas disponibles en modal de agregar usuario
function populateCompaniesInAddUserModal() {
    const companiesDiv = document.getElementById('companiesCheckboxes');
    const rolesSection = document.getElementById('rolesSection');
    const roleAssignments = document.getElementById('roleAssignments');
    
    if (!companiesDiv || typeof availableCompanies === 'undefined') return;
    
    companiesDiv.innerHTML = '';
    
    availableCompanies.forEach(company => {
        const checkboxDiv = document.createElement('div');
        checkboxDiv.className = 'col-md-6 mb-2';
        checkboxDiv.innerHTML = `
            <div class="form-check">
                <input class="form-check-input company-checkbox" type="checkbox" 
                       value="${company.id}" id="company_${company.id}" 
                       name="companies[]" onchange="updateRoleAssignments()">
                <label class="form-check-label" for="company_${company.id}">
                    <i class="fas fa-building text-primary me-1"></i>
                    ${company.name}
                </label>
            </div>
        `;
        companiesDiv.appendChild(checkboxDiv);
    });
}

// Actualizar asignaciones de roles según empresas seleccionadas
function updateRoleAssignments() {
    const selectedCompanies = document.querySelectorAll('input[name="companies[]"]:checked');
    const rolesSection = document.getElementById('rolesSection');
    const roleAssignments = document.getElementById('roleAssignments');
    
    if (selectedCompanies.length > 0) {
        rolesSection.style.display = 'block';
        roleAssignments.innerHTML = '';
        
        selectedCompanies.forEach(checkbox => {
            const companyId = checkbox.value;
            const companyName = checkbox.nextElementSibling.textContent.trim();
            
            const roleDiv = document.createElement('div');
            roleDiv.className = 'row mb-2 align-items-center';
            roleDiv.innerHTML = `
                <div class="col-md-6">
                    <label class="form-label mb-0">
                        <i class="fas fa-building text-primary me-1"></i>
                        ${companyName}
                    </label>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm" name="role_${companyId}" required>
                        <option value="user">Usuario</option>
                        <option value="moderator">Moderador</option>
                        <option value="admin">Administrador</option>
                        <option value="superadmin">Superadministrador</option>
                    </select>
                </div>
            `;
            roleAssignments.appendChild(roleDiv);
        });
    } else {
        rolesSection.style.display = 'none';
        roleAssignments.innerHTML = '';
    }
}

// Formulario de agregar usuario
document.addEventListener('DOMContentLoaded', function() {
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'create_user';
            
            // Validaciones del lado cliente
            if (data.password !== data.password_confirm) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Las contraseñas no coinciden'
                });
                return;
            }
            
            // Recopilar roles de empresa seleccionados
            const companyRoles = [];
            const selectedCompanies = document.querySelectorAll('input[name="companies[]"]:checked');
            selectedCompanies.forEach(checkbox => {
                const companyId = checkbox.value;
                const roleSelect = document.querySelector(`select[name="role_${companyId}"]`);
                if (roleSelect) {
                    companyRoles.push({
                        company_id: companyId,
                        role: roleSelect.value
                    });
                }
            });
            data.company_roles = companyRoles;
            
            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario creado',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al crear usuario'
                });
            }
        });
    }

    // Formulario de editar usuario
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update_user';
            
            // Validar contraseñas si se están cambiando
            if (data.password && data.password !== data.password_confirm) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Las contraseñas no coinciden'
                });
                return;
            }
            
            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario actualizado',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al actualizar usuario'
                });
            }
        });
    }

    // Formulario de nueva asignación de empresa en modal de roles
    const newCompanyRoleForm = document.getElementById('newCompanyRoleForm');
    if (newCompanyRoleForm) {
        newCompanyRoleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'add_user_company_role';
            data.user_id = document.getElementById('manageRolesUserId').value;
            
            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rol agregado',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Recargar modal de gestión de roles
                        manageUserRoles(data.user_id);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al agregar rol'
                });
            }
        });
    }

    // Evento para abrir modal de agregar usuario
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('show.bs.modal', function() {
            populateCompaniesInAddUserModal();
        });
    }
});

// ==================== FUNCIONES DE GESTIÓN DE MÓDULOS ====================

/**
 * Ver detalles de un módulo
 */
async function viewModule(moduleId) {
    try {
        const response = await fetch(`controller.php?action=get_module&id=${moduleId}`);
        const result = await response.json();

        if (result.success) {
            const module = result.module;
            const plans = result.plans;
            const companies = result.companies;

            // Llenar información básica
            document.getElementById('viewModuleId').textContent = module.id;
            document.getElementById('viewModuleName').textContent = module.name;
            document.getElementById('viewModuleSlug').textContent = `@${module.slug}`;
            document.getElementById('viewModuleSlugCode').textContent = module.slug;
            document.getElementById('viewModuleDescription').textContent = module.description;
            document.getElementById('viewModuleIconClass').textContent = module.icon;
            document.getElementById('viewModuleColorCode').textContent = module.color;
            document.getElementById('viewModuleCreatedAt').textContent = new Date(module.created_at).toLocaleDateString();

            // Ícono y color
            const moduleIcon = document.getElementById('viewModuleIcon');
            moduleIcon.style.backgroundColor = module.color;
            moduleIcon.innerHTML = `<i class="${module.icon}"></i>`;

            const colorDisplay = document.getElementById('viewModuleColorDisplay');
            colorDisplay.style.backgroundColor = module.color;

            // Estado
            const statusBadge = document.getElementById('viewModuleStatus');
            statusBadge.className = `badge fs-6 ${module.status === 'active' ? 'bg-success' : 'bg-danger'}`;
            statusBadge.innerHTML = `<i class="fas ${module.status === 'active' ? 'fa-check' : 'fa-times'}"></i> ${module.status === 'active' ? 'Activo' : 'Inactivo'}`;

            // Planes que usan el módulo
            const plansContainer = document.getElementById('viewModulePlans');
            if (plans && plans.length > 0) {
                plansContainer.innerHTML = plans.map(plan => `
                    <div class="mb-2">
                        <span class="badge bg-primary me-2">${plan.name}</span>
                        <small class="text-muted">Asignado: ${new Date(plan.assigned_at).toLocaleDateString()}</small>
                    </div>
                `).join('');
            } else {
                plansContainer.innerHTML = '<p class="text-muted">Este módulo no está asignado a ningún plan.</p>';
            }

            // Obtener estadísticas de uso
            const usageResponse = await fetch(`controller.php?action=get_module_usage&module_id=${moduleId}`);
            const usageResult = await usageResponse.json();
            
            if (usageResult.success) {
                const stats = usageResult.stats;
                document.getElementById('viewModulePlansCount').textContent = stats.plans_count;
                document.getElementById('viewModuleCompaniesCount').textContent = stats.companies_count;
                document.getElementById('viewModuleUsersCount').textContent = stats.users_count;
                document.getElementById('viewModuleAvailability').textContent = `${stats.availability}%`;
            }

            // Tabla de empresas
            const companiesTable = document.getElementById('viewModuleCompaniesTable');
            if (companies && companies.length > 0) {
                companiesTable.innerHTML = companies.map(company => `
                    <tr>
                        <td>
                            <i class="fas fa-building text-primary me-2"></i>
                            ${company.name}
                        </td>
                        <td>
                            <span class="badge bg-info">${company.plan_name}</span>
                        </td>
                        <td>
                            <span class="badge ${company.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                ${company.status === 'active' ? 'Activa' : 'Inactiva'}
                            </span>
                        </td>
                        <td>${company.users_count} usuarios</td>
                        <td>
                            <small class="text-muted">
                                ${company.last_access ? new Date(company.last_access).toLocaleDateString() : 'Sin acceso'}
                            </small>
                        </td>
                    </tr>
                `).join('');
            } else {
                companiesTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin empresas con acceso</td></tr>';
            }

            new bootstrap.Modal(document.getElementById('viewModuleModal')).show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar datos del módulo'
        });
    }
}

/**
 * Editar módulo
 */
async function editModule(moduleId) {
    try {
        const response = await fetch(`controller.php?action=get_module&id=${moduleId}`);
        const result = await response.json();

        if (result.success) {
            const module = result.module;
            const plans = result.plans;

            // Llenar formulario
            document.getElementById('editModuleId').value = module.id;
            document.getElementById('editModuleName').value = module.name;
            document.getElementById('editModuleSlug').value = module.slug;
            document.getElementById('editModuleDescription').value = module.description;
            document.getElementById('editModuleIcon').value = module.icon;
            document.getElementById('editModuleColor').value = module.color;
            document.getElementById('editModuleStatus').value = module.status;

            // Información adicional
            document.getElementById('editModuleIdDisplay').textContent = module.id;
            document.getElementById('editModuleCreatedAt').textContent = new Date(module.created_at).toLocaleDateString();
            document.getElementById('editModulePlansCount').textContent = module.plans_using_count;
            document.getElementById('editModuleCurrentStatus').textContent = module.status === 'active' ? 'Activo' : 'Inactivo';

            // Actualizar vista previa
            updateEditModulePreview();
            updateEditIconPreview();

            // Mostrar planes actuales
            const currentPlansContainer = document.getElementById('editCurrentPlans');
            if (plans && plans.length > 0) {
                currentPlansContainer.innerHTML = `
                    <label class="form-label">Planes que usan este módulo</label>
                    <div class="list-group">
                        ${plans.map(plan => `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>${plan.name}</strong>
                                    <small class="text-muted">${new Date(plan.assigned_at).toLocaleDateString()}</small>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                currentPlansContainer.innerHTML = '<p class="text-muted">Sin planes asignados.</p>';
            }

            new bootstrap.Modal(document.getElementById('editModuleModal')).show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar datos del módulo'
        });
    }
}

/**
 * Cambiar estado de módulo
 */
async function toggleModuleStatus(moduleId, newStatus) {
    const statusText = newStatus === 'active' ? 'activar' : 'desactivar';
    
    const confirmation = await Swal.fire({
        title: `¿${statusText.charAt(0).toUpperCase() + statusText.slice(1)} módulo?`,
        text: `¿Está seguro que desea ${statusText} este módulo?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'active' ? '#28a745' : '#6c757d',
        cancelButtonColor: '#dc3545',
        confirmButtonText: `Sí, ${statusText}`,
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        try {
            const response = await fetch('controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_module_status',
                    module_id: moduleId,
                    status: newStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Estado actualizado',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al actualizar estado del módulo'
            });
        }
    }
}

/**
 * Eliminar módulo
 */
async function deleteModule(moduleId, plansUsingCount) {
    let warningText = '¿Está seguro que desea eliminar este módulo?';
    if (plansUsingCount > 0) {
        warningText = `Este módulo está siendo usado por ${plansUsingCount} plan(es). No se puede eliminar mientras esté en uso.`;
        
        Swal.fire({
            icon: 'warning',
            title: 'Módulo en uso',
            text: warningText
        });
        return;
    }

    const confirmation = await Swal.fire({
        title: 'Eliminar Módulo',
        text: warningText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_module');
            formData.append('id', moduleId);

            const response = await fetch('controller.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al eliminar módulo'
            });
        }
    }
}

/**
 * Sincronizar módulos del sistema
 */
async function syncSystemModules() {
    const confirmation = await Swal.fire({
        title: 'Sincronizar Módulos',
        text: '¿Desea sincronizar todos los módulos predefinidos del sistema?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, sincronizar',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        try {
            const response = await fetch('controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'sync_system_modules'
                })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sincronización completada',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al sincronizar módulos'
            });
        }
    }
}

/**
 * Cargar módulo predefinido del sistema
 */
function loadSystemModule(moduleSlug) {
    if (typeof systemModules !== 'undefined' && systemModules[moduleSlug]) {
        const module = systemModules[moduleSlug];
        
        document.getElementById('moduleName').value = module.name;
        document.getElementById('moduleSlug').value = module.slug;
        document.getElementById('moduleDescription').value = module.description;
        document.getElementById('moduleIcon').value = module.icon;
        document.getElementById('moduleColor').value = module.color;
        
        updateModulePreview();
        updateIconPreview();
    }
}

/**
 * Actualizar vista previa del módulo
 */
function updateModulePreview() {
    const name = document.getElementById('moduleName').value || 'Nombre del Módulo';
    const description = document.getElementById('moduleDescription').value || 'Descripción del módulo...';
    const icon = document.getElementById('moduleIcon').value || 'fas fa-puzzle-piece';
    const color = document.getElementById('moduleColor').value || '#3498db';
    
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewDescription').textContent = description.substring(0, 60) + '...';
    
    const previewIcon = document.getElementById('previewIcon');
    previewIcon.style.backgroundColor = color;
    previewIcon.innerHTML = `<i class="${icon}"></i>`;
}

/**
 * Actualizar vista previa del ícono
 */
function updateIconPreview() {
    const icon = document.getElementById('moduleIcon').value || 'fas fa-puzzle-piece';
    document.getElementById('iconPreview').className = icon;
    updateModulePreview();
}

/**
 * Actualizar vista previa del módulo en edición
 */
function updateEditModulePreview() {
    const name = document.getElementById('editModuleName').value || 'Nombre del Módulo';
    const description = document.getElementById('editModuleDescription').value || 'Descripción del módulo...';
    const icon = document.getElementById('editModuleIcon').value || 'fas fa-puzzle-piece';
    const color = document.getElementById('editModuleColor').value || '#3498db';
    
    document.getElementById('editPreviewName').textContent = name;
    document.getElementById('editPreviewDescription').textContent = description.substring(0, 60) + '...';
    
    const previewIcon = document.getElementById('editPreviewIcon');
    previewIcon.style.backgroundColor = color;
    previewIcon.innerHTML = `<i class="${icon}"></i>`;
}

/**
 * Actualizar vista previa del ícono en edición
 */
function updateEditIconPreview() {
    const icon = document.getElementById('editModuleIcon').value || 'fas fa-puzzle-piece';
    document.getElementById('editIconPreview').className = icon;
    updateEditModulePreview();
}

/**
 * Editar módulo desde modal de vista
 */
function editModuleFromView() {
    const moduleId = document.getElementById('viewModuleId').textContent;
    bootstrap.Modal.getInstance(document.getElementById('viewModuleModal')).hide();
    setTimeout(() => editModule(moduleId), 300);
}

/**
 * Cambiar estado desde modal de vista
 */
function toggleModuleStatusFromView() {
    const moduleId = document.getElementById('viewModuleId').textContent;
    const currentStatus = document.getElementById('viewModuleStatus').textContent.includes('Activo') ? 'active' : 'inactive';
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    
    bootstrap.Modal.getInstance(document.getElementById('viewModuleModal')).hide();
    setTimeout(() => toggleModuleStatus(moduleId, newStatus), 300);
}

// ==================== EVENTOS DE FORMULARIOS DE MÓDULOS ====================

// Agregar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Formulario de agregar módulo
    const addModuleForm = document.getElementById('addModuleForm');
    if (addModuleForm) {
        addModuleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'create_module';
            
            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Módulo creado',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        bootstrap.Modal.getInstance(document.getElementById('addModuleModal')).hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al crear módulo'
                });
            }
        });

        // Eventos de actualización en tiempo real
        document.getElementById('moduleName').addEventListener('input', updateModulePreview);
        document.getElementById('moduleDescription').addEventListener('input', updateModulePreview);
        document.getElementById('moduleIcon').addEventListener('input', updateIconPreview);
        document.getElementById('moduleColor').addEventListener('input', updateModulePreview);
    }

    // Formulario de editar módulo
    const editModuleForm = document.getElementById('editModuleForm');
    if (editModuleForm) {
        editModuleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update_module';
            
            try {
                const response = await fetch('controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Módulo actualizado',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        bootstrap.Modal.getInstance(document.getElementById('editModuleModal')).hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al actualizar módulo'
                });
            }
        });

        // Eventos de actualización en tiempo real para edición
        document.getElementById('editModuleName').addEventListener('input', updateEditModulePreview);
        document.getElementById('editModuleDescription').addEventListener('input', updateEditModulePreview);
        document.getElementById('editModuleIcon').addEventListener('input', updateEditIconPreview);
        document.getElementById('editModuleColor').addEventListener('input', updateEditModulePreview);
    }
});
