# 🔄 Módulo de Procesos y Tareas - Sistema SaaS Indice

## 🎯 Descripción
Módulo completo de gestión de procesos operativos y tareas diseñado para el sistema SaaS Indice. Permite la asignación de flujos operativos, tareas y responsables por unidad o rol, basado en la arquitectura probada de los módulos de gastos y recursos humanos.

---

## ✨ Características Principales

### 📋 **Gestión de Procesos**
- ✅ CRUD completo de procesos operativos
- ✅ Creación de flujos de trabajo personalizados
- ✅ Configuración de etapas y secuencias
- ✅ Asignación automática de responsables
- ✅ Plantillas predefinidas de procesos comunes
- ✅ Sistema de estatus (Borrador, Activo, Pausado, Completado, Cancelado)

### ⚡ **Gestión de Tareas**
- ✅ CRUD completo de tareas
- ✅ Asignación automática basada en roles y departamentos
- ✅ Seguimiento de progreso en tiempo real
- ✅ Sistema de prioridades (Baja, Media, Alta, Crítica)
- ✅ Fechas límite y recordatorios
- ✅ Estados de tareas (Pendiente, En Progreso, Revisión, Completada, Cancelada)

### 🤖 **Automatización**
- ✅ Disparadores automáticos de procesos
- ✅ Asignación inteligente de tareas
- ✅ Notificaciones automáticas
- ✅ Escalamiento por vencimiento
- ✅ Integración con otros módulos

### 👥 **Gestión de Responsables**
- ✅ Asignación por empleado específico
- ✅ Asignación por rol/departamento
- ✅ Sistema de suplentes y delegación
- ✅ Carga de trabajo balanceada
- ✅ Historial de asignaciones

### 📊 **Reportes y Productividad**
- ✅ KPIs de productividad por empleado
- ✅ Tiempo promedio de completado
- ✅ Procesos activos vs completados
- ✅ Tareas vencidas y en riesgo
- ✅ Eficiencia por departamento
- ✅ Reportes de carga de trabajo

### 🔐 **Sistema de Permisos**
- ✅ Control granular por roles
- ✅ Permisos específicos para procesos, tareas y reportes
- ✅ Integración con el sistema de roles del SaaS
- ✅ Visibilidad basada en asignación y jerarquía

---

## 🗄️ Base de Datos

### Tablas Principales
```sql
processes              -- Definición de procesos operativos
process_steps          -- Etapas de cada proceso
tasks                  -- Tareas individuales
task_assignments       -- Asignaciones de tareas a empleados
process_instances      -- Instancias ejecutándose de procesos
task_history          -- Historial de cambios en tareas
workflow_templates     -- Plantillas de flujos de trabajo
```

### Campos Clave de Procesos
- `process_id` - ID único del proceso
- `name` - Nombre del proceso
- `description` - Descripción detallada
- `department_id` - Departamento propietario
- `status` - Estado del proceso (draft, active, paused, completed, cancelled)
- `priority` - Prioridad (low, medium, high, critical)
- `estimated_duration` - Duración estimada en horas
- `created_by` - Usuario creador
- `company_id` - Empresa (multi-tenant)

### Campos Clave de Tareas
- `task_id` - ID único de la tarea
- `process_id` - Proceso al que pertenece (opcional)
- `title` - Título de la tarea
- `description` - Descripción detallada
- `assigned_to` - Empleado asignado
- `assigned_by` - Quien asignó la tarea
- `priority` - Prioridad (low, medium, high, critical)
- `status` - Estado (pending, in_progress, review, completed, cancelled)
- `due_date` - Fecha límite
- `estimated_hours` - Horas estimadas
- `actual_hours` - Horas reales trabajadas
- `completion_percentage` - Porcentaje de completado
- `department_id` - Departamento responsable
- `company_id` - Empresa (multi-tenant)

---

## 🎨 Interfaz de Usuario

### 📱 **Pantalla Principal**
- **Header con KPIs**: Procesos Activos, Tareas Pendientes, Tareas Vencidas, Productividad Promedio
- **Pestañas principales**: 
  - 🔄 Procesos
  - ✅ Tareas
  - 📊 Reportes
  - ⚙️ Plantillas

