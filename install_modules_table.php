<?php
/**
 * Script para crear las tablas de módulos
 * Ejecutar una vez para crear las estructuras necesarias
 */

require_once 'config.php';

try {
    $pdo = getDB();
    
    echo "<h1>Creando tablas de módulos...</h1>";
    
        // Crear tabla modules
        $createModulesSQL = "
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'fas fa-puzzle-piece',
            color VARCHAR(7) DEFAULT '#3498db',
            url VARCHAR(200),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createModulesSQL);
        echo "<div class='success'>✅ Tabla 'modules' creada exitosamente</div>";
        
        // Agregar índice único de forma segura
        try {
            $pdo->exec("ALTER TABLE modules ADD UNIQUE KEY slug (slug)");
            echo "<div class='success'>✅ Índice único 'slug' agregado</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='info'>ℹ️ Índice 'slug' ya existe</div>";
            } else {
                throw $e;
            }
        }    // Crear tabla plan_modules
    $sql = "CREATE TABLE IF NOT EXISTS `plan_modules` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `plan_id` int(11) NOT NULL,
      `module_id` int(11) NOT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `plan_module_unique` (`plan_id`, `module_id`),
      KEY `plan_id` (`plan_id`),
      KEY `module_id` (`module_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p>✅ Tabla 'plan_modules' creada</p>";
    
    // Verificar si ya existen módulos
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM modules");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // Insertar módulos predefinidos
        $modules = [
            ['Gastos', 'gastos', 'Gestión y control de gastos empresariales', 'fas fa-coins', '#e74c3c'],
            ['Mantenimiento', 'mantenimiento', 'Control de mantenimiento de equipos y vehículos', 'fas fa-tools', '#f39c12'],
            ['Servicio al Cliente', 'servicio_cliente', 'Gestión de tickets y atención al cliente', 'fas fa-headset', '#3498db'],
            ['Usuarios', 'usuarios', 'Gestión de usuarios y permisos', 'fas fa-users', '#9b59b6'],
            ['KPIs', 'kpis', 'Indicadores clave de rendimiento', 'fas fa-chart-line', '#27ae60'],
            ['Compras', 'compras', 'Gestión de compras y proveedores', 'fas fa-shopping-cart', '#34495e'],
            ['Lavandería', 'lavanderia', 'Control de servicios de lavandería', 'fas fa-tshirt', '#1abc9c'],
            ['Transfers', 'transfers', 'Gestión de servicios de transporte', 'fas fa-bus', '#e67e22']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO modules (name, slug, description, icon, color, status) VALUES (?, ?, ?, ?, ?, 'active')");
        
        $insertedCount = 0;
        foreach ($modules as $module) {
            $result = $stmt->execute($module);
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Módulo '{$module[0]}' insertado</p>";
                $insertedCount++;
            } else {
                echo "<p>ℹ️ Módulo '{$module[0]}' ya existe</p>";
            }
        }
        
        echo "<div class='success'><strong>Resumen: $insertedCount módulos nuevos insertados</strong></div>";
        
        echo "<p><strong>✅ {count($modules)} módulos predefinidos insertados</strong></p>";
    } else {
        echo "<p>ℹ️ Ya existen $count módulos en la base de datos</p>";
    }
    
    // Verificar estructura final
    $stmt = $pdo->query("SELECT * FROM modules ORDER BY name");
    $modules = $stmt->fetchAll();
    
    echo "<h2>Módulos disponibles:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Descripción</th><th>Ícono</th><th>Color</th><th>Estado</th></tr>";
    
    foreach ($modules as $module) {
        echo "<tr>";
        echo "<td>{$module['id']}</td>";
        echo "<td>{$module['name']}</td>";
        echo "<td><code>{$module['slug']}</code></td>";
        echo "<td>{$module['description']}</td>";
        echo "<td><i class='{$module['icon']}'></i> {$module['icon']}</td>";
        echo "<td><span style='background: {$module['color']}; color: white; padding: 2px 8px; border-radius: 3px;'>{$module['color']}</span></td>";
        echo "<td>{$module['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h2>🎉 ¡Instalación completada exitosamente!</h2>";
    echo "<p><a href='panel_root/modules.php'>➡️ Ir a Gestión de Módulos</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
