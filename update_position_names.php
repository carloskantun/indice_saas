<?php
require_once 'config.php';

echo "🏷️ ACTUALIZANDO NOMBRES DE POSICIONES\n";
echo "====================================\n\n";

$db = getDB();

try {
    // Nombres para las posiciones existentes
    $positionNames = [
        1 => 'Gerente General',
        2 => 'Desarrollador Senior',
        3 => 'Vendedor',
        4 => 'Analista de HR',
        5 => 'Especialista en Marketing',
        6 => 'Desarrollador Junior',
        7 => 'Supervisor de Ventas',
        8 => 'Asistente Administrativo',
        9 => 'Contador',
        10 => 'Coordinador de Proyectos'
    ];
    
    echo "Actualizando nombres de posiciones...\n";
    
    foreach ($positionNames as $id => $name) {
        $stmt = $db->prepare("UPDATE positions SET name = ? WHERE id = ?");
        $result = $stmt->execute([$name, $id]);
        
        if ($result) {
            echo "✅ Posición $id: $name\n";
        } else {
            echo "❌ Error actualizando posición $id\n";
        }
    }
    
    echo "\n📋 Verificación final:\n";
    $stmt = $db->query("SELECT id, name FROM positions ORDER BY id");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($positions as $pos) {
        echo "- ID: " . $pos['id'] . " | " . $pos['name'] . "\n";
    }
    
    echo "\n✅ POSICIONES ACTUALIZADAS EXITOSAMENTE\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
