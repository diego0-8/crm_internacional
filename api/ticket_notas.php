<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/TiketeraModel.php';

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

    $tieneColEstado = false;
    try {
        $chk = $db->query("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ticket_notas'
              AND COLUMN_NAME = 'estado_ticket'
        ");
        $tieneColEstado = ((int) $chk->fetchColumn()) > 0;
    } catch (Exception $e) {
        $tieneColEstado = false;
    }

    $tieneColTipo = false;
    try {
        $chkT = $db->query("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ticket_notas'
              AND COLUMN_NAME = 'tipo_nota'
        ");
        $tieneColTipo = ((int) $chkT->fetchColumn()) > 0;
    } catch (Exception $e) {
        $tieneColTipo = false;
    }

    $sql = "
        SELECT
            tn.id,
            tn.contenido,
            tn.proxima_accion,
            tn.fecha_proxima_accion,
            tn.fecha_creacion,
            u.nombre AS asesor_nombre,
            u.apellido AS asesor_apellido"
        . ($tieneColEstado ? ", tn.estado_ticket" : ", NULL AS estado_ticket")
        . ($tieneColTipo ? ", tn.tipo_nota" : ", 'asesor' AS tipo_nota")
        . "
        FROM ticket_notas tn
        JOIN usuarios u ON tn.asesor_cedula = u.cedula
        WHERE tn.ticket_id = ?
        ORDER BY tn.fecha_creacion DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$ticketId]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notas as &$nota) {
        $nota['asesor_nombre'] = trim($nota['asesor_nombre'] . ' ' . $nota['asesor_apellido']);
        $estKey = trim((string) ($nota['estado_ticket'] ?? ''));
        $nota['estado_ticket'] = $estKey;
        $nota['estado_label'] = $estKey !== ''
            ? (TiketeraModel::ESTADO_LABELS[$estKey] ?? $estKey)
            : '';
        $nota['tipo_nota'] = trim((string) ($nota['tipo_nota'] ?? 'asesor'));
        if ($nota['tipo_nota'] === '') {
            $nota['tipo_nota'] = 'asesor';
        }
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
