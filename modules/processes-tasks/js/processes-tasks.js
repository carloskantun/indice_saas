/**
 * JAVASCRIPT MÓDULO PROCESOS Y TAREAS
 * Funcionalidad principal del módulo
 */

// Objeto principal del módulo
const ProcessesTasksApp = {
    // Configuración
    config: {
        apiUrl: 'controller.php',
        currentTab: 'processes',
        itemsPerPage: 25,
        currentPage: 1,
        sortField: null,
        sortDirection: 'asc',
        filters: {},
        selectedTasks: []
    },

    // Cache para datos
    cache: {
        departments: [],
        employees: [],
        processes: [],
        tasks: []
    },

    // Inicialización
    init() {
        console.log('Inicializando módulo Procesos y Tareas...');

        // Cargar configuración desde el DOM
        if (window.ProcessesTasksConfig) {
            Object.assign(this.config, window.ProcessesTasksConfig);
        }

        this.bindEvents();
        this.loadInitialData();
        this.setupColumnDragDrop();
        this.loadProcesses();
    },

    // Vincular eventos
    bindEvents() {
        // Pestañas principales
        $('#mainTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
            const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
            this.config.currentTab = tabId;
            this.onTabChange(tabId);
        });

        // Formularios de modales
        $('#newProcessForm').on('submit', (e) => this.handleProcessSubmit(e, 'create'));
        $('#editProcessForm').on('submit', (e) => this.handleProcessSubmit(e, 'update'));
        $('#newTaskForm').on('submit', (e) => this.handleTaskSubmit(e, 'create'));
        $('#editTaskForm').on('submit', (e) => this.handleTaskSubmit(e, 'update'));
        $('#completeTaskForm').on('submit', (e) => this.handleCompleteTask(e));
        $('#reassignTaskForm').on('submit', (e) => this.handleReassignTask(e));
        $('#bulkAssignForm').on('submit', (e) => this.handleBulkAssign(e));
        $('#updateProgressForm').on('submit', (e) => this.handleUpdateProgress(e));

        // Filtros
        $('#filterProcessStatus, #filterProcessPriority, #filterProcessDepartment').on('change', () => {
            this.applyProcessFilters();
        });

        $('#filterTaskStatus, #filterTaskPriority, #filterTaskAssigned, #filterTaskDepartment, #filterTaskDue').on('change', () => {
            this.applyTaskFilters();
        });

        $('#filterTaskSearch').on('input', this.debounce(() => {
            this.applyTaskFilters();
        }, 500));

        // Selección múltiple de tareas
        $('#selectAllTasks').on('change', (e) => {
            const isChecked = e.target.checked;
            $('.task-checkbox').prop('checked', isChecked);
            this.updateSelectedTasks();
        });

        $(document).on('change', '.task-checkbox', () => {
            this.updateSelectedTasks();
        });

        // Ordenamiento de tablas
        $(document).on('click', '.sortable', (e) => {
            const column = e.currentTarget.getAttribute('data-column');
            this.sortTable(column);
        });

        // Progress slider sincronizado
        $('#progressSlider').on('input', (e) => {
            $('#progressInput').val(e.target.value);
        });

        $('#progressInput').on('input', (e) => {
            $('#progressSlider').val(e.target.value);
        });
    },

    // Cargar datos iniciales
    async loadInitialData() {
        try {
            // Cargar departamentos
            const deptResponse = await this.apiCall('get_departments');
            if (deptResponse.success) {
                this.cache.departments = deptResponse.data;
                this.populateDepartmentSelects();
            }

            // Cargar empleados
            const empResponse = await this.apiCall('get_employees');
            if (empResponse.success) {
                this.cache.employees = empResponse.data;
                this.populateEmployeeSelects();
            }
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
        }
    },

    // Cambio de pestaña
    onTabChange(tabId) {
        switch (tabId) {
            case 'processes':
                this.loadProcesses();
                break;
            case 'tasks':
                this.loadTasks();
                break;
            case 'reports':
                this.loadReports();
                break;
            case 'templates':
                this.loadTemplates();
                break;
        }
    },

    // ============ GESTIÓN DE PROCESOS ============

    async loadProcesses(page = 1) {
        try {
            this.showLoading('#processesTable tbody');

            const params = {
                action: 'get_processes',
                page: page,
                limit: this.config.itemsPerPage,
                ...this.config.filters.processes,
                sort: this.config.sortField,
                dir: this.config.sortDirection
            };

            const response = await this.apiCall('get_processes', params);

            if (response.success) {
                this.cache.processes = response.data;
                this.renderProcessesTable(response.data);
                this.renderPagination('#processesPagination', response.pagination, () => this.loadProcesses);
            } else {
                this.showError('Error cargando procesos: ' + response.message);
            }
        } catch (error) {
            console.error('Error cargando procesos:', error);
            this.showError('Error de conexión al cargar procesos');
        }
    },

    renderProcessesTable(processes) {
        const tbody = $('#processesTable tbody');

        if (processes.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-cogs"></i>
                            <h4>No hay procesos</h4>
                            <p>Crea tu primer proceso para comenzar</p>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        const rows = processes.map(process => {
            const priorityClass = this.getPriorityClass(process.priority);
            const statusClass = this.getStatusClass(process.status);
            const progressBar = this.renderProgressBar(process.progress_percentage);

            return `
                <tr class="fade-in">
                    <td>
                        <strong><a href="#" onclick="ProcessesTasksApp.viewProcess(${process.process_id})" class="text-decoration-none">${this.escapeHtml(process.name)}</a></strong>
                        <br><small class="text-muted">${this.escapeHtml(process.description || '').substring(0, 50)}${process.description && process.description.length > 50 ? '...' : ''}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${this.escapeHtml(process.department_name || 'Sin departamento')}</span>
                    </td>
                    <td>
                        <span class="badge badge-status ${statusClass}">${this.getStatusText(process.status)}</span>
                    </td>
                    <td>
                        <span class="badge badge-priority ${priorityClass}">${this.getPriorityText(process.priority)}</span>
                    </td>
                    <td>
                        ${progressBar}
                        <small class="text-muted">${process.progress_percentage}%</small>
                    </td>
                    <td>
                        <span class="badge bg-info">${process.completed_tasks}/${process.total_tasks}</span>
                    </td>
                    <td>
                        <small class="text-muted">${process.created_at_formatted}</small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-view btn-action" onclick="ProcessesTasksApp.viewProcess(${process.process_id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${this.config.permissions.processes.edit ? `
                                <button class="btn btn-edit btn-action" onclick="ProcessesTasksApp.editProcess(${process.process_id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            ` : ''}
                            ${process.status === 'draft' && this.config.permissions.processes.edit ? `
                                <button class="btn btn-complete btn-action" onclick="ProcessesTasksApp.startProcess(${process.process_id})" title="Iniciar proceso">
                                    <i class="fas fa-play"></i>
                                </button>
                            ` : ''}
                            ${process.status === 'active' && this.config.permissions.processes.edit ? `
                                <button class="btn btn-warning btn-action" onclick="ProcessesTasksApp.pauseProcess(${process.process_id})" title="Pausar proceso">
                                    <i class="fas fa-pause"></i>
                                </button>
                            ` : ''}
                            ${this.config.permissions.processes.delete ? `
                                <button class="btn btn-delete btn-action" onclick="ProcessesTasksApp.deleteProcess(${process.process_id})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.html(rows);
    },

    // ============ GESTIÓN DE TAREAS ============

    async loadTasks(page = 1) {
        try {
            this.showLoading('#tasksTable tbody');

            const params = {
                action: 'get_tasks',
                page: page,
                limit: this.config.itemsPerPage,
                ...this.config.filters.tasks,
                sort: this.config.sortField,
                dir: this.config.sortDirection
            };

            const response = await this.apiCall('get_tasks', params);

            if (response.success) {
                this.cache.tasks = response.data;
                this.renderTasksTable(response.data);
                this.renderPagination('#tasksPagination', response.pagination, () => this.loadTasks);
            } else {
                this.showError('Error cargando tareas: ' + response.message);
            }
        } catch (error) {
            console.error('Error cargando tareas:', error);
            this.showError('Error de conexión al cargar tareas');
        }
    },

    renderTasksTable(tasks) {
        const tbody = $('#tasksTable tbody');

        if (tasks.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-tasks"></i>
                            <h4>No hay tareas</h4>
                            <p>Crea tu primera tarea para comenzar</p>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        const rows = tasks.map(task => {
            const priorityClass = this.getPriorityClass(task.priority);
            const statusClass = this.getStatusClass(task.status);
            const dueIndicator = this.getDueIndicator(task);
            const progressBar = this.renderProgressBar(task.completion_percentage);

            return `
                <tr class="fade-in">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input task-checkbox" value="${task.task_id}">
                    </td>
                    <td>
                        <strong><a href="#" onclick="ProcessesTasksApp.viewTask(${task.task_id})" class="text-decoration-none">${this.escapeHtml(task.title)}</a></strong>
                        <br><small class="text-muted">${this.escapeHtml(task.description || '').substring(0, 50)}${task.description && task.description.length > 50 ? '...' : ''}</small>
                    </td>
                    <td>
                        ${task.process_name ? `<span class="badge bg-primary">${this.escapeHtml(task.process_name)}</span>` : '<span class="text-muted">Individual</span>'}
                    </td>
                    <td>
                        ${task.assigned_name ? `<span class="badge bg-info">${this.escapeHtml(task.assigned_name)}</span>` : '<span class="text-muted">Sin asignar</span>'}
                    </td>
                    <td>
                        <span class="badge badge-status ${statusClass}">${this.getStatusText(task.status)}</span>
                    </td>
                    <td>
                        <span class="badge badge-priority ${priorityClass}">${this.getPriorityText(task.priority)}</span>
                    </td>
                    <td>
                        ${task.due_date_formatted ? `
                            <span class="due-indicator ${task.due_status}">${task.due_date_formatted}</span>
                        ` : '<span class="text-muted">Sin fecha</span>'}
                    </td>
                    <td>
                        ${progressBar}
                        <small class="text-muted">${task.completion_percentage}%</small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-view btn-action" onclick="ProcessesTasksApp.viewTask(${task.task_id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${this.config.permissions.tasks.edit ? `
                                <button class="btn btn-edit btn-action" onclick="ProcessesTasksApp.editTask(${task.task_id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            ` : ''}
                            ${task.status !== 'completed' && this.config.permissions.tasks.complete ? `
                                <button class="btn btn-warning btn-action" onclick="ProcessesTasksApp.updateTaskProgress(${task.task_id})" title="Actualizar progreso">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button class="btn btn-complete btn-action" onclick="ProcessesTasksApp.completeTask(${task.task_id})" title="Completar">
                                    <i class="fas fa-check"></i>
                                </button>
                            ` : ''}
                            ${this.config.permissions.tasks.assign ? `
                                <button class="btn btn-info btn-action" onclick="ProcessesTasksApp.reassignTask(${task.task_id})" title="Reasignar">
                                    <i class="fas fa-user-tag"></i>
                                </button>
                            ` : ''}
                            ${this.config.permissions.tasks.delete ? `
                                <button class="btn btn-delete btn-action" onclick="ProcessesTasksApp.deleteTask(${task.task_id})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.html(rows);
    },

    // ============ FUNCIONES DE MODAL ============

    async editProcess(processId) {
        const process = this.cache.processes.find(p => p.process_id == processId);
        if (!process) return;

        $('#editProcessId').val(process.process_id);
        $('#editProcessName').val(process.name);
        $('#editProcessDescription').val(process.description);
        $('#editProcessDepartment').val(process.department_id);
        $('#editProcessPriority').val(process.priority);
        $('#editProcessStatus').val(process.status);
        $('#editProcessDuration').val(process.estimated_duration);

        $('#editProcessModal').modal('show');
    },

    async editTask(taskId) {
        const task = this.cache.tasks.find(t => t.task_id == taskId);
        if (!task) return;

        $('#editTaskId').val(task.task_id);
        $('#editTaskTitle').val(task.title);
        $('#editTaskDescription').val(task.description);
        $('#editTaskPriority').val(task.priority);
        $('#editTaskStatus').val(task.status);
        $('#editTaskProgress').val(task.completion_percentage);
        $('#editTaskEstimatedHours').val(task.estimated_hours);

        if (task.due_date) {
            const dueDate = new Date(task.due_date);
            const formattedDate = dueDate.toISOString().slice(0, 16);
            $('#editTaskDueDate').val(formattedDate);
        }

        $('#editTaskModal').modal('show');
    },

    completeTask(taskId) {
        $('#completeTaskId').val(taskId);
        $('#completeTaskModal').modal('show');
    },

    updateTaskProgress(taskId) {
        const task = this.cache.tasks.find(t => t.task_id == taskId);
        if (!task) return;

        $('#updateProgressTaskId').val(task.task_id);
        $('#currentProgressBar').style.width = task.completion_percentage + '%';
        $('#currentProgressText').text(`Progreso actual: ${task.completion_percentage}%`);
        $('#progressSlider').val(task.completion_percentage);
        $('#progressInput').val(task.completion_percentage);

        $('#updateProgressModal').modal('show');
    },

    // ============ MANEJADORES DE FORMULARIOS ============

    async handleProcessSubmit(e, action) {
        e.preventDefault();

        try {
            const formData = new FormData(e.target);
            formData.append('action', action === 'create' ? 'create_process' : 'update_process');

            const response = await this.apiCall(action === 'create' ? 'create_process' : 'update_process', formData);

            if (response.success) {
                this.showSuccess(response.message);
                $(e.target).closest('.modal').modal('hide');
                this.loadProcesses();
                e.target.reset();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            console.error('Error en formulario de proceso:', error);
            this.showError('Error al procesar la solicitud');
        }
    },

    async handleTaskSubmit(e, action) {
        e.preventDefault();

        try {
            const formData = new FormData(e.target);
            formData.append('action', action === 'create' ? 'create_task' : 'update_task');

            const response = await this.apiCall(action === 'create' ? 'create_task' : 'update_task', formData);

            if (response.success) {
                this.showSuccess(response.message);
                $(e.target).closest('.modal').modal('hide');
                this.loadTasks();
                e.target.reset();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            console.error('Error en formulario de tarea:', error);
            this.showError('Error al procesar la solicitud');
        }
    },

    async handleCompleteTask(e) {
        e.preventDefault();

        try {
            const formData = new FormData(e.target);
            formData.append('action', 'complete_task');

            const response = await this.apiCall('complete_task', formData);

            if (response.success) {
                this.showSuccess(response.message);
                $('#completeTaskModal').modal('hide');
                this.loadTasks();
                e.target.reset();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            console.error('Error completando tarea:', error);
            this.showError('Error al completar la tarea');
        }
    },

    // ============ FUNCIONES AUXILIARES ============

    getPriorityClass(priority) {
        const classes = {
            'low': 'low',
            'medium': 'medium',
            'high': 'high',
            'critical': 'critical'
        };
        return classes[priority] || 'medium';
    },

    getStatusClass(status) {
        return status;
    },

    getPriorityText(priority) {
        const texts = {
            'low': 'Baja',
            'medium': 'Media',
            'high': 'Alta',
            'critical': 'Crítica'
        };
        return texts[priority] || priority;
    },

    getStatusText(status) {
        const texts = {
            'draft': 'Borrador',
            'active': 'Activo',
            'paused': 'Pausado',
            'completed': 'Completado',
            'cancelled': 'Cancelado',
            'pending': 'Pendiente',
            'in_progress': 'En Progreso',
            'review': 'Revisión'
        };
        return texts[status] || status;
    },

    renderProgressBar(percentage) {
        const color = percentage >= 75 ? 'success' : percentage >= 50 ? 'warning' : 'danger';
        return `
            <div class="progress progress-custom" style="height: 6px;">
                <div class="progress-bar bg-${color}" style="width: ${percentage}%"></div>
            </div>
        `;
    },

    getDueIndicator(task) {
        if (!task.due_date) return 'normal';

        const now = new Date();
        const dueDate = new Date(task.due_date);
        const diffHours = (dueDate - now) / (1000 * 60 * 60);

        if (diffHours < 0) return 'overdue';
        if (diffHours < 24) return 'due-today';
        if (diffHours < 72) return 'due-soon';
        return 'normal';
    },

    updateSelectedTasks() {
        const selected = $('.task-checkbox:checked').map(function () {
            return parseInt(this.value);
        }).get();

        this.config.selectedTasks = selected;
        $('#selectedTasksCount').text(selected.length);
        $('#btnBulkAssign').prop('disabled', selected.length === 0);
    },

    populateDepartmentSelects() {
        const selects = $('#processDepartmentSelect, #editProcessDepartment, #taskDepartmentSelect, #filterProcessDepartment, #filterTaskDepartment, #bulkAssignDepartment');

        selects.each(function () {
            const currentValue = $(this).val();
            $(this).find('option:not(:first)').remove();

            ProcessesTasksApp.cache.departments.forEach(dept => {
                $(this).append(`<option value="${dept.department_id}">${ProcessesTasksApp.escapeHtml(dept.name)}</option>`);
            });

            if (currentValue) $(this).val(currentValue);
        });
    },

    populateEmployeeSelects() {
        const selects = $('#taskAssignedSelect, #filterTaskAssigned, #reassignTaskEmployee, #bulkAssignEmployee');

        selects.each(function () {
            const currentValue = $(this).val();
            $(this).find('option:not(:first)').remove();

            ProcessesTasksApp.cache.employees.forEach(emp => {
                $(this).append(`<option value="${emp.employee_id}">${ProcessesTasksApp.escapeHtml(emp.name)}</option>`);
            });

            if (currentValue) $(this).val(currentValue);
        });
    },

    // ============ UTILIDADES ============

    async apiCall(action, params = {}) {
        const isFormData = params instanceof FormData;

        if (!isFormData) {
            params.action = action;
        }

        const options = {
            method: 'POST',
            body: isFormData ? params : new URLSearchParams(params)
        };

        const response = await fetch(this.config.apiUrl, options);
        return await response.json();
    },

    showLoading(selector) {
        $(selector).html(`
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </td>
            </tr>
        `);
    },

    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    },

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    },

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    setupColumnDragDrop() {
        // Implementar drag & drop para reordenar columnas (próximamente)
        console.log('Column drag & drop configurado');
    },

    // Funciones de filtros y búsqueda
    applyProcessFilters() {
        this.config.filters.processes = {
            status: $('#filterProcessStatus').val(),
            priority: $('#filterProcessPriority').val(),
            department_id: $('#filterProcessDepartment').val()
        };
        this.loadProcesses(1);
    },

    applyTaskFilters() {
        this.config.filters.tasks = {
            status: $('#filterTaskStatus').val(),
            priority: $('#filterTaskPriority').val(),
            assigned_to: $('#filterTaskAssigned').val(),
            department_id: $('#filterTaskDepartment').val(),
            due_filter: $('#filterTaskDue').val(),
            search: $('#filterTaskSearch').val()
        };
        this.loadTasks(1);
    },

    sortTable(column) {
        if (this.config.sortField === column) {
            this.config.sortDirection = this.config.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.config.sortField = column;
            this.config.sortDirection = 'asc';
        }

        // Actualizar indicadores visuales
        $('.sortable').removeClass('sorted');
        $(`.sortable[data-column="${column}"]`).addClass('sorted');

        // Recargar datos con nuevo ordenamiento
        if (this.config.currentTab === 'processes') {
            this.loadProcesses(1);
        } else if (this.config.currentTab === 'tasks') {
            this.loadTasks(1);
        }
    },

    renderPagination(selector, pagination, loadFunction) {
        const container = $(selector);
        if (pagination.total_pages <= 1) {
            container.empty();
            return;
        }

        let html = '';

        // Botón anterior
        html += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="ProcessesTasksApp.${loadFunction.name}(${pagination.current_page - 1}); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Páginas
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="ProcessesTasksApp.${loadFunction.name}(${i}); return false;">${i}</a>
                </li>
            `;
        }

        // Botón siguiente
        html += `
            <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="ProcessesTasksApp.${loadFunction.name}(${pagination.current_page + 1}); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        container.html(html);
    },

    // Funciones de placeholder para desarrollo futuro
    loadReports() {
        console.log('Cargando reportes...');
    },

    loadTemplates() {
        console.log('Cargando plantillas...');
    },

    viewProcess(processId) {
        console.log('Ver proceso:', processId);
    },

    viewTask(taskId) {
        console.log('Ver tarea:', taskId);
    },

    deleteProcess(processId) {
        Swal.fire({
            title: '¿Eliminar proceso?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.doDeleteProcess(processId);
            }
        });
    },

    deleteTask(taskId) {
        Swal.fire({
            title: '¿Eliminar tarea?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.doDeleteTask(taskId);
            }
        });
    },

    async doDeleteProcess(processId) {
        try {
            const response = await this.apiCall('delete_process', { process_id: processId });
            if (response.success) {
                this.showSuccess(response.message);
                this.loadProcesses();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            console.error('Error eliminando proceso:', error);
            this.showError('Error al eliminar el proceso');
        }
    },

    async doDeleteTask(taskId) {
        try {
            const response = await this.apiCall('delete_task', { task_id: taskId });
            if (response.success) {
                this.showSuccess(response.message);
                this.loadTasks();
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            console.error('Error eliminando tarea:', error);
            this.showError('Error al eliminar la tarea');
        }
    }
};

// Funciones globales para eventos onclick
function openNewProcessModal() {
    $('#newProcessModal').modal('show');
}

function openNewTaskModal() {
    $('#newTaskModal').modal('show');
}

function toggleProcessFilters() {
    $('#processFilters').toggleClass('d-none');
}

function toggleTaskFilters() {
    $('#taskFilters').toggleClass('d-none');
}

function showMyTasks() {
    $('#filterTaskAssigned').val(window.ProcessesTasksConfig.currentEmployeeId);
    ProcessesTasksApp.applyTaskFilters();
}

function exportProcesses() {
    console.log('Exportar procesos - Por implementar');
}

function exportTasks() {
    console.log('Exportar tareas - Por implementar');
}
