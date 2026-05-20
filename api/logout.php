<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../controller/LoginController.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

$loginController = new LoginController();

try {
    $result = $loginController->logout();
    app_clear_route();
    app_set_route('login');
    $result['redirect'] = app_home_url();
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
