<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../controller/MassUploadController.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$massUploadController = new MassUploadController();
$user = getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['archivo_id']) || !isset($input['lote'])) {
        throw new Exception('Parámetros requeridos: archivo_id, lote');
    }
    
    $archivoId = $input['archivo_id'];
    $lote = $input['lote'];
    
    $result = $massUploadController->procesarLote($archivoId, $lote, $user['cedula']);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
