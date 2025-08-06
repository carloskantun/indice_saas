# 📘 README.md — Indice SaaS Modular Platform

Sistema modular y escalable en PHP + MySQL que permite a múltiples empresas gestionar sus negocios, unidades, personal y servicios desde un solo ecosistema.

## 🎯 Objetivo

**Transformar** el sistema `Indice` (actualmente usado por elcorazondelcaribe.com) en una **plataforma SaaS multicliente**. La solución permite que un mismo usuario administre múltiples empresas, unidades o negocios, con jerarquías, roles y módulos personalizados.

**Visión:** Indice SaaS es una plataforma modular para empresas, diseñada para gestionar múltiples negocios y unidades operativas bajo un solo ecosistema. Este sistema permite escalar desde un solo usuario hasta una red de empresas y sucursales con roles jerárquicos y módulos dinámicos.

---

## ⚙️ Instalación y Configuración

### Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior  
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL

### Instalación Paso a Paso
1. **Configurar Base de Datos**: 
   ```bash
   # Crear base de datos
   CREATE DATABASE indice_saas;
   
   # Editar config.php con credenciales
   ```

2. **Ejecutar Instaladores**:
   ```bash
   # Instalar estructura base
   php install_database.php
   
   # Crear usuario root y planes SaaS
   php panel_root/create_plans_table.php
   
   # Instalar sistema de invitaciones
   php admin/install_missing_table.php
   
   # Instalar plantillas de permisos
   # Ir a: admin/complete_role_installation.php
   ```

### Credenciales Iniciales
- **Root**: `root@indiceapp.com` / `root123`
- **Admin Ejemplo**: `admin@indiceapp.com` / `admin123`

⚠️ **IMPORTANTE**: Cambiar credenciales en producción

---

## ✅ ESTRUCTURA DE ROLES Y JERARQUÍA

| Rol         | Descripción                                                       | Capacidades |
|-------------|-------------------------------------------------------------------|-------------|
| `root`       | Acceso total al sistema SaaS. Administra empresas, usuarios y planes. | Todas |
| `support`    | Soporte técnico limitado. No puede modificar cuentas ni empresas. | Solo lectura |
| `superadmin` | Propietario de empresas. Controla unidades, usuarios y módulos.  | Gestión empresarial |
| `admin`      | Administra una unidad o negocio dentro de una empresa.           | Gestión operativa |
| `moderator`  | Gerente de operación local. Supervisa tareas y registros.        | Supervisión |
| `user`       | Usuario operativo. Accede según permisos del sistema.            | Operación básica |

🔄 **Multi-Empresa**: Un mismo usuario puede tener múltiples roles en distintas empresas.

---

## 🧱 ARQUITECTURA Y ESCALAMIENTO

### Jerarquía de Entidades
```
👤 Usuario
└── 🏢 Empresa (múltiples)
    └── 🏭 Unidad de Negocio (regiones, áreas)
        └── 🏪 Negocio (sucursales físicas/digitales)
            └── 📦 Módulos Funcionales
```

### Estructura de Carpetas
```
indice_saas/
├── 📁 config.php              # Configuración global
├── 📁 index.php               # Punto de entrada principal
├── 📁 auth/                   # Sistema de autenticación
│   ├── index.php              # Login
│   ├── register.php           # Registro de usuarios
│   └── logout.php             # Cerrar sesión
├── 📁 companies/              # Gestión de empresas
├── 📁 units/                  # Gestión de unidades
├── 📁 businesses/             # Gestión de negocios
├── 📁 admin/                  # Panel de administración empresarial
│   ├── controller.php         # API para gestión de usuarios
│   ├── permissions_management.php # Sistema de permisos granulares
│   └── accept_invitation.php  # Aceptación de invitaciones
├── 📁 panel_root/             # Panel maestro del sistema SaaS
│   ├── index.php              # Dashboard root
│   ├── plans.php              # Gestión de planes SaaS
│   └── companies.php          # Todas las empresas del sistema
├── 📁 modules/                # Hub de módulos operativos
│   └── index.php              # Catálogo de módulos disponibles
├── 📁 indice-produccion/      # Sistema legacy (base funcional)
└── 📁 lang/                   # Archivos de idioma
    └── es.php                 # Español (idioma base)
```

