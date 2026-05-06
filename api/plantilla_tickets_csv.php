<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    exit('No autorizado');
}

$tipo = strtolower(trim($_GET['tipo'] ?? 'simple'));
if (!in_array($tipo, ['simple', 'foreclosure'], true)) {
    $tipo = 'simple';
}

if ($tipo === 'foreclosure') {
    $headers = [
        'Valor a devolver', 'Valor de vendido', 'Valor inicial de la subasta', 'Date Sold',
        'Case Number', 'Parcel Number', 'Type of foreclosure',
        'First Name', 'Last Name',
        'Mailing City-Street', 'Mailing State', 'Mailing ZIP Code',
        'Property Street', 'Property City', 'Property State', 'Property ZIP Code',
        'County', 'Source',
        'Age', 'Deceased',
    ];

    for ($i = 1; $i <= 5; $i++) {
        $headers[] = "Phone {$i}";
        $headers[] = "Phone {$i}: Type";
        $headers[] = "Phone {$i}: DNC/Litigator";
    }
    for ($i = 1; $i <= 5; $i++) {
        $headers[] = "Email {$i}";
    }
    for ($r = 1; $r <= 5; $r++) {
        $headers[] = "RELATIVE {$r}: First Name";
        $headers[] = "RELATIVE {$r}: Last Name";
        $headers[] = "RELATIVE {$r}: Possible Type";
        $headers[] = "RELATIVE {$r}: Age";
        for ($p = 1; $p <= 5; $p++) {
            $headers[] = "RELATIVE {$r}: Phone {$p}";
            $headers[] = "RELATIVE {$r}: Phone {$p}: Type";
            $headers[] = "RELATIVE {$r}: Phone {$p}: DNC/Litigator";
        }
        for ($e = 1; $e <= 5; $e++) {
            $headers[] = "RELATIVE {$r}: Email {$e}";
        }
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="plantilla_tickets_foreclosure.csv"');
    echo "\xEF\xBB\xBF";

    $sep = ';';
    $line = implode($sep, array_map(function ($h) {
        if (strpos($h, $sep = ';') !== false || strpos($h, '"') !== false || strpos($h, "\n") !== false) {
            return '"' . str_replace('"', '""', $h) . '"';
        }
        return $h;
    }, $headers));
    echo $line . "\r\n";
    echo str_repeat($sep, count($headers) - 1) . "\r\n";
    exit;
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="plantilla_tickets.csv"');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, [
    'cliente_cedula',
    'titulo',
    'descripcion',
    'estado',
    'asesor_cedula',
    'categoria_codigo',
    'nombre_completo',
    'email',
    'telefono',
]);
fputcsv($out, [
    '1234567890',
    'Asunto opcional del caso',
    'Descripción opcional del caso',
    'comunicacion',
    '',
    'GEN',
    '',
    '',
    '',
]);
fclose($out);