### 🔄 **Vista de Procesos**
#### Botones de Acción Principales:
- 🆕 **Nuevo Proceso** - Crear proceso desde cero
- 📋 **Desde Plantilla** - Crear proceso usando plantilla existente
- 📊 **Estadísticas** - Ver KPIs de procesos
- 📤 **Exportar** - CSV/PDF de procesos
- 🔍 **Filtros Avanzados** - Por estado, departamento, responsable, fecha

#### Tabla de Procesos:
**Columnas configurables (drag & drop):**
1. ✅ **Nombre** - Nombre del proceso (con link a detalles)
2. 🏢 **Departamento** - Departamento propietario
3. 👤 **Responsable** - Usuario responsable principal
4. 🎯 **Estado** - Badge con color según estado
5. 🔥 **Prioridad** - Indicador visual de prioridad
6. 📅 **Fecha Inicio** - Cuándo comenzó
7. ⏱️ **Duración Est.** - Tiempo estimado
8. 📈 **Progreso** - Barra de progreso visual
9. 👥 **Tareas** - Contador de tareas (completadas/total)
10. 📅 **Última Act.** - Última actividad
11. 🔧 **Acciones** - Botones de acción

#### Acciones por Fila:
- 👁️ **Ver** - Detalles del proceso
- ✏️ **Editar** - Modificar proceso
- ▶️ **Iniciar** - Ejecutar instancia del proceso
- ⏸️ **Pausar** - Pausar proceso activo
- 📋 **Duplicar** - Crear copia del proceso
- 🗑️ **Eliminar** - Eliminar proceso

### ✅ **Vista de Tareas**
#### Botones de Acción Principales:
- 🆕 **Nueva Tarea** - Crear tarea individual
- 📋 **Asignar Masivo** - Asignar múltiples tareas
- 👥 **Mis Tareas** - Filtro rápido para tareas asignadas al usuario
- 📊 **Dashboard Personal** - KPIs personales
- 📤 **Exportar** - CSV/PDF de tareas
- 🔍 **Filtros Avanzados** - Por estado, prioridad, asignado, vencimiento

#### Tabla de Tareas:
**Columnas configurables (drag & drop):**
1. ✅ **Título** - Nombre de la tarea (con link a detalles)
2. 🔄 **Proceso** - Proceso relacionado (si aplica)
3. 👤 **Asignado** - Empleado responsable
4. 👤 **Asignado Por** - Quien asignó la tarea
5. 🎯 **Estado** - Badge con color según estado
6. 🔥 **Prioridad** - Indicador visual de prioridad
7. 📅 **Fecha Límite** - Cuándo vence (con alertas de color)
8. ⏱️ **Est./Real** - Horas estimadas vs reales
9. 📈 **Progreso** - Barra de progreso o porcentaje
10. 🏢 **Departamento** - Departamento responsable
11. 📅 **Creada** - Fecha de creación
12. 📅 **Última Act.** - Última actualización
13. 🔧 **Acciones** - Botones de acción

#### Acciones por Fila:
- 👁️ **Ver** - Detalles de la tarea
- ✏️ **Editar** - Modificar tarea
- ✅ **Completar** - Marcar como completada (modal con detalles)
- 📝 **Actualizar** - Actualizar progreso (modal)
- 👥 **Reasignar** - Cambiar responsable
- 🗑️ **Eliminar** - Eliminar tarea

### 📊 **Vista de Reportes**
#### Secciones de Reportes:
1. **📈 KPIs Generales**
   - Procesos activos vs completados
   - Tareas por estado
   - Productividad promedio
   - Tiempo promedio de completado

2. **👥 Productividad por Empleado**
   - Tareas completadas por periodo
   - Tiempo promedio por tarea
   - Carga de trabajo actual
   - Eficiencia personal

3. **🏢 Análisis por Departamento**
   - Procesos por departamento
   - Carga de trabajo departamental
   - Comparativo de eficiencia

