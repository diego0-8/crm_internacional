<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/TicketImportController.php';

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

$user = getCurrentUser();
$crear = isset($_POST['crear_clientes_si_faltan']) && $_POST['crear_clientes_si_faltan'] === '1';
$asesorDefault = isset($_POST['asesor_default_cedula']) ? trim((string) $_POST['asesor_default_cedula']) : '';
if ($asesorDefault === '') {
    $asesorDefault = null;
}

if (!isset($_FILES['archivo_csv']) || ($_FILES['archivo_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe adjuntar un archivo CSV']);
    exit;
}

try {
    $ctrl = new TicketImportController();
    $result = $ctrl->procesarCsvTickets($_FILES['archivo_csv'], $user['cedula'], $crear, $asesorDefault);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
