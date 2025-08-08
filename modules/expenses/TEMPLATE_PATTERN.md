# 📋 MÓDULO EXPENSES - PLANTILLA PARA OTROS MÓDULOS

## 🎯 Concepto "Modal-Formulario-General"

El módulo de **Expenses** implementa un patrón reutilizable llamado **"modal-formulario-general"** que puede adaptarse a diferentes módulos según su contexto.

### 🔄 Adaptabilidad por Módulo

| Módulo | Acción Principal | Campos del Modal | Propósito |
|--------|------------------|------------------|-----------|
| **Expenses** | 💰 Registrar Pago/Abono | Monto + Fecha + Comprobantes | Registro de pagos parciales/totales |
| **Mantenimiento** | 🔧 Completar Servicio | Fotos + Descripción + Estado | Evidencia de trabajo realizado |
| **Ventas** | ✅ Confirmar Entrega | Firma + Fotos + Observaciones | Confirmación de entrega |
| **Inventario** | 📦 Registrar Movimiento | Cantidad + Ubicación + Motivo | Control de stock |

## 🏗️ Estructura del Patrón

### 1. **Botones de Acción en Tabla**
```javascript
// Botones estándar en todas las tablas
$('.btn-view')    // 👁️ Ver detalles
$('.btn-edit')    // ✏️ Editar
$('.btn-delete')  // 🗑️ Eliminar
$('.btn-pay')     // 💰 Acción contextual (modal-formulario-general)
```

### 2. **Función de Apertura de Modal**
```javascript
function openPaymentModal(itemId) {
    // 1. Limpiar formulario
    // 2. Cargar datos del item
    // 3. Configurar campos específicos
    // 4. Abrir modal
}
```

### 3. **Función de Envío**
```javascript
function handlePaymentSubmit(e) {
    // 1. Validaciones específicas del módulo
    // 2. Envío AJAX al controller
    // 3. Manejo de respuesta
    // 4. Actualización de UI
}
```

## 🎨 Implementación en Expenses

### Archivos Clave:
- **`js/expenses-debug.js`** → Lógica JavaScript completa
- **`modals.php`** → Definición de modales HTML
- **`controller.php`** → Endpoints backend
- **`index.php`** → Interfaz principal con botones

### Eventos Vinculados:
```javascript
// En bindBasicEvents()
$(document).off('click', '.btn-pay').on('click', '.btn-pay', function() {
    const expenseId = $(this).data('id');
    openPaymentModal(expenseId);
});

$(document).off('submit', '#paymentForm').on('submit', '#paymentForm', function(e) {
    e.preventDefault();
    handlePaymentSubmit.call(this, e);
});
```

## 🔧 Adaptación para Otros Módulos

### Para Módulo de Mantenimiento:

1. **Cambiar el botón:**
```html
<button class="btn btn-outline-success btn-sm btn-complete" data-id="<?php echo $maintenance['id']; ?>" title="Completar Servicio">
    <i class="fas fa-check"></i>
</button>
```

2. **Adaptar el modal:**
```html
<div class="modal fade" id="completeModal">
    <form id="completeForm">
        <!-- Campos específicos: fotos, descripción, estado -->
        <input type="file" name="service_photos[]" multiple accept="image/*">
        <textarea name="work_description" placeholder="Describe el trabajo realizado"></textarea>
        <select name="completion_status">
            <option value="completed">Completado</option>
            <option value="partial">Parcial</option>
        </select>
    </form>
</div>
```

3. **Adaptar JavaScript:**
```javascript
function openCompleteModal(maintenanceId) {
    // Cargar datos del mantenimiento
    // Configurar campos específicos
    $('#completeModal').modal('show');
}

function handleCompleteSubmit(e) {
    // Validar fotos y descripción
    // Enviar con FormData para archivos
    // Endpoint: 'complete_service'
}
```

4. **Endpoint en Controller:**
```php
case 'complete_service':
    completeService();
    break;
```

## ✅ **FUNCIONALIODAD ACTUAL DE EXPENSES**

### 🎯 **Estado de Implementación:**

#### ✅ **Formularios Principales (FUNCIONANDO)**
- **Crear Gastos** → Modal + Validación + AJAX + Subida de archivos ✅
- **Crear Órdenes** → Modal + Validación + AJAX + Archivos ✅  
- **Crear Proveedores** → Modal + Validación + AJAX ✅

#### ✅ **Botones de Acción (FUNCIONANDO)**
- **Ver Detalles** (`btn-view`) → Modal con información completa ✅
- **Registrar Pago** (`btn-pay`) → Modal-formulario-general + archivos ✅
- **Editar** (`btn-edit`) → Modal de edición con carga de datos ✅
- **Eliminar** (`btn-delete`) → Confirmación + eliminación ✅
- **PDF Individual** (`btn-pdf`) → Generación de PDF por gasto ✅

#### ✅ **Modal-Formulario-General (FUNCIONANDO)**
- **Apertura dinámica** → Carga datos del gasto ✅
- **Validaciones** → Monto máximo, campos requeridos ✅
- **Envío AJAX** → Registro de pagos/abonos ✅
- **Subida de archivos** → Drag & drop + preview ✅
- **Actualización UI** → Recarga automática ✅

#### ✅ **Sistema de Archivos (NUEVO - FUNCIONANDO)**
- **Subida múltiple** → Imágenes y PDFs ✅
- **Drag & drop** → Arrastrar archivos al modal ✅
- **Preview** → Vista previa de archivos seleccionados ✅
- **Validación** → Tipos de archivo permitidos ✅

#### ✅ **Exportaciones (FUNCIONANDO)**
- **CSV General** → Exportación con filtros ✅
- **PDF Individual** → Por gasto específico ✅
- **PDF General** → Placeholder preparado ⏳

#### 🔄 **Pendientes para Completar la Plantilla:**
- **KPIs** → Dashboard con métricas ⏳

## 🚀 **Próximos Pasos:**

1. **Verificar funcionamiento completo** de botones de acción
2. **Implementar KPIs** y dashboard
3. **Sistema de exportaciones** (PDF/Excel)
4. **Documentación final** para replicar en otros módulos

### 📝 **Notas de Implementación:**
- El patrón es **100% reutilizable**
- Cambiar solo: nombres de campos, validaciones específicas, endpoints
- Mantener: estructura de archivos, patrón de eventos, flujo AJAX
- Ideal para: cualquier módulo que necesite acciones contextuales en registros
