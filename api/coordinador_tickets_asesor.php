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
$asesorCedula = trim($_GET['asesor_cedula'] ?? '');

if ($asesorCedula === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'asesor_cedula requerido']);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT u.cedula FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE r.nombre = 'asesor' AND u.cedula = ? AND u.coordinador_cedula = ?
        LIMIT 1
    ");
    $stmt->execute([$asesorCedula, $coordinadorCedula]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'El asesor no pertenece a su equipo']);
        exit;
    }

    $hasPropiedades = false;
    try {
        $db->query("SELECT 1 FROM propiedades LIMIT 1");
        $hasPropiedades = true;
    } catch (PDOException $e) {}

    $hasPredios = false;
    try {
        $db->query("SELECT 1 FROM predios LIMIT 1");
        $hasPredios = true;
    } catch (PDOException $e) {}

    $extraSelect = '';
    $extraJoin = '';

    if ($hasPropiedades) {
        $extraSelect .= ", p.numero_caso AS case_number, p.numero_parcela AS parcel_number";
        $extraJoin .= "
            LEFT JOIN titulares tit ON tit.id_cliente = CAST(REPLACE(t.cliente_cedula, 'TIT-', '') AS UNSIGNED)
            LEFT JOIN propiedades p ON p.id_cliente = tit.id_cliente
        ";
    } elseif ($hasPredios) {
        $extraSelect .= ", pr.case_number, pr.parcel_number";
        $extraJoin .= " LEFT JOIN predios pr ON pr.ticket_id = t.id";
    } else {
        $extraSelect .= ", NULL AS case_number, NULL AS parcel_number";
    }

    $sql = "
        SELECT
            t.id,
            t.numero_ticket,
            t.estado,
            t.titulo,
            t.fecha_creacion,
            t.fecha_actualizacion,
            (SELECT COUNT(*) FROM ticket_notas tn WHERE tn.ticket_id = t.id) AS total_gestiones
            {$extraSelect}
        FROM tiketera t
        {$extraJoin}
        WHERE t.asesor_cedula = ?
        ORDER BY t.fecha_actualizacion DESC, t.id DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$asesorCedula]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = TiketeraModel::ESTADO_LABELS;
    $legacy = TiketeraModel::ESTADO_LEGACY_LABELS;
    foreach ($tickets as &$tk) {
        $ek = trim((string) ($tk['estado'] ?? ''));
        $tk['estado_label'] = $labels[$ek] ?? $legacy[$ek] ?? $ek;
    }
    unset($tk);

    echo json_encode(['success' => true, 'data' => $tickets]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