4. **⚠️ Alertas y Seguimiento**
   - Tareas vencidas
   - Tareas en riesgo de vencimiento
   - Procesos estancados
   - Sobrecarga de trabajo

### ⚙️ **Vista de Plantillas**
#### Gestión de Plantillas:
- 📋 **Plantillas de Procesos** - Procesos predefinidos reutilizables
- ✅ **Plantillas de Tareas** - Conjuntos de tareas comunes
- 🎯 **Plantillas por Departamento** - Específicas para cada área
- 📥 **Importar Plantillas** - Desde archivo o sistema externo

---

## 🔧 Funcionalidades Técnicas

### 📱 **Responsive y UX**
- ✅ Diseño totalmente responsive
- ✅ Interfaz táctil optimizada para móvil
- ✅ Drag & drop para reordenar columnas
- ✅ Carga asíncrona con paginación
- ✅ Filtros en tiempo real
- ✅ Búsqueda instantánea

### 🔄 **Integraciones**
- ✅ Integración con módulo de Recursos Humanos (empleados, departamentos)
- ✅ Notificaciones automáticas por email
- ✅ Logs de auditoría completos
- ✅ API REST para integraciones externas
- ✅ Webhooks para eventos importantes

### 📊 **Exportación y Reportes**
- ✅ Exportación a CSV con filtros aplicados
- ✅ Exportación a PDF con formato personalizable
- ✅ Reportes programados automáticos
- ✅ Dashboard ejecutivo con métricas clave

### 🔐 **Seguridad y Permisos**
- ✅ Control granular por roles
- ✅ Permisos a nivel de proceso y tarea
- ✅ Auditoría completa de acciones
- ✅ Encriptación de datos sensibles

---

## 🚀 Plan de Implementación

### 📋 **Fase 1: Estructura Base**
1. ✅ Crear estructura de base de datos
2. ✅ Implementar CRUD básico de procesos
3. ✅ Implementar CRUD básico de tareas
4. ✅ Sistema de permisos básico
5. ✅ Interfaz principal responsive

### 📈 **Fase 2: Funcionalidades Avanzadas**
1. 🔄 Sistema de flujos de trabajo
2. 🤖 Automatización básica
3. 📊 Reportes y KPIs
4. 📤 Exportación CSV/PDF
5. 🔍 Filtros avanzados

### ⚡ **Fase 3: Automatización y Optimización**
1. 🤖 Automatización avanzada
2. 📧 Sistema de notificaciones
3. 📱 Optimización móvil
4. 🔗 Integraciones con otros módulos
5. 📊 Dashboard ejecutivo

---

## 📁 Estructura de Archivos

```
modules/processes-tasks/
├── README.md                 # Este archivo de documentación
├── index.php                 # Vista principal del módulo
├── controller.php             # Lógica de negocio y API endpoints
├── modals.php                 # Modales para CRUD y acciones
├── config.php                 # Configuración específica del módulo
├── css/
│   ├── processes-tasks.css    # Estilos específicos del módulo
│   └── responsive.css         # Estilos responsive adicionales
├── js/
│   ├── processes-tasks.js     # JavaScript principal del módulo
│   ├── modals.js             # Gestión de modales
│   ├── filters.js            # Sistema de filtros
│   └── charts.js             # Gráficos y visualizaciones
├── includes/
│   ├── permissions.php        # Sistema de permisos del módulo
│   ├── automation.php         # Lógica de automatización
│   └── notifications.php      # Sistema de notificaciones
└── sql/
    ├── install.sql           # Script de instalación inicial
    ├── migrations/           # Migraciones de base de datos
    └── sample_data.sql       # Datos de ejemplo
```

---

## 🌐 Textos para Carpeta Lang

