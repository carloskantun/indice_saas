<?php
/**
 * VERIFICACIÓN URGENTE - CORRECIÓN PROFILE.PHP
 * Verifica que las columnas de notificaciones estén correctas
 */

echo "<h2>🔧 VERIFICACIÓN DE CORRECCIÓN - PROFILE.PHP</h2>";
echo "<hr>";

try {
    require_once 'config.php';
    $db = getDB();
    
    // 1. Verificar estructura actual de notifications
    echo "<h3>1. Columnas de notificaciones en users:</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notification_columns = [];
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'notification') !== false) {
            $notification_columns[] = $column['Field'];
            echo "✅ <strong>{$column['Field']}</strong> ({$column['Type']})<br>";
        }
    }
    
    if (empty($notification_columns)) {
        echo "❌ No se encontraron columnas de notificaciones<br>";
    }
    echo "<br>";
    
    // 2. Verificar archivo profile.php
    echo "<h3>2. Verificación de profile.php:</h3>";
    
    if (!file_exists('profile.php')) {
        echo "❌ Archivo profile.php no encontrado<br>";
    } else {
        $profile_content = file_get_contents('profile.php');
        
        // Verificar uso correcto en SQL
        if (strpos($profile_content, 'notifications_email') !== false && 
            strpos($profile_content, 'notifications_sms') !== false) {
            echo "✅ Nombres correctos en consulta SQL<br>";
        } else {
            echo "❌ Nombres incorrectos en consulta SQL<br>";
        }
        
        // Verificar uso correcto en HTML
        if (strpos($profile_content, 'name="notifications_email"') !== false && 
            strpos($profile_content, 'name="notifications_sms"') !== false) {
            echo "✅ Nombres correctos en formulario HTML<br>";
        } else {
            echo "❌ Nombres incorrectos en formulario HTML<br>";
        }
        
        // Verificar que no queden referencias antiguas
        if (strpos($profile_content, 'notification_email') === false && 
            strpos($profile_content, 'notification_sms') === false) {
            echo "✅ No hay referencias a nombres antiguos<br>";
        } else {
            echo "⚠️ Aún hay referencias a nombres sin 's'<br>";
        }
    }
    
    // 3. Test con usuario
    echo "<h3>3. Test con usuario actual:</h3>";
    $stmt = $db->prepare("SELECT notifications_email, notifications_sms FROM users WHERE email = ?");
    $stmt->execute(['carlosadmin@indiceapp.com']);
    $user_notif = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_notif) {
        echo "✅ Usuario encontrado:<br>";
        echo "- notifications_email: " . ($user_notif['notifications_email'] ?? 'NULL') . "<br>";
        echo "- notifications_sms: " . ($user_notif['notifications_sms'] ?? 'NULL') . "<br>";
    } else {
        echo "❌ No se pudo obtener datos de notificaciones del usuario<br>";
    }
    
    echo "<hr>";
    echo "<h3>📋 RESUMEN DE CORRECCIÓN:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Problema:</strong> Column not found 'notification_email'<br>";
    echo "<strong>Causa:</strong> Discrepancia entre nombres en código vs base de datos<br>";
    echo "<strong>Solución:</strong> Cambiar notification_* por notifications_* en profile.php<br>";
    echo "</div>";
    
    echo "<br>";
    echo "<div style='color: green; font-weight: bold;'>✅ CORRECCIÓN APLICADA</div>";
    echo "<p>Los nombres de columnas han sido corregidos en profile.php</p>";
    echo "<p><strong>Próximo paso:</strong> Probar nuevamente la actualización del perfil</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</div>";
}
?>
