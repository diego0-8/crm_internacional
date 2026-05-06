<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/TicketImportController.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
if ($batchId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'batch_id requerido']);
    exit;
}

$user = getCurrentUser();

try {
    $ctrl = new TicketImportController();
    $rows = $ctrl->detalleLoteErrores($batchId, $user['cedula']);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
