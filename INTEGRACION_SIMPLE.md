# 🎯 Integración Simple con tu Panel Root

## ✅ Lo que YA TIENES funcionando:

### 📊 Panel Root Completo (`/panel_root/`)
- **Gestión de Planes** - Crear, editar, eliminar planes
- **Gestión de Empresas** - Administrar todas las empresas
- **Gestión de Usuarios** - Control total de usuarios
- **Gestión de Módulos** - Activar/desactivar funcionalidades
- **Dashboard con estadísticas** - Métricas del sistema

### 🏗️ Estructura de Base de Datos
- **Tabla `plans`** con: `id`, `name`, `description`, `price_monthly`, `users_max`, `businesses_max`, `units_max`, `storage_max_mb`, `modules_included`, `is_active`
- **Tabla `companies`** con `plan_id` relacionado
- **Tabla `user_companies`** para roles y permisos

## 🔧 Lo que necesitamos ADAPTAR:

### 1. **Registro Simplificado** ✅ (YA CORREGIDO)
- **Archivo:** `auth/register.php` 
- **Cambio:** Usar `is_active = 1` en lugar de `status = 'active'`
- **Funciona:** Selección de planes existentes de tu panel root

### 2. **Sistema de Notificaciones** ⚠️ (SIMPLIFICAR)
En lugar del sistema complejo que creé, solo necesitas:

```php
// Archivo simple: includes/simple_notifications.php
function createSimpleNotification($company_id, $user_id, $message) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (company_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
    return $stmt->execute([$company_id, $user_id, $message]);
}

function getNotificationsForUser($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
```

### 3. **Restricciones Básicas** ⚠️ (SIMPLIFICAR)
```php
// Archivo: includes/simple_restrictions.php
function checkPlanLimit($company_id, $limit_type) {
    $db = getDB();
    
    // Obtener límites del plan
    $stmt = $db->prepare("
        SELECT p.users_max, p.businesses_max 
        FROM companies c 
        JOIN plans p ON c.plan_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$company_id]);
    $limits = $stmt->fetch();
    
    // Contar uso actual
    if ($limit_type === 'users') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM user_companies WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $current = $stmt->fetchColumn();
        return ($limits['users_max'] == -1) || ($current < $limits['users_max']);
    }
    
    return true; // Por defecto permitir
}
```

## 🎯 **Integración REAL que necesitas:**

### En lugar de todo lo complejo que creamos, solo necesitas:

1. **Un botón en tu panel root** para enviar notificaciones
2. **Verificaciones simples** antes de crear usuarios/empresas
3. **Un sistema básico de invitaciones** que use tu estructura existente

### **Ejemplo: Invitación Simple**

```php
// En companies/invite.php
if (checkPlanLimit($_SESSION['company_id'], 'users')) {
    // Crear invitación
    $message = "Has sido invitado a unirte a " . $company_name;
    createSimpleNotification($company_id, $invited_user_id, $message);
    echo "Invitación enviada";
} else {
    echo "Has alcanzado el límite de usuarios de tu plan";
}
```

## 🚀 **Pasos Siguientes:**

1. **¿Quieres que simplifique todo el sistema?**
2. **¿O prefieres mantener tu panel root tal como está?**
3. **¿Qué funcionalidad específica necesitas que no tienes?**

### **Lo que REALMENTE necesitas decidir:**
- ✅ **Panel Root** - Ya tienes todo
- ❓ **¿Sistema de notificaciones simple?**
- ❓ **¿Verificaciones de límites básicas?**
- ❓ **¿Invitaciones por email o internas?**

**Dime exactamente qué quieres agregar a tu sistema actual** y lo haremos de manera simple, sin complicar lo que ya tienes funcionando.
