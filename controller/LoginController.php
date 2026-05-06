<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/AuthController.php';

class LoginController {
    private $authController;
    
    public function __construct() {
        $this->authController = new AuthController();
    }
    
    /**
     * Mostrar formulario de login
     */
    public function showLogin() {
        // Si ya está logueado, redirigir al dashboard
        if ($this->authController->checkAuth()) {
            $this->redirectToDashboard();
        }
        
        // No hacer nada más, la vista se mostrará automáticamente
        // ya que este método se llama desde login.php
    }
    
    /**
     * Procesar login
     */
    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderLoginView();
            return;
        }

        $usuario = sanitize($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';

        // Validar datos de entrada
        if (empty($usuario) || empty($password)) {
            $this->renderLoginView("Por favor, complete todos los campos", $usuario);
            return;
        }

        // Procesar login a través del AuthController
        $result = $this->authController->login($usuario, $password, $rememberMe);

        if ($result['success']) {
            // Configurar "Remember Me" si fue solicitado
            if ($rememberMe) {
                setRememberMeCookie($result['user']['cedula']);
            }

            // Redirigir según el rol
            $this->redirectToDashboard();
        } else {
            // Mostrar error específico
            $this->renderLoginView($result['message'], $usuario);
        }
    }
    
    /**
     * Renderizar vista de login
     */
    private function renderLoginView($error = '', $usuario = '') {
        // Guardar variables en la sesión para que estén disponibles en la vista
        $_SESSION['login_error'] = $error;
        $_SESSION['login_usuario'] = $usuario;
        
        // Redirigir a la vista de login
        redirect('../views/login.php');
    }
    
    /**
     * Redirigir al dashboard según el rol del usuario
     */
    public function redirectToDashboard() {
        if (!isset($_SESSION['user_role_name'])) {
            redirect('../views/login.php');
            return;
        }
        
        $rol = $_SESSION['user_role_name'];
        
        switch ($rol) {
            case 'admin':
                redirect('../views/admin_dashboard.php');
                break;
            case 'coordinador':
                redirect('../views/coordinador_dashboard.php');
                break;
            case 'asesor':
                redirect('../views/asesor_dashboard.php');
                break;
            case 'cliente':
                redirect('../views/cliente_dashboard.php');
                break;
            default:
                // Rol no reconocido, redirigir al login
                redirect('../views/login.php');
                break;
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $result = $this->authController->logout();
        
        return $result;
    }
}
?>
