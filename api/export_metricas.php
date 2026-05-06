<?php
require_once '../config.php';
require_once '../controller/CoordinadorController.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$coordinadorController = new CoordinadorController();
$user = getCurrentUser();

try {
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    
    $result = $coordinadorController->exportarMetricas($user['cedula'], $fechaInicio, $fechaFin);
    
    if ($result['success']) {
        $allowedBase = realpath(__DIR__ . '/../uploads/exports');
        $ruta = (string) ($result['ruta'] ?? '');
        $candidate = realpath($ruta);
        if (!$allowedBase || !$candidate || strncmp($candidate, $allowedBase . DIRECTORY_SEPARATOR, strlen($allowedBase . DIRECTORY_SEPARATOR)) !== 0) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ruta de exportación inválida']);
            exit;
        }

        $downloadName = (string) ($result['archivo'] ?? 'metricas.csv');
        $downloadName = basename(str_replace(["\r", "\n", "\0"], '', $downloadName));
        if ($downloadName === '') $downloadName = 'metricas.csv';

        // Enviar archivo para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($candidate));
        readfile($candidate);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    error_log("export_metricas.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
