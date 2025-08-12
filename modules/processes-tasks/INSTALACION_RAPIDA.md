# 🚀 Instalación Rápida - Módulo Procesos y Tareas

## Para ejecutar directamente en phpMyAdmin

### 📋 Pasos:

1. **Acceder a phpMyAdmin** en `app.indiceapp.com/phpmyadmin`

2. **Seleccionar su base de datos** (probablemente `corazon_indicesaas`)

3. **Ir a la pestaña SQL**

4. **Copiar y pegar UNO de estos scripts:**

## 🔧 Opción 1: Script Optimizado (Recomendado)
```sql
-- Copiar todo el contenido de: install_optimized.sql
-- Este script detecta automáticamente qué tablas existen y se adapta
```

## 🔧 Opción 2: Script Original  
```sql  
-- Copiar todo el contenido de: install.sql
-- Script completo asumiendo estructura estándar
```

## 📊 Opción 3: Solo Datos de Ejemplo
```sql
-- Después de cualquiera de los anteriores, ejecutar:
-- Copiar todo el contenido de: sample_data.sql
```

## ✅ Verificación Post-Instalación

Ejecutar esta consulta para verificar:

```sql
-- Verificar tablas creadas
SELECT 
    TABLE_NAME as 'Tabla Creada',
    TABLE_ROWS as 'Registros'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE '%process%' 
OR TABLE_NAME LIKE '%task%'
ORDER BY TABLE_NAME;

-- Verificar si hay datos de ejemplo
SELECT 'Procesos' as Tipo, COUNT(*) as Cantidad FROM processes
UNION ALL
SELECT 'Tareas', COUNT(*) FROM tasks
UNION ALL  
SELECT 'Plantillas', COUNT(*) FROM workflow_templates;
```

## 🎯 Acceso al Módulo

Una vez instalado:
- **URL**: `app.indiceapp.com/modules/processes-tasks/`
- **Permisos**: Configurar en panel de administración

## 🔍 Troubleshooting

Si hay errores:

1. **Error de foreign key**: Significa que falta alguna tabla base
2. **Error de sintaxis**: Copiar script completo sin modificar
3. **Error de permisos**: Verificar permisos de usuario MySQL

## 📱 Archivos en el Proyecto

Los archivos están listos en:
```
/modules/processes-tasks/
├── sql/
│   ├── install_optimized.sql  ← Script inteligente (recomendado)
│   ├── install.sql            ← Script completo original  
│   └── sample_data.sql        ← Datos de ejemplo
├── index.php                  ← Interfaz principal
├── controller.php             ← API backend
├── modals.php                 ← Modales de UI
├── config.php                 ← Configuración
├── css/processes-tasks.css    ← Estilos
└── js/processes-tasks.js      ← JavaScript
```

## 🚀 Resultado Esperado

Después de la instalación exitosa:
- ✅ 10 nuevas tablas creadas
- ✅ 2-3 vistas SQL disponibles  
- ✅ 2 triggers de auditoría activos
- ✅ Datos de ejemplo (si se ejecutó sample_data.sql)
- ✅ Módulo accesible vía web

¡Listo para usar! 🎉
