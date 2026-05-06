<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ClienteModel.php';
require_once __DIR__ . '/../model/TipificacionModel.php';
require_once __DIR__ . '/../model/HistorialLlamadaModel.php';
require_once __DIR__ . '/../model/TareaModel.php';
require_once __DIR__ . '/../model/TiketeraModel.php';

class AsesorController {
    private $clienteModel;
    private $tipificacionModel;
    private $historialLlamadaModel;
    private $tareaModel;
    private $tiketeraModel;
    
    public function __construct() {
        $this->clienteModel = new ClienteModel();
        $this->tipificacionModel = new TipificacionModel();
        $this->historialLlamadaModel = new HistorialLlamadaModel();
        $this->tareaModel = new TareaModel();
        $this->tiketeraModel = new TiketeraModel();
    }
    
    /**
     * Obtener clientes del asesor
     */
    public function getClientesAsesor($asesorCedula) {
        try {
            $clientes = $this->clienteModel->getClientesByAsesor($asesorCedula);
            
            return [
                'success' => true,
                'data' => $clientes
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener cliente específico con historial
     */
    public function getClienteDetalle($clienteCedula, $asesorCedula) {
        try {
            $cliente = $this->clienteModel->getClienteByCedula($clienteCedula);
            
            if (!$cliente) {
                throw new Exception("Cliente no encontrado");
            }
            
            // Verificar que el cliente pertenece al asesor
            if ($cliente['asesor_cedula'] != $asesorCedula) {
                throw new Exception("No tienes permisos para ver este cliente");
            }
            
            // Obtener historial de llamadas
            $historial = $this->historialLlamadaModel->getHistorialByCliente($clienteCedula);
            
            return [
                'success' => true,
                'data' => [
                    'cliente' => $cliente,
                    'historial' => $historial
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener tipificaciones
     */
    public function getTipificaciones() {
        try {
            $tipificaciones = $this->tipificacionModel->getAllTipificaciones();
            $tipificacionesJerarquicas = $this->tipificacionModel->getTipificacionesJerarquicas();
            $categorias = $this->tipificacionModel->getCategorias();
            
            return [
                'success' => true,
                'data' => [
                    'tipificaciones' => $tipificaciones,
                    'tipificaciones_jerarquicas' => $tipificacionesJerarquicas,
                    'categorias' => $categorias
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar llamada
     */
    public function registrarLlamada($data) {
        try {
            // Validar datos requeridos
            $requiredFields = ['cliente_cedula', 'asesor_cedula', 'tipificacion_id', 'observacion'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            // Verificar que el cliente pertenece al asesor
            $cliente = $this->clienteModel->getClienteByCedula($data['cliente_cedula']);
            if (!$cliente || $cliente['asesor_cedula'] != $data['asesor_cedula']) {
                throw new Exception("No tienes permisos para gestionar este cliente");
            }
            
            // Registrar la llamada
            $llamadaId = $this->historialLlamadaModel->createLlamada($data);
            
            // Actualizar estado del cliente basado en la tipificación
            $this->historialLlamadaModel->actualizarEstadoCliente($data['cliente_cedula'], $data['tipificacion_id']);
            
            return [
                'success' => true,
                'message' => 'Llamada registrada exitosamente',
                'llamada_id' => $llamadaId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas del asesor
     */
    public function getEstadisticasAsesor($asesorCedula, $fechaInicio = null, $fechaFin = null) {
        try {
            $clientes = $this->clienteModel->getClientesByAsesor($asesorCedula);
            $estadisticasLlamadas = $this->historialLlamadaModel->getEstadisticasLlamadas($asesorCedula, $fechaInicio, $fechaFin);
            $resumenTipificaciones = $this->historialLlamadaModel->getResumenTipificaciones($asesorCedula, $fechaInicio, $fechaFin);
            $tareasPendientes = $this->tareaModel->getTareasPendientesByAsesor($asesorCedula);
            
            return [
                'success' => true,
                'data' => [
                    'clientes' => [
                        'total' => count($clientes),
                        'por_estado' => $this->agruparClientesPorEstado($clientes)
                    ],
                    'llamadas' => $estadisticasLlamadas,
                    'tipificaciones' => $resumenTipificaciones,
                    'tareas_pendientes' => count($tareasPendientes)
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener dashboard del asesor
     */
    public function getDashboardAsesor($asesorCedula) {
        try {
            $estadisticas = $this->getEstadisticasAsesor($asesorCedula);
            $clientes = $this->getClientesAsesor($asesorCedula);
            
            if (!$estadisticas['success'] || !$clientes['success']) {
                throw new Exception("Error al cargar datos del dashboard");
            }
            
            return [
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas['data'],
                    'clientes' => $clientes['data']
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Agrupar clientes por estado
     */
    private function agruparClientesPorEstado($clientes) {
        $estados = [];
        foreach ($clientes as $cliente) {
            $estado = $cliente['estado'];
            if (!isset($estados[$estado])) {
                $estados[$estado] = 0;
            }
            $estados[$estado]++;
        }
        return $estados;
    }
    
    /**
     * Buscar clientes del asesor
     */
    public function buscarClientes($asesorCedula, $termino) {
        try {
            $clientes = $this->clienteModel->getClientesByAsesor($asesorCedula);
            
            // Filtrar clientes por término de búsqueda
            $clientesFiltrados = array_filter($clientes, function($cliente) use ($termino) {
                $termino = strtolower($termino);
                return strpos(strtolower($cliente['nombre']), $termino) !== false ||
                       strpos(strtolower($cliente['apellido']), $termino) !== false ||
                       strpos(strtolower($cliente['email']), $termino) !== false ||
                       strpos(strtolower($cliente['empresa']), $termino) !== false;
            });
            
            return [
                'success' => true,
                'data' => array_values($clientesFiltrados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear ticket en tiketera
     */
    public function crearTicket($data) {
        try {
            $ticketId = $this->tiketeraModel->createTicket($data);
            
            return [
                'success' => true,
                'message' => 'Ticket creado exitosamente',
                'ticket_id' => $ticketId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener tickets del asesor
     */
    public function getTicketsAsesor($asesorCedula, $estado = null, $clienteCedula = null) {
        try {
            $tickets = $this->tiketeraModel->getTicketsByAsesor($asesorCedula, $estado, $clienteCedula);
            
            return [
                'success' => true,
                'data' => $tickets
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar estado del ticket
     */
    public function actualizarTicketEstado($ticketId, $nuevoEstado, $asesorCedula, $observaciones = null) {
        try {
            $this->tiketeraModel->updateTicketEstado($ticketId, $nuevoEstado, $asesorCedula, $observaciones);
            
            return [
                'success' => true,
                'message' => 'Estado del ticket actualizado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Editar ticket
     */
    public function editarTicket($ticketId, $asesorId, $datos) {
        try {
            // Verificar que el ticket pertenece al asesor
            $ticket = $this->tiketeraModel->getTicketById($ticketId, $asesorId);
            if (!$ticket) {
                throw new Exception('Ticket no encontrado');
            }
            
            if ($ticket['asesor_cedula'] !== $asesorId) {
                throw new Exception('No tienes permisos para editar este ticket');
            }
            
            // Actualizar ticket
            $result = $this->tiketeraModel->actualizarTicket($ticketId, $datos);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Ticket actualizado exitosamente'
                ];
            } else {
                throw new Exception('Error al actualizar el ticket');
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de tickets del asesor
     */
    public function getEstadisticasTickets($asesorCedula) {
        try {
            $db = getDB();

            $stmt = $db->prepare("
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
            $estadisticas = $stmt->fetch();

            $stmt = $db->prepare("
                SELECT
                    DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                    COUNT(*) as cantidad
                FROM tiketera
                WHERE asesor_cedula = ?
                AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                ORDER BY mes ASC
            ");
            $stmt->execute([$asesorCedula]);
            $tickets_por_mes = $stmt->fetchAll();

            $stmt = $db->prepare("
                SELECT
                    t.*,
                    c.nombre_completo as cliente_nombre
                FROM tiketera t
                LEFT JOIN clientes c ON t.cliente_cedula = c.cedula
                WHERE t.asesor_cedula = ?
                AND t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY t.fecha_creacion DESC
                LIMIT 10
            ");
            $stmt->execute([$asesorCedula]);
            $tickets_recientes = $stmt->fetchAll();

            $stmt = $db->prepare("
                SELECT
                    AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre)) as tiempo_promedio_horas
                FROM tiketera
                WHERE asesor_cedula = ?
                AND estado = 'cierre'
                AND fecha_cierre IS NOT NULL
            ");
            $stmt->execute([$asesorCedula]);
            $tiempo_promedio = $stmt->fetch();

            return [
                'total_tickets'             => (int) $estadisticas['total_tickets'],
                'tickets_abiertos'          => (int) $estadisticas['tickets_abiertos'],
                'tickets_comunicacion'      => (int) $estadisticas['tickets_comunicacion'],
                'tickets_validacion'        => (int) $estadisticas['tickets_validacion'],
                'tickets_proceso_judicial'  => (int) $estadisticas['tickets_proceso_judicial'],
                'tickets_remate'            => (int) $estadisticas['tickets_remate'],
                'tickets_recuperacion'      => (int) $estadisticas['tickets_recuperacion'],
                'tickets_cierre'            => (int) $estadisticas['tickets_cierre'],
                'tickets_por_mes'           => $tickets_por_mes,
                'tickets_recientes'         => $tickets_recientes,
                'tiempo_promedio_horas'     => $tiempo_promedio['tiempo_promedio_horas'] ? round($tiempo_promedio['tiempo_promedio_horas'], 2) : 0,
            ];

        } catch (Exception $e) {
            throw new Exception('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }
}
?>
