<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/TiketeraModel.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$asesorCedula = (string) ($user['cedula'] ?? '');

$db = getDB();

function tablaExisteApi(PDO $db, string $nombre): bool
{
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$nombre]);
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        return false;
    }
}

try {
    if (!tablaExisteApi($db, 'ticket_notas') || !tablaExisteApi($db, 'tiketera')) {
        echo json_encode(['success' => true, 'data' => [], 'total' => 0]);
        exit;
    }

    $extrasPredio = '';
    if (tablaExisteApi($db, 'predios')) {
        $extrasPredio = ",
            (SELECT pd.case_number FROM predios pd WHERE pd.ticket_id = t.id ORDER BY pd.id DESC LIMIT 1) AS case_number,
            (SELECT pd.parcel_number FROM predios pd WHERE pd.ticket_id = t.id ORDER BY pd.id DESC LIMIT 1) AS parcel_number";
    } else {
        $extrasPredio = ",
            NULL AS case_number,
            NULL AS parcel_number";
    }

    $sql = "
        SELECT
            tn.id AS nota_id,
            tn.ticket_id,
            tn.proxima_accion,
            tn.fecha_proxima_accion,
            t.numero_ticket,
            t.titulo,
            t.estado,
            t.cliente_cedula,
            c.nombre_completo AS cliente_nombre,
            c.telefono AS cliente_telefono
            {$extrasPredio}
        FROM ticket_notas tn
        INNER JOIN (
            SELECT ticket_id, MAX(id) AS max_id
            FROM ticket_notas
            WHERE asesor_cedula = ?
              AND fecha_proxima_accion IS NOT NULL
            GROUP BY ticket_id
        ) ult ON ult.max_id = tn.id
        JOIN tiketera t ON t.id = tn.ticket_id AND t.asesor_cedula = ?
        JOIN clientes c ON c.cedula = t.cliente_cedula
        WHERE tn.fecha_proxima_accion < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
          AND t.estado NOT IN ('desembolso', 'cierre')
        ORDER BY tn.fecha_proxima_accion ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$asesorCedula, $asesorCedula]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hoy = (new DateTime('today'))->format('Y-m-d');
    $items = [];

    foreach ($rows as $row) {
        $fechaRaw = (string) ($row['fecha_proxima_accion'] ?? '');
        $fechaDia = $fechaRaw !== '' ? substr($fechaRaw, 0, 10) : '';
        $urgencia = ($fechaDia !== '' && $fechaDia < $hoy) ? 'vencida' : 'hoy';

        $estKey = trim((string) ($row['estado'] ?? ''));
        $items[] = [
            'nota_id' => (int) $row['nota_id'],
            'ticket_id' => (int) $row['ticket_id'],
            'proxima_accion' => $row['proxima_accion'],
            'fecha_proxima_accion' => $fechaRaw,
            'numero_ticket' => $row['numero_ticket'],
            'titulo' => $row['titulo'],
            'estado' => $estKey,
            'estado_label' => $estKey !== ''
                ? (TiketeraModel::ESTADO_LABELS[$estKey] ?? $estKey)
                : '',
            'cliente_cedula' => $row['cliente_cedula'],
            'cliente_nombre' => $row['cliente_nombre'],
            'cliente_telefono' => $row['cliente_telefono'],
            'case_number' => $row['case_number'] ?? null,
            'parcel_number' => $row['parcel_number'] ?? null,
            'urgencia' => $urgencia,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $items,
        'total' => count($items),
    ]);
} catch (Exception $e) {
    error_log('Error en llamadas_pendientes_asesor.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
    ]);
}
