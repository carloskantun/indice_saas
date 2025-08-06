<?php
require_once '../config.php';

// Verificar autenticación
if (!checkAuth()) {
    if (isset($_POST['action'])) {
        // Formulario HTML - redirigir
        header('Location: ../auth/');
        exit;
    } else {
        // API JSON
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$user_id = $_SESSION['user_id'];

// Determinar si es una petición de formulario HTML o API JSON
$isFormRequest = isset($_POST['action']);

try {
    switch ($method) {
        case 'POST':
            $action = $_POST['action'] ?? '';
            
            if ($action === 'create_company') {
                // Crear nueva empresa desde formulario
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = $_POST['status'] ?? 'active';
                
                if (empty($name)) {
                    if ($isFormRequest) {
                        $_SESSION['error'] = 'El nombre de la empresa es requerido';
                        header('Location: index.php');
                        exit;
                    } else {
                        header('Content-Type: application/json');
                        http_response_code(400);
                        echo json_encode(['error' => 'El nombre de la empresa es requerido']);
                        exit;
                    }
                }
                
                // Verificar si ya existe una empresa con ese nombre para el usuario
                $stmt = $db->prepare("
                    SELECT c.id FROM companies c 
                    INNER JOIN user_companies uc ON c.id = uc.company_id 
                    WHERE uc.user_id = ? AND c.name = ?
                ");
                $stmt->execute([$user_id, $name]);
                if ($stmt->fetch()) {
                    if ($isFormRequest) {
                        $_SESSION['error'] = 'Ya tienes una empresa con ese nombre';
                        header('Location: index.php');
                        exit;
                    } else {
                        header('Content-Type: application/json');
                        http_response_code(400);
                        echo json_encode(['error' => 'Ya tienes una empresa con ese nombre']);
                        exit;
                    }
                }
                
                $db->beginTransaction();
                
                // Crear empresa
                $stmt = $db->prepare("INSERT INTO companies (name, description, status, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $description, $status, $user_id]);
                $company_id = $db->lastInsertId();
                
                // Relacionar usuario con empresa como admin
                $stmt = $db->prepare("INSERT INTO user_companies (user_id, company_id, role, created_at, last_accessed) VALUES (?, ?, 'admin', NOW(), NOW())");
                $stmt->execute([$user_id, $company_id]);
                
                $db->commit();
                
                if ($isFormRequest) {
                    $_SESSION['success'] = 'Empresa creada exitosamente';
                    header('Location: index.php');
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Empresa creada exitosamente',
                        'company_id' => $company_id
                    ]);
                    exit;
                }
            }
            
            // API JSON original para otras acciones
            header('Content-Type: application/json');
                'company_id' => $company_id
            ]);
            break;
            
        case 'PUT':
            // Actualizar empresa
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $data['id'] ?? 0;
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            
            if (empty($name) || !$company_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $db->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ?");
            $stmt->execute([$user_id, $company_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para editar esta empresa']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE companies SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $description, $company_id]);
            
            echo json_encode([
                'success' => true,
                'message' => $lang['updated_successfully']
            ]);
            break;
            
        case 'DELETE':
            // Eliminar empresa
            $company_id = $_GET['id'] ?? 0;
            
            if (!$company_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de empresa requerido']);
                exit;
            }
            
            // Verificar permisos
            $stmt = $db->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ?");
            $stmt->execute([$user_id, $company_id]);
            $userRole = $stmt->fetchColumn();
            
            if (!in_array($userRole, ['admin', 'superadmin', 'root'])) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para eliminar esta empresa']);
                exit;
            }
            
            $db->beginTransaction();
            
            // Eliminar relaciones usuario-empresa
            $stmt = $db->prepare("DELETE FROM user_companies WHERE company_id = ?");
            $stmt->execute([$company_id]);
            
            // Eliminar empresa
            $stmt = $db->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            
            $db->commit();
            
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
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
