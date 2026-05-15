<?php
require_once __DIR__ . '/../config.php';

class ClienteModel {
    private $db;

    /** @var array<string, bool> */
    private static $tableExistsCache = [];

    public function __construct() {
        $this->db = getDB();
    }

    /** True si la tabla existe en la BD actual (dump parcial sin CRM completo). */
    private function tablaExiste(string $nombreTabla): bool {
        if (array_key_exists($nombreTabla, self::$tableExistsCache)) {
            return self::$tableExistsCache[$nombreTabla];
        }
        try {
            $stmt = $this->db->prepare('
                SELECT 1 FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                LIMIT 1
            ');
            $stmt->execute([$nombreTabla]);
            self::$tableExistsCache[$nombreTabla] = (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            self::$tableExistsCache[$nombreTabla] = false;
        }
        return self::$tableExistsCache[$nombreTabla];
    }
    
    /**
     * Obtener clientes por coordinador
     */
    public function getClientesByCoordinador($coordinadorCedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                        CONCAT(a.nombre, ' ', a.apellido) as asesor_nombre,
                        a.email as asesor_email
                FROM clientes c
                LEFT JOIN usuarios a ON c.asesor_cedula = a.cedula
                WHERE c.coordinador_cedula = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$coordinadorCedula]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener clientes: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener cliente por ID (cedula)
     */
    public function getClienteById($cedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       CONCAT(a.nombre, ' ', a.apellido) as asesor_nombre,
                       a.email as asesor_email
                FROM clientes c 
                LEFT JOIN usuarios a ON c.asesor_cedula = a.cedula
                WHERE c.cedula = ?
            ");
            $stmt->execute([$cedula]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener cliente: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener cliente por cédula
     */
    public function getClienteByCedula($cedula) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       CONCAT(a.nombre, ' ', a.apellido) as asesor_nombre,
                       a.email as asesor_email
                FROM clientes c 
                LEFT JOIN usuarios a ON c.asesor_cedula = a.cedula
                WHERE c.cedula = ?
            ");
            $stmt->execute([$cedula]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener cliente: " . $e->getMessage());
        }
    }

    /**
     * Indica si el cliente pertenece al coordinador (para importaciones CSV).
     */
    public function clienteBelongsToCoordinador($cedula, $coordinadorCedula) {
        $c = $this->getClienteByCedula($cedula);
        return $c && (($c['coordinador_cedula'] ?? '') === $coordinadorCedula);
    }

    /**
     * Crear cliente
     */
    public function createCliente($data) {
        try {
            $nombreCompleto = trim((string) ($data['nombre_completo'] ?? ''));
            if ($nombreCompleto === '') {
                throw new Exception('nombre_completo es requerido');
            }

            $stmt = $this->db->prepare("
                INSERT INTO clientes (cedula, nombre_completo, email, telefono, direccion, ciudad, pais,
                                      asesor_cedula, coordinador_cedula, archivo_csv_id, estado, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['cedula'],
                $nombreCompleto,
                $data['email'] ?? null,
                $data['telefono'] ?? null,
                $data['direccion'] ?? null,
                $data['ciudad'] ?? null,
                $data['pais'] ?? null,
                $data['asesor_cedula'] ?? null,
                $data['coordinador_cedula'],
                $data['archivo_csv_id'] ?? null,
                $data['estado'] ?? 'nuevo',
                $data['notas'] ?? null
            ]);
            
            if ($result) {
                logActivity('cliente_created', "Cliente creado: {$nombreCompleto}");
                return $data['cedula'];
            } else {
                throw new Exception("Error al crear cliente");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Actualizar cliente
     */
    public function updateCliente($cedula, $data) {
        try {
            $fields = [];
            $values = [];

            $allowedFields = [
                'nombre_completo',
                'email',
                'telefono',
                'direccion',
                'ciudad',
                'pais',
                'asesor_cedula',
                'archivo_csv_id',
                'estado',
                'notas'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception("No hay datos para actualizar");
            }
            
            $values[] = $cedula;
            
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE cedula = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                logActivity('cliente_updated', "Cliente actualizado: Cédula $cedula");
                return true;
            } else {
                throw new Exception("Error al actualizar cliente");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Eliminar cliente
     */
    public function deleteCliente($cedula) {
        try {
            $this->db->beginTransaction();
            
            // Obtener información del cliente antes de eliminar
            $cliente = $this->getClienteById($cedula);
            if (!$cliente) {
                throw new Exception("Cliente no encontrado");
            }
            
            // Eliminar tareas relacionadas
            $stmt = $this->db->prepare("DELETE FROM tareas WHERE cliente_cedula = ?");
            $stmt->execute([$cedula]);
            
            // Eliminar cliente
            $stmt = $this->db->prepare("DELETE FROM clientes WHERE cedula = ?");
            $result = $stmt->execute([$cedula]);
            
            if ($result) {
                $this->db->commit();
                $nombreCompleto = (string) ($cliente['nombre_completo'] ?? '');
                logActivity('cliente_deleted', "Cliente eliminado: " . ($nombreCompleto !== '' ? $nombreCompleto : "Cédula $cedula"));
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("Error al eliminar cliente");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Asignar cliente a asesor
     */
    public function asignarClienteAAsesor($clienteCedula, $asesorCedula) {
        try {
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET asesor_cedula = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE cedula = ?
            ");
            
            $result = $stmt->execute([$asesorCedula, $clienteCedula]);
            
            if ($result) {
                logActivity('cliente_assigned', "Cliente asignado a asesor: Cliente Cédula $clienteCedula, Asesor Cédula $asesorCedula");
                return true;
            } else {
                throw new Exception("Error al asignar cliente");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener estadísticas de clientes por coordinador
     */
    public function getEstadisticasClientes($coordinadorCedula) {
        try {
            $stats = [];
            
            // Total de clientes
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM clientes WHERE coordinador_cedula = ?");
            $stmt->execute([$coordinadorCedula]);
            $result = $stmt->fetch();
            $stats['total'] = $result ? $result['total'] : 0;
            
            // Clientes por estado
            $stmt = $this->db->prepare("
                SELECT estado, COUNT(*) as count 
                FROM clientes 
                WHERE coordinador_cedula = ? 
                GROUP BY estado
            ");
            $stmt->execute([$coordinadorCedula]);
            $estados = $stmt->fetchAll();
            
            foreach ($estados as $estado) {
                $stats[$estado['estado']] = $estado['count'];
            }
            
            // Clientes asignados vs no asignados
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN asesor_cedula IS NOT NULL THEN 1 END) as asignados,
                    COUNT(CASE WHEN asesor_cedula IS NULL THEN 1 END) as no_asignados
                FROM clientes 
                WHERE coordinador_cedula = ?
            ");
            $stmt->execute([$coordinadorCedula]);
            $asignacion = $stmt->fetch();
            $stats['asignados'] = $asignacion['asignados'];
            $stats['no_asignados'] = $asignacion['no_asignados'];
            
            return $stats;
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener clientes por asesor
     */
    public function getClientesByAsesor($asesorCedula) {
        try {
            $hasHistorial = $this->tablaExiste('historial_llamadas');
            $hasTiketera = $this->tablaExiste('tiketera');

            if ($hasHistorial && $hasTiketera) {
                $sql = "
                SELECT c.*,
                       CASE 
                           WHEN EXISTS (
                               SELECT 1 FROM historial_llamadas hl 
                               WHERE hl.cliente_cedula = c.cedula
                           ) OR EXISTS (
                               SELECT 1 FROM tiketera t 
                               WHERE t.cliente_cedula = c.cedula
                           ) THEN 'gestionado'
                           ELSE 'nuevo'
                       END as estado_gestion
                FROM clientes c
                WHERE c.asesor_cedula = ? 
                ORDER BY c.created_at DESC
            ";
            } elseif ($hasHistorial) {
                $sql = "
                SELECT c.*,
                       CASE 
                           WHEN EXISTS (
                               SELECT 1 FROM historial_llamadas hl 
                               WHERE hl.cliente_cedula = c.cedula
                           ) THEN 'gestionado'
                           ELSE 'nuevo'
                       END as estado_gestion
                FROM clientes c
                WHERE c.asesor_cedula = ? 
                ORDER BY c.created_at DESC
            ";
            } elseif ($hasTiketera) {
                $sql = "
                SELECT c.*,
                       CASE 
                           WHEN EXISTS (
                               SELECT 1 FROM tiketera t 
                               WHERE t.cliente_cedula = c.cedula
                           ) THEN 'gestionado'
                           ELSE 'nuevo'
                       END as estado_gestion
                FROM clientes c
                WHERE c.asesor_cedula = ? 
                ORDER BY c.created_at DESC
            ";
            } else {
                $sql = "
                SELECT c.*, 'nuevo' AS estado_gestion
                FROM clientes c
                WHERE c.asesor_cedula = ? 
                ORDER BY c.created_at DESC
            ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$asesorCedula]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener clientes del asesor: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar clientes
     */
    public function buscarClientes($coordinadorCedula, $termino) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                        CONCAT(a.nombre, ' ', a.apellido) as asesor_nombre
                FROM clientes c
                LEFT JOIN usuarios a ON c.asesor_cedula = a.cedula
                WHERE c.coordinador_cedula = ?
                AND (c.nombre_completo LIKE ? OR c.email LIKE ?)
                ORDER BY c.created_at DESC
            ");
            $termino = "%$termino%";
            $stmt->execute([$coordinadorCedula, $termino, $termino]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al buscar clientes: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener clientes disponibles para asignación (sin asesor asignado)
     */
    public function getClientesDisponibles($limite = null) {
        try {
            $sql = "SELECT * FROM clientes WHERE asesor_cedula IS NULL ORDER BY created_at ASC";
            
            if ($limite) {
                $sql .= " LIMIT ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$limite]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error obteniendo clientes disponibles: " . $e->getMessage());
        }
    }
}
?>
