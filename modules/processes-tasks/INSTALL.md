# Módulo Procesos y Tareas - Guía de Instalación

## Instalación del Módulo

### 1. Verificar Prerequisitos

Antes de instalar el módulo, asegúrate de tener:

- Sistema SaaS Indice funcionando
- Base de datos MySQL/MariaDB configurada
- Al menos una empresa (company) creada
- Departamentos y empleados registrados en el módulo de Recursos Humanos

### 2. Ejecutar Script de Instalación

```sql
-- Conectar a la base de datos del sistema
USE nombre_de_tu_base_de_datos;

-- Ejecutar el script de instalación principal
SOURCE /path/to/modules/processes-tasks/sql/install.sql;
```

### 3. Cargar Datos de Ejemplo (Opcional)

Para facilitar las pruebas y demostración:

```sql
-- Ejecutar después del script de instalación
SOURCE /path/to/modules/processes-tasks/sql/sample_data.sql;
```

### 4. Verificar Instalación

```sql
-- Verificar que las tablas se crearon correctamente
SHOW TABLES LIKE '%process%';
SHOW TABLES LIKE '%task%';

-- Verificar datos de ejemplo (si los cargaste)
SELECT 'Procesos' as Tabla, COUNT(*) as Registros FROM processes
UNION ALL
SELECT 'Tareas', COUNT(*) FROM tasks
UNION ALL
SELECT 'Plantillas', COUNT(*) FROM workflow_templates;
```

### 5. Configurar Permisos del Sistema

En el panel de administración del sistema:

1. Ir a **Configuración → Módulos**
2. Activar el módulo "Procesos y Tareas"
3. Configurar permisos por rol:
   - **Root/Superadmin**: Acceso completo
   - **Admin**: Crear/editar procesos y tareas
   - **Moderator**: Gestionar tareas asignadas
   - **User**: Ver tareas propias y reportar progreso

### 6. Configuración Inicial

#### 6.1 Configurar Notificaciones

En `config.php` del módulo, ajustar:

```php
// Configuración de notificaciones
'notifications' => [
    'email_enabled' => true,
    'task_reminders' => true,
    'deadline_alerts' => 48, // horas antes del vencimiento
    'overdue_escalation' => 24 // horas después del vencimiento
]
```

#### 6.2 Personalizar Estados y Prioridades

Modificar los arrays de configuración según las necesidades de tu empresa:

```php
'task_statuses' => [
    'pending' => 'Pendiente',
    'in_progress' => 'En Progreso', 
    'review' => 'En Revisión',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada'
]
```

### 7. Integración con Otros Módulos

#### 7.1 Módulo de Recursos Humanos
- Las tareas se pueden asignar a empleados registrados
- Los departamentos se utilizan para organizar procesos
- La jerarquía de permisos se basa en roles de RRHH

#### 7.2 Módulo de Gastos (si está instalado)
- Los procesos pueden generar gastos automáticamente
- Las tareas pueden tener presupuestos asociados

### 8. Acceso al Módulo

Una vez instalado, el módulo estará disponible en:

- **URL**: `/modules/processes-tasks/`
- **Menú del sistema**: "Procesos y Tareas"
- **Permisos**: Según configuración por rol

### 9. Primer Uso

1. **Crear tu primer proceso**:
   - Ir a la pestaña "Procesos"
   - Hacer clic en "Nuevo Proceso"
   - Definir etapas y responsables

2. **Asignar tareas**:
   - Ir a la pestaña "Tareas"
   - Crear tarea individual o desde proceso
   - Asignar a empleado específico

3. **Usar plantillas**:
   - Ir a la pestaña "Plantillas"
   - Crear proceso desde plantilla existente
   - Personalizar según necesidades

### 10. Solución de Problemas Comunes

#### Error: "Tabla no existe"
```sql
-- Verificar que se ejecutó correctamente el script de instalación
SHOW CREATE TABLE processes;
```

#### Error: "Usuario sin permisos"
- Verificar rol del usuario en la tabla `users`
- Comprobar configuración de permisos en `config.php`

#### Tareas no se muestran
- Verificar filtro de empresa (`company_id`)
- Comprobar permisos de departamento

### 11. Configuración de Automatización

Para activar las reglas de automatización:

```sql
-- Crear evento programado para verificar tareas vencidas (cada hora)
CREATE EVENT IF NOT EXISTS check_overdue_tasks
ON SCHEDULE EVERY 1 HOUR
DO
  UPDATE tasks 
  SET status = 'overdue' 
  WHERE due_date < NOW() 
    AND status IN ('pending', 'in_progress')
    AND company_id = 1; -- Ajustar según tu company_id
```

### 12. Mantenimiento

#### Limpieza periódica de historial
```sql
-- Limpiar historial mayor a 1 año (ejecutar mensualmente)
DELETE FROM task_history 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

#### Backup de datos importantes
```sql
-- Backup de configuraciones críticas
SELECT * FROM process_automation_rules WHERE company_id = 1;
SELECT * FROM workflow_templates WHERE company_id = 1;
```

### 13. Próximos Pasos

Después de la instalación exitosa:

1. **Personalización de UI**: El diseñador UX/UI puede ajustar estilos y layouts
2. **Capacitación de usuarios**: Entrenar al equipo en el uso del módulo
3. **Migración de procesos existentes**: Digitalizar flujos de trabajo actuales
4. **Configuración de reportes**: Personalizar métricas y KPIs

### Soporte

Para más información, consultar:
- `README.md` - Documentación completa del módulo
- Archivos de configuración en `/modules/processes-tasks/config.php`
- Logs del sistema para diagnóstico de problemas

---

**¡El módulo está listo para usar!** 🚀
