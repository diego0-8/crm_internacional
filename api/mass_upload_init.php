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
    
    if (!isset($_FILES['csv_file'])) {
        throw new Exception('No se ha subido ningún archivo');
    }
    
    $result = $massUploadController->procesarArchivoInmediato($_FILES['csv_file'], $user['cedula']);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
