<?php
require_once __DIR__ . '/../config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario actual
$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exporte de Métricas - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/role-specific.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/asesor.css" rel="stylesheet">
    <link href="../css/coordinador.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo logo-asesor">
                    <img src="../img/logo2.png" alt="Logo CRM">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Coordinador</div>
                    <a href="coordinador_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="coordinador_tareas.php" class="nav-item">
                        <i class="fas fa-tasks"></i>
                        Tareas
                    </a>
                    <a href="coordinador_gestion.php" class="nav-item">
                        <i class="fas fa-upload"></i>
                        Gestión CSV
                    </a>
                    <a href="coordinador_exporte.php" class="nav-item active">
                        <i class="fas fa-download"></i>
                        Exporte
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="profile-card">
                    <div class="profile-avatar avatar-asesor">
                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h4 class="text-asesor"><?php echo $user['nombre'] . ' ' . $user['apellido']; ?></h4>
                        <p class="text-asesor">Coordinador</p>
                    </div>
                </div>
                
                <button class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <div class="top-header header-asesor">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1 class="title-asesor">Exporte de Métricas</h1>
                        <p class="subtitle-asesor">Exporta métricas y reportes de rendimiento en formato CSV.</p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <?php if ($message): ?>
                    <div class="message <?php echo $message['type']; ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo $message['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Export Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-download"></i> Generar Reporte</h3>
                    </div>
                    <div class="card-content">
                        <form id="exportForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fechaInicio">Fecha de Inicio</label>
                                    <input type="date" id="fechaInicio" name="fecha_inicio" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="fechaFin">Fecha de Fin</label>
                                    <input type="date" id="fechaFin" name="fecha_fin" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipoReporte">Tipo de Reporte</label>
                                <select id="tipoReporte" name="tipo_reporte" class="form-control" required>
                                    <option value="">Seleccionar tipo de reporte</option>
                                    <option value="metricas_generales">Métricas Generales</option>
                                    <option value="metricas_asesores">Métricas por Asesor</option>
                                    <option value="metricas_clientes">Métricas de Clientes</option>
                                    <option value="metricas_tickets">Métricas de Tickets</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download"></i> Generar y Descargar
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Limpiar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Export Options -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Exportes Rápidos</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-export-grid">
                            <div class="quick-export-item" onclick="exporteRapido('hoy')">
                                <div class="quick-export-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="quick-export-content">
                                    <h4>Hoy</h4>
                                    <p>Métricas del día actual</p>
                                </div>
                            </div>
                            
                            <div class="quick-export-item" onclick="exporteRapido('semana')">
                                <div class="quick-export-icon">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <div class="quick-export-content">
                                    <h4>Esta Semana</h4>
                                    <p>Métricas de los últimos 7 días</p>
                                </div>
                            </div>
                            
                            <div class="quick-export-item" onclick="exporteRapido('mes')">
                                <div class="quick-export-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="quick-export-content">
                                    <h4>Este Mes</h4>
                                    <p>Métricas del mes actual</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
        });

        // Inicializar formulario
        function initializeForm() {
            // Establecer fechas por defecto
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('fechaInicio').value = firstDay.toISOString().split('T')[0];
            document.getElementById('fechaFin').value = today.toISOString().split('T')[0];
            
            // Manejar envío del formulario
            document.getElementById('exportForm').addEventListener('submit', function(e) {
                e.preventDefault();
                generateExport();
            });
        }

        // Generar exporte
        async function generateExport() {
            const formData = new FormData(document.getElementById('exportForm'));
            
            try {
                showMessage('Generando reporte...', 'info');
                
                const response = await fetch('../api/export_metricas.php?' + new URLSearchParams({
                    fecha_inicio: formData.get('fecha_inicio'),
                    fecha_fin: formData.get('fecha_fin'),
                    tipo_reporte: formData.get('tipo_reporte')
                }), {
                    credentials: 'include'
                });
                
                if (response.ok) {
                    // Descargar archivo
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `reporte_${formData.get('tipo_reporte')}_${formData.get('fecha_inicio')}_${formData.get('fecha_fin')}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    showMessage('Reporte generado y descargado exitosamente', 'success');
                } else {
                    const result = await response.json();
                    showMessage('Error generando reporte: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error generando reporte', 'error');
            }
        }

        // Exporte rápido
        function exporteRapido(tipo) {
            const today = new Date();
            let fechaInicio, fechaFin;
            
            switch (tipo) {
                case 'hoy':
                    fechaInicio = fechaFin = today.toISOString().split('T')[0];
                    break;
                case 'semana':
                    fechaInicio = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    fechaFin = today.toISOString().split('T')[0];
                    break;
                case 'mes':
                    fechaInicio = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    fechaFin = today.toISOString().split('T')[0];
                    break;
            }
            
            // Establecer fechas en el formulario
            document.getElementById('fechaInicio').value = fechaInicio;
            document.getElementById('fechaFin').value = fechaFin;
            document.getElementById('tipoReporte').value = 'metricas_generales';
            
            // Generar exporte
            generateExport();
        }

        // Resetear formulario
        function resetForm() {
            document.getElementById('exportForm').reset();
            initializeForm();
        }

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            let icon = 'info-circle';
            if (type === 'success') icon = 'check-circle';
            else if (type === 'error') icon = 'exclamation-triangle';
            else if (type === 'warning') icon = 'exclamation-triangle';
            
            messageDiv.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            `;
            
            const contentArea = document.querySelector('.content-area');
            contentArea.insertBefore(messageDiv, contentArea.firstChild);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Cerrar sesión
        async function cerrarSesion() {
            if (!confirm('¿Está seguro de cerrar sesión?')) return;
            
            try {
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = '../views/login.php';
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cerrando sesión:', error);
                showMessage('Error cerrando sesión', 'error');
            }
        }

        // Toggle sidebar para dispositivos móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
    </script>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .quick-export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-export-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .quick-export-item:hover {
            border-color: #3b82f6;
            background-color: #f8fafc;
            transform: translateY(-2px);
        }

        .quick-export-icon {
            margin-right: 1rem;
        }

        .quick-export-icon i {
            font-size: 2rem;
            color: #3b82f6;
        }

        .quick-export-content h4 {
            margin: 0;
            color: #374151;
        }

        .quick-export-content p {
            margin: 0.25rem 0 0 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>

    <script>
        // Función para cerrar sesión
        async function cerrarSesion() {
            if (!confirm('¿Está seguro de cerrar sesión?')) return;

            try {
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = '../views/login.php';
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cerrando sesión:', error);
                showMessage('Error cerrando sesión', 'error');
            }
        }

        // Función para toggle del sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        // Cerrar sidebar al hacer clic fuera en móviles
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');

            if (window.innerWidth <= 768 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(event.target) &&
                !menuToggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        });

        // Cerrar sidebar en desktop
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });

        // Función para mostrar mensajes
        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
