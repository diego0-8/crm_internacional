<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/RoleModel.php';

class UserController {
    private $userModel;
    private $roleModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function getAllUsers() {
        try {
            $users = $this->userModel->getAllUsers();
            return [
                'success' => true,
                'data' => $users
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getUserByCedula($id) {
        try {
            $user = $this->userModel->getUserByCedula($id);
            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }
            
            return [
                'success' => true,
                'data' => $user
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function createUser($data) {
        try {
            // Validar datos requeridos
            $requiredFields = ['cedula', 'usuario', 'nombre', 'apellido', 'email', 'password', 'rol_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            // Validar cédula
            if (!preg_match('/^\d{6,20}$/', $data['cedula'])) {
                throw new Exception("La cédula debe contener solo números y tener entre 6 y 20 dígitos");
            }
            
            // Validar usuario
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['usuario'])) {
                throw new Exception("El usuario debe contener solo letras, números y guiones bajos, entre 3 y 20 caracteres");
            }
            
            // Validar email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El email no es válido");
            }
            
            // Validar contraseña
            if (strlen($data['password']) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres");
            }
            
            // Verificar que el rol existe
            $role = $this->roleModel->getRoleById($data['rol_id']);
            if (!$role) {
                throw new Exception("El rol seleccionado no es válido");
            }
            
            // Si es asesor, verificar que tiene coordinador
            if ($role['nombre'] === 'asesor' && empty($data['coordinador_cedula'])) {
                throw new Exception("Los asesores deben tener un coordinador asignado");
            }

            if (!empty($data['sip_extension']) && strlen($data['sip_extension']) > 40) {
                throw new Exception('La extensión SIP no puede superar 40 caracteres');
            }
            if (!empty($data['sip_secret']) && strlen($data['sip_secret']) > 128) {
                throw new Exception('La clave SIP no puede superar 128 caracteres');
            }
            
            $userId = $this->userModel->createUser($data);
            
            return [
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'user_cedula' => $userId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function updateUser($cedula, $data) {
        try {
            // Verificar que el usuario existe
            $user = $this->userModel->getUserByCedula($cedula);
            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }

            // Validar email si se proporciona
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El email no es válido");
            }

            // Validar contraseña si se proporciona
            if (isset($data['password']) && !empty($data['password']) && strlen($data['password']) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres");
            }

            // Verificar que el rol existe si se proporciona
            if (isset($data['rol_id'])) {
                $role = $this->roleModel->getRoleById($data['rol_id']);
                if (!$role) {
                    throw new Exception("El rol seleccionado no es válido");
                }

                // Si es asesor, verificar que tiene coordinador
                if ($role['nombre'] === 'asesor' && empty($data['coordinador_cedula'])) {
                    throw new Exception("Los asesores deben tener un coordinador asignado");
                }
            }

            if (isset($data['rol_id'])) {
                $role = $this->roleModel->getRoleById($data['rol_id']);
                if ($role && $role['nombre'] !== 'asesor') {
                    $data['sip_extension'] = '';
                    unset($data['sip_secret']);
                }
            }

            if (isset($data['sip_extension']) && strlen((string) $data['sip_extension']) > 40) {
                throw new Exception('La extensión SIP no puede superar 40 caracteres');
            }
            if (array_key_exists('sip_secret', $data) && $data['sip_secret'] !== null && strlen((string) $data['sip_secret']) > 128) {
                throw new Exception('La clave SIP no puede superar 128 caracteres');
            }

            $this->userModel->updateUser($cedula, $data);
            
            return [
                'success' => true,
                'message' => 'Usuario actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function deleteUser($cedula) {
        try {
            $this->userModel->deleteUser($cedula);

            return [
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambiar estado de usuario
     */
    public function toggleUserStatus($cedula) {
        try {
            $newStatus = $this->userModel->toggleUserStatus($cedula);
            $statusText = $newStatus ? 'habilitado' : 'deshabilitado';

            return [
                'success' => true,
                'message' => "Usuario $statusText exitosamente",
                'new_status' => $newStatus
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener coordinadores
     */
    public function getCoordinators() {
        try {
            $coordinators = $this->userModel->getUsersByRole('coordinador');
            
            return [
                'success' => true,
                'data' => $coordinators
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener asesores
     */
    public function getAdvisors() {
        try {
            $advisors = $this->userModel->getUsersByRole('asesor');
            
            return [
                'success' => true,
                'data' => $advisors
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Asignar asesores a coordinador
     */
    public function assignAdvisorsToCoordinator($coordinatorId, $advisorIds) {
        try {
            // Validar datos
            if (empty($coordinatorId) || empty($advisorIds)) {
                throw new Exception("Seleccione un coordinador y al menos un asesor");
            }
            
            if (!is_array($advisorIds)) {
                throw new Exception("Los asesores deben ser un array");
            }
            
            $this->userModel->assignAdvisorsToCoordinator($coordinatorId, $advisorIds);
            
            return [
                'success' => true,
                'message' => 'Asesores asignados exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function getUserStats() {
        try {
            $stats = $this->userModel->getUserStats();
            
            return [
                'success' => true,
                'data' => $stats
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
