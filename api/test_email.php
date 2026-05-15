<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

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

$user = trim((string) (SMTP_USERNAME ?? ''));
$pass = trim((string) (SMTP_PASSWORD ?? ''));
$host = trim((string) (SMTP_HOST ?? ''));

if ($host === '' || $user === '' || $pass === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Configure SMTP_HOST, SMTP_USERNAME y SMTP_PASSWORD en config.php (o variables de entorno equivalentes) antes de probar el envío.',
    ]);
    exit;
}

// Sin librería SMTP en el proyecto: no enviamos correo real; solo validamos que hay credenciales.
echo json_encode([
    'success' => true,
    'message' => 'Credenciales SMTP configuradas. El envío real no está implementado en esta instalación; use un cliente SMTP o integre PHPMailer si necesita prueba de entrega.',
    'data' => [
        'smtp_host' => $host,
        'smtp_port' => (int) SMTP_PORT,
        'smtp_username_set' => true,
    ],
]);
