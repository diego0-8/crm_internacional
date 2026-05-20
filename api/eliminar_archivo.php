<?php
header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $archivoId = $input['archivo_id'] ?? null;
    
    if (!$archivoId) {
        throw new Exception('ID de archivo requerido');
    }
    
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'No se permite eliminar cargues CSV. Use inhabilitar para conservar el historial de gestión y tickets.',
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
