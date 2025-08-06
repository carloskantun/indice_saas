# Módulo de Gastos - Sistema SaaS Indice

## Descripción
Módulo completo de gestión de gastos migrado desde `indice-produccion/gastos.php` al sistema SaaS con arquitectura multi-inquilino (Company → Unit → Business → Modules).

## Características Principales

### ✅ Funcionalidades Implementadas
- **Gestión completa de gastos** con CRUD completo
- **Sistema de órdenes de compra** con folio automático
- **Sistema de proveedores** integrado
- **Registro de pagos** parciales y totales
- **Filtros avanzados** por fecha, proveedor, estatus, etc.
- **Columnas configurables** - mostrar/ocultar y reordenar con drag & drop
- **Edición en línea** para admin/superadmin en campos específicos
- **Selección múltiple** y eliminación masiva
- **Totales dinámicos** en footer de tabla
- **KPIs y estadísticas** con gráficos interactivos
- **Exportación** a CSV/PDF (pendiente implementar)
- **Permisos granulares** según roles de usuario
- **Responsive design** optimizado para móvil
- **Integración completa** con el sistema SaaS

### 🎯 Funcionalidades Específicas del Sistema Original

#### 1. **Columnas Reordenables y Configurables**
- Dropdown "Columnas" permite mostrar/ocultar columnas
- Drag & drop en headers para reordenar columnas
- Configuración guardada en localStorage del navegador
- 14 columnas disponibles: Folio, Proveedor, Monto, Fecha, Unidad, Tipo, Tipo Compra, Método Pago, Cuenta, Concepto, Estatus, Pagado, Pendiente, Comprobante

#### 2. **Edición en Línea (Admin/Superadmin)**
- Campos editables directamente en la tabla:
  - **Tipo Compra**: Select con opciones (Venta, Administrativa, Operativo, etc.)
  - **Método de Pago**: Select (Transferencia, Efectivo, Cheque, Tarjeta)
  - **Cuenta Bancaria**: Input de texto
  - **Concepto**: Input de texto
  - **Estatus**: Select (Pendiente, Pago parcial, Pagado, Cancelado)
- Auto-guardado al cambiar valor
- Solo disponible para roles admin y superadmin

#### 3. **Órdenes de Compra**
- Botón "Nueva Orden de Compra" en interfaz principal
- Modal específico con campos adicionales para órdenes
- Folio automático formato: ORD000001, ORD000002, etc.
- Soporte para órdenes recurrentes con configuración de frecuencia
- Las órdenes se muestran como "Orden (Tipo)" en la columna Tipo
- Conversión automática a "Orden (Tipo) → Gasto" cuando se marca como pagado

#### 4. **Selección Múltiple y Operaciones Masivas**
- Checkbox en primera columna (solo admin/superadmin)
- "Seleccionar todos" en header
- Resumen flotante con totales de seleccionados
- Botón "Eliminar Seleccionados" aparece al seleccionar items
- Exportación masiva a PDF/CSV de elementos seleccionados

#### 5. **Totales Dinámicos**
- Footer de tabla con totales automáticos
- Columnas: Monto total, Pagado total, Pendiente total
- Actualización automática al filtrar o cambiar datos
- Formato de moneda mexicana ($1,234.56)

#### 6. **Filtros Rápidos**
- Botones predefinidos: Pendientes, Vencidos, Pagados, Órdenes pendientes
- Un clic aplica filtro específico
- Botón "Limpiar" restaura vista completa

#### 7. **Responsive Design Mejorado**
- Optimización específica para móviles
- Botones stack verticalmente en pantallas pequeñas
- Tabla con scroll horizontal automático
- Columnas menos importantes se ocultan en pantallas muy pequeñas
- Filtros adaptados a pantallas táctiles

### 🏗️ Arquitectura
```
modules/expenses/
├── index.php          # Vista principal
├── controller.php     # Controlador AJAX/API
├── modals.php         # Modales de interfaz
├── css/
│   └── expenses.css   # Estilos personalizados
└── js/
    └── expenses.js    # JavaScript/jQuery
```

### 🎯 Adaptaciones del Sistema Original
1. **Base de datos**: Migradas 5 tablas principales con triggers
2. **Permisos**: Integración con sistema de roles SaaS
3. **Multi-tenancy**: Soporte completo para company/business/unit
4. **UI/UX**: Rediseño completo con Bootstrap 5 y componentes modernos
5. **API REST**: Controlador modular con respuestas JSON

## Base de Datos

### Tablas Creadas
- `providers` - Gestión de proveedores por empresa
- `expenses` - Gastos principales con folio auto-generado
- `expense_payments` - Historial de pagos por gasto
- `credit_notes` - Notas de crédito
- `credit_note_payments` - Pagos de notas de crédito

### Triggers Implementados
- `generate_expense_folio` - Auto-genera folios únicos
- `generate_credit_note_folio` - Auto-genera folios de notas de crédito

## Permisos y Roles

