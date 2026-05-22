<?php
/**
 * Verifica e integra CSS/JS de la campana del asesor.
 * Ejecutar: php tools/fix_asesor_bell_css.php
 */
$root = dirname(__DIR__);

$checks = [
    'CSS campana' => $root . '/css/asesor-bell.css',
    'JS campana' => $root . '/assets/js/asesor-llamadas-hoy.js',
    'Partial HTML' => $root . '/views/partials/asesor_navbar_bell.php',
];

$views = [
    $root . '/views/asesor_dashboard.php',
    $root . '/views/asesor_tickets.php',
];

echo "=== Fix campana asesor (CSS + panel) ===\n\n";

foreach ($checks as $label => $path) {
    echo ($label . ': ' . (is_file($path) ? 'OK' : 'FALTA') . "\n");
}

$cssLink = '<link href="css/asesor-bell.css" rel="stylesheet">';
$patched = 0;

foreach ($views as $viewPath) {
    $name = basename($viewPath);
    if (!is_file($viewPath)) {
        echo "[SKIP] $name no existe\n";
        continue;
    }
    $content = file_get_contents($viewPath);
    $ok = true;

    if (strpos($content, 'asesor-bell.css') === false) {
        if (strpos($content, 'css/tickets.css') !== false) {
            $content = str_replace(
                '<link href="css/tickets.css" rel="stylesheet">',
                '<link href="css/tickets.css" rel="stylesheet">' . "\n    " . $cssLink,
                $content
            );
            $patched++;
            echo "[PATCH] $name — añadido link asesor-bell.css\n";
        } else {
            echo "[PENDIENTE] $name — no se encontró tickets.css para insertar link\n";
            $ok = false;
        }
    } else {
        echo "[OK] $name — asesor-bell.css ya enlazado\n";
    }

    foreach (['asesor_navbar_bell.php', 'asesor-llamadas-hoy.js'] as $needle) {
        if (strpos($content, $needle) === false) {
            echo "[PENDIENTE] $name — falta $needle\n";
            $ok = false;
        }
    }

    if ($patched > 0 && $content !== file_get_contents($viewPath)) {
        file_put_contents($viewPath, $content);
    }
}

$bellCss = @file_get_contents($root . '/css/asesor-bell.css');
if ($bellCss !== false) {
    $rules = ['.asesor-bell-panel', 'position: fixed', 'z-index: 10050'];
    foreach ($rules as $r) {
        echo (strpos($bellCss, $r) !== false ? '[OK]' : '[FALTA]') . " regla CSS: $r\n";
    }
}

echo "\nCausa habitual sin estilos: .dashboard-container { overflow:hidden } recorta el panel.\n";
echo "Solución aplicada: css/asesor-bell.css con position:fixed + JS positionBellPanel().\n";
echo "Recargue con Ctrl+F5 en asesor_dashboard y asesor_tickets.\n";
