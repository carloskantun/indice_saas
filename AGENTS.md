# AGENTS.md

## 👤 ROLES Y FLUJO

- El sistema debe ser compatible con múltiples roles y múltiples empresas por usuario.
- Cada entidad (empresa, unidad, negocio) puede tener múltiples usuarios con distintos roles.
- Los permisos deben consultarse antes de mostrar acciones o datos.

---

## ✅ MODULOS

### Módulo: gastos
Ruta: /app/modules/gastos/
Responsable: Codex

Archivos clave:
- index.php
- controller.php
- modal_abono.php
- modal_registro.php
- modal_kpis.php

Depende de:
- auth.php
- conexion.php
- includes/permisos.php
- includes/controllers/exportar_kpis_pdf.php

Permisos:
- gastos.ver
- gastos.editar
- gastos.kpis

JS:
- kpis_gastos.js
- gastos_sumatoria_seleccionados.js

---

(Agregar mantenimiento, transfers, servicio_cliente... conforme se vayan migrando)

## 🧠 Módulo: panel_root

Ruta: `/panel_root/`  
Responsable: root  
Descripción: Panel de gestión de planes SaaS y usuarios root.

Permisos requeridos:
- checkRole(['root'])

Archivos clave:
- index.php (Dashboard del sistema SaaS)
- plans.php (Gestión de planes)
- controller.php (Acciones de planes)
- modals/modal_add_plan.php
- modals/modal_edit_plan.php
- js/root_panel.js

Tablas relacionadas:
- `plans`
- `user_companies` (con campo `role`)
- `companies.plan_id`

Módulo: Admin (Gestión de usuarios)
Ruta: /admin/
Responsable: SUPERADMIN

Archivos clave:
- invite_user.php
- manage_roles.php
- controller.php
- js/admin_users.js
- modals/modal_invite_user.php

Permisos aplicables:
- admin.invite_user
- admin.manage_roles
