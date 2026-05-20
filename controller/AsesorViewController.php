<?php
require_once __DIR__ . '/../config.php';

class AsesorViewController {
    
    public function __construct() {
        // Constructor del controlador
    }
    
    /**
     * Mostrar el dashboard del asesor
     */
    public function showDashboard() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('asesor')) {
            app_set_route('login');
            app_redirect_home();
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/asesor_dashboard.php';
    }
    
    /**
     * Mostrar la vista de tickets
     */
    public function showTickets() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('asesor')) {
            app_set_route('login');
            app_redirect_home();
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/asesor_tickets.php';
    }
}
?>
