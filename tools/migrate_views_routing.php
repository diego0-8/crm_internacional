<?php
/**
 * Actualiza enlaces y rutas de assets en views/ para el front controller.
 * Ejecutar: C:\xampp\php\php.exe tools/migrate_views_routing.php
 */
$viewsDir = dirname(__DIR__) . '/views';
$files = glob($viewsDir . '/*.php') ?: [];

$routeNames = [
    'login', 'admin_dashboard', 'analytics', 'settings',
    'coordinador_dashboard', 'coordinador_tareas', 'coordinador_gestion', 'coordinador_exporte',
    'asesor_dashboard', 'asesor_tickets', 'asesor_estadisticas', 'asesor_gestionar_ticket',
    'cliente_dashboard', 'cliente_mis_tickets',
];

foreach ($files as $file) {
    $name = basename($file);
    if ($name === 'login.php') {
        continue;
    }

    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }

    $content = str_replace('../css/', 'css/', $content);
    $content = str_replace('../img/', 'img/', $content);
    $content = str_replace('../api/', 'api/', $content);
    $content = str_replace("require __DIR__ . '/partials/favicon.php';", "require __DIR__ . '/partials/app_head.php';", $content);

    foreach ($routeNames as $route) {
        $content = str_replace(
            'href="' . $route . '.php"',
            'href="<?php echo app_nav_url(\'' . $route . '\'); ?>"',
            $content
        );
    }

    $content = str_replace(
        'href="asesor_gestionar_ticket.php?id=${ticket.id}"',
        "href=\"' + appNav('asesor_gestionar_ticket', {id: ticket.id}) + '\"",
        $content
    );

    $content = str_replace("window.location.href = '../views/login.php';", 'window.appGoLogin();', $content);
    $content = str_replace("window.location.href = 'login.php';", 'window.appGoLogin();', $content);

    foreach ($routeNames as $route) {
        if ($route === 'login') {
            continue;
        }
        $content = str_replace(
            "window.location.href = '" . $route . ".php';",
            "window.appGo('" . $route . "');",
            $content
        );
    }

    $content = str_replace(
        "window.location.href = 'asesor_gestionar_ticket.php?id=' + result.ticket_id;",
        "window.appGo('asesor_gestionar_ticket', {id: result.ticket_id});",
        $content
    );

    $content = str_replace(
        'header(\'Location: asesor_tickets.php\');',
        "app_redirect_route('asesor_tickets');",
        $content
    );

    $content = str_replace(
        "\$ticketId = isset(\$_GET['id']) ? (int) \$_GET['id'] : 0;",
        "\$ticketId = (int) app_route_param('id', 0);",
        $content
    );

    file_put_contents($file, $content);
    echo "Updated: {$name}\n";
}

echo "Done.\n";
