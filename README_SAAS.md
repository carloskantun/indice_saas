# 📦 Indice SaaS - Sistema Modular Empresarial

Sistema SaaS modular desarrollado en PHP nativo + Bootstrap para la gestión de múltiples empresas, unidades de negocio y módulos funcionales.

## 🚀 Características Principales

- **Multi-empresa**: Gestión de múltiples empresas por usuario
- **Estructura jerárquica**: Empresas → Unidades → Negocios → Módulos
- **Sistema de roles**: root, support, superadmin, admin, moderator, user
- **Módulos intercambiables**: Sistema preparado para módulos como gastos, mantenimiento, etc.
- **Responsive**: Bootstrap 5 + Font Awesome
- **Seguridad**: PDO, sesiones seguras, validación de permisos

## 📁 Estructura del Proyecto

```
indice_saas/
├── config.php              # Configuración principal
├── index.php               # Punto de entrada
├── install_database.php    # Script de instalación de BD
├── lang/
│   └── es.php              # Archivo de idioma español
├── auth/                   # Sistema de autenticación
│   ├── index.php          # Login
│   ├── register.php       # Registro
│   └── logout.php         # Cerrar sesión
├── companies/             # Gestión de empresas
│   ├── index.php          # Lista de empresas
│   ├── controller.php     # API REST para empresas
│   ├── style.css          # Estilos
│   └── js/
│       └── companies.js   # JavaScript
├── units/                 # Gestión de unidades
│   ├── index.php          # Lista de unidades
│   ├── controller.php     # API REST para unidades
│   ├── style.css          # Estilos
│   └── js/
│       └── units.js       # JavaScript
├── businesses/            # Gestión de negocios
│   ├── index.php          # Lista de negocios
│   ├── controller.php     # API REST para negocios
│   ├── style.css          # Estilos
│   └── js/
│       └── businesses.js  # JavaScript
└── modules/               # Hub de módulos
    ├── index.php          # Lista de módulos disponibles
    ├── style.css          # Estilos
    └── js/
        └── modules.js     # JavaScript
```

## ⚙️ Instalación

### 1. Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL

### 2. Configuración de Base de Datos
1. Crea una base de datos MySQL:
   ```sql
   CREATE DATABASE indice_saas;
   ```

2. Configura la conexión en `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'indice_saas');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_password');
   ```

### 3. Instalación de Tablas
Ejecuta el script de instalación:
```bash
php install_database.php
```

Este script creará todas las tablas necesarias e insertará datos iniciales.

### 4. Credenciales Iniciales
- **Email**: admin@indiceapp.com
- **Password**: admin123

⚠️ **IMPORTANTE**: Cambia estas credenciales inmediatamente en producción.

## 🔐 Sistema de Roles y Permisos

### Jerarquía de Roles
1. **root** - Acceso total al sistema
2. **support** - Soporte técnico
3. **superadmin** - Administrador completo de empresa
4. **admin** - Administrador de empresa
5. **moderator** - Moderador con permisos limitados
6. **user** - Usuario básico (solo lectura)

### Variables de Sesión
```php
$_SESSION['user_id']      // ID del usuario
$_SESSION['company_id']   // Empresa actual
$_SESSION['unit_id']      // Unidad actual
$_SESSION['business_id']  // Negocio actual
$_SESSION['current_role'] // Rol actual del usuario
```

## 🏗️ Arquitectura del Sistema

### 1. Estructura Jerárquica
```
👤 Usuario
└── 🏢 Empresa (puede tener múltiples)
    └── 🏭 Unidad de Negocio
        └── 🏪 Negocio
            └── 📦 Módulos Funcionales
```

### 2. Módulos Disponibles
- ✅ **Gastos** - Gestión de ingresos y egresos (activo)
- 🔜 **Mantenimiento** - Control de servicios técnicos
- 🔜 **Servicio al Cliente** - Gestión de tickets y soporte
- 🔜 **Inventario** - Control de stock y productos
- 🔜 **Ventas** - Facturación y gestión comercial
- 🔜 **Empleados** - Gestión de personal y nómina

### 3. API REST
Cada módulo incluye un controlador con endpoints REST:
- `POST` - Crear registro
- `GET` - Listar/obtener registros
- `PUT` - Actualizar registro
- `DELETE` - Eliminar registro

## 🎨 Interfaz de Usuario

### Tecnologías Frontend
- **Bootstrap 5.3** - Framework CSS
- **Font Awesome 6** - Iconografía
- **JavaScript Vanilla** - Interactividad
- **CSS3** - Estilos personalizados

### Características de UX
- Diseño responsive para móviles y escritorio
- Navegación breadcrumb intuitiva
- Alertas dinámicas con auto-dismiss
- Estados de carga y confirmaciones
- Modo claro/oscuro (futuro)

## 🔄 Flujo de Trabajo Típico

1. **Login** → Usuario se autentica
2. **Empresas** → Selecciona o crea empresa
3. **Unidades** → Navega a unidades de la empresa
4. **Negocios** → Accede a negocios específicos
5. **Módulos** → Utiliza módulos funcionales (gastos, etc.)

## 🛠️ Desarrollo de Nuevos Módulos

### 1. Estructura Mínima
Cada módulo debe tener:
```
modulo/
├── index.php          # Vista principal
├── controller.php     # API REST
├── style.css          # Estilos específicos
└── js/
    └── modulo.js      # JavaScript del módulo
```

### 2. Plantilla Base
```php
<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    redirect('auth/');
}

// Verificar permisos
if (!checkRole(['admin', 'superadmin', 'root'])) {
    redirect('companies/');
}

// Tu código aquí...
?>
```

### 3. Registro en Sistema
Agregar el módulo en `/modules/index.php`:
```php
[
    'id' => 'nuevo_modulo',
    'name' => 'Nuevo Módulo',
    'description' => 'Descripción del módulo',
    'icon' => 'fas fa-icon',
    'color' => 'primary',
    'url' => 'nuevo_modulo/',
    'active' => true
]
```

## 🚨 Consideraciones de Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Consultas preparadas PDO contra SQL injection
- ✅ Validación de permisos por rol y contexto
- ✅ Sesiones seguras con regeneración de ID
- ✅ Escape de datos en vistas con `htmlspecialchars()`
- ✅ Validación de entrada en formularios
- ✅ Headers de seguridad (futuro)
- ✅ CSRF tokens (futuro)

## 📱 Compatibilidad

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Dispositivos móviles iOS/Android

## 🔮 Roadmap

### Versión 1.1
- [ ] Módulo de Mantenimiento completo
- [ ] Módulo de Servicio al Cliente
- [ ] Sistema de notificaciones
- [ ] API keys para integraciones

### Versión 1.2
- [ ] Módulo de Inventario
- [ ] Módulo de Ventas
- [ ] Dashboard con métricas
- [ ] Exportaciones avanzadas

### Versión 2.0
- [ ] Modo multi-idioma
- [ ] Tema oscuro/claro
- [ ] PWA (Progressive Web App)
- [ ] Integración con WhatsApp/Telegram

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📞 Soporte

- **Documentación**: Este README
- **Issues**: GitHub Issues
- **Email**: soporte@indiceapp.com

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

Desarrollado con ❤️ para la gestión empresarial moderna.
