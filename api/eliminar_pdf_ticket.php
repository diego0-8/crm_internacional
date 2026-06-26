<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

http_response_code(403);
echo json_encode([
    'success' => false,
    'message' => 'La eliminación de archivos PDF está deshabilitada'
]);
