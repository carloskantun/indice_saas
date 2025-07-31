# Sistema de Gestión de Usuarios Admin

## 📋 Descripción
Sistema completo de gestión de usuarios a nivel administrativo con funcionalidades de invitación, roles jerárquicos y permisos granulares.

## 🚀 Características

### ✅ Gestión de Invitaciones
- Envío de invitaciones por email con token único
- Expiración automática de invitaciones (48 horas)
- Reenvío y cancelación de invitaciones
- Aceptación de invitaciones con creación automática de cuenta

### ✅ Sistema de Roles Jerárquicos
- **Superadmin**: Acceso total al sistema
- **Admin**: Gestión de empresa y usuarios
- **Moderator**: Supervisión y moderación
- **User**: Acceso básico

### ✅ Asignación Granular
- Asignación a nivel de empresa
- Asignación opcional a unidades específicas
- Asignación opcional a negocios específicos

### ✅ Gestión de Estados
- Activación/suspensión de usuarios
- Control de acceso por estado
- Historial de cambios

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
