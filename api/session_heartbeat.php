<?php
header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();

try {
    // Actualizar timestamp de última actividad
    $_SESSION['last_activity'] = time();

    // Verificar si el usuario aún existe y está activo
    if (!$user || !$user['activo']) {
        // Usuario inactivo o eliminado, forzar logout
        forceLogout();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Log de actividad (opcional, solo para debugging)
    // logActivity('heartbeat', 'Verificación de sesión activa');

    echo json_encode([
        'success' => true,
        'message' => 'Sesión activa',
        'user' => [
            'cedula' => $user['cedula'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'],
            'rol' => $user['rol_nombre']
        ],
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    error_log("Error en session_heartbeat.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
