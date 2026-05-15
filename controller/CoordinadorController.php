<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ClienteModel.php';
require_once __DIR__ . '/../model/TitularModel.php';
require_once __DIR__ . '/../model/ArchivoCsvModel.php';
require_once __DIR__ . '/../model/TareaModel.php';
require_once __DIR__ . '/../model/MetricaModel.php';
require_once __DIR__ . '/../model/UserModel.php';

class CoordinadorController {
    private $clienteModel;
    private $titularModel;
    private $archivoCsvModel;
    private $tareaModel;
    private $metricaModel;
    private $userModel;
    
    public function __construct() {
        $this->clienteModel = new ClienteModel();
        $this->titularModel = new TitularModel();
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
            
            // Estadísticas de titulares (reparto) para el panel de tareas
            $data['clientes'] = $this->titularModel->getEstadisticasPorCoordinador($coordinadorId);
            
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
     * Listado para coordinador_tareas: titulares (reparto) del coordinador.
     */
    public function getClientes($coordinadorId, $busqueda = null) {
        try {
            $titulares = $this->titularModel->listarPorCoordinador($coordinadorId, $busqueda);
            
            return [
                'success' => true,
                'data' => $titulares
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Asignar titular (reparto) a asesor del mismo coordinador.
     */
    public function asignarTitularAAsesor($titularId, $asesorCedula, $coordinadorCedula) {
        try {
            $asesor = $this->userModel->getUserByCedula($asesorCedula);
            if (!$asesor || ($asesor['rol_nombre'] ?? '') !== 'asesor') {
                throw new Exception('Asesor no válido');
            }
            if (($asesor['coordinador_cedula'] ?? '') !== $coordinadorCedula) {
                throw new Exception('El asesor no pertenece a su equipo');
            }
            $this->titularModel->asignarAsesor((int) $titularId, $asesorCedula, $coordinadorCedula);
            
            return [
                'success' => true,
                'message' => 'Titular asignado al asesor correctamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Asignar cliente CRM a asesor (cédula PK en clientes).
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
     * Exportar reporte CSV según tipo (reparto, asignación, métricas, tickets).
     */
    public function exportarReporte(
        string $coordinadorId,
        string $tipoReporte,
        ?string $fechaInicio,
        ?string $fechaFin,
        ?string $filtroAsignacion = null
    ): array {
        try {
            $tipo = trim($tipoReporte);
            if ($tipo === '') {
                throw new Exception('Debe seleccionar un tipo de reporte');
            }

            $fechaInicio = $this->normalizarFechaExporte($fechaInicio);
            $fechaFin = $this->normalizarFechaExporte($fechaFin);
            if ($fechaInicio && $fechaFin && $fechaInicio > $fechaFin) {
                throw new Exception('La fecha de inicio no puede ser posterior a la fecha de fin');
            }

            switch ($tipo) {
                case 'titulares_reparto':
                    return $this->exportarCsvTitularesReparto($coordinadorId, $fechaInicio, $fechaFin, $filtroAsignacion);
                case 'resumen_asignacion':
                    return $this->exportarCsvResumenAsignacion($coordinadorId, $fechaInicio, $fechaFin);
                case 'metricas_asesores':
                    return $this->exportarCsvMetricasAsesores($coordinadorId, $fechaInicio, $fechaFin);
                case 'tickets_equipo':
                    return $this->exportarCsvTicketsEquipo($coordinadorId, $fechaInicio, $fechaFin);
                default:
                    throw new Exception('Tipo de reporte no válido');
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @deprecated Use exportarReporte('metricas_asesores', ...)
     */
    public function exportarMetricas($coordinadorId, $fechaInicio, $fechaFin) {
        return $this->exportarReporte($coordinadorId, 'metricas_asesores', $fechaInicio, $fechaFin);
    }

    /**
     * Vista previa: cantidad de registros del reporte.
     */
    public function previewExporte(
        string $coordinadorId,
        string $tipoReporte,
        ?string $fechaInicio,
        ?string $fechaFin,
        ?string $filtroAsignacion = null
    ): array {
        try {
            $tipo = trim($tipoReporte);
            $fechaInicio = $this->normalizarFechaExporte($fechaInicio);
            $fechaFin = $this->normalizarFechaExporte($fechaFin);

            $total = 0;
            switch ($tipo) {
                case 'titulares_reparto':
                    $total = $this->titularModel->contarParaExporteCoordinador(
                        $coordinadorId,
                        $fechaInicio,
                        $fechaFin,
                        $filtroAsignacion
                    );
                    break;
                case 'resumen_asignacion':
                    $rows = $this->titularModel->resumenAsignacionParaExporte($coordinadorId, $fechaInicio, $fechaFin);
                    $total = count($rows);
                    break;
                case 'metricas_asesores':
                    if ($fechaInicio && $fechaFin) {
                        $rows = $this->metricaModel->getMetricasParaExportar($coordinadorId, $fechaInicio, $fechaFin);
                        $total = count($rows);
                    }
                    break;
                case 'tickets_equipo':
                    $total = $this->contarTicketsEquipoParaExporte($coordinadorId, $fechaInicio, $fechaFin);
                    break;
                default:
                    throw new Exception('Tipo de reporte no válido');
            }

            return [
                'success' => true,
                'total' => $total,
                'tipo_reporte' => $tipo,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function normalizarFechaExporte(?string $fecha): ?string {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }
        $fecha = trim($fecha);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new Exception('Formato de fecha inválido (use AAAA-MM-DD)');
        }
        return $fecha;
    }

    private function asegurarDirectorioExportaciones(): string {
        $exportDirFs = __DIR__ . '/../uploads/exports/';
        if (!is_dir($exportDirFs)) {
            mkdir($exportDirFs, 0755, true);
        }
        return $exportDirFs;
    }

    /**
     * @param list<string> $encabezados
     * @param list<array<string, mixed>> $filas
     * @param list<string> $columnasOrden claves por fila en el mismo orden que encabezados
     */
    private function escribirArchivoCsv(string $prefijoNombre, array $encabezados, array $filas, array $columnasOrden): array {
        if (empty($filas)) {
            throw new Exception('No hay datos para exportar con los filtros seleccionados');
        }

        $exportDirFs = $this->asegurarDirectorioExportaciones();
        $nombreArchivo = $prefijoNombre . '_' . date('Y-m-d_His') . '.csv';
        $rutaArchivo = $exportDirFs . $nombreArchivo;

        $handle = fopen($rutaArchivo, 'w');
        if (!$handle) {
            throw new Exception('Error al crear archivo de exportación');
        }

        fprintf($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $encabezados);
        foreach ($filas as $fila) {
            $linea = [];
            foreach ($columnasOrden as $col) {
                $linea[] = $fila[$col] ?? '';
            }
            fputcsv($handle, $linea);
        }
        fclose($handle);

        return [
            'success' => true,
            'message' => 'Archivo exportado exitosamente',
            'archivo' => $nombreArchivo,
            'ruta' => $rutaArchivo,
            'registros' => count($filas),
        ];
    }

    private function exportarCsvTitularesReparto(
        string $coordinadorId,
        ?string $fechaInicio,
        ?string $fechaFin,
        ?string $filtroAsignacion
    ): array {
        $filas = $this->titularModel->filasParaExporteCoordinador(
            $coordinadorId,
            $fechaInicio,
            $fechaFin,
            $filtroAsignacion
        );

        $encabezados = [
            'ID titular', 'Primer nombre', 'Apellido', 'Reg_Int', 'BaseD', 'F_Correo', 'Agente', 'Prioridad',
            'Asesor cédula', 'Asesor nombre', 'Fecha registro', 'Fecha actualización',
            'Case Number', 'Parcel Number', 'Tipo foreclosure', 'Condado', 'Fuente', 'Fecha venta', 'Días transcurridos',
            'Excedente', 'Puja apertura', 'Puja cierre', 'Monetización',
            'Mailing calle', 'Mailing ciudad', 'Mailing estado', 'Mailing CP',
            'Propiedad calle', 'Propiedad ciudad', 'Propiedad estado', 'Propiedad CP',
            'Edad', 'Fallecido', 'Teléfonos', 'Correos',
        ];
        $columnas = [
            'id_cliente', 'primer_nombre', 'apellido', 'reg_int', 'base_d', 'f_correo', 'agente', 'prioridad',
            'asesor_cedula', 'asesor_nombre', 'fecha_registro', 'fecha_actualizacion',
            'numero_caso', 'numero_parcela', 'tipo_foreclosure', 'condado', 'fuente', 'fecha_venta', 'dias_transcurridos',
            'excedente', 'puja_apertura', 'puja_cierre', 'monetizacion',
            'mailing_calle', 'mailing_ciudad', 'mailing_estado', 'mailing_codigo_postal',
            'propiedad_calle', 'propiedad_ciudad', 'propiedad_estado', 'propiedad_codigo_postal',
            'edad', 'fallecido', 'telefonos', 'correos',
        ];

        return $this->escribirArchivoCsv('titulares_reparto', $encabezados, $filas, $columnas);
    }

    private function exportarCsvResumenAsignacion(
        string $coordinadorId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        $filas = $this->titularModel->resumenAsignacionParaExporte($coordinadorId, $fechaInicio, $fechaFin);
        return $this->escribirArchivoCsv(
            'resumen_asignacion',
            ['Asesor cédula', 'Asesor', 'Total titulares'],
            $filas,
            ['asesor_cedula', 'asesor_nombre', 'total_titulares']
        );
    }

    private function exportarCsvMetricasAsesores(
        string $coordinadorId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        if (!$fechaInicio || !$fechaFin) {
            throw new Exception('Las métricas por asesor requieren fecha de inicio y fin');
        }
        $filas = $this->metricaModel->getMetricasParaExportar($coordinadorId, $fechaInicio, $fechaFin);
        return $this->escribirArchivoCsv(
            'metricas_asesores',
            [
                'Asesor', 'Email', 'Fecha', 'Clientes asignados', 'Clientes contactados',
                'Llamadas', 'Emails', 'Reuniones', 'Tareas completadas', 'Convertidos', 'Ingresos',
                '% contacto', '% efectividad',
            ],
            $filas,
            [
                'asesor_nombre', 'asesor_email', 'fecha_reporte', 'clientes_asignados', 'clientes_contactados',
                'llamadas_realizadas', 'emails_enviados', 'reuniones_realizadas', 'tareas_completadas',
                'clientes_convertidos', 'ingresos_generados', 'porcentaje_contacto', 'porcentaje_efectividad',
            ]
        );
    }

    private function exportarCsvTicketsEquipo(
        string $coordinadorId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        $filas = $this->filasTicketsEquipoParaExporte($coordinadorId, $fechaInicio, $fechaFin);
        return $this->escribirArchivoCsv(
            'tickets_equipo',
            [
                'ID ticket', 'Número ticket', 'Estado', 'Título', 'Cliente cédula',
                'Asesor cédula', 'Asesor', 'Fecha creación', 'Fecha actualización', 'Fecha cierre', 'Origen',
            ],
            $filas,
            [
                'id', 'numero_ticket', 'estado', 'titulo', 'cliente_cedula',
                'asesor_cedula', 'asesor_nombre', 'fecha_creacion', 'fecha_actualizacion', 'fecha_cierre', 'origen',
            ]
        );
    }

    private function contarTicketsEquipoParaExporte(
        string $coordinadorId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): int {
        $db = getDB();
        $sql = "
            SELECT COUNT(*)
            FROM tiketera tk
            INNER JOIN usuarios u ON tk.asesor_cedula = u.cedula
            WHERE u.coordinador_cedula = ?
        ";
        $params = [$coordinadorId];
        if ($fechaInicio) {
            $sql .= ' AND DATE(tk.fecha_creacion) >= ?';
            $params[] = $fechaInicio;
        }
        if ($fechaFin) {
            $sql .= ' AND DATE(tk.fecha_creacion) <= ?';
            $params[] = $fechaFin;
        }
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function filasTicketsEquipoParaExporte(
        string $coordinadorId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        $db = getDB();
        $sql = "
            SELECT
                tk.id,
                tk.numero_ticket,
                tk.estado,
                tk.titulo,
                tk.cliente_cedula,
                tk.asesor_cedula,
                TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))) AS asesor_nombre,
                DATE_FORMAT(tk.fecha_creacion, '%Y-%m-%d %H:%i') AS fecha_creacion,
                DATE_FORMAT(tk.fecha_actualizacion, '%Y-%m-%d %H:%i') AS fecha_actualizacion,
                DATE_FORMAT(tk.fecha_cierre, '%Y-%m-%d %H:%i') AS fecha_cierre,
                tk.origen
            FROM tiketera tk
            INNER JOIN usuarios u ON tk.asesor_cedula = u.cedula
            WHERE u.coordinador_cedula = ?
        ";
        $params = [$coordinadorId];
        if ($fechaInicio) {
            $sql .= ' AND DATE(tk.fecha_creacion) >= ?';
            $params[] = $fechaInicio;
        }
        if ($fechaFin) {
            $sql .= ' AND DATE(tk.fecha_creacion) <= ?';
            $params[] = $fechaFin;
        }
        $sql .= ' ORDER BY tk.fecha_creacion DESC, tk.id DESC';

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('No se pudo consultar la tiketera (verifique que la tabla exista)');
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
     * Asignar titulares sin asesor repartiendo en round-robin entre los asesores del coordinador.
     *
     * @param string $coordinadorCedula Cédula del coordinador (sesión)
     * @param int $cantidad Cuántos titulares asignar
     */
    public function asignarTitularesAutomatico($coordinadorCedula, $cantidad, $notas = '') {
        $db = getDB();
        try {
            $asesoresRes = $this->getAsesores($coordinadorCedula);
            if (!$asesoresRes['success']) {
                throw new Exception($asesoresRes['message'] ?? 'No se pudieron obtener asesores');
            }
            $asesores = $asesoresRes['data'] ?? [];
            if (count($asesores) === 0) {
                throw new Exception('No hay asesores en su equipo');
            }
            
            $titularesDisponibles = $this->titularModel->listarDisponiblesPorCoordinador($coordinadorCedula, (int) $cantidad);
            
            if (count($titularesDisponibles) < $cantidad) {
                throw new Exception('No hay suficientes titulares sin asesor. Disponibles: ' . count($titularesDisponibles) . ', solicitados: ' . (int) $cantidad);
            }
            
            $db->beginTransaction();
            $asignados = 0;
            $errores = [];
            
            foreach ($titularesDisponibles as $i => $row) {
                try {
                    $asesor = $asesores[$i % count($asesores)];
                    $this->titularModel->asignarAsesor((int) $row['id_cliente'], $asesor['cedula'], $coordinadorCedula);
                    $asignados++;
                } catch (Exception $e) {
                    $errores[] = 'Titular ' . ($row['id_cliente'] ?? '?') . ': ' . $e->getMessage();
                }
            }
            
            $db->commit();
            
            $mensaje = "Se asignaron $asignados titular(es) entre " . count($asesores) . ' asesor(es).';
            if (!empty($errores)) {
                $mensaje .= ' Errores: ' . implode('; ', $errores);
            }
            
            return [
                'success' => true,
                'message' => $mensaje,
                'asignados' => $asignados,
                'errores' => count($errores),
            ];
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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
