<?php
// Increase limits for analytics processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60);

header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación y permisos de admin
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = getDB();

    // Initialize analytics array with basic data first
    $analytics = [
        'overview' => [
            'total_users' => 0,
            'active_users' => 0,
            'total_clients' => 0,
            'total_tickets' => 0,
            'total_files' => 0,
            'total_logs' => 0
        ],
        'charts' => [
            'users_by_role' => [],
            'clients_by_status' => [],
            'tickets_by_status' => []
        ],
        'trends' => [
            'users_created' => [],
            'clients_created' => [],
            'tickets_created' => []
        ],
        'performance' => [
            'activity_by_hour' => [],
            'top_coordinators' => [],
            'top_advisors' => [],
            'file_statistics' => []
        ],
        'system' => [
            'active_sessions' => 0,
            'recent_activity' => []
        ],
        'changes' => [
            'users_change' => '+0%',
            'clients_change' => '+0%',
            'period' => date('M Y')
        ]
    ];

    // Basic overview statistics
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM usuarios");
        $result = $stmt->fetch();
        $analytics['overview']['total_users'] = $result ? (int)$result['count'] : 0;

        $stmt = $db->query("SELECT COUNT(*) as count FROM usuarios WHERE activo = 1");
        $result = $stmt->fetch();
        $analytics['overview']['active_users'] = $result ? (int)$result['count'] : 0;

        $stmt = $db->query("SELECT COUNT(*) as count FROM clientes");
        $result = $stmt->fetch();
        $analytics['overview']['total_clients'] = $result ? (int)$result['count'] : 0;

        $stmt = $db->query("SELECT COUNT(*) as count FROM tiketera");
        $result = $stmt->fetch();
        $analytics['overview']['total_tickets'] = $result ? (int)$result['count'] : 0;

        $stmt = $db->query("SELECT COUNT(*) as count FROM archivos_csv");
        $result = $stmt->fetch();
        $analytics['overview']['total_files'] = $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        // Continue with defaults if basic queries fail
    }

    // Simple chart data
    try {
        $stmt = $db->prepare("SELECT r.nombre as role, COUNT(u.cedula) as count FROM usuarios u JOIN roles r ON u.rol_id = r.id GROUP BY r.nombre ORDER BY count DESC");
        $stmt->execute();
        $analytics['charts']['users_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $db->prepare("SELECT estado, COUNT(*) as count FROM clientes GROUP BY estado ORDER BY count DESC");
        $stmt->execute();
        $analytics['charts']['clients_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $db->prepare("SELECT estado, COUNT(*) as count FROM tiketera GROUP BY estado ORDER BY count DESC");
        $stmt->execute();
        $analytics['charts']['tickets_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        // Continue with empty arrays if chart queries fail
    }

    echo json_encode([
        'success' => true,
        'data' => $analytics
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error obteniendo datos analíticos: ' . $e->getMessage()
    ]);
}
?>