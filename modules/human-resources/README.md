# 👥 Módulo de Recursos Humanos - Sistema SaaS Indice

## 🎯 Descripción
Módulo completo de gestión de recursos humanos diseñado para el sistema SaaS Indice. Incluye gestión de empleados, departamentos, posiciones y KPIs de recursos humanos, basado en la arquitectura probada del módulo de gastos.

---

## ✨ Características Principales

### 👤 **Gestión de Empleados**
- ✅ CRUD completo de empleados
- ✅ Información personal y laboral
- ✅ Gestión de salarios y contratos
- ✅ Múltiples tipos de empleo y contrato
- ✅ Sistema de estatus (Activo, Inactivo, Vacaciones, Licencia, Baja)

### 🏢 **Organización Empresarial**
- ✅ Gestión de departamentos
- ✅ Gestión de posiciones/puestos
- ✅ Jerarquía organizacional
- ✅ Asignación de empleados por departamento y posición

### 📊 **KPIs y Reportes**
- ✅ Empleados activos
- ✅ Nuevas contrataciones mensuales
- ✅ Nómina total mensual
- ✅ Distribución por departamentos
- ✅ Distribución por estatus

### 🔐 **Sistema de Permisos**
- ✅ Control granular por roles
- ✅ Permisos específicos para empleados, departamentos y posiciones
- ✅ Integración con el sistema de roles del SaaS

---

## 🗄️ Base de Datos

### Tablas Principales
```sql
employees          -- Información de empleados
departments        -- Departamentos de la empresa
positions          -- Posiciones/puestos de trabajo
```

### Campos Clave de Empleados
- `employee_number` - Número único de empleado
- `first_name`, `last_name` - Nombre completo
- `email`, `phone` - Información de contacto
- `department_id`, `position_id` - Asignación organizacional
- `hire_date` - Fecha de ingreso
- `employment_type` - Tipo de empleo (Tiempo Completo, Medio Tiempo, etc.)
- `contract_type` - Tipo de contrato (Indefinido, Temporal, etc.)
- `salary` - Salario base
- `payment_frequency` - Frecuencia de pago
- `status` - Estatus actual del empleado

---

## 🔐 Permisos y Roles

### Permisos Granulares
```php
// Empleados
'employees.view'    // Ver listado de empleados
'employees.create'  // Crear nuevos empleados
'employees.edit'    // Editar empleados existentes
'employees.delete'  // Dar de baja empleados
'employees.export'  // Exportar datos
'employees.kpis'    // Ver estadísticas

// Departamentos
'departments.view'   // Ver departamentos
'departments.create' // Crear departamentos
'departments.edit'   // Editar departamentos
'departments.delete' // Eliminar departamentos

// Posiciones
'positions.view'     // Ver posiciones
'positions.create'   // Crear posiciones
'positions.edit'     // Editar posiciones
'positions.delete'   // Eliminar posiciones
```

### Matriz de Roles
| Rol | View | Create | Edit | Delete | Export | KPIs |
|-----|------|--------|------|--------|--------|------|
| **root** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **superadmin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **moderator** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **user** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🎨 Interfaz y UX

