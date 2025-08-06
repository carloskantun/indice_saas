/**
 * Admin Permissions - JavaScript Controller
 * Gestión de permisos granulares por usuario y módulo
 */

// Variables globales
let currentUserId = null;
let currentUserData = null;
let systemModules = [];
let allUsers = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Admin Permissions: Inicializando...');
    loadInitialData();
});

/**
 * Cargar datos iniciales
 */
function loadInitialData() {
    showLoadingState(true);
    
    // Cargar usuarios y módulos en paralelo
    Promise.all([
        loadUsers(),
        loadSystemModules()
    ]).then(() => {
        showLoadingState(false);
        showNoUserMessage();
        console.log('✅ Datos iniciales cargados');
    }).catch(error => {
        console.error('❌ Error al cargar datos iniciales:', error);
        showError('Error al cargar los datos iniciales');
        showLoadingState(false);
    });
}

/**
 * Cargar lista de usuarios
 */
function loadUsers() {
    return fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=load_users'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allUsers = data.users;
            updateUserSelect();
            updateBulkUsersSelect();
        } else {
            throw new Error(data.message || 'Error al cargar usuarios');
        }
    });
}

/**
 * Cargar módulos del sistema
 */
function loadSystemModules() {
    return fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_modules'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            systemModules = data.modules;
            updateBulkModulesSelect();
        } else {
            throw new Error(data.message || 'Error al cargar módulos');
        }
    });
}

/**
 * Actualizar selector de usuarios
 */
function updateUserSelect() {
    const userSelect = document.getElementById('userSelect');
    userSelect.innerHTML = '<option value="">Selecciona un usuario para gestionar permisos</option>';
    
    allUsers.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = `${user.name} ${user.lastname} (${user.role})`;
        userSelect.appendChild(option);
    });
}

/**
 * Cargar permisos de usuario seleccionado
 */
function loadUserPermissions() {
    const userSelect = document.getElementById('userSelect');
    const userId = userSelect.value;
    
    if (!userId) {
        showNoUserMessage();
        return;
    }
    
    currentUserId = userId;
    currentUserData = allUsers.find(user => user.id == userId);
    
    showLoadingState(true);
    
    fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=load_permissions&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUserInfo();
            renderPermissionsMatrix(data.permissions);
            showPermissionsMatrix();
        } else {
            throw new Error(data.message || 'Error al cargar permisos');
        }
    })
    .catch(error => {
        console.error('❌ Error al cargar permisos:', error);
        showError('Error al cargar permisos del usuario');
    })
    .finally(() => {
        showLoadingState(false);
    });
}

/**
 * Actualizar información del usuario seleccionado
 */
function updateUserInfo() {
    if (!currentUserData) return;
    
    const userInfo = document.getElementById('userInfo');
    const userInitial = document.getElementById('userInitial');
    const userName = document.getElementById('userName');
    const userRole = document.getElementById('userRole');
    
    userInitial.textContent = currentUserData.name.charAt(0).toUpperCase();
    userName.textContent = `${currentUserData.name} ${currentUserData.lastname}`;
    userRole.textContent = currentUserData.role;
    
    userInfo.style.display = 'block';
    
    // Deshabilitar plantilla SuperAdmin si el usuario ya es SuperAdmin
    const btnSuperAdmin = document.getElementById('btnSuperAdmin');
    if (currentUserData.role === 'superadmin') {
        btnSuperAdmin.disabled = true;
        btnSuperAdmin.innerHTML = '<i class="fas fa-crown me-2"></i>Ya es SuperAdmin';
    } else {
        btnSuperAdmin.disabled = false;
        btnSuperAdmin.innerHTML = '<i class="fas fa-crown me-2"></i>Aplicar Plantilla SuperAdmin';
    }
}

/**
 * Renderizar matriz de permisos
 */
