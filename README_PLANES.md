📦 README_PLANES.md — Gestión de Planes SaaS en Índice
🎯 Objetivo
Establecer las reglas, estructura y funciones que permiten al usuario root controlar la monetización y escalabilidad de la plataforma, a través de planes SaaS con límites definidos por usuarios, módulos, unidades y almacenamiento.

✅ ¿Quién puede gestionar los planes?
El usuario con rol root (desde /panel_root/) es el único que puede:

Crear, editar o eliminar planes

Asignar o cambiar planes a empresas

Ver estadísticas y límites superados

Forzar upgrades o suspender planes


🧩 Estructura de un plan
Los planes se almacenan en la tabla plans con los siguientes campos clave:

| Campo              | Descripción                                          |
| ------------------ | ---------------------------------------------------- |
| `id`               | ID único del plan                                    |
| `name`             | Nombre del plan (ej. Free, Starter, Pro)             |
| `description`      | Descripción del plan                                 |
| `price_monthly`    | Precio mensual                                       |
| `modules_included` | JSON con IDs de módulos habilitados                  |
| `users_max`        | Máximo de usuarios permitidos                        |
| `companies_max`    | (opcional) Número de empresas si aplica multitenancy |
| `units_max`        | Máximo de unidades por empresa                       |
| `businesses_max`   | Máximo de negocios por unidad                        |
| `storage_max_mb`   | Límite de almacenamiento en MB                       |
| `is_active`        | true / false (plan habilitado)                       |


📊 Planes predefinidos sugeridos
Plan	Empresas	Unidades	Negocios	Usuarios	Módulos	Precio
Free	1	1	1	3	2	$0
Starter	2	5	10	10	5	$25 USD
Pro	5	10	25	25	8	$75 USD
Enterprise	Ilimitado	Ilimitado	Ilimitado	Ilimitado	Todos	A medida

🛠️ Panel Root: estructura sugerida
Ubicación: /panel_root/

/panel_root/
├── index.php           # Dashboard general
├── plans.php           # Vista y control de planes
├── companies.php       # Empresas registradas
├── modules.php         # Módulos disponibles del sistema
├── controller.php      # Acciones centralizadas (AJAX)
└── js/
    └── root_panel.js   # Interacciones JS del panel

🔄 Comportamiento esperado
El sistema debe validar los límites del plan activo antes de permitir:

Crear nuevas unidades

Agregar más usuarios

Subir archivos (verificar storage_max_mb)

Activar módulos fuera del plan

🔁 Si el límite se alcanza:

// Mensaje ejemplo
"Tu plan actual no permite agregar más usuarios. Mejora tu plan para continuar."
🔐 Validación en backend
Se recomienda crear una clase o helper en:
/lib/plan_limiter.php

Con funciones como:
function checkLimit($type, $currentValue, $maxAllowed);
function getCurrentUsage($company_id);
function planAllowsModule($company_id, $module_id);

🧪 Flujo típico de upgrade
Desde /panel_admin/planes.php (visible al superadmin):

Se muestra el plan actual

Se comparan límites y características

Se habilita un botón "Mejorar Plan"

Opcional: integración con Stripe / PayPal / Mercado Pago / 

🧾 Notas adicionales
El plan Free es clave como onboarding gratuito

El sistema no debe bloquear el uso si expira un plan, sino mostrar alertas y limitar nuevas acciones

Los upgrades deben aplicarse en tiempo real

Toda empresa (tabla companies) debe tener un campo plan_id

