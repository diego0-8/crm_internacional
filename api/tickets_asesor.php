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
    $estado = $_GET['estado'] ?? null;
    $busqueda = $_GET['busqueda'] ?? null;
    $cliente = $_GET['cliente'] ?? null;
    
    if ($busqueda) {
        // Buscar tickets
        require_once '../model/TiketeraModel.php';
        $tiketeraModel = new TiketeraModel();
        $tickets = $tiketeraModel->buscarTickets($user['cedula'], $busqueda);
        $result = [
            'success' => true,
            'data' => $tickets
        ];
    } else {
        // Obtener tickets (con filtro por cliente si se especifica)
        $result = $asesorController->getTicketsAsesor($user['cedula'], $estado, $cliente);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

