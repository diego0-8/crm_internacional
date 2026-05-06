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
    <title>Dashboard Coordinador - <?php echo APP_NAME; ?></title>
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
                    <a href="coordinador_dashboard.php" class="nav-item active">
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
                    <a href="coordinador_tickets_import.php" class="nav-item">
                        <i class="fas fa-file-upload"></i>
                        Importar tickets CSV
                    </a>
                    <a href="coordinador_exporte.php" class="nav-item">
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
                        <h1 class="title-asesor">Dashboard Coordinador</h1>
                        <p class="subtitle-asesor">Gestiona tus asesores y clientes de manera eficiente.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar clientes..." id="searchInput">
                    </div>
                    <div class="header-icon">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">2</span>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area coordinador-dashboard">
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
                        <div class="stat-change" id="clientesChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Clientes Asignados</div>
                        </div>
                        <div class="stat-value" id="clientesAsignados">0</div>
                        <div class="stat-change" id="asignadosChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Tickets Activos</div>
                        </div>
                        <div class="stat-value" id="ticketsActivos">0</div>
                        <div class="stat-change" id="ticketsChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Efectividad General</div>
                        </div>
                        <div class="stat-value" id="efectividadGeneral">0%</div>
                        <div class="stat-change" id="efectividadChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>
                </div>

                <!-- Asesores Section -->
                <div class="users-section">
                            <div class="section-header">
                                <h2 class="section-title">Métricas de Asesores</h2>
                                <button class="btn btn-primary" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i> Actualizar
                                </button>
                            </div>
                            <div id="asesoresList">
                                <!-- Los asesores se cargarán aquí -->
                            </div>
                </div>

                <!-- Métricas de Tickets -->
                <div class="users-section">
                            <div class="section-header">
                                <h2 class="section-title">Resumen de Tickets</h2>
                            </div>
                            <div class="tickets-summary">
                                <div class="ticket-metric">
                                    <div class="metric-label">Total Tickets</div>
                                    <div class="metric-value" id="totalTickets">0</div>
                                </div>
                                <div class="ticket-metric">
                                    <div class="metric-label">Abiertos</div>
                                    <div class="metric-value tickets-abiertos" id="ticketsAbiertos">0</div>
                                </div>
                                <div class="ticket-metric">
                                    <div class="metric-label">En Progreso</div>
                                    <div class="metric-value tickets-procesando" id="ticketsProcesando">0</div>
                                </div>
                                <div class="ticket-metric">
                                    <div class="metric-label">Terminados</div>
                                    <div class="metric-value tickets-terminados" id="ticketsTerminados">0</div>
                                </div>
                                <div class="ticket-metric">
                                    <div class="metric-label">Urgentes</div>
                                    <div class="metric-value tickets-urgentes" id="ticketsUrgentes">0</div>
                                </div>
                            </div>
                </div>

                <!-- Gestión de Asesores -->
                <div class="users-section">
                            <div class="section-header">
                                <h2 class="section-title">Gestión de Asesores</h2>
                                <div class="header-actions">
                                    <button class="btn btn-success" onclick="openCreateUserModal()">
                                        <i class="fas fa-user-plus"></i> Crear Usuario Manualmente
                                    </button>
                                    <button class="btn btn-primary" onclick="refreshAsesores()">
                                        <i class="fas fa-sync-alt"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                            <div class="table-container">
                                <table class="users-table" id="asesoresTable">
                                    <thead>
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Usuario</th>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Teléfono</th>
                                            <th>Estado</th>
                                            <th>Clientes</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="asesoresTableBody">
                                        <!-- Los asesores se cargarán aquí -->
                                    </tbody>
                                </table>
                            </div>
                </div>

                <!-- Rendimiento General -->
                <div class="users-section">
                            <div class="section-header">
                                <h2 class="section-title">Rendimiento del Equipo</h2>
                            </div>
                            <div class="performance-metrics">
                                <div class="performance-card">
                                    <div class="performance-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="performance-content">
                                        <div class="performance-title">Clientes Activos</div>
                                        <div class="performance-value" id="clientesActivos">0</div>
                                        <div class="performance-subtitle">Últimos 30 días</div>
                                    </div>
                                </div>
                                <div class="performance-card">
                                    <div class="performance-icon">
                                        <i class="fas fa-ticket-alt"></i>
                                    </div>
                                    <div class="performance-content">
                                        <div class="performance-title">Tickets Resueltos</div>
                                        <div class="performance-value" id="ticketsResueltos">0</div>
                                        <div class="performance-subtitle">Últimos 30 días</div>
                                    </div>
                                </div>
                                <div class="performance-card">
                                    <div class="performance-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="performance-content">
                                        <div class="performance-title">Tiempo Promedio</div>
                                        <div class="performance-value" id="tiempoPromedio">0h</div>
                                        <div class="performance-subtitle">Resolución de tickets</div>
                                    </div>
                                </div>
                                <div class="performance-card">
                                    <div class="performance-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="performance-content">
                                        <div class="performance-title">Efectividad</div>
                                        <div class="performance-value" id="efectividadEquipo">0%</div>
                                        <div class="performance-subtitle">Tickets resueltos/creados</div>
                                    </div>
                                </div>
                            </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear usuario manualmente -->
    <div id="createUserModal" class="modal-cliente">
        <div class="modal-cliente-content" style="max-width: 720px;">
            <div class="modal-cliente-header">
                <h3 class="modal-cliente-title">Crear Usuario Manualmente</h3>
                <span class="close-modal" onclick="closeCreateUserModal()" role="button" tabindex="0">&times;</span>
            </div>
            <div class="ticket-form">
            <form id="createUserForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="userCedula">Cédula *</label>
                        <input type="text" id="userCedula" name="cedula" class="form-control" required
                               placeholder="1234567890" pattern="[0-9]{7,20}">
                    </div>
                    <div class="form-group">
                        <label for="userUsuario">Usuario *</label>
                        <input type="text" id="userUsuario" name="usuario" class="form-control" required
                               placeholder="nombre_usuario" pattern="[a-zA-Z0-9_]{3,20}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userNombre">Nombre *</label>
                        <input type="text" id="userNombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="userApellido">Apellido *</label>
                        <input type="text" id="userApellido" name="apellido" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userEmail">Email *</label>
                        <input type="email" id="userEmail" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="userTelefono">Teléfono</label>
                        <input type="text" id="userTelefono" name="telefono" class="form-control"
                               placeholder="+57 300 123 4567">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userPassword">Contraseña *</label>
                        <input type="password" id="userPassword" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="userConfirmPassword">Confirmar Contraseña *</label>
                        <input type="password" id="userConfirmPassword" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userRol">Rol *</label>
                        <select id="userRol" name="rol_id" class="form-control" required>
                            <option value="">Seleccionar rol</option>
                        </select>
                    </div>
                    <div class="form-group" id="asesorGroup" style="display: none;">
                        <label for="userAsesor">Asesor Asignado</label>
                        <select id="userAsesor" name="asesor_cedula" class="form-control">
                            <option value="">Seleccionar asesor</option>
                        </select>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="createUserSubmitBtn">Crear Usuario</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
        let dashboardData = {};
        let sessionCheckInterval;

        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadAsesores();
            loadRolesForUserCreation();
            initializeSessionManagement();
        });

        // Funciones para crear usuario manualmente
        function openCreateUserModal() {
            document.getElementById('createUserForm').reset();
            loadAsesoresForAssignment();
            document.getElementById('createUserModal').style.display = 'block';
        }

        function closeCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'none';
            document.getElementById('createUserForm').reset();
        }

        // Cargar roles para creación de usuario
        async function loadRolesForUserCreation() {
            try {
                const response = await fetch('../api/roles.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    console.error('Error cargando roles');
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    const rolSelect = document.getElementById('userRol');
                    rolSelect.innerHTML = '<option value="">Seleccionar rol</option>';

                    result.forEach(role => {
                        // Solo permitir crear asesores (no admins)
                        if (role.nombre === 'asesor') {
                            rolSelect.innerHTML += `<option value="${role.id}">${role.nombre}</option>`;
                        }
                    });
                }
            } catch (error) {
                console.error('Error cargando roles:', error);
            }
        }

        // Cargar asesores para asignación
        async function loadAsesoresForAssignment() {
            try {
                const response = await fetch('../api/coordinador_asesores.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    console.error('Error cargando asesores');
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    const asesorSelect = document.getElementById('userAsesor');
                    asesorSelect.innerHTML = '<option value="">Sin asignar</option>';

                    result.data.forEach(asesor => {
                        asesorSelect.innerHTML += `<option value="${asesor.cedula}">${asesor.nombre} ${asesor.apellido}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error cargando asesores:', error);
            }
        }

        // Manejar cambio de rol
        document.getElementById('userRol').addEventListener('change', function() {
            const asesorGroup = document.getElementById('asesorGroup');
            if (this.value) {
                asesorGroup.style.display = 'block';
            } else {
                asesorGroup.style.display = 'none';
            }
        });

        // Validar contraseñas
        function validateUserPasswords() {
            const password = document.getElementById('userPassword').value;
            const confirmPassword = document.getElementById('userConfirmPassword').value;

            if (password !== confirmPassword) {
                showMessage('Las contraseñas no coinciden', 'error');
                return false;
            }

            if (password.length < 6) {
                showMessage('La contraseña debe tener al menos 6 caracteres', 'error');
                return false;
            }

            return true;
        }

        // Manejar envío del formulario de creación de usuario
        document.getElementById('createUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validar contraseñas
            if (!validateUserPasswords()) {
                return;
            }

            const submitBtn = document.getElementById('createUserSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(this);

                const response = await fetch('../api/create_user_manual.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    closeCreateUserModal();
                    showMessage(result.message, 'success');
                    loadAsesores(); // Recargar la lista de asesores
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error creando usuario:', error);
                showMessage('Error creando usuario', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Sistema de gestión de sesiones
        function initializeSessionManagement() {
            // Verificar sesión cada 5 minutos
            sessionCheckInterval = setInterval(checkSessionStatus, 5 * 60 * 1000);

            // Sincronización entre pestañas
            window.addEventListener('storage', function(e) {
                if (e.key === 'crm_force_logout' && e.newValue === 'true') {
                    // Otra pestaña cerró sesión, cerrar esta también
                    clearInterval(sessionCheckInterval);
                    window.location.href = '../views/login.php';
                }
            });

            // Verificar sesión al hacer foco en la ventana
            window.addEventListener('focus', checkSessionStatus);
        }

        // Verificar estado de la sesión
        async function checkSessionStatus() {
            try {
                const response = await fetch('../api/session_heartbeat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (response.status === 401) {
                    // Sesión expirada
                    clearInterval(sessionCheckInterval);
                    showMessage('Tu sesión ha expirado. Redirigiendo al login...', 'warning');
                    setTimeout(() => {
                        window.location.href = '../views/login.php';
                    }, 3000);
                    return;
                }

                if (!response.ok) {
                    throw new Error('Error de conexión');
                }

                const result = await response.json();
                if (!result.success) {
                    // Sesión inválida
                    clearInterval(sessionCheckInterval);
                    showMessage('Sesión inválida. Redirigiendo al login...', 'error');
                    setTimeout(() => {
                        window.location.href = '../views/login.php';
                    }, 3000);
                }
            } catch (error) {
                console.warn('Error verificando sesión:', error);
                // No mostrar error al usuario para evitar spam
            }
        }

        // Cargar datos del dashboard
        async function loadDashboardData() {
            try {
                const response = await fetch('../api/coordinador_dashboard.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    dashboardData = result.data;
                    // #region agent log
                    (function(){
                        const a = (dashboardData.asesores && dashboardData.asesores[0]) ? dashboardData.asesores[0] : null;
                        fetch('http://127.0.0.1:7895/ingest/5fcbb579-d53a-47d5-84e9-86a55388551f',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'7f7f84'},body:JSON.stringify({sessionId:'7f7f84',runId:'pre-fix',hypothesisId:'H1-H3',location:'coordinador_dashboard.php:loadDashboardData',message:'sample asesor from API',data:{hasAsesores:!!(dashboardData.asesores&&dashboardData.asesores.length),keys:a?Object.keys(a):[],estado:a&&a.estado,activo:a&&a.activo},timestamp:Date.now()})}).catch(function(){});
                    })();
                    // #endregion
                    updateStats();
                    renderAsesores();

                    // Solo cargar asesores para asignación si estamos en la sección de tareas
                    asesores = dashboardData.asesores || [];
                    const tareasTab = document.getElementById('tareas-tab');
                    if (tareasTab && tareasTab.classList.contains('active')) {
                        renderAsesoresParaAsignacion();
                        updateClientesDisponibles();
                    }
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando datos:', error);
                showMessage('Error cargando datos del dashboard', 'error');
            }
        }

        // Actualizar estadísticas
        function updateStats() {
            // Estadísticas principales
            document.getElementById('totalClientes').textContent = dashboardData.clientes?.total || 0;
            document.getElementById('clientesAsignados').textContent = dashboardData.clientes?.asignados || 0;
            
            const ticketsActivos = dashboardData.tickets?.tickets_abiertos || 0;
            document.getElementById('ticketsActivos').textContent = ticketsActivos;
            
            // Efectividad general
            const efectividad = dashboardData.rendimiento?.efectividad_general || 0;
            document.getElementById('efectividadGeneral').textContent = efectividad + '%';
            
            // Actualizar cambios porcentuales
            updateStatChanges();
            
            // Métricas de tickets
            updateTicketsMetrics();
            
            // Métricas de rendimiento
            updatePerformanceMetrics();
        }
        
        function updateTicketsMetrics() {
            const tickets = dashboardData.tickets || {};
            const procesando = (tickets.tickets_validacion || 0) + (tickets.tickets_proceso_judicial || 0)
                             + (tickets.tickets_remate || 0)    + (tickets.tickets_recuperacion || 0);
            const urgentes   = (tickets.tickets_remate || 0)    + (tickets.tickets_proceso_judicial || 0);

            const elTotal = document.getElementById('totalTickets');
            const elAb    = document.getElementById('ticketsAbiertos');
            const elProc  = document.getElementById('ticketsProcesando');
            const elTerm  = document.getElementById('ticketsTerminados');
            const elUrg   = document.getElementById('ticketsUrgentes');
            if (elTotal) elTotal.textContent = tickets.total_tickets || 0;
            if (elAb)    elAb.textContent    = tickets.tickets_comunicacion || 0;
            if (elProc)  elProc.textContent  = procesando;
            if (elTerm)  elTerm.textContent  = tickets.tickets_cierre || 0;
            if (elUrg)   elUrg.textContent   = urgentes;
        }
        
        // Actualizar métricas de rendimiento
        function updatePerformanceMetrics() {
            const rendimiento = dashboardData.rendimiento || {};
            document.getElementById('clientesActivos').textContent = rendimiento.total_clientes_activos || 0;
            document.getElementById('ticketsResueltos').textContent = rendimiento.total_tickets_resueltos || 0;
            document.getElementById('tiempoPromedio').textContent = (rendimiento.tiempo_promedio_resolucion_general || 0) + 'h';
            document.getElementById('efectividadEquipo').textContent = (rendimiento.efectividad_general || 0) + '%';
        }
        
        // Actualizar indicadores de cambio
        function updateStatChanges() {
            const clientes = dashboardData.clientes || {};
            const asignados = clientes.asignados || 0;
            const total = clientes.total || 0;
            const porcentajeAsignados = total > 0 ? Math.round((asignados / total) * 100) : 0;
            
            // Cambio en clientes
            const clientesChange = document.getElementById('clientesChange');
            clientesChange.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>${porcentajeAsignados}% asignados</span>
            `;
            
            // Cambio en asignados
            const asignadosChange = document.getElementById('asignadosChange');
            const noAsignados = total - asignados;
            asignadosChange.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>${noAsignados} disponibles</span>
            `;
            
            const tickets = dashboardData.tickets || {};
            const ticketsChange = document.getElementById('ticketsChange');
            const ticketsTerminados = tickets.tickets_cierre || 0;
            const totalTickets = tickets.total_tickets || 0;
            const porcentajeTerminados = totalTickets > 0 ? Math.round((ticketsTerminados / totalTickets) * 100) : 0;
            if (ticketsChange) {
                ticketsChange.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    <span>${porcentajeTerminados}% cerrados</span>
                `;
            }
            
            // Cambio en efectividad
            const efectividad = dashboardData.rendimiento?.efectividad_general || 0;
            const efectividadChange = document.getElementById('efectividadChange');
            const colorClass = efectividad >= 80 ? 'positive' : efectividad >= 60 ? 'warning' : 'negative';
            efectividadChange.className = `stat-change ${colorClass}`;
            efectividadChange.innerHTML = `
                <i class="fas fa-${efectividad >= 80 ? 'arrow-up' : efectividad >= 60 ? 'minus' : 'arrow-down'}"></i>
                <span>${efectividad}% efectividad</span>
            `;
        }

        /** Etiqueta de estado en tarjetas: el API del dashboard envía activo (1/0), no estado. */
        function badgeEstadoAsesorCard(asesor) {
            if (asesor.estado != null && String(asesor.estado).trim() !== '') {
                const slug = String(asesor.estado).toLowerCase().replace(/\s+/g, '-');
                return { className: 'status-badge status-' + slug, text: String(asesor.estado) };
            }
            const esActivo = asesor.activo === true || asesor.activo === 1 || asesor.activo === '1';
            return {
                className: 'status-badge ' + (esActivo ? 'status-active' : 'status-inactive'),
                text: esActivo ? 'Activo' : 'Inactivo'
            };
        }

        // Renderizar asesores
        function renderAsesores() {
            const container = document.getElementById('asesoresList');
            container.innerHTML = '';

            if (!dashboardData.asesores || dashboardData.asesores.length === 0) {
                container.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 20px;">No hay asesores asignados</p>';
                return;
            }

            dashboardData.asesores.forEach((asesor, idx) => {
                const tickets = asesor.tickets || {};
                const rendimiento = asesor.rendimiento || {};
                const estadoBadge = badgeEstadoAsesorCard(asesor);
                // #region agent log
                if (idx === 0) {
                    fetch('http://127.0.0.1:7895/ingest/5fcbb579-d53a-47d5-84e9-86a55388551f',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'7f7f84'},body:JSON.stringify({sessionId:'7f7f84',runId:'post-fix',hypothesisId:'H1-H4',location:'coordinador_dashboard.php:renderAsesores',message:'badge after mapping',data:{estadoRaw:asesor.estado,activo:asesor.activo,badgeText:estadoBadge.text,badgeClass:estadoBadge.className},timestamp:Date.now()})}).catch(function(){});
                }
                // #endregion
                
                const asesorCard = document.createElement('div');
                asesorCard.className = 'asesor-card';
                asesorCard.innerHTML = `
                    <div class="asesor-header">
                        <div>
                            <div class="asesor-name">${asesor.nombre} ${asesor.apellido}</div>
                            <div class="asesor-email">${asesor.email}</div>
                            <div class="asesor-status">
                                <span class="${estadoBadge.className}">${estadoBadge.text}</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="verDetallesAsesor('${asesor.cedula}')">
                            <i class="fas fa-chart-bar"></i> Ver Detalles
                        </button>
                    </div>
                    <div class="asesor-metrics">
                        <div class="metric-item">
                            <div class="metric-item-value">${asesor.total_clientes || 0}</div>
                            <div class="metric-item-label">Clientes</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-item-value">${tickets.total_tickets || 0}</div>
                            <div class="metric-item-label">Tickets</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-item-value">${tickets.tickets_cierre || 0}</div>
                            <div class="metric-item-label">Cerrados</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-item-value">${rendimiento.efectividad || 0}%</div>
                            <div class="metric-item-label">Efectividad</div>
                        </div>
                    </div>
                    <div class="asesor-details">
                        <div class="detail-row">
                            <span class="detail-label">Comunicación:</span>
                            <span class="detail-value">${tickets.tickets_comunicacion || 0}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Validación:</span>
                            <span class="detail-value">${tickets.tickets_validacion || 0}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Proceso judicial:</span>
                            <span class="detail-value">${tickets.tickets_proceso_judicial || 0}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Remate:</span>
                            <span class="detail-value">${tickets.tickets_remate || 0}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Recuperación:</span>
                            <span class="detail-value">${tickets.tickets_recuperacion || 0}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tiempo Promedio:</span>
                            <span class="detail-value">${Math.round(tickets.tiempo_promedio_resolucion || 0)}h</span>
                        </div>
                    </div>
                `;
                container.appendChild(asesorCard);
            });
        }

        // Cargar asesores para gestión
        async function loadAsesores() {
            try {
                const response = await fetch('../api/coordinador_asesores.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    renderAsesoresTable(result.data);
                } else {
                    showMessage('Error cargando asesores: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando asesores:', error);
                showMessage('Error cargando asesores', 'error');
            }
        }

        // Renderizar tabla de asesores
        function renderAsesoresTable(asesores) {
            const tbody = document.getElementById('asesoresTableBody');
            tbody.innerHTML = '';

            if (!asesores || asesores.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #6b7280;">No hay asesores asignados</td></tr>';
                return;
            }

            asesores.forEach(asesor => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="text-truncate" title="${asesor.cedula}" style="max-width: 120px;">
                            ${asesor.cedula}
                        </div>
                    </td>
                    <td>
                        <div class="text-truncate" title="${asesor.usuario}" style="max-width: 100px;">
                            ${asesor.usuario}
                        </div>
                    </td>
                    <td>
                        <div class="user-info-cell">
                            <div class="user-avatar-small">
                                ${asesor.nombre.charAt(0).toUpperCase()}${asesor.apellido.charAt(0).toUpperCase()}
                            </div>
                            <div class="user-details">
                                <div class="text-truncate" title="${asesor.nombre} ${asesor.apellido}" style="max-width: 150px; font-weight: 600;">
                                    ${asesor.nombre} ${asesor.apellido}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-truncate" title="${asesor.email}" style="max-width: 180px;">
                            ${asesor.email}
                        </div>
                    </td>
                    <td>
                        <div class="text-truncate" title="${asesor.telefono || 'Sin teléfono'}" style="max-width: 120px;">
                            ${asesor.telefono || 'N/A'}
                        </div>
                    </td>
                    <td>
                        <span class="status-badge ${asesor.activo ? 'status-active' : 'status-inactive'}">
                            ${asesor.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <span class="client-count">${asesor.total_clientes || 0}</span>
                    </td>
                    <td>
                        <div class="action-buttons-table">
                            <button class="btn btn-sm btn-info" onclick="verDetallesAsesor('${asesor.cedula}')" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="editarAsesor('${asesor.cedula}')" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm ${asesor.activo ? 'btn-danger' : 'btn-success'}" onclick="toggleAsesor('${asesor.cedula}')" title="${asesor.activo ? 'Deshabilitar' : 'Habilitar'}">
                                <i class="fas fa-${asesor.activo ? 'ban' : 'check'}"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Ver detalles del asesor
        function verDetallesAsesor(cedula) {
            // Implementar modal de detalles
            showMessage('Funcionalidad de detalles próximamente', 'info');
        }

        // Editar asesor
        function editarAsesor(cedula) {
            // Implementar modal de edición
            showMessage('Funcionalidad de edición próximamente', 'info');
        }

        // Toggle estado del asesor
        async function toggleAsesor(cedula) {
            if (!confirm('¿Está seguro de cambiar el estado de este asesor?')) return;

            try {
                const response = await fetch('../api/toggle_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ user_cedula: cedula })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    loadAsesores();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cambiando estado:', error);
                showMessage('Error cambiando estado del asesor', 'error');
            }
        }

        // Funciones de utilidad
        function refreshData() {
            loadDashboardData();
        }

        function refreshAsesores() {
            showMessage('Actualizando lista de asesores...', 'info');
            loadAsesores();
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
                <span style="white-space: pre-line;">${message}</span>
            `;
            
            const contentArea = document.querySelector('.content-area');
            contentArea.insertBefore(messageDiv, contentArea.firstChild);
            
            // Tiempo de duración basado en el tipo
            let duration = 5000;
            if (type === 'info') duration = 3000;
            else if (type === 'warning') duration = 7000;
            else if (type === 'error') duration = 8000;
            
            setTimeout(() => {
                messageDiv.remove();
            }, duration);
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
                    // Notificar a otras pestañas que se cerró sesión
                    localStorage.setItem('crm_force_logout', 'true');
                    clearInterval(sessionCheckInterval);
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

        // Búsqueda de clientes
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const termino = e.target.value;
            if (termino.length > 2) {
                // Implementar búsqueda
                console.log('Buscando:', termino);
            }
        });

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('createUserModal');
            if (modal && event.target === modal) {
                closeCreateUserModal();
            }
        });

    </script>
</body>
</html>
