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
    <title>Dashboard Asesor - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/role-specific.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/asesor.css" rel="stylesheet">
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
                    <div class="nav-section-title">Asesor</div>
                    <a href="asesor_dashboard.php" class="nav-item active">
                        <i class="fas fa-users"></i>
                        Mis Clientes
                    </a>
                    <a href="asesor_tickets.php" class="nav-item">
                        <i class="fas fa-ticket-alt"></i>
                        Mis Tickets
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
                        <p class="text-asesor">Asesor</p>
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
                        <h1 class="title-asesor">Mis Clientes</h1>
                        <p class="subtitle-asesor">Gestiona y da seguimiento a tus clientes asignados.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar clientes..." id="searchInput">
                    </div>
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

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Clientes</div>
                        </div>
                        <div class="stat-value" id="totalClientes">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Clientes Nuevos</div>
                        </div>
                        <div class="stat-value" id="clientesNuevos">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Clientes Gestionados</div>
                        </div>
                        <div class="stat-value" id="clientesGestionados">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Llamadas Hoy</div>
                        </div>
                        <div class="stat-value" id="llamadasHoy">0</div>
                    </div>
                </div>

                <!-- Clientes Section -->
                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Mis Clientes</h2>
                        <button class="btn btn-primary btn-refresh" onclick="refreshClientes()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div id="clientesGrid" class="clientes-grid">
                        <!-- Los clientes se cargarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let clientes = [];

        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadClientes();
            loadEstadisticas();
        });

        // Cargar clientes
        async function loadClientes() {
            try {
                const response = await fetch('/crm_internacional/api/asesor_clientes.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    clientes = result.data;
                    renderClientes();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando clientes:', error);
                showMessage('Error cargando clientes', 'error');
            }
        }

        // Renderizar clientes
        function renderClientes() {
            const container = document.getElementById('clientesGrid');
            container.innerHTML = '';

            if (clientes.length === 0) {
                container.innerHTML = '<p class="message-no-data">No tienes clientes asignados</p>';
                return;
            }

            clientes.forEach(cliente => {
                const clienteCard = document.createElement('div');
                clienteCard.className = 'cliente-card';
                
                // Determinar el estado y el color
                const estadoGestion = cliente.estado_gestion || 'nuevo';
                const estadoClass = estadoGestion === 'gestionado' ? 'estado-gestionado' : 'estado-nuevo';
                const estadoIcon = estadoGestion === 'gestionado' ? 'fas fa-check-circle' : 'fas fa-clock';
                const estadoText = estadoGestion === 'gestionado' ? 'Gestionado' : 'Nuevo';
                
                clienteCard.innerHTML = `
                    <div class="cliente-header">
                        <div class="cliente-info">
                            <h3>${cliente.nombre} ${cliente.apellido}</h3>
                            <div class="estado-cliente">
                                <span class="${estadoClass}">
                                    <i class="${estadoIcon}"></i> ${estadoText}
                                </span>
                            </div>
                            <p><i class="fas fa-id-card"></i> Cédula: ${cliente.cedula || 'Sin cédula'}</p>
                            <p><i class="fas fa-mobile-alt"></i> Teléfono: ${cliente.telefono || 'Sin teléfono'}</p>
                            <p><i class="fas fa-envelope"></i> ${cliente.email || 'Sin email'}</p>
                            <p><i class="fas fa-building"></i> ${cliente.empresa || 'Sin empresa'}</p>
                        </div>
                        <div class="cliente-actions">
                            <button class="btn-ticket" onclick="irATickets('${cliente.cedula}')">
                                <i class="fas fa-ticket-alt"></i> Ver Tickets
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(clienteCard);
            });
        }

        // Cargar estadísticas
        async function loadEstadisticas() {
            try {
                const response = await fetch('/crm_internacional/api/estadisticas_asesor.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    const stats = result.data;
                    document.getElementById('totalClientes').textContent = stats.total_clientes || 0;
                    document.getElementById('clientesNuevos').textContent = stats.clientes_nuevos || 0;
                    document.getElementById('clientesGestionados').textContent = stats.clientes_gestionados || 0;
                    document.getElementById('llamadasHoy').textContent = stats.llamadas_hoy || 0;
                } else {
                    console.error('Error cargando estadísticas:', result.message);
                    showMessage('Error cargando estadísticas: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                showMessage('Error cargando estadísticas', 'error');
            }
        }

        // Funciones de utilidad
        function refreshClientes() {
            loadClientes();
        }


        function irATickets(clienteCedula = null) {
            if (clienteCedula) {
                // Redirigir con parámetro de filtro por cliente
                window.location.href = `asesor_tickets.php?cliente=${clienteCedula}`;
            } else {
                // Redirigir sin filtro (todos los tickets)
                window.location.href = 'asesor_tickets.php';
            }
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

        // Búsqueda de clientes
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const termino = e.target.value;
            if (termino.length > 2) {
                const clientesFiltrados = clientes.filter(cliente => 
                    cliente.nombre.toLowerCase().includes(termino.toLowerCase()) ||
                    cliente.apellido.toLowerCase().includes(termino.toLowerCase()) ||
                    cliente.cedula.toLowerCase().includes(termino.toLowerCase()) ||
                    cliente.empresa.toLowerCase().includes(termino.toLowerCase())
                );
                renderClientesFiltrados(clientesFiltrados);
            } else {
                renderClientes();
            }
        });

        function renderClientesFiltrados(clientesFiltrados) {
            const container = document.getElementById('clientesGrid');
            container.innerHTML = '';

            if (clientesFiltrados.length === 0) {
                container.innerHTML = '<p class="message-no-data">No se encontraron clientes</p>';
                return;
            }

            clientesFiltrados.forEach(cliente => {
                const clienteCard = document.createElement('div');
                clienteCard.className = 'cliente-card';
                
                const estadoGestion = cliente.estado_gestion || 'nuevo';
                const estadoClass = estadoGestion === 'gestionado' ? 'estado-gestionado' : 'estado-nuevo';
                const estadoIcon = estadoGestion === 'gestionado' ? 'fas fa-check-circle' : 'fas fa-clock';
                const estadoText = estadoGestion === 'gestionado' ? 'Gestionado' : 'Nuevo';
                
                clienteCard.innerHTML = `
                    <div class="cliente-header">
                        <div class="cliente-info">
                            <h3>${cliente.nombre} ${cliente.apellido}</h3>
                            <div class="estado-cliente">
                                <span class="${estadoClass}">
                                    <i class="${estadoIcon}"></i> ${estadoText}
                                </span>
                            </div>
                            <p><i class="fas fa-id-card"></i> Cédula: ${cliente.cedula || 'Sin cédula'}</p>
                            <p><i class="fas fa-mobile-alt"></i> Teléfono: ${cliente.telefono || 'Sin teléfono'}</p>
                            <p><i class="fas fa-envelope"></i> ${cliente.email || 'Sin email'}</p>
                            <p><i class="fas fa-building"></i> ${cliente.empresa || 'Sin empresa'}</p>
                        </div>
                        <div class="cliente-actions">
                            <button class="btn-ticket" onclick="irATickets('${cliente.cedula}')">
                                <i class="fas fa-ticket-alt"></i> Ver Tickets
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(clienteCard);
            });
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
</body>
</html>