### Permisos Granulares
```php
// Gastos
'expenses.view'    // Ver listado de gastos
'expenses.create'  // Crear nuevos gastos
'expenses.edit'    // Editar gastos existentes
'expenses.delete'  // Eliminar gastos
'expenses.pay'     // Registrar pagos
'expenses.export'  // Exportar datos
'expenses.kpis'    // Ver estadísticas

// Proveedores
'providers.view'   // Ver proveedores
'providers.create' // Crear proveedores
'providers.edit'   // Editar proveedores
'providers.delete' // Eliminar proveedores
```

### Matriz de Roles
| Rol | View | Create | Edit | Delete | Pay | Export | KPIs |
|-----|------|--------|------|--------|-----|--------|------|
| **root** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **superadmin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **moderator** | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **user** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

## Uso del Módulo

### 1. Acceso
```
/modules/expenses/
```

### 2. Crear Gasto
1. Clic en "Nuevo Gasto"
2. Llenar formulario (proveedor opcional, monto requerido)
3. Seleccionar tipo, método de pago, etc.
4. Guardar

### 3. Gestionar Pagos
1. Localizar gasto en tabla
2. Clic en "Pagar" (botón verde)
3. Ingresar monto (puede ser parcial)
4. Agregar comentario opcional
5. Registrar pago

### 4. Ver Estadísticas
1. Clic en "KPIs" 
2. Ajustar filtros de fecha/unidad
3. Ver gráficos y métricas

### 5. Gestionar Proveedores
1. Clic en "Nuevo Proveedor"
2. Completar datos (nombre requerido)
3. Guardar y usar en gastos

## API/Controller

### Endpoints Disponibles
```php
// Gastos
POST controller.php?action=create_expense
POST controller.php?action=edit_expense
POST controller.php?action=delete_expense
POST controller.php?action=delete_multiple
POST controller.php?action=add_payment
POST controller.php?action=update_field
GET  controller.php?action=get_expense&expense_id=X
GET  controller.php?action=get_kpis

// Órdenes de Compra
POST controller.php?action=create_order

// Proveedores
POST controller.php?action=create_provider
POST controller.php?action=edit_provider
POST controller.php?action=delete_provider
GET  controller.php?action=get_providers
```

### Respuestas JSON
```json
// Éxito
{
  "success": true,
  "message": "Operación exitosa",
  "data": {}
}

// Error
{
  "error": "Mensaje de error",
  "code": 400
}
```

## Tecnologías Utilizadas

### Frontend
- **Bootstrap 5.3** - Framework CSS
- **jQuery 3.6** - Manipulación DOM
- **Select2 4.0** - Selectores avanzados
- **DataTables 1.11** - Tablas interactivas
- **Chart.js** - Gráficos estadísticos
- **FontAwesome 6.0** - Iconografía

### Backend
- **PHP 8+** - Servidor
- **MySQL 8+** - Base de datos
- **PDO** - Conexión segura DB

## Instalación y Configuración

### 1. Base de Datos
```sql
-- Ejecutar migration_gastos_safe.sql en phpMyAdmin
-- Las tablas y permisos se crean automáticamente
```

### 2. Archivos
```bash
# Copiar módulo a directorio
cp -r modules/expenses /ruta/saas/modules/

# Verificar permisos
chmod 755 modules/expenses/
chmod 644 modules/expenses/*.php
```

### 3. Configuración
- El módulo hereda configuración de `config.php`
- No requiere configuración adicional
- Se adapta automáticamente al contexto SaaS

## Próximas Funcionalidades

### 📋 Pendientes de Implementación
- [ ] **Exportación CSV/PDF** completa
- [ ] **Carga masiva** de gastos via Excel
- [ ] **Categorización avanzada** de gastos
- [ ] **Aprobaciones** con flujo de trabajo
- [ ] **Notificaciones** automáticas
- [ ] **Integración contable** con terceros
- [ ] **Dashboard** ejecutivo
- [ ] **API REST** completa para integraciones

### 🔄 Mejoras Técnicas
- [ ] **Caché Redis** para consultas frecuentes
- [ ] **Indexación** optimizada de BD
- [ ] **Validaciones frontend** mejoradas
- [ ] **Tests unitarios** automatizados
- [ ] **Logs** de auditoría detallados

## Mantenimiento

### Logs y Debugging
- Errores PHP: `/logs/expenses_errors.log`
- Queries lentas: Revisar `slow_query_log`
- Debug JS: Console del navegador

### Backup
```sql
-- Backup específico del módulo
mysqldump -u user -p database_name providers expenses expense_payments credit_notes credit_note_payments > backup_expenses.sql
```

### Monitoreo
- **Performance**: Consultas > 2 segundos
- **Uso**: Gastos por empresa/mes
- **Errores**: 4xx/5xx en controller.php

---

## Soporte y Desarrollo

**Desarrollado por**: GitHub Copilot  
**Fecha**: Enero 2025  
**Versión**: 1.0.0  
**Compatible con**: Sistema SaaS Indice v2.0+

Para soporte técnico o nuevas funcionalidades, consultar la documentación del sistema SaaS principal.
