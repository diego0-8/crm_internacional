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

$userController = new UserController();

try {
    $result = $userController->getAllUsers();
    if ($result['success']) {
        echo json_encode($result['data']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
