<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/TiketeraModel.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('cliente')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$tiketeraModel = new TiketeraModel();

try {
    // Obtener tickets del cliente
    $tickets = $tiketeraModel->getTicketsByCliente($user['cedula']);

    echo json_encode([
        'success' => true,
        'data' => $tickets
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>