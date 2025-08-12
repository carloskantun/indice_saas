<?php
/**
 * MODALES MÓDULO PROCESOS Y TAREAS
 * Define todos los modales necesarios para el módulo
 */
?>

<!-- Modal Nuevo Proceso -->
<div class="modal fade" id="newProcessModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="newProcessForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus text-primary me-2"></i>
                        <?= __('new_process') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= __('process_name') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label"><?= __('process_description') ?></label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('department') ?></label>
                            <select class="form-select" name="department_id" id="processDepartmentSelect">
                                <option value=""><?= __('select_department') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('priority') ?></label>
                            <select class="form-select" name="priority">
                                <option value="low"><?= __('low_priority') ?></option>
                                <option value="medium" selected><?= __('medium_priority') ?></option>
                                <option value="high"><?= __('high_priority') ?></option>
                                <option value="critical"><?= __('critical_priority') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('estimated_duration') ?> (<?= __('hours') ?>)</label>
                            <input type="number" class="form-control" name="estimated_duration" min="0" step="0.5">
                        </div>
                        
                        <div class="col-12">
                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><?= __('process_steps') ?></h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addProcessStep()">
                                    <i class="fas fa-plus me-1"></i><?= __('add_step') ?>
                                </button>
                            </div>
                            <div id="processStepsContainer">
                                <!-- Los pasos se agregan dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?= __('create_process') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Proceso -->
<div class="modal fade" id="editProcessModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editProcessForm">
                <input type="hidden" name="process_id" id="editProcessId">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit text-primary me-2"></i>
                        <?= __('edit_process') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= __('process_name') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="editProcessName" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label"><?= __('process_description') ?></label>
                            <textarea class="form-control" name="description" id="editProcessDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('department') ?></label>
                            <select class="form-select" name="department_id" id="editProcessDepartment">
                                <option value=""><?= __('select_department') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('priority') ?></label>
                            <select class="form-select" name="priority" id="editProcessPriority">
                                <option value="low"><?= __('low_priority') ?></option>
                                <option value="medium"><?= __('medium_priority') ?></option>
                                <option value="high"><?= __('high_priority') ?></option>
                                <option value="critical"><?= __('critical_priority') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('process_status') ?></label>
                            <select class="form-select" name="status" id="editProcessStatus">
                                <option value="draft"><?= __('draft') ?></option>
                                <option value="active"><?= __('active') ?></option>
                                <option value="paused"><?= __('paused') ?></option>
                                <option value="completed"><?= __('completed') ?></option>
                                <option value="cancelled"><?= __('cancelled') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('estimated_duration') ?> (<?= __('hours') ?>)</label>
                            <input type="number" class="form-control" name="estimated_duration" id="editProcessDuration" min="0" step="0.5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?= __('save_changes') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nueva Tarea -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="newTaskForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus text-success me-2"></i>
                        <?= __('new_task') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= __('task_title') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label"><?= __('task_description') ?></label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('process') ?></label>
                            <select class="form-select" name="process_id" id="taskProcessSelect">
                                <option value=""><?= __('independent_task') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('assigned_to') ?></label>
                            <select class="form-select" name="assigned_to" id="taskAssignedSelect">
                                <option value=""><?= __('unassigned') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('priority') ?></label>
                            <select class="form-select" name="priority">
                                <option value="low"><?= __('low_priority') ?></option>
                                <option value="medium" selected><?= __('medium_priority') ?></option>
                                <option value="high"><?= __('high_priority') ?></option>
                                <option value="critical"><?= __('critical_priority') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('department') ?></label>
                            <select class="form-select" name="department_id" id="taskDepartmentSelect">
                                <option value=""><?= __('select_department') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('estimated_hours') ?></label>
                            <input type="number" class="form-control" name="estimated_hours" min="0" step="0.5">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('due_date') ?></label>
                            <input type="datetime-local" class="form-control" name="due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i><?= __('create_task') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Tarea -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editTaskForm">
                <input type="hidden" name="task_id" id="editTaskId">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit text-success me-2"></i>
                        <?= __('edit_task') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= __('task_title') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="editTaskTitle" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label"><?= __('task_description') ?></label>
                            <textarea class="form-control" name="description" id="editTaskDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('priority') ?></label>
                            <select class="form-select" name="priority" id="editTaskPriority">
                                <option value="low"><?= __('low_priority') ?></option>
                                <option value="medium"><?= __('medium_priority') ?></option>
                                <option value="high"><?= __('high_priority') ?></option>
                                <option value="critical"><?= __('critical_priority') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('task_status') ?></label>
                            <select class="form-select" name="status" id="editTaskStatus">
                                <option value="pending"><?= __('pending') ?></option>
                                <option value="in_progress"><?= __('in_progress') ?></option>
                                <option value="review"><?= __('review') ?></option>
                                <option value="completed"><?= __('completed') ?></option>
                                <option value="cancelled"><?= __('cancelled') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?= __('completion_percentage') ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="completion_percentage" id="editTaskProgress" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('due_date') ?></label>
                            <input type="datetime-local" class="form-control" name="due_date" id="editTaskDueDate">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('estimated_hours') ?></label>
                            <input type="number" class="form-control" name="estimated_hours" id="editTaskEstimatedHours" min="0" step="0.5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i><?= __('save_changes') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Completar Tarea -->
