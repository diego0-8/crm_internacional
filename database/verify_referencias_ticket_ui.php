<?php
/**
 * Verificación: botón Referencias + modal en asesor_gestionar_ticket.php
 * y datos en API/BD.
 *
 * Uso: php database/verify_referencias_ticket_ui.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$viewPath = $root . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'asesor_gestionar_ticket.php';
$apiPath = $root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'ticket_detalle_completo.php';
$cssPath = $root . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'tickets.css';

$errors = [];
$warnings = [];
$ok = [];

function mustContain(string $path, string $needle, string $label): array {
    $content = @file_get_contents($path);
    if ($content === false) {
        return ['ERR', "No se puede leer: {$path}"];
    }
    if (strpos($content, $needle) === false) {
        return ['ERR', "Falta en vista/API: {$label} (no encontrado «{$needle}»)"];
    }
    return ['OK', $label];
}

echo "=== Verificación UI Referencias (ticket asesor) ===\n\n";

if (!is_file($viewPath)) {
    $errors[] = "Archivo no existe: {$viewPath}";
} else {
    foreach ([
        ['id="btnAbrirReferencias"', 'Botón HTML id=btnAbrirReferencias'],
        ['fa-address-book"></i> Referencias', 'Texto del botón Referencias (con ícono)'],
        ['id="modalReferencias"', 'Modal id=modalReferencias'],
        ['id="referenciasModalBody"', 'Cuerpo del modal referenciasModalBody'],
        ['function abrirModalReferencias', 'Función JS abrirModalReferencias'],
        ['function cerrarModalReferencias', 'Función JS cerrarModalReferencias'],
        ['function buildReferenciasHtml', 'Función JS buildReferenciasHtml'],
        ['btnRef.addEventListener(\'click\'', 'Listener click en btnAbrirReferencias'],
        ['detalle-acciones-cliente', 'Contenedor CSS detalle-acciones-cliente'],
        ['ticket-detalles-aside', 'Aside column ticket-detalles-aside'],
    ] as [$needle, $label]) {
        [$st, $msg] = mustContain($viewPath, $needle, $label);
        if ($st === 'OK') {
            $ok[] = $msg;
        } else {
            $errors[] = $msg;
        }
    }
}

if (!is_file($apiPath)) {
    $errors[] = "API no existe: {$apiPath}";
} else {
    foreach ([
        ['referencias_personales', 'Campo JSON referencias_personales'],
        ['FROM referencias_personales', 'Consulta SQL referencias_personales'],
        ['referencia_telefonos', 'Consulta referencia_telefonos'],
        ['referencia_emails', 'Consulta referencia_emails'],
    ] as [$needle, $label]) {
        [$st, $msg] = mustContain($apiPath, $needle, $label);
        if ($st === 'OK') {
            $ok[] = "API: {$msg}";
        } else {
            $errors[] = "API: {$msg}";
        }
    }
}

if (is_file($cssPath)) {
    foreach ([
        ['.modal-referencias-content', 'Estilos modal referencias'],
        ['.detalle-acciones-cliente', 'Estilos botón área cliente'],
    ] as [$needle, $label]) {
        [$st, $msg] = mustContain($cssPath, $needle, $label);
        if ($st === 'OK') {
            $ok[] = "CSS: {$msg}";
        } else {
            $warnings[] = "CSS: {$msg}";
        }
    }
}

echo "--- Archivos fuente ---\n";
echo "OK (" . count($ok) . "):\n";
foreach ($ok as $m) {
    echo "  ✓ {$m}\n";
}
if ($warnings) {
    echo "\nAdvertencias (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  ⚠ {$w}\n";
    }
}
if ($errors) {
    echo "\nErrores (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  ✗ {$e}\n";
    }
}

echo "\n--- Base de datos (muestra) ---\n";
require_once $root . DIRECTORY_SEPARATOR . 'config.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SHOW TABLES LIKE 'referencias_personales'");
    if (!$stmt->fetch()) {
        echo "  ⚠ Tabla referencias_personales no existe (ejecute migration_foreclosure_v1.sql).\n";
    } else {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM referencias_personales")->fetchColumn();
        echo "  ✓ Tabla referencias_personales: {$n} filas.\n";

        $stmt = $pdo->query("
            SELECT rp.cliente_cedula, COUNT(*) AS cnt
            FROM referencias_personales rp
            GROUP BY rp.cliente_cedula
            ORDER BY cnt DESC
            LIMIT 3
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "  Ejemplos de clientes con más referencias:\n";
            foreach ($rows as $r) {
                echo "    - cliente {$r['cliente_cedula']}: {$r['cnt']} referencia(s)\n";
            }
        }
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'referencia_telefonos'");
    if ($stmt->fetch()) {
        $nt = (int) $pdo->query("SELECT COUNT(*) FROM referencia_telefonos")->fetchColumn();
        echo "  ✓ referencia_telefonos: {$nt} filas.\n";
    }
} catch (Throwable $e) {
    echo "  ⚠ BD: " . $e->getMessage() . "\n";
}

echo "\n--- Diagnóstico si no ves el botón en el navegador ---\n";
echo "1. El botón está en el HTML dentro de .ticket-detalles-aside > .detalle-grupo (sección Cliente),\n";
echo "   debajo de Dirección postal. Si la columna izquierda hace scroll, baja hasta el final del bloque Cliente.\n";
echo "2. Fuerza recarga sin caché (Ctrl+F5) o vacía caché del navegador por si sirve una vista vieja.\n";
echo "3. Confirma que abres: views/asesor_gestionar_ticket.php?id=TICKET_ID (no otra URL).\n";
echo "4. Revisa la consola (F12): un error JS antes de cargar el ticket puede impedir ver datos,\n";
echo "   pero el botón debería verse igual; si el aside entero no aparece, revise grid/CSS.\n";

exit($errors ? 1 : 0);
