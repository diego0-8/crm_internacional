<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../model/RoleModel.php';

// Verificar autenticación
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinador'))) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$roleModel = new RoleModel();

try {
    $roles = $roleModel->getAllRoles();
    echo json_encode($roles);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
