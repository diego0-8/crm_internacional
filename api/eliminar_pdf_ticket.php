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

$data = json_decode(file_get_contents('php://input'), true);
$ticketId = $data['ticket_id'];

// Obtener conexión a la base de datos
$db = getDB();

try {
    // Verificar que el ticket pertenece al asesor
    $stmt = $db->prepare("SELECT pdf_archivo FROM tiketera WHERE id = ? AND asesor_cedula = ?");
    $stmt->execute([$ticketId, $asesorCedula]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o no autorizado']);
        exit;
    }

    // Eliminar archivo físico si existe
    if ($ticket['pdf_archivo'] && file_exists('../' . $ticket['pdf_archivo'])) {
        unlink('../' . $ticket['pdf_archivo']);
    }

    // Actualizar base de datos
    $stmt = $db->prepare("UPDATE tiketera SET pdf_archivo = NULL WHERE id = ?");
    $stmt->execute([$ticketId]);

    echo json_encode([
        'success' => true,
        'message' => 'PDF eliminado exitosamente'
    ]);

} catch (Exception $e) {
    error_log("Error en eliminar_pdf_ticket.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
