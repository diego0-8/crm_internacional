<?php
/**
 * Enrutamiento por sesión: la barra de direcciones muestra solo APP_URL/
 * La vista activa se guarda en $_SESSION['app_route'].
 */

function app_routes() {
    static $routes = null;
    if ($routes !== null) {
        return $routes;
    }

    $routes = [
        'login' => ['view' => 'login.php', 'public' => true],
        'admin_dashboard' => ['view' => 'admin_dashboard.php', 'role' => 'admin'],
        'analytics' => ['view' => 'analytics.php', 'role' => 'admin'],
        'settings' => ['view' => 'settings.php', 'role' => 'admin'],
        'coordinador_dashboard' => ['view' => 'coordinador_dashboard.php', 'role' => 'coordinador'],
        'coordinador_tareas' => ['view' => 'coordinador_tareas.php', 'role' => 'coordinador'],
        'coordinador_gestion' => ['view' => 'coordinador_gestion.php', 'role' => 'coordinador'],
        'coordinador_exporte' => ['view' => 'coordinador_exporte.php', 'role' => 'coordinador'],
        'asesor_dashboard' => ['view' => 'asesor_dashboard.php', 'role' => 'asesor'],
        'asesor_tickets' => ['view' => 'asesor_tickets.php', 'role' => 'asesor'],
        'asesor_estadisticas' => ['view' => 'asesor_estadisticas.php', 'role' => 'asesor'],
        'asesor_gestionar_ticket' => ['view' => 'asesor_gestionar_ticket.php', 'role' => 'asesor'],
        'cliente_dashboard' => ['view' => 'cliente_dashboard.php', 'role' => 'cliente'],
        'cliente_mis_tickets' => ['view' => 'cliente_mis_tickets.php', 'role' => 'cliente'],
    ];

    return $routes;
}

function app_home_url() {
    return rtrim(APP_URL, '/') . '/';
}

function app_asset($relativePath) {
    return rtrim(APP_URL, '/') . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
}

function app_default_route_for_role($rol) {
    $map = [
        'admin' => 'admin_dashboard',
        'coordinador' => 'coordinador_dashboard',
        'asesor' => 'asesor_dashboard',
        'cliente' => 'cliente_dashboard',
    ];
    return $map[$rol] ?? 'login';
}

function app_set_route($route, array $params = []) {
    $_SESSION['app_route'] = $route;
    $_SESSION['app_route_params'] = $params;
}

function app_clear_route() {
    unset($_SESSION['app_route'], $_SESSION['app_route_params']);
}

/** Parámetros de la vista actual (id de ticket, filtro cliente, etc.). */
function app_route_param($key, $default = null) {
    if (isset($_SESSION['app_route_params'][$key])) {
        return $_SESSION['app_route_params'][$key];
    }
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    return $default;
}

function app_nav_url($route, array $params = []) {
    $query = array_merge(['_route' => $route], $params);
    return app_home_url() . '?' . http_build_query($query);
}

function app_redirect_home() {
    header('Location: ' . app_home_url());
    exit;
}

function app_redirect_route($route, array $params = []) {
    app_set_route($route, $params);
    app_redirect_home();
}

function app_sanitize_route_name($route) {
    return preg_replace('/[^a-z0-9_]/', '', (string) $route);
}

/** Aplica ?_route= desde la URL y redirige a la raíz (URL limpia). */
function app_handle_route_request() {
    if (!isset($_GET['_route'])) {
        return;
    }

    $route = app_sanitize_route_name($_GET['_route']);
    $routes = app_routes();

    if ($route === '' || !isset($routes[$route])) {
        app_redirect_home();
    }

    $def = $routes[$route];
    if (empty($def['public'])) {
        if (!isLoggedIn()) {
            app_set_route('login');
            app_redirect_home();
        }
        if (!empty($def['role']) && !hasRole($def['role'])) {
            $user = getCurrentUser();
            app_set_route(app_default_route_for_role($user['rol_nombre'] ?? ''));
            app_redirect_home();
        }
    }

    $allowedParams = ['id', 'cliente', 'estado'];
    $params = [];
    foreach ($allowedParams as $param) {
        if (isset($_GET[$param]) && $_GET[$param] !== '') {
            $params[$param] = sanitize((string) $_GET[$param]);
        }
    }

    app_set_route($route, $params);
    app_redirect_home();
}

function app_resolve_route() {
    $routes = app_routes();

    if (!isLoggedIn()) {
        return $routes['login'];
    }

    $user = getCurrentUser();
    $rol = $user['rol_nombre'] ?? '';
    $routeName = $_SESSION['app_route'] ?? null;

    if ($routeName === null || $routeName === '' || $routeName === 'login') {
        $routeName = app_default_route_for_role($rol);
        app_set_route($routeName);
    }

    $routeName = app_sanitize_route_name($routeName);
    if (!isset($routes[$routeName])) {
        $routeName = app_default_route_for_role($rol);
        app_set_route($routeName);
    }

    $def = $routes[$routeName];

    if (!empty($def['public'])) {
        $routeName = app_default_route_for_role($rol);
        app_set_route($routeName);
        $def = $routes[$routeName];
    }

    if (!empty($def['role']) && !hasRole($def['role'])) {
        $routeName = app_default_route_for_role($rol);
        app_set_route($routeName);
        $def = $routes[$routeName];
    }

    return $def;
}

function app_dispatch_view() {
    app_handle_route_request();

    $def = app_resolve_route();
    $viewFile = dirname(__DIR__) . '/views/' . $def['view'];

    if (!is_file($viewFile)) {
        http_response_code(404);
        echo 'Vista no encontrada';
        exit;
    }

    require $viewFile;
}
