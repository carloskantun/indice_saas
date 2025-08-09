<?php
/**
 * MODALES DEL MÓDULO RECURSOS HUMANOS
 * Sistema SaaS Indice
 */
?>

<!-- Modal Empleado -->
<div class="modal fade modal-hr" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Empleado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="employeeForm">
                <div class="modal-body">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" 
                                    data-bs-target="#personal" type="button" role="tab">
                                <i class="fas fa-user me-2"></i>Información Personal
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="laboral-tab" data-bs-toggle="tab" 
                                    data-bs-target="#laboral" type="button" role="tab">
                                <i class="fas fa-briefcase me-2"></i>Información Laboral
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="access-tab" data-bs-toggle="tab" 
                                    data-bs-target="#access" type="button" role="tab">
                                <i class="fas fa-key me-2"></i>Permisos y Acceso
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="employeeTabsContent">
                        
                        <!-- Tab 1: Información Personal -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="employee_number" class="form-label">Número de Empleado</label>
                                        <input type="text" class="form-control" id="employee_number" name="employee_number" 
                                               placeholder="Opcional - Se generará automáticamente">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="hire_date" class="form-label">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Apellido(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="empleado@empresa.com" required>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Se verificará si el usuario ya existe en el sistema
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="+52 555-123-4567">
                                    </div>
                                </div>

                                <!-- RFC/ID Fiscal -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fiscal_id" class="form-label">RFC/DNI/ID Fiscal</label>
                                        <input type="text" class="form-control" id="fiscal_id" name="fiscal_id" 
                                               placeholder="RFC123456789">
                                    </div>
                                </div>

                                <!-- Status de Usuario -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Estado del Usuario</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="send_invitation" name="send_invitation" checked>
                                            <label class="form-check-label" for="send_invitation">
                                                Enviar invitación por email
                                            </label>
                                        </div>
                                        <div class="form-text" id="user_status_info">
                                            <i class="fas fa-envelope me-1"></i>
                                            Se enviará una invitación para registrarse en el sistema
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 2: Información Laboral -->
                        <div class="tab-pane fade" id="laboral" role="tabpanel">
                            <div class="row">
                                <!-- Asignación Organizacional -->
                                <div class="col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-building me-2"></i>Asignación Organizacional
                                    </h6>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="assigned_company_id" class="form-label">Empresa <span class="text-danger">*</span></label>
                                        <select class="form-select" id="assigned_company_id" name="assigned_company_id" required>
                                            <option value="<?php echo $company_id; ?>" selected>
                                                <?php echo $_SESSION['company_name'] ?? 'Empresa Actual'; ?>
                                            </option>
                                        </select>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Empresa donde trabajará el empleado
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="assigned_unit_id" class="form-label">Unidad de Negocio</label>
                                        <select class="form-select" id="assigned_unit_id" name="assigned_unit_id">
                                            <option value="<?php echo $unit_id; ?>" selected>
                                                <?php echo $_SESSION['unit_name'] ?? 'Unidad Actual'; ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="assigned_business_id" class="form-label">Negocio <span class="text-danger">*</span></label>
                                        <select class="form-select" id="assigned_business_id" name="assigned_business_id" required>
                                            <option value="<?php echo $business_id; ?>" selected>
                                                <?php echo $_SESSION['business_name'] ?? 'Negocio Actual'; ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Información de Puesto -->
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-briefcase me-2"></i>Información del Puesto
                                    </h6>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="department_id" class="form-label">Departamento <span class="text-danger">*</span></label>
                                        <select class="form-select select2" id="department_id" name="department_id" required>
                                            <option value="">Seleccionar departamento</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['id']; ?>">
                                                    <?php echo htmlspecialchars($dept['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="position_id" class="form-label">Posición <span class="text-danger">*</span></label>
                                        <select class="form-select select2" id="position_id" name="position_id" required>
                                            <option value="">Seleccionar posición</option>
                                            <?php foreach ($positions as $pos): ?>
                                                <option value="<?php echo $pos['id']; ?>">
                                                    <?php echo htmlspecialchars($pos['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="employment_type" class="form-label">Tipo de Empleo</label>
                                        <select class="form-select" id="employment_type" name="employment_type">
                                            <option value="Tiempo_Completo">Tiempo Completo</option>
                                            <option value="Medio_Tiempo">Medio Tiempo</option>
                                            <option value="Temporal">Temporal</option>
                                            <option value="Freelance">Freelance</option>
                                            <option value="Practicante">Practicante</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contract_type" class="form-label">Tipo de Contrato</label>
                                        <select class="form-select" id="contract_type" name="contract_type">
                                            <option value="Indefinido">Indefinido</option>
                                            <option value="Temporal">Temporal</option>
                                            <option value="Por_Obra">Por Obra</option>
                                            <option value="Practicas">Prácticas</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Información Salarial -->
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-dollar-sign me-2"></i>Información Salarial
                                    </h6>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="salary" class="form-label">Salario</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="salary" name="salary" 
                                                   min="0" step="0.01" value="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_frequency" class="form-label">Frecuencia de Pago</label>
                                        <select class="form-select" id="payment_frequency" name="payment_frequency">
                                            <option value="Mensual">Mensual</option>
                                            <option value="Quincenal">Quincenal</option>
                                            <option value="Semanal">Semanal</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Estatus del Empleado</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Activo">Activo</option>
                                            <option value="Inactivo">Inactivo</option>
                                            <option value="Vacaciones">Vacaciones</option>
                                            <option value="Licencia">Licencia</option>
                                            <option value="Baja">Baja</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="sync_with_expenses" name="sync_with_expenses" checked>
                                            <label class="form-check-label" for="sync_with_expenses">
                                                Sincronizar salario con módulo de gastos
                                            </label>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-link me-1"></i>
                                            Se creará automáticamente el gasto recurrente de nómina
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notas Adicionales</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Observaciones sobre el empleado..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 3: Permisos y Acceso -->
                        <div class="tab-pane fade" id="access" role="tabpanel">
                            <div class="row">
                                <!-- Detección de Usuario Existente -->
                                <div class="col-12">
                                    <div id="user_detection_alert" class="alert alert-info d-none">
                                        <i class="fas fa-user-check me-2"></i>
                                        <strong>Usuario encontrado en el sistema:</strong>
                                        <span id="existing_user_info"></span>
                                        <br><small>Se precargarán sus datos básicos</small>
                                    </div>
                                </div>

                                <!-- Templates de Roles -->
                                <div class="col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-user-tag me-2"></i>Plantillas de Roles
                                    </h6>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Seleccionar plantilla de rol:</label>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <div class="card role-template" data-role="admin">
                                                    <div class="card-body text-center p-3">
                                                        <i class="fas fa-crown fa-2x text-warning mb-2"></i>
                                                        <h6 class="card-title">Gerente/Admin</h6>
                                                        <p class="card-text small">Acceso completo a módulos y gestión de usuarios</p>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="role_template" id="role_admin" value="admin">
                                                            <label class="form-check-label" for="role_admin">Seleccionar</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card role-template" data-role="moderator">
                                                    <div class="card-body text-center p-3">
                                                        <i class="fas fa-user-tie fa-2x text-info mb-2"></i>
                                                        <h6 class="card-title">Supervisor</h6>
                                                        <p class="card-text small">Supervisión y gestión operativa limitada</p>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="role_template" id="role_moderator" value="moderator">
                                                            <label class="form-check-label" for="role_moderator">Seleccionar</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card role-template" data-role="user">
                                                    <div class="card-body text-center p-3">
                                                        <i class="fas fa-user fa-2x text-success mb-2"></i>
                                                        <h6 class="card-title">Empleado</h6>
                                                        <p class="card-text small">Acceso básico según módulos asignados</p>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="role_template" id="role_user" value="user" checked>
                                                            <label class="form-check-label" for="role_user">Seleccionar</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Asignación de Módulos -->
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-puzzle-piece me-2"></i>Asignación de Módulos
                                    </h6>
                                </div>

                                <div class="col-12">
                                    <div class="row g-3" id="modules_assignment">
                                        <!-- Módulos disponibles se cargarán dinámicamente -->
                                        <div class="col-md-6">
                                            <div class="card border-light">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-users fa-2x text-primary me-3"></i>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">Recursos Humanos</h6>
                                                            <small class="text-muted">Gestión de empleados y permisos</small>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="module_hr" name="modules[]" value="human-resources">
                                                        </div>
                                                    </div>
                                                    <!-- Permisos específicos -->
                                                    <div class="module-permissions mt-2 d-none" id="permissions_hr">
                                                        <small class="text-muted">Permisos específicos:</small>
                                                        <div class="form-check form-check-sm">
                                                            <input class="form-check-input" type="checkbox" id="perm_hr_view" name="permissions[]" value="employees.view">
                                                            <label class="form-check-label small" for="perm_hr_view">Ver empleados</label>
                                                        </div>
                                                        <div class="form-check form-check-sm">
                                                            <input class="form-check-input" type="checkbox" id="perm_hr_create" name="permissions[]" value="employees.create">
                                                            <label class="form-check-label small" for="perm_hr_create">Crear empleados</label>
                                                        </div>
                                                        <div class="form-check form-check-sm">
                                                            <input class="form-check-input" type="checkbox" id="perm_hr_manage" name="permissions[]" value="hr.manage_permissions">
                                                            <label class="form-check-label small" for="perm_hr_manage">Gestionar permisos</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card border-light">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-dollar-sign fa-2x text-success me-3"></i>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">Gastos</h6>
                                                            <small class="text-muted">Gestión de gastos e ingresos</small>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="module_expenses" name="modules[]" value="expenses">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Más módulos se agregarán dinámicamente -->
                                    </div>
                                </div>

                                <!-- Configuración de Invitación -->
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-envelope me-2"></i>Configuración de Invitación
                                    </h6>
                                </div>

                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="create_user_account" name="create_user_account" checked>
                                                        <label class="form-check-label" for="create_user_account">
                                                            <strong>Crear cuenta de usuario</strong>
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Si está desactivado, solo se registrará como empleado interno</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="auto_send_invitation" name="auto_send_invitation" checked>
                                                        <label class="form-check-label" for="auto_send_invitation">
                                                            <strong>Enviar invitación automáticamente</strong>
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Se enviará email con instrucciones de acceso</small>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3" id="invitation_preview" style="display: none;">
                                                <label class="form-label">Vista previa del mensaje:</label>
                                                <div class="bg-white p-3 border rounded">
                                                    <small class="text-muted">
                                                        <strong>Asunto:</strong> Invitación para unirse a [Empresa] en Indice SaaS<br>
                                                        <strong>Mensaje:</strong> Has sido invitado a formar parte del equipo como [Posición] en [Departamento]...
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-primary" id="btn_save_draft">
                        <i class="fas fa-save me-2"></i>Guardar como Borrador
                    </button>
                    <button type="submit" class="btn btn-hr-primary">
                        <i class="fas fa-user-plus me-2"></i>Crear Empleado y Enviar Invitación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal KPIs -->
<div class="modal fade modal-hr" id="kpisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-pie me-2"></i>KPIs Recursos Humanos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="kpisContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando estadísticas...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-hr-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Departamentos -->
<div class="modal fade modal-hr" id="departmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-building me-2"></i>Gestión de Departamentos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-hr-success" id="btnNewDepartment">
                            <i class="fas fa-plus me-2"></i>Nuevo Departamento
                        </button>
                    </div>
                </div>
                
                <div id="departmentsContent">
                    <h6 class="mb-3">Departamentos más comunes</h6>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">Recursos Humanos</li>
                        <li class="list-group-item">Finanzas</li>
                        <li class="list-group-item">Contabilidad</li>
                        <li class="list-group-item">Ventas</li>
                        <li class="list-group-item">Marketing</li>
                        <li class="list-group-item">Operaciones</li>
                        <li class="list-group-item">Tecnología de la Información (TI)</li>
                        <li class="list-group-item">Atención al Cliente</li>
                        <li class="list-group-item">Logística</li>
                        <li class="list-group-item">Compras</li>
                        <li class="list-group-item">Producción</li>
                        <li class="list-group-item">Calidad</li>
                        <li class="list-group-item">Legal</li>
                    </ul>
                    <hr>
                    <h6 class="mb-3">Crear nuevo puesto</h6>
                    <form id="formNewPosition">
                        <div class="mb-3">
                            <label for="positionName" class="form-label">Nombre del Puesto</label>
                            <input type="text" class="form-control" id="positionName" name="positionName" placeholder="Ejemplo: Analista de Datos" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Crear Puesto</button>
                    </form>
                    <div id="positionSuccessMsg" class="alert alert-success mt-3 d-none">¡Puesto creado exitosamente!</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Posiciones -->
<div class="modal fade modal-hr" id="positionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-briefcase me-2"></i>Gestión de Posiciones
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-hr-warning" id="btnNewPosition">
                            <i class="fas fa-plus me-2"></i>Nueva Posición
                        </button>
                    </div>
                </div>
                
                <div id="positionsContent">
                    <div class="text-center py-4">
                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Funcionalidad en desarrollo</h6>
                        <p class="text-muted">Pronto podrás gestionar posiciones desde aquí.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">¿Estás seguro de realizar esta acción?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales para los modales */
.modal-hr .form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.modal-hr .form-control:focus,
.modal-hr .form-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.modal-hr .input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #6c757d;
}

.modal-hr hr {
    margin: 1.5rem 0;
    border-color: #e9ecef;
}

.modal-hr .text-danger {
    color: #e74c3c !important;
}

/* KPI Cards en modal */
#kpisContent .kpi-card {
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

#kpisContent .kpi-card:hover {
    transform: translateY(-3px);
}

/* Loading states */
.loading-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000;
}

.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.6;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }
    
    .modal-hr .modal-body {
        padding: 1rem;
    }
    
    .modal-hr .row > [class*="col-"] {
        margin-bottom: 1rem;
    }
}
</style>
