<?php
require_once __DIR__ . '/../config.php';

class TitularModel {
    private $db;

    private static $tieneColAsesor = null;

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

    public function __construct() {
        $this->db = getDB();
    }

    public function getEstadisticasPorCoordinador(string $coordinadorCedula): array {
        $conAsesor = $this->titularesTieneColumnaAsesorCedula();
        if ($conAsesor) {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN asesor_cedula IS NOT NULL THEN 1 ELSE 0 END) AS asignados
                FROM titulares
                WHERE coordinador_cedula = ?
            ');
        } else {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) AS total, 0 AS asignados
                FROM titulares
                WHERE coordinador_cedula = ?
            ');
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
                p.condado,
                (SELECT c.email FROM correos c WHERE c.id_cliente = t.id_cliente ORDER BY c.orden ASC LIMIT 1) AS email,
                (SELECT tel.numero FROM telefonos tel WHERE tel.id_cliente = t.id_cliente ORDER BY tel.orden ASC LIMIT 1) AS telefono
            FROM titulares t
            LEFT JOIN propiedades p ON p.id_cliente = t.id_cliente
            LEFT JOIN usuarios a ON t.asesor_cedula = a.cedula
            WHERE t.coordinador_cedula = ?
        ';
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
                p.condado,
                (SELECT c.email FROM correos c WHERE c.id_cliente = t.id_cliente ORDER BY c.orden ASC LIMIT 1) AS email,
                (SELECT tel.numero FROM telefonos tel WHERE tel.id_cliente = t.id_cliente ORDER BY tel.orden ASC LIMIT 1) AS telefono
            FROM titulares t
            LEFT JOIN propiedades p ON p.id_cliente = t.id_cliente
            WHERE t.coordinador_cedula = ?
        ';
        }
        $params = [$coordinadorCedula];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $like = '%' . trim($busqueda) . '%';
            $sql .= ' AND (
                t.primer_nombre LIKE ? OR t.apellido LIKE ?
                OR p.numero_caso LIKE ? OR p.condado LIKE ?
                OR EXISTS (SELECT 1 FROM telefonos tel2 WHERE tel2.id_cliente = t.id_cliente AND tel2.numero LIKE ?)
                OR EXISTS (SELECT 1 FROM correos co2 WHERE co2.id_cliente = t.id_cliente AND co2.email LIKE ?)
                OR COALESCE(t.reg_int, \'\') LIKE ?
                OR COALESCE(t.base_d, \'\') LIKE ?
                OR COALESCE(t.f_correo, \'\') LIKE ?
            )';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like, $like]);
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
        ';
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
        $stmt = $this->db->prepare('
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
        ');
        $stmt->execute([$asesorCedula]);
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
    public function listarDisponiblesPorCoordinador(string $coordinadorCedula, int $limite): array {
        if ($this->titularesTieneColumnaAsesorCedula()) {
            $stmt = $this->db->prepare('
            SELECT id_cliente FROM titulares
            WHERE coordinador_cedula = ? AND asesor_cedula IS NULL
            ORDER BY id_cliente ASC
            LIMIT ?
        ');
        } else {
            $stmt = $this->db->prepare('
            SELECT id_cliente FROM titulares
            WHERE coordinador_cedula = ?
            ORDER BY id_cliente ASC
            LIMIT ?
        ');
        }
        $stmt->bindValue(1, $coordinadorCedula, PDO::PARAM_STR);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
