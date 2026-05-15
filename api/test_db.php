<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->query('SELECT DATABASE() AS db, VERSION() AS version');
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'message' => 'Conexión a la base de datos correcta',
        'data' => [
            'host' => DB_HOST,
            'database' => $row['db'] ?? DB_NAME,
            'server_version' => $row['version'] ?? null,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage(),
    ]);
}
