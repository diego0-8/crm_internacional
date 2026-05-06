<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../controller/AsesorController.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$asesorController = new AsesorController();
$user = getCurrentUser();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ticketId = $input['ticket_id'] ?? null;
    $nuevoEstado = $input['estado'] ?? null;
    $observaciones = $input['observaciones'] ?? null;
    
    if (!$ticketId || !$nuevoEstado) {
        throw new Exception('ID del ticket y nuevo estado son requeridos');
    }
    
    $result = $asesorController->actualizarTicketEstado($ticketId, $nuevoEstado, $user['cedula'], $observaciones);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

