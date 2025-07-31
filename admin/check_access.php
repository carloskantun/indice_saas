<?php
/**
 * Verificador de Acceso Admin
 * Verifica que el sistema permita acceso correcto al área de administración
 */

require_once '../config.php';

// Verificar si está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/');
    exit();
}

$pdo = getDB();

try {
    // Obtener información del usuario actual
    $stmt = $pdo->prepare("SELECT name, email, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ../auth/logout.php');
        exit();
    }
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Verificador de Acceso Admin</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
    </style></head><body>";
    
    echo "<h1>🔍 Verificador de Acceso Admin</h1>";
    
    echo "<h2>👤 Información del Usuario</h2>";
    echo "<ul>";
    echo "<li><strong>Nombre:</strong> " . htmlspecialchars($user['name']) . "</li>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</li>";
    echo "<li><strong>Estado:</strong> " . $user['status'] . "</li>";
    echo "</ul>";
    
    echo "<h2>🏢 Roles y Empresas</h2>";
    
    // Verificar roles en empresas
    $stmt = $pdo->prepare("
        SELECT uc.role, c.name as company_name, c.id as company_id
        FROM user_companies uc
        JOIN companies c ON uc.company_id = c.id
        WHERE uc.user_id = ? AND uc.status = 'active'
        ORDER BY uc.role, c.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_companies = $stmt->fetchAll();
    
    if (count($user_companies) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Empresa</th><th>Rol</th><th>Acciones</th></tr>";
        foreach ($user_companies as $uc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($uc['company_name']) . "</td>";
            echo "<td>" . $uc['role'] . "</td>";
            $adminAccess = in_array($uc['role'], ['superadmin', 'admin']) ? 'Sí' : 'No';
            echo "<td class='" . (in_array($uc['role'], ['superadmin', 'admin']) ? 'success' : 'warning') . "'>Acceso Admin: $adminAccess</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No se encontraron asignaciones de empresa para este usuario</div>";
    }
    
    echo "<h2>🔐 Verificación de Permisos de Acceso</h2>";
    
    // Verificar si tiene acceso de superadmin o admin
    $hasAdminAccess = checkRole(['superadmin', 'admin']);
    $isSuperAdmin = checkRole(['superadmin']);
    
    echo "<ul>";
    echo "<li>Acceso al Panel Admin: " . ($hasAdminAccess ? "<span class='success'>✅ Permitido</span>" : "<span class='error'>❌ Denegado</span>") . "</li>";
    echo "<li>Rol Superadmin: " . ($isSuperAdmin ? "<span class='success'>✅ Sí</span>" : "<span class='info'>ℹ️ No</span>") . "</li>";
    echo "</ul>";
    
    echo "<h2>🏭 Estado de Empresa Activa</h2>";
    
    if (isset($_SESSION['current_company_id']) && !empty($_SESSION['current_company_id'])) {
        $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$_SESSION['current_company_id']]);
        $current_company = $stmt->fetch();
        
        if ($current_company) {
            echo "<div class='success'>✅ Empresa activa: " . htmlspecialchars($current_company['name']) . " (ID: {$_SESSION['current_company_id']})</div>";
        } else {
            echo "<div class='error'>❌ Empresa activa no válida (ID: {$_SESSION['current_company_id']})</div>";
        }
    } else {
        if ($isSuperAdmin) {
            echo "<div class='info'>ℹ️ Sin empresa seleccionada (modo global para superadmin)</div>";
        } else {
            echo "<div class='warning'>⚠️ Sin empresa activa (requerida para admin)</div>";
        }
    }
    
    echo "<h2>🚀 Acciones Recomendadas</h2>";
    
    if ($hasAdminAccess) {
        echo "<div class='success'>";
        echo "<p>✅ Tu usuario tiene acceso al panel de administración.</p>";
        echo "<a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>Ir al Panel Admin</a>";
        
        if (!$isSuperAdmin && (!isset($_SESSION['current_company_id']) || empty($_SESSION['current_company_id']))) {
            echo "<a href='../companies/' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Seleccionar Empresa</a>";
        }
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Tu usuario no tiene permisos para acceder al panel de administración.</p>";
        echo "<p>Contacta al administrador del sistema para solicitar permisos.</p>";
        echo "</div>";
    }
    
    echo "<br><br>";
    echo "<a href='../index.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Volver al Panel Principal</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
