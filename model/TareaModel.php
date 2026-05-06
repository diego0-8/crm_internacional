<?php
require_once __DIR__ . '/../config.php';

class TareaModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Crear tarea
     */
    public function createTarea($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tareas (titulo, descripcion, cliente_cedula, asesor_cedula, coordinador_cedula, 
                                  tipo, prioridad, estado, fecha_vencimiento, resultado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['cliente_cedula'] ?? null,
                $data['asesor_cedula'],
                $data['coordinador_cedula'],
                $data['tipo'],
                $data['prioridad'] ?? 'media',
                $data['estado'] ?? 'pendiente',
                $data['fecha_vencimiento'] ?? null,
                $data['resultado'] ?? null
            ]);
            
            if ($result) {
                $tareaId = $this->db->lastInsertId();
                logActivity('tarea_created', "Tarea creada: {$data['titulo']}");
                return $tareaId;
            } else {
                throw new Exception("Error al crear tarea");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener tareas por coordinador
     */
    public function getTareasByCoordinador($coordinadorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, 
                       CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                       CONCAT(a.nombre, ' ', a.apellido) as asesor_nombre
                FROM tareas t 
                LEFT JOIN clientes c ON t.cliente_cedula = c.cedula
                LEFT JOIN usuarios a ON t.asesor_cedula = a.cedula
                WHERE t.coordinador_cedula = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$coordinadorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tareas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener tareas por asesor
     */
    public function getTareasByAsesor($asesorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, 
                       CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre
                FROM tareas t 
                LEFT JOIN clientes c ON t.cliente_cedula = c.cedula
                WHERE t.asesor_cedula = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$asesorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tareas del asesor: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar tarea
     */
    public function updateTarea($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = ['titulo', 'descripcion', 'tipo', 'prioridad', 'estado', 
                            'fecha_vencimiento', 'resultado'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            // Si se marca como completada, agregar fecha de completado
            if (isset($data['estado']) && $data['estado'] === 'completada') {
                $fields[] = 'fecha_completada = CURRENT_TIMESTAMP';
            }
            
            if (empty($fields)) {
                throw new Exception("No hay datos para actualizar");
            }
            
            $values[] = $id;
            
            $stmt = $this->db->prepare("
                UPDATE tareas 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                logActivity('tarea_updated', "Tarea actualizada: ID $id");
                return true;
            } else {
                throw new Exception("Error al actualizar tarea");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Eliminar tarea
     */
    public function deleteTarea($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM tareas WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('tarea_deleted', "Tarea eliminada: ID $id");
                return true;
            } else {
                throw new Exception("Error al eliminar tarea");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener estadísticas de tareas
     */
    public function getEstadisticasTareas($coordinadorId) {
        try {
            $stats = [];
            
            // Total de tareas
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tareas WHERE coordinador_cedula = ?");
            $stmt->execute([$coordinadorId]);
            $result = $stmt->fetch();
            $stats['total'] = $result ? $result['total'] : 0;
            
            // Tareas por estado
            $stmt = $this->db->prepare("
                SELECT estado, COUNT(*) as count 
                FROM tareas 
                WHERE coordinador_cedula = ? 
                GROUP BY estado
            ");
            $stmt->execute([$coordinadorId]);
            $estados = $stmt->fetchAll();
            
            foreach ($estados as $estado) {
                $stats[$estado['estado']] = $estado['count'];
            }
            
            // Tareas por tipo
            $stmt = $this->db->prepare("
                SELECT tipo, COUNT(*) as count 
                FROM tareas 
                WHERE coordinador_cedula = ? 
                GROUP BY tipo
            ");
            $stmt->execute([$coordinadorId]);
            $tipos = $stmt->fetchAll();
            
            foreach ($tipos as $tipo) {
                $stats[$tipo['tipo']] = $tipo['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas de tareas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener tareas pendientes por asesor
     */
    public function getTareasPendientesByAsesor($asesorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, 
                       CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre
                FROM tareas t 
                LEFT JOIN clientes c ON t.cliente_cedula = c.cedula
                WHERE t.asesor_cedula = ? AND t.estado IN ('pendiente', 'en_progreso')
                ORDER BY t.prioridad DESC, t.fecha_vencimiento ASC
            ");
            $stmt->execute([$asesorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tareas pendientes: " . $e->getMessage());
        }
    }
}
?>
