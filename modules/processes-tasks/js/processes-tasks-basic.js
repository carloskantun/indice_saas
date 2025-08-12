/**
 * MÓDULO PROCESOS Y TAREAS - JAVASCRIPT BÁSICO
 * Funcionalidades principales del módulo
 */

// Configuración global del módulo
window.ProcessesTasksModule = {
    config: {
        company_id: 1,
        user_id: 1,
        user_role: 'user',
        can_write: false,
        can_delete: false,
        endpoints: {
            processes: 'controller.php?action=processes',
            tasks: 'controller.php?action=tasks',
            templates: 'controller.php?action=templates'
        }
    },

    // Inicializar módulo
    init: function () {
        console.log('Módulo Procesos y Tareas inicializado');
        this.bindEvents();
        this.loadInitialData();
    },

    // Vincular eventos
    bindEvents: function () {
        // Eventos de tabs
        $('#mainTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
            console.log('Tab cambiado:', e.target.getAttribute('data-bs-target'));
        });
    },

    // Cargar datos iniciales
    loadInitialData: function () {
        // Por ahora solo mostramos mensaje de inicialización
        console.log('Configuración:', this.config);
    },

    // Funciones de procesos
    processes: {
        create: function () {
            Swal.fire({
                title: 'Nuevo Proceso',
                text: 'Funcionalidad en desarrollo',
                icon: 'info'
            });
        },

        edit: function (id) {
            Swal.fire({
                title: 'Editar Proceso',
                text: `Editando proceso ID: ${id}`,
                icon: 'info'
            });
        },

        delete: function (id) {
            Swal.fire({
                title: '¿Eliminar proceso?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Eliminando proceso:', id);
                }
            });
        }
    },

    // Funciones de tareas
    tasks: {
        create: function () {
            Swal.fire({
                title: 'Nueva Tarea',
                text: 'Funcionalidad en desarrollo',
                icon: 'info'
            });
        },

        edit: function (id) {
            Swal.fire({
                title: 'Editar Tarea',
                text: `Editando tarea ID: ${id}`,
                icon: 'info'
            });
        },

        complete: function (id) {
            Swal.fire({
                title: '¿Marcar como completada?',
                text: 'La tarea se marcará como completada',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Completar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Completando tarea:', id);
                    Swal.fire('¡Completada!', 'La tarea ha sido marcada como completada', 'success');
                }
            });
        }
    },

    // Funciones de utilidad
    utils: {
        formatDate: function (date) {
            return new Date(date).toLocaleDateString('es-ES');
        },

        formatDateTime: function (date) {
            return new Date(date).toLocaleString('es-ES');
        },

        showToast: function (message, type = 'info') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: type,
                title: message
            });
        }
    }
};

// Funciones globales para compatibilidad
function newProcess() {
    ProcessesTasksModule.processes.create();
}

function newFromTemplate() {
    Swal.fire('Info', 'Función de plantillas en desarrollo', 'info');
}

function newTask() {
    ProcessesTasksModule.tasks.create();
}

function showMyTasks() {
    Swal.fire('Info', 'Función de mis tareas en desarrollo', 'info');
}

function newTemplate() {
    Swal.fire('Info', 'Función de nueva plantilla en desarrollo', 'info');
}

// Inicializar cuando el DOM esté listo
$(document).ready(function () {
    // Aplicar configuración desde PHP si está disponible
    if (typeof MODULE_CONFIG !== 'undefined') {
        Object.assign(ProcessesTasksModule.config, MODULE_CONFIG);
    }

    // Inicializar módulo
    ProcessesTasksModule.init();
});
