<?php
require_once '../config.php';
require_once '../controller/CoordinadorController.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$coordinadorController = new CoordinadorController();
$user = getCurrentUser();

try {
    $result = $coordinadorController->getClientes($user['cedula']);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>