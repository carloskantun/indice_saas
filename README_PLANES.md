# 📦 README_PLANES.md — Planes SaaS de Indice

Este documento define los diferentes planes disponibles en el sistema Indice SaaS, así como las reglas, límites y funcionalidades que cada uno habilita para empresas registradas.

---

## 🎯 Objetivo

Permitir que el `root` gestione la monetización del sistema a través de paquetes limitados o ilimitados, definidos por cantidad de usuarios, módulos, unidades, negocios y funcionalidades activas.

---

## 🧩 Estructura de un Plan

Cada plan se registra en la tabla `planes` con los siguientes campos clave:

| Campo               | Descripción                                   |
|---------------------|-----------------------------------------------|
| `id`                | Identificador único del plan                  |
| `nombre`            | Nombre comercial del plan                     |
| `descripcion`       | Descripción resumida                          |
| `precio_mensual`    | Costo mensual (opcional)                      |
| `modulos_incluidos` | JSON con IDs de módulos activados            |
| `usuarios_max`      | Número máximo de usuarios permitidos         |
| `empresas_max`      | Empresas que puede crear ese SuperAdmin      |
| `unidades_max`      | Unidades de negocio por empresa              |
| `negocios_max`      | Negocios por unidad                           |
| `storage_max_mb`    | Límite de almacenamiento en MB               |
| `activo`            | true / false                                  |

---

## 📊 Planes Predefinidos

| Plan         | Empresas | Unidades | Negocios | Usuarios | Módulos | Precio    |
|--------------|----------|----------|----------|----------|---------|-----------|
| **Free**     | 1        | 1        | 1        | 3        | 2       | $0        |
| **Starter**  | 2        | 5        | 10       | 10       | 5       | $25 USD   |
| **Pro**      | 5        | 10       | 25       | 25       | 8       | $75 USD   |
| **Enterprise** | Ilimitado | Ilimitado | Ilimitado | Ilimitado | Todos   | A medida |

---

## 🎛️ Gestión desde panel_root/

El `root` puede:

- ✅ Crear nuevos planes personalizados
- ✅ Activar / desactivar planes
- ✅ Ver qué empresa está en qué plan
- ✅ Actualizar límites en tiempo real
- ✅ Forzar upgrades si se supera el límite

---

## ⚙️ Validaciones del sistema

Las validaciones se aplican al crear:

- Empresas (si `empresas_max` está alcanzado)
- Usuarios (si supera `usuarios_max`)
- Módulos (solo los del plan)
- Archivos subidos (si se supera `storage_max_mb`)
- Unidades / Negocios (según el plan)

El sistema debe prevenir la acción o mostrar mensaje como:

```php
"Tu plan actual no permite agregar más unidades. Mejora a Starter o superior."
🔄 Upgrade de Plan
Los upgrades se gestionan desde /panel_admin/planes.php:

Vista de plan actual

Planes disponibles

Botón de upgrade manual o vía Stripe / PayPal (opcional)

Permisos aplican en tiempo real

📂 Estructura sugerida
text
Copiar
Editar
/planes/
├── index.php          # Vista de planes para el root
├── editar.php         # Edición de planes
├── controller.php     # CRUD de planes
└── js/planes.js       # JS de validación de límites
🔐 Seguridad y lógica
La lógica para aplicar límites debe centralizarse en /lib/plan_limiter.php o similar.

Toda acción como crear usuarios, unidades o subir archivos debe consultar el límite antes de permitirlo.

🧪 Siguientes pasos
Crear tabla planes en la base de datos

Asociar campo plan_id a la tabla empresas

Agregar validador de límites en funciones clave

Panel visual para gestión de planes en /panel_root/

🧾 Nota
Este sistema no impide el uso gratuito. El plan Free sirve como onboarding natural y debe permitir experimentar con la plataforma sin pagar.
