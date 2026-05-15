<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$newSecret = bin2hex(random_bytes(32));

try {
    if (function_exists('logActivity')) {
        logActivity('jwt_regenerate_requested', 'Se generó un nuevo JWT_SECRET sugerido (aplicación manual)');
    }
} catch (Throwable $e) {
    // no bloquear respuesta si logs_actividad u otra dependencia falla
}

// Invalidar tokens "recordarme" para forzar re-login en dispositivos con cookie guardada.
try {
    $db = getDB();
    $db->exec('DELETE FROM remember_tokens');
} catch (Throwable $e) {
    // tabla puede no existir en dumps mínimos
}

echo json_encode([
    'success' => true,
    'message' => 'Nuevo secreto generado. Cópielo en JWT_SECRET (variable de entorno o ajuste en config.php) y despliegue. Las cookies "recordarme" se invalidaron si la tabla existía.',
    'data' => [
        'new_secret' => $newSecret,
        'note' => 'JWT_SECRET se define al cargar config.php; no se sobrescribe el archivo automáticamente por seguridad.',
    ],
]);
