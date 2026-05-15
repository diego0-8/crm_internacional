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
if (empty($input['asesor_cedula'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Asesor es requerido']);
    exit;
}

$coordinadorController = new CoordinadorController();
$user = getCurrentUser();

try {
    if (!empty($input['titular_id'])) {
        $result = $coordinadorController->asignarTitularAAsesor(
            (int) $input['titular_id'],
            $input['asesor_cedula'],
            $user['cedula']
        );
    } elseif (!empty($input['cliente_id'])) {
        $result = $coordinadorController->asignarClienteAAsesor(
            $input['cliente_id'],
            $input['asesor_cedula']
        );
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'titular_id o cliente_id es requerido']);
        exit;
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>