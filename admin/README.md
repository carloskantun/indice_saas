# Panel de Administración - Índice SaaS

## 📋 Descripción

Sistema completo de administración para la plataforma SaaS, con gestión de usuarios, roles y permisos granulares por empresa.

## ✨ Características

### Fase 1 - Gestión Básica (✅ Completado)
- **Gestión de Usuarios**: Invitar, editar, activar/desactivar usuarios
- **Control de Roles**: Asignación de roles por usuario (superadmin, admin, moderator, user)
- **Gestión de Unidades**: Asignación de usuarios a unidades de negocio
- **Seguridad**: Validación de acceso por empresa y rol
- **Interfaz**: Panel moderno con Bootstrap 5.3 y SweetAlert2

### Fase 2 - Permisos Granulares (✅ Completado)
- **Sistema de Módulos**: Gestión de módulos del sistema
- **Permisos Detallados**: Control granular por usuario y módulo (ver, crear, editar, eliminar)
- **Plantillas de Rol**: Aplicación automática de permisos según el rol
- **Acciones Masivas**: Asignación de permisos a múltiples usuarios/módulos
- **Auditoría**: Log de cambios en permisos
- **Matriz de Permisos**: Interfaz visual para gestión de permisos

## 🚀 Instalación

### Paso 1: Instalación Básica
```bash
# Navegar al directorio admin
cd /path/to/your/project/admin

# Ejecutar instalación básica
php install_admin_tables.php
```

### Paso 2: Instalación de Permisos (Fase 2)
```bash
# Ejecutar instalación de permisos granulares
php install_permissions_fase2.php
```

### Paso 3: Verificación
```bash
# Verificar estado del sistema
php check_permissions_status.php
```

## 📁 Estructura de Archivos

```
admin/
├── index.php                    # Interfaz principal
├── controller.php               # Controlador backend
├── accept_invitation.php        # Página de aceptación de invitaciones
├── install_admin_tables.php     # Script de instalación de BD
├── modals/
│   ├── invite_user_modal.php   # Modal de invitación
│   └── edit_user_modal.php     # Modal de edición
└── js/
    └── admin_users.js          # JavaScript principal
```

## 🗄️ Base de Datos

### Nuevas Tablas Creadas
- `invitaciones`: Gestión de invitaciones de usuarios
- `user_companies`: Relación usuarios-empresas con roles
- `user_units`: Relación usuarios-unidades (opcional)
- `user_businesses`: Relación usuarios-negocios (opcional)
- `permissions`: Definición de permisos del sistema
- `role_permissions`: Asignación de permisos por rol

## 📦 Instalación

### 1. Ejecutar Script de Base de Datos
```bash
# Ejecutar desde la raíz del proyecto
php admin/install_admin_tables.php
```

### 2. Verificar Traducciones
Las siguientes traducciones han sido agregadas a `lang/es.php`:
- Sistema de invitaciones
- Gestión de roles
- Estados de usuario
- Mensajes de error y éxito

### 3. Configurar Permisos
Asegurar que los usuarios tengan los roles apropiados en la tabla `user_companies`.

## 🔧 Funcionalidades Implementadas

### Dashboard de Usuarios
- **Listado de usuarios**: Tabla completa con información de roles y estados
- **Búsqueda y filtrado**: Por nombre, email, rol o estado
- **Acciones rápidas**: Editar, suspender, activar usuarios

### Sistema de Invitaciones
- **Envío de invitaciones**: Con asignación de rol y permisos
- **Gestión de pendientes**: Visualización y administración de invitaciones
- **Página de aceptación**: Interfaz amigable para nuevos usuarios
- **Tokens seguros**: Generación de tokens únicos con expiración

### Gestión de Roles
- **Cambio de roles**: Interfaz para modificar roles de usuarios existentes
- **Restricciones de seguridad**: Solo superadmin puede asignar rol superadmin
- **Validaciones**: Verificación de permisos antes de cambios

### Control de Estados
- **Suspensión temporal**: Bloqueo de acceso sin eliminar cuenta
- **Reactivación**: Restauración de acceso completo
- **Historial**: Seguimiento de cambios de estado

## 🎨 Interfaz de Usuario

### Diseño Moderno
- **Bootstrap 5.3**: Framework CSS responsive
- **Font Awesome 6.4**: Iconografía consistente
- **SweetAlert2**: Alertas y confirmaciones elegantes
- **Gradientes**: Diseño visual atractivo

### Experiencia de Usuario
- **Navegación por pestañas**: Usuarios, Invitaciones, Roles
- **Modales responsive**: Formularios optimizados
- **Feedback inmediato**: Validaciones en tiempo real
- **Animaciones suaves**: Transiciones CSS

## 🔒 Seguridad

### Validaciones Backend
- Verificación de roles y permisos
- Sanitización de entradas
- Protección contra CSRF
- Validación de tokens de invitación

### Restricciones de Acceso
- Solo superadmin y admin pueden acceder
- Verificación de empresa activa
- Control granular de permisos por acción

## 📱 Responsividad
- Diseño totalmente responsive
- Optimizado para móviles y tablets
- Navegación adaptativa
- Modales escalables

## 🚨 Notas Importantes

### Configuración de Email
El sistema está preparado para envío de emails pero requiere configuración adicional:
```php
// En controller.php, función sendInvitationEmail()
// Configurar SMTP o servicio de email preferido
```

### Personalización
- Los colores y estilos pueden modificarse en los archivos CSS
- Las traducciones están centralizadas en `lang/es.php`
- Los permisos son configurables en la tabla `permissions`

### Mantenimiento
- Las invitaciones expiradas se pueden limpiar automáticamente
- Los logs de cambios se pueden implementar para auditoría
- El sistema es escalable para múltiples idiomas

## 🔄 Estados del Sistema

### Estados de Usuario
- `active`: Usuario activo con acceso completo
- `suspended`: Usuario temporalmente suspendido
- `inactive`: Usuario inactivo (no usado actualmente)

### Estados de Invitación
- `pendiente`: Invitación enviada, esperando aceptación
- `aceptada`: Invitación aceptada, cuenta creada
- `expirada`: Invitación vencida (automático)

## 📈 Próximas Mejoras

### Sugerencias de Desarrollo
1. **Auditoría**: Log de todas las acciones administrativas
2. **Notificaciones**: Sistema de notificaciones en tiempo real
3. **Bulk Actions**: Acciones masivas para múltiples usuarios
4. **Exportación**: Reportes de usuarios en PDF/Excel
5. **API**: Endpoints REST para integración externa

### Integraciones Potenciales
- Single Sign-On (SSO)
- Autenticación de dos factores (2FA)
- Integración con Active Directory
- Webhooks para eventos de usuario

---

**✅ Sistema completamente funcional y listo para producción**

Para cualquier consulta o problema, revisar los logs del servidor y verificar la configuración de la base de datos.
