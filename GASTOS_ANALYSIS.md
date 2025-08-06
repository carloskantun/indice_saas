# 📋 ANÁLISIS COMPLETO - MÓDULO GASTOS

## 🔍 ARCHIVOS PRINCIPALES IDENTIFICADOS

### 📄 **Archivo Principal**
- `gastos.php` - Vista principal del módulo

### 🏗️ **Archivos de Estructura**
- `create_gastos_table.php` - Creación de tabla gastos
- `create_tables.php` - Creación de tablas relacionadas

### 🔧 **Scripts de Funcionalidad**
- `guardar_abono_gasto.php` - Guardar pagos/abonos
- `eliminar_gasto.php` - Eliminar gasto individual
- `eliminar_gastos_multiples.php` - Eliminar múltiples gastos
- `generar_pdf_gasto.php` - PDF individual de gasto

### 📊 **Exports y Reportes**
- `exportar_gastos.php` - Export CSV general
- `exportar_gastos_pdf.php` - Export PDF general
- `includes/controllers/exportar_gastos_seleccionados.php` - CSV seleccionados
- `includes/controllers/exportar_gastos_seleccionados_pdf.php` - PDF seleccionados
- `includes/controllers/exportar_kpis_csv.php` - Export KPIs CSV
- `includes/controllers/exportar_kpis_pdf.php` - Export KPIs PDF

### 📈 **Sistema de KPIs**
- `includes/controllers/analisis_kpis_gastos.php` - API de KPIs
- `includes/assets/js/kpis_gastos.js` - JavaScript para KPIs
- `includes/assets/js/gastos_sumatoria_seleccionados.js` - Sumatoria

### 🎨 **Modales**
- `modal_orden.php` - Modal nueva orden de compra
- `includes/modals/modal_kpis_gastos.php` - Modal KPIs
- `includes/modals/modal_ver_abonos_nota.php` - Ver abonos nota crédito

### 🔗 **Archivos de Integración**
- `auth.php` - Autenticación (NO USAR - usar config.php del SaaS)
- `conexion.php` - Conexión BD (NO USAR - usar getDB() del SaaS)

### 📋 **Funcionalidades Core Identificadas**

1. **CRUD de Gastos**
   - ✅ Crear gastos individuales y desde órdenes
   - ✅ Editar gastos existentes
   - ✅ Eliminar gastos (individual y múltiple)
   - ✅ Sistema de folios automáticos

2. **Sistema de Pagos/Abonos**
   - ✅ Pagos parciales con comprobantes
   - ✅ Múltiples archivos por pago
   - ✅ Cálculo automático de saldos
   - ✅ Actualización automática de estatus

3. **Filtros Avanzados**
   - ✅ Por proveedor, unidad, fechas, estatus, origen
   - ✅ Ordenamiento por cualquier columna
   - ✅ Filtros predeterminados (vencidos, pagados, etc.)

4. **Sistema de Columnas**
   - ✅ Mostrar/ocultar columnas dinámicamente
   - ✅ Reordenamiento de columnas
   - ✅ Selección múltiple con checkbox

5. **KPIs y Análisis**
   - ✅ KPIs por tipo, unidad, estatus, proveedor
   - ✅ Gráficos dinámicos (Chart.js)
   - ✅ Análisis de abonos vs saldos
   - ✅ Filtros de período personalizable

6. **Exportaciones**
   - ✅ CSV/PDF general filtrado
   - ✅ CSV/PDF de registros seleccionados
   - ✅ PDF individual por gasto
   - ✅ Export de KPIs con gráficos

7. **Integración con Órdenes**
   - ✅ Crear gastos desde órdenes de compra
   - ✅ Seguimiento de origen (Directo/Orden)
   - ✅ Vinculación con folios de orden

8. **Sistema de Proveedores**
   - ✅ Gestión completa de proveedores
   - ✅ Información bancaria y fiscal
   - ✅ Vinculación con gastos

9. **Notas de Crédito**
   - ✅ Sistema de notas de crédito
   - ✅ Abonos a notas de crédito
   - ✅ Vinculación con gastos

## 🎯 **DISEÑO OBJETIVO PARA SAAS**

### 📱 **Estructura de Vista Principal**
```
┌─ Navbar SaaS (breadcrumb: Empresa > Unidad > Negocio > Gastos)
├─ Botones principales: [Nuevo Gasto] [Nueva Orden] [Ver KPIs]
├─ Filtros: Proveedor, Fechas, Estatus, Origen
├─ Acciones: [Export CSV] [Export PDF] [Filtros rápidos]
├─ Control columnas: [Mostrar/Ocultar] con checkboxes
├─ Tabla principal con selección múltiple
├─ Ventana flotante: Sumatoria de seleccionados
└─ Modales: KPIs, Pagos, Órdenes
```

### 🔧 **Adaptaciones SAAS Necesarias**

1. **Autenticación y Permisos**
   - ❌ Reemplazar `include 'auth.php'` 
   - ✅ Usar `checkAuth()` y `hasPermission()` del SaaS
   - ✅ Verificar permisos granulares por acción

2. **Contexto Empresarial**
   - ✅ Filtrar por `company_id`, `unit_id`, `business_id`
   - ✅ Mostrar breadcrumb de contexto
   - ✅ Restringir datos por empresa activa

3. **Base de Datos**
   - ❌ Reemplazar `include 'conexion.php'`
   - ✅ Usar `getDB()` del sistema SaaS
   - ✅ Adaptar todas las consultas a nuevas tablas

4. **Navegación**
   - ✅ Integrar con navbar del SaaS
   - ✅ Breadcrumbs dinámicos
   - ✅ Botón de retorno al hub de módulos

5. **Archivos y Uploads**
   - ✅ Estructura de carpetas por empresa/negocio
   - ✅ Control de tamaño y tipos de archivo
   - ✅ Seguridad en acceso a archivos

## 🚀 **PLAN DE IMPLEMENTACIÓN**

### Fase 1: Estructura Base
1. ✅ Ejecutar SQL en PhpMyAdmin
2. ✅ Crear carpeta `/modules/expenses/`
3. ✅ Implementar `index.php` principal
4. ✅ Crear `controller.php` para API

### Fase 2: Funcionalidad Core
1. ✅ Sistema CRUD básico
2. ✅ Integración con permisos SaaS
3. ✅ Filtros y búsquedas
4. ✅ Sistema de pagos/abonos

### Fase 3: Características Avanzadas
1. ✅ KPIs y gráficos
2. ✅ Exportaciones múltiples
3. ✅ Sistema de columnas dinámicas
4. ✅ Integración con órdenes

### Fase 4: Optimización
1. ✅ Performance y caching
2. ✅ Validaciones cliente/servidor
3. ✅ Responsive design
4. ✅ Testing completo

¿Procedemos con la implementación de la Fase 1?
