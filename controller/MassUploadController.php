<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ArchivoCsvModel.php';
require_once __DIR__ . '/../model/ClienteModel.php';

class MassUploadController {
    private $archivoCsvModel;
    private $clienteModel;
    
    public function __construct() {
        $this->archivoCsvModel = new ArchivoCsvModel();
        $this->clienteModel = new ClienteModel();
    }
    
    /**
     * Procesar archivo CSV inmediatamente
     */
    public function procesarArchivoInmediato($archivo, $coordinadorId) {
        try {
            // Validar archivo
            $tmpName = $archivo['tmp_name'] ?? null;
            if (!$tmpName) {
                throw new Exception("No se ha subido ningún archivo");
            }
            $esSubidaHttp = is_uploaded_file($tmpName);
            $esArchivoLocal = (PHP_SAPI === 'cli' && file_exists($tmpName) && is_readable($tmpName));
            if (!$esSubidaHttp && !$esArchivoLocal) {
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
            $nombreOriginal = (string) ($archivo['name'] ?? 'upload.csv');
            $nombreSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
            $nombreArchivo = uniqid('csv_', true) . '_' . $nombreSeguro;
            $rutaArchivo = $uploadDir . $nombreArchivo;
            
            // Mover archivo
            if ($esSubidaHttp) {
                if (!move_uploaded_file($tmpName, $rutaArchivo)) {
                    throw new Exception("Error al subir el archivo");
                }
            } else {
                if (!copy($tmpName, $rutaArchivo)) {
                    throw new Exception("Error al copiar el archivo");
                }
            }
            
            // Leer archivo CSV
            $handle = fopen($rutaArchivo, 'r');
            if (!$handle) {
                throw new Exception("Error al leer el archivo");
            }
            
            $headers = fgetcsv($handle);
            $totalRegistros = 0;
            $registrosProcesados = 0;
            $errores = [];
            
            // Crear registro del archivo
            $archivoData = [
                'nombre_archivo' => $archivo['name'],
                'ruta_archivo' => $rutaArchivo,
                'coordinador_cedula' => $coordinadorId,
                'estado' => 'procesando',
                'total_registros' => 0,
                'registros_procesados' => 0
            ];
            
            $archivoId = $this->archivoCsvModel->createArchivo($archivoData);
            
            // Procesar cada línea del CSV
            while (($data = fgetcsv($handle)) !== false) {
                $totalRegistros++;

                // Log the raw data for debugging
                // debug logs removidos para evitar filtrar información sensible

                try {
                    // Mapear datos del CSV según formato esperado
                    $clienteData = [
                        'cedula' => $this->generarCedulaUnica(), // Generar cédula única
                        'nombre_completo' => trim($data[0] ?? ''), // Nombre completo desde primera columna
                        'email' => trim($data[1] ?? '') ?: null,
                        'telefono' => trim($data[2] ?? '') ?: null,
                        'direccion' => trim($data[3] ?? '') ?: null,
                        'ciudad' => trim($data[4] ?? '') ?: null,
                        'pais' => trim($data[5] ?? '') ?: null,
                        'coordinador_cedula' => $coordinadorId,
                        'archivo_csv_id' => $archivoId,
                        'estado' => 'nuevo'
                    ];

                    // debug logs removidos para evitar filtrar información sensible
                    
                    // Validar datos obligatorios
                    if (empty($clienteData['nombre_completo'])) {
                        throw new Exception("Nombre completo es obligatorio. Fila contiene: nombre_completo='{$clienteData['nombre_completo']}'");
                    }

                    // Validar formato de email si se proporciona
                    if (!empty($clienteData['email']) && !filter_var($clienteData['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("El email '{$clienteData['email']}' no tiene un formato válido");
                    }

                    // Validar que no haya datos mal mapeados (ej: teléfono en lugar de email)
                    if (!empty($clienteData['email']) && is_numeric($clienteData['email'])) {
                        throw new Exception("El email '{$clienteData['email']}' parece ser un número de teléfono. Verifique el orden de las columnas en el CSV");
                    }
                    
                    // Verificar si el cliente ya existe (por email o nombre completo)
                    if ($clienteData['email']) {
                        // Verificar por email usando consulta directa
                        $db = getDB();
                        $stmt = $db->prepare("SELECT id FROM clientes WHERE email = ?");
                        $stmt->execute([$clienteData['email']]);
                        $clienteExistente = $stmt->fetch();
                        
                        if ($clienteExistente) {
                            throw new Exception("Cliente con email {$clienteData['email']} ya existe");
                        }
                    }
                    
                    $this->clienteModel->createCliente($clienteData);
                    $registrosProcesados++;
                } catch (Exception $e) {
                    $errores[] = "Fila $totalRegistros: " . $e->getMessage();
                    continue;
                }
            }
            
            fclose($handle);
            
            // Actualizar estado del archivo
            $estadoFinal = empty($errores) ? 'completado' : (count($errores) === $totalRegistros ? 'error' : 'completado_con_errores');
            $this->archivoCsvModel->updateEstado($archivoId, $estadoFinal, $registrosProcesados, $totalRegistros);
            
            // Preparar mensaje de resultado
            $mensaje = "Archivo procesado exitosamente. ";
            $mensaje .= "$registrosProcesados de $totalRegistros registros importados.";
            
            if (!empty($errores)) {
                $mensaje .= " Errores: " . count($errores);
            }
            
            return [
                'success' => true,
                'message' => $mensaje,
                'archivo_id' => $archivoId,
                'total_registros' => $totalRegistros,
                'registros_procesados' => $registrosProcesados,
                'errores' => count($errores),
                'detalles_errores' => $errores
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Inicializar procesamiento masivo
     */
    public function inicializarProcesamiento($archivo, $coordinadorId) {
        try {
            // Validar archivo
            $tmpName = $archivo['tmp_name'] ?? null;
            if (!$tmpName) {
                throw new Exception("No se ha subido ningún archivo");
            }
            $esSubidaHttp = is_uploaded_file($tmpName);
            $esArchivoLocal = (PHP_SAPI === 'cli' && file_exists($tmpName) && is_readable($tmpName));
            if (!$esSubidaHttp && !$esArchivoLocal) {
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
            $nombreOriginal = (string) ($archivo['name'] ?? 'upload.csv');
            $nombreSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
            $nombreArchivo = uniqid('csv_', true) . '_' . $nombreSeguro;
            $rutaArchivo = $uploadDir . $nombreArchivo;
            
            // Mover archivo
            if ($esSubidaHttp) {
                if (!move_uploaded_file($tmpName, $rutaArchivo)) {
                    throw new Exception("Error al subir el archivo");
                }
            } else {
                if (!copy($tmpName, $rutaArchivo)) {
                    throw new Exception("Error al copiar el archivo");
                }
            }
            
            // Contar líneas del archivo
            $totalRegistros = $this->contarLineasCSV($rutaArchivo) - 1; // -1 por el header
            
            // Crear registro del archivo
            $archivoData = [
                'nombre_archivo' => $archivo['name'],
                'ruta_archivo' => $rutaArchivo,
                'coordinador_cedula' => $coordinadorId,
                'estado' => 'pendiente',
                'total_registros' => $totalRegistros,
                'registros_procesados' => 0
            ];
            
            $archivoId = $this->archivoCsvModel->createArchivo($archivoData);
            
            return [
                'success' => true,
                'message' => 'Archivo preparado para procesamiento',
                'archivo_id' => $archivoId,
                'total_registros' => $totalRegistros
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesar archivo en lotes
     */
    public function procesarLote($archivoId, $loteSize = 100) {
        try {
            $archivo = $this->archivoCsvModel->getArchivoById($archivoId);
            if (!$archivo) {
                throw new Exception("Archivo no encontrado");
            }
            
            if ($archivo['estado'] !== 'pendiente' && $archivo['estado'] !== 'procesando') {
                throw new Exception("El archivo no está disponible para procesamiento");
            }
            
            // Actualizar estado a procesando
            $this->archivoCsvModel->updateEstado($archivoId, 'procesando');
            
            $handle = fopen($archivo['ruta_archivo'], 'r');
            if (!$handle) {
                throw new Exception("Error al leer el archivo");
            }
            
            // Saltar header
            fgetcsv($handle);
            
            $registrosProcesados = 0;
            $errores = [];
            $loteActual = 0;
            
            while (($data = fgetcsv($handle)) !== false && $loteActual < $loteSize) {
                try {
                    // Mapear datos del CSV según formato esperado
                    $clienteData = [
                        'cedula' => $this->generarCedulaUnica(), // Generar cédula única
                        'nombre_completo' => trim($data[0] ?? ''), // Nombre completo desde primera columna
                        'email' => trim($data[1] ?? '') ?: null,
                        'telefono' => trim($data[2] ?? '') ?: null,
                        'direccion' => trim($data[3] ?? '') ?: null,
                        'ciudad' => trim($data[4] ?? '') ?: null,
                        'pais' => trim($data[5] ?? '') ?: null,
                        'coordinador_cedula' => $archivo['coordinador_cedula'],
                        'archivo_csv_id' => $archivoId,
                        'estado' => 'nuevo'
                    ];
                    
                    // Validar datos obligatorios
                    if (empty($clienteData['nombre_completo'])) {
                        throw new Exception("Nombre completo es obligatorio. Fila contiene: nombre_completo='{$clienteData['nombre_completo']}'");
                    }

                    // Validar formato de email si se proporciona
                    if (!empty($clienteData['email']) && !filter_var($clienteData['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("El email '{$clienteData['email']}' no tiene un formato válido");
                    }

                    // Validar que no haya datos mal mapeados (ej: teléfono en lugar de email)
                    if (!empty($clienteData['email']) && is_numeric($clienteData['email'])) {
                        throw new Exception("El email '{$clienteData['email']}' parece ser un número de teléfono. Verifique el orden de las columnas en el CSV");
                    }
                    
                    $this->clienteModel->createCliente($clienteData);
                    $registrosProcesados++;
                } catch (Exception $e) {
                    $errores[] = "Fila " . ($archivo['registros_procesados'] + $loteActual + 1) . ": " . $e->getMessage();
                }
                
                $loteActual++;
            }
            
            fclose($handle);
            
            // Actualizar progreso
            $nuevoTotal = $archivo['registros_procesados'] + $registrosProcesados;
            $estado = $nuevoTotal >= $archivo['total_registros'] ? 'completado' : 'procesando';
            
            $this->archivoCsvModel->updateEstado($archivoId, $estado, $nuevoTotal);
            
            return [
                'success' => true,
                'message' => "Lote procesado: $registrosProcesados registros",
                'registros_procesados' => $registrosProcesados,
                'total_procesados' => $nuevoTotal,
                'total_registros' => $archivo['total_registros'],
                'estado' => $estado,
                'errores' => count($errores),
                'detalles_errores' => $errores
            ];
            
        } catch (Exception $e) {
            // Marcar archivo como error
            $this->archivoCsvModel->updateEstado($archivoId, 'error', 0, 0, $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Finalizar procesamiento
     */
    public function finalizarProcesamiento($archivoId) {
        try {
            $archivo = $this->archivoCsvModel->getArchivoById($archivoId);
            if (!$archivo) {
                throw new Exception("Archivo no encontrado");
            }
            
            // Verificar si el procesamiento está completo
            if ($archivo['registros_procesados'] < $archivo['total_registros']) {
                throw new Exception("El procesamiento no está completo");
            }
            
            // Actualizar estado final
            $this->archivoCsvModel->updateEstado($archivoId, 'completado', $archivo['registros_procesados']);
            
            return [
                'success' => true,
                'message' => 'Procesamiento finalizado exitosamente',
                'archivo_id' => $archivoId,
                'total_registros' => $archivo['total_registros'],
                'registros_procesados' => $archivo['registros_procesados']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Contar líneas de un archivo CSV
     */
    private function contarLineasCSV($rutaArchivo) {
        $handle = fopen($rutaArchivo, 'r');
        $contador = 0;
        
        while (fgetcsv($handle) !== false) {
            $contador++;
        }
        
        fclose($handle);
        return $contador;
    }
    
    /**
     * Obtener estado del procesamiento
     */
    public function obtenerEstadoProcesamiento($archivoId) {
        try {
            $archivo = $this->archivoCsvModel->getArchivoById($archivoId);
            if (!$archivo) {
                throw new Exception("Archivo no encontrado");
            }

            return [
                'success' => true,
                'data' => [
                    'archivo_id' => $archivo['id'],
                    'nombre_archivo' => $archivo['nombre_archivo'],
                    'estado' => $archivo['estado'],
                    'total_registros' => $archivo['total_registros'],
                    'registros_procesados' => $archivo['registros_procesados'],
                    'progreso_porcentaje' => $archivo['total_registros'] > 0 ?
                        round(($archivo['registros_procesados'] / $archivo['total_registros']) * 100, 2) : 0,
                    'fecha_subida' => $archivo['fecha_subida'],
                    'fecha_procesamiento' => $archivo['fecha_procesamiento']
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
     * Generar una cédula única para clientes importados desde CSV
     */
    private function generarCedulaUnica() {
        $db = getDB();
        $intentos = 0;
        $maxIntentos = 10;

        do {
            // Generar cédula aleatoria de 8-10 dígitos
            $cedula = strval(mt_rand(10000000, 9999999999));

            // Verificar si ya existe
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM clientes WHERE cedula = ?");
            $stmt->execute([$cedula]);
            $result = $stmt->fetch();

            if ($result['count'] == 0) {
                return $cedula;
            }

            $intentos++;
        } while ($intentos < $maxIntentos);

        // Si no se pudo generar una única después de varios intentos, usar timestamp
        return 'CSV' . time() . mt_rand(100, 999);
    }
}
?>