### 🔤 **Español (lang/es.php)**
```php
// Módulo Procesos y Tareas
'processes_tasks' => 'Procesos y Tareas',
'processes' => 'Procesos',
'tasks' => 'Tareas',
'workflows' => 'Flujos de Trabajo',
'templates' => 'Plantillas',

// Procesos
'process' => 'Proceso',
'new_process' => 'Nuevo Proceso',
'process_name' => 'Nombre del Proceso',
'process_description' => 'Descripción del Proceso',
'process_steps' => 'Etapas del Proceso',
'process_owner' => 'Responsable del Proceso',
'estimated_duration' => 'Duración Estimada',
'process_status' => 'Estado del Proceso',
'active_processes' => 'Procesos Activos',
'completed_processes' => 'Procesos Completados',
'process_efficiency' => 'Eficiencia del Proceso',

// Tareas
'task' => 'Tarea',
'new_task' => 'Nueva Tarea',
'task_title' => 'Título de la Tarea',
'task_description' => 'Descripción de la Tarea',
'assigned_to' => 'Asignado a',
'assigned_by' => 'Asignado por',
'task_priority' => 'Prioridad de la Tarea',
'task_status' => 'Estado de la Tarea',
'due_date' => 'Fecha Límite',
'estimated_hours' => 'Horas Estimadas',
'actual_hours' => 'Horas Reales',
'completion_percentage' => 'Porcentaje de Completado',
'pending_tasks' => 'Tareas Pendientes',
'overdue_tasks' => 'Tareas Vencidas',
'my_tasks' => 'Mis Tareas',

// Estados
'draft' => 'Borrador',
'active' => 'Activo',
'paused' => 'Pausado',
'completed' => 'Completado',
'cancelled' => 'Cancelado',
'pending' => 'Pendiente',
'in_progress' => 'En Progreso',
'review' => 'En Revisión',

// Prioridades
'low_priority' => 'Baja',
'medium_priority' => 'Media',
'high_priority' => 'Alta',
'critical_priority' => 'Crítica',

// Acciones
'start_process' => 'Iniciar Proceso',
'pause_process' => 'Pausar Proceso',
'complete_task' => 'Completar Tarea',
'update_progress' => 'Actualizar Progreso',
'reassign_task' => 'Reasignar Tarea',
'duplicate_process' => 'Duplicar Proceso',
'create_from_template' => 'Crear desde Plantilla',
'assign_multiple' => 'Asignar Múltiple',

// Reportes
'productivity_report' => 'Reporte de Productividad',
'department_analysis' => 'Análisis por Departamento',
'employee_performance' => 'Rendimiento por Empleado',
'process_metrics' => 'Métricas de Procesos',
'workflow_efficiency' => 'Eficiencia de Flujos',
'overdue_alerts' => 'Alertas de Vencimiento',

// KPIs
'average_completion_time' => 'Tiempo Promedio de Completado',
'process_success_rate' => 'Tasa de Éxito de Procesos',
'employee_productivity' => 'Productividad por Empleado',
'department_workload' => 'Carga de Trabajo por Departamento',
'on_time_completion' => 'Completado a Tiempo',

// Automatización
'automation_rules' => 'Reglas de Automatización',
'auto_assignment' => 'Asignación Automática',
'escalation_rules' => 'Reglas de Escalamiento',
'notification_settings' => 'Configuración de Notificaciones',

// Plantillas
'process_template' => 'Plantilla de Proceso',
'task_template' => 'Plantilla de Tarea',
'workflow_template' => 'Plantilla de Flujo',
'department_templates' => 'Plantillas por Departamento',
'import_template' => 'Importar Plantilla',
'export_template' => 'Exportar Plantilla',
```

