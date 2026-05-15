<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$asesorCedula = $user['cedula'];

/**
 * @param PDO $db
 */
function actividad_tabla_existe(PDO $db, string $tableName): bool {
    static $cache = [];
    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }
    $stmt = $db->prepare('
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        LIMIT 1
    ');
    $stmt->execute([$tableName]);
    return $cache[$tableName] = (bool) $stmt->fetchColumn();
}

/**
 * @param PDO $db
 */
function actividad_columna_existe(PDO $db, string $tableName, string $columnName): bool {
    $key = $tableName . '.' . $columnName;
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = $db->prepare('
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        LIMIT 1
    ');
    $stmt->execute([$tableName, $columnName]);
    return $cache[$key] = (bool) $stmt->fetchColumn();
}

function actividad_formatear_tiempo(string $fechaMysql): string {
    $ts = strtotime($fechaMysql);
    if ($ts === false) {
        return $fechaMysql;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Hace un momento';
    }
    if ($diff < 3600) {
        return 'Hace ' . (int) floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return 'Hace ' . (int) floor($diff / 3600) . ' h';
    }
    if ($diff < 172800) {
        return 'Ayer';
    }
    return date('d/m/Y H:i', $ts);
}

try {
    $db = getDB();
    $items = [];

    if (actividad_tabla_existe($db, 'historial_llamadas')) {
        $stmt = $db->prepare('
            SELECT fecha_llamada, observacion, cliente_cedula
            FROM historial_llamadas
            WHERE asesor_cedula = ?
            ORDER BY fecha_llamada DESC
            LIMIT 8
        ');
        $stmt->execute([$asesorCedula]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $obs = (string) ($row['observacion'] ?? '');
            $snippet = mb_strlen($obs, 'UTF-8') > 120
                ? mb_substr($obs, 0, 120, 'UTF-8') . '…'
                : $obs;
            $items[] = [
                'ts' => strtotime((string) ($row['fecha_llamada'] ?? '')) ?: 0,
                'titulo' => 'Gestión / llamada',
                'descripcion' => 'Cliente ' . ($row['cliente_cedula'] ?? '') . ($snippet !== '' ? ': ' . $snippet : ''),
                'tiempo' => actividad_formatear_tiempo($row['fecha_llamada']),
                'icono' => 'phone',
            ];
        }
    }

    if (actividad_tabla_existe($db, 'tiketera')) {
        $stmt = $db->prepare('
            SELECT titulo, numero_ticket, estado, fecha_creacion, fecha_actualizacion, fecha_cierre
            FROM tiketera
            WHERE asesor_cedula = ?
            ORDER BY COALESCE(fecha_actualizacion, fecha_creacion) DESC
            LIMIT 8
        ');
        $stmt->execute([$asesorCedula]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ref = $row['fecha_cierre'] ?? $row['fecha_actualizacion'] ?? $row['fecha_creacion'];
            $items[] = [
                'ts' => strtotime((string) $ref) ?: 0,
                'titulo' => 'Ticket',
                'descripcion' => trim(($row['numero_ticket'] ? '#' . $row['numero_ticket'] . ' — ' : '') . ($row['titulo'] ?? ''))
                    . ' · Estado: ' . ($row['estado'] ?? ''),
                'tiempo' => actividad_formatear_tiempo((string) $ref),
                'icono' => 'ticket-alt',
            ];
        }
    }

    if (actividad_tabla_existe($db, 'titulares')
        && actividad_columna_existe($db, 'titulares', 'asesor_cedula')
        && actividad_columna_existe($db, 'titulares', 'actualizado_en')) {
        $stmt = $db->prepare('
            SELECT t.primer_nombre, t.apellido, t.actualizado_en,
                   (SELECT p2.numero_caso FROM propiedades p2 WHERE p2.id_cliente = t.id_cliente LIMIT 1) AS numero_caso
            FROM titulares t
            WHERE t.asesor_cedula = ?
            ORDER BY t.actualizado_en DESC
            LIMIT 5
        ');
        $stmt->execute([$asesorCedula]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nombre = trim(($row['primer_nombre'] ?? '') . ' ' . ($row['apellido'] ?? ''));
            $caso = (string) ($row['numero_caso'] ?? '');
            $items[] = [
                'ts' => strtotime((string) ($row['actualizado_en'] ?? '')) ?: 0,
                'titulo' => 'Caso reparto',
                'descripcion' => ($nombre !== '' ? $nombre : 'Titular') . ($caso !== '' ? ' · Caso ' . $caso : ''),
                'tiempo' => actividad_formatear_tiempo($row['actualizado_en']),
                'icono' => 'user',
            ];
        }
    }

    usort($items, static function ($a, $b) {
        return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0);
    });
    $items = array_slice($items, 0, 15);
    foreach ($items as &$it) {
        unset($it['ts']);
    }
    unset($it);

    echo json_encode([
        'success' => true,
        'data' => $items,
    ]);
} catch (Throwable $e) {
    error_log('actividad_asesor.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar actividad',
    ]);
}
