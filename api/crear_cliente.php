<?php
require_once '../config.php';
require_once '../controller/CoordinadorController.php';

header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$coordinadorController = new CoordinadorController();

try {
    // Validar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y sanitizar datos del formulario
    $clienteData = [
        'cedula' => sanitize($_POST['cedula'] ?? ''),
        'nombre' => sanitize($_POST['nombre'] ?? ''),
        'apellido' => sanitize($_POST['apellido'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'telefono' => sanitize($_POST['telefono'] ?? ''),
        'empresa' => sanitize($_POST['empresa'] ?? ''),
        'cargo' => sanitize($_POST['cargo'] ?? ''),
        'direccion' => sanitize($_POST['direccion'] ?? ''),
        'ciudad' => sanitize($_POST['ciudad'] ?? ''),
        'pais' => sanitize($_POST['pais'] ?? 'Colombia'),
        'codigo_postal' => sanitize($_POST['codigo_postal'] ?? ''),
        'notas' => sanitize($_POST['notas'] ?? ''),
        'asesor_cedula' => sanitize($_POST['asesor_cedula'] ?? ''),
        'coordinador_cedula' => $_SESSION['user_cedula'],
        'estado' => 'nuevo'
    ];

    // Validaciones básicas
    if (empty($clienteData['cedula']) || empty($clienteData['nombre']) || empty($clienteData['apellido']) || empty($clienteData['asesor_cedula'])) {
        throw new Exception('Cédula, nombre, apellido y asesor son campos obligatorios');
    }

    if (!preg_match('/^[0-9]{7,20}$/', $clienteData['cedula'])) {
        throw new Exception('La cédula debe contener solo números (7-20 dígitos)');
    }

    if (!empty($clienteData['email']) && !filter_var($clienteData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El email no tiene un formato válido');
    }

    // Verificar que el asesor existe y pertenece a este coordinador
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.cedula FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE u.cedula = ? AND r.nombre = 'asesor' AND u.coordinador_cedula = ?
    ");
    $stmt->execute([$clienteData['asesor_cedula'], $_SESSION['user_cedula']]);
    if (!$stmt->fetch()) {
        throw new Exception('El asesor seleccionado no es válido o no pertenece a su coordinación');
    }

    // Verificar que no exista un cliente con la misma cédula
    $stmt = $db->prepare("SELECT cedula FROM clientes WHERE cedula = ?");
    $stmt->execute([$clienteData['cedula']]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un cliente con esta cédula');
    }

    $result = $coordinadorController->crearCliente($clienteData);

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>