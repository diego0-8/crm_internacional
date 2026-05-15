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
if (empty($input['total_clientes'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Total de clientes es requerido']);
    exit;
}

$coordinadorController = new CoordinadorController();
$user = getCurrentUser();

try {
    $result = $coordinadorController->asignarTitularesAutomatico(
        $user['cedula'],
        (int) $input['total_clientes'],
        $input['notas'] ?? ''
    );
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>