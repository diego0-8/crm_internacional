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
$archivoId = $_POST['archivo_id'];

// Obtener conexión a la base de datos
$db = getDB();

try {
    // Verificar que el archivo pertenece al asesor
    $stmt = $db->prepare("
        SELECT ta.*, t.id as ticket_id
        FROM ticket_archivos ta
        JOIN tiketera t ON ta.ticket_id = t.id
        WHERE ta.id = ? AND ta.asesor_cedula = ?
    ");
    $stmt->execute([$archivoId, $asesorCedula]);
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$archivo) {
        echo json_encode(['success' => false, 'message' => 'Archivo no encontrado o no autorizado']);
        exit;
    }

    // Eliminar archivo físico
    $filePath = '../' . $archivo['ruta_archivo'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Eliminar registro de la base de datos
    $stmt = $db->prepare("DELETE FROM ticket_archivos WHERE id = ?");
    $stmt->execute([$archivoId]);

    echo json_encode([
        'success' => true,
        'message' => 'Archivo eliminado exitosamente'
    ]);

} catch (Exception $e) {
    error_log("Error en eliminar_archivo_ticket.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
