# Panel Root - Gestión de Planes SaaS

## 📋 Descripción

El módulo `panel_root/` es el panel de administración exclusivo para usuarios con rol `root` en el sistema Indice SaaS. Permite gestionar los planes SaaS que definen las capacidades y límites de las empresas registradas.

## 🚀 Instalación

### 1. Crear las tablas base del sistema

Primero ejecuta el instalador principal:

```bash
php install_database.php
```

### 2. Crear tabla de planes y usuario root

Ejecuta el script para crear la tabla `plans` y configurar el usuario root:

```bash
php create_plans_table.php
```

Este script creará:
- Tabla `plans` con estructura completa
- Columna `plan_id` en tabla `companies` 
- 4 planes predefinidos (Free, Starter, Pro, Enterprise)
- Usuario root automático: `root@indiceapp.com` / `root123`

### 3. Scripts adicionales disponibles

```bash
# Crear usuario root adicional (interactivo)
php create_root_user.php

# Listar todos los usuarios y roles
php list_users.php
```

### 2. Configurar permisos

El sistema usa una estructura de roles basada en la tabla `user_companies`. Para crear un usuario root, ejecuta el script `create_plans_table.php` que automáticamente creará:

- Un usuario root con email `root@indiceapp.com` 
- Una empresa "Sistema" para administración
- La relación usuario-empresa con rol 'root'

```sql
-- Estructura de roles en user_companies
-- role ENUM('root', 'support', 'superadmin', 'admin', 'moderator', 'user')

-- Verificar usuarios root existentes:
SELECT u.name, u.email, uc.role, c.name as company 
FROM users u 
INNER JOIN user_companies uc ON u.id = uc.user_id 
INNER JOIN companies c ON uc.company_id = c.id
WHERE uc.role = 'root';
```

El script también agregará la columna `plan_id` a la tabla `companies` para relacionar cada empresa con su plan SaaS.

## 📁 Estructura del módulo

```
panel_root/
├── index.php              # Dashboard principal del root
├── plans.php              # Vista CRUD de planes SaaS
├── controller.php         # Lógica central AJAX
├── js/
│   └── root_panel.js      # Acciones JS para tablas, formularios
└── modals/
    ├── modal_add_plan.php # Modal para agregar planes
    └── modal_edit_plan.php # Modal para editar planes
```

## 🔐 Seguridad

- **Acceso restringido**: Solo usuarios con `$_SESSION['current_role'] === 'root'` pueden acceder
- **Validación de datos**: Todos los inputs son validados tanto en frontend como backend
- **Protección CSRF**: Los formularios incluyen tokens de seguridad
- **Sanitización**: Todos los datos se sanitizan antes de mostrarlos

## 🎯 Funcionalidades

### Dashboard Principal (`index.php`)
- Estadísticas generales del sistema
- Contadores de planes activos/inactivos
- Planes más utilizados
- Acceso rápido a funciones principales

### Gestión de Planes (`plans.php`)
- **Ver todos los planes**: Tabla con información completa
- **Crear nuevo plan**: Modal con formulario completo
- **Editar plan**: Modal prellenado con datos actuales
- **Eliminar plan**: Con validación de uso
- **Estado del plan**: Activar/desactivar planes

### Campos de Plan
- **Información básica**: Nombre, descripción, precio mensual
- **Límites**: Usuarios, unidades, negocios, almacenamiento
- **Módulos**: Selección múltiple de funcionalidades incluidas
- **Estado**: Activo/inactivo

## 🛠️ API Endpoints (controller.php)

### `create_plan`
```javascript
POST controller.php
{
    action: 'create_plan',
    name: 'Plan Pro',
    description: 'Plan profesional...',
    price_monthly: 75.00,
    modules_included: ['gastos', 'mantenimiento', ...],
    users_max: 25,
    units_max: 10,
    businesses_max: 25,
    storage_max_mb: 2000,
    is_active: 1
}
```

### `update_plan`
```javascript
POST controller.php
{
    action: 'update_plan',
    id: 1,
    // ... mismos campos que create_plan
}
```

### `delete_plan`
```javascript
POST controller.php
{
    action: 'delete_plan',
    id: 1
}
```

### `get_plan`
```javascript
GET controller.php?action=get_plan&id=1
```

### `get_modules`
```javascript
GET controller.php?action=get_modules
```

### `check_plan_usage`
```javascript
GET controller.php?action=check_plan_usage&id=1
```

## 🎨 Diseño y UX

- **Bootstrap 5**: Framework CSS moderno
- **Font Awesome 6**: Iconos consistentes
- **SweetAlert2**: Alertas y confirmaciones elegantes
- **Diseño responsivo**: Compatible con móviles y tablets
- **Sidebar fijo**: Navegación persistente
- **Cards estadísticas**: Visualización clara de métricas

## 📊 Planes Predefinidos

| Plan | Precio | Usuarios | Unidades | Negocios | Storage | Módulos |
|------|--------|----------|----------|----------|---------|---------|
| Free | $0 | 3 | 1 | 1 | 100MB | 2 básicos |
| Starter | $25 | 10 | 5 | 10 | 500MB | 5 módulos |
| Pro | $75 | 25 | 10 | 25 | 2GB | 8 módulos |
| Enterprise | $200 | Ilimitado | Ilimitado | Ilimitado | Ilimitado | Todos |

## 🔧 Configuración

### Variables de entorno
```env
DB_HOST=localhost
DB_NAME=indice_saas
DB_USER=usuario
DB_PASS=contraseña
```

### Constantes importantes
```php
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');
```

## 🐛 Solución de problemas

### Error: "Acceso denegado"
- Verificar que el usuario tenga rol 'root' en la tabla `user_companies`
- Comprobar que la sesión esté activa con `$_SESSION['user_id']`
- Revisar función `checkRole(['root'])` en `config.php`
- Verificar que el usuario esté activo: `uc.status = 'active'`

### Error: "Plan en uso"
- No se puede eliminar un plan que esté asignado a empresas
- Verificar tabla `companies` con `plan_id`
- Revisar consulta en `controller.php` función `deletePlan()`

### Error de conexión a BD
- Verificar credenciales en archivo `.env`
- Comprobar que las tablas `plans`, `companies`, `user_companies` existan
- Ejecutar `install_database.php` y luego `create_plans_table.php`

### Error: "Usuario root no encontrado"
- Ejecutar `create_plans_table.php` para crear usuario root automáticamente
- Verificar en BD: `SELECT * FROM user_companies WHERE role = 'root'`
- Credenciales por defecto: `root@indiceapp.com` / `root123`

## 🔄 Próximas mejoras

- [ ] Histórico de cambios en planes
- [ ] Notificaciones por email
- [ ] Exportación de reportes
- [ ] API REST completa
- [ ] Integración con sistemas de pago
- [ ] Dashboard avanzado con gráficos

## 📝 Notas de desarrollo

- Utilizar `json_encode()` para almacenar módulos en BD
- Validar límites con `-1` para "ilimitado"
- Mantener consistencia en nombres de variables
- Documentar cambios en este README

📊 Panel Root — Administración Global del SaaS
markdown
Copiar
Editar
El sistema cuenta con un `panel_root/` exclusivo para usuarios con rol `root`, desde donde se pueden gestionar:

- ✅ Planes del sistema (CRUD completo)
- ✅ Empresas registradas y su plan activo
- ✅ Módulos disponibles y su activación global
- ✅ Usuarios registrados y sus roles cruzados
- ✅ KPIs globales: ingresos, empresas, usuarios, uso de módulos