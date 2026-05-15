<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/CoordinadorController.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$coordinadorController = new CoordinadorController();
$user = getCurrentUser();

$fechaInicio = $_GET['fecha_inicio'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;
$tipoReporte = $_GET['tipo_reporte'] ?? 'titulares_reparto';
$filtroAsignacion = $_GET['filtro_asignacion'] ?? '';

try {
    $result = $coordinadorController->exportarReporte(
        $user['cedula'],
        (string) $tipoReporte,
        $fechaInicio !== null && $fechaInicio !== '' ? (string) $fechaInicio : null,
        $fechaFin !== null && $fechaFin !== '' ? (string) $fechaFin : null,
        (string) $filtroAsignacion
    );

    if (!$result['success']) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

    $allowedBase = realpath(__DIR__ . '/../uploads/exports');
    $ruta = (string) ($result['ruta'] ?? '');
    $candidate = $ruta !== '' ? realpath($ruta) : false;
    if (!$allowedBase || !$candidate || strncmp($candidate, $allowedBase . DIRECTORY_SEPARATOR, strlen($allowedBase . DIRECTORY_SEPARATOR)) !== 0) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Ruta de exportación inválida']);
        exit;
    }

    $downloadName = basename(str_replace(["\r", "\n", "\0"], '', (string) ($result['archivo'] ?? 'reporte.csv')));
    if ($downloadName === '') {
        $downloadName = 'reporte.csv';
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($candidate));
    readfile($candidate);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    error_log('export_metricas.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
