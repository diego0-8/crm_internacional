<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/TitularModel.php';
require_once '../model/TiketeraModel.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user = getCurrentUser();
$asesorCedula = $user['cedula'];

$data = json_decode(file_get_contents('php://input'), true);
$titularId = isset($data['titular_id']) ? (int) $data['titular_id'] : 0;

if ($titularId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de titular inválido']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // 1. Obtener datos del titular
    $stmt = $db->prepare("SELECT * FROM titulares WHERE id_cliente = ? AND asesor_cedula = ?");
    $stmt->execute([$titularId, $asesorCedula]);
    $titular = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$titular) {
        throw new Exception('Titular no encontrado o no asignado a usted.');
    }

    $titularModel = new TitularModel();
    if (!$titularModel->titularTieneArchivoCsvActivo($titularId)) {
        throw new Exception('Este caso pertenece a un cargue CSV inhabilitado y no puede gestionarse.');
    }

    // 2. Obtener propiedad asociada para el caso
    $stmt = $db->prepare("SELECT * FROM propiedades WHERE id_cliente = ?");
    $stmt->execute([$titularId]);
    $propiedad = $stmt->fetch(PDO::FETCH_ASSOC);

    $caseNumber = trim((string) ($propiedad['numero_caso'] ?? ''));
    if ($caseNumber === '') {
        $caseNumber = 'TIT-' . $titularId;
    }
    $cedulaCliente = 'TIT-' . $titularId;

    // 3. Ticket existente: por titular (TIT-id) o por case number del mismo asesor
    $stmt = $db->prepare("SELECT id FROM tiketera WHERE cliente_cedula = ? LIMIT 1");
    $stmt->execute([$cedulaCliente]);
    $existingTicket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingTicket) {
        $db->commit();
        echo json_encode(['success' => true, 'ticket_id' => (int) $existingTicket['id']]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT id FROM tiketera
        WHERE numero_ticket = ? AND asesor_cedula = ?
        LIMIT 1
    ");
    $stmt->execute([$caseNumber, $asesorCedula]);
    $existingByCase = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existingByCase) {
        $db->commit();
        echo json_encode(['success' => true, 'ticket_id' => (int) $existingByCase['id']]);
        exit;
    }

    // numero_ticket único (uk_tiketera_numero_ticket): sufijo o TK- autogenerado
    $numeroTicketInsert = $caseNumber;
    $stmtDup = $db->prepare("SELECT id FROM tiketera WHERE numero_ticket = ? LIMIT 1");
    $stmtDup->execute([$numeroTicketInsert]);
    if ($stmtDup->fetch()) {
        $candidato = $numeroTicketInsert . '-T' . $titularId;
        $stmtDup->execute([$candidato]);
        $numeroTicketInsert = $stmtDup->fetch() ? null : $candidato;
    }

    // 4. Crear el cliente en la tabla `clientes`
    $stmt = $db->prepare("SELECT cedula FROM clientes WHERE cedula = ?");
    $stmt->execute([$cedulaCliente]);
    if (!$stmt->fetch()) {
        $nombreCompleto = trim($titular['primer_nombre'] . ' ' . $titular['apellido']);
        if (empty($nombreCompleto)) $nombreCompleto = 'Cliente ' . $cedulaCliente;

        $stmt = $db->prepare("
            INSERT INTO clientes (
                cedula, nombre_completo, nombre, apellido, 
                asesor_cedula, coordinador_cedula, estado,
                age, deceased, source,
                mailing_street, mailing_city, mailing_state, mailing_zip
            ) VALUES (?, ?, ?, ?, ?, ?, 'nuevo', ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cedulaCliente,
            $nombreCompleto,
            $titular['primer_nombre'] ?? '',
            $titular['apellido'] ?? '',
            $asesorCedula,
            $titular['coordinador_cedula'] ?? $user['coordinador_cedula'],
            $titular['edad'] ?? null,
            $titular['fallecido'] ?? null,
            $propiedad['fuente'] ?? null,
            $titular['mailing_calle'] ?? null,
            $titular['mailing_ciudad'] ?? null,
            $titular['mailing_estado'] ?? null,
            $titular['mailing_codigo_postal'] ?? null
        ]);

        // Migrar telefonos
        $stmtTel = $db->prepare("SELECT * FROM telefonos WHERE id_cliente = ?");
        $stmtTel->execute([$titularId]);
        foreach ($stmtTel->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $stmtInsert = $db->prepare("INSERT INTO cliente_telefonos (cliente_cedula, numero, tipo, dnc_litigator, orden) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([
                $cedulaCliente,
                $t['numero'],
                $t['tipo'] ?: 'other',
                $t['dnc_litigator'],
                $t['orden']
            ]);
        }

        // Migrar correos
        $stmtEmail = $db->prepare("SELECT * FROM correos WHERE id_cliente = ?");
        $stmtEmail->execute([$titularId]);
        foreach ($stmtEmail->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $stmtInsert = $db->prepare("INSERT INTO cliente_emails (cliente_cedula, email, orden) VALUES (?, ?, ?)");
            $stmtInsert->execute([
                $cedulaCliente,
                $e['email'],
                $e['orden']
            ]);
        }

        // Migrar referencias
        $stmtRef = $db->prepare("SELECT * FROM referencias WHERE id_cliente = ?");
        $stmtRef->execute([$titularId]);
        foreach ($stmtRef->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $stmtInsertRef = $db->prepare("
                INSERT INTO referencias_personales (cliente_cedula, nombre, apellido, possible_type, age, orden) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsertRef->execute([
                $cedulaCliente,
                $r['primer_nombre'],
                $r['apellido'],
                $r['tipo_posible'],
                $r['edad'],
                $r['orden']
            ]);
            $newRefId = $db->lastInsertId();

            // Teléfonos de referencia
            $stmtRefTel = $db->prepare("SELECT * FROM referencias_telefonos WHERE id_referencia = ?");
            $stmtRefTel->execute([$r['id_referencia']]);
            foreach ($stmtRefTel->fetchAll(PDO::FETCH_ASSOC) as $rt) {
                $db->prepare("INSERT INTO referencia_telefonos (referencia_id, numero, tipo, dnc_litigator, orden) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$newRefId, $rt['numero'], $rt['tipo'] ?: 'other', $rt['dnc_litigator'], $rt['orden']]);
            }

            // Correos de referencia
            $stmtRefEmail = $db->prepare("SELECT * FROM referencias_correos WHERE id_referencia = ?");
            $stmtRefEmail->execute([$r['id_referencia']]);
            foreach ($stmtRefEmail->fetchAll(PDO::FETCH_ASSOC) as $re) {
                $db->prepare("INSERT INTO referencia_emails (referencia_id, email, orden) VALUES (?, ?, ?)")
                   ->execute([$newRefId, $re['email'], $re['orden']]);
            }
        }
    }

    // 5. Crear el Ticket
    $stmt = $db->prepare("
        INSERT INTO tiketera (
            cliente_cedula, asesor_cedula, titulo, descripcion, 
            estado, numero_ticket, origen
        ) VALUES (?, ?, ?, ?, 'contactabilidad_cliente', ?, 'csv')
    ");
    $tituloTicket = 'Caso ' . $caseNumber;
    $stmt->execute([
        $cedulaCliente,
        $asesorCedula,
        $tituloTicket,
        'Ticket generado automáticamente desde el Titular asignado',
        $numeroTicketInsert,
    ]);

    $ticketId = (int) $db->lastInsertId();

    if ($numeroTicketInsert === null) {
        $tiketeraModel = new TiketeraModel();
        $tiketeraModel->assignNumeroTicket($ticketId);
    }

    // 6. Crear el Predio
    if ($propiedad) {
        $stmt = $db->prepare("
            INSERT INTO predios (
                cliente_cedula, ticket_id, case_number, parcel_number, type_of_foreclosure,
                property_street, property_city, property_state, property_zip,
                county, source, valor_a_devolver, valor_vendido, valor_inicial_subasta, date_sold, monetizacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $dateSold = null;
        if (!empty($propiedad['fecha_venta'])) {
            $parts = explode('/', $propiedad['fecha_venta']);
            if (count($parts) === 3) {
                $dateSold = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
            } else {
                // Fallback por si el formato cambia
                $ts = strtotime($propiedad['fecha_venta']);
                if ($ts) $dateSold = date('Y-m-d', $ts);
            }
        }

        $stmt->execute([
            $cedulaCliente,
            $ticketId,
            $propiedad['numero_caso'],
            $propiedad['numero_parcela'],
            $propiedad['tipo_foreclosure'],
            $propiedad['propiedad_calle'],
            $propiedad['propiedad_ciudad'],
            $propiedad['propiedad_estado'],
            $propiedad['propiedad_codigo_postal'],
            $propiedad['condado'],
            $propiedad['fuente'],
            empty($propiedad['excedente']) ? null : (float) str_replace(['$', ','], '', $propiedad['excedente']),
            empty($propiedad['puja_cierre']) ? null : (float) str_replace(['$', ','], '', $propiedad['puja_cierre']),
            empty($propiedad['puja_apertura']) ? null : (float) str_replace(['$', ','], '', $propiedad['puja_apertura']),
            $dateSold,
            $propiedad['monetizacion'] ?? null
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'ticket_id' => $ticketId]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error creando ticket desde titular: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