---

## 🚀 FLUJO DE USUARIO

### Usuario Nuevo
1. **Registro** → `auth/register.php`
2. **Opción**:
   - ✅ Crear empresa (gratis con plan Free)
   - ✅ Unirse con código de invitación
3. **Dashboard** → Detecta contexto y muestra empresas disponibles

### Usuario Existente (Multi-Empresa)
1. **Recibe Invitación** → Email con token
2. **Acepta** → `admin/accept_invitation.php`
3. **Acceso** → Nuevo rol en empresa adicional

### Variables de Sesión
```php
$_SESSION['user_id']      // ID del usuario
$_SESSION['company_id']   // Empresa activa
$_SESSION['unit_id']      // Unidad activa (opcional)
$_SESSION['business_id']  // Negocio activo (opcional)
$_SESSION['current_role'] // Rol actual del usuario
```

---

## 📦 SISTEMA DE MÓDULOS

### Estructura Estándar
```
modules/[modulo]/
├── index.php              # Vista principal
├── controller.php         # Backend del módulo
├── js/[modulo].js         # Scripts JS y AJAX
├── modal_[funcion].php    # Modales reutilizables
├── kpis.php               # Indicadores clave
└── style.css              # Estilos locales
```

### Módulos Disponibles
| Módulo | Estado | Descripción |
|--------|--------|-------------|
| ✅ **Gastos** | Migrado | Control de ingresos y egresos (desde indice-produccion) |
| 🔜 **Mantenimiento** | Planeado | Control de servicios técnicos |
| 🔜 **Servicio Cliente** | Planeado | Gestión de tickets y soporte |
| 🔜 **Inventario** | Planeado | Control de stock y productos |
| 🔜 **Ventas** | Planeado | Facturación y gestión comercial |

### Características de Módulos
- 📊 **KPIs dinámicos** (Chart.js)
- ☑️ **Sumatorias con checkboxes**
- 🔍 **Filtros rápidos**
- 🖼️ **Carrusel de fotos**
- 📋 **Columnas ordenables/ocultables**
- 🔧 **Botones**: editar, duplicar, eliminar, PDF

---

## 🔐 SISTEMA DE PERMISOS

### Validación por Rol y Empresa
```php
// Verificar permiso específico
if (!hasPermission('gastos.view')) {
    exit('Access denied');
}

// Verificar rol en empresa actual
if (!checkRole(['admin', 'superadmin'])) {
    redirect('companies/');
}
```

### Permisos Granulares (Admin Panel)
- ✅ **Por Usuario**: Asignación individual de permisos
- ✅ **Por Módulo**: Control de ver, crear, editar, eliminar
- ✅ **Plantillas de Rol**: Aplicación automática según rol
- ✅ **Matriz Visual**: Interfaz para gestión masiva
- ✅ **Auditoría**: Log de cambios en permisos

### Archivo Central
📍 Permisos centralizados en `admin/permissions_manager.php`

---

## 🏗️ GESTIÓN DE PLANES SAAS

### Panel Root (`panel_root/`)
Solo usuarios con rol `root` pueden:
- 📊 **Crear/Editar Planes**: Free, Starter, Pro, Enterprise
- 🏢 **Gestionar Empresas**: Asignar planes y límites
- 📈 **Ver Estadísticas**: Uso y límites por empresa
- ⚠️ **Controlar Acceso**: Suspender o forzar upgrades

### Planes Predefinidos
| Plan | Empresas | Usuarios | Módulos | Precio |
|------|----------|----------|---------|--------|
| **Free** | 1 | 3 | 2 | $0 |
| **Starter** | 2 | 10 | 5 | $25 |
| **Pro** | 5 | 25 | 8 | $75 |
| **Enterprise** | ∞ | ∞ | Todos | Custom |

📥 **Detalles**: [`panel_root/README.md`](panel_root/README.md)