<div class="modal fade" id="completeTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="completeTaskForm">
                <input type="hidden" name="task_id" id="completeTaskId">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <?= __('complete_task') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= __('mark_task_as_completed_confirmation') ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('actual_hours') ?></label>
                        <input type="number" class="form-control" name="actual_hours" step="0.5" min="0">
                        <div class="form-text"><?= __('actual_time_spent_on_task') ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('completion_notes') ?></label>
                        <textarea class="form-control" name="completion_notes" rows="3" placeholder="<?= __('optional_completion_notes') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i><?= __('mark_completed') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reasignar Tarea -->
<div class="modal fade" id="reassignTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reassignTaskForm">
                <input type="hidden" name="task_id" id="reassignTaskId">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-tag text-primary me-2"></i>
                        <?= __('reassign_task') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('current_assigned') ?></label>
                        <input type="text" class="form-control" id="currentAssignedName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('new_assigned') ?> <span class="text-danger">*</span></label>
                        <select class="form-select" name="assigned_to" id="reassignTaskEmployee" required>
                            <option value=""><?= __('select_employee') ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('reassignment_reason') ?></label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="<?= __('reason_for_reassignment') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-exchange-alt me-2"></i><?= __('reassign') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Asignación Múltiple -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="bulkAssignForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-users text-primary me-2"></i>
                        <?= __('assign_multiple_tasks') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="selectedTasksCount">0</span> <?= __('tasks_selected') ?>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= __('assign_to') ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_to" id="bulkAssignEmployee" required>
                                <option value=""><?= __('select_employee') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('due_date') ?></label>
                            <input type="datetime-local" class="form-control" name="due_date">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('priority') ?></label>
                            <select class="form-select" name="priority">
                                <option value=""><?= __('keep_current') ?></option>
                                <option value="low"><?= __('low_priority') ?></option>
                                <option value="medium"><?= __('medium_priority') ?></option>
                                <option value="high"><?= __('high_priority') ?></option>
                                <option value="critical"><?= __('critical_priority') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><?= __('department') ?></label>
                            <select class="form-select" name="department_id" id="bulkAssignDepartment">
                                <option value=""><?= __('keep_current') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label"><?= __('assignment_notes') ?></label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="<?= __('notes_for_assignment') ?>"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i><?= __('assign_tasks') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Detalles del Proceso -->
<div class="modal fade" id="viewProcessModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye text-info me-2"></i>
                    <?= __('process_details') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="processDetailsContent">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= __('close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Detalles de la Tarea -->
<div class="modal fade" id="viewTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye text-info me-2"></i>
                    <?= __('task_details') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="taskDetailsContent">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= __('close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Actualizar Progreso -->
<div class="modal fade" id="updateProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updateProgressForm">
                <input type="hidden" name="task_id" id="updateProgressTaskId">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-line text-warning me-2"></i>
                        <?= __('update_progress') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('current_progress') ?></label>
                        <div class="progress mb-2">
                            <div class="progress-bar" id="currentProgressBar" role="progressbar"></div>
                        </div>
                        <small class="text-muted" id="currentProgressText"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('new_progress') ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="range" class="form-range" name="completion_percentage" id="progressSlider" min="0" max="100" step="5">
                            <input type="number" class="form-control" id="progressInput" min="0" max="100" style="max-width: 80px;">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('progress_notes') ?></label>
                        <textarea class="form-control" name="progress_notes" rows="3" placeholder="<?= __('describe_progress_made') ?>"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('hours_worked') ?></label>
                        <input type="number" class="form-control" name="hours_worked" step="0.5" min="0" placeholder="<?= __('hours_spent_today') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i><?= __('update_progress') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Template para paso de proceso
function getProcessStepTemplate(index) {
    return `
        <div class="card mb-2 process-step" data-index="${index}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0"><?= __('step') ?> ${index + 1}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProcessStep(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" name="steps[${index}][name]" placeholder="<?= __('step_name') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" name="steps[${index}][estimated_hours]" placeholder="<?= __('hours') ?>" min="0" step="0.5">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="steps[${index}][responsible_role]">
                            <option value=""><?= __('responsible') ?></option>
                            <option value="admin"><?= __('admin') ?></option>
                            <option value="moderator"><?= __('moderator') ?></option>
                            <option value="user"><?= __('user') ?></option>
                        </select>
                    </div>
                    <div class="col-12">
                        <textarea class="form-control form-control-sm" name="steps[${index}][description]" placeholder="<?= __('step_description') ?>" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Funciones para gestión de pasos
function addProcessStep() {
    const container = document.getElementById('processStepsContainer');
    const index = container.children.length;
    container.insertAdjacentHTML('beforeend', getProcessStepTemplate(index));
}

function removeProcessStep(index) {
    const step = document.querySelector(`.process-step[data-index="${index}"]`);
    if (step) {
        step.remove();
        // Reindexar pasos restantes
        reindexProcessSteps();
    }
}

function reindexProcessSteps() {
    const steps = document.querySelectorAll('.process-step');
    steps.forEach((step, newIndex) => {
        step.setAttribute('data-index', newIndex);
        step.querySelector('.card-title').textContent = `<?= __('step') ?> ${newIndex + 1}`;
        
        // Actualizar nombres de campos
        const inputs = step.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name && name.includes('steps[')) {
                const newName = name.replace(/steps\[\d+\]/, `steps[${newIndex}]`);
                input.setAttribute('name', newName);
            }
        });
        
        // Actualizar botón de eliminar
        const deleteBtn = step.querySelector('button[onclick]');
        if (deleteBtn) {
            deleteBtn.setAttribute('onclick', `removeProcessStep(${newIndex})`);
        }
    });
}
</script>
