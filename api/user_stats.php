<?php
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

    // Función para calcular porcentaje de cambio
    function calculateChange($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    // Función para formatear el cambio
    function formatChange($change) {
        $sign = $change >= 0 ? '+' : '';
        return $sign . $change . '% este mes';
    }

    // Obtener estadísticas del mes actual
    $currentMonth = date('Y-m');
    $currentMonthStart = $currentMonth . '-01';
    $currentMonthEnd = date('Y-m-t', strtotime($currentMonthStart));

    // Total usuarios creados este mes
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$currentMonthStart, $currentMonthEnd]);
    $currentTotalUsers = $stmt->fetch()['total'];

    // Usuarios activos creados este mes
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios
        WHERE activo = 1 AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$currentMonthStart, $currentMonthEnd]);
    $currentActiveUsers = $stmt->fetch()['total'];

    // Coordinadores creados este mes
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE r.nombre = 'coordinador' AND DATE(u.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$currentMonthStart, $currentMonthEnd]);
    $currentCoordinators = $stmt->fetch()['total'];

    // Asesores creados este mes
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE r.nombre = 'asesor' AND DATE(u.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$currentMonthStart, $currentMonthEnd]);
    $currentAdvisors = $stmt->fetch()['total'];

    // Obtener estadísticas del mes anterior
    $previousMonth = date('Y-m', strtotime('-1 month'));
    $previousMonthStart = $previousMonth . '-01';
    $previousMonthEnd = date('Y-m-t', strtotime($previousMonthStart));

    // Total usuarios creados el mes pasado
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$previousMonthStart, $previousMonthEnd]);
    $previousTotalUsers = $stmt->fetch()['total'];

    // Usuarios activos creados el mes pasado
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios
        WHERE activo = 1 AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$previousMonthStart, $previousMonthEnd]);
    $previousActiveUsers = $stmt->fetch()['total'];

    // Coordinadores creados el mes pasado
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE r.nombre = 'coordinador' AND DATE(u.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$previousMonthStart, $previousMonthEnd]);
    $previousCoordinators = $stmt->fetch()['total'];

    // Asesores creados el mes pasado
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE r.nombre = 'asesor' AND DATE(u.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$previousMonthStart, $previousMonthEnd]);
    $previousAdvisors = $stmt->fetch()['total'];

    // Calcular porcentajes de cambio
    $totalUsersChange = calculateChange($currentTotalUsers, $previousTotalUsers);
    $activeUsersChange = calculateChange($currentActiveUsers, $previousActiveUsers);
    $coordinatorsChange = calculateChange($currentCoordinators, $previousCoordinators);
    $advisorsChange = calculateChange($currentAdvisors, $previousAdvisors);

    // Preparar respuesta
    $stats = [
        'total_users_change' => formatChange($totalUsersChange),
        'active_users_change' => formatChange($activeUsersChange),
        'coordinators_change' => formatChange($coordinatorsChange),
        'advisors_change' => formatChange($advisorsChange),
        'current_period' => date('M Y', strtotime($currentMonthStart)),
        'previous_period' => date('M Y', strtotime($previousMonthStart))
    ];

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error calculando estadísticas: ' . $e->getMessage()
    ]);
}
?>