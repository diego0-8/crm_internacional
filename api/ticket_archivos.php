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
$ticketId = $_GET['ticket_id'];

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

    // Obtener archivos PDF del ticket
    $stmt = $db->prepare("
        SELECT
            id,
            nombre_archivo,
            ruta_archivo,
            fecha_subida
        FROM ticket_archivos
        WHERE ticket_id = ?
        ORDER BY fecha_subida DESC
    ");
    $stmt->execute([$ticketId]);
    $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $archivos
    ]);

} catch (Exception $e) {
    error_log("Error en ticket_archivos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
