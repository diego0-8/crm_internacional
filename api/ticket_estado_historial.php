<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/TiketeraModel.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$ticketId = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;
if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ticket_id requerido']);
    exit;
}

try {
    $db = getDB();

    // Validar pertenencia / permiso de lectura.
    $rol = $user['rol'] ?? '';
    if ($rol === 'asesor') {
        $stmt = $db->prepare('SELECT id FROM tiketera WHERE id = ? AND asesor_cedula = ?');
        $stmt->execute([$ticketId, $user['cedula']]);
    } elseif ($rol === 'cliente') {
        $stmt = $db->prepare('SELECT id FROM tiketera WHERE id = ? AND cliente_cedula = ?');
        $stmt->execute([$ticketId, $user['cedula']]);
    } else {
        // coordinador/admin: acceso amplio.
        $stmt = $db->prepare('SELECT id FROM tiketera WHERE id = ?');
        $stmt->execute([$ticketId]);
    }
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o no autorizado']);
        exit;
    }

    $tiketera = new TiketeraModel();
    $hist = $tiketera->getHistorialEstado($ticketId);

    echo json_encode([
        'success' => true,
        'data' => [
            'historial' => $hist['historial'],
            'total_segundos' => $hist['total_segundos'],
            'total_legible' => TiketeraModel::humanizeSeconds($hist['total_segundos']),
            'estados' => array_map(function ($k) {
                return [
                    'key'   => $k,
                    'label' => TiketeraModel::ESTADO_LABELS[$k] ?? $k,
                ];
            }, TiketeraModel::ESTADOS),
        ],
    ]);
} catch (Exception $e) {
    error_log('ticket_estado_historial.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