### 🔤 **Inglés (lang/en.php)**
```php
// Processes and Tasks Module
'processes_tasks' => 'Processes and Tasks',
'processes' => 'Processes',
'tasks' => 'Tasks',
'workflows' => 'Workflows',
'templates' => 'Templates',

// Processes
'process' => 'Process',
'new_process' => 'New Process',
'process_name' => 'Process Name',
'process_description' => 'Process Description',
'process_steps' => 'Process Steps',
'process_owner' => 'Process Owner',
'estimated_duration' => 'Estimated Duration',
'process_status' => 'Process Status',
'active_processes' => 'Active Processes',
'completed_processes' => 'Completed Processes',
'process_efficiency' => 'Process Efficiency',

// Tasks
'task' => 'Task',
'new_task' => 'New Task',
'task_title' => 'Task Title',
'task_description' => 'Task Description',
'assigned_to' => 'Assigned To',
'assigned_by' => 'Assigned By',
'task_priority' => 'Task Priority',
'task_status' => 'Task Status',
'due_date' => 'Due Date',
'estimated_hours' => 'Estimated Hours',
'actual_hours' => 'Actual Hours',
'completion_percentage' => 'Completion Percentage',
'pending_tasks' => 'Pending Tasks',
'overdue_tasks' => 'Overdue Tasks',
'my_tasks' => 'My Tasks',

// Status
'draft' => 'Draft',
'active' => 'Active',
'paused' => 'Paused',
'completed' => 'Completed',
'cancelled' => 'Cancelled',
'pending' => 'Pending',
'in_progress' => 'In Progress',
'review' => 'Under Review',

// Priorities
'low_priority' => 'Low',
'medium_priority' => 'Medium',
'high_priority' => 'High',
'critical_priority' => 'Critical',

// Actions
'start_process' => 'Start Process',
'pause_process' => 'Pause Process',
'complete_task' => 'Complete Task',
'update_progress' => 'Update Progress',
'reassign_task' => 'Reassign Task',
'duplicate_process' => 'Duplicate Process',
'create_from_template' => 'Create from Template',
'assign_multiple' => 'Assign Multiple',

// Reports
'productivity_report' => 'Productivity Report',
'department_analysis' => 'Department Analysis',
'employee_performance' => 'Employee Performance',
'process_metrics' => 'Process Metrics',
'workflow_efficiency' => 'Workflow Efficiency',
'overdue_alerts' => 'Overdue Alerts',

// KPIs
'average_completion_time' => 'Average Completion Time',
'process_success_rate' => 'Process Success Rate',
'employee_productivity' => 'Employee Productivity',
'department_workload' => 'Department Workload',
'on_time_completion' => 'On-Time Completion',

// Automation
'automation_rules' => 'Automation Rules',
'auto_assignment' => 'Auto Assignment',
'escalation_rules' => 'Escalation Rules',
'notification_settings' => 'Notification Settings',

// Templates
'process_template' => 'Process Template',
'task_template' => 'Task Template',
'workflow_template' => 'Workflow Template',
'department_templates' => 'Department Templates',
'import_template' => 'Import Template',
'export_template' => 'Export Template',
```

---

## 💾 Scripts SQL para Base de Datos

### 📄 **install.sql** - Script de instalación inicial
```sql
-- Tabla principal de procesos
CREATE TABLE processes (
    process_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT,
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    estimated_duration INT, -- en horas
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_id INT NOT NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_department_id (department_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
);

-- Etapas de cada proceso
CREATE TABLE process_steps (
    step_id INT PRIMARY KEY AUTO_INCREMENT,
    process_id INT NOT NULL,
    step_name VARCHAR(255) NOT NULL,
    step_description TEXT,
    step_order INT NOT NULL,
    estimated_hours INT,
    responsible_role VARCHAR(50),
    required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE CASCADE,
    INDEX idx_process_id (process_id),
    INDEX idx_step_order (step_order)
);

-- Tabla principal de tareas
CREATE TABLE tasks (
    task_id INT PRIMARY KEY AUTO_INCREMENT,
    process_id INT NULL, -- puede ser independiente del proceso
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT, -- employee_id
    assigned_by INT, -- user_id que asignó
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'review', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATETIME,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2) DEFAULT 0,
    completion_percentage INT DEFAULT 0,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    company_id INT NOT NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_process_id (process_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_department_id (department_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date),
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE SET NULL
);

-- Asignaciones de tareas (historial)
CREATE TABLE task_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    assigned_to INT NOT NULL, -- employee_id
    assigned_by INT NOT NULL, -- user_id
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    is_current BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_is_current (is_current)
);

-- Instancias de procesos ejecutándose
CREATE TABLE process_instances (
    instance_id INT PRIMARY KEY AUTO_INCREMENT,
    process_id INT NOT NULL,
    instance_name VARCHAR(255),
    started_by INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_completion DATETIME,
    actual_completion DATETIME NULL,
    status ENUM('running', 'paused', 'completed', 'cancelled') DEFAULT 'running',
    completion_percentage INT DEFAULT 0,
    company_id INT NOT NULL,
    FOREIGN KEY (process_id) REFERENCES processes(process_id) ON DELETE CASCADE,
    INDEX idx_process_id (process_id),
    INDEX idx_company_id (company_id),
    INDEX idx_status (status)
);

-- Historial de cambios en tareas
CREATE TABLE task_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    field_changed VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_changed_at (changed_at)
);

-- Plantillas de flujos de trabajo
CREATE TABLE workflow_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    department_id INT NULL, -- NULL = disponible para todos
    template_data JSON, -- estructura del flujo en JSON
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_id INT NOT NULL,
    INDEX idx_company_id (company_id),
    INDEX idx_department_id (department_id),
    INDEX idx_category (category)
);

-- Comentarios en tareas
CREATE TABLE task_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_internal BOOLEAN DEFAULT FALSE, -- true = solo visible para el equipo
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_created_at (created_at)
);

-- Archivos adjuntos en tareas
CREATE TABLE task_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id)
);
```

