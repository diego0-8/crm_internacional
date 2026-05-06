<?php
require_once __DIR__ . '/../config.php';

class MetricaModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Crear o actualizar métricas de asesor
     */
    public function upsertMetricas($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO metricas_asesores 
                (asesor_cedula, coordinador_cedula, fecha_reporte, clientes_asignados, clientes_contactados, 
                 llamadas_realizadas, emails_enviados, reuniones_realizadas, tareas_completadas, 
                 clientes_convertidos, ingresos_generados) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                clientes_asignados = VALUES(clientes_asignados),
                clientes_contactados = VALUES(clientes_contactados),
                llamadas_realizadas = VALUES(llamadas_realizadas),
                emails_enviados = VALUES(emails_enviados),
                reuniones_realizadas = VALUES(reuniones_realizadas),
                tareas_completadas = VALUES(tareas_completadas),
                clientes_convertidos = VALUES(clientes_convertidos),
                ingresos_generados = VALUES(ingresos_generados),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $result = $stmt->execute([
                $data['asesor_cedula'],
                $data['coordinador_cedula'],
                $data['fecha_reporte'],
                $data['clientes_asignados'] ?? 0,
                $data['clientes_contactados'] ?? 0,
                $data['llamadas_realizadas'] ?? 0,
                $data['emails_enviados'] ?? 0,
                $data['reuniones_realizadas'] ?? 0,
                $data['tareas_completadas'] ?? 0,
                $data['clientes_convertidos'] ?? 0,
                $data['ingresos_generados'] ?? 0.00
            ]);
            
            if ($result) {
                logActivity('metricas_updated', "Métricas actualizadas para asesor: {$data['asesor_cedula']}");
                return true;
            } else {
                throw new Exception("Error al actualizar métricas");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener métricas de asesor por fecha
     */
    public function getMetricasByAsesor($asesorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "
                SELECT m.*, 
                       CONCAT(u.nombre, ' ', u.apellido) as asesor_nombre
                FROM metricas_asesores m
                JOIN usuarios u ON m.asesor_cedula = u.cedula
                WHERE m.asesor_cedula = ?
            ";
            $params = [$asesorId];
            
            if ($fechaInicio) {
                $sql .= " AND m.fecha_reporte >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND m.fecha_reporte <= ?";
                $params[] = $fechaFin;
            }
            
            $sql .= " ORDER BY m.fecha_reporte DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener métricas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener métricas de todos los asesores por coordinador
     */
    public function getMetricasByCoordinador($coordinadorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "
                SELECT m.*, 
                       CONCAT(u.nombre, ' ', u.apellido) as asesor_nombre,
                       u.email as asesor_email
                FROM metricas_asesores m
                JOIN usuarios u ON m.asesor_cedula = u.cedula
                WHERE m.coordinador_cedula = ?
            ";
            $params = [$coordinadorId];
            
            if ($fechaInicio) {
                $sql .= " AND m.fecha_reporte >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND m.fecha_reporte <= ?";
                $params[] = $fechaFin;
            }
            
            $sql .= " ORDER BY m.fecha_reporte DESC, u.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener métricas del coordinador: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener resumen de métricas por coordinador
     */
    public function getResumenMetricas($coordinadorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(DISTINCT m.asesor_cedula) as total_asesores,
                    SUM(m.clientes_asignados) as total_clientes_asignados,
                    SUM(m.clientes_contactados) as total_clientes_contactados,
                    SUM(m.llamadas_realizadas) as total_llamadas,
                    SUM(m.emails_enviados) as total_emails,
                    SUM(m.reuniones_realizadas) as total_reuniones,
                    SUM(m.tareas_completadas) as total_tareas_completadas,
                    SUM(m.clientes_convertidos) as total_clientes_convertidos,
                    SUM(m.ingresos_generados) as total_ingresos
                FROM metricas_asesores m
                WHERE m.coordinador_cedula = ?
            ";
            $params = [$coordinadorId];
            
            if ($fechaInicio) {
                $sql .= " AND m.fecha_reporte >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND m.fecha_reporte <= ?";
                $params[] = $fechaFin;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: [
                'total_asesores' => 0,
                'total_clientes_asignados' => 0,
                'total_clientes_contactados' => 0,
                'total_llamadas' => 0,
                'total_emails' => 0,
                'total_reuniones' => 0,
                'total_tareas_completadas' => 0,
                'total_clientes_convertidos' => 0,
                'total_ingresos' => 0
            ];
        } catch (Exception $e) {
            throw new Exception("Error al obtener resumen de métricas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener métricas por asesor para exportar
     */
    public function getMetricasParaExportar($coordinadorId, $fechaInicio, $fechaFin) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    CONCAT(u.nombre, ' ', u.apellido) as asesor_nombre,
                    u.email as asesor_email,
                    m.fecha_reporte,
                    m.clientes_asignados,
                    m.clientes_contactados,
                    m.llamadas_realizadas,
                    m.emails_enviados,
                    m.reuniones_realizadas,
                    m.tareas_completadas,
                    m.clientes_convertidos,
                    m.ingresos_generados,
                    ROUND((m.clientes_contactados / NULLIF(m.clientes_asignados, 0)) * 100, 2) as porcentaje_contacto,
                    ROUND((m.tareas_completadas / NULLIF((m.llamadas_realizadas + m.emails_enviados + m.reuniones_realizadas), 0)) * 100, 2) as porcentaje_efectividad
                FROM metricas_asesores m
                JOIN usuarios u ON m.asesor_cedula = u.cedula
                WHERE m.coordinador_cedula = ? 
                AND m.fecha_reporte BETWEEN ? AND ?
                ORDER BY u.nombre ASC, m.fecha_reporte DESC
            ");
            $stmt->execute([$coordinadorId, $fechaInicio, $fechaFin]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener métricas para exportar: " . $e->getMessage());
        }
    }
    
    /**
     * Calcular métricas automáticamente para un asesor
     */
    public function calcularMetricasAutomaticas($asesorId, $coordinadorId, $fecha) {
        try {
            $this->db->beginTransaction();
            
            // Obtener datos de clientes
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as clientes_asignados,
                    COUNT(CASE WHEN estado != 'nuevo' THEN 1 END) as clientes_contactados
                FROM clientes 
                WHERE asesor_cedula = ? AND DATE(created_at) <= ?
            ");
            $stmt->execute([$asesorId, $fecha]);
            $clientes = $stmt->fetch();
            
            // Obtener datos de tareas
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN tipo = 'llamada' THEN 1 END) as llamadas_realizadas,
                    COUNT(CASE WHEN tipo = 'email' THEN 1 END) as emails_enviados,
                    COUNT(CASE WHEN tipo = 'reunion' THEN 1 END) as reuniones_realizadas,
                    COUNT(CASE WHEN estado = 'completada' THEN 1 END) as tareas_completadas
                FROM tareas 
                WHERE asesor_cedula = ? AND DATE(created_at) = ?
            ");
            $stmt->execute([$asesorId, $fecha]);
            $tareas = $stmt->fetch();
            
            // Obtener clientes convertidos
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as clientes_convertidos
                FROM clientes 
                WHERE asesor_cedula = ? AND estado = 'cliente' AND DATE(updated_at) = ?
            ");
            $stmt->execute([$asesorId, $fecha]);
            $convertidos = $stmt->fetch();
            
            // Crear/actualizar métricas
            $metricas = [
                'asesor_cedula' => $asesorId,
                'coordinador_cedula' => $coordinadorId,
                'fecha_reporte' => $fecha,
                'clientes_asignados' => $clientes['clientes_asignados'],
                'clientes_contactados' => $clientes['clientes_contactados'],
                'llamadas_realizadas' => $tareas['llamadas_realizadas'],
                'emails_enviados' => $tareas['emails_enviados'],
                'reuniones_realizadas' => $tareas['reuniones_realizadas'],
                'tareas_completadas' => $tareas['tareas_completadas'],
                'clientes_convertidos' => $convertidos['clientes_convertidos'],
                'ingresos_generados' => 0.00 // Se puede calcular basado en ventas
            ];
            
            $this->upsertMetricas($metricas);
            $this->db->commit();
            
            return $metricas;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>
