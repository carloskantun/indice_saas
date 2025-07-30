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
            // Crear nuevo negocio
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $type_id = $data['type_id'] ?? null;
            $unit_id = $data['unit_id'] ?? 0;
            
            if (empty($name) || !$unit_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar permisos en la unidad
            $stmt = $db->prepare("
                SELECT uc.role FROM units u 
                INNER JOIN user_companies uc ON u.company_id = uc.company_id 
                WHERE uc.user_id = ? AND u.id = ?
            ");
            $stmt->execute([$user_id, $unit_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para crear negocios en esta unidad']);
                exit;
            }
            
            // Verificar si ya existe un negocio con ese nombre en la unidad
            $stmt = $db->prepare("SELECT id FROM businesses WHERE unit_id = ? AND name = ?");
            $stmt->execute([$unit_id, $name]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Ya existe un negocio con ese nombre en esta unidad']);
                exit;
            }
            
            // Crear negocio
            $stmt = $db->prepare("INSERT INTO businesses (name, description, type_id, unit_id, status, created_by, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
            $stmt->execute([$name, $description, $type_id ?: null, $unit_id, $user_id]);
            $business_id = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => $lang['created_successfully'],
                'business_id' => $business_id
            ]);
            break;
            
        case 'PUT':
            // Actualizar negocio
            $data = json_decode(file_get_contents('php://input'), true);
            $business_id = $data['id'] ?? 0;
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $type_id = $data['type_id'] ?? null;
            
            if (empty($name) || !$business_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $db->prepare("
                SELECT uc.role FROM businesses b 
                INNER JOIN units u ON b.unit_id = u.id
                INNER JOIN user_companies uc ON u.company_id = uc.company_id 
                WHERE uc.user_id = ? AND b.id = ?
            ");
            $stmt->execute([$user_id, $business_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para editar este negocio']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE businesses SET name = ?, description = ?, type_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $description, $type_id ?: null, $business_id]);
            
            echo json_encode([
                'success' => true,
                'message' => $lang['updated_successfully']
            ]);
            break;
            
        case 'DELETE':
            // Eliminar negocio
            $business_id = $_GET['id'] ?? 0;
            
            if (!$business_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de negocio requerido']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $db->prepare("
                SELECT uc.role FROM businesses b 
                INNER JOIN units u ON b.unit_id = u.id
                INNER JOIN user_companies uc ON u.company_id = uc.company_id 
                WHERE uc.user_id = ? AND b.id = ?
            ");
            $stmt->execute([$user_id, $business_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para eliminar este negocio']);
                exit;
            }
            
            // Eliminar negocio
            $stmt = $db->prepare("DELETE FROM businesses WHERE id = ?");
            $stmt->execute([$business_id]);
            
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
