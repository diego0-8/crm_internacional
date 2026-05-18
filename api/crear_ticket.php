<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../controller/AsesorController.php';

// Verificar autenticación: los asesores no pueden crear tickets (UI y política).
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
if (hasRole('asesor')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Los asesores no pueden crear tickets. La creación la asigna el coordinador o administración.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$asesorController = new AsesorController();
$user = getCurrentUser();

try {
    $data = [
        'cliente_cedula' => $_POST['cliente_cedula'] ?? '',
        'asesor_cedula'  => $user['cedula'],
        'titulo'         => sanitize($_POST['titulo'] ?? ''),
        'descripcion'    => sanitize($_POST['descripcion'] ?? ''),
        'observaciones'  => sanitize($_POST['observaciones'] ?? ''),
        'estado'         => 'contactabilidad_cliente',
    ];

    $categoriaId = isset($_POST['categoria_id']) ? (int) $_POST['categoria_id'] : 0;
    if ($categoriaId > 0) {
        $data['categoria_id'] = $categoriaId;
    }

    if (empty($data['cliente_cedula'])) {
        throw new Exception('El cliente es requerido');
    }
    if ($data['titulo'] === '') {
        // El asunto es opcional desde la UI nueva; tomamos un valor por defecto
        // basado en el cliente para no quedar vacío.
        $data['titulo'] = 'Ticket cliente ' . substr((string) $data['cliente_cedula'], 0, 32);
    }
    
    // Manejar subida de PDF
    if (isset($_FILES['pdf_archivo']) && $_FILES['pdf_archivo']['error'] === UPLOAD_ERR_OK) {
        $uploadDirFs = __DIR__ . '/../uploads/tickets_pdf/';
        $uploadDirRel = 'uploads/tickets_pdf/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDirFs)) {
            mkdir($uploadDirFs, 0755, true);
        }
        
        $file = $_FILES['pdf_archivo'];
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Subida inválida');
        }

        $clienteCedulaSafe = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $data['cliente_cedula']);
        $fileName = 'ticket_' . time() . '_' . $clienteCedulaSafe . '.pdf';
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
            $data['pdf_archivo'] = $uploadDirRel . $fileName;
        } else {
            throw new Exception('Error al subir el archivo PDF');
        }
    }
    
    $result = $asesorController->crearTicket($data);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("crear_ticket.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>

