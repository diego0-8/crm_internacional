<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/CoordinadorController.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Datos inválidos');
    }

    $archivoId = $input['archivo_id'] ?? null;
    if ($archivoId === null || $archivoId === '') {
        throw new Exception('ID de archivo requerido');
    }

    if (!array_key_exists('activo', $input)) {
        throw new Exception('Indique activo (1 habilitar, 0 inhabilitar)');
    }

    $activo = (int) $input['activo'] === 1;
    $user = getCurrentUser();
    $coordinadorController = new CoordinadorController();
    $result = $coordinadorController->setArchivoCsvActivo($archivoId, $user['cedula'], $activo);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
