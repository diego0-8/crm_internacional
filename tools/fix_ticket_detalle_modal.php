<?php
/**
 * Verifica e informa el parche del modal "Ver detalle" en asesor_tickets.
 * Ejecutar: php tools/fix_ticket_detalle_modal.php
 */
$root = dirname(__DIR__);
$view = $root . '/views/asesor_tickets.php';
$js = $root . '/assets/js/ticket-detalle-modal.js';
$css = $root . '/css/tickets.css';

$checks = [
    'Vista asesor_tickets' => $view,
    'JS ticket-detalle-modal' => $js,
    'CSS tickets' => $css,
];

echo "=== Fix modal detalle ticket ===\n\n";

foreach ($checks as $label => $path) {
    echo ($label . ': ' . (is_file($path) ? 'OK' : 'FALTA') . ' (' . $path . ")\n");
}

if (!is_file($view)) {
    exit(1);
}

$content = file_get_contents($view);
$required = [
    'ticket-detalle-modal-dialog' => 'Contenedor dialog sin modal-content',
    'ticket-detalle-modal.js' => 'Script de layout y título',
    'TicketDetalleModal' => 'API JS del modal',
    'TicketDetalleModal.setTitulo' => 'Título Case/Parcel',
];

echo "\nComprobaciones en la vista:\n";
foreach ($required as $needle => $desc) {
    $ok = strpos($content, $needle) !== false;
    echo ($ok ? '[OK]' : '[PENDIENTE]') . ' ' . $desc . "\n";
    if (!$ok) {
        echo "  → Falta: {$needle}\n";
    }
}

echo "\nListo. Recargue asesor_tickets con Ctrl+F5.\n";