### 📄 **sample_data.sql** - Datos de ejemplo
```sql
-- Procesos de ejemplo
INSERT INTO processes (name, description, department_id, status, priority, estimated_duration, created_by, company_id) VALUES
('Onboarding de Nuevo Empleado', 'Proceso completo de incorporación de nuevos empleados a la empresa', 1, 'active', 'high', 16, 1, 1),
('Revisión Mensual de Inventario', 'Proceso de verificación y actualización del inventario mensual', 2, 'active', 'medium', 8, 1, 1),
('Solicitud de Vacaciones', 'Proceso para solicitar y aprobar vacaciones de empleados', 1, 'active', 'low', 2, 1, 1),
('Evaluación de Desempeño Anual', 'Proceso de evaluación anual del desempeño de empleados', 1, 'draft', 'high', 24, 1, 1);

-- Etapas de procesos
INSERT INTO process_steps (process_id, step_name, step_description, step_order, estimated_hours, responsible_role) VALUES
(1, 'Preparación de Documentos', 'Preparar contrato, manual del empleado y documentos necesarios', 1, 2, 'hr_admin'),
(1, 'Configuración de Accesos', 'Crear cuentas de usuario y asignar permisos del sistema', 2, 1, 'it_admin'),
(1, 'Tour de Instalaciones', 'Mostrar las instalaciones y presentar al equipo', 3, 2, 'hr_admin'),
(1, 'Capacitación Inicial', 'Capacitación sobre procesos, políticas y herramientas', 4, 8, 'supervisor'),
(1, 'Seguimiento Primera Semana', 'Verificar adaptación y resolver dudas', 5, 3, 'supervisor');

-- Tareas de ejemplo
INSERT INTO tasks (process_id, title, description, assigned_to, assigned_by, priority, status, due_date, estimated_hours, completion_percentage, department_id, company_id) VALUES
(1, 'Revisar expediente del candidato', 'Verificar que todos los documentos estén completos antes del primer día', 1, 1, 'high', 'completed', '2024-12-15 17:00:00', 1.0, 100, 1, 1),
(1, 'Preparar estación de trabajo', 'Configurar computadora, teléfono y espacio de trabajo', 2, 1, 'medium', 'in_progress', '2024-12-16 09:00:00', 2.0, 60, 3, 1),
(NULL, 'Actualizar inventario de oficina', 'Revisar y actualizar stock de suministros de oficina', 3, 1, 'low', 'pending', '2024-12-20 17:00:00', 3.0, 0, 2, 1),
(2, 'Verificar stock de productos A-001 a A-050', 'Contar físicamente productos de la categoría A', 4, 1, 'medium', 'pending', '2024-12-18 15:00:00', 4.0, 0, 2, 1);

-- Plantillas de flujos
INSERT INTO workflow_templates (name, description, category, department_id, template_data, created_by, company_id) VALUES
('Proceso de Contratación Estándar', 'Plantilla para el proceso completo de contratación de personal', 'Recursos Humanos', 1, '{"steps": [{"name": "Publicación de vacante", "duration": 2}, {"name": "Revisión de CV", "duration": 4}, {"name": "Entrevistas", "duration": 6}, {"name": "Referencias", "duration": 2}, {"name": "Oferta de trabajo", "duration": 1}]}', 1, 1),
('Mantenimiento Preventivo Equipos', 'Plantilla para mantenimiento mensual de equipos', 'Operaciones', 2, '{"steps": [{"name": "Inspección visual", "duration": 1}, {"name": "Lubricación", "duration": 2}, {"name": "Calibración", "duration": 3}, {"name": "Pruebas de funcionamiento", "duration": 2}]}', 1, 1);
```