function renderPermissionsMatrix(permissions) {
    const tbody = document.getElementById('permissionsTableBody');
    tbody.innerHTML = '';
    
    systemModules.forEach(module => {
        const userPermission = permissions.find(p => p.module_id == module.id) || {};
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-bold">${escapeHtml(module.name)}</div>
                <small class="text-muted">${escapeHtml(module.description)}</small>
            </td>
            <td class="text-center">
                <div class="form-check d-flex justify-content-center">
                    <input class="form-check-input permission-checkbox" 
                           type="checkbox" 
                           data-module="${module.id}" 
                           data-permission="can_view"
                           ${userPermission.can_view ? 'checked' : ''}>
                </div>
            </td>
            <td class="text-center">
                <div class="form-check d-flex justify-content-center">
                    <input class="form-check-input permission-checkbox" 
                           type="checkbox" 
                           data-module="${module.id}" 
                           data-permission="can_create"
                           ${userPermission.can_create ? 'checked' : ''}>
                </div>
            </td>
            <td class="text-center">
                <div class="form-check d-flex justify-content-center">
                    <input class="form-check-input permission-checkbox" 
                           type="checkbox" 
                           data-module="${module.id}" 
                           data-permission="can_edit"
                           ${userPermission.can_edit ? 'checked' : ''}>
                </div>
            </td>
            <td class="text-center">
                <div class="form-check d-flex justify-content-center">
                    <input class="form-check-input permission-checkbox" 
                           type="checkbox" 
                           data-module="${module.id}" 
                           data-permission="can_delete"
                           ${userPermission.can_delete ? 'checked' : ''}>
                </div>
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-success btn-sm" 
                            onclick="toggleModulePermissions(${module.id}, true)"
                            title="Activar todos">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" 
                            onclick="toggleModulePermissions(${module.id}, false)"
                            title="Desactivar todos">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Agregar listeners para cambios
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            markAsModified();
        });
    });
}

/**
 * Alternar todos los permisos de un módulo
 */
