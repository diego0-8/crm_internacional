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
    if ($fechaProximaAccion !== '') {
        $fechaProximaAccion = preg_replace('/\s*T\s*/', ' ', $fechaProximaAccion);
    }

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

    // Primera gestión: el titular deja de aparecer en «Mis casos (reparto)» del asesor.
    try {
        $db->prepare("
            UPDATE tiketera
            SET primera_gestion_at = COALESCE(primera_gestion_at, NOW())
            WHERE id = ? AND asesor_cedula = ?
        ")->execute([$ticketId, $asesorCedula]);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'primera_gestion_at') === false && stripos($msg, 'Unknown column') === false) {
            throw $e;
        }
    }

    $perfilRaw = isset($_POST['perfilacion_contactabilidad']) ? (string) $_POST['perfilacion_contactabilidad'] : '';
    if ($perfilRaw !== '') {
        $decoded = json_decode($perfilRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $perfilSan = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            if (strlen($perfilSan) > 16384) {
                $perfilSan = substr($perfilSan, 0, 16384);
            }
            try {
                $db->prepare(
                    "UPDATE tiketera SET perfilacion_contactabilidad = ? WHERE id = ? AND asesor_cedula = ?"
                )->execute([$perfilSan, $ticketId, $asesorCedula]);
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'perfilacion_contactabilidad') === false && stripos($msg, 'Unknown column') === false) {
                    throw $e;
                }
            }
        }
    }

    /** @var array<string,mixed>|null */
    $payloadPerfilActGuardado = null;

    $perfActRaw = isset($_POST['perfilacion_actualizacion']) ? (string) $_POST['perfilacion_actualizacion'] : '';
    if ($perfActRaw !== '') {
        $decAct = json_decode($perfActRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decAct)) {
            $r = strtolower((string) ($decAct['respuesta'] ?? ''));
            if (in_array($r, ['si', 'no'], true)) {
                $stmtEf = $db->prepare("SELECT estado FROM tiketera WHERE id = ? AND asesor_cedula = ?");
                $stmtEf->execute([$ticketId, $asesorCedula]);
                $filE = $stmtEf->fetch();
                $estadoEfectivoPerf = is_array($filE) ? strtolower((string) ($filE['estado'] ?? '')) : '';
                if ($estadoEfectivoPerf === '' || !in_array($estadoEfectivoPerf, TiketeraModel::ESTADOS, true)) {
                    $estadoEfectivoPerf = 'contactabilidad_cliente';
                }
                if ($estadoEfectivoPerf !== 'contactabilidad_cliente') {
                    $esDesembolsoPerf = ($estadoEfectivoPerf === 'desembolso');
                    $accionSiT = $esDesembolsoPerf ? 'Actualizamos sistema' : 'Actualización en el sistema';
                    $accionNoT = $esDesembolsoPerf ? 'Lanzamos alerta Clean Trust' : 'Escalamos con director de la operación';
                    $detCliente = trim((string) ($decAct['accion_confirmada'] ?? ''));
                    $esperadoT = ($r === 'si') ? $accionSiT : $accionNoT;

                    if ($detCliente !== '' && mb_strlen($detCliente, 'UTF-8') <= 500 && $detCliente === $esperadoT) {
                        if ($proximaAccion === '' || $fechaProximaAccion === '') {
                            http_response_code(422);
                            echo json_encode([
                                'success' => false,
                                'message' => 'Para registrar la tipificación en el historial debe completar «Próxima acción» y «Fecha de próxima acción».',
                            ]);
                            exit;
                        }

                        $decActSan = [
                            'version'            => 2,
                            'respuesta'          => $r,
                            'estado_al_guardar'  => $estadoEfectivoPerf,
                            'variante'           => $esDesembolsoPerf ? 'desembolso' : 'pipeline',
                            'accion_si'          => $accionSiT,
                            'accion_no'          => $accionNoT,
                            'accion_confirmada'  => $detCliente,
                        ];
                        $payloadPerfilActGuardado = $decActSan;

                        $jsAct = json_encode($decActSan, JSON_UNESCAPED_UNICODE);
                        if (strlen($jsAct) > 4096) {
                            $jsAct = substr($jsAct, 0, 4096);
                        }
                        try {
                            $db->prepare(
                                "UPDATE tiketera SET perfilacion_actualizacion = ? WHERE id = ? AND asesor_cedula = ?"
                            )->execute([$jsAct, $ticketId, $asesorCedula]);
                        } catch (PDOException $e) {
                            $msg = $e->getMessage();
                            if (stripos($msg, 'perfilacion_actualizacion') === false && stripos($msg, 'Unknown column') === false) {
                                throw $e;
                            }
                        }
                    }
                }
            }
        }
    }

    if (is_array($payloadPerfilActGuardado)) {
        $estLabelTi = TiketeraModel::estadoLabelFor((string) $payloadPerfilActGuardado['estado_al_guardar']);
        $primeraLbl = (($payloadPerfilActGuardado['respuesta'] ?? '') === 'si') ? 'Sí' : 'No';
        $accionTxt = (string) ($payloadPerfilActGuardado['accion_confirmada'] ?? '');
        $contenidoTipif = json_encode([
            'tipo'                => 'tipificacion_actualizacion_v1',
            'version'             => 1,
            'estado_key'          => (string) ($payloadPerfilActGuardado['estado_al_guardar'] ?? ''),
            'estado_label'        => $estLabelTi,
            'aplica_gestion'      => $primeraLbl,
            'accion_confirmada'   => $accionTxt,
        ], JSON_UNESCAPED_UNICODE);

        $tieneTipoNota = false;
        try {
            $chkTipo = $db->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ticket_notas'
                  AND COLUMN_NAME = 'tipo_nota'
            ");
            $tieneTipoNota = ((int) $chkTipo->fetchColumn()) > 0;
        } catch (Exception $e) {
            $tieneTipoNota = false;
        }

        if ($tieneTipoNota) {
            try {
                $stmtT = $db->prepare(
                    "
                    INSERT INTO ticket_notas (
                        ticket_id, asesor_cedula, tipo_nota, estado_ticket, contenido,
                        proxima_accion, fecha_proxima_accion, fecha_creacion
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    "
                );
                $stmtT->execute([
                    $ticketId,
                    $asesorCedula,
                    'tipificacion_actualizacion',
                    $payloadPerfilActGuardado['estado_al_guardar'],
                    $contenidoTipif,
                    $proximaAccion ?: null,
                    $fechaProximaAccion ?: null,
                ]);
            } catch (PDOException $e) {
                try {
                    $stmtTf = $db->prepare(
                        "
                        INSERT INTO ticket_notas (
                            ticket_id, asesor_cedula, estado_ticket, contenido,
                            proxima_accion, fecha_proxima_accion, fecha_creacion
                        )
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                        "
                    );
                    $stmtTf->execute([
                        $ticketId,
                        $asesorCedula,
                        $payloadPerfilActGuardado['estado_al_guardar'],
                        $contenidoTipif,
                        $proximaAccion ?: null,
                        $fechaProximaAccion ?: null,
                    ]);
                } catch (PDOException $e2) {
                    $stmtTn2 = $db->prepare(
                        "
                        INSERT INTO ticket_notas (ticket_id, asesor_cedula, contenido, proxima_accion, fecha_proxima_accion, fecha_creacion)
                        VALUES (?, ?, ?, ?, ?, NOW())
                        "
                    );
                    $stmtTn2->execute([
                        $ticketId,
                        $asesorCedula,
                        $contenidoTipif,
                        $proximaAccion ?: null,
                        $fechaProximaAccion ?: null,
                    ]);
                }
            }
        } else {
            $stmtTn = $db->prepare(
                "
                INSERT INTO ticket_notas (
                    ticket_id, asesor_cedula, estado_ticket, contenido,
                    proxima_accion, fecha_proxima_accion, fecha_creacion
                )
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                "
            );
            try {
                $stmtTn->execute([
                    $ticketId,
                    $asesorCedula,
                    $payloadPerfilActGuardado['estado_al_guardar'],
                    $contenidoTipif,
                    $proximaAccion ?: null,
                    $fechaProximaAccion ?: null,
                ]);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'estado_ticket') !== false ||
                    strpos($e->getMessage(), 'Unknown column') !== false) {
                    $stmtTn2 = $db->prepare(
                        "
                        INSERT INTO ticket_notas (ticket_id, asesor_cedula, contenido, proxima_accion, fecha_proxima_accion, fecha_creacion)
                        VALUES (?, ?, ?, ?, ?, NOW())
                        "
                    );
                    $stmtTn2->execute([
                        $ticketId,
                        $asesorCedula,
                        $contenidoTipif,
                        $proximaAccion ?: null,
                        $fechaProximaAccion ?: null,
                    ]);
                } else {
                    throw $e;
                }
            }
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
