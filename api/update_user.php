<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../controller/UserController.php';

// Verificar autenticación
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$userController = new UserController();

try {
    $cedula = trim((string) ($_POST['id'] ?? $_POST['cedula'] ?? ''));
    
    if ($cedula === '') {
        throw new Exception('Identificador de usuario requerido');
    }
    
    $data = [
        'nombre' => sanitize($_POST['nombre'] ?? ''),
        'apellido' => sanitize($_POST['apellido'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'telefono' => sanitize($_POST['telefono'] ?? ''),
        'rol_id' => (int)($_POST['rol_id'] ?? 0),
        'coordinador_cedula' => !empty($_POST['coordinador_cedula']) ? sanitize($_POST['coordinador_cedula']) : null,
        // Vacío = quitar extensión en BD
        'sip_extension' => trim((string) ($_POST['sip_extension'] ?? '')),
    ];

    // Clave SIP: solo actualizar si el admin escribe un valor nuevo
    $sipSecret = trim((string) ($_POST['sip_secret'] ?? ''));
    if ($sipSecret !== '') {
        $data['sip_secret'] = $sipSecret;
    }
    
    // Contraseña: vacío en edición = mantener la actual
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    
    $result = $userController->updateUser($cedula, $data);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
