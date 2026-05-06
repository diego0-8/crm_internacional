<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/TicketImportController.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();

try {
    $ctrl = new TicketImportController();
    $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
    $data = $ctrl->listarLotes($user['cedula'], $limite);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
