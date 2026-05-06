<?php
require_once __DIR__ . '/../config.php';

class UserModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Obtener todos los usuarios con información de roles y coordinadores
     */
    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre, 
                       CONCAT(c.nombre, ' ', c.apellido) as coordinador_nombre
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                LEFT JOIN usuarios c ON u.coordinador_cedula = c.cedula
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener usuarios: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getUserByCedula($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE u.cedula = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener usuario por email
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener usuario por nombre de usuario
     */
    public function getUserByUsername($usuario) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE u.usuario = ?
            ");
            $stmt->execute([$usuario]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function createUser($data) {
        try {
            $this->db->beginTransaction();
            
            // Verificar si el email ya existe
            $existingUser = $this->getUserByEmail($data['email']);
            if ($existingUser) {
                throw new Exception("El email ya está registrado");
            }
            
            // Verificar si el usuario ya existe
            $existingUsername = $this->getUserByUsername($data['usuario']);
            if ($existingUsername) {
                throw new Exception("El nombre de usuario ya está registrado");
            }
            
            // Verificar si la cédula ya existe
            $existingCedula = $this->getUserByCedula($data['cedula']);
            if ($existingCedula) {
                throw new Exception("La cédula ya está registrada");
            }
            
            // Hash de la contraseña
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $sipExt = isset($data['sip_extension']) ? trim((string) $data['sip_extension']) : '';
            $sipSecret = isset($data['sip_secret']) ? trim((string) $data['sip_secret']) : '';
            $sipExtDb = $sipExt !== '' ? $sipExt : null;
            $sipSecretDb = $sipSecret !== '' ? $sipSecret : null;

            $stmt = $this->db->prepare("
                INSERT INTO usuarios (cedula, usuario, nombre, apellido, email, password, telefono, sip_extension, sip_secret, rol_id, coordinador_cedula, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['cedula'],
                $data['usuario'],
                $data['nombre'],
                $data['apellido'],
                $data['email'],
                $hashedPassword,
                $data['telefono'] ?? null,
                $sipExtDb,
                $sipSecretDb,
                $data['rol_id'],
                $data['coordinador_cedula'] ?? null,
                $data['activo'] ?? 1
            ]);
            
            if ($result) {
                $this->db->commit();
                
                // Registrar actividad
                logActivity('user_created', "Usuario creado: {$data['nombre']} {$data['apellido']}");
                
                return $data['cedula'];
            } else {
                $this->db->rollBack();
                throw new Exception("Error al crear usuario");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function updateUser($cedula, $data) {
        try {
            $this->db->beginTransaction();
            
            // Verificar si el email ya existe en otro usuario
            if (isset($data['email'])) {
                $existingUser = $this->getUserByEmail($data['email']);
                if ($existingUser && $existingUser['cedula'] != $cedula) {
                    throw new Exception("El email ya está registrado por otro usuario");
                }
            }
            
            // Construir query dinámicamente
            $fields = [];
            $values = [];
            
            $allowedFields = ['usuario', 'nombre', 'apellido', 'email', 'telefono', 'rol_id', 'coordinador_cedula', 'activo'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }

            // SIP WebRTC/PBX: texto plano en sip_secret (requisito PBX / softphone)
            foreach (['sip_extension', 'sip_secret'] as $sf) {
                if (array_key_exists($sf, $data)) {
                    $val = $data[$sf];
                    $fields[] = "{$sf} = ?";
                    $values[] = ($val === null || $val === '') ? null : (string) $val;
                }
            }
            
            // Si se proporciona contraseña, hashearla
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = ?";
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($fields)) {
                throw new Exception("No hay datos para actualizar");
            }
            
            $values[] = $cedula;
            
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE cedula = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                $this->db->commit();
                
                // Registrar actividad
                logActivity('user_updated', "Usuario actualizado: Cédula $cedula");
                
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("Error al actualizar usuario");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Eliminar usuario (soft delete)
     */
    public function deleteUser($cedula) {
        try {
            $this->db->beginTransaction();
            
            // Obtener información del usuario antes de eliminar
            $user = $this->getUserByCedula($cedula);
            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }
            
            // Verificar si es admin
            if ($user['rol_nombre'] === 'admin') {
                throw new Exception("No se puede eliminar el administrador");
            }
            
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE cedula = ?");
            $result = $stmt->execute([$cedula]);
            
            if ($result) {
                $this->db->commit();
                
                // Registrar actividad
                logActivity('user_deleted', "Usuario eliminado: {$user['nombre']} {$user['apellido']}");
                
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("Error al eliminar usuario");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Cambiar estado de usuario (habilitar/deshabilitar)
     */
    public function toggleUserStatus($cedula) {
        try {
            $this->db->beginTransaction();
            
            $user = $this->getUserByCedula($cedula);
            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }
            
            // Verificar si es admin
            if ($user['rol_nombre'] === 'admin') {
                throw new Exception("No se puede deshabilitar el administrador");
            }
            
            $newStatus = $user['activo'] ? 0 : 1;
            
            $stmt = $this->db->prepare("UPDATE usuarios SET activo = ?, updated_at = CURRENT_TIMESTAMP WHERE cedula = ?");
            $result = $stmt->execute([$newStatus, $cedula]);
            
            if ($result) {
                $this->db->commit();
                
                // Registrar actividad
                $action = $newStatus ? 'habilitado' : 'deshabilitado';
                logActivity('user_toggled', "Usuario $action: {$user['nombre']} {$user['apellido']}");
                
                return $newStatus;
            } else {
                $this->db->rollBack();
                throw new Exception("Error al cambiar estado del usuario");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener usuarios por rol
     */
    public function getUsersByRole($roleName) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE r.nombre = ? AND u.activo = 1
                ORDER BY u.nombre, u.apellido
            ");
            $stmt->execute([$roleName]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener usuarios por rol: " . $e->getMessage());
        }
    }
    
    /**
     * Asignar asesores a coordinador
     */
    public function assignAdvisorsToCoordinator($coordinatorId, $advisorIds) {
        try {
            $this->db->beginTransaction();
            
            // Verificar que el coordinador existe
            $coordinator = $this->getUserByCedula($coordinatorId);
            if (!$coordinator || $coordinator['rol_nombre'] !== 'coordinador') {
                throw new Exception("Coordinador no válido");
            }
            
            // Actualizar cada asesor
            foreach ($advisorIds as $advisorId) {
                $stmt = $this->db->prepare("
                    UPDATE usuarios 
                    SET coordinador_cedula = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE cedula = ? AND rol_id = (SELECT id FROM roles WHERE nombre = 'asesor')
                ");
                $stmt->execute([$coordinatorId, $advisorId]);
            }
            
            $this->db->commit();
            
            // Registrar actividad
            logActivity('advisors_assigned', "Asesores asignados al coordinador: {$coordinator['nombre']} {$coordinator['apellido']}");
            
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function getUserStats() {
        try {
            $stats = [];
            
            // Total de usuarios
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM usuarios");
            $stmt->execute();
            $stats['total'] = $stmt->fetch()['total'];
            
            // Usuarios activos
            $stmt = $this->db->prepare("SELECT COUNT(*) as active FROM usuarios WHERE activo = 1");
            $stmt->execute();
            $stats['active'] = $stmt->fetch()['active'];
            
            // Por rol
            $stmt = $this->db->prepare("
                SELECT r.nombre, COUNT(u.cedula) as count 
                FROM roles r 
                LEFT JOIN usuarios u ON r.id = u.rol_id AND u.activo = 1
                GROUP BY r.id, r.nombre
            ");
            $stmt->execute();
            $roleStats = $stmt->fetchAll();
            
            foreach ($roleStats as $role) {
                $stats[$role['nombre']] = $role['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
}
?>
