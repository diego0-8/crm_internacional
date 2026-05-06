<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ClienteModel.php';
require_once __DIR__ . '/../model/ArchivoCsvModel.php';
require_once __DIR__ . '/../model/TareaModel.php';
require_once __DIR__ . '/../model/MetricaModel.php';
require_once __DIR__ . '/../model/UserModel.php';

class CoordinadorController {
    private $clienteModel;
    private $archivoCsvModel;
    private $tareaModel;
    private $metricaModel;
    private $userModel;
    
    public function __construct() {
        $this->clienteModel = new ClienteModel();
        $this->archivoCsvModel = new ArchivoCsvModel();
        $this->tareaModel = new TareaModel();
        $this->metricaModel = new MetricaModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Obtener dashboard del coordinador
     */
    public function getDashboardData($coordinadorId) {
        try {
            $data = [];
            
            // Estadísticas de clientes
            $data['clientes'] = $this->clienteModel->getEstadisticasClientes($coordinadorId);
            
            // Estadísticas de archivos CSV
            $data['archivos'] = $this->archivoCsvModel->getEstadisticasArchivos($coordinadorId);
            
            // Estadísticas de tareas
            $data['tareas'] = $this->tareaModel->getEstadisticasTareas($coordinadorId);
            
            // Resumen de métricas
            $data['metricas'] = $this->metricaModel->getResumenMetricas($coordinadorId);
            
            // Asesores asignados con métricas detalladas
            $data['asesores'] = $this->getAsesoresConMetricas($coordinadorId);
            
            // Estadísticas generales de tickets
            $data['tickets'] = $this->getEstadisticasTicketsGenerales($coordinadorId);
            
            // Estadísticas de rendimiento
            $data['rendimiento'] = $this->getEstadisticasRendimiento($coordinadorId);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener asesores con métricas detalladas
     */
    private function getAsesoresConMetricas($coordinadorId) {
        try {
            $db = getDB();
            
            // Obtener asesores asignados al coordinador
            $stmt = $db->prepare("
                SELECT u.cedula, u.nombre, u.apellido, u.email, u.activo,
                       COUNT(c.cedula) as total_clientes,
                       COUNT(CASE WHEN c.estado = 'activo' THEN 1 END) as clientes_activos,
                       COUNT(CASE WHEN c.estado = 'inactivo' THEN 1 END) as clientes_inactivos
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN clientes c ON u.cedula = c.asesor_cedula
                WHERE r.nombre = 'asesor' 
                AND u.coordinador_cedula = ?
                GROUP BY u.cedula, u.nombre, u.apellido, u.email, u.activo
                ORDER BY total_clientes DESC
            ");
            $stmt->execute([$coordinadorId]);
            $asesores = $stmt->fetchAll();
            
            // Agregar métricas de tickets para cada asesor
            foreach ($asesores as &$asesor) {
                $ticketsStats = $this->getEstadisticasTicketsAsesor($asesor['cedula']);
                $asesor['tickets'] = $ticketsStats;
                
                // Calcular métricas de rendimiento
                $asesor['rendimiento'] = $this->calcularRendimientoAsesor($asesor['cedula']);
            }
            
            return $asesores;
        } catch (Exception $e) {
            throw new Exception("Error obteniendo asesores con métricas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de tickets para un asesor
     */
    private function getEstadisticasTicketsAsesor($asesorCedula) {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN estado = 'comunicacion'     THEN 1 END) as tickets_comunicacion,
                    COUNT(CASE WHEN estado = 'validacion'       THEN 1 END) as tickets_validacion,
                    COUNT(CASE WHEN estado = 'proceso_judicial' THEN 1 END) as tickets_proceso_judicial,
                    COUNT(CASE WHEN estado = 'remate'           THEN 1 END) as tickets_remate,
                    COUNT(CASE WHEN estado = 'recuperacion'     THEN 1 END) as tickets_recuperacion,
                    COUNT(CASE WHEN estado = 'cierre'           THEN 1 END) as tickets_cierre,
                    COUNT(CASE WHEN estado <> 'cierre'          THEN 1 END) as tickets_abiertos,
                    AVG(CASE WHEN estado = 'cierre' THEN
                        TIMESTAMPDIFF(HOUR, fecha_creacion, COALESCE(fecha_cierre, fecha_actualizacion))
                    END) as tiempo_promedio_resolucion
                FROM tiketera
                WHERE asesor_cedula = ?
            ");
            $stmt->execute([$asesorCedula]);
            $stats = $stmt->fetch();

            $defaults = [
                'total_tickets'             => 0,
                'tickets_abiertos'          => 0,
                'tickets_comunicacion'      => 0,
                'tickets_validacion'        => 0,
                'tickets_proceso_judicial'  => 0,
                'tickets_remate'            => 0,
                'tickets_recuperacion'      => 0,
                'tickets_cierre'            => 0,
                'tiempo_promedio_resolucion' => 0,
            ];
            return $stats ?: $defaults;
        } catch (Exception $e) {
            return [
                'total_tickets'             => 0,
                'tickets_abiertos'          => 0,
                'tickets_comunicacion'      => 0,
                'tickets_validacion'        => 0,
                'tickets_proceso_judicial'  => 0,
                'tickets_remate'            => 0,
                'tickets_recuperacion'      => 0,
                'tickets_cierre'            => 0,
                'tiempo_promedio_resolucion' => 0,
            ];
        }
    }
    
    /**
     * Obtener estadísticas generales de tickets
     */
    private function getEstadisticasTicketsGenerales($coordinadorId) {
        try {
            $db = getDB();
            
            // Obtener todos los asesores del coordinador
            $stmt = $db->prepare("
                SELECT u.cedula FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                WHERE r.nombre = 'asesor' AND u.coordinador_cedula = ?
            ");
            $stmt->execute([$coordinadorId]);
            $asesores = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $defaults = [
                'total_tickets'            => 0,
                'tickets_abiertos'         => 0,
                'tickets_comunicacion'     => 0,
                'tickets_validacion'       => 0,
                'tickets_proceso_judicial' => 0,
                'tickets_remate'           => 0,
                'tickets_recuperacion'     => 0,
                'tickets_cierre'           => 0,
            ];

            if (empty($asesores)) {
                return $defaults;
            }

            $placeholders = str_repeat('?,', count($asesores) - 1) . '?';

            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN estado = 'comunicacion'     THEN 1 END) as tickets_comunicacion,
                    COUNT(CASE WHEN estado = 'validacion'       THEN 1 END) as tickets_validacion,
                    COUNT(CASE WHEN estado = 'proceso_judicial' THEN 1 END) as tickets_proceso_judicial,
                    COUNT(CASE WHEN estado = 'remate'           THEN 1 END) as tickets_remate,
                    COUNT(CASE WHEN estado = 'recuperacion'     THEN 1 END) as tickets_recuperacion,
                    COUNT(CASE WHEN estado = 'cierre'           THEN 1 END) as tickets_cierre,
                    COUNT(CASE WHEN estado <> 'cierre'          THEN 1 END) as tickets_abiertos
                FROM tiketera
                WHERE asesor_cedula IN ($placeholders)
            ");
            $stmt->execute($asesores);
            $stats = $stmt->fetch();
            return $stats ?: $defaults;
        } catch (Exception $e) {
            return [
                'total_tickets'            => 0,
                'tickets_abiertos'         => 0,
                'tickets_comunicacion'     => 0,
                'tickets_validacion'       => 0,
                'tickets_proceso_judicial' => 0,
                'tickets_remate'           => 0,
                'tickets_recuperacion'     => 0,
                'tickets_cierre'           => 0,
            ];
        }
    }

    /**
     * Calcular rendimiento de un asesor
     */
    private function calcularRendimientoAsesor($asesorCedula) {
        try {
            $db = getDB();
            
            // Obtener métricas del último mes
            $stmt = $db->prepare("
                SELECT
                    COUNT(DISTINCT c.cedula) as clientes_contactados,
                    COUNT(t.id) as tickets_creados,
                    COUNT(CASE WHEN t.estado = 'cierre' THEN 1 END) as tickets_resueltos,
                    AVG(CASE WHEN t.estado = 'cierre' THEN
                        TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, t.fecha_actualizacion))
                    END) as tiempo_promedio_resolucion
                FROM clientes c
                LEFT JOIN tiketera t ON c.asesor_cedula = t.asesor_cedula
                WHERE c.asesor_cedula = ?
                AND c.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$asesorCedula]);
            $metricas = $stmt->fetch();

            // Si no hay resultados, devolver valores por defecto
            if (!$metricas) {
                $metricas = [
                    'clientes_contactados' => 0,
                    'tickets_creados' => 0,
                    'tickets_resueltos' => 0,
                    'tiempo_promedio_resolucion' => 0
                ];
            }
            
            // Calcular porcentajes
            $ticketsResueltos = $metricas['tickets_resueltos'] ?? 0;
            $ticketsCreados = $metricas['tickets_creados'] ?? 0;
            $efectividad = $ticketsCreados > 0 ? round(($ticketsResueltos / $ticketsCreados) * 100, 2) : 0;
            
            return [
                'clientes_contactados' => $metricas['clientes_contactados'] ?? 0,
                'tickets_creados' => $ticketsCreados,
                'tickets_resueltos' => $ticketsResueltos,
                'efectividad' => $efectividad,
                'tiempo_promedio_resolucion' => round($metricas['tiempo_promedio_resolucion'] ?? 0, 2)
            ];
        } catch (Exception $e) {
            return [
                'clientes_contactados' => 0,
                'tickets_creados' => 0,
                'tickets_resueltos' => 0,
                'efectividad' => 0,
                'tiempo_promedio_resolucion' => 0
            ];
        }
    }
    
    /**
     * Obtener estadísticas de rendimiento general
     */
    private function getEstadisticasRendimiento($coordinadorId) {
        try {
            $db = getDB();
            
            // Obtener métricas del último mes
            $stmt = $db->prepare("
                SELECT
                    COUNT(DISTINCT c.cedula) as total_clientes_activos,
                    COUNT(DISTINCT t.id) as total_tickets_creados,
                    COUNT(CASE WHEN t.estado = 'cierre' THEN 1 END) as total_tickets_resueltos,
                    AVG(CASE WHEN t.estado = 'cierre' THEN
                        TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, t.fecha_actualizacion))
                    END) as tiempo_promedio_resolucion_general,
                    COUNT(DISTINCT CASE WHEN c.asesor_cedula IS NOT NULL THEN c.asesor_cedula END) as asesores_activos
                FROM clientes c
                LEFT JOIN tiketera t ON c.asesor_cedula = t.asesor_cedula
                WHERE c.coordinador_cedula = ?
                AND c.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$coordinadorId]);
            $metricas = $stmt->fetch();

            // Si no hay resultados, devolver valores por defecto
            if (!$metricas) {
                $metricas = [
                    'total_clientes_activos' => 0,
                    'total_tickets_creados' => 0,
                    'total_tickets_resueltos' => 0,
                    'tiempo_promedio_resolucion_general' => 0,
                    'asesores_activos' => 0
                ];
            }
            
            // Calcular porcentajes
            $ticketsResueltos = $metricas['total_tickets_resueltos'] ?? 0;
            $ticketsCreados = $metricas['total_tickets_creados'] ?? 0;
            $efectividadGeneral = $ticketsCreados > 0 ? round(($ticketsResueltos / $ticketsCreados) * 100, 2) : 0;
            
            return [
                'total_clientes_activos' => $metricas['total_clientes_activos'] ?? 0,
                'total_tickets_creados' => $ticketsCreados,
                'total_tickets_resueltos' => $ticketsResueltos,
                'efectividad_general' => $efectividadGeneral,
                'tiempo_promedio_resolucion_general' => round($metricas['tiempo_promedio_resolucion_general'] ?? 0, 2),
                'asesores_activos' => $metricas['asesores_activos'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'total_clientes_activos' => 0,
                'total_tickets_creados' => 0,
                'total_tickets_resueltos' => 0,
                'efectividad_general' => 0,
                'tiempo_promedio_resolucion_general' => 0,
                'asesores_activos' => 0
            ];
        }
    }
    
