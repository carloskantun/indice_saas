<?php
/**
 * MODALES DEL MÓDULO RECURSOS HUMANOS
 * Sistema SaaS Indice
 */
?>

<!-- Modal Empleado -->
<div class="modal fade modal-hr" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Empleado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="employeeForm">
                <div class="modal-body">
                    <div class="row">
                        <!-- Información Personal -->
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Información Personal
                            </h6>
                        </div>
                        
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
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="empleado@empresa.com">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="555-123-4567">
                            </div>
                        </div>
                        
                        <!-- Información Laboral -->
                        <div class="col-12 mt-3">
                            <hr>
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-briefcase me-2"></i>Información Laboral
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
                            <hr>
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
                                    <option value="Semanal">Semanal</option>
                                    <option value="Quincenal">Quincenal</option>
                                    <option value="Mensual" selected>Mensual</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estatus</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Activo" selected>Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                    <option value="Vacaciones">Vacaciones</option>
                                    <option value="Licencia">Licencia</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-hr-primary">
                        <i class="fas fa-save me-2"></i>Guardar Empleado
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
                    <div class="text-center py-4">
                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Funcionalidad en desarrollo</h6>
                        <p class="text-muted">Pronto podrás gestionar departamentos desde aquí.</p>
                    </div>
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
