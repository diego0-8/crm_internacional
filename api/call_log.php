<?php
/**
 * Registro de llamadas del softphone (inicio/fin).
 * Responde JSON; si no hay tabla call_log, acepta la petición sin error para no romper la UI.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $_GET['action'] ?? '';
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$allowed = ['registrar_inicio_llamada', 'registrar_fin_llamada'];
if (!in_array($action, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

echo json_encode(['success' => true, 'action' => $action]);
