/**
 * JavaScript para gestión de usuarios admin
 */

// Variables globales
let currentUsers = [];
let currentInvitations = [];

// Al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadUnits();
    setupEventListeners();
});

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Tab navigation
    document.querySelectorAll('[data-tab]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            switchTab(this.getAttribute('data-tab'));
        });
    });

    // Unit selection change
    document.getElementById('inviteUnit').addEventListener('change', function() {
        loadBusinessesByUnit(this.value);
    });
}

/**
 * Cambiar pestaña
 */
function switchTab(tabName) {
    // Remover clases activas
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
        content.classList.remove('active');
    });

    // Activar pestaña seleccionada
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    const tabContent = document.getElementById(`${tabName}-tab`);
    tabContent.style.display = 'block';
    tabContent.classList.add('active');

    // Cargar contenido según la pestaña
    switch(tabName) {
        case 'users':
            loadUsers();
            break;
        case 'invitations':
            loadInvitations();
            break;
        case 'roles':
            // No necesita carga adicional
            break;
    }
}

/**
 * Mostrar modal de invitación
 */
function showInviteModal() {
    const modal = new bootstrap.Modal(document.getElementById('inviteUserModal'));
    modal.show();
}

/**
 * Cargar usuarios
 */
async function loadUsers() {
    try {
        const companyId = window.currentCompanyId || getCompanyId();
        if (!companyId) {
            showAlert('error', 'Error', 'No se ha seleccionado una empresa');
            return;
        }

        const response = await fetch('controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=load_users&company_id=${companyId}`
        });

        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = data.html;
        } else {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error al cargar usuarios: ' + (data.message || 'Error desconocido') + '</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error de conexión</td></tr>';
    }
}

/**
 * Mostrar usuarios en la tabla
 */
function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-users fa-3x mb-3 d-block"></i>
                    No hay usuarios registrados
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = users.map(user => `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="fw-bold">${escapeHtml(user.name)}</div>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td>
                <span class="role-badge ${getRoleBadgeClass(user.role)}">
                    ${getRoleIcon(user.role)} ${getRoleText(user.role)}
                </span>
            </td>
            <td>
                <span class="status-badge ${getStatusBadgeClass(user.company_status)}">
                    ${getStatusIcon(user.company_status)} ${getStatusText(user.company_status)}
                </span>
            </td>
            <td>${formatDate(user.joined_date)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editUser(${user.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${user.company_status === 'active' ? 
                        `<button class="btn btn-outline-warning" onclick="suspendUser(${user.id})" title="Suspender">
                            <i class="fas fa-user-slash"></i>
                        </button>` :
                        `<button class="btn btn-outline-success" onclick="activateUser(${user.id})" title="Activar">
                            <i class="fas fa-user-check"></i>
                        </button>`
                    }
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Cargar unidades
 */
async function loadUnits() {
    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_units_by_company'
        });

        const data = await response.json();
        
        if (data.success) {
            const unitSelect = document.getElementById('inviteUnit');
            unitSelect.innerHTML = '<option value="">Seleccionar unidad</option>';
            
            data.units.forEach(unit => {
                unitSelect.innerHTML += `<option value="${unit.id}">${escapeHtml(unit.name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error al cargar unidades:', error);
    }
}

/**
 * Cargar negocios por unidad
 */
async function loadBusinessesByUnit(unitId) {
    const businessSelect = document.getElementById('inviteBusiness');
    
    if (!unitId) {
        businessSelect.innerHTML = '<option value="">Seleccionar negocio</option>';
        businessSelect.disabled = true;
        return;
    }

    try {
        const response = await fetch(`controller.php?action=get_businesses_by_unit&unit_id=${unitId}`);
        const data = await response.json();
        
        if (data.success) {
            businessSelect.innerHTML = '<option value="">Seleccionar negocio</option>';
            businessSelect.disabled = false;
            
            data.businesses.forEach(business => {
                businessSelect.innerHTML += `<option value="${business.id}">${escapeHtml(business.name)}</option>`;
            });
        }
    } catch (error) {
        console.error('Error al cargar negocios:', error);
    }
}

/**
 * Enviar invitación
 */
async function sendInvitation() {
    const form = document.getElementById('inviteUserForm');
    const formData = new FormData(form);
    formData.append('action', 'send_invitation');

    // Validar formulario
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Éxito', data.message);
            bootstrap.Modal.getInstance(document.getElementById('inviteUserModal')).hide();
            form.reset();
            form.classList.remove('was-validated');
            
            // Si estamos en la pestaña de invitaciones, recargar
            if (document.querySelector('[data-tab="invitations"]').classList.contains('active')) {
                loadInvitations();
            }
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error al enviar invitación');
        console.error('Error:', error);
    }
}