### Diseño Consistente
- ✅ **Colores primarios**: Azul (#3498db) para HR vs Gastos (#667eea)
- ✅ **Iconografía**: Font Awesome 6.0 con iconos específicos de RH
- ✅ **Layout responsivo**: Bootstrap 5.3 con adaptación móvil
- ✅ **Componentes**: Basados en la plantilla de gastos (botones, tablas, modales)

### Funcionalidades UI
- ✅ **Filtros avanzados**: Por departamento, posición, estatus, tipo empleo
- ✅ **Columnas ordenables**: Click en headers para ordenar
- ✅ **Toggle de columnas**: Mostrar/ocultar columnas según necesidad
- ✅ **Modales dinámicos**: Para crear/editar empleados
- ✅ **Alertas informativas**: SweetAlert2 para confirmaciones
- ✅ **Tooltips**: Información contextual en botones

---

## 📁 Estructura de Archivos

```
modules/human-resources/
├── index.php              # Vista principal
├── controller.php         # Controlador API
├── config.php             # Configuración del módulo
├── modals.php             # Modales de la interfaz
├── css/
│   └── human-resources.css # Estilos específicos
└── js/
    └── human-resources.js  # Funcionalidad JavaScript
```

---

## ⚙️ Instalación y Configuración

### 1. Scripts SQL (Pendientes)
```sql
-- Crear tablas necesarias
CREATE TABLE departments (...)
CREATE TABLE positions (...)
CREATE TABLE employees (...)

-- Insertar permisos
INSERT INTO permissions (...)

-- Configurar módulo
INSERT INTO modules (...)
```

### 2. Configuración de Permisos
Los permisos se manejan automáticamente a través del sistema de roles existente del SaaS.

### 3. Contexto Empresarial
El módulo respeta la estructura multi-tenant:
- `company_id` - Empresa actual
- `business_id` - Negocio actual  
- `unit_id` - Unidad actual

---

## 🔧 Stack Tecnológico

### Backend
- **PHP 8.0+** con PDO para base de datos
- **MySQL 8.0** para almacenamiento
- **Sistema de permisos** integrado del SaaS

### Frontend
- **Bootstrap 5.3** para componentes UI
- **jQuery 3.6** para interactividad
- **Select2** para autocomplete
- **SweetAlert2** para alertas
- **Font Awesome 6.0** para iconografía

### Librerías Específicas
- **Select2** - Selects avanzados para departamentos/posiciones
- **Bootstrap Modals** - Interfaces de creación/edición
- **Responsive Tables** - Tablas adaptables móvil

---

## 🚀 Funcionalidades Implementadas

### ✅ Completadas
1. **Vista principal** con listado de empleados
2. **Sistema de filtros** avanzados
3. **Modal de empleados** para crear/editar
4. **Controlador API** con operaciones CRUD
5. **Sistema de permisos** granular
6. **KPIs básicos** en dashboard
7. **Interfaz responsiva** y moderna
8. **Integración Select2** para dropdowns

### 🔄 En Desarrollo
1. **Gestión completa de departamentos** (modal funcional)
2. **Gestión completa de posiciones** (modal funcional)
3. **Exportación CSV/PDF** de empleados
4. **Reportes avanzados** con gráficas
5. **Historial de cambios** de empleados
6. **Gestión de documentos** de empleados

### 📋 Próximas Funcionalidades
1. **Módulo de nómina** integrado
2. **Gestión de vacaciones** y permisos
3. **Evaluaciones de desempeño**
4. **Organigrama visual** de la empresa
5. **Notificaciones automáticas** (cumpleaños, vencimientos)
6. **Dashboard ejecutivo** con métricas avanzadas

---

## 🔍 Comparación con Módulo de Gastos

| Aspecto | Gastos | Recursos Humanos |
|---------|--------|------------------|
| **Color Principal** | #667eea (Púrpura) | #3498db (Azul) |
| **Icono Principal** | fas fa-receipt | fas fa-users |
| **Entidad Principal** | Expenses | Employees |
| **Entidades Relacionadas** | Providers | Departments, Positions |
| **Operaciones CRUD** | ✅ Completo | ✅ Completo |
| **Sistema Permisos** | ✅ Granular | ✅ Granular |
| **KPIs** | ✅ Financieros | ✅ RH |
| **Exportaciones** | ✅ PDF/CSV | 🔄 En desarrollo |
| **Filtros** | ✅ Avanzados | ✅ Avanzados |

---

## 🎯 Objetivos del Módulo

### Inmediatos
- ✅ Replicar la estabilidad y funcionalidad del módulo de gastos
- ✅ Mantener consistencia visual y de UX
- ✅ Implementar permisos granulares efectivos

### Mediano Plazo
- 📋 Completar gestión de departamentos y posiciones
- 📊 Implementar reportes avanzados con gráficas
- 📄 Sistema completo de exportaciones

### Largo Plazo
- 💰 Integración con módulo de nómina
- 📅 Sistema de gestión de vacaciones
- 📈 Analytics avanzados de recursos humanos

---

## 🔧 Mantenimiento y Desarrollo

### Patrón de Desarrollo
El módulo sigue el **patrón establecido por el módulo de gastos**:

1. **Controlador centralizado** (`controller.php`) para todas las operaciones API
2. **Vista principal** (`index.php`) con tabla interactiva
3. **Modales dinámicos** para operaciones CRUD
4. **JavaScript modular** con funciones específicas
5. **CSS específico** manteniendo consistencia visual

### Mejores Prácticas
- ✅ **Validación de permisos** en cada operación
- ✅ **Sanitización de datos** en inputs y outputs
- ✅ **Manejo de errores** consistente
- ✅ **Logging de operaciones** para auditoría
- ✅ **Responsive design** móvil-first

---

## 👨‍💻 Desarrollo y Soporte

**📅 Estado Actual**: Módulo base implementado y funcional  
**🔄 Próxima Iteración**: Gestión completa de departamentos y posiciones  
**🎯 Objetivo**: Módulo de RH completamente funcional siguiendo estándares del sistema

**Desarrollado por**: GitHub Copilot + Equipo Indice SaaS  
**Fecha**: 7 de agosto de 2025  
**Versión**: 1.0.0 (Base funcional)
