# 🚀 GUÍA DE INSTALACIÓN MANUAL EN SERVIDOR

## 📋 Pasos a seguir en app.indiceapp.com

### 1. ✅ Verificar que todos los archivos estén subidos
Asegúrate de que estos archivos estén en tu servidor:

```
admin/
├── index.php
├── controller.php
├── accept_invitation.php
├── install_admin_tables.php
├── email_config.php
├── modals/
│   ├── invite_user_modal.php
│   └── edit_user_modal.php
└── js/
    └── admin_users.js
```

### 2. 🗄️ Crear las tablas de base de datos

**OPCIÓN A (Recomendada) - Instalación completa:**
```
https://app.indiceapp.com/admin/install_admin_tables.php
```

**OPCIÓN B - Si hay errores con el trigger:**
```
https://app.indiceapp.com/admin/complete_installation.php
```

**Esto creará automáticamente:**
- ✅ Tabla `invitaciones`
- ✅ Tabla `user_companies` (si no existe)
- ✅ Tabla `user_units`
- ✅ Tabla `user_businesses`
- ✅ Tabla `permissions`
- ✅ Tabla `role_permissions`
- ✅ Trigger para fechas de expiración (si es posible)
- ✅ Permisos básicos del sistema

### 2.1 🔧 Si aparece error de sintaxis SQL
Si ves el error `SQLSTATE[42000]: Syntax error`, usa el script alternativo:
```
https://app.indiceapp.com/admin/complete_installation.php
```

Este script evita problemas de sintaxis específicos de MySQL y completa la instalación de forma segura.

### 3. 🔧 Verificar la instalación
**Para verificar que todo esté correcto:**
```
https://app.indiceapp.com/admin/verify_system.php
```

Este script mostrará:
- ✅ Estado de todas las tablas
- ✅ Permisos configurados
- ✅ Usuarios con roles administrativos
- ✅ Archivos del sistema

### 4. 🔑 Asignar rol de administrador
**IMPORTANTE:** Si no tienes usuarios administradores, ejecuta en tu base de datos:

```sql
-- Reemplaza 'tu_email@ejemplo.com' con tu email real
-- Reemplaza 1 con el ID de tu empresa

INSERT IGNORE INTO user_companies (user_id, company_id, role, status) 
SELECT u.id, 1, 'superadmin', 'active'
FROM users u 
WHERE u.email = 'tu_email@ejemplo.com';
```

### 5. 🎯 Acceder al sistema
**URL principal del sistema admin:**
```
https://app.indiceapp.com/admin/
```

**Requisitos de acceso:**
- Usuario con rol `superadmin` o `admin`
- Empresa activa en la sesión

---

## 🔑 CONFIGURACIÓN INICIAL REQUERIDA

### Asignar rol de superadmin a tu usuario
Si necesitas asignar el rol de superadmin a tu usuario, ejecuta esta consulta SQL directamente en tu base de datos:

```sql
-- Reemplaza 'tu_email@ejemplo.com' con tu email real
-- Reemplaza 1 con el ID de tu empresa

INSERT IGNORE INTO user_companies (user_id, company_id, role, status) 
SELECT u.id, 1, 'superadmin', 'active'
FROM users u 
WHERE u.email = 'tu_email@ejemplo.com';
```

### Verificar que existan empresas
Si no tienes empresas creadas, asegúrate de tener al menos una:

```sql
SELECT * FROM companies;
```

---

## 📧 CONFIGURACIÓN DE EMAIL (OPCIONAL)

### Para activar el envío de emails de invitación:

1. **Edita el archivo `admin/email_config.php`**
2. **Descomenta y configura las líneas SMTP según tu proveedor:**

```php
// Para Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tu-email@gmail.com');
define('SMTP_PASSWORD', 'tu-app-password');
```

3. **El sistema funcionará sin configuración de email** (las invitaciones se crearán en la base de datos)

---

## 🧪 PRUEBAS DEL SISTEMA

### Después de la instalación, verifica:

1. **Acceso al panel admin:**
   - Ve a `https://app.indiceapp.com/admin/`
   - Deberías ver la interfaz de gestión de usuarios

2. **Crear una invitación de prueba:**
   - Haz clic en "Invitar Usuario"
   - Completa el formulario
   - Verifica que se guarde en la base de datos

3. **Probar aceptación de invitación:**
   - Ve a la pestaña "Invitaciones"
   - Copia el token de una invitación
   - Accede a: `https://app.indiceapp.com/admin/accept_invitation.php?token=TU_TOKEN`

---

## 🔍 VERIFICACIÓN DE TABLAS

### Consultas SQL para verificar que todo esté bien:

```sql
-- Verificar que las tablas se crearon
SHOW TABLES LIKE '%invitaciones%';
SHOW TABLES LIKE '%user_companies%';
SHOW TABLES LIKE '%permissions%';

-- Ver invitaciones creadas
SELECT * FROM invitaciones;

-- Ver permisos del sistema
SELECT * FROM permissions;

-- Ver asignaciones de roles
SELECT * FROM role_permissions;
```

---

## 🚨 SOLUCIÓN DE PROBLEMAS

### Si algo no funciona:

1. **Verificar logs de PHP en el servidor**
2. **Revisar la consola del navegador (F12)**
3. **Verificar que todas las tablas se crearon correctamente**
4. **Asegurar que tu usuario tenga rol `superadmin`**

### URLs importantes:
- **Panel Admin:** `https://app.indiceapp.com/admin/`
- **Instalación completa:** `https://app.indiceapp.com/admin/install_admin_tables.php`
- **Instalación alternativa:** `https://app.indiceapp.com/admin/complete_installation.php`
- **Verificación del sistema:** `https://app.indiceapp.com/admin/verify_system.php`
- **Aceptar invitación:** `https://app.indiceapp.com/admin/accept_invitation.php?token=...`

---

## ✅ CHECKLIST DE INSTALACIÓN

- [ ] Archivos subidos al servidor
- [ ] Ejecutado `install_admin_tables.php` O `complete_installation.php` en el navegador
- [ ] Verificado instalación con `verify_system.php`
- [ ] Verificado que las tablas se crearon correctamente
- [ ] Asignado rol `superadmin` a tu usuario con SQL
- [ ] Probado acceso a `/admin/`
- [ ] Probado crear una invitación
- [ ] (Opcional) Configurado SMTP para emails

---

**🎉 ¡Sistema listo para usar!**

Una vez completados estos pasos, tendrás un sistema completamente funcional de gestión de usuarios administrativos con invitaciones, roles y permisos granulares.
