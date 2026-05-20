<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/TitularModel.php';
require_once '../model/TiketeraModel.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$asesorCedula = $user['cedula'];

/**
 * Comprueba si existe la tabla en la BD actual (evita 500 si el dump solo trae reparto/titulares).
 */
function estadisticas_asesor_tabla_existe(PDO $db, string $tableName): bool {
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

try {
    $db = getDB();
    $titularModel = new TitularModel();
    $titStats = $titularModel->estadisticasPorAsesor($asesorCedula);

    $tbClientes = estadisticas_asesor_tabla_existe($db, 'clientes');
    $tbHistorial = estadisticas_asesor_tabla_existe($db, 'historial_llamadas');
    $tbTiketera = estadisticas_asesor_tabla_existe($db, 'tiketera');

    $clienteStats = [
        'total_clientes_crm' => 0,
        'clientes_nuevos_crm' => 0,
        'clientes_gestionados_crm' => 0,
    ];
    if ($tbClientes) {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) AS total_clientes_crm,
                SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END) AS clientes_nuevos_crm,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS clientes_gestionados_crm
            FROM clientes
            WHERE asesor_cedula = ?
        ");
        $stmt->execute([$asesorCedula]);
        $clienteStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $clienteStats;
    }

    $llamadasHoy = 0;
    $llamadasMes = 0;
    $clientesAtendidosMes = 0;
    if ($tbHistorial) {
        $hoy = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT COUNT(*) AS llamadas_hoy
            FROM historial_llamadas
            WHERE asesor_cedula = ? AND DATE(fecha_llamada) = ?
        ");
        $stmt->execute([$asesorCedula, $hoy]);
        $llamadasHoy = (int) ($stmt->fetchColumn() ?: 0);

        $stmt = $db->prepare("
            SELECT COUNT(*) AS llamadas_mes
            FROM historial_llamadas
            WHERE asesor_cedula = ? AND fecha_llamada >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $stmt->execute([$asesorCedula]);
        $llamadasMes = (int) ($stmt->fetchColumn() ?: 0);

        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT cliente_cedula) AS clientes_distintos_llamadas_mes
            FROM historial_llamadas
            WHERE asesor_cedula = ? AND fecha_llamada >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $stmt->execute([$asesorCedula]);
        $clientesAtendidosMes = (int) ($stmt->fetchColumn() ?: 0);
    }

    $ticketStats = [
        'total_tickets' => 0,
        'tickets_abiertos' => 0,
        'tickets_cerrados' => 0,
    ];
    $ticketsResueltosMes = 0;
    $ticketsCreadosMes = 0;
    $tiempoPromedioResolucion = 0;
    if ($tbTiketera) {
        $tiketeraModel = new TiketeraModel();
        $filtroCsv = $tiketeraModel->filtroSqlTicketsSinCsvInhabilitado('t');

        $stmt = $db->prepare("
            SELECT
                COUNT(*) AS total_tickets,
                SUM(CASE WHEN estado NOT IN ('desembolso', 'cierre') THEN 1 ELSE 0 END) AS tickets_abiertos,
                SUM(CASE WHEN estado IN ('desembolso', 'cierre') THEN 1 ELSE 0 END) AS tickets_cerrados
            FROM tiketera t
            WHERE t.asesor_cedula = ?
            $filtroCsv
        ");
        $stmt->execute([$asesorCedula]);
        $ticketStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $ticketStats;

        $stmt = $db->prepare("
            SELECT COUNT(*) AS tickets_resueltos_mes
            FROM tiketera t
            WHERE t.asesor_cedula = ?
              AND estado IN ('desembolso', 'cierre')
              AND fecha_cierre IS NOT NULL
              AND fecha_cierre >= DATE_FORMAT(NOW(), '%Y-%m-01')
            $filtroCsv
        ");
        $stmt->execute([$asesorCedula]);
        $ticketsResueltosMes = (int) ($stmt->fetchColumn() ?: 0);

        $stmt = $db->prepare("
            SELECT COUNT(*) AS tickets_creados_mes
            FROM tiketera t
            WHERE t.asesor_cedula = ?
              AND fecha_creacion >= DATE_FORMAT(NOW(), '%Y-%m-01')
            $filtroCsv
        ");
        $stmt->execute([$asesorCedula]);
        $ticketsCreadosMes = (int) ($stmt->fetchColumn() ?: 0);

        $stmt = $db->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre)) AS tiempo_promedio_horas
            FROM tiketera t
            WHERE t.asesor_cedula = ?
              AND estado IN ('desembolso', 'cierre')
              AND fecha_cierre IS NOT NULL
              AND fecha_cierre >= DATE_FORMAT(NOW(), '%Y-%m-01')
            $filtroCsv
        ");
        $stmt->execute([$asesorCedula]);
        $tiempoRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $tiempoPromedioResolucion = $tiempoRow && $tiempoRow['tiempo_promedio_horas'] !== null
            ? round((float) $tiempoRow['tiempo_promedio_horas'], 1)
            : 0;
    }

    $totalTit = (int) ($titStats['total_titulares'] ?? 0);
    $conContacto = (int) ($titStats['con_contacto'] ?? 0);
    $tasaGestionTit = $totalTit > 0 ? round(100 * $conContacto / $totalTit, 1) : 0;

    $totalTickets = (int) ($ticketStats['total_tickets'] ?? 0);
    $ticketsCerrados = (int) ($ticketStats['tickets_cerrados'] ?? 0);
    $efectividad = $totalTickets > 0 ? round(100 * $ticketsCerrados / $totalTickets, 1) : 0;
    if ($ticketsCreadosMes > 0 && $ticketsResueltosMes > 0) {
        $efectividad = min(100, round(100 * $ticketsResueltosMes / max(1, $ticketsCreadosMes), 1));
    }

    $estadisticas = [
        'total_casos_reparto' => $totalTit,
        'casos_con_prioridad' => (int) ($titStats['con_prioridad'] ?? 0),
        'casos_con_contacto' => $conContacto,
        'titulares_actualizados_mes' => (int) ($titStats['titulares_mes'] ?? 0),

        'total_clientes_crm' => (int) ($clienteStats['total_clientes_crm'] ?? 0),
        'clientes_nuevos_crm' => (int) ($clienteStats['clientes_nuevos_crm'] ?? 0),
        'clientes_gestionados_crm' => (int) ($clienteStats['clientes_gestionados_crm'] ?? 0),

        'llamadas_hoy' => $llamadasHoy,
        'llamadas_mes' => $llamadasMes,

        'total_tickets' => $totalTickets,
        'tickets_abiertos' => (int) ($ticketStats['tickets_abiertos'] ?? 0),
        'tickets_procesando' => 0,
        'tickets_terminados' => $ticketsCerrados,
        'tickets_resueltos_mes' => $ticketsResueltosMes,
        'tickets_creados_mes' => $ticketsCreadosMes,
        'tiempo_promedio_resolucion' => $tiempoPromedioResolucion,

        'total_clientes' => $totalTit,
        'clientes_nuevos' => (int) ($titStats['con_prioridad'] ?? 0),
        'clientes_gestionados' => $conContacto,
        'clientes_nuevos_mes' => (int) ($titStats['titulares_mes'] ?? 0),
        'clientes_atendidos_mes' => $clientesAtendidosMes,

        'efectividad_general' => $efectividad,
        'tasa_gestion' => $tasaGestionTit,
        'tasa_conversion' => 0,
        'satisfaccion_cliente' => 0,
        'emails_enviados_mes' => 0,
        'reuniones_mes' => 0,
        'meta_cumplida_mes' => min(10, $ticketsResueltosMes),
        'meta_total_mes' => 10,
    ];

    echo json_encode([
        'success' => true,
        'data' => $estadisticas,
    ]);
} catch (Exception $e) {
    error_log('Error en estadisticas_asesor.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
