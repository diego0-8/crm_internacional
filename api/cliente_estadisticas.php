<?php
header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('cliente')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();

try {
    $db = getDB();

    // Obtener estadísticas de tickets del cliente
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'comunicacion'     THEN 1 ELSE 0 END) as comunicacion,
            SUM(CASE WHEN estado = 'validacion'       THEN 1 ELSE 0 END) as validacion,
            SUM(CASE WHEN estado = 'proceso_judicial' THEN 1 ELSE 0 END) as proceso_judicial,
            SUM(CASE WHEN estado = 'remate'           THEN 1 ELSE 0 END) as remate,
            SUM(CASE WHEN estado = 'recuperacion'     THEN 1 ELSE 0 END) as recuperacion,
            SUM(CASE WHEN estado = 'cierre'           THEN 1 ELSE 0 END) as cierre,
            SUM(CASE WHEN estado <> 'cierre'          THEN 1 ELSE 0 END) as abiertos
        FROM tiketera
        WHERE cliente_cedula = ?
    ");
    $stmt->execute([$user['cedula']]);
    $estadisticas = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => [
            'total'            => (int) $estadisticas['total'],
            'abiertos'         => (int) $estadisticas['abiertos'],
            'comunicacion'     => (int) $estadisticas['comunicacion'],
            'validacion'       => (int) $estadisticas['validacion'],
            'proceso_judicial' => (int) $estadisticas['proceso_judicial'],
            'remate'           => (int) $estadisticas['remate'],
            'recuperacion'     => (int) $estadisticas['recuperacion'],
            'cierre'           => (int) $estadisticas['cierre'],
            // Compatibilidad con cliente_dashboard.php anterior:
            'proceso'          => (int) $estadisticas['validacion'] + (int) $estadisticas['proceso_judicial'] + (int) $estadisticas['remate'] + (int) $estadisticas['recuperacion'],
            'resueltos'        => (int) $estadisticas['cierre'],
        ]
    ]);

} catch (Exception $e) {
    error_log('cliente_estadisticas.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>