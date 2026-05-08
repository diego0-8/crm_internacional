<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/TiketeraModel.php';

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
    echo json_encode(['success' => false, 'message' => 'ticket_id requerido']);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT
            t.*,
            tc.codigo AS categoria_codigo,
            tc.nombre AS categoria_nombre,
            c.nombre_completo as cliente_nombre,
            c.telefono as cliente_telefono,
            c.email as cliente_email,
            c.direccion as cliente_direccion,
            c.ciudad as cliente_ciudad,
            c.cedula as cliente_cedula,
            c.age as cliente_age,
            c.deceased as cliente_deceased,
            c.source as cliente_source,
            c.mailing_street as cliente_mailing_street,
            c.mailing_city as cliente_mailing_city,
            c.mailing_state as cliente_mailing_state,
            c.mailing_zip as cliente_mailing_zip
        FROM tiketera t
        JOIN clientes c ON t.cliente_cedula = c.cedula
        LEFT JOIN ticket_categorias tc ON t.categoria_id = tc.id
        WHERE t.id = ? AND t.asesor_cedula = ?
    ");
    $stmt->execute([$ticketId, $asesorCedula]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o no autorizado']);
        exit;
    }

    $estadoActual = (string) ($ticket['estado'] ?? '');
    $ticket['estado_label'] = TiketeraModel::ESTADO_LABELS[$estadoActual] ?? $estadoActual;
    $ticket['transiciones_permitidas'] = TiketeraModel::allowedTransitions()[$estadoActual] ?? [];

    // Estados disponibles UI: el actual + los permitidos. Resto van como deshabilitados.
    $estadosUI = [];
    foreach (TiketeraModel::ESTADOS as $k) {
        $estadosUI[] = [
            'key'      => $k,
            'label'    => TiketeraModel::ESTADO_LABELS[$k] ?? $k,
            'enabled'  => ($k === $estadoActual) || in_array($k, $ticket['transiciones_permitidas'], true),
        ];
    }
    $ticket['estados_disponibles'] = $estadosUI;

    $tiketera = new TiketeraModel();
    $hist = $tiketera->getHistorialEstado($ticketId);
    $ticket['historial_estado'] = $hist['historial'];
    $ticket['tiempo_total_segundos'] = $hist['total_segundos'];
    $ticket['tiempo_total_legible']  = TiketeraModel::humanizeSeconds($hist['total_segundos']);

    // estado_actual_desde = timestamp del último cambio.
    $estadoActualDesde = null;
    if (!empty($hist['historial'])) {
        $ultima = end($hist['historial']);
        $estadoActualDesde = $ultima['fecha_cambio'] ?? null;
    }
    $ticket['estado_actual_desde'] = $estadoActualDesde;

    $stmt = $db->prepare("
        SELECT
            hl.fecha_llamada,
            hl.duracion_minutos,
            hl.observacion,
            hl.proxima_accion,
            t.categoria as tipificacion_categoria,
            t.codigo as tipificacion_codigo
        FROM historial_llamadas hl
        LEFT JOIN tipificaciones_llamadas t ON hl.tipificacion_id = t.id
        WHERE hl.cliente_cedula = ?
        ORDER BY hl.fecha_llamada DESC
        LIMIT 10
    ");
    $stmt->execute([$ticket['cliente_cedula']]);
    $ticket['historial_cliente'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $ticket['archivos_pdf'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Predio asociado al ticket (importado desde CSV foreclosure). Tolerante a entornos
    // donde aún no se haya aplicado la migración: si la tabla no existe, se devuelve null.
    $ticket['predio'] = null;
    try {
        $stmt = $db->prepare("
            SELECT id,
                   case_number, parcel_number, type_of_foreclosure,
                   property_street, property_city, property_state, property_zip,
                   county, source AS predio_source,
                   valor_a_devolver, valor_vendido, valor_inicial_subasta, date_sold,
                   created_at
            FROM predios
            WHERE ticket_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$ticketId]);
        $predio = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($predio) {
            $ticket['predio'] = $predio;
        }
    } catch (PDOException $e) {
        // Tabla no existe (migración pendiente) u otro error de BD: no rompemos la respuesta.
        $ticket['predio'] = null;
    }

    $ticket['cliente_telefonos'] = [];
    $ticket['cliente_emails_list'] = [];
    $ticket['referencias_personales'] = [];

    try {
        $stmt = $db->prepare("
            SELECT numero, numero_normalizado, tipo, dnc_litigator, orden
            FROM cliente_telefonos
            WHERE cliente_cedula = ?
            ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([$ticket['cliente_cedula']]);
        $ticket['cliente_telefonos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT email, orden
            FROM cliente_emails
            WHERE cliente_cedula = ?
            ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([$ticket['cliente_cedula']]);
        $ticket['cliente_emails_list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT id, nombre, apellido, possible_type, age, orden
            FROM referencias_personales
            WHERE cliente_cedula = ?
            ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([$ticket['cliente_cedula']]);
        $refs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($refs)) {
            $ids = array_map('intval', array_column($refs, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $phonesByRef = [];
            $stmt = $db->prepare("
                SELECT referencia_id, numero, numero_normalizado, tipo, dnc_litigator, orden
                FROM referencia_telefonos
                WHERE referencia_id IN ($placeholders)
                ORDER BY referencia_id ASC, orden ASC, id ASC
            ");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $rid = (int) $row['referencia_id'];
                unset($row['referencia_id']);
                $phonesByRef[$rid][] = $row;
            }

            $emailsByRef = [];
            $stmt = $db->prepare("
                SELECT referencia_id, email, orden
                FROM referencia_emails
                WHERE referencia_id IN ($placeholders)
                ORDER BY referencia_id ASC, orden ASC, id ASC
            ");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $rid = (int) $row['referencia_id'];
                unset($row['referencia_id']);
                $emailsByRef[$rid][] = $row;
            }

            foreach ($refs as &$r) {
                $rid = (int) $r['id'];
                $r['telefonos'] = $phonesByRef[$rid] ?? [];
                $r['emails'] = $emailsByRef[$rid] ?? [];
            }
            unset($r);
            $ticket['referencias_personales'] = $refs;
        }
    } catch (PDOException $e) {
        $ticket['cliente_telefonos'] = [];
        $ticket['cliente_emails_list'] = [];
        $ticket['referencias_personales'] = [];
    }

    echo json_encode([
        'success' => true,
        'data' => $ticket,
    ]);

} catch (Exception $e) {
    error_log("Error en ticket_detalle_completo.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
