<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!checkAuth()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$user_id = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'POST':
            // Crear nueva unidad
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $company_id = $data['company_id'] ?? 0;
            
            if (empty($name) || !$company_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar permisos en la empresa
            $stmt = $db->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ?");
            $stmt->execute([$user_id, $company_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para crear unidades en esta empresa']);
                exit;
            }
            
            // Verificar si ya existe una unidad con ese nombre en la empresa
            $stmt = $db->prepare("SELECT id FROM units WHERE company_id = ? AND name = ?");
            $stmt->execute([$company_id, $name]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Ya existe una unidad con ese nombre en esta empresa']);
                exit;
            }
            
            // Crear unidad
            $stmt = $db->prepare("INSERT INTO units (name, description, company_id, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $description, $company_id, $user_id]);
            $unit_id = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => $lang['created_successfully'],
                'unit_id' => $unit_id
            ]);
            break;
            
        case 'PUT':
            // Actualizar unidad
            $data = json_decode(file_get_contents('php://input'), true);
            $unit_id = $data['id'] ?? 0;
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            
            if (empty($name) || !$unit_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $db->prepare("
                SELECT uc.role FROM units u 
                INNER JOIN user_companies uc ON u.company_id = uc.company_id 
                WHERE uc.user_id = ? AND u.id = ?
            ");
            $stmt->execute([$user_id, $unit_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para editar esta unidad']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE units SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $description, $unit_id]);
            
            echo json_encode([
                'success' => true,
                'message' => $lang['updated_successfully']
            ]);
            break;
            
        case 'DELETE':
            // Eliminar unidad
            $unit_id = $_GET['id'] ?? 0;
            
            if (!$unit_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de unidad requerido']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $db->prepare("
                SELECT uc.role FROM units u 
                INNER JOIN user_companies uc ON u.company_id = uc.company_id 
                WHERE uc.user_id = ? AND u.id = ?
            ");
            $stmt->execute([$user_id, $unit_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para eliminar esta unidad']);
                exit;
            }
            
            // Verificar si tiene negocios asociados
            $stmt = $db->prepare("SELECT COUNT(*) FROM businesses WHERE unit_id = ?");
            $stmt->execute([$unit_id]);
            $businessCount = $stmt->fetchColumn();
            
            if ($businessCount > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede eliminar una unidad que tiene negocios asociados']);
                exit;
            }
            
            // Eliminar unidad
            $stmt = $db->prepare("DELETE FROM units WHERE id = ?");
            $stmt->execute([$unit_id]);
            
            echo json_encode([
                'success' => true,
                'message' => $lang['deleted_successfully']
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