    /**
     * Procesar archivo CSV
     */
    public function procesarArchivoCsv($archivo, $coordinadorId) {
        try {
            // Validar archivo
            if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
                throw new Exception("No se ha subido ningún archivo");
            }
            
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                throw new Exception("El archivo debe ser un CSV");
            }
            
            // Crear directorio de uploads si no existe
            $uploadDir = '../uploads/csv/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generar nombre único para el archivo
            $nombreArchivo = uniqid() . '_' . $archivo['name'];
            $rutaArchivo = $uploadDir . $nombreArchivo;
            
            // Mover archivo
            if (!move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) {
                throw new Exception("Error al subir el archivo");
            }
            
            // Leer archivo CSV
            $handle = fopen($rutaArchivo, 'r');
            if (!$handle) {
                throw new Exception("Error al leer el archivo");
            }
            
            $headers = fgetcsv($handle);
            $totalRegistros = 0;
            $registrosProcesados = 0;
            
            // Crear registro del archivo
            $archivoData = [
                'nombre_archivo' => $archivo['name'],
                'ruta_archivo' => $rutaArchivo,
                'coordinador_cedula' => $coordinadorId,
                'estado' => 'procesando'
            ];
            
            $archivoId = $this->archivoCsvModel->createArchivo($archivoData);
            