/**
 * Editar usuario
 */
function editUser(userId) {
    // Buscar los datos del usuario en la tabla
    const row = document.querySelector(`[onclick="editUser(${userId})"]`).closest('tr');
    if (!row) return;
    
    const cells = row.querySelectorAll('td');
    const userName = cells[0].textContent.trim();
    const userEmail = cells[1].textContent.trim();
    const userRole = cells[2].querySelector('.badge').textContent.toLowerCase().trim();
    
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserName').value = userName;
    document.getElementById('editUserEmail').value = userEmail;
    document.getElementById('editUserRole').value = userRole;

    // Cargar unidades y negocios para los selects
    loadUnits();
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

/**
 * Actualizar rol de usuario
 */
async function updateUserRole() {
    const formData = new FormData();
    formData.append('action', 'update_role');
    formData.append('user_id', document.getElementById('editUserId').value);
    formData.append('new_role', document.getElementById('editUserRole').value);
    formData.append('unit_id', document.getElementById('editUserUnit').value);
    formData.append('business_id', document.getElementById('editUserBusiness').value);
    formData.append('company_id', window.currentCompanyId);

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Éxito', data.message || 'Usuario actualizado correctamente');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            loadUsers();
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error de conexión');
        console.error('Error:', error);
    }
}

/**
 * Suspender usuario
 */
function suspendUser(userId) {
    document.getElementById('suspendUserId').value = userId;
    const modal = new bootstrap.Modal(document.getElementById('suspendUserModal'));
    modal.show();
}

/**
 * Confirmar suspensión de usuario
 */
async function confirmSuspendUser() {
    const userId = document.getElementById('suspendUserId').value;
    
    const formData = new FormData();
    formData.append('action', 'suspend_user');
    formData.append('user_id', userId);

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Éxito', data.message);
            bootstrap.Modal.getInstance(document.getElementById('suspendUserModal')).hide();
            loadUsers();
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error al suspender usuario');
        console.error('Error:', error);
    }
}

/**
 * Activar usuario
 */
function activateUser(userId) {
    document.getElementById('activateUserId').value = userId;
    const modal = new bootstrap.Modal(document.getElementById('activateUserModal'));
    modal.show();
}

/**
 * Confirmar activación de usuario
 */
async function confirmActivateUser() {
    const userId = document.getElementById('activateUserId').value;
    
    const formData = new FormData();
    formData.append('action', 'activate_user');
    formData.append('user_id', userId);

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Éxito', data.message);
            bootstrap.Modal.getInstance(document.getElementById('activateUserModal')).hide();
            loadUsers();
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error al activar usuario');
        console.error('Error:', error);
    }
}

/**
 * Cargar invitaciones pendientes
 */
async function loadInvitations() {
    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_pending_invitations'
        });

        const data = await response.json();
        
        if (data.success) {
            currentInvitations = data.invitations;
            displayInvitations(data.invitations);
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error al cargar invitaciones');
        console.error('Error:', error);
    }
}

/**
 * Mostrar invitaciones
 */
