<?php
require_once __DIR__ . '/../config.php';

class SLAController {
    
    /**
     * Obtener SLA aplicable para un ticket
     */
    public static function obtenerSLA($prioridad) {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("
                SELECT * FROM sla_policies 
                WHERE prioridad = :prioridad AND activa = 1 
                ORDER BY fecha_creacion DESC 
                LIMIT 1
            ");
            $stmt->execute([':prioridad' => $prioridad]);
            $sla = $stmt->fetch();
            
            return [
                'success' => $sla ? true : false,
                'data' => $sla
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo SLA: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear tracking de SLA para un ticket
     */
    public static function crearTrackingSLA($ticketId, $prioridad) {
        try {
            $db = getDB();
            
            // Obtener SLA aplicable
            $slaResult = self::obtenerSLA($prioridad);
            if (!$slaResult['success']) {
                return $slaResult;
            }
            
            $sla = $slaResult['data'];
            
            // Calcular tiempos objetivo
            $tiempoRespuestaObjetivo = date('Y-m-d H:i:s', strtotime("+{$sla['tiempo_respuesta_minutos']} minutes"));
            $tiempoResolucionObjetivo = date('Y-m-d H:i:s', strtotime("+{$sla['tiempo_resolucion_horas']} hours"));
            
            $stmt = $db->prepare("
                INSERT INTO sla_tracking 
                (ticket_id, sla_policy_id, tiempo_respuesta_objetivo, tiempo_resolucion_objetivo) 
                VALUES (:ticket_id, :sla_policy_id, :tiempo_respuesta, :tiempo_resolucion)
            ");
            
            $stmt->execute([
                ':ticket_id' => $ticketId,
                ':sla_policy_id' => $sla['id'],
                ':tiempo_respuesta' => $tiempoRespuestaObjetivo,
                ':tiempo_resolucion' => $tiempoResolucionObjetivo
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'sla_policy_id' => (int) $sla['id'],
                    'tiempo_respuesta_objetivo' => $tiempoRespuestaObjetivo,
                    'tiempo_resolucion_objetivo' => $tiempoResolucionObjetivo
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creando tracking SLA: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar cumplimiento de SLAs
     */
    public static function verificarCumplimientoSLA() {
        try {
            $db = getDB();
            
            // Obtener tickets que están cerca de vencer o han vencido
            $stmt = $db->query("
                SELECT t.*, st.*, sp.nombre as sla_nombre, sp.tiempo_respuesta_minutos, sp.tiempo_resolucion_horas
                FROM tiketera t
                JOIN sla_tracking st ON t.id = st.ticket_id
                JOIN sla_policies sp ON st.sla_policy_id = sp.id
                WHERE t.estado IN ('abierto', 'procesando')
                AND (st.tiempo_respuesta_objetivo <= NOW() OR st.tiempo_resolucion_objetivo <= NOW())
                AND st.cumplio_respuesta = 0
            ");
            
            $ticketsVencidos = $stmt->fetchAll();
            
            // Procesar cada ticket vencido
            foreach ($ticketsVencidos as $ticket) {
                self::procesarTicketVencido($ticket);
            }
            
            return [
                'success' => true,
                'data' => $ticketsVencidos
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error verificando cumplimiento SLA: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener métricas de SLA
     */
    public static function obtenerMetricasSLA($fechaInicio = null, $fechaFin = null) {
        try {
            $db = getDB();
            
            $fechaInicio = $fechaInicio ?: date('Y-m-01'); // Primer día del mes
            $fechaFin = $fechaFin ?: date('Y-m-d'); // Hoy
            
            $stmt = $db->prepare("
                SELECT 
                    sp.nombre as sla_nombre,
                    sp.prioridad,
                    COUNT(st.id) as total_tickets,
                    SUM(CASE WHEN st.cumplio_respuesta = 1 THEN 1 ELSE 0 END) as cumplio_respuesta,
                    SUM(CASE WHEN st.cumplio_resolucion = 1 THEN 1 ELSE 0 END) as cumplio_resolucion,
                    AVG(TIMESTAMPDIFF(MINUTE, t.fecha_creacion, st.tiempo_respuesta_real)) as tiempo_respuesta_promedio,
                    AVG(TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre)) as tiempo_resolucion_promedio
                FROM sla_tracking st
                JOIN sla_policies sp ON st.sla_policy_id = sp.id
                JOIN tiketera t ON st.ticket_id = t.id
                WHERE DATE(t.fecha_creacion) BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY sp.id, sp.nombre, sp.prioridad
                ORDER BY sp.prioridad
            ");
            
            $stmt->execute([
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            $metricas = $stmt->fetchAll();
            
            // Calcular porcentajes de cumplimiento
            foreach ($metricas as &$metrica) {
                $metrica['porcentaje_cumplimiento_respuesta'] = $metrica['total_tickets'] > 0 
                    ? round(($metrica['cumplio_respuesta'] / $metrica['total_tickets']) * 100, 2) 
                    : 0;
                    
                $metrica['porcentaje_cumplimiento_resolucion'] = $metrica['total_tickets'] > 0 
                    ? round(($metrica['cumplio_resolucion'] / $metrica['total_tickets']) * 100, 2) 
                    : 0;
            }
            
            return [
                'success' => true,
                'data' => $metricas
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo métricas SLA: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesar ticket vencido (escalamiento automático)
     */
    private static function procesarTicketVencido($ticket) {
        try {
            $db = getDB();
            
            // Marcar como no cumplido
            $stmt = $db->prepare("
                UPDATE sla_tracking 
                SET cumplio_respuesta = 0, cumplio_resolucion = 0 
                WHERE ticket_id = :ticket_id
            ");
            $stmt->execute([':ticket_id' => $ticket['id']]);
            
            // Aquí se podría implementar lógica de escalamiento automático
            // Por ejemplo, notificar al supervisor o cambiar la prioridad
            
            // Log del evento
            error_log("Ticket {$ticket['id']} ha vencido SLA - Prioridad: {$ticket['prioridad']}");
            
        } catch (Exception $e) {
            error_log("Error procesando ticket vencido: " . $e->getMessage());
        }
    }
}
?>
