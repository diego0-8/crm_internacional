<?php
require_once __DIR__ . '/../config.php';

class HistorialLlamadaModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Crear registro de llamada
     */
    public function createLlamada($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO historial_llamadas 
                (cliente_cedula, asesor_cedula, tipificacion_id, fecha_llamada, duracion_minutos, 
                 observacion, proxima_accion, fecha_proxima_accion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['cliente_cedula'],
                $data['asesor_cedula'],
                $data['tipificacion_id'],
                $data['fecha_llamada'],
                $data['duracion_minutos'] ?? 0,
                $data['observacion'],
                $data['proxima_accion'] ?? null,
                $data['fecha_proxima_accion'] ?? null
            ]);
            
            if ($result) {
                $llamadaId = $this->db->lastInsertId();
                logActivity('llamada_registrada', "Llamada registrada para cliente ID: {$data['cliente_cedula']}");
                return $llamadaId;
            } else {
                throw new Exception("Error al registrar llamada");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener historial de llamadas por cliente
     */
    public function getHistorialByCliente($clienteCedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT h.*, 
                       CONCAT(u.nombre, ' ', u.apellido) as asesor_nombre,
                       t.codigo as tipificacion_codigo,
                       t.categoria as tipificacion_categoria,
                       t.descripcion as tipificacion_descripcion,
                       t.es_positivo
                FROM historial_llamadas h
                JOIN usuarios u ON h.asesor_cedula = u.cedula
                JOIN tipificaciones_llamadas t ON h.tipificacion_id = t.id
                WHERE h.cliente_cedula = ?
                ORDER BY h.fecha_llamada DESC
            ");
            $stmt->execute([$clienteCedula]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener historial de llamadas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener historial de llamadas por asesor
     */
    public function getHistorialByAsesor($asesorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "
                SELECT h.*, 
                       CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                       c.empresa as cliente_empresa,
                       t.codigo as tipificacion_codigo,
                       t.categoria as tipificacion_categoria,
                       t.descripcion as tipificacion_descripcion,
                       t.es_positivo
                FROM historial_llamadas h
                JOIN clientes c ON h.cliente_cedula = c.cedula
                JOIN tipificaciones_llamadas t ON h.tipificacion_id = t.id
                WHERE h.asesor_cedula = ?
            ";
            $params = [$asesorId];
            
            if ($fechaInicio) {
                $sql .= " AND DATE(h.fecha_llamada) >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND DATE(h.fecha_llamada) <= ?";
                $params[] = $fechaFin;
            }
            
            $sql .= " ORDER BY h.fecha_llamada DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener historial del asesor: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de llamadas por asesor
     */
    public function getEstadisticasLlamadas($asesorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_llamadas,
                    COUNT(CASE WHEN t.es_positivo = 1 THEN 1 END) as llamadas_positivas,
                    COUNT(CASE WHEN t.es_positivo = 0 THEN 1 END) as llamadas_negativas,
                    SUM(h.duracion_minutos) as duracion_total,
                    AVG(h.duracion_minutos) as duracion_promedio,
                    COUNT(CASE WHEN t.categoria = 'Llamadas Efectivas' THEN 1 END) as efectivas,
                    COUNT(CASE WHEN t.categoria = 'Llamadas Fallidas' THEN 1 END) as fallidas,
                    COUNT(CASE WHEN t.categoria = 'Llamadas de Seguimiento' THEN 1 END) as seguimiento
                FROM historial_llamadas h
                JOIN tipificaciones_llamadas t ON h.tipificacion_id = t.id
                WHERE h.asesor_cedula = ?
            ";
            $params = [$asesorId];
            
            if ($fechaInicio) {
                $sql .= " AND DATE(h.fecha_llamada) >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND DATE(h.fecha_llamada) <= ?";
                $params[] = $fechaFin;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener resumen de tipificaciones por asesor
     */
    public function getResumenTipificaciones($asesorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "
                SELECT 
                    t.codigo,
                    t.categoria,
                    t.descripcion,
                    COUNT(*) as cantidad,
                    COUNT(CASE WHEN DATE(h.fecha_llamada) = CURDATE() THEN 1 END) as hoy
                FROM historial_llamadas h
                JOIN tipificaciones_llamadas t ON h.tipificacion_id = t.id
                WHERE h.asesor_cedula = ?
            ";
            $params = [$asesorId];
            
            if ($fechaInicio) {
                $sql .= " AND DATE(h.fecha_llamada) >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND DATE(h.fecha_llamada) <= ?";
                $params[] = $fechaFin;
            }
            
            $sql .= " GROUP BY t.id, t.codigo, t.categoria, t.descripcion ORDER BY cantidad DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener resumen de tipificaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar estado del cliente basado en la tipificación
     */
    public function actualizarEstadoCliente($clienteId, $tipificacionId) {
        try {
            $tipificacion = $this->db->prepare("SELECT * FROM tipificaciones_llamadas WHERE id = ?");
            $tipificacion->execute([$tipificacionId]);
            $tip = $tipificacion->fetch();
            
            if (!$tip) {
                throw new Exception("Tipificación no encontrada");
            }
            
            $nuevoEstado = 'contactado'; // Estado por defecto
            
            // Mapear tipificaciones a estados de cliente basado en la nueva estructura
            if ($tip['es_positivo']) {
                // Tipificaciones positivas
                switch ($tip['codigo']) {
                    case 'INTERESADO':
                    case 'ENVIO_INFO':
                    case 'CITA_AGENDADA':
                    case 'CALIFICADO':
                        $nuevoEstado = 'interesado';
                        break;
                    case 'DOCUMENTACION_ENVIADA':
                        $nuevoEstado = 'prospecto';
                        break;
                }
            } else {
                // Tipificaciones negativas
                switch ($tip['codigo']) {
                    case 'NO_INTERESADO':
                    case 'NO_CONTESTA':
                    case 'BUZON_VOZ':
                    case 'NUMERO_ERRONEO':
                        $nuevoEstado = 'inactivo';
                        break;
                    case 'MENSAJE_ENVIADO':
                    case 'CASO_PROCESO':
                        $nuevoEstado = 'contactado';
                        break;
                }
            }
            
            $stmt = $this->db->prepare("UPDATE clientes SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE cedula = ?");
            $result = $stmt->execute([$nuevoEstado, $clienteId]);
            
            if ($result) {
                logActivity('cliente_estado_updated', "Estado del cliente actualizado a: $nuevoEstado");
                return true;
            } else {
                throw new Exception("Error al actualizar estado del cliente");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
