<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/UserModel.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    /**
     * Procesar login
     */
    public function login($usuario, $password, $rememberMe = false) {
        try {
            // Validar datos de entrada
            if (empty($usuario) || empty($password)) {
                throw new Exception("Por favor, complete todos los campos");
            }
            
            // Obtener usuario por nombre de usuario
            $user = $this->userModel->getUserByUsername($usuario);
            
            if (!$user) {
                logActivity('login_failed', "Usuario no encontrado: $usuario");
                throw new Exception("Usuario no encontrado");
            }
            
            // Verificar si el usuario está activo
            if (!$user['activo']) {
                logActivity('login_failed', "Intento de login con cuenta deshabilitada: $usuario");
                throw new Exception("Su cuenta está deshabilitada. Contacte al administrador.");
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                logActivity('login_failed', "Contraseña incorrecta para usuario: $usuario");
                throw new Exception("Contraseña incorrecta");
            }
            
            // Iniciar sesión
            $_SESSION['user_cedula'] = $user['cedula'];
            $_SESSION['user_usuario'] = $user['usuario'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
            $_SESSION['user_role'] = $user['rol_id'];
            $_SESSION['user_role_name'] = $user['rol_nombre'];
            
            // Registrar actividad
            logActivity('login', "Inicio de sesión exitoso para usuario: $usuario");
            
            return [
                'success' => true,
                'message' => 'Login exitoso',
                'user' => $user
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        try {
            // Registrar actividad antes de cerrar sesión
            if (isLoggedIn()) {
                logActivity('logout', "Cierre de sesión");
            }
            
            // Destruir sesión y abrir una nueva vacía (permite guardar ruta de login, etc.)
            session_destroy();
            setcookie('crm_remember', '', time() - 3600, '/');
            app_restart_session();

            return [
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar autenticación
     */
    public function checkAuth() {
        return isLoggedIn();
    }
    
    /**
     * Obtener usuario actual
     */
    public function getCurrentUser() {
        return getCurrentUser();
    }
    
    /**
     * Verificar permisos
     */
    public function hasPermission($requiredRole) {
        return hasRole($requiredRole);
    }
    
    /**
     * Redirigir según rol
     */
    public function redirectByRole() {
        if (!isLoggedIn()) {
            return 'login.php';
        }
        
        $user = getCurrentUser();
        return $user['rol_nombre'] . '_dashboard.php';
    }
}

