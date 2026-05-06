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
    $data = [
        'cedula' => sanitize($_POST['cedula'] ?? ''),
        'usuario' => sanitize($_POST['usuario'] ?? ''),
        'nombre' => sanitize($_POST['nombre'] ?? ''),
        'apellido' => sanitize($_POST['apellido'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'telefono' => sanitize($_POST['telefono'] ?? ''),
        'sip_extension' => trim((string) ($_POST['sip_extension'] ?? '')),
        'sip_secret' => isset($_POST['sip_secret']) ? trim((string) $_POST['sip_secret']) : '',
        'rol_id' => (int)($_POST['rol_id'] ?? 0),
        'coordinador_cedula' => !empty($_POST['coordinador_cedula']) ? sanitize($_POST['coordinador_cedula']) : null,
        'activo' => 1,
        'pdf_documento' => null
    ];
    
    // Manejar subida de PDF
    if (isset($_FILES['pdf_documento']) && $_FILES['pdf_documento']['error'] === UPLOAD_ERR_OK) {
        $uploadDirFs = __DIR__ . '/../uploads/usuarios_pdf/';
        $uploadDirRel = 'uploads/usuarios_pdf/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDirFs)) {
            mkdir($uploadDirFs, 0755, true);
        }
        
        $file = $_FILES['pdf_documento'];
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Subida inválida');
        }

        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $data['cedula']) . '_' . time() . '.pdf';
        $filePath = $uploadDirFs . $fileName;
        
        // Validar que sea PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) finfo_close($finfo);
        if (!in_array($fileType, ['application/pdf', 'application/x-pdf'], true)) {
            throw new Exception('El archivo debe ser un PDF válido');
        }
        
        // Validar tamaño (máximo 50MB)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $maxSize) {
            throw new Exception('El archivo PDF no puede ser mayor a 50MB');
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $data['pdf_documento'] = $uploadDirRel . $fileName;
        } else {
            throw new Exception('Error al subir el archivo PDF');
        }
    }
    
    $result = $userController->createUser($data);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    error_log("create_user.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
