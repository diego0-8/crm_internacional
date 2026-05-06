<?php
require_once __DIR__ . '/../config.php';

class CoordinadorViewController {
    
    public function __construct() {
        // Constructor del controlador
    }
    
    /**
     * Mostrar el dashboard del coordinador
     */
    public function showDashboard() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('coordinador')) {
            redirect('../views/login.php');
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/coordinador_dashboard.php';
    }
    
    /**
     * Mostrar la vista de tareas
     */
    public function showTareas() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('coordinador')) {
            redirect('../views/login.php');
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/coordinador_tareas.php';
    }
    
    /**
     * Mostrar la vista de gestión CSV
     */
    public function showGestion() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('coordinador')) {
            redirect('../views/login.php');
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/coordinador_gestion.php';
    }
    
    /**
     * Mostrar la vista de exporte
     */
    public function showExporte() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('coordinador')) {
            redirect('../views/login.php');
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/coordinador_exporte.php';
    }
    
    /**
     * Mostrar la vista de archivos CSV
     */
    public function showArchivos() {
        // Verificar autenticación y permisos
        if (!isLoggedIn() || !hasRole('coordinador')) {
            redirect('../views/login.php');
        }
        
        $user = getCurrentUser();
        $message = getMessage();
        
        // Incluir la vista
        include __DIR__ . '/../views/coordinador_archivos.php';
    }
}
?>
