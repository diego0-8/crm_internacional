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
        if ($this->authController->checkAuth()) {
            $this->redirectToDashboard();
        }
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

        if (empty($usuario) || empty($password)) {
            $this->renderLoginView("Por favor, complete todos los campos", $usuario);
            return;
        }

        $result = $this->authController->login($usuario, $password, $rememberMe);

        if ($result['success']) {
            if ($rememberMe) {
                setRememberMeCookie($result['user']['cedula']);
            }
            $this->redirectToDashboard();
        } else {
            $this->renderLoginView($result['message'], $usuario);
        }
    }
    
    /**
     * Guardar error de login y volver a la pantalla de acceso (URL raíz).
     */
    private function renderLoginView($error = '', $usuario = '') {
        $_SESSION['login_error'] = $error;
        $_SESSION['login_usuario'] = $usuario;
        app_set_route('login');
        app_redirect_home();
    }
    
    /**
     * Redirigir al dashboard según el rol del usuario
     */
    public function redirectToDashboard() {
        if (!isset($_SESSION['user_role_name'])) {
            app_set_route('login');
            app_redirect_home();
        }
        
        $rol = $_SESSION['user_role_name'];
        app_set_route(app_default_route_for_role($rol));
        app_redirect_home();
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $result = $this->authController->logout();
        return $result;
    }
}

