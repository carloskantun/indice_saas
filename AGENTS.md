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
