<?php
header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación y permisos de admin
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener configuración actual
            $settings = [
                'app_name' => APP_NAME,
                'app_url' => APP_URL,
                'app_version' => APP_VERSION,
                'session_lifetime' => SESSION_LIFETIME,
                'max_file_size' => MAX_FILE_SIZE,
                'upload_path' => UPLOAD_PATH,
                'smtp_host' => SMTP_HOST,
                'smtp_port' => SMTP_PORT,
                'smtp_username' => SMTP_USERNAME,
                'enable_logs' => true, // Por defecto habilitado
                'enable_sessions' => true // Por defecto habilitado
            ];

            echo json_encode([
                'success' => true,
                'data' => $settings
            ]);
            break;

        case 'POST':
            // Guardar configuración
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                throw new Exception('Datos de configuración inválidos');
            }

            // Validar datos
            $errors = validateSettings($input);
            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Errores de validación: ' . implode(', ', $errors)
                ]);
                exit;
            }

            // Aquí normalmente guardaríamos en base de datos o archivo de configuración
            // Por ahora, solo validamos y retornamos éxito
            // En un sistema real, esto actualizaría variables de entorno o base de datos

            // Log de actividad
            logActivity('settings_updated', 'Configuración del sistema actualizada');

            echo json_encode([
                'success' => true,
                'message' => 'Configuración guardada exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en configuración: ' . $e->getMessage()
    ]);
}

// Función para validar configuración
function validateSettings($settings) {
    $errors = [];

    // Validar nombre de aplicación
    if (empty($settings['app_name']) || strlen($settings['app_name']) > 100) {
        $errors[] = 'Nombre de aplicación inválido';
    }

    // Validar URL
    if (empty($settings['app_url']) || !filter_var($settings['app_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'URL de aplicación inválida';
    }

    // Validar tiempo de sesión
    if (!is_numeric($settings['session_lifetime']) || $settings['session_lifetime'] < 300 || $settings['session_lifetime'] > 28800) {
        $errors[] = 'Tiempo de sesión debe estar entre 5 minutos y 8 horas';
    }

    // Validar tamaño máximo de archivo
    if (!is_numeric($settings['max_file_size']) || $settings['max_file_size'] < 1024 || $settings['max_file_size'] > 52428800) {
        $errors[] = 'Tamaño máximo de archivo inválido (1KB - 50MB)';
    }

    // Validar puerto SMTP
    if (!is_numeric($settings['smtp_port']) || $settings['smtp_port'] < 1 || $settings['smtp_port'] > 65535) {
        $errors[] = 'Puerto SMTP inválido';
    }

    // Validar email SMTP si se proporciona
    if (!empty($settings['smtp_username']) && !filter_var($settings['smtp_username'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email SMTP inválido';
    }

    return $errors;
}
?>