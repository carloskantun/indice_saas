<?php
/**
 * Script para actualizar roles de usuarios existentes
 */
require_once 'config.php';

$db = getDB();

echo "<h1>🔄 Actualización de Roles de Usuario</h1>";

try {
    // Actualizar todos los usuarios existentes en user_companies a superadmin
    // (temporal para testing - en producción sería más selectivo)
    $stmt = $db->prepare("UPDATE user_companies SET role = 'superadmin' WHERE role IN ('admin', 'user')");
    $affected = $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    
    echo "<p>✅ Actualizados $rowsAffected usuarios a rol 'superadmin'</p>";
    
    // Mostrar usuarios actualizados
    $stmt = $db->query("
        SELECT u.name, u.email, uc.role, c.name as company_name 
        FROM users u 
        INNER JOIN user_companies uc ON u.id = uc.user_id 
        INNER JOIN companies c ON uc.company_id = c.id 
        ORDER BY u.name
    ");
    $users = $stmt->fetchAll();
    
    echo "<h3>👥 Usuarios con sus roles actualizados:</h3>";
    echo "<table class='table table-striped'>";
    echo "<tr><th>Usuario</th><th>Email</th><th>Empresa</th><th>Rol</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['company_name']) . "</td>";
        echo "<td><span class='badge bg-primary'>" . htmlspecialchars($user['role']) . "</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='companies/' class='btn btn-primary'>🏢 Ir a Companies</a></p>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container mt-4"><?php echo isset($output) ? $output : ''; ?></div>
