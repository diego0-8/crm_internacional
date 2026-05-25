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

/**
 * dias_transcurridos en CSV = mora al momento del registro. Se suman días calendario desde la
 * fecha de venta (propiedades.fecha_venta) si existe; si no, desde creado_en / actualizado_en.
 * «Hoy» en APP_TIMEZONE; +1 día inclusivo frente a medianoches. Tope 300. JD evita DST.
 */
function ticket_propiedad_ymd_desde_db(string $refStr): ?string {
    $refStr = trim($refStr);
    if ($refStr === '') {
        return null;
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $refStr, $m)) {
        return $m[1];
    }
    $ts = strtotime($refStr);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d', $ts);
}

function ticket_gregorian_jd(string $ymd): ?int {
    $p = array_map('intval', explode('-', $ymd));
    if (count($p) !== 3) {
        return null;
    }
    [$y, $m, $d] = $p;
    if ($y < 1 || $m < 1 || $m > 12 || $d < 1 || $d > 31) {
        return null;
    }
    return gregoriantojd($m, $d, $y);
}

function ticket_calcular_dias_mora_reparto(array $propRow): array {
    $limite = 300;
    $raw = $propRow['dias_transcurridos'] ?? null;
    $base = ($raw !== null && $raw !== '')
        ? max(0, (int) $raw)
        : 0;
    $fechaVenta = isset($propRow['fecha_venta']) ? trim((string) $propRow['fecha_venta']) : '';
    $anclaOrigen = 'creado_en';
    $refYmd = null;
    if ($fechaVenta !== '') {
        $refYmd = ticket_propiedad_ymd_desde_db($fechaVenta);
        if ($refYmd !== null) {
            $anclaOrigen = 'fecha_venta';
        }
    }
    if ($refYmd === null) {
        $refStr = (string) ($propRow['creado_en'] ?? $propRow['actualizado_en'] ?? date('Y-m-d H:i:s'));
        $refYmd = ticket_propiedad_ymd_desde_db($refStr);
        if ($refYmd === null) {
            $refYmd = date('Y-m-d');
        }
    }

    $tzName = (string) (defined('APP_TIMEZONE') ? constant('APP_TIMEZONE') : 'America/Bogota');
    try {
        $tz = new DateTimeZone($tzName);
    } catch (Exception $e) {
        $tz = new DateTimeZone('UTC');
    }
    $todayYmd = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

    $jRef = ticket_gregorian_jd($refYmd);
    $jToday = ticket_gregorian_jd($todayYmd);
    if ($jRef === null || $jToday === null) {
        $extra = 0;
    } else {
        // +1: la mora debe reflejar la vigencia hasta el día calendario actual, no hasta medianoche de hoy.
        $extra = $jToday - $jRef + 1;
        if ($extra < 0) {
            $extra = 0;
        }
    }

    $total = min($limite, $base + $extra);

    return [
        'dias_transcurridos_origen_csv' => $raw,
        'dias_mora_activos' => $total,
        'dias_mora_limite' => $limite,
        'dias_incrementados_desde_registro' => $extra,
        'fecha_referencia_mora' => $refYmd,
        'mora_fecha_ancla_origen' => $anclaOrigen,
        'mora_zona_horaria' => $tzName,
        'en_limite' => $total >= $limite,
    ];
}

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
    $ticket['estado_label'] = TiketeraModel::estadoLabelFor($estadoActual);
    $ticket['transiciones_permitidas'] = TiketeraModel::allowedTransitions()[$estadoActual] ?? [];

    // Estados disponibles UI: el actual + los permitidos. Resto van como deshabilitados.
    $estadosUI = [];
    foreach (TiketeraModel::ESTADOS as $k) {
        $estadosUI[] = [
            'key'      => $k,
            'label'    => TiketeraModel::estadoLabelFor($k),
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
                   valor_a_devolver, valor_vendido, valor_inicial_subasta, date_sold, monetizacion,
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

    // Titular / propiedad reparto (tablas titulares + propiedades) vinculado al ticket
    $ticket['titular_reparto'] = null;
    $ticket['propiedad_reparto'] = null;

    $titularIdResuelto = null;
    $cedulaCliente = (string) ($ticket['cliente_cedula'] ?? '');
    if (preg_match('/^TIT-(\d+)$/i', $cedulaCliente, $mTit)) {
        $titularIdResuelto = (int) $mTit[1];
    } elseif (!empty($ticket['numero_ticket'])) {
        try {
            $stmt = $db->prepare('
                SELECT t.id_cliente
                FROM titulares t
                INNER JOIN propiedades p ON p.id_cliente = t.id_cliente
                WHERE p.numero_caso = ? AND t.asesor_cedula = ?
                LIMIT 1
            ');
            $stmt->execute([(string) $ticket['numero_ticket'], $asesorCedula]);
            $rowT = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rowT) {
                $titularIdResuelto = (int) $rowT['id_cliente'];
            }
        } catch (PDOException $e) {
            // esquema sin titulares/propiedades
        }
    }

    if ($titularIdResuelto > 0) {
        try {
            $stmt = $db->prepare('
                SELECT id_cliente, primer_nombre, apellido, prioridad,
                       mailing_calle, mailing_ciudad, mailing_estado, mailing_codigo_postal
                FROM titulares
                WHERE id_cliente = ? AND asesor_cedula = ?
                LIMIT 1
            ');
            $stmt->execute([$titularIdResuelto, $asesorCedula]);
            $titRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($titRow) {
                $ticket['titular_reparto'] = [
                    'id_cliente' => (int) $titRow['id_cliente'],
                    'primer_nombre' => $titRow['primer_nombre'],
                    'apellido' => $titRow['apellido'],
                    'prioridad' => $titRow['prioridad'],
                    'mailing_calle' => $titRow['mailing_calle'],
                    'mailing_ciudad' => $titRow['mailing_ciudad'],
                    'mailing_estado' => $titRow['mailing_estado'],
                    'mailing_codigo_postal' => $titRow['mailing_codigo_postal'],
                ];
            }

            $stmt = $db->prepare('
                SELECT id_propiedad, id_cliente, dias_transcurridos, creado_en, actualizado_en,
                       fecha_venta,
                       numero_caso, numero_parcela, tipo_foreclosure,
                       propiedad_calle, propiedad_ciudad, propiedad_estado, propiedad_codigo_postal,
                       condado, fuente, monetizacion
                FROM propiedades
                WHERE id_cliente = ?
                LIMIT 1
            ');
            $stmt->execute([$titularIdResuelto]);
            $propRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($propRow) {
                $mora = ticket_calcular_dias_mora_reparto($propRow);
                $ticket['propiedad_reparto'] = array_merge(
                    [
                        'numero_caso' => $propRow['numero_caso'],
                        'numero_parcela' => $propRow['numero_parcela'],
                        'tipo_foreclosure' => $propRow['tipo_foreclosure'],
                        'propiedad_calle' => $propRow['propiedad_calle'],
                        'propiedad_ciudad' => $propRow['propiedad_ciudad'],
                        'propiedad_estado' => $propRow['propiedad_estado'],
                        'propiedad_codigo_postal' => $propRow['propiedad_codigo_postal'],
                        'condado' => $propRow['condado'],
                        'fuente' => $propRow['fuente'],
                        'monetizacion' => $propRow['monetizacion'] ?? null,
                    ],
                    $mora
                );
            }
        } catch (PDOException $e) {
            // sin tablas reparto
        }
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
