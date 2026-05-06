<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!hasRole('asesor') && !hasRole('coordinador') && !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, codigo, nombre FROM ticket_categorias WHERE activo = 1 ORDER BY nombre");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
