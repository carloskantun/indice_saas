# Admin Panel - Gestión de Empresa

## 🎯 Descripción

Sistema de administración mejorado para usuarios con rol `superadmin`, permitiendo la gestión completa de usuarios, roles y permisos por empresa.

## ✅ Funcionalidades Implementadas

### 1. Acceso desde Companies
- **Botón "Admin"**: Visible solo para usuarios con rol `superadmin` en `/companies/index.php`
- **Validación de permisos**: Solo superadmins pueden acceder al panel de administración de cada empresa

### 2. Panel Principal (`/admin/index.php`)
- **Dashboard con métricas**: Usuarios totales, invitaciones pendientes, unidades y negocios
- **Navegación por empresa**: Muestra el nombre de la empresa activa
- **Acciones rápidas**: Invitar usuarios y acceso directo a gestión
- **Validación de empresa**: Solo permite gestionar empresas donde el usuario es superadmin

### 3. Gestión de Usuarios (`/admin/usuarios.php`)
- **Tabla completa de usuarios** con información detallada:
  - Nombre y email del usuario
  - Rol actual en la empresa
  - Unidad y negocio asignados
  - Estado de acceso (activo/inactivo/pendiente)
  - Último acceso registrado
  - Estado de invitación
- **Acciones disponibles**:
  - Editar rol del usuario
  - Cambiar unidad y negocio de trabajo
  - Activar/desactivar acceso
  - Ver historial de actividad
- **Invitación de usuarios**:
  - Modal con selección de rol, unidad y negocio
  - Validación de email único
  - Envío automático de invitación

### 4. Gestión de Roles (`/admin/roles.php`)
- **Vista de roles disponibles**:
  - SuperAdmin: Control total de la empresa
  - Admin: Gestión avanzada
  - Moderador: Supervisión de operaciones
  - Usuario: Acceso básico
- **Estadísticas por rol**: Total de usuarios y usuarios activos
- **Descripción de permisos**: Explicación clara de cada rol

### 5. Gestión de Permisos (`/admin/permisos.php`)
- **Vista previa de funcionalidad futura**
- **Matriz de permisos por módulo**:
  - Ver, Crear, Editar, Eliminar, Admin
  - Control granular por usuario y módulo
- **Preparado para Fase 2**: Estructura lista para implementación completa

## 🛠️ Backend (controller.php)

### Nuevas Acciones Implementadas
- `load_users`: Carga usuarios con información completa
- `update_role`: Actualiza rol de usuario
- `assign_unit`: Asigna unidad y negocio a usuario
- `toggle_access`: Activa/desactiva acceso de usuario

### Validaciones de Seguridad
- Verificación de rol superadmin
- Validación de empresa activa
- Comprobación de permisos por empresa
- Sanitización de datos de entrada

## 🎨 Frontend

### Estilo Visual
- **Bootstrap 5.3**: Framework CSS moderno
- **SweetAlert2**: Confirmaciones elegantes
- **Gradientes**: Sidebar con gradiente morado/azul
- **Animaciones**: Efectos de hover y transiciones suaves
- **Cards modernas**: Bordes redondeados y sombras

### JavaScript
- **admin_users.js**: Funcionalidades AJAX para gestión de usuarios
- **Carga dinámica**: Tablas y modales actualizados en tiempo real
- **Validaciones**: Formularios con validación en frontend y backend

## 🔒 Seguridad

### Control de Acceso
- Solo usuarios con rol `superadmin` pueden acceder
- Validación en cada página de la empresa activa
- Verificación de permisos en base de datos
- Redirección automática si no tiene permisos

### Validación de Datos
- Sanitización de inputs en PHP
- Validación de emails y roles
- Protección contra inyección SQL con prepared statements
- Validación de empresa en cada acción

## 📋 Estructura de Archivos

```
/admin/
├── index.php              # Dashboard principal
├── usuarios.php           # Gestión de usuarios
├── roles.php              # Información de roles
├── permisos.php           # Gestión de permisos (Fase 2)
├── controller.php         # Backend API
├── modals/
│   ├── invite_user_modal.php    # Modal para invitar usuarios
│   └── edit_user_modal.php      # Modal para editar usuarios
└── js/
    └── admin_users.js      # JavaScript para funcionalidades
```

## 🌐 Idioma y Localización

### Nuevas Claves en `lang/es.php`
- `admin_company`: "Administrar Empresa"
- `company_users`: "Usuarios de la Empresa"
- `company_roles`: "Roles de la Empresa"
- `company_permissions`: "Permisos de la Empresa"
- `working_unit`: "Unidad de Trabajo"
- `working_business`: "Negocio de Trabajo"
- `invitation_status`: "Estado de Invitación"
- `access_enabled/disabled`: Estados de acceso
- Y muchas más...

## 🚀 Cómo Usar

### Para SuperAdmin:
1. **Acceder**: Desde `/companies/` hacer clic en "Admin" de una empresa
2. **Dashboard**: Ver métricas generales y acciones rápidas
3. **Gestionar Usuarios**: Acceder a usuarios específicos por empresa
4. **Invitar Nuevos**: Usar el modal de invitación con rol y asignaciones
5. **Configurar Roles**: Ver y entender los roles disponibles

### Flujo de Invitación:
1. SuperAdmin invita usuario con email y rol
2. Se puede asignar unidad y negocio específicos
3. Usuario recibe invitación por email
4. Usuario acepta y crea cuenta
5. Aparece en la lista con estado "activo"

## 🔮 Roadmap - Fase 2

### Funcionalidades Pendientes:
- [ ] Permisos granulares por módulo
- [ ] Roles personalizados por empresa
- [ ] Plantillas de permisos
- [ ] Herencia de permisos
- [ ] Auditoría de cambios
- [ ] Notificaciones de actividad
- [ ] Exportación de reportes de usuarios

### Mejoras Técnicas:
- [ ] Cache de permisos
- [ ] API REST para integraciones
- [ ] WebSockets para actualizaciones en tiempo real
- [ ] Logs de auditoría
- [ ] Backup automático de configuraciones

## 📞 Soporte

Para dudas o mejoras en el sistema de administración, consultar la documentación técnica o contactar al equipo de desarrollo.
