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

// Función para redirigir al dashboard según el rol
function redirectToDashboard($rol) {
    switch ($rol) {
        case 'admin':
            header('Location: views/admin_dashboard.php');
            break;
        case 'coordinador':
            header('Location: views/coordinador_dashboard.php');
            break;
        case 'asesor':
            header('Location: views/asesor_dashboard.php');
            break;
        case 'cliente':
            header('Location: views/cliente_dashboard.php');
            break;
        default:
            header('Location: views/login.php');
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
header('Location: views/login.php');
exit();
?>