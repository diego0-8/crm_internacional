<?php
require_once __DIR__ . '/../config.php';

class TitularModel {
    private $db;

    private static $tieneColAsesor = null;
    /** @var bool|null */
    private static $tiketeraTienePrimeraGestion = null;
    /** @var bool|null */
    private static $tieneColArchivoCsv = null;
    /** @var bool|null */
    private static $archivosCsvTieneActivo = null;

    /** True si la columna existe en la BD actual (evita 1054 si la migración no se aplicó). */
    private function titularesTieneColumnaAsesorCedula(): bool {
        if (self::$tieneColAsesor !== null) {
            return self::$tieneColAsesor;
        }
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'titulares'
                  AND COLUMN_NAME = 'asesor_cedula'
            ");
            self::$tieneColAsesor = ((int) $stmt->fetchColumn()) > 0;
        } catch (Exception $e) {
            self::$tieneColAsesor = false;
        }
        return self::$tieneColAsesor;
    }

    private function tiketeraTieneColumnaPrimeraGestion(): bool {
        if (self::$tiketeraTienePrimeraGestion !== null) {
            return self::$tiketeraTienePrimeraGestion;
        }
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'tiketera'
                  AND COLUMN_NAME = 'primera_gestion_at'
            ");
            self::$tiketeraTienePrimeraGestion = ((int) $stmt->fetchColumn()) > 0;
        } catch (Exception $e) {
            self::$tiketeraTienePrimeraGestion = false;
        }
        return self::$tiketeraTienePrimeraGestion;
    }

    private function titularesTieneColumnaArchivoCsvId(): bool {
        if (self::$tieneColArchivoCsv !== null) {
            return self::$tieneColArchivoCsv;
        }
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'titulares'
                  AND COLUMN_NAME = 'archivo_csv_id'
            ");
            self::$tieneColArchivoCsv = ((int) $stmt->fetchColumn()) > 0;
        } catch (Exception $e) {
            self::$tieneColArchivoCsv = false;
        }
        return self::$tieneColArchivoCsv;
    }

    private function archivosCsvTieneColumnaActivo(): bool {
        if (self::$archivosCsvTieneActivo !== null) {
            return self::$archivosCsvTieneActivo;
        }
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'archivos_csv'
                  AND COLUMN_NAME = 'activo'
            ");
            self::$archivosCsvTieneActivo = ((int) $stmt->fetchColumn()) > 0;
        } catch (Exception $e) {
            self::$archivosCsvTieneActivo = false;
        }
        return self::$archivosCsvTieneActivo;
    }

    /**
     * Fragmento SQL: excluye titulares cuyo cargue CSV está inhabilitado.
     */
    private function sqlSoloArchivoCsvActivo(string $titularAlias = 't'): string {
        if (!$this->titularesTieneColumnaArchivoCsvId() || !$this->archivosCsvTieneColumnaActivo()) {
            return '';
        }
        return " AND ({$titularAlias}.archivo_csv_id IS NULL OR EXISTS (
            SELECT 1 FROM archivos_csv ac_csv_vis
            WHERE ac_csv_vis.id = {$titularAlias}.archivo_csv_id AND ac_csv_vis.activo = 1
        ))";
    }

    /**
     * True si el titular no tiene CSV o su cargue sigue habilitado.
     */
    public function titularTieneArchivoCsvActivo(int $idCliente): bool {
        if (!$this->titularesTieneColumnaArchivoCsvId()) {
            return true;
        }
        $sql = 'SELECT archivo_csv_id FROM titulares WHERE id_cliente = ? LIMIT 1';
        if ($this->archivosCsvTieneColumnaActivo()) {
            $sql = '
                SELECT t.archivo_csv_id, COALESCE(ac.activo, 1) AS activo
                FROM titulares t
                LEFT JOIN archivos_csv ac ON ac.id = t.archivo_csv_id
                WHERE t.id_cliente = ?
                LIMIT 1
            ';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCliente]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if (empty($row['archivo_csv_id'])) {
            return true;
        }
        if (!$this->archivosCsvTieneColumnaActivo()) {
            return true;
        }
        return (int) ($row['activo'] ?? 1) === 1;
    }

    public function __construct() {
        $this->db = getDB();
    }

    public function getEstadisticasPorCoordinador(string $coordinadorCedula): array {
        $conAsesor = $this->titularesTieneColumnaAsesorCedula();
        if ($conAsesor) {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN asesor_cedula IS NOT NULL THEN 1 ELSE 0 END) AS asignados
                FROM titulares t
                WHERE t.coordinador_cedula = ?
            ' . $this->sqlSoloArchivoCsvActivo('t'));
        } else {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) AS total, 0 AS asignados
                FROM titulares t
                WHERE t.coordinador_cedula = ?
            ' . $this->sqlSoloArchivoCsvActivo('t'));
        }
        $stmt->execute([$coordinadorCedula]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'asignados' => 0];
        return [
            'total' => (int) ($row['total'] ?? 0),
            'asignados' => (int) ($row['asignados'] ?? 0),
            'no_asignados' => max(0, (int) ($row['total'] ?? 0) - (int) ($row['asignados'] ?? 0)),
        ];
    }

    /**
     * Lista titulares del coordinador con datos para la UI de tareas.
     */
    public function listarPorCoordinador(string $coordinadorCedula, ?string $busqueda = null): array {
        $conAsesor = $this->titularesTieneColumnaAsesorCedula();
        if ($conAsesor) {
            $sql = '
            SELECT
                t.id_cliente AS titular_id,
                t.primer_nombre AS nombre,
                t.apellido,
                t.prioridad,
                t.agente,
                t.reg_int,
                t.base_d,
                t.f_correo,
                t.mailing_ciudad AS ciudad,
                t.mailing_estado AS estado_region,
                t.asesor_cedula,
                CONCAT(a.nombre, \' \', a.apellido) AS asesor_nombre,
                p.numero_caso,
                p.numero_parcela,
                p.condado,
                (SELECT c.email FROM correos c WHERE c.id_cliente = t.id_cliente ORDER BY c.orden ASC LIMIT 1) AS email,
                (SELECT tel.numero FROM telefonos tel WHERE tel.id_cliente = t.id_cliente ORDER BY tel.orden ASC LIMIT 1) AS telefono
            FROM titulares t
            LEFT JOIN propiedades p ON p.id_cliente = t.id_cliente
            LEFT JOIN usuarios a ON t.asesor_cedula = a.cedula
            WHERE t.coordinador_cedula = ?
        ' . $this->sqlSoloArchivoCsvActivo('t');
        } else {
            $sql = '
            SELECT
                t.id_cliente AS titular_id,
                t.primer_nombre AS nombre,
                t.apellido,
                t.prioridad,
                t.agente,
                t.reg_int,
                t.base_d,
                t.f_correo,
                t.mailing_ciudad AS ciudad,
                t.mailing_estado AS estado_region,
                NULL AS asesor_cedula,
                NULL AS asesor_nombre,
                p.numero_caso,
                p.numero_parcela,
                p.condado,
                (SELECT c.email FROM correos c WHERE c.id_cliente = t.id_cliente ORDER BY c.orden ASC LIMIT 1) AS email,
                (SELECT tel.numero FROM telefonos tel WHERE tel.id_cliente = t.id_cliente ORDER BY tel.orden ASC LIMIT 1) AS telefono
            FROM titulares t
            LEFT JOIN propiedades p ON p.id_cliente = t.id_cliente
            WHERE t.coordinador_cedula = ?
        ' . $this->sqlSoloArchivoCsvActivo('t');
        }
        $params = [$coordinadorCedula];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $like = '%' . trim($busqueda) . '%';
            $sql .= ' AND (
                t.primer_nombre LIKE ? OR t.apellido LIKE ?
                OR CONCAT(COALESCE(t.primer_nombre, \'\'), \' \', COALESCE(t.apellido, \'\')) LIKE ?
                OR p.numero_caso LIKE ? OR p.numero_parcela LIKE ? OR p.condado LIKE ?
                OR EXISTS (SELECT 1 FROM telefonos tel2 WHERE tel2.id_cliente = t.id_cliente AND tel2.numero LIKE ?)
                OR EXISTS (SELECT 1 FROM correos co2 WHERE co2.id_cliente = t.id_cliente AND co2.email LIKE ?)
                OR COALESCE(t.reg_int, \'\') LIKE ?
                OR COALESCE(t.base_d, \'\') LIKE ?
                OR COALESCE(t.f_correo, \'\') LIKE ?
            )';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like]);
        }
        $sql .= ' ORDER BY t.id_cliente DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['titular_id'] = (int) $r['titular_id'];
            $r['estado'] = $r['prioridad'] ? ('prio: ' . $r['prioridad']) : 'reparto';
            $r['empresa'] = $r['condado'] ?? '';
        }
        unset($r);
        return $rows;
    }

    /**
     * Titulares asignados al asesor (reparto CSV / coordinador).
     */
    public function listarPorAsesor(string $asesorCedula, ?string $busqueda = null): array {
        if (!$this->titularesTieneColumnaAsesorCedula()) {
            return [];
        }
        $sql = '
            SELECT
                t.id_cliente AS titular_id,
                t.primer_nombre AS nombre,
                t.apellido,
                t.prioridad,
                t.agente,
                t.mailing_ciudad AS ciudad,
                t.mailing_estado AS estado_region,
                t.asesor_cedula,
                CONCAT(a.nombre, \' \', a.apellido) AS asesor_nombre,
                p.numero_caso,
                p.condado,
                (SELECT c.email FROM correos c WHERE c.id_cliente = t.id_cliente ORDER BY c.orden ASC LIMIT 1) AS email,
                (SELECT tel.numero FROM telefonos tel WHERE tel.id_cliente = t.id_cliente ORDER BY tel.orden ASC LIMIT 1) AS telefono
            FROM titulares t
            LEFT JOIN propiedades p ON p.id_cliente = t.id_cliente
            LEFT JOIN usuarios a ON t.asesor_cedula = a.cedula
            WHERE t.asesor_cedula = ?
        ' . $this->sqlSoloArchivoCsvActivo('t');
        $params = [$asesorCedula];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $like = '%' . trim($busqueda) . '%';
            $sql .= ' AND (
                t.primer_nombre LIKE ? OR t.apellido LIKE ?
                OR p.numero_caso LIKE ? OR p.condado LIKE ?
                OR EXISTS (SELECT 1 FROM telefonos tel2 WHERE tel2.id_cliente = t.id_cliente AND tel2.numero LIKE ?)
                OR EXISTS (SELECT 1 FROM correos co2 WHERE co2.id_cliente = t.id_cliente AND co2.email LIKE ?)
            )';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        }
        /** Casos CRM ya gestionados (primera vez): desaparecen del dashboard de reparto. */
        if ($this->tiketeraTieneColumnaPrimeraGestion()) {
            $sql .= ' AND NOT EXISTS (
                SELECT 1 FROM tiketera tk
                WHERE tk.cliente_cedula = CONCAT(\'TIT-\', t.id_cliente)
                  AND tk.asesor_cedula = ?
                  AND tk.primera_gestion_at IS NOT NULL
            )';
            $params[] = $asesorCedula;
        }
        $sql .= ' ORDER BY t.id_cliente DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['titular_id'] = (int) $r['titular_id'];
            $r['estado'] = $r['prioridad'] ? ('prio: ' . $r['prioridad']) : 'reparto';
            $r['empresa'] = $r['condado'] ?? '';
        }
        unset($r);
        return $rows;
    }

    /**
     * Conteos rápidos para dashboard/estadísticas del asesor (tabla titulares).
     */
    public function estadisticasPorAsesor(string $asesorCedula): array {
        if (!$this->titularesTieneColumnaAsesorCedula()) {
            return [
                'total_titulares' => 0,
                'con_prioridad' => 0,
                'con_contacto' => 0,
                'titulares_mes' => 0,
            ];
        }
        $sql = '
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN t.prioridad IS NOT NULL AND TRIM(t.prioridad) <> \'\' THEN 1 ELSE 0 END) AS con_prioridad,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM telefonos tel WHERE tel.id_cliente = t.id_cliente LIMIT 1
                ) OR EXISTS (
                    SELECT 1 FROM correos co WHERE co.id_cliente = t.id_cliente LIMIT 1
                ) THEN 1 ELSE 0 END) AS con_contacto,
                SUM(CASE WHEN t.actualizado_en >= DATE_FORMAT(NOW(), \'%Y-%m-01\') THEN 1 ELSE 0 END) AS titulares_mes
            FROM titulares t
            WHERE t.asesor_cedula = ?
        ' . $this->sqlSoloArchivoCsvActivo('t');
        $params = [$asesorCedula];
        if ($this->tiketeraTieneColumnaPrimeraGestion()) {
            $sql .= ' AND NOT EXISTS (
                SELECT 1 FROM tiketera tk
                WHERE tk.cliente_cedula = CONCAT(\'TIT-\', t.id_cliente)
                  AND tk.asesor_cedula = ?
                  AND tk.primera_gestion_at IS NOT NULL
            )';
            $params[] = $asesorCedula;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_titulares' => (int) ($row['total'] ?? 0),
            'con_prioridad' => (int) ($row['con_prioridad'] ?? 0),
            'con_contacto' => (int) ($row['con_contacto'] ?? 0),
            'titulares_mes' => (int) ($row['titulares_mes'] ?? 0),
        ];
    }

    public function titularPerteneceACoordinador(int $idCliente, string $coordinadorCedula): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM titulares WHERE id_cliente = ? AND coordinador_cedula = ? LIMIT 1');
        $stmt->execute([$idCliente, $coordinadorCedula]);
        return (bool) $stmt->fetchColumn();
    }

    public function asignarAsesor(int $idCliente, string $asesorCedula, string $coordinadorCedula): void {
        if (!$this->titularesTieneColumnaAsesorCedula()) {
            throw new Exception(
                'La tabla titulares no tiene la columna asesor_cedula. Ejecute en MySQL el script database/migration_titulares_archivo_csv.sql (o importe database/internacional2.sql en una base nueva).'
            );
        }
        if (!$this->titularPerteneceACoordinador($idCliente, $coordinadorCedula)) {
            throw new Exception('Titular no encontrado o no pertenece a este coordinador');
        }
        if (!$this->titularTieneArchivoCsvActivo($idCliente)) {
            throw new Exception('Este titular pertenece a un cargue CSV inhabilitado y no puede asignarse');
        }
        $stmt = $this->db->prepare('UPDATE titulares SET asesor_cedula = ?, actualizado_en = CURRENT_TIMESTAMP WHERE id_cliente = ?');
        $stmt->execute([$asesorCedula, $idCliente]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('No se pudo actualizar el titular');
        }
        logActivity('titular_assigned', "Titular id_cliente=$idCliente asignado a asesor $asesorCedula");
    }

    /**
     * Titulares sin asesor del coordinador, orden antiguos primero.
     */
    /**
     * Filas completas titular + propiedad + contactos para exportación CSV del coordinador.
     *
     * @param string|null $filtroAsignacion '' | 'asignados' | 'sin_asesor'
     */
    public function filasParaExporteCoordinador(
        string $coordinadorCedula,
        ?string $fechaInicio,
        ?string $fechaFin,
        ?string $filtroAsignacion = null
    ): array {
        $conAsesor = $this->titularesTieneColumnaAsesorCedula();
        $asesorJoin = $conAsesor
            ? 'LEFT JOIN usuarios a ON t.asesor_cedula = a.cedula'
            : '';
        $asesorSelect = $conAsesor
            ? "t.asesor_cedula, TRIM(CONCAT(COALESCE(a.nombre, ''), ' ', COALESCE(a.apellido, ''))) AS asesor_nombre,"
            : "NULL AS asesor_cedula, NULL AS asesor_nombre,";

        $sql = "
            SELECT
                t.id_cliente,
                t.primer_nombre,
                t.apellido,
                t.mailing_calle,
                t.mailing_ciudad,
                t.mailing_estado,
                t.mailing_codigo_postal,
                t.edad,
                t.fallecido,
                t.reg_int,
                t.base_d,
                t.f_correo,
                t.agente,
                t.prioridad,
                {$asesorSelect}
                DATE_FORMAT(t.creado_en, '%Y-%m-%d %H:%i') AS fecha_registro,
                DATE_FORMAT(t.actualizado_en, '%Y-%m-%d %H:%i') AS fecha_actualizacion,
                p.numero_caso,
                p.numero_parcela,
                p.tipo_foreclosure,
                p.propiedad_calle,
                p.propiedad_ciudad,
                p.propiedad_estado,
                p.propiedad_codigo_postal,
                p.condado,
                p.fuente,
                p.fecha_venta,
                p.dias_transcurridos,
                p.excedente,
                p.puja_apertura,
                p.puja_cierre,
                p.monetizacion,
                (SELECT GROUP_CONCAT(tel.numero ORDER BY tel.orden SEPARATOR ' | ')
                 FROM telefonos tel WHERE tel.id_cliente = t.id_cliente) AS telefonos,
                (SELECT GROUP_CONCAT(co.email ORDER BY co.orden SEPARATOR ' | ')
                 FROM correos co WHERE co.id_cliente = t.id_cliente) AS correos
            FROM titulares t
            LEFT JOIN propiedades p ON p.id_cliente = t.id_cliente
            {$asesorJoin}
            WHERE t.coordinador_cedula = ?
        ";
        $params = [$coordinadorCedula];

        if ($fechaInicio !== null && $fechaInicio !== '') {
            $sql .= ' AND DATE(t.creado_en) >= ?';
            $params[] = $fechaInicio;
        }
        if ($fechaFin !== null && $fechaFin !== '') {
            $sql .= ' AND DATE(t.creado_en) <= ?';
            $params[] = $fechaFin;
        }

        $filtro = $filtroAsignacion !== null ? trim($filtroAsignacion) : '';
        if ($conAsesor && $filtro === 'asignados') {
            $sql .= ' AND t.asesor_cedula IS NOT NULL AND TRIM(t.asesor_cedula) <> \'\'';
        } elseif ($conAsesor && $filtro === 'sin_asesor') {
            $sql .= ' AND (t.asesor_cedula IS NULL OR TRIM(t.asesor_cedula) = \'\')';
        }

        $sql .= ' ORDER BY t.id_cliente ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen de titulares por asesor (incluye fila sin asignar) para exportación.
     */
    public function resumenAsignacionParaExporte(
        string $coordinadorCedula,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        $conAsesor = $this->titularesTieneColumnaAsesorCedula();
        if (!$conAsesor) {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) AS total_titulares
                FROM titulares
                WHERE coordinador_cedula = ?
                  AND (? IS NULL OR ? = \'\' OR DATE(creado_en) >= ?)
                  AND (? IS NULL OR ? = \'\' OR DATE(creado_en) <= ?)
            ');
            $stmt->execute([
                $coordinadorCedula,
                $fechaInicio, $fechaInicio, $fechaInicio,
                $fechaFin, $fechaFin, $fechaFin,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return [[
                'asesor_cedula' => '',
                'asesor_nombre' => 'Sin columna asesor (migración pendiente)',
                'total_titulares' => (int) ($row['total_titulares'] ?? 0),
            ]];
        }

        $sql = "
            SELECT
                COALESCE(NULLIF(TRIM(t.asesor_cedula), ''), '') AS asesor_cedula,
                CASE
                    WHEN t.asesor_cedula IS NULL OR TRIM(t.asesor_cedula) = '' THEN 'Sin asignar'
                    ELSE TRIM(CONCAT(COALESCE(a.nombre, ''), ' ', COALESCE(a.apellido, '')))
                END AS asesor_nombre,
                COUNT(*) AS total_titulares
            FROM titulares t
            LEFT JOIN usuarios a ON t.asesor_cedula = a.cedula
            WHERE t.coordinador_cedula = ?
        ";
        $params = [$coordinadorCedula];
        if ($fechaInicio !== null && $fechaInicio !== '') {
            $sql .= ' AND DATE(t.creado_en) >= ?';
            $params[] = $fechaInicio;
        }
        if ($fechaFin !== null && $fechaFin !== '') {
            $sql .= ' AND DATE(t.creado_en) <= ?';
            $params[] = $fechaFin;
        }
        $sql .= ' GROUP BY asesor_cedula, asesor_nombre ORDER BY total_titulares DESC, asesor_nombre ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conteo de filas que tendría un export de titulares (vista previa).
     */
    public function contarParaExporteCoordinador(
        string $coordinadorCedula,
        ?string $fechaInicio,
        ?string $fechaFin,
        ?string $filtroAsignacion = null
    ): int {
        $conAsesor = $this->titularesTieneColumnaAsesorCedula();
        $sql = 'SELECT COUNT(*) FROM titulares t WHERE t.coordinador_cedula = ?';
        $params = [$coordinadorCedula];
        if ($fechaInicio !== null && $fechaInicio !== '') {
            $sql .= ' AND DATE(t.creado_en) >= ?';
            $params[] = $fechaInicio;
        }
        if ($fechaFin !== null && $fechaFin !== '') {
            $sql .= ' AND DATE(t.creado_en) <= ?';
            $params[] = $fechaFin;
        }
        $filtro = $filtroAsignacion !== null ? trim($filtroAsignacion) : '';
        if ($conAsesor && $filtro === 'asignados') {
            $sql .= ' AND t.asesor_cedula IS NOT NULL AND TRIM(t.asesor_cedula) <> \'\'';
        } elseif ($conAsesor && $filtro === 'sin_asesor') {
            $sql .= ' AND (t.asesor_cedula IS NULL OR TRIM(t.asesor_cedula) = \'\')';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listarDisponiblesPorCoordinador(string $coordinadorCedula, int $limite): array {
        $filtroArchivo = $this->sqlSoloArchivoCsvActivo('t');
        if ($this->titularesTieneColumnaAsesorCedula()) {
            $stmt = $this->db->prepare('
            SELECT t.id_cliente FROM titulares t
            WHERE t.coordinador_cedula = ? AND t.asesor_cedula IS NULL
            ' . $filtroArchivo . '
            ORDER BY t.id_cliente ASC
            LIMIT ?
        ');
        } else {
            $stmt = $this->db->prepare('
            SELECT t.id_cliente FROM titulares t
            WHERE t.coordinador_cedula = ?
            ' . $filtroArchivo . '
            ORDER BY t.id_cliente ASC
            LIMIT ?
        ');
        }
        $stmt->bindValue(1, $coordinadorCedula, PDO::PARAM_STR);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
