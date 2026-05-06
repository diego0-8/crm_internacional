<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/UserModel.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !hasRole('coordinador')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userModel = new UserModel();

try {
    // Validar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y sanitizar datos
    $cedula = sanitize($_POST['cedula'] ?? '');
    $usuario = sanitize($_POST['usuario'] ?? '');
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $rolId = (int)($_POST['rol_id'] ?? 0);
    $asesorCedula = sanitize($_POST['asesor_cedula'] ?? '');

    // Validaciones básicas
    if (empty($cedula) || empty($usuario) || empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
        throw new Exception('Todos los campos obligatorios deben ser completados');
    }

    if ($password !== $confirmPassword) {
        throw new Exception('Las contraseñas no coinciden');
    }

    if (strlen($password) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El email no tiene un formato válido');
    }

    if (!preg_match('/^[0-9]{7,20}$/', $cedula)) {
        throw new Exception('La cédula debe contener solo números (7-20 dígitos)');
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $usuario)) {
        throw new Exception('El usuario debe tener entre 3-20 caracteres alfanuméricos');
    }

    // Verificar que el rol sea válido (solo asesor para coordinadores)
    $db = getDB();
    $stmt = $db->prepare("SELECT id, nombre FROM roles WHERE id = ? AND nombre = 'asesor'");
    $stmt->execute([$rolId]);
    $rol = $stmt->fetch();

    if (!$rol) {
        throw new Exception('Rol no válido');
    }

    // Verificar que no exista la cédula
    $existingUser = $userModel->getUserByCedula($cedula);
    if ($existingUser) {
        throw new Exception('Ya existe un usuario con esta cédula');
    }

    // Verificar que no exista el usuario
    $existingUser = $userModel->getUserByUsername($usuario);
    if ($existingUser) {
        throw new Exception('Ya existe un usuario con este nombre de usuario');
    }

    // Verificar que no exista el email
    $stmt = $db->prepare("SELECT cedula FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un usuario con este email');
    }

    // Si se especifica un asesor, verificar que existe y pertenece a este coordinador
    if (!empty($asesorCedula)) {
        $stmt = $db->prepare("
            SELECT u.cedula FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE u.cedula = ? AND r.nombre = 'asesor' AND u.coordinador_cedula = ?
        ");
        $stmt->execute([$asesorCedula, $_SESSION['user_cedula']]);
        if (!$stmt->fetch()) {
            throw new Exception('El asesor seleccionado no es válido o no pertenece a su coordinación');
        }
    }

    // Crear el usuario
    $userData = [
        'cedula' => $cedula,
        'usuario' => $usuario,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'password' => $password, // El modelo se encarga del hash
        'telefono' => $telefono,
        'rol_id' => $rolId,
        'coordinador_cedula' => $_SESSION['user_cedula'], // El coordinador actual
        'activo' => true
    ];

    $result = $userModel->createUser($userData);

    if ($result) {
        // Log de actividad
        logActivity('user_created', "Usuario creado: {$usuario} ({$cedula}) por coordinador {$_SESSION['user_cedula']}");

        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => [
                'cedula' => $cedula,
                'usuario' => $usuario,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'rol' => $rol['nombre']
            ]
        ]);
    } else {
        throw new Exception('Error al crear el usuario en la base de datos');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>