            // Procesar cada línea del CSV
            while (($data = fgetcsv($handle)) !== false) {
                $totalRegistros++;
                
                try {
                    // Mapear datos del CSV (ajustar según estructura del archivo)
                    $clienteData = [
                        'nombre' => $data[0] ?? '',
                        'apellido' => $data[1] ?? '',
                        'email' => $data[2] ?? null,
                        'telefono' => $data[3] ?? null,
                        'empresa' => $data[4] ?? null,
                        'cargo' => $data[5] ?? null,
                        'direccion' => $data[6] ?? null,
                        'ciudad' => $data[7] ?? null,
                        'pais' => $data[8] ?? null,
                        'codigo_postal' => $data[9] ?? null,
                        'coordinador_cedula' => $coordinadorId,
                        'archivo_csv_id' => $archivoId,
                        'estado' => 'nuevo'
                    ];
                    
                    $this->clienteModel->createCliente($clienteData);
                    $registrosProcesados++;
                } catch (Exception $e) {
                    // Continuar con el siguiente registro si hay error
                    continue;
                }
            }
            
            fclose($handle);
            
            // Actualizar estado del archivo
            $this->archivoCsvModel->updateEstado($archivoId, 'completado', $registrosProcesados);
            
            return [
                'success' => true,
                'message' => "Archivo procesado exitosamente. $registrosProcesados de $totalRegistros registros importados.",
                'archivo_id' => $archivoId,
                'total_registros' => $totalRegistros,
                'registros_procesados' => $registrosProcesados
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener clientes
     */
    public function getClientes($coordinadorId, $busqueda = null) {
        try {
            if ($busqueda) {
                $clientes = $this->clienteModel->buscarClientes($coordinadorId, $busqueda);
            } else {
                $clientes = $this->clienteModel->getClientesByCoordinador($coordinadorId);
            }
            
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
     * Asignar cliente a asesor
     */
    public function asignarClienteAAsesor($clienteId, $asesorId) {
        try {
            $this->clienteModel->asignarClienteAAsesor($clienteId, $asesorId);
            
            return [
                'success' => true,
                'message' => 'Cliente asignado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener tareas
     */
    public function getTareas($coordinadorId) {
        try {
            $tareas = $this->tareaModel->getTareasByCoordinador($coordinadorId);
            
            return [
                'success' => true,
                'data' => $tareas
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear tarea
     */
    public function createTarea($data) {
        try {
            $tareaId = $this->tareaModel->createTarea($data);
            
            return [
                'success' => true,
                'message' => 'Tarea creada exitosamente',
                'tarea_id' => $tareaId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener métricas de asesores
     */
    public function getMetricasAsesores($coordinadorId, $fechaInicio = null, $fechaFin = null) {
        try {
            $metricas = $this->metricaModel->getMetricasByCoordinador($coordinadorId, $fechaInicio, $fechaFin);
            
            return [
                'success' => true,
                'data' => $metricas
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Exportar métricas a CSV
     */
    public function exportarMetricas($coordinadorId, $fechaInicio, $fechaFin) {
        try {
            $metricas = $this->metricaModel->getMetricasParaExportar($coordinadorId, $fechaInicio, $fechaFin);
            
            if (empty($metricas)) {
                throw new Exception("No hay datos para exportar en el rango de fechas seleccionado");
            }
            
            // Crear directorio de exportaciones si no existe
            $exportDirFs = __DIR__ . '/../uploads/exports/';
            if (!is_dir($exportDirFs)) {
                mkdir($exportDirFs, 0755, true);
            }
            
            $nombreArchivo = 'metricas_' . date('Y-m-d_H-i-s') . '.csv';
            $rutaArchivo = $exportDirFs . $nombreArchivo;
            
            $handle = fopen($rutaArchivo, 'w');
            if (!$handle) {
                throw new Exception("Error al crear archivo de exportación");
            }
            
            // Escribir encabezados
            fputcsv($handle, [
                'Asesor', 'Email', 'Fecha', 'Clientes Asignados', 'Clientes Contactados',
                'Llamadas Realizadas', 'Emails Enviados', 'Reuniones Realizadas',
                'Tareas Completadas', 'Clientes Convertidos', 'Ingresos Generados',
                '% Contacto', '% Efectividad'
            ]);
            
            // Escribir datos
            foreach ($metricas as $metrica) {
                fputcsv($handle, [
                    $metrica['asesor_nombre'],
                    $metrica['asesor_email'],
                    $metrica['fecha_reporte'],
                    $metrica['clientes_asignados'],
                    $metrica['clientes_contactados'],
                    $metrica['llamadas_realizadas'],
                    $metrica['emails_enviados'],
                    $metrica['reuniones_realizadas'],
                    $metrica['tareas_completadas'],
                    $metrica['clientes_convertidos'],
                    $metrica['ingresos_generados'],
                    $metrica['porcentaje_contacto'],
                    $metrica['porcentaje_efectividad']
                ]);
            }
            
            fclose($handle);
            
            return [
                'success' => true,
                'message' => 'Archivo exportado exitosamente',
                'archivo' => $nombreArchivo,
                'ruta' => $rutaArchivo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener archivos CSV subidos
     */
    public function getArchivosCsv($coordinadorId) {
        try {
            $archivos = $this->archivoCsvModel->getArchivosByCoordinador($coordinadorId);
            
            return [
                'success' => true,
                'data' => $archivos
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar archivo CSV
     */
    public function eliminarArchivoCsv($archivoId) {
        try {
            $this->archivoCsvModel->deleteArchivo($archivoId);
            
            return [
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener asesores asignados al coordinador
     */
    public function getAsesoresAsignados($coordinadorId) {
        try {
            $asesores = $this->userModel->getUsersByRole('asesor');
            $asesoresAsignados = array_filter($asesores, function($asesor) use ($coordinadorId) {
                return $asesor['coordinador_cedula'] == $coordinadorId;
            });
            
            return [
                'success' => true,
                'data' => array_values($asesoresAsignados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Asignar múltiples clientes a un asesor
     */
    public function asignarClientesMasivo($clienteIds, $asesorCedula, $notas = '') {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            $asesor = $this->userModel->getUserByCedula($asesorCedula);
            if (!$asesor || $asesor['rol_nombre'] !== 'asesor') {
                throw new Exception("Asesor no válido");
            }
            
            $asignados = 0;
            $errores = [];
            
            foreach ($clienteIds as $clienteId) {
                try {
                    $this->clienteModel->asignarClienteAAsesor($clienteId, $asesorCedula);
                    $asignados++;
                } catch (Exception $e) {
                    $errores[] = "Cliente ID $clienteId: " . $e->getMessage();
                }
            }
            
            $db->commit();
            
            $mensaje = "Se asignaron $asignados clientes exitosamente";
            if (!empty($errores)) {
                $mensaje .= ". Errores: " . implode(', ', $errores);
            }
            
            return [
                'success' => true,
                'message' => $mensaje,
                'asignados' => $asignados,
                'errores' => count($errores)
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Asignar clientes automáticamente a un asesor
     */
    public function asignarClientesAutomatico($asesorCedula, $cantidad, $notas = '') {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // Verificar que el asesor existe
            $asesor = $this->userModel->getUserByCedula($asesorCedula);
            if (!$asesor || $asesor['rol_nombre'] !== 'asesor') {
                throw new Exception("Asesor no válido");
            }
            
            // Obtener clientes disponibles (sin asesor asignado)
            $clientesDisponibles = $this->clienteModel->getClientesDisponibles($cantidad);
            
            if (count($clientesDisponibles) < $cantidad) {
                throw new Exception("No hay suficientes clientes disponibles. Disponibles: " . count($clientesDisponibles) . ", Solicitados: $cantidad");
            }
            
            $asignados = 0;
            $errores = [];
            
            // Asignar los clientes
            for ($i = 0; $i < $cantidad; $i++) {
                try {
                    $cliente = $clientesDisponibles[$i];
                    $this->clienteModel->asignarClienteAAsesor($cliente['id'], $asesorCedula);
                    $asignados++;
                } catch (Exception $e) {
                    $errores[] = "Cliente ID {$cliente['id']}: " . $e->getMessage();
                }
            }
            
            $db->commit();
            
            $mensaje = "Se asignaron $asignados clientes al asesor {$asesor['nombre']} {$asesor['apellido']} exitosamente";
            if (!empty($errores)) {
                $mensaje .= ". Errores: " . implode(', ', $errores);
            }
            
            return [
                'success' => true,
                'message' => $mensaje,
                'asignados' => $asignados,
                'errores' => count($errores),
                'asesor' => $asesor
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear nuevo cliente manualmente
     */
    public function crearCliente($clienteData) {
        try {
            // Verificar que la cédula no existe
            $clienteExistente = $this->clienteModel->getClienteByCedula($clienteData['cedula']);
            if ($clienteExistente) {
                throw new Exception("Ya existe un cliente con la cédula {$clienteData['cedula']}");
            }
            
            // Si se especifica un asesor, verificar que existe
            if (!empty($clienteData['asesor_cedula'])) {
                $asesor = $this->userModel->getUserByCedula($clienteData['asesor_cedula']);
                if (!$asesor || $asesor['rol_nombre'] !== 'asesor') {
                    throw new Exception("El asesor especificado no es válido");
                }
            }
            
            // Crear el cliente
            $clienteId = $this->clienteModel->createCliente($clienteData);
            
            // Log de actividad
            logActivity('cliente_created', "Cliente creado: {$clienteData['nombre']} {$clienteData['apellido']} (Cédula: {$clienteData['cedula']})");
            
            return [
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'cliente_id' => $clienteId,
                'cliente' => [
                    'cedula' => $clienteData['cedula'],
                    'nombre' => $clienteData['nombre'],
                    'apellido' => $clienteData['apellido'],
                    'email' => $clienteData['email'],
                    'empresa' => $clienteData['empresa'],
                    'estado' => $clienteData['estado']
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
     * Obtener asesores asignados al coordinador
     */
    public function getAsesores($coordinadorId) {
        try {
            $asesores = $this->userModel->getUsersByRole('asesor');
            
            // Filtrar solo los asesores asignados a este coordinador
            $asesoresAsignados = array_filter($asesores, function($asesor) use ($coordinadorId) {
                return $asesor['coordinador_cedula'] == $coordinadorId;
            });
            
            return [
                'success' => true,
                'data' => array_values($asesoresAsignados)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener archivos CSV del coordinador
     */
    public function getArchivos($coordinadorId) {
        try {
            $archivos = $this->archivoCsvModel->getArchivosByCoordinador($coordinadorId);
            
            return [
                'success' => true,
                'data' => $archivos
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>
