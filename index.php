<?php
/**
 * CRM Internacional - Router Principal
 * Sistema de gestión de clientes y tickets
 * 
 * ROLES DEL SISTEMA:
 * 1. ADMINISTRADOR - Gestión completa del sistema
 * 2. COORDINADOR - Gestión de asesores y clientes
 * 3. ASESOR - Gestión de clientes asignados y tickets
 * 4. CLIENTE - Visualización de tickets propios
 */

// Incluir configuración (config.php inicia/configura la sesión)
require_once 'config.php';

// Rutas absolutas de redirección (evitan bucles cuando .htaccess envía a index.php
// una URL bajo views/ que ya no existe: un Location relativo "views/..." se duplicaba).
function appRedirectPath($relativePath) {
    return rtrim(APP_URL, '/') . '/' . ltrim($relativePath, '/');
}

// Función para redirigir al dashboard según el rol
function redirectToDashboard($rol) {
    switch ($rol) {
        case 'admin':
            header('Location: ' . appRedirectPath('views/admin_dashboard.php'));
            break;
        case 'coordinador':
            header('Location: ' . appRedirectPath('views/coordinador_dashboard.php'));
            break;
        case 'asesor':
            header('Location: ' . appRedirectPath('views/asesor_dashboard.php'));
            break;
        case 'cliente':
            header('Location: ' . appRedirectPath('views/cliente_dashboard.php'));
            break;
        default:
            header('Location: ' . appRedirectPath('views/login.php'));
            break;
    }
    exit();
}

// Verificar si el usuario ya está logueado
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        // Redirigir al dashboard correspondiente según el rol
        redirectToDashboard($user['rol_nombre']);
    }
}

// Si no está logueado, redirigir al login
header('Location: ' . appRedirectPath('views/login.php'));
exit();
?>