<?php
header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$asesorCedula = $user['cedula'];
$ticketId = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;
if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de ticket requerido']);
    exit;
}

// Obtener conexión a la base de datos
$db = getDB();

try {
    // Verificar que el ticket pertenece al asesor
    $stmt = $db->prepare("SELECT id FROM tiketera WHERE id = ? AND asesor_cedula = ?");
    $stmt->execute([$ticketId, $asesorCedula]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o no autorizado']);
        exit;
    }

    // Obtener notas del ticket
    $stmt = $db->prepare("
        SELECT
            tn.id,
            tn.contenido,
            tn.proxima_accion,
            tn.fecha_proxima_accion,
            tn.fecha_creacion,
            u.nombre as asesor_nombre,
            u.apellido as asesor_apellido
        FROM ticket_notas tn
        JOIN usuarios u ON tn.asesor_cedula = u.cedula
        WHERE tn.ticket_id = ?
        ORDER BY tn.fecha_creacion DESC
    ");
    $stmt->execute([$ticketId]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos
    foreach ($notas as &$nota) {
        $nota['asesor_nombre'] = $nota['asesor_nombre'] . ' ' . $nota['asesor_apellido'];
    }
    unset($nota);

    echo json_encode([
        'success' => true,
        'data' => $notas
    ]);

} catch (Exception $e) {
    error_log("Error en ticket_notas.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
