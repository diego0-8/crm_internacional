<?php
require_once __DIR__ . '/../config.php';

class ArchivoCsvModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Crear registro de archivo CSV
     */
    public function createArchivo($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO archivos_csv (nombre_archivo, ruta_archivo, coordinador_cedula, total_registros, estado) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nombre_archivo'],
                $data['ruta_archivo'],
                $data['coordinador_cedula'],
                $data['total_registros'] ?? 0,
                $data['estado'] ?? 'procesando'
            ]);
            
            if ($result) {
                $archivoId = $this->db->lastInsertId();
                logActivity('csv_uploaded', "Archivo CSV subido: {$data['nombre_archivo']}");
                return $archivoId;
            } else {
                throw new Exception("Error al crear registro de archivo");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Actualizar estado del archivo
     *
     * @param int|null $totalRegistros Si se indica, actualiza total_registros (importación reparto / legacy).
     */
    public function updateEstado($id, $estado, $registrosProcesados = null, $totalRegistros = null) {
        try {
            if ($estado === 'completado_con_errores') {
                $estado = 'completado';
            }
            $fields = ['estado = ?'];
            $values = [$estado];
            
            if ($registrosProcesados !== null) {
                $fields[] = 'registros_procesados = ?';
                $values[] = $registrosProcesados;
            }
            if ($totalRegistros !== null) {
                $fields[] = 'total_registros = ?';
                $values[] = $totalRegistros;
            }
            
            $values[] = $id;
            
            $stmt = $this->db->prepare("
                UPDATE archivos_csv 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                logActivity('csv_updated', "Estado de archivo CSV actualizado: ID $id - $estado");
                return true;
            } else {
                throw new Exception("Error al actualizar estado del archivo");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener archivos por coordinador
     */
    public function getArchivosByCoordinador($coordinadorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM archivos_csv 
                WHERE coordinador_cedula = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$coordinadorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener archivos: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener archivo por ID
     */
    public function getArchivoById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM archivos_csv WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener archivo: " . $e->getMessage());
        }
    }
    
    /**
     * Eliminar archivo
     */
    public function deleteArchivo($id) {
        try {
            $this->db->beginTransaction();
            
            // Obtener información del archivo
            $archivo = $this->getArchivoById($id);
            if (!$archivo) {
                throw new Exception("Archivo no encontrado");
            }
            
            // Eliminar archivo físico si existe
            if (file_exists($archivo['ruta_archivo'])) {
                unlink($archivo['ruta_archivo']);
            }
            
            // Titulares importados desde este CSV (reparto) y datos en cascada
            $stmt = $this->db->prepare("DELETE FROM titulares WHERE archivo_csv_id = ?");
            $stmt->execute([$id]);

            // Clientes CRM asociados al mismo archivo
            $stmt = $this->db->prepare("DELETE FROM clientes WHERE archivo_csv_id = ?");
            $stmt->execute([$id]);
            
            // Eliminar registro del archivo
            $stmt = $this->db->prepare("DELETE FROM archivos_csv WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->db->commit();
                logActivity('csv_deleted', "Archivo CSV eliminado: {$archivo['nombre_archivo']}");
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("Error al eliminar archivo");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener estadísticas de archivos
     */
    public function getEstadisticasArchivos($coordinadorId) {
        try {
            $stats = [];
            
            // Total de archivos
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM archivos_csv WHERE coordinador_cedula = ?");
            $stmt->execute([$coordinadorId]);
            $result = $stmt->fetch();
            $stats['total'] = $result ? $result['total'] : 0;
            
            // Archivos por estado
            $stmt = $this->db->prepare("
                SELECT estado, COUNT(*) as count 
                FROM archivos_csv 
                WHERE coordinador_cedula = ? 
                GROUP BY estado
            ");
            $stmt->execute([$coordinadorId]);
            $estados = $stmt->fetchAll();
            
            foreach ($estados as $estado) {
                $stats[$estado['estado']] = $estado['count'];
            }
            
            // Total de registros procesados
            $stmt = $this->db->prepare("
                SELECT SUM(total_registros) as total_registros, 
                       SUM(registros_procesados) as registros_procesados 
                FROM archivos_csv 
                WHERE coordinador_cedula = ?
            ");
            $stmt->execute([$coordinadorId]);
            $registros = $stmt->fetch();
            $stats['total_registros'] = $registros['total_registros'] ?? 0;
            $stats['registros_procesados'] = $registros['registros_procesados'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas de archivos: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar total de registros
     */
    public function updateTotalRegistros($id, $totalRegistros) {
        try {
            $stmt = $this->db->prepare("
                UPDATE archivos_csv 
                SET total_registros = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$totalRegistros, $id]);
            
            if ($result) {
                return true;
            } else {
                throw new Exception("Error al actualizar total de registros");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Actualizar progreso del procesamiento
     */
    public function actualizarProgreso($id, $registrosProcesados) {
        try {
            $stmt = $this->db->prepare("
                UPDATE archivos_csv 
                SET registros_procesados = registros_procesados + ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$registrosProcesados, $id]);
            
            if ($result) {
                return true;
            } else {
                throw new Exception("Error al actualizar progreso");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener progreso del procesamiento
     */
    public function obtenerProgreso($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT total_registros, registros_procesados 
                FROM archivos_csv 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if (!$data) {
                throw new Exception("Archivo no encontrado");
            }
            
            $porcentaje = 0;
            if ($data['total_registros'] > 0) {
                $porcentaje = round(($data['registros_procesados'] / $data['total_registros']) * 100, 2);
            }
            
            return [
                'total_registros' => $data['total_registros'],
                'registros_procesados' => $data['registros_procesados'],
                'porcentaje' => $porcentaje
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
