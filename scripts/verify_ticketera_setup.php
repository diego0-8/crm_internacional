#!/usr/bin/env php
<?php
/**
 * Verificación Tiketera v2: conexión, tablas y columnas clave + sintaxis PHP.
 * Uso: php scripts/verify_ticketera_setup.php
 * Código de salida distinto de 0 si falla.
 */

$root = dirname(__DIR__);
require_once $root . '/config.php';

$errors = [];
$ok = [];

try {
    $db = getDB();
    $ok[] = 'Conexión PDO OK';
} catch (Throwable $e) {
    fwrite(STDERR, "FATAL: " . $e->getMessage() . "\n");
    exit(1);
}

$tables = [
    'ticket_categorias',
    'sla_policies',
    'sla_tracking',
    'ticket_import_batches',
    'ticket_import_filas',
    'tiketera',
];

foreach ($tables as $t) {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($t));
    if (!$stmt->fetch()) {
        $errors[] = "Tabla faltante: {$t} (ejecute database/migration_ticketera_v2.sql o reinstale desde database.sql)";
    } else {
        $ok[] = "Tabla {$t} existe";
    }
}

$colsRequired = [
    'numero_ticket',
    'categoria_id',
    'origen',
    'import_batch_id',
    'sla_politica_id',
    'sla_respuesta_limite',
    'sla_resolucion_limite',
];

try {
    $stmt = $db->query("SHOW COLUMNS FROM tiketera");
    $have = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $have[$row['Field']] = true;
    }
    $missingCols = [];
    foreach ($colsRequired as $c) {
        if (empty($have[$c])) {
            $missingCols[] = $c;
        }
    }
    if ($missingCols) {
        $errors[] = 'Faltan columnas en tiketera: ' . implode(', ', $missingCols) . ' — ejecute database/migration_ticketera_v2.sql';
    } else {
        $ok[] = 'Columnas Tiketera v2 en tiketera OK';
    }
} catch (Throwable $e) {
    $errors[] = 'No se pudo leer columnas de tiketera: ' . $e->getMessage();
}

$phpFiles = [
    $root . '/model/TiketeraModel.php',
    $root . '/controller/TicketImportController.php',
    $root . '/api/coordinador_import_tickets_csv.php',
    $root . '/api/coordinador_ticket_imports.php',
    $root . '/api/coordinador_ticket_import_errors.php',
    $root . '/api/plantilla_tickets_csv.php',
    $root . '/api/ticket_categorias.php',
    $root . '/views/coordinador_tickets_import.php',
    $root . '/views/cliente_mis_tickets.php',
];

foreach ($phpFiles as $file) {
    if (!is_readable($file)) {
        $errors[] = 'Archivo no legible: ' . str_replace($root . '/', '', $file);
        continue;
    }
    $out = [];
    $ret = 0;
    $phpBin = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
    exec($phpBin . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $ret);
    if ($ret !== 0) {
        $errors[] = 'php -l falló: ' . $file . ' → ' . implode("\n", $out);
    } else {
        $ok[] = 'Syntax OK ' . basename($file);
    }
}

foreach ($ok as $m) {
    echo "[OK] {$m}\n";
}
foreach ($errors as $m) {
    fwrite(STDERR, "[ERROR] {$m}\n");
}

exit(empty($errors) ? 0 : 2);
