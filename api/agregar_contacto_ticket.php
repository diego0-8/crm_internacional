<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

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
$asesorCedula = (string) ($user['cedula'] ?? '');

$ticketId      = isset($_POST['ticket_id'])      ? (int) $_POST['ticket_id'] : 0;
$destino       = trim($_POST['destino'] ?? '');
$tipoContacto  = trim($_POST['tipo_contacto'] ?? '');
$valor         = trim($_POST['valor'] ?? '');

if ($ticketId <= 0 || $destino === '' || $tipoContacto === '' || $valor === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
    exit;
}

if (!in_array($tipoContacto, ['telefono', 'email'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'tipo_contacto inválido']);
    exit;
}

if ($tipoContacto === 'telefono') {
    $digits = preg_replace('/[^0-9]/', '', $valor);
    if (strlen($digits) < 7) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El teléfono debe tener al menos 7 dígitos']);
        exit;
    }
} else {
    if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido']);
        exit;
    }
    $valor = strtolower($valor);
}

$db = getDB();

$MAX_PER_PERSON = 10;

try {
    $stmt = $db->prepare("SELECT id, cliente_cedula FROM tiketera WHERE id = ? AND asesor_cedula = ? LIMIT 1");
    $stmt->execute([$ticketId, $asesorCedula]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado o no pertenece al asesor']);
        exit;
    }

    $clienteCedula = $ticket['cliente_cedula'];

    if ($destino === 'cliente') {

        if ($tipoContacto === 'telefono') {
            $table = 'cliente_telefonos';
            $countCol = 'cliente_cedula';
            $countVal = $clienteCedula;
        } else {
            $table = 'cliente_emails';
            $countCol = 'cliente_cedula';
            $countVal = $clienteCedula;
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$countCol} = ?");
        $stmt->execute([$countVal]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $MAX_PER_PERSON) {
            echo json_encode([
                'success'       => false,
                'limit_reached' => true,
                'message'       => 'Has alcanzado el límite máximo de registros. Comunícate con el administrador para seguir agregando más números.'
            ]);
            exit;
        }

        $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM {$table} WHERE {$countCol} = ?");
        $stmt->execute([$countVal]);
        $nextOrden = (int) $stmt->fetchColumn();

        if ($tipoContacto === 'telefono') {
            $numeroNorm = preg_replace('/[^0-9]/', '', $valor);
            $stmt = $db->prepare("SELECT id FROM cliente_telefonos WHERE cliente_cedula = ? AND numero_normalizado = ? LIMIT 1");
            $stmt->execute([$clienteCedula, $numeroNorm]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ese teléfono ya está registrado para este cliente']);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO cliente_telefonos (cliente_cedula, numero, numero_normalizado, tipo, orden) VALUES (?, ?, ?, 'other', ?)");
            $stmt->execute([$clienteCedula, $valor, $numeroNorm, $nextOrden]);
        } else {
            $stmt = $db->prepare("SELECT id FROM cliente_emails WHERE cliente_cedula = ? AND LOWER(email) = ? LIMIT 1");
            $stmt->execute([$clienteCedula, $valor]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ese correo ya está registrado para este cliente']);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO cliente_emails (cliente_cedula, email, orden) VALUES (?, ?, ?)");
            $stmt->execute([$clienteCedula, $valor, $nextOrden]);
        }

    } elseif (preg_match('/^referencia_(\d+)$/', $destino, $m)) {

        $referenciaId = (int) $m[1];

        $stmt = $db->prepare("SELECT id FROM referencias_personales WHERE id = ? AND cliente_cedula = ? LIMIT 1");
        $stmt->execute([$referenciaId, $clienteCedula]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Referencia no pertenece a este cliente']);
            exit;
        }

        if ($tipoContacto === 'telefono') {
            $table = 'referencia_telefonos';
        } else {
            $table = 'referencia_emails';
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE referencia_id = ?");
        $stmt->execute([$referenciaId]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $MAX_PER_PERSON) {
            echo json_encode([
                'success'       => false,
                'limit_reached' => true,
                'message'       => 'Has alcanzado el límite máximo de registros. Comunícate con el administrador para seguir agregando más números.'
            ]);
            exit;
        }

        $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM {$table} WHERE referencia_id = ?");
        $stmt->execute([$referenciaId]);
        $nextOrden = (int) $stmt->fetchColumn();

        if ($tipoContacto === 'telefono') {
            $numeroNorm = preg_replace('/[^0-9]/', '', $valor);
            $stmt = $db->prepare("SELECT id FROM referencia_telefonos WHERE referencia_id = ? AND numero_normalizado = ? LIMIT 1");
            $stmt->execute([$referenciaId, $numeroNorm]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ese teléfono ya está registrado para esta referencia']);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO referencia_telefonos (referencia_id, numero, numero_normalizado, tipo, orden) VALUES (?, ?, ?, 'other', ?)");
            $stmt->execute([$referenciaId, $valor, $numeroNorm, $nextOrden]);
        } else {
            $stmt = $db->prepare("SELECT id FROM referencia_emails WHERE referencia_id = ? AND LOWER(email) = ? LIMIT 1");
            $stmt->execute([$referenciaId, $valor]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ese correo ya está registrado para esta referencia']);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO referencia_emails (referencia_id, email, orden) VALUES (?, ?, ?)");
            $stmt->execute([$referenciaId, $valor, $nextOrden]);
        }

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Destino inválido']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Contacto agregado correctamente']);

} catch (PDOException $e) {
    error_log('agregar_contacto_ticket.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
