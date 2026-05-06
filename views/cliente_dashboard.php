<?php
require_once __DIR__ . '/../config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar que sea cliente
if (!hasRole('cliente')) {
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
    <title>Dashboard Cliente - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/role-specific.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo logo-cliente">
                    <i class="fas fa-user"></i>
                    CRM
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Cliente</div>
                    <a href="cliente_dashboard.php" class="nav-item active">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                    <a href="cliente_mis_tickets.php" class="nav-item">
                        <i class="fas fa-ticket-alt"></i>
                        Mis Tickets
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="profile-card">
                    <div class="profile-avatar avatar-cliente">
                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h4 class="text-asesor"><?php echo $user['nombre'] . ' ' . $user['apellido']; ?></h4>
                        <p class="text-asesor">Cliente</p>
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
                        <h1 class="title-asesor">Mi Panel de Cliente</h1>
                        <p class="subtitle-asesor">Gestiona tus solicitudes y tickets de soporte.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="header-icon">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationCount">0</span>
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

                <!-- Welcome Card -->
                <div class="welcome-card-cliente">
                    <h2>¡Bienvenido, <?php echo $user['nombre']; ?>!</h2>
                    <p>Desde aquí puedes gestionar tus tickets de soporte y hacer seguimiento a tus solicitudes.</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Tickets Abiertos</div>
                            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="ticketsAbiertos">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Tickets en Proceso</div>
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="ticketsProceso">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Tickets Resueltos</div>
                            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="ticketsResueltos">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Tickets</div>
                            <div class="stat-icon" style="background: rgba(96, 100, 58, 0.2); color: #60643A;">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalTickets">0</div>
                    </div>
                </div>

                <!-- Recent Tickets -->
                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Mis Tickets Recientes</h2>
                        <button class="btn btn-primary" onclick="refreshTickets()" style="background: linear-gradient(135deg, #60643A, #81864E); color: #EBF58E;">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div id="ticketsGrid" class="tickets-grid">
                        <!-- Los tickets se cargarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadTickets();
            loadEstadisticas();
        });

        // Cargar tickets del cliente
        async function loadTickets() {
            try {
                const response = await fetch('/crm_internacional/api/cliente_tickets.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = 'login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    renderTickets(result.data);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando tickets:', error);
                showMessage('Error cargando tickets', 'error');
            }
        }

        // Renderizar tickets
        function renderTickets(tickets) {
            const container = document.getElementById('ticketsGrid');
            container.innerHTML = '';

            if (!tickets || tickets.length === 0) {
                container.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 40px; grid-column: 1 / -1;">No tienes tickets registrados</p>';
                return;
            }

            tickets.forEach(ticket => {
                const ticketCard = document.createElement('div');
                ticketCard.className = 'ticket-card';

                const estadoClass = getEstadoClass(ticket.estado);
                const estadoText = getEstadoText(ticket.estado);

                ticketCard.innerHTML = `
                    <div class="ticket-header">
                        <div class="ticket-info">
                            <h3>${ticket.titulo}</h3>
                            <div class="ticket-status">
                                <span class="status-${ticket.estado}" style="background: ${estadoClass};">
                                    <i class="fas fa-circle"></i> ${estadoText}
                                </span>
                            </div>
                            <p><i class="fas fa-calendar"></i> Creado: ${new Date(ticket.fecha_creacion).toLocaleDateString()}</p>
                            <p><i class="fas fa-clock"></i> Última actualización: ${new Date(ticket.fecha_actualizacion).toLocaleDateString()}</p>
                        </div>
                        <div class="ticket-actions">
                            <button class="btn-view" onclick="verTicket(${ticket.id})" style="background: linear-gradient(135deg, #60643A, #81864E); color: #EBF58E;">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                        </div>
                    </div>
                    <div class="ticket-description">
                        <p>${ticket.descripcion || 'Sin descripción'}</p>
                    </div>
                `;
                container.appendChild(ticketCard);
            });
        }

        // Cargar estadísticas
        async function loadEstadisticas() {
            try {
                const response = await fetch('/crm_internacional/api/cliente_estadisticas.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = 'login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    const stats = result.data;
                    document.getElementById('ticketsAbiertos').textContent = stats.abiertos || 0;
                    document.getElementById('ticketsProceso').textContent = stats.proceso || 0;
                    document.getElementById('ticketsResueltos').textContent = stats.resueltos || 0;
                    document.getElementById('totalTickets').textContent = stats.total || 0;
                } else {
                    console.error('Error cargando estadísticas:', result.message);
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        }

        function getEstadoClass(estado) {
            switch(estado) {
                case 'comunicacion':     return 'linear-gradient(135deg, #3b82f6, #2563eb)';
                case 'validacion':       return 'linear-gradient(135deg, #f59e0b, #d97706)';
                case 'proceso_judicial': return 'linear-gradient(135deg, #a855f7, #7e22ce)';
                case 'remate':           return 'linear-gradient(135deg, #ef4444, #dc2626)';
                case 'recuperacion':     return 'linear-gradient(135deg, #10b981, #059669)';
                case 'cierre':           return 'linear-gradient(135deg, #6b7280, #4b5563)';
                default:                 return 'linear-gradient(135deg, #6b7280, #4b5563)';
            }
        }

        function getEstadoText(estado) {
            switch(estado) {
                case 'comunicacion':     return 'Comunicación';
                case 'validacion':       return 'Validación';
                case 'proceso_judicial': return 'Proceso judicial';
                case 'remate':           return 'Remate';
                case 'recuperacion':     return 'Recuperación';
                case 'cierre':           return 'Cierre';
                default: return estado;
            }
        }

        function refreshTickets() {
            loadTickets();
            loadEstadisticas();
        }

        function verTicket(ticketId) {
            // Implementar vista de detalle del ticket
            alert('Funcionalidad de ver ticket en desarrollo. Ticket ID: ' + ticketId);
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
                const response = await fetch('/crm_internacional/api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = 'login.php';
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
</body>
</html>