<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/CoordinadorController.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['titular_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'titular_id es requerido']);
    exit;
}

$coordinadorController = new CoordinadorController();
$user = getCurrentUser();

try {
    $result = $coordinadorController->desasignarTitularDeAsesor(
        (int) $input['titular_id'],
        $user['cedula']
    );
    if (empty($result['success'])) {
        http_response_code(400);
    }
    echo json_encode($result);
} catch (Exception $e) {
    error_log('desasignar_titular.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
