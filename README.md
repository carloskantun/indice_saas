## 📘 README.md — Indice SaaS Modular Platform
Sistema modular y escalable en PHP + MySQL que permite a múltiples empresas gestionar sus negocios, unidades, personal y servicios desde un solo ecosistema.
### 🎯 Objetivo
Primer Objetivo: Transformar el sistema `Indice` (actualmente usado por elcorazondelcaribe.com) en una plataforma SaaS multicliente. La solución permitirá que un mismo usuario administre múltiples empresas, unidades o negocios, con jerarquías, roles y módulos personalizados.

Despues dejar: Indice SaaS es una plataforma modular para empresas, diseñada para gestionar múltiples negocios y unidades operativas bajo un solo ecosistema. Este sistema permite escalar desde un solo usuario hasta una red de empresas y sucursales con roles jerárquicos y módulos dinámicos.


## ✅ ESTRUCTURA DE ROLES Y JERARQUÍA

| Rol         | Descripción                                                       |
|-------------|--------------------------------------------------------------------|
| `root`       | Acceso total al sistema SaaS. Administra empresas, usuarios y planes. |
| `support`    | Soporte técnico limitado. No puede modificar cuentas ni empresas. |
| `superadmin` | Propietario de empresas. Controla unidades, usuarios y módulos.  |
| `admin`      | Administra una unidad o negocio dentro de una empresa.           |
| `moderator`  | Gerente de operación local. Supervisa tareas y registros.        |
| `user`       | Usuario operativo. Accede según permisos del sistema.            |

🔄 Un mismo usuario puede tener múltiples roles en distintas empresas.

---

## 🧱 ESCALAMIENTO: JERARQUÍA DE ENTIDADES

usuario → empresas → unidades → negocios (opcional)

Estructura flexible para startups, empresas o trabajadores con múltiples negocios.

📂 Carpetas base:

| Carpeta         | Descripción                                  |
|------------------|----------------------------------------------|
| `/companies/`     | Empresas creadas por superadmins            |
| `/units/`         | Unidades por empresa (regiones, áreas)      |
| `/businesses/`    | Negocios o sucursales físicas o digitales   |
| `/modules/`       | Módulos de gestión operativa                |
| `/auth/`          | Registro, login, invitaciones               |
| `/panel_admin/`   | Dashboard para cada empresa                 |
| `/panel_root/`    | Panel maestro del sistema SaaS              |

---

## 🚀 FLUJO DE USUARIO NUEVO

1. Usuario accede a `register.php`.
2. Decide:
   - Crear una empresa (gratis)
   - Unirse con código
3. El dashboard detecta contexto y muestra:
   - Sus empresas disponibles
   - Roles que tiene en cada empresa

```php
$_SESSION['user_id']
$_SESSION['company_id']
$_SESSION['unit_id']
$_SESSION['business_id']
$_SESSION['current_role']
📦 ESTRUCTURA DE MÓDULOS
Todos los módulos funcionales viven en:
/app/modules/[modulo]/
├── index.php              # Vista principal
├── controller.php         # Backend del módulo
├── js/[modulo].js         # Scripts JS y AJAX
├── modal_[funcion].php    # Modales reutilizables
├── kpis.php               # Indicadores clave
├── style.css              # Estilos locales
🔁 Se utilizará como plantilla funcional el módulo gastos de indice-produccion.

Incluye:

KPIs dinámicos (Chart.js)

Sumatorias con checkboxes

Filtros rápidos

Carrusel de fotos

Columnas ordenables/ocultables

Botones: editar, duplicar (Agregar), eliminar, ver PDF, etc.

🔐 SISTEMA DE PERMISOS
Cada acción del sistema valida los permisos según el rol actual y empresa activa.

if (!hasPermission('gastos.view')) {
    exit('Access denied');
}
📍 Los permisos están centralizados en includes/permisos.php

🌍 INTERNACIONALIZACIÓN (i18n)
Sistema multilenguaje desde el inicio. Carpeta /lang/:

Español (es.php) como base.

Las variables y estructuras estarán en inglés.

Ejemplo:
$lang['login'] = 'Iniciar sesión';
$lang['logout'] = 'Cerrar sesión';

📁 uploads/
Ruta para almacenar archivos subidos (PDFs, imágenes, evidencias):
/uploads/[modulo]/[YYYY]/[MM]/archivo.ext
🔧 COMPONENTES Y REUTILIZABLES
Carpeta	Uso
/includes/	Controladores globales
/utils/	Funciones comunes (auth, slugify, etc.)
/components/	Formularios, tablas, modales

🧪 ESTADO ACTUAL DEL PROYECTO
🧱 Base visual funcional desde indice-produccion

✅ Primer módulo migrado: gastos

🧪 Estructura modular activa en /app/modules/

🌍 Preparado para i18n

⚙️ Codex y Copilot integrados para desarrollo continuo

📚 Documentación adicional
README_DATABASE.md → estructura SQL completa

AGENTS.md → listado de módulos, rutas y permisos

lang/es.php → diccionario inicial

indice-produccion/ → carpeta base funcional

---

## 👑 Panel Root - Gestión de Planes SaaS

El sistema incluye un módulo `panel_root/` que permite al usuario con rol `root` controlar toda la gestión de monetización, planes y límites SaaS.

### Funcionalidades incluidas:

- CRUD completo de planes SaaS
- Interfaz moderna con Bootstrap 5 y modales
- Validaciones frontend y backend
- Sistema de roles real (basado en `user_companies`)
- Scripts de utilidad: crear root, listar usuarios, validar empresas con planes

📥 Ver detalles en [`panel_root/README.md`](panel_root/README.md)

## 👥 Gestión de Usuarios y Roles

Los `superadmin` pueden:

- Invitar nuevos usuarios por correo electrónico
- Asignar roles por empresa, unidad o negocio
- Controlar el acceso por módulos y acciones

🧠 Un usuario puede tener múltiples roles en distintas empresas o unidades.