function displayInvitations(invitations) {
    const container = document.getElementById('invitationsContainer');
    
    if (invitations.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-envelope fa-3x mb-3"></i>
                <h5>No hay invitaciones pendientes</h5>
                <p>Las invitaciones enviadas aparecerán aquí</p>
            </div>
        `;
        return;
    }

    container.innerHTML = invitations.map(invitation => `
        <div class="invitation-item p-3">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="fw-bold">${escapeHtml(invitation.email)}</div>
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i> ${getRoleText(invitation.rol)}
                    </small>
                </div>
                <div class="col-md-2">
                    <span class="status-badge ${getInvitationStatusBadgeClass(invitation.status)}">
                        ${getInvitationStatusText(invitation.status)}
                    </span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i> ${formatDate(invitation.fecha_envio)}
                        <br>
                        <i class="fas fa-clock me-1"></i> Expira: ${formatDate(invitation.fecha_expiracion)}
                    </small>
                </div>
                <div class="col-md-2">
                    <small class="text-muted">
                        Por: ${escapeHtml(invitation.sent_by_name || 'Sistema')}
                    </small>
                </div>
                <div class="col-md-2">
                    <div class="btn-group btn-group-sm">
                        ${invitation.status === 'pendiente' ? `
                            <button class="btn btn-outline-primary" onclick="resendInvitation(${invitation.id})" title="Reenviar">
                                <i class="fas fa-redo"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-outline-danger" onclick="deleteInvitation(${invitation.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

/**
 * Reenviar invitación
 */
async function resendInvitation(invitationId) {
    const result = await Swal.fire({
        title: '¿Reenviar invitación?',
        text: 'Se generará un nuevo enlace de invitación',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    const formData = new FormData();
    formData.append('action', 'resend_invitation');
    formData.append('invitation_id', invitationId);

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Éxito', data.message);
            loadInvitations();
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error al reenviar invitación');
        console.error('Error:', error);
    }
}

/**
 * Eliminar invitación
 */
async function deleteInvitation(invitationId) {
    const result = await Swal.fire({
        title: '¿Eliminar invitación?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    });

    if (!result.isConfirmed) return;

    const formData = new FormData();
    formData.append('action', 'delete_invitation');
    formData.append('invitation_id', invitationId);

    try {
        const response = await fetch('controller.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Éxito', data.message);
            loadInvitations();
        } else {
            showAlert('error', 'Error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Error', 'Error al eliminar invitación');
        console.error('Error:', error);
    }
}

// Funciones de utilidad
function getRoleBadgeClass(role) {
    const classes = {
        'superadmin': 'bg-primary',
        'admin': 'bg-success',
        'moderator': 'bg-warning text-dark',
        'user': 'bg-info'
    };
    return classes[role] || 'bg-secondary';
}

function getRoleIcon(role) {
    const icons = {
        'superadmin': '<i class="fas fa-crown"></i>',
        'admin': '<i class="fas fa-user-shield"></i>',
        'moderator': '<i class="fas fa-user-edit"></i>',
        'user': '<i class="fas fa-user"></i>'
    };
    return icons[role] || '<i class="fas fa-user"></i>';
}

function getRoleText(role) {
    const texts = {
        'superadmin': 'Superadmin',
        'admin': 'Admin',
        'moderator': 'Moderador',
        'user': 'Usuario'
    };
    return texts[role] || role;
}

function getStatusBadgeClass(status) {
    const classes = {
        'active': 'bg-success',
        'suspended': 'bg-warning text-dark',
        'inactive': 'bg-secondary'
    };
    return classes[status] || 'bg-secondary';
}

function getStatusIcon(status) {
    const icons = {
        'active': '<i class="fas fa-check"></i>',
        'suspended': '<i class="fas fa-pause"></i>',
        'inactive': '<i class="fas fa-times"></i>'
    };
    return icons[status] || '<i class="fas fa-question"></i>';
}

function getStatusText(status) {
    const texts = {
        'active': 'Activo',
        'suspended': 'Suspendido',
        'inactive': 'Inactivo'
    };
    return texts[status] || status;
}

function getInvitationStatusBadgeClass(status) {
    const classes = {
        'pendiente': 'bg-warning text-dark',
        'aceptada': 'bg-success',
        'expirada': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
}

function getInvitationStatusText(status) {
    const texts = {
        'pendiente': 'Pendiente',
        'aceptada': 'Aceptada',
        'expirada': 'Expirada'
    };
    return texts[status] || status;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(type, title, message) {
    Swal.fire({
        icon: type,
        title: title,
        text: message,
        confirmButtonColor: '#667eea'
    });
}

/**
 * Activar/desactivar acceso de usuario
 */
function toggleAccess(userId, enable) {
    const action = enable ? 'habilitar' : 'deshabilitar';
    const title = enable ? 'Habilitar Acceso' : 'Deshabilitar Acceso';
    const text = `¿Estás seguro de que quieres ${action} el acceso de este usuario?`;
    
    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: enable ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: enable ? 'Sí, habilitar' : 'Sí, deshabilitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'toggle_access');
            formData.append('user_id', userId);
            formData.append('enable', enable);
            formData.append('company_id', getCompanyId());
            
            fetch('controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Éxito', data.message);
                    loadUsers(); // Recargar la lista
                } else {
                    showAlert('error', 'Error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Error', 'Error de conexión');
            });
        }
    });
}

/**
 * Obtener ID de empresa actual
 */
function getCompanyId() {
    // Obtener del URL o de una variable global
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('company_id') || window.currentCompanyId || null;
}