---

## 🎯 Notas para el Diseñador UX/UI

### 🎨 **Paleta de Colores Sugerida**
- **Procesos**: `#2563eb` (azul) - Representa flujos y organización
- **Tareas**: `#059669` (verde) - Representa acción y completado
- **Vencidas**: `#dc2626` (rojo) - Alerta de urgencia
- **En Progreso**: `#d97706` (naranja) - Trabajo activo
- **Pausado**: `#6b7280` (gris) - Estado inactivo

### 📱 **Consideraciones de UX**
1. **Drag & Drop** - Implementar para reordenar columnas y tareas
2. **Filtros Visuales** - Chips de filtros activos visibles
3. **Estados Visuales** - Badges coloridos para estados y prioridades
4. **Progress Bars** - Barras de progreso animadas
5. **Notificaciones** - Toast notifications para acciones importantes
6. **Loading States** - Skeletons durante carga de datos
7. **Empty States** - Ilustraciones para vistas vacías

### 🔤 **Iconografía Sugerida**
- 🔄 Procesos
- ✅ Tareas
- 📊 Reportes
- ⚙️ Configuración
- 🎯 Objetivos
- ⏰ Tiempo/Vencimiento
- 👥 Asignaciones
- 📈 Progreso
- 🔥 Prioridad Alta
- ⚠️ Alertas

### 📱 **Responsive Breakpoints**
- **Mobile**: < 768px (vista de tarjetas apiladas)
- **Tablet**: 768px - 1024px (tabla simplificada)
- **Desktop**: > 1024px (tabla completa con todas las columnas)

---

## ✅ Checklist de Implementación

### 🗄️ **Base de Datos**
- [ ] Crear tablas principales (processes, tasks, etc.)
- [ ] Insertar datos de ejemplo
- [ ] Configurar índices para optimización
- [ ] Crear triggers para auditoría automática

### 🖥️ **Backend**
- [ ] Implementar `controller.php` con endpoints API
- [ ] Sistema de permisos por roles
- [ ] Validaciones de datos
- [ ] Manejo de errores y logging

### 🎨 **Frontend**
- [ ] Estructura HTML responsiva
- [ ] CSS con variables para temas
- [ ] JavaScript modular
- [ ] Integración con sistema de notificaciones existente

### 🔧 **Funcionalidades Core**
- [ ] CRUD de procesos
- [ ] CRUD de tareas
- [ ] Sistema de asignaciones
- [ ] Filtros y búsqueda
- [ ] Exportación CSV/PDF

### 📊 **Reportes y KPIs**
- [ ] Dashboard con métricas principales
- [ ] Gráficos de productividad
- [ ] Reportes por departamento/empleado
- [ ] Alertas de vencimientos

### 🔗 **Integraciones**
- [ ] Integración con módulo de Recursos Humanos
- [ ] Sistema de notificaciones por email
- [ ] Logs de auditoría
- [ ] Webhooks para eventos

---

**🚀 ¡Listo para implementar! El módulo sigue el patrón establecido y está preparado para integrarse perfectamente con el ecosistema SaaS existente.**
