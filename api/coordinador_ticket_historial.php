<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/TiketeraModel.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$coordinadorCedula = (string) ($user['cedula'] ?? '');
$ticketId = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ticket_id requerido']);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT t.id
        FROM tiketera t
        INNER JOIN usuarios u ON u.cedula = t.asesor_cedula
        WHERE t.id = ? AND u.coordinador_cedula = ?
        LIMIT 1
    ");
    $stmt->execute([$ticketId, $coordinadorCedula]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ticket no pertenece a su equipo']);
        exit;
    }

    $tiketera = new TiketeraModel();
    $hist = $tiketera->getHistorialEstado($ticketId);

    $tieneColEstado = false;
    try {
        $chk = $db->query("SELECT estado_ticket FROM ticket_notas LIMIT 1");
        $tieneColEstado = true;
    } catch (PDOException $e) {}

    $tieneColTipo = false;
    try {
        $chk = $db->query("SELECT tipo_nota FROM ticket_notas LIMIT 1");
        $tieneColTipo = true;
    } catch (PDOException $e) {}

    $tieneColTelefono = false;
    try {
        $chk = $db->query("SELECT telefono_contacto FROM ticket_notas LIMIT 1");
        $tieneColTelefono = true;
    } catch (PDOException $e) {}

    $sql = "
        SELECT
            tn.id, tn.contenido, tn.proxima_accion, tn.fecha_proxima_accion, tn.fecha_creacion,
            u.nombre AS asesor_nombre, u.apellido AS asesor_apellido"
        . ($tieneColEstado ? ", tn.estado_ticket" : ", NULL AS estado_ticket")
        . ($tieneColTipo ? ", tn.tipo_nota" : ", 'asesor' AS tipo_nota")
        . ($tieneColTelefono ? ", tn.telefono_contacto" : ", NULL AS telefono_contacto")
        . "
        FROM ticket_notas tn
        JOIN usuarios u ON tn.asesor_cedula = u.cedula
        WHERE tn.ticket_id = ?
        ORDER BY tn.fecha_creacion ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$ticketId]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = TiketeraModel::ESTADO_LABELS;
    $legacy = TiketeraModel::ESTADO_LEGACY_LABELS;
    $notasPorEstado = [];

    foreach ($notas as &$n) {
        $n['asesor_nombre'] = trim($n['asesor_nombre'] . ' ' . $n['asesor_apellido']);
        unset($n['asesor_apellido']);
        $ek = trim((string) ($n['estado_ticket'] ?? ''));
        $n['estado_ticket'] = $ek;
        $n['estado_label'] = $ek !== '' ? ($labels[$ek] ?? $legacy[$ek] ?? $ek) : '';
        $key = $ek !== '' ? $ek : '_sin_estado';
        $notasPorEstado[$key][] = $n;
    }
    unset($n);

    echo json_encode([
        'success' => true,
        'historial_estado' => $hist['historial'],
        'tiempo_total_legible' => TiketeraModel::humanizeSeconds($hist['total_segundos']),
        'notas' => $notas,
        'notas_por_estado' => $notasPorEstado,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
