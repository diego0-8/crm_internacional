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
    $coordinatorId = (int)($input['coordinador_cedula'] ?? 0);
    $advisorIds = $input['advisor_cedulas'] ?? [];
    
    if (!$coordinatorId) {
        throw new Exception('ID de coordinador requerido');
    }
    
    if (empty($advisorIds) || !is_array($advisorIds)) {
        throw new Exception('Seleccione al menos un asesor');
    }
    
    $result = $userController->assignAdvisorsToCoordinator($coordinatorId, $advisorIds);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