---

## 👥 GESTIÓN DE USUARIOS Y ROLES

### Sistema de Invitaciones
Los `superadmin` pueden:
- 📧 **Invitar usuarios** por correo electrónico
- 🎭 **Asignar roles** por empresa, unidad o negocio
- 🔑 **Controlar acceso** por módulos y acciones
- 👥 **Gestionar multi-empresa**: Un usuario, múltiples roles

### Casos de Uso Multi-Empresa
- **Freelancer**: Múltiples clientes con roles diferentes
- **Empleado Multi-Sucursal**: Acceso a varias ubicaciones
- **Consultor**: Permisos específicos por proyecto
- **Subcontratista**: Acceso temporal a empresas

📥 **Detalles**: [`admin/README.md`](admin/README.md)

---

## 🌍 INTERNACIONALIZACIÓN (i18n)

### Sistema Multiidioma
```php
// Carpeta /lang/
$lang['login'] = 'Iniciar sesión';
$lang['logout'] = 'Cerrar sesión';
$lang['welcome'] = 'Bienvenido';
```

- 🇪🇸 **Español** (es.php) como base
- 🇺🇸 **Inglés** estructuras preparadas
- 🔧 **Variables** en inglés para desarrollo

---

## 📁 ORGANIZACIÓN DE ARCHIVOS

### Componentes Reutilizables
| Carpeta | Uso |
|---------|-----|
| `/includes/` | Controladores globales |
| `/utils/` | Funciones comunes (auth, slugify, etc.) |
| `/components/` | Formularios, tablas, modales |

### Uploads y Almacenamiento
```
/uploads/[modulo]/[YYYY]/[MM]/archivo.ext
```

---

## 🧪 ESTADO ACTUAL DEL PROYECTO

### ✅ Completado
- 🏗️ **Arquitectura base** funcional
- 🎨 **UI/UX** con Bootstrap 5.3
- 👥 **Sistema multi-empresa** implementado
- 🔐 **Permisos granulares** operativos
- 📧 **Sistema de invitaciones** para usuarios nuevos/existentes
- 👑 **Panel Root** con gestión de planes SaaS
- 📦 **Primer módulo migrado**: Gastos (desde indice-produccion)

### 🔜 En Desarrollo
- 🧩 **Migración de módulos** adicionales
- 🌍 **Sistema i18n** completo
- 📱 **PWA** (Progressive Web App)
- 🔔 **Sistema de notificaciones**

### 🧪 Preparado Para
- ⚙️ **Codex y Copilot** integrados
- 🌍 **Internacionalización**
- 📊 **Analytics** y métricas
- 🔗 **APIs** externas

---

## 📚 Documentación Adicional

| Archivo | Descripción |
|---------|-------------|
| [`README_DATABASE.md`](README_DATABASE.md) | Estructura SQL completa |
| [`README_SAAS.md`](README_SAAS.md) | Detalles técnicos del sistema |
| [`README_PLANES.md`](README_PLANES.md) | Gestión de planes SaaS |
| [`AGENTS.md`](AGENTS.md) | Módulos, rutas y permisos |
| [`admin/README.md`](admin/README.md) | Panel de administración |
| [`panel_root/README.md`](panel_root/README.md) | Panel maestro root |

---

## 🚀 Desarrollo y Contribución

### Tecnologías
- **Backend**: PHP 7.4+ nativo, MySQL
- **Frontend**: Bootstrap 5.3, Font Awesome 6, JavaScript Vanilla
- **Arquitectura**: MVC modular, API REST
- **Seguridad**: PDO, sesiones seguras, validación de permisos

### Próximos Módulos
1. **Mantenimiento** - Control de servicios técnicos
2. **Servicio al Cliente** - Gestión de tickets
3. **Inventario** - Control de stock
4. **Ventas** - Facturación y comercial

---

**🎯 Objetivo**: Convertir Indice en la plataforma SaaS líder para gestión empresarial modular, escalable desde startups hasta networks corporativos.

*Desarrollado con ❤️ para la gestión empresarial del futuro.*
