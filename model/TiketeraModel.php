<?php
require_once __DIR__ . '/../config.php';

class TiketeraModel {
    private $db;

    /**
     * Lista canónica de los 6 estados del workflow profesional.
     */
    public const ESTADOS = [
        'comunicacion',
        'validacion',
        'proceso_judicial',
        'remate',
        'recuperacion',
        'cierre',
    ];

    /**
     * Etiquetas legibles para UI (pueden usarse como label de badges).
     */
    public const ESTADO_LABELS = [
        'comunicacion'     => 'Comunicación',
        'validacion'       => 'Validación',
        'proceso_judicial' => 'Proceso judicial',
        'remate'           => 'Remate',
        'recuperacion'     => 'Recuperación',
        'cierre'           => 'Cierre',
    ];

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Mapa central de transiciones permitidas (única fuente de verdad).
     * El estado `cierre` es terminal (no tiene transiciones de salida).
     *
     * @return array<string, string[]>
     */
    public static function allowedTransitions() {
        return [
            'comunicacion'     => ['validacion'],
            'validacion'       => ['comunicacion', 'proceso_judicial'],
            'proceso_judicial' => ['validacion', 'remate'],
            'remate'           => ['proceso_judicial', 'recuperacion'],
            'recuperacion'     => ['cierre'],
            'cierre'           => [],
        ];
    }

    /**
     * Devuelve true si la transición $from -> $to es válida.
     */
    public static function isValidTransition($from, $to) {
        $from = (string) $from;
        $to   = (string) $to;
        if (!in_array($to, self::ESTADOS, true)) {
            return false;
        }
        if ($from === $to) {
            return true; // no-op (no se registra historial pero tampoco rompe)
        }
        $map = self::allowedTransitions();
        if (!isset($map[$from])) {
            return false;
        }
        return in_array($to, $map[$from], true);
    }

