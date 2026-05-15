<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/TiketeraModel.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$asesorCedula = $user['cedula'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $ticketId           = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
    $estado             = sanitize($_POST['estado'] ?? '');
    $descripcion        = isset($_POST['descripcion']) ? sanitize($_POST['descripcion']) : null;
    $observaciones      = isset($_POST['observaciones']) ? sanitize($_POST['observaciones']) : null;
    $nuevaNota          = sanitize($_POST['nueva_nota'] ?? '');
    $proximaAccion      = sanitize($_POST['proxima_accion'] ?? '');
    $fechaProximaAccion = sanitize($_POST['fecha_proxima_accion'] ?? '');

    if ($ticketId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ticket_id requerido']);
        exit;
    }

    $db = getDB();

    // Verificar que el ticket pertenece al asesor y obtener estado actual.
    $stmt = $db->prepare("SELECT id, estado FROM tiketera WHERE id = ? AND asesor_cedula = ?");
    $stmt->execute([$ticketId, $asesorCedula]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o no autorizado']);
        exit;
    }
    $estadoActual = (string) $row['estado'];

    $tiketera = new TiketeraModel();

    // Si llega un estado nuevo válido y distinto, cambiar usando el modelo (valida transición + historial).
    if ($estado !== '' && $estado !== $estadoActual) {
        if (!in_array($estado, TiketeraModel::ESTADOS, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estado no soportado']);
            exit;
        }
        if (!TiketeraModel::isValidTransition($estadoActual, $estado)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => "Transición no permitida: {$estadoActual} → {$estado}",
            ]);
            exit;
        }

        try {
            $tiketera->updateTicketEstado($ticketId, $estado, $asesorCedula, $observaciones);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code === 422 ? 422 : ($code === 404 ? 404 : 500));
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    } else {
        // Sin cambio de estado: actualizar campos editables.
        $campos = [];
        $valores = [];
        if ($descripcion !== null) {
            $campos[] = 'descripcion = ?';
            $valores[] = $descripcion;
        }
        if ($observaciones !== null) {
            $campos[] = 'observaciones = ?';
            $valores[] = $observaciones;
        }
        if (!empty($campos)) {
            $campos[] = 'fecha_actualizacion = CURRENT_TIMESTAMP';
            $valores[] = $ticketId;
            $valores[] = $asesorCedula;
            $upd = $db->prepare(
                'UPDATE tiketera SET ' . implode(', ', $campos) . ' WHERE id = ? AND asesor_cedula = ?'
            );
            $upd->execute($valores);
        }
    }

    // Adjuntar PDF nuevo (sin tocar los previos).
    if (isset($_FILES['nuevo_pdf']) && ($_FILES['nuevo_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $pdfFile = $_FILES['nuevo_pdf'];

        if (!isset($pdfFile['tmp_name']) || !is_uploaded_file($pdfFile['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Subida inválida']);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $pdfFile['tmp_name']) : null;
        if ($finfo) finfo_close($finfo);
        if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF']);
            exit;
        }

        $maxSize = 50 * 1024 * 1024;
        if ($pdfFile['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'El archivo PDF no puede ser mayor a 50MB']);
            exit;
        }

        $uploadDirFs  = __DIR__ . '/../uploads/tickets_pdf/';
        $uploadDirRel = 'uploads/tickets_pdf/';
        if (!is_dir($uploadDirFs)) {
            mkdir($uploadDirFs, 0755, true);
        }

        $fileName = 'ticket_' . $ticketId . '_' . time() . '.pdf';
        $filePath = $uploadDirFs . $fileName;

        if (move_uploaded_file($pdfFile['tmp_name'], $filePath)) {
            $stmt = $db->prepare("
                INSERT INTO ticket_archivos (ticket_id, asesor_cedula, nombre_archivo, ruta_archivo, fecha_subida)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ticketId, $asesorCedula, $fileName, $uploadDirRel . $fileName]);
        }
    }

    if (!empty($nuevaNota)) {
        $estadoParaNota = $estadoActual;
        if ($estado !== '' && in_array($estado, TiketeraModel::ESTADOS, true)) {
            $estadoParaNota = $estado;
        }
        try {
            $stmt = $db->prepare("
                INSERT INTO ticket_notas (
                    ticket_id, asesor_cedula, estado_ticket, contenido,
                    proxima_accion, fecha_proxima_accion, fecha_creacion
                )
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $ticketId,
                $asesorCedula,
                $estadoParaNota,
                $nuevaNota,
                $proximaAccion ?: null,
                $fechaProximaAccion ?: null,
            ]);
        } catch (PDOException $e) {
            // BD sin columna estado_ticket (esquema antiguo)
            if (strpos($e->getMessage(), 'estado_ticket') === false) {
                throw $e;
            }
            $stmt = $db->prepare("
                INSERT INTO ticket_notas (ticket_id, asesor_cedula, contenido, proxima_accion, fecha_proxima_accion, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ticketId, $asesorCedula, $nuevaNota, $proximaAccion ?: null, $fechaProximaAccion ?: null]);
        }
    }

    // Devolver historial actualizado para que el cliente pinte la línea de tiempo sin recargar.
    $hist = $tiketera->getHistorialEstado($ticketId);

    echo json_encode([
        'success' => true,
        'message' => 'Ticket actualizado exitosamente',
        'historial' => $hist['historial'],
        'total_segundos' => $hist['total_segundos'],
    ]);

} catch (Exception $e) {
    error_log("Error en gestionar_ticket.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
