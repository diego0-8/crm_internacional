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

$asesorController = new AsesorController();
$user = getCurrentUser();

try {
    $busqueda = $_GET['busqueda'] ?? null;
    
    if ($busqueda) {
        $result = $asesorController->buscarClientes($user['cedula'], $busqueda);
    } else {
        $result = $asesorController->getClientesAsesor($user['cedula']);
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
