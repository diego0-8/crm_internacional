<?php
/**
 * CRM Internacional - Front controller
 * Todas las vistas se sirven desde aquí; la URL visible es siempre la raíz del proyecto.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/router.php';

// Recordar sesión válida
if (!isLoggedIn() && checkRememberMeCookie()) {
    require_once __DIR__ . '/controller/LoginController.php';
    (new LoginController())->redirectToDashboard();
}

// Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])) {
    require_once __DIR__ . '/controller/LoginController.php';
    (new LoginController())->processLogin();
}

// Cerrar sesión vía GET (enlaces legacy)
if (isset($_GET['logout'])) {
    require_once __DIR__ . '/controller/LoginController.php';
    (new LoginController())->logout();
    app_clear_route();
    app_set_route('login');
    app_redirect_home();
}

app_dispatch_view();
