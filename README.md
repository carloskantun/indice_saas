## 📘 README.md — Indice SaaS Modular Platform

### 🎯 Objetivo
Indice SaaS es una plataforma modular para empresas, diseñada para gestionar múltiples negocios y unidades operativas bajo un solo ecosistema. Este sistema permite escalar desde un solo usuario hasta una red de empresas y sucursales con roles jerárquicos y módulos dinámicos.

---

## ✅ ESTRUCTURA DE ROLES Y JERARQUÍA

| Rol         | Descripción                                                       |
|--------------|-------------------------------------------------------------------|
| `root`       | Acceso total al sistema. Administra cuentas y empresas SaaS.     |
| `support`    | Soporte técnico con acceso parcial sin modificar cuentas SaaS.   |
| `superadmin` | Crea y administra empresas y unidades. Define permisos.          |
| `admin`      | Administra unidades o empresas específicas.                      |
| `moderator`  | Gerente de unidades o negocios. Supervisa operaciones.           |
| `user`       | Usuario operativo. Accede a formularios, KPIs y tareas asignadas.|

Cada usuario puede tener distintos roles en distintas empresas o unidades.

---

## 🧱 ESCALAMIENTO: ENTIDADES DEL SISTEMA

```text
Usuario → Empresas → Unidades → Negocios (opcional)
```

### 📂 Carpetas base

| Carpeta         | Descripción                                  |
|-----------------|----------------------------------------------|
| `/companies/`    | Gestión de empresas del sistema SaaS         |
| `/units/`        | Unidades de negocio por empresa              |
| `/businesses/`   | Negocios (sucursales físicas o virtuales)    |
| `/modules/`      | Módulos funcionales del sistema              |
| `/auth/`         | Login, registro, verificación                |


---

## 🚀 FLUJO DE REGISTRO DE USUARIO

1. Usuario se registra (sin pago obligatorio).
2. Opción: "Crear empresa ahora" o "Unirse más tarde".
3. Accede a dashboard según su contexto:
   - Si no pertenece a nada: se invita a crear o unirse.
   - Si fue invitado: ve listado de empresas disponibles.

```php
// Variables de sesión
$_SESSION['user_id']
$_SESSION['company_id']
$_SESSION['unit_id']
$_SESSION['business_id']
$_SESSION['current_role']
```

---

## 📦 ESTRUCTURA DE MÓDULOS

Cada módulo debe vivir en `/app/modules/[modulo]/` con esta estructura:

```text
/app/modules/[modulo]/
├── index.php              # Vista principal
├── controller.php         # Backend de acciones
├── js/[modulo].js         # Funciones JS y AJAX
├── modal_[funcion].php    # Modales reutilizables
├── kpis.php               # KPIs del módulo
```

---

## 🔐 SISTEMA DE PERMISOS

- Los permisos están definidos por módulo y acción:

```php
if (!hasPermission('gastos.view')) {
    exit('Access denied');
}
```

---

## 🌍 INTERNACIONALIZACIÓN (i18n)

- Carpeta `/lang/`
- Idioma principal: español (`es/`)
- Variables comunes: botones, etiquetas, acciones
- Soporte futuro para múltiples idiomas (`en/`, `pt/`, etc.)

Ejemplo:
```php
$lang['login'] = 'Iniciar sesión';
$lang['logout'] = 'Cerrar sesión';
```

---

## 🔧 COMPONENTES Y REUTILIZABLES

| Carpeta           | Uso                           |
|-------------------|-------------------------------|
| `/includes/`       | Archivos globales (`auth.php`) |
| `/components/`     | Formularios, validadores       |
| `/utils/`          | Funciones auxiliares           |

---

## 📁 uploads/

Estructura para archivos subidos:

```
uploads/[modulo]/[año]/[mes]/archivo.ext
```

---

## 🧪 Estado actual
- Base funcional de módulos integrada
- Plantilla visual tomada del módulo `gastos`
- Estructura escalable activa para Codex y Copilot
- Preparado para bifurcación SaaS con multitenencia

