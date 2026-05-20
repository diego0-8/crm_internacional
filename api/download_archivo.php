<?php
require_once '../config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    exit('No autorizado');
}

$user = getCurrentUser();

if (!hasRole('coordinador')) {
    http_response_code(403);
    exit('Acceso denegado');
}

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    exit('ID de archivo requerido');
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM archivos_csv WHERE id = ? AND coordinador_cedula = ?");
    $stmt->execute([$id, $user['cedula']]);
    $archivo = $stmt->fetch();

    if (!$archivo) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }

    $rawPath = (string) ($archivo['ruta_archivo'] ?? '');
    if ($rawPath === '') {
        http_response_code(404);
        exit('Archivo no encontrado en el servidor');
    }

    // Resolver ruta (admite relativa) y confinar a uploads/csv/
    $allowedBase = realpath(__DIR__ . '/../uploads/csv');
    $candidate = realpath(__DIR__ . '/../' . ltrim(str_replace(['\\', "\0"], ['/', ''], $rawPath), '/'));

    if (!$allowedBase || !$candidate || strncmp($candidate, $allowedBase . DIRECTORY_SEPARATOR, strlen($allowedBase . DIRECTORY_SEPARATOR)) !== 0) {
        http_response_code(403);
        exit('Ruta de archivo inválida');
    }

    $filePath = $candidate;
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('Archivo no encontrado en el servidor');
    }

    $downloadName = (string) ($archivo['nombre_archivo'] ?? 'archivo.csv');
    $downloadName = basename(str_replace(["\r", "\n", "\0"], '', $downloadName));
    if ($downloadName === '') $downloadName = 'archivo.csv';

    // Forzar descarga
    header('Content-Type: application/octet-stream');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($filePath);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    exit('Error interno del servidor');
}
?>