<?php
/**
 * Sirve PDFs de tickets con autenticación (evita 404 por rutas relativas / base href).
 *
 * GET ?archivo_id=3  o  ?ticket_id=13
 */
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    exit('No autorizado');
}

if (!hasRole('asesor') && !hasRole('coordinador') && !hasRole('admin')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Acceso denegado');
}

$user = getCurrentUser();
$archivoId = isset($_GET['archivo_id']) ? (int) $_GET['archivo_id'] : 0;
$ticketId  = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;

if ($archivoId <= 0 && $ticketId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('archivo_id o ticket_id requerido');
}

/**
 * @return string|null Ruta absoluta validada dentro de uploads/tickets_pdf
 */
function ticket_pdf_resolve_path(string $rawPath): ?string {
    $rawPath = str_replace(["\0", '\\'], ['', '/'], trim($rawPath));
    if ($rawPath === '') {
        return null;
    }

    $allowedBase = realpath(__DIR__ . '/../uploads/tickets_pdf');
    if ($allowedBase === false) {
        return null;
    }

    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        return null;
    }

    $candidate = $rawPath;
    if (!preg_match('#^[a-zA-Z]:/#', $candidate) && $candidate[0] !== '/') {
        $candidate = $root . '/' . ltrim($candidate, '/');
    }
    $real = realpath($candidate);
    if ($real === false || !is_file($real)) {
        return null;
    }

    $allowedPrefix = $allowedBase . DIRECTORY_SEPARATOR;
    if (strncmp($real, $allowedPrefix, strlen($allowedPrefix)) !== 0 && $real !== $allowedBase) {
        return null;
    }

    return $real;
}

$db = getDB();
$rutaRel = null;
$downloadName = 'documento.pdf';

try {
    if ($archivoId > 0) {
        $stmt = $db->prepare("
            SELECT ta.ruta_archivo, ta.nombre_archivo, t.id AS ticket_id, t.asesor_cedula
            FROM ticket_archivos ta
            INNER JOIN tiketera t ON t.id = ta.ticket_id
            WHERE ta.id = ?
            LIMIT 1
        ");
        $stmt->execute([$archivoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            exit('Archivo no encontrado');
        }
        $ticketId = (int) $row['ticket_id'];
        $rutaRel = (string) ($row['ruta_archivo'] ?? '');
        $downloadName = (string) ($row['nombre_archivo'] ?? $downloadName);
    } else {
        $stmt = $db->prepare("
            SELECT id, pdf_archivo, asesor_cedula
            FROM tiketera WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$ticketId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            exit('Ticket no encontrado');
        }

        $pdfLegacy = trim((string) ($row['pdf_archivo'] ?? ''));
        if ($pdfLegacy !== '') {
            $rutaRel = $pdfLegacy;
            $downloadName = basename($pdfLegacy);
        } else {
            $stmt = $db->prepare("
                SELECT ruta_archivo, nombre_archivo FROM ticket_archivos
                WHERE ticket_id = ? ORDER BY fecha_subida DESC LIMIT 1
            ");
            $stmt->execute([$ticketId]);
            $arch = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$arch) {
                http_response_code(404);
                exit('Este ticket no tiene PDF adjunto');
            }
            $rutaRel = (string) ($arch['ruta_archivo'] ?? '');
            $downloadName = (string) ($arch['nombre_archivo'] ?? $downloadName);
        }
    }

    $stmt = $db->prepare('SELECT asesor_cedula FROM tiketera WHERE id = ? LIMIT 1');
    $stmt->execute([$ticketId]);
    $tkAuth = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tkAuth) {
        http_response_code(404);
        exit('Ticket no encontrado');
    }

    $cedulaUser = (string) ($user['cedula'] ?? '');
    $rol = (string) ($user['rol_nombre'] ?? '');
    $autorizado = false;

    if ($rol === 'admin') {
        $autorizado = true;
    } elseif ($rol === 'asesor' && (string) ($tkAuth['asesor_cedula'] ?? '') === $cedulaUser) {
        $autorizado = true;
    } elseif ($rol === 'coordinador') {
        $asesorDelTicket = trim((string) ($tkAuth['asesor_cedula'] ?? ''));
        if ($asesorDelTicket !== '') {
            $stmt = $db->prepare("
                SELECT u.coordinador_cedula FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.cedula = ? AND r.nombre = 'asesor' LIMIT 1
            ");
            $stmt->execute([$asesorDelTicket]);
            $coordAsesor = $stmt->fetchColumn();
            if ($coordAsesor && (string) $coordAsesor === $cedulaUser) {
                $autorizado = true;
            }
        }
    }

    if (!$autorizado) {
        http_response_code(403);
        exit('No autorizado para ver este archivo');
    }

    $filePath = ticket_pdf_resolve_path($rutaRel);
    if ($filePath === null) {
        http_response_code(404);
        exit('Archivo no encontrado en el servidor');
    }

    $downloadName = basename(str_replace(["\r", "\n", "\0"], '', $downloadName));
    if ($downloadName === '') {
        $downloadName = basename($filePath);
    }

    header('Content-Type: application/pdf');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=3600');

    readfile($filePath);
    exit;

} catch (Exception $e) {
    error_log('ver_ticket_pdf.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor');
}
