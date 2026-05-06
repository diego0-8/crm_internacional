<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../controller/UserController.php';

// Verificar autenticación
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$userController = new UserController();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['user_cedula'] ?? 0);
    
    if (!$userId) {
        throw new Exception('ID de usuario requerido');
    }
    
    $result = $userController->toggleUserStatus($userId);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