function toggleModulePermissions(moduleId, enable) {
    const checkboxes = document.querySelectorAll(`[data-module="${moduleId}"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = enable;
    });
    markAsModified();
}

/**
 * Seleccionar todos los permisos
 */
function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    markAsModified();
}

/**
 * Deseleccionar todos los permisos
 */
function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    markAsModified();
}

/**
 * Marcar como modificado
 */
function markAsModified() {
    // Cambiar el color del botón guardar para indicar cambios pendientes
    const saveBtn = document.querySelector('[onclick="saveUserPermissions()"]');
    if (saveBtn) {
        saveBtn.classList.remove('btn-primary');
        saveBtn.classList.add('btn-warning');
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Cambios*';
    }
}

/**
 * Guardar permisos del usuario
 */
function saveUserPermissions() {
    if (!currentUserId) {
        showError('No hay usuario seleccionado');
        return;
    }
    
    const permissions = [];
    
    // Recopilar todos los permisos
    systemModules.forEach(module => {
        const permission = {
            module_id: module.id,
            can_view: document.querySelector(`[data-module="${module.id}"][data-permission="can_view"]`).checked,
            can_create: document.querySelector(`[data-module="${module.id}"][data-permission="can_create"]`).checked,
            can_edit: document.querySelector(`[data-module="${module.id}"][data-permission="can_edit"]`).checked,
            can_delete: document.querySelector(`[data-module="${module.id}"][data-permission="can_delete"]`).checked
        };
        permissions.push(permission);
    });
    
    // Enviar al servidor
    fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_permissions&user_id=${currentUserId}&permissions=${encodeURIComponent(JSON.stringify(permissions))}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Permisos actualizados correctamente');
            resetSaveButton();
        } else {
            throw new Error(data.message || 'Error al guardar permisos');
        }
    })
    .catch(error => {
        console.error('❌ Error al guardar permisos:', error);
        showError('Error al guardar permisos');
    });
}

/**
 * Restablecer botón de guardar
 */
function resetSaveButton() {
    const saveBtn = document.querySelector('[onclick="saveUserPermissions()"]');
    if (saveBtn) {
        saveBtn.classList.remove('btn-warning');
        saveBtn.classList.add('btn-primary');
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar';
    }
}

/**
 * Aplicar plantilla de rol
 */
function applyRoleTemplate(role) {
    if (!currentUserId) {
        showError('No hay usuario seleccionado');
        return;
    }
    
    Swal.fire({
        title: '¿Aplicar plantilla de rol?',
        text: `Esto sobrescribirá los permisos actuales con la plantilla "${role}"`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, aplicar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            applyTemplate(role);
        }
    });
}

/**
 * Aplicar plantilla
 */
function applyTemplate(role) {
    fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=apply_role_template&user_id=${currentUserId}&role=${role}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(`Plantilla "${role}" aplicada correctamente`);
            loadUserPermissions(); // Recargar para mostrar los nuevos permisos
        } else {
            throw new Error(data.message || 'Error al aplicar plantilla');
        }
    })
    .catch(error => {
        console.error('❌ Error al aplicar plantilla:', error);
        showError('Error al aplicar plantilla de rol');
    });
}

/**
 * Mostrar modal de acciones masivas
 */
function showBulkActions() {
    updateBulkUsersSelect();
    updateBulkModulesSelect();
    
    const modal = new bootstrap.Modal(document.getElementById('bulkActionsModal'));
    modal.show();
}

/**
 * Actualizar selector de usuarios para acciones masivas
 */
function updateBulkUsersSelect() {
    const select = document.getElementById('bulkUsersSelect');
    select.innerHTML = '';
    
    allUsers.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = `${user.name} ${user.lastname} (${user.role})`;
        select.appendChild(option);
    });
}

/**
 * Actualizar selector de módulos para acciones masivas
 */
function updateBulkModulesSelect() {
    const select = document.getElementById('bulkModulesSelect');
    select.innerHTML = '';
    
    systemModules.forEach(module => {
        const option = document.createElement('option');
        option.value = module.id;
        option.textContent = module.name;
        select.appendChild(option);
    });
}

/**
 * Aplicar permisos masivos
 */
function applyBulkPermissions() {
    const selectedUsers = Array.from(document.getElementById('bulkUsersSelect').selectedOptions).map(o => o.value);
    const selectedModules = Array.from(document.getElementById('bulkModulesSelect').selectedOptions).map(o => o.value);
    
    const permissions = {
        can_view: document.getElementById('bulkView').checked,
        can_create: document.getElementById('bulkCreate').checked,
        can_edit: document.getElementById('bulkEdit').checked,
        can_delete: document.getElementById('bulkDelete').checked
    };
    
    const overwrite = document.getElementById('bulkOverwrite').checked;
    
    if (selectedUsers.length === 0 || selectedModules.length === 0) {
        showError('Debe seleccionar al menos un usuario y un módulo');
        return;
    }
    
    const data = {
        action: 'bulk_update_permissions',
        users: selectedUsers,
        modules: selectedModules,
        permissions: permissions,
        overwrite: overwrite
    };
    
    fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: Object.keys(data).map(key => 
            `${key}=${encodeURIComponent(typeof data[key] === 'object' ? JSON.stringify(data[key]) : data[key])}`
        ).join('&')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(`Permisos aplicados a ${data.affected_users} usuarios`);
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkActionsModal'));
            modal.hide();
            
            // Recargar permisos si hay usuario seleccionado
            if (currentUserId) {
                loadUserPermissions();
            }
        } else {
            throw new Error(data.message || 'Error al aplicar permisos masivos');
        }
    })
    .catch(error => {
        console.error('❌ Error en permisos masivos:', error);
        showError('Error al aplicar permisos masivos');
    });
}

/**
 * Cargar matriz de permisos completa
 */
function loadPermissionMatrix() {
    showLoadingState(true);
    
    fetch('controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_permission_matrix'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('📋 Matriz de permisos completa:', data.matrix);
            showSuccess('Matriz de permisos actualizada');
        } else {
            throw new Error(data.message || 'Error al cargar matriz');
        }
    })
    .catch(error => {
        console.error('❌ Error al cargar matriz:', error);
        showError('Error al cargar matriz de permisos');
    })
    .finally(() => {
        showLoadingState(false);
    });
}

// Funciones de UI

/**
 * Mostrar mensaje de no usuario seleccionado
 */
function showNoUserMessage() {
    document.getElementById('noUserMessage').style.display = 'block';
    document.getElementById('permissionsMatrix').style.display = 'none';
    document.getElementById('loadingState').style.display = 'none';
}

/**
 * Mostrar matriz de permisos
 */
function showPermissionsMatrix() {
    document.getElementById('noUserMessage').style.display = 'none';
    document.getElementById('permissionsMatrix').style.display = 'block';
    document.getElementById('loadingState').style.display = 'none';
}

/**
 * Mostrar estado de carga
 */
function showLoadingState(show) {
    if (show) {
        document.getElementById('noUserMessage').style.display = 'none';
        document.getElementById('permissionsMatrix').style.display = 'none';
        document.getElementById('loadingState').style.display = 'block';
    } else {
        document.getElementById('loadingState').style.display = 'none';
    }
}

/**
 * Mostrar mensaje de éxito
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: message,
        timer: 3000,
        showConfirmButton: false
    });
}

/**
 * Mostrar mensaje de error
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#d33'
    });
}

/**
 * Escapar HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Debug logging
console.log('🎯 Admin Permissions JS: Cargado y listo');
