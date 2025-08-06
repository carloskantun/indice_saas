<?php
// Iniciar sesión y verificar autenticación
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/index.php');
    exit();
}

// Verificar que tenga permisos de administrador
$user_role = $_SESSION['current_role'] ?? 'user';
if (!in_array($user_role, ['superadmin', 'admin', 'root'])) {
    header('Location: ../acceso_denegado.php');
    exit();
}

// Incluir configuraciones necesarias
require_once '../config.php';
require_once 'permissions_manager.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Permisos Granulares - Índice SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .permissions-matrix {
            max-width: 100%;
            overflow-x: auto;
        }
        .permission-checkbox {
            transform: scale(1.2);
        }
        .module-header {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .user-card {
            transition: all 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .permission-indicator {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
        }
        .permission-view { background: #28a745; }
        .permission-create { background: #17a2b8; }
        .permission-edit { background: #ffc107; }
        .permission-delete { background: #dc3545; }
        .permission-admin { background: #6f42c1; }
        .role-badge {
            position: relative;
            overflow: hidden;
        }
        .role-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 2s infinite;
        }
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        .compact-table th, .compact-table td {
            padding: 0.5rem 0.25rem;
            font-size: 0.875rem;
        }
        .btn-sm-custom {
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../admin/">
                <i class="fas fa-shield-alt"></i> Gestión de Permisos
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/">
                            <i class="fas fa-arrow-left"></i> Volver al Admin
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h1 class="h3 mb-0">
                                    <i class="fas fa-shield-alt text-primary"></i> 
                                    Sistema de Permisos Granulares
                                </h1>
                                <p class="text-muted mb-0">Gestiona permisos específicos por usuario, empresa y módulo</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" onclick="showRoleTemplates()">
                                    <i class="fas fa-user-tag"></i> Plantillas de Roles
                                </button>
                                <button class="btn btn-success" onclick="exportPermissions()">
                                    <i class="fas fa-download"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Empresa</label>
                                <select class="form-select" id="company_filter">
                                    <option value="">Seleccionar empresa...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="user_filter" placeholder="Buscar usuario...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Módulo</label>
                                <select class="form-select" id="module_filter">
                                    <option value="">Todos los módulos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vista</label>
                                <select class="form-select" id="view_mode">
                                    <option value="cards">Tarjetas</option>
                                    <option value="matrix">Matriz</option>
                                    <option value="list">Lista</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div id="main-content">
            <!-- El contenido se cargará dinámicamente aquí -->
        </div>
    </div>

    <!-- Modal para editar permisos de usuario -->
    <div class="modal fade" id="editPermissionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Editar Permisos de Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="permissions-form">
                        <!-- Formulario se carga dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveUserPermissions()">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para plantillas de roles -->
    <div class="modal fade" id="roleTemplatesModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-tag"></i> Plantillas de Roles
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="role-templates-content">
                        <!-- Contenido se carga dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentCompanyId = null;
        let currentUserId = null;
        let systemModules = [];
        let permissionsData = {};

        // Inicializar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadCompanies();
            loadModules();
            loadPermissionsView();
            
            // Event listeners para filtros
            document.getElementById('company_filter').addEventListener('change', function() {
                currentCompanyId = this.value;
                loadPermissionsView();
            });
            
            document.getElementById('view_mode').addEventListener('change', function() {
                loadPermissionsView();
            });
            
            document.getElementById('user_filter').addEventListener('input', function() {
                filterUsers(this.value);
            });
        });

        // Cargar empresas
        async function loadCompanies() {
            try {
                const response = await fetch('controller.php?action=get_companies');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('company_filter');
                    select.innerHTML = '<option value="">Seleccionar empresa...</option>';
                    
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.id;
                        option.textContent = company.name;
                        select.appendChild(option);
                    });
                    
                    // Seleccionar la primera empresa por defecto
                    if (data.companies.length > 0) {
                        select.value = data.companies[0].id;
                        currentCompanyId = data.companies[0].id;
                    }
                }
            } catch (error) {
                console.error('Error cargando empresas:', error);
            }
        }

        // Cargar módulos del sistema
        async function loadModules() {
            try {
                const response = await fetch('controller.php?action=get_modules');
                const data = await response.json();
                
                if (data.success) {
                    systemModules = data.modules;
                    
                    const select = document.getElementById('module_filter');
                    select.innerHTML = '<option value="">Todos los módulos</option>';
                    
                    data.modules.forEach(module => {
                        const option = document.createElement('option');
                        option.value = module.id;
                        option.textContent = `${module.name} (${module.category})`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error cargando módulos:', error);
            }
        }

        // Cargar vista de permisos
        async function loadPermissionsView() {
            if (!currentCompanyId) return;
            
            const viewMode = document.getElementById('view_mode').value;
            const mainContent = document.getElementById('main-content');
            
            mainContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            
            try {
                const response = await fetch(`controller.php?action=get_permission_matrix&company_id=${currentCompanyId}`);
                const data = await response.json();
                
                if (data.success) {
                    permissionsData = data;
                    
                    switch (viewMode) {
                        case 'cards':
                            renderCardsView(data);
                            break;
                        case 'matrix':
                            renderMatrixView(data);
                            break;
                        case 'list':
                            renderListView(data);
                            break;
                    }
                } else {
                    mainContent.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error cargando permisos:', error);
                mainContent.innerHTML = '<div class="alert alert-danger">Error cargando datos</div>';
            }
        }

        // Renderizar vista de tarjetas
        function renderCardsView(data) {
            let html = '<div class="row">';
            
            Object.values(data.matrix).forEach(userInfo => {
                const user = userInfo.user_info;
                const permissions = userInfo.permissions;
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card user-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">${user.user_name || 'Usuario'}</h6>
                                    <small class="text-muted">${user.user_email || ''}</small>
                                </div>
                                <span class="badge role-badge bg-primary">${user.user_role || 'user'}</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted">Permisos en ${Object.keys(permissions).length} módulos</small>
                                </div>
                                <div class="permission-indicators mb-3">
                                    ${renderPermissionIndicators(permissions)}
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="editUserPermissions(${user.user_id})">
                                    <i class="fas fa-edit"></i> Editar Permisos
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('main-content').innerHTML = html;
        }

        // Renderizar vista de matriz
        function renderMatrixView(data) {
            let html = `
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Matriz de Permisos</h5>
                    </div>
                    <div class="card-body permissions-matrix">
                        <table class="table table-bordered compact-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
            `;
            
            // Headers de módulos
            systemModules.forEach(module => {
                html += `<th class="text-center" title="${module.description}">
                    <i class="${module.icon}"></i><br>
                    <small>${module.name}</small>
                </th>`;
            });
            
            html += '</tr></thead><tbody>';
            
            // Filas de usuarios
            Object.values(data.matrix).forEach(userInfo => {
                const user = userInfo.user_info;
                const permissions = userInfo.permissions;
                
                html += `
                    <tr>
                        <td>
                            <strong>${user.user_name || 'Usuario'}</strong><br>
                            <small class="text-muted">${user.user_email || ''}</small>
                        </td>
                        <td><span class="badge bg-primary">${user.user_role || 'user'}</span></td>
                `;
                
                // Celdas de permisos por módulo
                systemModules.forEach(module => {
                    const modulePermissions = permissions[module.id];
                    html += `<td class="text-center">
                        ${renderModulePermissionCell(modulePermissions, user.user_id, module.id)}
                    </td>`;
                });
                
                html += '</tr>';
            });
            
            html += '</tbody></table></div></div>';
            document.getElementById('main-content').innerHTML = html;
        }

        // Renderizar vista de lista
        function renderListView(data) {
            let html = '<div class="accordion" id="userPermissionsAccordion">';
            
            Object.values(data.matrix).forEach((userInfo, index) => {
                const user = userInfo.user_info;
                const permissions = userInfo.permissions;
                
                html += `
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse${user.user_id}">
                                <div class="d-flex w-100 justify-content-between align-items-center me-3">
                                    <div>
                                        <strong>${user.user_name || 'Usuario'}</strong>
                                        <small class="text-muted ms-2">${user.user_email || ''}</small>
                                    </div>
                                    <span class="badge bg-primary">${user.user_role || 'user'}</span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse${user.user_id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" 
                             data-bs-parent="#userPermissionsAccordion">
                            <div class="accordion-body">
                                ${renderUserPermissionsList(permissions, user.user_id)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('main-content').innerHTML = html;
        }

        // Funciones auxiliares de renderizado
        function renderPermissionIndicators(permissions) {
            let html = '';
            Object.values(permissions).forEach(perm => {
                if (perm.can_view) html += '<span class="permission-indicator permission-view" title="Ver"></span>';
                if (perm.can_create) html += '<span class="permission-indicator permission-create" title="Crear"></span>';
                if (perm.can_edit) html += '<span class="permission-indicator permission-edit" title="Editar"></span>';
                if (perm.can_delete) html += '<span class="permission-indicator permission-delete" title="Eliminar"></span>';
                if (perm.can_admin) html += '<span class="permission-indicator permission-admin" title="Administrar"></span>';
            });
            return html || '<span class="text-muted">Sin permisos</span>';
        }

        function renderModulePermissionCell(permissions, userId, moduleId) {
            if (!permissions) return '<span class="text-muted">-</span>';
            
            let html = '<div class="d-flex flex-column">';
            const perms = ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_admin'];
            const icons = ['eye', 'plus', 'edit', 'trash', 'crown'];
            const colors = ['success', 'info', 'warning', 'danger', 'primary'];
            
            perms.forEach((perm, index) => {
                if (permissions[perm]) {
                    html += `<i class="fas fa-${icons[index]} text-${colors[index]}" title="${perm}"></i>`;
                }
            });
            
            html += '</div>';
            return html;
        }

        function renderUserPermissionsList(permissions, userId) {
            let html = '<div class="row">';
            
            systemModules.forEach(module => {
                const modulePermissions = permissions[module.id];
                
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header module-header py-2">
                                <h6 class="mb-0">
                                    <i class="${module.icon}"></i> ${module.name}
                                </h6>
                            </div>
                            <div class="card-body py-2">
                                ${renderPermissionCheckboxes(modulePermissions, userId, module.id)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            return html;
        }

        function renderPermissionCheckboxes(permissions, userId, moduleId) {
            if (!permissions) permissions = {};
            
            const perms = [
                { key: 'can_view', label: 'Ver', icon: 'eye' },
                { key: 'can_create', label: 'Crear', icon: 'plus' },
                { key: 'can_edit', label: 'Editar', icon: 'edit' },
                { key: 'can_delete', label: 'Eliminar', icon: 'trash' },
                { key: 'can_admin', label: 'Administrar', icon: 'crown' }
            ];
            
            let html = '';
            perms.forEach(perm => {
                const checked = permissions[perm.key] ? 'checked' : '';
                html += `
                    <div class="form-check form-check-inline">
                        <input class="form-check-input permission-checkbox" type="checkbox" 
                               id="${userId}_${moduleId}_${perm.key}" ${checked}
                               onchange="updatePermission(${userId}, '${moduleId}', '${perm.key}', this.checked)">
                        <label class="form-check-label" for="${userId}_${moduleId}_${perm.key}">
                            <i class="fas fa-${perm.icon}"></i> ${perm.label}
                        </label>
                    </div>
                `;
            });
            
            return html;
        }

        // Funciones de interacción
        async function updatePermission(userId, moduleId, permissionType, value) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_permissions');
                formData.append('user_id', userId);
                formData.append('company_id', currentCompanyId);
                formData.append('module_id', moduleId);
                formData.append('permissions', JSON.stringify({[permissionType]: value ? 1 : 0}));
                
                const response = await fetch('controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Permiso actualizado',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error actualizando permiso:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo actualizar el permiso'
                });
            }
        }

        function editUserPermissions(userId) {
            currentUserId = userId;
            // Implementar modal de edición detallada
            const modal = new bootstrap.Modal(document.getElementById('editPermissionsModal'));
            modal.show();
        }

        function showRoleTemplates() {
            // Implementar modal de plantillas de roles
            const modal = new bootstrap.Modal(document.getElementById('roleTemplatesModal'));
            modal.show();
        }

        function exportPermissions() {
            // Implementar exportación de permisos
            Swal.fire({
                icon: 'info',
                title: 'Exportar Permisos',
                text: 'Función de exportación en desarrollo'
            });
        }

        function filterUsers(searchTerm) {
            // Implementar filtrado de usuarios
            const userCards = document.querySelectorAll('.user-card');
            userCards.forEach(card => {
                const userName = card.querySelector('h6').textContent.toLowerCase();
                const userEmail = card.querySelector('.text-muted').textContent.toLowerCase();
                
                if (userName.includes(searchTerm.toLowerCase()) || userEmail.includes(searchTerm.toLowerCase())) {
                    card.closest('.col-md-6').style.display = 'block';
                } else {
                    card.closest('.col-md-6').style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
