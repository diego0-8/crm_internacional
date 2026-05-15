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
    <title>Estadísticas - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/asesor.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo" style="color: #EBF58E;">
                    <i class="fas fa-chart-line"></i>
                    CRM
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Asesor</div>
                    <a href="asesor_dashboard.php" class="nav-item">
                        <i class="fas fa-folder-open"></i>
                        Mis casos (reparto)
                    </a>
                    <a href="asesor_tickets.php" class="nav-item">
                        <i class="fas fa-ticket-alt"></i>
                        Mis tickets CRM
                    </a>
                    <a href="asesor_estadisticas.php" class="nav-item active">
                        <i class="fas fa-chart-bar"></i>
                        Estadísticas
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="profile-card">
                    <div class="profile-avatar" style="background: linear-gradient(135deg, #81864E, #AEB669);">
                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h4 style="color: #EBF58E;"><?php echo $user['nombre'] . ' ' . $user['apellido']; ?></h4>
                        <p style="color: #E2EC89;">Asesor</p>
                    </div>
                </div>

                <button class="logout-btn" onclick="cerrarSesion()" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <div class="top-header" style="background: linear-gradient(180deg, #60643A 0%, #81864E 100%);">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()" style="color: #EBF58E;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1 style="background: linear-gradient(135deg, #81864E, #AEB669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Mis Estadísticas</h1>
                        <p style="color: #E2EC89;">Casos de reparto (titulares), clientes CRM, tickets y llamadas.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="refreshEstadisticas()" style="background: linear-gradient(135deg, #60643A, #81864E); color: #EBF58E;">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
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

                <!-- Performance Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Casos reparto</div>
                            <div class="stat-icon" style="background: rgba(96, 100, 58, 0.2); color: #60643A;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalClientes">0</div>
                        <div class="stat-change" id="clientesChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Con email o teléfono</div>
                            <div class="stat-icon" style="background: rgba(129, 134, 78, 0.2); color: #81864E;">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="clientesGestionados">0</div>
                        <div class="stat-change" id="gestionadosChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Tickets Resueltos</div>
                            <div class="stat-icon" style="background: rgba(174, 182, 105, 0.2); color: #AEB669;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="ticketsResueltos">0</div>
                        <div class="stat-change" id="ticketsChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Tiempo Promedio</div>
                            <div class="stat-icon" style="background: rgba(194, 202, 117, 0.2); color: #C2CA75;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="tiempoPromedio">0h</div>
                        <div class="stat-change" id="tiempoChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics -->
                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Métricas Detalladas</h2>
                    </div>
                    <div class="performance-metrics">
                        <div class="performance-card">
                            <div class="performance-icon" style="background: linear-gradient(135deg, #60643A, #81864E); color: #EBF58E;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="performance-content">
                                <div class="performance-title">Efectividad General</div>
                                <div class="performance-value" id="efectividadGeneral">0%</div>
                                <div class="performance-subtitle">Tickets resueltos vs creados</div>
                            </div>
                        </div>

                        <div class="performance-card">
                            <div class="performance-icon" style="background: linear-gradient(135deg, #81864E, #AEB669); color: #EBF58E;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="performance-content">
                                <div class="performance-title">Llamadas del Mes</div>
                                <div class="performance-value" id="llamadasMes">0</div>
                                <div class="performance-subtitle">Total de llamadas realizadas</div>
                            </div>
                        </div>

                        <div class="performance-card">
                            <div class="performance-icon" style="background: linear-gradient(135deg, #AEB669, #C2CA75); color: #60643A;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="performance-content">
                                <div class="performance-title">Emails Enviados</div>
                                <div class="performance-value" id="emailsEnviados">0</div>
                                <div class="performance-subtitle">Comunicaciones por email</div>
                            </div>
                        </div>

                        <div class="performance-card">
                            <div class="performance-icon" style="background: linear-gradient(135deg, #C2CA75, #EBF58E); color: #60643A;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="performance-content">
                                <div class="performance-title">Reuniones</div>
                                <div class="performance-value" id="reunionesMes">0</div>
                                <div class="performance-subtitle">Citas agendadas este mes</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Tendencias Mensuales</h2>
                    </div>
                    <div class="tickets-summary">
                        <div class="ticket-metric">
                            <div class="metric-label">CRM distintos (llamadas mes)</div>
                            <div class="metric-value" id="clientesAtendidosMes">0</div>
                        </div>
                        <div class="ticket-metric">
                            <div class="metric-label">Conversión</div>
                            <div class="metric-value" id="tasaConversion">0%</div>
                        </div>
                        <div class="ticket-metric">
                            <div class="metric-label">Satisfacción</div>
                            <div class="metric-value" id="satisfaccionCliente">0%</div>
                        </div>
                        <div class="ticket-metric">
                            <div class="metric-label">Meta Mensual</div>
                            <div class="metric-value" id="metaMensual">0/10</div>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Actividad Reciente</h2>
                    </div>
                    <div id="actividadReciente" class="actividad-timeline">
                        <!-- La actividad se cargará aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let estadisticasData = {};

        // Inicializar estadísticas
        document.addEventListener('DOMContentLoaded', function() {
            loadEstadisticasDetalladas();
        });

        // Cargar estadísticas detalladas
        async function loadEstadisticasDetalladas() {
            try {
                const response = await fetch('../api/estadisticas_asesor.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    estadisticasData = result.data;
                    updateEstadisticasUI();
                    loadActividadReciente();
                } else {
                    showMessage('Error cargando estadísticas: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                showMessage('Error cargando estadísticas detalladas', 'error');
            }
        }

        // Actualizar interfaz de estadísticas
        function updateEstadisticasUI() {
            const stats = estadisticasData;

            // Estadísticas principales
            document.getElementById('totalClientes').textContent = stats.total_casos_reparto ?? stats.total_clientes ?? 0;
            document.getElementById('clientesGestionados').textContent = stats.casos_con_contacto ?? stats.clientes_gestionados ?? 0;
            document.getElementById('ticketsResueltos').textContent = stats.tickets_resueltos_mes || 0;
            document.getElementById('tiempoPromedio').textContent = (stats.tiempo_promedio_resolucion || 0) + 'h';

            // Métricas detalladas
            document.getElementById('efectividadGeneral').textContent = (stats.efectividad_general || 0) + '%';
            document.getElementById('llamadasMes').textContent = stats.llamadas_mes || 0;
            document.getElementById('emailsEnviados').textContent = stats.emails_enviados_mes || 0;
            document.getElementById('reunionesMes').textContent = stats.reuniones_mes || 0;

            // Tendencias mensuales
            document.getElementById('clientesAtendidosMes').textContent = stats.clientes_atendidos_mes || 0;
            document.getElementById('tasaConversion').textContent = (stats.tasa_conversion || 0) + '%';
            document.getElementById('satisfaccionCliente').textContent = (stats.satisfaccion_cliente || 0) + '%';
            document.getElementById('metaMensual').textContent = `${stats.meta_cumplida_mes || 0}/${stats.meta_total_mes || 10}`;

            // Actualizar indicadores de cambio
            updateEstadisticasChanges();
        }

        // Actualizar indicadores de cambio
        function updateEstadisticasChanges() {
            const stats = estadisticasData;

            // Cambio en clientes
            const clientesChange = document.getElementById('clientesChange');
            const clientesNuevos = stats.titulares_actualizados_mes ?? stats.clientes_nuevos_mes ?? 0;
            clientesChange.innerHTML = `
                <i class="fas fa-${clientesNuevos > 0 ? 'arrow-up' : 'minus'}"></i>
                <span>${clientesNuevos} titulares tocados este mes</span>
            `;

            // Cambio en gestionados
            const gestionadosChange = document.getElementById('gestionadosChange');
            const tasaGestion = stats.tasa_gestion || 0;
            gestionadosChange.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>${tasaGestion}% con datos de contacto</span>
            `;

            // Cambio en tickets
            const ticketsChange = document.getElementById('ticketsChange');
            const ticketsMes = stats.tickets_resueltos_mes || 0;
            ticketsChange.innerHTML = `
                <i class="fas fa-${ticketsMes > 5 ? 'arrow-up' : 'minus'}"></i>
                <span>${ticketsMes} resueltos este mes</span>
            `;

            // Cambio en tiempo
            const tiempoChange = document.getElementById('tiempoChange');
            const tiempoPromedio = stats.tiempo_promedio_resolucion || 0;
            const colorClass = tiempoPromedio < 24 ? 'positive' : tiempoPromedio < 48 ? 'warning' : 'negative';
            tiempoChange.className = `stat-change ${colorClass}`;
            tiempoChange.innerHTML = `
                <i class="fas fa-${tiempoPromedio < 24 ? 'check-circle' : 'clock'}"></i>
                <span>${tiempoPromedio < 24 ? 'Excelente' : tiempoPromedio < 48 ? 'Bueno' : 'Mejorar'}</span>
            `;
        }

        // Cargar actividad reciente
        async function loadActividadReciente() {
            try {
                const response = await fetch('../api/actividad_asesor.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
                        return;
                    }
                    // Si la API no existe aún, mostrar mensaje alternativo
                    if (response.status === 404) {
                        showActividadAlternativa();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    renderActividadReciente(result.data);
                } else {
                    showActividadAlternativa();
                }
            } catch (error) {
                console.error('Error cargando actividad:', error);
                showActividadAlternativa();
            }
        }

        // Mostrar actividad alternativa cuando no hay API
        function showActividadAlternativa() {
            const container = document.getElementById('actividadReciente');
            container.innerHTML = `
                <div class="actividad-item">
                    <div class="actividad-icon" style="background: linear-gradient(135deg, #81864E, #AEB669);">
                        <i class="fas fa-chart-line" style="color: #EBF58E;"></i>
                    </div>
                    <div class="actividad-content">
                        <div class="actividad-title">Estadísticas Actualizadas</div>
                        <div class="actividad-description">Tus métricas de rendimiento se han cargado correctamente</div>
                        <div class="actividad-time">Ahora</div>
                    </div>
                </div>
                <div class="actividad-item">
                    <div class="actividad-icon" style="background: linear-gradient(135deg, #AEB669, #C2CA75);">
                        <i class="fas fa-users" style="color: #60643A;"></i>
                    </div>
                    <div class="actividad-content">
                        <div class="actividad-title">Casos de reparto</div>
                        <div class="actividad-description">Tienes ${estadisticasData.total_casos_reparto ?? estadisticasData.total_clientes ?? 0} titulares asignados en el CSV</div>
                        <div class="actividad-time">Esta semana</div>
                    </div>
                </div>
                <div class="actividad-item">
                    <div class="actividad-icon" style="background: linear-gradient(135deg, #C2CA75, #EBF58E);">
                        <i class="fas fa-ticket-alt" style="color: #60643A;"></i>
                    </div>
                    <div class="actividad-content">
                        <div class="actividad-title">Tickets Resueltos</div>
                        <div class="actividad-description">${estadisticasData.tickets_resueltos_mes || 0} tickets cerrados exitosamente</div>
                        <div class="actividad-time">Este mes</div>
                    </div>
                </div>
            `;
        }

        // Renderizar actividad reciente
        function renderActividadReciente(actividades) {
            const container = document.getElementById('actividadReciente');

            if (!actividades || actividades.length === 0) {
                showActividadAlternativa();
                return;
            }

            container.innerHTML = '';

            actividades.forEach(actividad => {
                const actividadItem = document.createElement('div');
                actividadItem.className = 'actividad-item';
                actividadItem.innerHTML = `
                    <div class="actividad-icon" style="background: linear-gradient(135deg, #81864E, #AEB669);">
                        <i class="fas fa-${actividad.icono || 'circle'}" style="color: #EBF58E;"></i>
                    </div>
                    <div class="actividad-content">
                        <div class="actividad-title">${actividad.titulo}</div>
                        <div class="actividad-description">${actividad.descripcion}</div>
                        <div class="actividad-time">${actividad.tiempo}</div>
                    </div>
                `;
                container.appendChild(actividadItem);
            });
        }

        // Funciones de utilidad
        function refreshEstadisticas() {
            showMessage('Actualizando estadísticas...', 'info');
            loadEstadisticasDetalladas();
        }

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')}"></i>
                ${message}
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

        // Toggle sidebar para dispositivos móviles
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

        // Cerrar sidebar al redimensionar ventana
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });
    </script>

    <style>
        .actividad-timeline {
            max-height: 400px;
            overflow-y: auto;
        }

        .actividad-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.3s ease;
        }

        .actividad-item:hover {
            background-color: rgba(129, 134, 78, 0.05);
        }

        .actividad-item:last-child {
            border-bottom: none;
        }

        .actividad-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .actividad-content {
            flex: 1;
        }

        .actividad-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .actividad-description {
            color: #4a5568;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .actividad-time {
            color: #718096;
            font-size: 0.8rem;
        }
    </style>
</body>
</html>