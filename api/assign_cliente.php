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

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Validar datos requeridos
if (empty($input['cliente_id']) || empty($input['asesor_cedula'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cliente ID y Asesor son requeridos']);
    exit;
}

$coordinadorController = new CoordinadorController();

try {
    $result = $coordinadorController->asignarClienteAAsesor(
        $input['cliente_id'],
        $input['asesor_cedula'],
        $input['notas'] ?? ''
    );
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>