    /**
     * Asigna número legible único TK-AAAA-NNNNNN (usa id del ticket).
     */
    public function assignNumeroTicket($ticketId) {
        $ticketId = (int) $ticketId;
        $stmt = $this->db->prepare("
            UPDATE tiketera SET numero_ticket = CONCAT('TK-', YEAR(CURDATE()), '-', LPAD(?, 6, '0'))
            WHERE id = ?
        ");
        $stmt->execute([$ticketId, $ticketId]);
    }

    /**
     * Inserta una fila en el historial de estado.
     */
    private function insertHistorialEstado($ticketId, $estadoAnterior, $estadoNuevo, $asesorCedula = null, $observacion = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ticket_estado_historial
                    (ticket_id, estado_anterior, estado_nuevo, asesor_cedula, observacion)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int) $ticketId,
                $estadoAnterior,
                $estadoNuevo,
                $asesorCedula,
                $observacion,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('TiketeraModel insertHistorialEstado error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Crear nuevo ticket. Estado inicial siempre `comunicacion`.
     * Se registra fila inicial en ticket_estado_historial (estado_anterior=NULL).
     */
    public function createTicket($data) {
        $this->db->beginTransaction();
        try {
            $estadoInicial = 'comunicacion';
            if (!empty($data['estado']) && in_array($data['estado'], self::ESTADOS, true)) {
                $estadoInicial = $data['estado'];
            }

            // titulo conserva semántica de "asunto" (lo que ve el cliente).
            // El identificador profesional es numero_ticket.
            $stmt = $this->db->prepare("
                INSERT INTO tiketera
                    (cliente_cedula, asesor_cedula, titulo, descripcion, estado,
                     observaciones, pdf_archivo, categoria_id, origen, import_batch_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['cliente_cedula'],
                $data['asesor_cedula'],
                $data['titulo'] ?? '',
                $data['descripcion'] ?? null,
                $estadoInicial,
                $data['observaciones'] ?? null,
                $data['pdf_archivo'] ?? null,
                $data['categoria_id'] ?? null,
                $data['origen'] ?? 'asesor',
                $data['import_batch_id'] ?? null,
            ]);

            $ticketId = (int) $this->db->lastInsertId();
            $this->assignNumeroTicket($ticketId);

            $this->insertHistorialEstado(
                $ticketId,
                null,
                $estadoInicial,
                $data['asesor_cedula'] ?? null,
                'Creación del ticket'
            );

            $this->db->commit();
            logActivity('ticket_created', "Ticket #{$ticketId} creado para cliente {$data['cliente_cedula']}");
            return $ticketId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener tickets por asesor (incluye categoría si existe).
     */
    public function getTicketsByAsesor($asesorCedula, $estado = null, $clienteCedula = null) {
        try {
            $sql = "
                SELECT t.*,
                       tc.codigo AS categoria_codigo,
                       tc.nombre AS categoria_nombre,
                       c.nombre_completo as cliente_nombre,
                       c.telefono as cliente_telefono
                FROM tiketera t
                JOIN clientes c ON t.cliente_cedula = c.cedula
                LEFT JOIN ticket_categorias tc ON t.categoria_id = tc.id
                WHERE t.asesor_cedula = ?
            ";

            $params = [$asesorCedula];

            if ($estado) {
                $sql .= " AND t.estado = ?";
                $params[] = $estado;
            }

            if ($clienteCedula) {
                $sql .= " AND t.cliente_cedula = ?";
                $params[] = $clienteCedula;
            }

            $sql .= " ORDER BY t.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tickets: " . $e->getMessage());
        }
    }

    /**
     * Obtener ticket por ID
     */
    public function getTicketById($ticketId, $asesorCedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       tc.codigo AS categoria_codigo,
                       tc.nombre AS categoria_nombre,
                       c.nombre_completo as cliente_nombre,
                       c.telefono as cliente_telefono,
                       c.email as cliente_email
                FROM tiketera t
                JOIN clientes c ON t.cliente_cedula = c.cedula
                LEFT JOIN ticket_categorias tc ON t.categoria_id = tc.id
                WHERE t.id = ? AND t.asesor_cedula = ?
            ");
            $stmt->execute([$ticketId, $asesorCedula]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener ticket: " . $e->getMessage());
        }
    }

    /**
     * Actualizar estado del ticket validando la transición y registrando historial.
     * Lanza Exception con código 422 si la transición no es válida.
     */
    public function updateTicketEstado($ticketId, $nuevoEstado, $asesorCedula, $observaciones = null) {
        $ticketId = (int) $ticketId;
        $nuevoEstado = (string) $nuevoEstado;

        if (!in_array($nuevoEstado, self::ESTADOS, true)) {
            throw new Exception("Estado no válido: {$nuevoEstado}", 422);
        }

        $this->db->beginTransaction();
        try {
            // Cargar estado actual con lock para evitar carreras.
            $sel = $this->db->prepare("SELECT estado FROM tiketera WHERE id = ? AND asesor_cedula = ? FOR UPDATE");
            $sel->execute([$ticketId, $asesorCedula]);
            $row = $sel->fetch();
            if (!$row) {
                $this->db->rollBack();
                throw new Exception('Ticket no encontrado o no pertenece al asesor', 404);
            }
            $estadoActual = (string) $row['estado'];

            if ($estadoActual === $nuevoEstado) {
                // No hay cambio real; permitimos actualizar observaciones si vinieron.
                if ($observaciones !== null) {
                    $upd = $this->db->prepare("UPDATE tiketera SET observaciones = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
                    $upd->execute([$observaciones, $ticketId]);
                }
                $this->db->commit();
                return true;
            }

            if (!self::isValidTransition($estadoActual, $nuevoEstado)) {
                $this->db->rollBack();
                throw new Exception(
                    "Transición no permitida: {$estadoActual} → {$nuevoEstado}",
                    422
                );
            }

            $fechaCierre = ($nuevoEstado === 'cierre') ? date('Y-m-d H:i:s') : null;

            $upd = $this->db->prepare("
                UPDATE tiketera
                SET estado = ?,
                    fecha_cierre = COALESCE(?, fecha_cierre),
                    observaciones = COALESCE(?, observaciones),
                    fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = ? AND asesor_cedula = ?
            ");
            $upd->execute([
                $nuevoEstado,
                $fechaCierre,
                $observaciones,
                $ticketId,
                $asesorCedula,
            ]);

            $this->insertHistorialEstado(
                $ticketId,
                $estadoActual,
                $nuevoEstado,
                $asesorCedula,
                $observaciones
            );

            $this->db->commit();
            logActivity('ticket_updated', "Ticket {$ticketId}: {$estadoActual} → {$nuevoEstado}");
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Devuelve el historial de estados de un ticket con duración por etapa.
     * La última fila (estado actual abierto) calcula su duración contra NOW().
     *
     * @return array{historial: array<int, array<string, mixed>>, total_segundos: int}
     */
    public function getHistorialEstado($ticketId) {
        $ticketId = (int) $ticketId;
        $stmt = $this->db->prepare("
            SELECT h.id,
                   h.ticket_id,
                   h.estado_anterior,
                   h.estado_nuevo,
                   h.asesor_cedula,
                   h.observacion,
                   h.fecha_cambio,
                   u.nombre AS asesor_nombre,
                   u.apellido AS asesor_apellido
            FROM ticket_estado_historial h
            LEFT JOIN usuarios u ON u.cedula = h.asesor_cedula
            WHERE h.ticket_id = ?
            ORDER BY h.fecha_cambio ASC, h.id ASC
        ");
        $stmt->execute([$ticketId]);
        $rows = $stmt->fetchAll();

        // Calcular duración por etapa: cada entrada representa la transición a estado_nuevo.
        // La duración de esa etapa es: fecha_cambio_siguiente - fecha_cambio_actual.
        // Para la última entrada, la duración es NOW() - fecha_cambio (etapa abierta).
        $now = time();
        $totalSegundos = 0;
        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            $start = strtotime($rows[$i]['fecha_cambio']);
            $end = ($i + 1 < $count)
                ? strtotime($rows[$i + 1]['fecha_cambio'])
                : $now;
            $durSeg = max(0, $end - $start);
            $rows[$i]['duracion_segundos'] = $durSeg;
            $rows[$i]['duracion_legible']  = self::humanizeSeconds($durSeg);
            $rows[$i]['estado_label']      = self::ESTADO_LABELS[$rows[$i]['estado_nuevo']] ?? $rows[$i]['estado_nuevo'];
            $rows[$i]['asesor_nombre_completo'] = trim(
                ($rows[$i]['asesor_nombre'] ?? '') . ' ' . ($rows[$i]['asesor_apellido'] ?? '')
            );
            $totalSegundos += $durSeg;
        }

        return [
            'historial' => $rows,
            'total_segundos' => $totalSegundos,
        ];
    }

    /**
     * Convierte segundos a una cadena legible: "2d 3h 15m" o "45m" o "30s".
     */
    public static function humanizeSeconds($seconds) {
        $seconds = (int) $seconds;
        if ($seconds <= 0) return '0s';

        $days  = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins  = intdiv($seconds % 3600, 60);
        $secs  = $seconds % 60;

        $parts = [];
        if ($days  > 0) $parts[] = $days . 'd';
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($mins  > 0) $parts[] = $mins . 'm';
        if (empty($parts) && $secs >= 0) $parts[] = $secs . 's';

        return implode(' ', $parts);
    }

    /**
     * Estadísticas por estado para el asesor (sin prioridad).
     */
    public function getEstadisticasTickets($asesorCedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN estado = 'comunicacion'     THEN 1 ELSE 0 END) as tickets_comunicacion,
                    SUM(CASE WHEN estado = 'validacion'       THEN 1 ELSE 0 END) as tickets_validacion,
                    SUM(CASE WHEN estado = 'proceso_judicial' THEN 1 ELSE 0 END) as tickets_proceso_judicial,
                    SUM(CASE WHEN estado = 'remate'           THEN 1 ELSE 0 END) as tickets_remate,
                    SUM(CASE WHEN estado = 'recuperacion'     THEN 1 ELSE 0 END) as tickets_recuperacion,
                    SUM(CASE WHEN estado = 'cierre'           THEN 1 ELSE 0 END) as tickets_cierre,
                    SUM(CASE WHEN estado <> 'cierre'          THEN 1 ELSE 0 END) as tickets_abiertos
                FROM tiketera
                WHERE asesor_cedula = ?
            ");
            $stmt->execute([$asesorCedula]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }

    /**
     * Buscar tickets
     */
    public function buscarTickets($asesorCedula, $termino) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       tc.codigo AS categoria_codigo,
                       tc.nombre AS categoria_nombre,
                       c.nombre_completo as cliente_nombre
                FROM tiketera t
                JOIN clientes c ON t.cliente_cedula = c.cedula
                LEFT JOIN ticket_categorias tc ON t.categoria_id = tc.id
                WHERE t.asesor_cedula = ?
                AND (t.titulo LIKE ? OR t.descripcion LIKE ? OR t.numero_ticket LIKE ?
                     OR c.nombre_completo LIKE ?)
                ORDER BY t.fecha_creacion DESC
            ");

            $terminoLike = "%$termino%";
            $stmt->execute([
                $asesorCedula,
                $terminoLike,
                $terminoLike,
                $terminoLike,
                $terminoLike,
            ]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al buscar tickets: " . $e->getMessage());
        }
    }

    /**
     * Obtener tickets por cliente
     */
    public function getTicketsByCliente($clienteCedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       tc.codigo AS categoria_codigo,
                       tc.nombre AS categoria_nombre,
                       CONCAT(u.nombre, ' ', u.apellido) as asesor_nombre,
                       u.email as asesor_email
                FROM tiketera t
                LEFT JOIN usuarios u ON t.asesor_cedula = u.cedula
                LEFT JOIN ticket_categorias tc ON t.categoria_id = tc.id
                WHERE t.cliente_cedula = ?
                ORDER BY t.fecha_creacion DESC
            ");
            $stmt->execute([$clienteCedula]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tickets del cliente: " . $e->getMessage());
        }
    }

    /**
     * Obtener id de categoría por código
     */
    public function getCategoriaIdByCodigo($codigo) {
        if ($codigo === null || $codigo === '') {
            return null;
        }
        $stmt = $this->db->prepare("SELECT id FROM ticket_categorias WHERE codigo = ? AND activo = 1 LIMIT 1");
        $stmt->execute([trim($codigo)]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Actualizar campos del ticket (sin tocar estado).
     * Para cambiar el estado se debe usar updateTicketEstado() (valida transición y registra historial).
     */
    public function actualizarTicket($ticketId, $datos) {
        try {
            $campos = [];
            $valores = [];

            if (isset($datos['titulo'])) {
                $campos[] = "titulo = ?";
                $valores[] = $datos['titulo'];
            }

            if (isset($datos['descripcion'])) {
                $campos[] = "descripcion = ?";
                $valores[] = $datos['descripcion'];
            }

            if (isset($datos['observaciones'])) {
                $campos[] = "observaciones = ?";
                $valores[] = $datos['observaciones'];
            }

            if (isset($datos['pdf_archivo'])) {
                $campos[] = "pdf_archivo = ?";
                $valores[] = $datos['pdf_archivo'];
            }

            if (array_key_exists('categoria_id', $datos)) {
                $campos[] = "categoria_id = ?";
                $valores[] = $datos['categoria_id'];
            }

            if (empty($campos)) {
                return false;
            }

            $campos[] = "fecha_actualizacion = CURRENT_TIMESTAMP";
            $valores[] = $ticketId;

            $sql = "UPDATE tiketera SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($valores);
        } catch (Exception $e) {
            throw new Exception("Error al actualizar ticket: " . $e->getMessage());
        }
    }
}
