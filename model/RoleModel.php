<?php
require_once __DIR__ . '/../config.php';

class RoleModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Obtener todos los roles
     */
    public function getAllRoles() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM roles ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener roles: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener rol por ID
     */
    public function getRoleById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener rol: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener rol por nombre
     */
    public function getRoleByName($name) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM roles WHERE nombre = ?");
            $stmt->execute([$name]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener rol: " . $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo rol
     */
    public function createRole($data) {
        try {
            // Verificar si el rol ya existe
            $existingRole = $this->getRoleByName($data['nombre']);
            if ($existingRole) {
                throw new Exception("El rol ya existe");
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO roles (nombre, descripcion) 
                VALUES (?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null
            ]);
            
            if ($result) {
                $roleId = $this->db->lastInsertId();
                
                // Registrar actividad
                logActivity('role_created', "Rol creado: {$data['nombre']}");
                
                return $roleId;
            } else {
                throw new Exception("Error al crear rol");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Actualizar rol
     */
    public function updateRole($id, $data) {
        try {
            // Verificar si el rol existe
            $role = $this->getRoleById($id);
            if (!$role) {
                throw new Exception("Rol no encontrado");
            }
            
            // Verificar si el nuevo nombre ya existe en otro rol
            if (isset($data['nombre']) && $data['nombre'] !== $role['nombre']) {
                $existingRole = $this->getRoleByName($data['nombre']);
                if ($existingRole) {
                    throw new Exception("El nombre del rol ya existe");
                }
            }
            
            $fields = [];
            $values = [];
            
            $allowedFields = ['nombre', 'descripcion'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception("No hay datos para actualizar");
            }
            
            $values[] = $id;
            
            $stmt = $this->db->prepare("
                UPDATE roles 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                // Registrar actividad
                logActivity('role_updated', "Rol actualizado: ID $id");
                
                return true;
            } else {
                throw new Exception("Error al actualizar rol");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Eliminar rol
     */
    public function deleteRole($id) {
        try {
            $this->db->beginTransaction();
            
            // Verificar si el rol existe
            $role = $this->getRoleById($id);
            if (!$role) {
                throw new Exception("Rol no encontrado");
            }
            
            // Verificar si hay usuarios con este rol
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM usuarios WHERE rol_id = ?");
            $stmt->execute([$id]);
            $userCount = $stmt->fetch()['count'];
            
            if ($userCount > 0) {
                throw new Exception("No se puede eliminar el rol porque hay usuarios asignados a él");
            }
            
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->db->commit();
                
                // Registrar actividad
                logActivity('role_deleted', "Rol eliminado: {$role['nombre']}");
                
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("Error al eliminar rol");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener roles disponibles para asignación (excluyendo super_admin)
     */
    public function getAssignableRoles() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM roles 
                WHERE nombre != 'super_admin' 
                ORDER BY id
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener roles asignables: " . $e->getMessage());
        }
    }
}
?>
