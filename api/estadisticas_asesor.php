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

try {
    $db = getDB();

    // Obtener estadísticas de clientes
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_clientes,
            SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END) as clientes_nuevos,
            SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as clientes_gestionados
        FROM clientes
        WHERE asesor_cedula = ?
    ");
    $stmt->execute([$asesorCedula]);
    $clienteStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener llamadas del día
    $hoy = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT COUNT(*) as llamadas_hoy
        FROM historial_llamadas
        WHERE asesor_cedula = ? AND DATE(fecha_llamada) = ?
    ");
    $stmt->execute([$asesorCedula, $hoy]);
    $llamadasStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener estadísticas de tickets
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_tickets,
            SUM(CASE WHEN estado = 'abierto' THEN 1 ELSE 0 END) as tickets_abiertos,
            SUM(CASE WHEN estado = 'procesando' THEN 1 ELSE 0 END) as tickets_procesando,
            SUM(CASE WHEN estado = 'terminado' THEN 1 ELSE 0 END) as tickets_terminados
        FROM tiketera
        WHERE asesor_cedula = ?
    ");
    $stmt->execute([$asesorCedula]);
    $ticketStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Combinar estadísticas
    $estadisticas = [
        'total_clientes' => (int)($clienteStats['total_clientes'] ?? 0),
        'clientes_nuevos' => (int)($clienteStats['clientes_nuevos'] ?? 0),
        'clientes_gestionados' => (int)($clienteStats['clientes_gestionados'] ?? 0),
        'llamadas_hoy' => (int)($llamadasStats['llamadas_hoy'] ?? 0),
        'total_tickets' => (int)($ticketStats['total_tickets'] ?? 0),
        'tickets_abiertos' => (int)($ticketStats['tickets_abiertos'] ?? 0),
        'tickets_procesando' => (int)($ticketStats['tickets_procesando'] ?? 0),
        'tickets_terminados' => (int)($ticketStats['tickets_terminados'] ?? 0)
    ];

    echo json_encode([
        'success' => true,
        'data' => $estadisticas
    ]);

} catch (Exception $e) {
    error_log("Error en estadisticas_asesor.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>