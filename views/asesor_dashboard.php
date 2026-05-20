<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('asesor');

// Obtener datos del usuario actual
$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/partials/app_head.php'; ?>
    <title>Mis casos (reparto) - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/asesor.css" rel="stylesheet">
    <link href="css/tickets.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo logo-asesor">
                    <img src="img/logo2.png" alt="Logo CRM">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Asesor</div>
                    <a href="<?php echo app_nav_url('asesor_dashboard'); ?>" class="nav-item active">
                        <i class="fas fa-folder-open"></i>
                        Mis casos (reparto)
                    </a>
                    <a href="<?php echo app_nav_url('asesor_tickets'); ?>" class="nav-item">
                        <i class="fas fa-ticket-alt"></i>
                        Mis tickets CRM
                    </a>
                    <a href="<?php echo app_nav_url('asesor_estadisticas'); ?>" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        Estadísticas
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
                        <h1 class="title-asesor">Mis casos (reparto)</h1>
                        <p class="subtitle-asesor">Titulares recién en reparto: solo aparecen aquí hasta que registras la <strong>primera gestión</strong> del ticket; después los verás en <strong>Mis tickets</strong>.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar por nombre, caso, teléfono…" id="searchInput">
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
                        <?php echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Casos reparto</div>
                        </div>
                        <div class="stat-value" id="totalClientes">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Con prioridad CSV</div>
                        </div>
                        <div class="stat-value" id="clientesNuevos">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Con email o teléfono</div>
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

                <!-- Casos reparto + tickets CRM (misma sección: los tickets no debían quedar abajo recortados por CSS) -->
                <div class="users-section" id="casosAsignadosSection">
                    <div class="section-header">
                        <h2 class="section-title" id="casosAsignadosHeading">Mis casos asignados</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                            <button type="button" class="btn btn-primary btn-refresh" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i> Actualizar todo
                            </button>
                        </div>
                    </div>
                    <p class="subtitle-asesor" style="margin: 0 0 8px 0; font-size: 0.92rem;">
                        Incluye <strong>titulares del reparto</strong> (CSV) asignados a usted. Haga clic en <em>Gestionar Ticket</em> para comenzar a gestionarlos en el CRM.
                    </p>

                    <h3 class="asesor-subsection-title">Titulares (reparto CSV)</h3>
                    <div id="clientesGrid" class="clientes-grid">
                        <!-- Titulares del reparto -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let titulares = [];

        const ESTADO_TICKET_LABELS = {
            comunicacion: 'Comunicación',
            validacion: 'Validación',
            proceso_judicial: 'Proceso judicial',
            remate: 'Remate',
            recuperacion: 'Recuperación',
            cierre: 'Cierre'
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadTitulares();
            loadEstadisticas();
        });

        async function loadTitulares() {
            try {
                const response = await fetch('api/asesor_titulares.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    titulares = result.data;
                    renderTitulares();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando casos:', error);
                showMessage('Error cargando casos de reparto', 'error');
            }
        }

        function renderTitulares() {
            const container = document.getElementById('clientesGrid');
            container.innerHTML = '';

            if (titulares.length === 0) {
                container.innerHTML = '<p class="message-no-data">No tienes titulares asignados. El coordinador debe asignarte casos desde la gestión de reparto.</p>';
                return;
            }

            titulares.forEach(t => {
                const card = document.createElement('div');
                card.className = 'cliente-card cliente-card-titular';
                const nombre = [t.nombre || '', t.apellido || ''].join(' ').trim() || 'Sin nombre';
                const caso = t.numero_caso ? escapeHtml(t.numero_caso) : '—';
                const condado = t.condado ? escapeHtml(t.condado) : '';
                const ciudad = t.ciudad ? escapeHtml(t.ciudad) : '';
                const loc = [ciudad, t.estado_region ? escapeHtml(t.estado_region) : ''].filter(Boolean).join(', ');
                const prio = t.prioridad ? `<p><i class="fas fa-flag"></i> Prioridad: ${escapeHtml(t.prioridad)}</p>` : '';
                card.innerHTML = `
                    <div class="cliente-header">
                        <div class="cliente-info">
                            <h3>${escapeHtml(nombre)}</h3>
                            <div class="estado-cliente">
                                <span class="estado-gestionado">
                                    <i class="fas fa-user-check"></i> Titular ID ${t.titular_id}
                                </span>
                            </div>
                            <p><i class="fas fa-hashtag"></i> Case number: ${caso}</p>
                            ${prio}
                            <p><i class="fas fa-id-card"></i> ${loc ? 'Ubicación: ' + loc : 'Sin ubicación'}</p>
                            <p><i class="fas fa-mobile-alt"></i> ${t.telefono ? escapeHtml(t.telefono) : 'Sin teléfono'}</p>
                            <p><i class="fas fa-envelope"></i> ${t.email ? escapeHtml(t.email) : 'Sin email'}</p>
                            ${condado ? `<p><i class="fas fa-map"></i> County: ${condado}</p>` : ''}
                        </div>
                        <div class="cliente-actions">
                            <button type="button" class="btn btn-warning" onclick="gestionarTicket(${t.titular_id})">
                                <i class="fas fa-edit"></i> Gestionar Ticket
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const d = document.createElement('div');
            d.textContent = String(text);
            return d.innerHTML;
        }

        function gestionarTicket(titularId) {
            showMessage('Iniciando gestión...', 'info');
            fetch('api/crear_ticket_desde_titular.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ titular_id: titularId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    window.appGo('asesor_gestionar_ticket', {id: result.ticket_id});
                } else {
                    showMessage('Error: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error gestionando ticket:', error);
                showMessage('Error al iniciar la gestión del ticket', 'error');
            });
        }

        async function loadEstadisticas() {
            try {
                const response = await fetch('api/estadisticas_asesor.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    const stats = result.data;
                    document.getElementById('totalClientes').textContent = stats.total_casos_reparto ?? stats.total_clientes ?? 0;
                    document.getElementById('clientesNuevos').textContent = stats.casos_con_prioridad ?? stats.clientes_nuevos ?? 0;
                    document.getElementById('clientesGestionados').textContent = stats.casos_con_contacto ?? stats.clientes_gestionados ?? 0;
                    document.getElementById('llamadasHoy').textContent = stats.llamadas_hoy ?? 0;
                } else {
                    console.error('Error cargando estadísticas:', result.message);
                    showMessage('Error cargando estadísticas: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                showMessage('Error cargando estadísticas', 'error');
            }
        }

        function refreshDashboard() {
            loadTitulares();
            loadEstadisticas();
        }

        function irAMisTicketsCrm() {
            window.appGo('asesor_tickets');
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

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const termino = e.target.value.trim().toLowerCase();
            if (termino.length > 2) {
                const filtrados = titulares.filter(t => {
                    const nombre = `${t.nombre || ''} ${t.apellido || ''}`.toLowerCase();
                    const id = String(t.titular_id || '');
                    const caso = (t.numero_caso || '').toLowerCase();
                    const mail = (t.email || '').toLowerCase();
                    const tel = (t.telefono || '').toLowerCase();
                    const cond = (t.condado || '').toLowerCase();
                    return nombre.includes(termino) || id.includes(termino) || caso.includes(termino)
                        || mail.includes(termino) || tel.includes(termino) || cond.includes(termino);
                });
                renderTitularesFiltrados(filtrados);
            } else {
                renderTitulares();
            }
        });

        function renderTitularesFiltrados(lista) {
            const container = document.getElementById('clientesGrid');
            container.innerHTML = '';

            if (lista.length === 0) {
                container.innerHTML = '<p class="message-no-data">No se encontraron casos</p>';
                return;
            }

            lista.forEach(t => {
                const card = document.createElement('div');
                card.className = 'cliente-card cliente-card-titular';
                const nombre = [t.nombre || '', t.apellido || ''].join(' ').trim() || 'Sin nombre';
                const caso = t.numero_caso ? escapeHtml(t.numero_caso) : '—';
                const condado = t.condado ? escapeHtml(t.condado) : '';
                const ciudad = t.ciudad ? escapeHtml(t.ciudad) : '';
                const loc = [ciudad, t.estado_region ? escapeHtml(t.estado_region) : ''].filter(Boolean).join(', ');
                const prio = t.prioridad ? `<p><i class="fas fa-flag"></i> Prioridad: ${escapeHtml(t.prioridad)}</p>` : '';
                card.innerHTML = `
                    <div class="cliente-header">
                        <div class="cliente-info">
                            <h3>${escapeHtml(nombre)}</h3>
                            <div class="estado-cliente">
                                <span class="estado-gestionado">
                                    <i class="fas fa-user-check"></i> Titular ID ${t.titular_id}
                                </span>
                            </div>
                            <p><i class="fas fa-hashtag"></i> Case number: ${caso}</p>
                            ${prio}
                            <p><i class="fas fa-id-card"></i> ${loc ? 'Ubicación: ' + loc : 'Sin ubicación'}</p>
                            <p><i class="fas fa-mobile-alt"></i> ${t.telefono ? escapeHtml(t.telefono) : 'Sin teléfono'}</p>
                            <p><i class="fas fa-envelope"></i> ${t.email ? escapeHtml(t.email) : 'Sin email'}</p>
                            ${condado ? `<p><i class="fas fa-map"></i> County: ${condado}</p>` : ''}
                        </div>
                        <div class="cliente-actions">
                            <button type="button" class="btn btn-warning" onclick="gestionarTicket(${t.titular_id})">
                                <i class="fas fa-edit"></i> Gestionar Ticket
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Cerrar sesión
        async function cerrarSesion() {
            if (!confirm('¿Está seguro de cerrar sesión?')) return;
            
            try {
                const response = await fetch('api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.appGoLogin();
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
