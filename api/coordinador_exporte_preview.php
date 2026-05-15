<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/CoordinadorController.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$controller = new CoordinadorController();

$tipoReporte = $_GET['tipo_reporte'] ?? 'titulares_reparto';
$fechaInicio = $_GET['fecha_inicio'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;
$filtroAsignacion = $_GET['filtro_asignacion'] ?? '';

try {
    $result = $controller->previewExporte(
        $user['cedula'],
        (string) $tipoReporte,
        $fechaInicio !== null && $fechaInicio !== '' ? (string) $fechaInicio : null,
        $fechaFin !== null && $fechaFin !== '' ? (string) $fechaFin : null,
        (string) $filtroAsignacion
    );
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
