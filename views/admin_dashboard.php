<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('admin');

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
    <title>Dashboard Administrador - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
</head>
<body>
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    <div class="dashboard-container">
        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo logo-coordinador">
                    <img src="img/logo2.png" alt="logocrm">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Administration</div>
                    <a href="<?php echo app_nav_url('admin_dashboard'); ?>" class="nav-item active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo app_nav_url('analytics'); ?>" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                    <a href="<?php echo app_nav_url('settings'); ?>" class="nav-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h4><?php echo $user['nombre'] . ' ' . $user['apellido']; ?></h4>
                        <p><?php echo ucfirst($user['rol_nombre']); ?></p>
                    </div>
                </div>
                
                <button class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-section">
                    <h1>Bienvenido de vuelta, <?php echo $user['nombre']; ?>!</h1>
                    <p>Mejora tu gestión de ventas para un mejor crecimiento.</p>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar cualquier cosa...">
                    </div>
                    <div class="header-icon">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="header-icon">
                        <i class="fas fa-flag"></i>
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

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openCreateUserModal()">
                        <i class="fas fa-user-plus"></i> Crear Nuevo Usuario
                    </button>
                    <button class="btn btn-success" onclick="openAssignModal()">
                        <i class="fas fa-users-cog"></i> Asignar Asesores
                    </button>
                    <button class="btn btn-warning" onclick="refreshUsers()">
                        <i class="fas fa-sync-alt"></i> Actualizar Lista
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Usuarios</div>
                            <div class="stat-icon icon-total">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalUsers">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12% este mes</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Usuarios Activos</div>
                            <div class="stat-icon icon-nuevos">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="activeUsers">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8% este mes</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Coordinadores</div>
                            <div class="stat-icon icon-gestionados">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="coordinators">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+5% este mes</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Asesores</div>
                            <div class="stat-icon icon-llamadas">
                                <i class="fas fa-user-friends"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="advisors">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+15% este mes</span>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Gestión de Usuarios</h2>
                        <button class="btn btn-primary btn-refresh" onclick="refreshUsers()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div class="table-container">
                        <table class="users-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Coordinador</th>
                                    <th>Extensión SIP</th>
                                    <th>Estado</th>
                                    <th>PDF</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Los usuarios se cargarán aquí via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Crear Nuevo Usuario</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cedula">Cédula *</label>
                            <input type="text" id="cedula" name="cedula" class="form-control" required 
                                   placeholder="1234567890" pattern="[0-9]{7,20}">
                        </div>
                        <div class="form-group">
                            <label for="usuario">Usuario *</label>
                            <input type="text" id="usuario" name="usuario" class="form-control" required 
                                   placeholder="nombre_usuario" pattern="[a-zA-Z0-9_]{3,20}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido *</label>
                            <input type="text" id="apellido" name="apellido" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" 
                                   placeholder="+57 300 123 4567">
                        </div>
                    </div>
                    <div class="form-row" id="sipAsesorFieldsRow" style="display: none;">
                        <div class="form-group">
                            <label for="sip_extension">Extensión SIP (softphone / PBX)</label>
                            <input type="text" id="sip_extension" name="sip_extension" class="form-control" maxlength="40"
                                   placeholder="Ej. 1001" autocomplete="off">
                            <small id="sipExtensionHint" class="form-text form-text-muted">Solo asesores. En edición: dejar en blanco para quitar la extensión.</small>
                        </div>
                        <div class="form-group">
                            <label for="sip_secret">Clave SIP (password en texto plano)</label>
                            <input type="text" id="sip_secret" name="sip_secret" class="form-control" maxlength="128"
                                   placeholder="Tal como la lee el PBX / softphone" autocomplete="off">
                            <small id="sipSecretHint" class="form-text form-text-muted">
                                Solo aplica a <strong>asesores</strong>. Se guarda en texto plano (requisito Asterisk / WebRTC).
                            </small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" id="passwordLabel">Contraseña *</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                            <small id="passwordHint" class="form-text form-text-muted"></small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" id="confirmPasswordLabel">Confirmar contraseña *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rol_id">Rol *</label>
                            <select id="rol_id" name="rol_id" class="form-control" required>
                                <option value="">Seleccionar rol</option>
                            </select>
                        </div>
                        <div class="form-group" id="coordinadorGroup" style="display: none;">
                            <label for="coordinador_cedula">Coordinador</label>
                            <select id="coordinador_cedula" name="coordinador_cedula" class="form-control">
                                <option value="">Seleccionar coordinador</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pdf_documento">Documento PDF (Opcional)</label>
                        <input type="file" id="pdf_documento" name="pdf_documento" class="form-control" 
                               accept=".pdf" onchange="previewPDF(this)">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> 
                            Sube un documento PDF de cualquier tamaño. Formatos permitidos: PDF
                        </small>
                        <div id="pdf-preview" class="pdf-preview" style="display: none;">
                            <div class="pdf-preview-content">
                                <i class="fas fa-file-pdf"></i>
                                <span id="pdf-filename"></span>
                                <button type="button" class="btn-remove-pdf" onclick="removePDF()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para visualizar PDF -->
    <div id="pdfModal" class="pdf-modal">
        <div class="pdf-modal-content">
            <div class="pdf-modal-header">
                <h3 class="pdf-modal-title" id="pdfModalTitle">PDF del Usuario</h3>
                <span class="pdf-modal-close" onclick="closePDFModal()">&times;</span>
            </div>
            <div class="pdf-modal-body">
                <iframe id="pdfIframe" src=""></iframe>
            </div>
        </div>
    </div>

    <!-- Modal para asignar asesores -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Asignar Asesores a Coordinador</h3>
                <span class="close" onclick="closeAssignModal()">&times;</span>
            </div>
            <form id="assignForm">
                <div class="form-group">
                    <label for="assignCoordinador">Seleccionar Coordinador</label>
                    <select id="assignCoordinador" name="coordinador_id" class="form-control" required>
                        <option value="">Seleccionar coordinador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Asesores Disponibles</label>
                    <div id="advisorsList" style="max-height: 300px; overflow-y: auto; border: 1px solid #4a5568; border-radius: 8px; padding: 15px;">
                        <!-- Los asesores se cargarán aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales
        let users = [];
        let roles = [];
        let coordinators = [];

        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadRoles();
            loadUsers();
            loadCoordinators();
            initializeMobileSidebar();
        });

        // Inicializar sidebar móvil
        function initializeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.querySelector('.menu-toggle');
            const mainContent = document.querySelector('.main-content');

            // Función para abrir/cerrar sidebar
            function toggleSidebar() {
                const isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    sidebar.classList.toggle('mobile-open');
                    overlay.classList.toggle('active');
                    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
                }
            }

            // Event listeners
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }

            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }

            // Cerrar sidebar al redimensionar ventana
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // Cerrar sidebar al presionar Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                    toggleSidebar();
                }
            });
        }

        // Función global para toggle sidebar (puede ser llamada desde HTML)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = window.innerWidth <= 768;

            if (isMobile) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
            }
        }

        // Cargar roles
        async function loadRoles() {
            try {
                const response = await fetch('api/roles.php');
                const data = await response.json();
                
                if (data.success === false) {
                    console.error('Error cargando roles:', data.message);
                    return;
                }
                
                roles = data;
                
                const rolSelect = document.getElementById('rol_id');
                rolSelect.innerHTML = '<option value="">Seleccionar rol</option>';
                
                if (roles && roles.length > 0) {
                    roles.forEach(role => {
                        rolSelect.innerHTML += `<option value="${role.id}">${role.nombre}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error cargando roles:', error);
            }
        }

        // Cargar usuarios
        async function loadUsers() {
            try {
                const response = await fetch('api/users.php');
                const data = await response.json();
                
                if (data.success === false) {
                    showMessage(data.message, 'error');
                    return;
                }
                
                users = data;
                renderUsersTable();
                updateStats();
            } catch (error) {
                console.error('Error cargando usuarios:', error);
                showMessage('Error cargando usuarios', 'error');
            }
        }

        // Cargar coordinadores
        async function loadCoordinators() {
            try {
                const response = await fetch('api/coordinators.php');
                const data = await response.json();
                
                if (data.success === false) {
                    console.error('Error cargando coordinadores:', data.message);
                    return;
                }
                
                coordinators = data;
                
                const coordinadorSelect = document.getElementById('coordinador_cedula');
                const assignCoordinadorSelect = document.getElementById('assignCoordinador');
                
                coordinadorSelect.innerHTML = '<option value="">Seleccionar coordinador</option>';
                assignCoordinadorSelect.innerHTML = '<option value="">Seleccionar coordinador</option>';
                
                if (coordinators && coordinators.length > 0) {
                    coordinators.forEach(coord => {
                        coordinadorSelect.innerHTML += `<option value="${coord.cedula}">${coord.nombre} ${coord.apellido}</option>`;
                        assignCoordinadorSelect.innerHTML += `<option value="${coord.cedula}">${coord.nombre} ${coord.apellido}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error cargando coordinadores:', error);
            }
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateSipFieldsVisibility() {
            const row = document.getElementById('sipAsesorFieldsRow');
            if (!row) return;
            const rolId = document.getElementById('rol_id').value;
            const rol = roles && roles.find(r => String(r.id) === String(rolId));
            row.style.display = rol && rol.nombre === 'asesor' ? '' : 'none';
        }

        function setUserFormEditMode(isEdit) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordLabel = document.getElementById('passwordLabel');
            const confirmLabel = document.getElementById('confirmPasswordLabel');
            const passwordHint = document.getElementById('passwordHint');
            const sipExtHint = document.getElementById('sipExtensionHint');
            const sipSecHint = document.getElementById('sipSecretHint');

            if (isEdit) {
                password.required = false;
                confirmPassword.required = false;
                passwordLabel.textContent = 'Contraseña nueva (opcional)';
                confirmLabel.textContent = 'Confirmar contraseña nueva';
                password.placeholder = 'Dejar en blanco para mantener la actual';
                confirmPassword.placeholder = 'Repita solo si cambia la contraseña';
                if (passwordHint) {
                    passwordHint.textContent = 'Si no desea cambiar la contraseña, deje ambos campos vacíos.';
                }
                if (sipExtHint) {
                    sipExtHint.textContent = 'Si el asesor tiene extensión, aparece aquí. Dejar en blanco para quitarla.';
                }
                if (sipSecHint) {
                    sipSecHint.innerHTML = 'Dejar en blanco para <strong>mantener</strong> la clave SIP actual. Escriba un valor solo si desea cambiarla.';
                }
            } else {
                password.required = true;
                confirmPassword.required = true;
                passwordLabel.textContent = 'Contraseña *';
                confirmLabel.textContent = 'Confirmar contraseña *';
                password.placeholder = '';
                confirmPassword.placeholder = '';
                if (passwordHint) {
                    passwordHint.textContent = '';
                }
                if (sipExtHint) {
                    sipExtHint.textContent = 'Solo asesores. Opcional al crear.';
                }
                if (sipSecHint) {
                    sipSecHint.innerHTML = 'Solo aplica a <strong>asesores</strong>. Se guarda en texto plano (requisito Asterisk / WebRTC).';
                }
            }
        }

        // Renderizar tabla de usuarios
        function renderUsersTable() {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';

            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="message-no-data">No hay usuarios registrados</td></tr>';
                return;
            }

            users.forEach(user => {
                const row = document.createElement('tr');
                const sipCol = user.rol_nombre === 'asesor'
                    ? (user.sip_extension ? escapeHtml(String(user.sip_extension)) : '—')
                    : '—';
                row.innerHTML = `
                    <td>
                        <div class="cedula-text">${user.cedula}</div>
                    </td>
                    <td>
                        <div class="usuario-text">${user.usuario}</div>
                    </td>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">
                                ${user.nombre.charAt(0).toUpperCase()}${user.apellido.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="user-name">${user.nombre} ${user.apellido}</div>
                            </div>
                        </div>
                    </td>
                    <td>${user.email}</td>
                    <td>${user.telefono || 'N/A'}</td>
                    <td>
                        <span class="role-badge role-${user.rol_nombre}">
                            ${user.rol_nombre.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td>${user.coordinador_nombre || 'N/A'}</td>
                    <td>${sipCol}</td>
                    <td>
                        <span class="status-badge ${user.activo ? 'status-active' : 'status-inactive'}">
                            ${user.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        ${user.pdf_documento ? 
                            `<a href="#" class="pdf-viewer" onclick="viewPDF('${user.pdf_documento}', '${user.nombre} ${user.apellido}')">
                                <i class="fas fa-file-pdf"></i>
                                Ver PDF
                            </a>` : 
                            '<span class="no-pdf-text">Sin PDF</span>'
                        }
                    </td>
                    <td>
                        <div class="action-buttons-table">
                            <button class="btn btn-sm btn-edit" onclick="editUser('${user.cedula}')" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-toggle" onclick="toggleUser('${user.cedula}')" title="${user.activo ? 'Inhabilitar' : 'Habilitar'}">
                                <i class="fas fa-${user.activo ? 'ban' : 'check'}"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Actualizar estadísticas
        async function updateStats() {
            // Verificar que los elementos del DOM existen
            const totalUsersEl = document.getElementById('totalUsers');
            const activeUsersEl = document.getElementById('activeUsers');
            const coordinatorsEl = document.getElementById('coordinators');
            const advisorsEl = document.getElementById('advisors');

            if (!users || users.length === 0) {
                if (totalUsersEl) totalUsersEl.textContent = '0';
                if (activeUsersEl) activeUsersEl.textContent = '0';
                if (coordinatorsEl) coordinatorsEl.textContent = '0';
                if (advisorsEl) advisorsEl.textContent = '0';

                // Resetear cambios
                updateStatChanges({
                    total_users_change: '+0% este mes',
                    active_users_change: '+0% este mes',
                    coordinators_change: '+0% este mes',
                    advisors_change: '+0% este mes'
                });
                return;
            }

            const totalUsers = users.length;
            const activeUsers = users.filter(u => u.activo).length;
            const coordinators = users.filter(u => u.rol_nombre === 'coordinador').length;
            const advisors = users.filter(u => u.rol_nombre === 'asesor').length;

            if (totalUsersEl) totalUsersEl.textContent = totalUsers;
            if (activeUsersEl) activeUsersEl.textContent = activeUsers;
            if (coordinatorsEl) coordinatorsEl.textContent = coordinators;
            if (advisorsEl) advisorsEl.textContent = advisors;

            // Cargar cambios porcentuales
            await loadUserStatsChanges();
        }

        // Cargar cambios porcentuales de estadísticas
        async function loadUserStatsChanges() {
            try {
                const response = await fetch('api/user_stats.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    updateStatChanges(result.data);
                } else {
                    console.warn('Error cargando estadísticas de cambios:', result.message);
                    // Usar valores por defecto
                    updateStatChanges({
                        total_users_change: '+0% este mes',
                        active_users_change: '+0% este mes',
                        coordinators_change: '+0% este mes',
                        advisors_change: '+0% este mes'
                    });
                }
            } catch (error) {
                console.error('Error cargando cambios de estadísticas:', error.message);
                // Usar valores por defecto en caso de error
                updateStatChanges({
                    total_users_change: '+0% este mes',
                    active_users_change: '+0% este mes',
                    coordinators_change: '+0% este mes',
                    advisors_change: '+0% este mes'
                });
            }
        }

        // Actualizar elementos de cambio porcentual
        function updateStatChanges(stats) {
            // Verificar que los elementos existen antes de acceder a ellos
            const totalUsersChange = document.querySelector('#totalUsers')?.nextElementSibling;
            const activeUsersChange = document.querySelector('#activeUsers')?.nextElementSibling;
            const coordinatorsChange = document.querySelector('#coordinators')?.nextElementSibling;
            const advisorsChange = document.querySelector('#advisors')?.nextElementSibling;

            // Actualizar textos con verificación de existencia
            if (totalUsersChange) {
                totalUsersChange.innerHTML = `<i class="fas fa-arrow-${stats.total_users_change.includes('+') ? 'up' : 'down'}"></i> <span>${stats.total_users_change}</span>`;
            }
            if (activeUsersChange) {
                activeUsersChange.innerHTML = `<i class="fas fa-arrow-${stats.active_users_change.includes('+') ? 'up' : 'down'}"></i> <span>${stats.active_users_change}</span>`;
            }
            if (coordinatorsChange) {
                coordinatorsChange.innerHTML = `<i class="fas fa-arrow-${stats.coordinators_change.includes('+') ? 'up' : 'down'}"></i> <span>${stats.coordinators_change}</span>`;
            }
            if (advisorsChange) {
                advisorsChange.innerHTML = `<i class="fas fa-arrow-${stats.advisors_change.includes('+') ? 'up' : 'down'}"></i> <span>${stats.advisors_change}</span>`;
            }

            // Aplicar clases de color con verificación de existencia
            [totalUsersChange, activeUsersChange, coordinatorsChange, advisorsChange].forEach(change => {
                if (change) {
                    const span = change.querySelector('span');
                    if (span) {
                        const isPositive = span.textContent.includes('+');
                        change.className = `stat-change ${isPositive ? 'positive' : 'negative'}`;
                    }
                }
            });
        }

        // Abrir modal de crear usuario
        function openCreateUserModal() {
            document.getElementById('modalTitle').textContent = 'Crear Nuevo Usuario';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            setUserFormEditMode(false);
            document.getElementById('coordinadorGroup').style.display = 'none';
            updateSipFieldsVisibility();
            document.getElementById('userModal').style.display = 'block';
            
            // Configurar scroll del modal
            configurarScrollModalUsuario();
        }

        // Abrir modal de editar usuario
        function editUser(userCedula) {
            const user = users.find(u => u.cedula == userCedula);
            if (!user) return;

            document.getElementById('modalTitle').textContent = 'Editar Usuario';
            document.getElementById('userId').value = user.cedula;
            document.getElementById('cedula').value = user.cedula;
            document.getElementById('usuario').value = user.usuario;
            document.getElementById('nombre').value = user.nombre;
            document.getElementById('apellido').value = user.apellido;
            document.getElementById('email').value = user.email;
            document.getElementById('telefono').value = user.telefono || '';
            document.getElementById('sip_extension').value = user.sip_extension || '';
            document.getElementById('sip_secret').value = '';
            document.getElementById('rol_id').value = user.rol_id;
            document.getElementById('password').value = '';
            document.getElementById('confirm_password').value = '';
            setUserFormEditMode(true);
            
            // Mostrar campo coordinador si es asesor
            if (user.rol_nombre === 'asesor') {
                document.getElementById('coordinadorGroup').style.display = 'block';
                document.getElementById('coordinador_cedula').value = user.coordinador_cedula || '';
            } else {
                document.getElementById('coordinadorGroup').style.display = 'none';
            }
            updateSipFieldsVisibility();
            
            document.getElementById('userModal').style.display = 'block';
            
            // Configurar scroll del modal
            configurarScrollModalUsuario();
        }

        // Cerrar modal
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
            // Limpiar preview de PDF al cerrar
            removePDF();
        }

        // Configurar scroll del modal de usuario
        function configurarScrollModalUsuario() {
            const modalContent = document.querySelector('#userModal .modal-content');
            
            if (modalContent) {
                // Detectar scroll
                modalContent.addEventListener('scroll', function() {
                    if (modalContent.scrollTop > 0) {
                        modalContent.classList.add('scrolled');
                    } else {
                        modalContent.classList.remove('scrolled');
                    }
                });
                
                // Scroll suave al hacer clic en campos
                const formControls = modalContent.querySelectorAll('.form-control');
                formControls.forEach(control => {
                    control.addEventListener('focus', function() {
                        // Scroll suave al campo que recibe focus
                        setTimeout(() => {
                            control.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }, 100);
                    });
                });
            }
        }

        // Preview de PDF
        function previewPDF(input) {
            const file = input.files[0];
            const preview = document.getElementById('pdf-preview');
            const filename = document.getElementById('pdf-filename');
            
            if (file) {
                // Validar que sea PDF
                if (file.type !== 'application/pdf') {
                    showMessage('Por favor selecciona un archivo PDF válido', 'error');
                    input.value = '';
                    return;
                }
                
                // Validar tamaño (máximo 50MB)
                const maxSize = 50 * 1024 * 1024; // 50MB
                if (file.size > maxSize) {
                    showMessage('El archivo PDF no puede ser mayor a 50MB', 'error');
                    input.value = '';
                    return;
                }
                
                filename.textContent = file.name;
                preview.style.display = 'block';
                showMessage('PDF seleccionado correctamente', 'success');
            } else {
                preview.style.display = 'none';
            }
        }

        // Remover PDF
        function removePDF() {
            const input = document.getElementById('pdf_documento');
            const preview = document.getElementById('pdf-preview');
            
            input.value = '';
            preview.style.display = 'none';
        }

        // Visualizar PDF
        function viewPDF(pdfPath, userName) {
            const modal = document.getElementById('pdfModal');
            const title = document.getElementById('pdfModalTitle');
            const iframe = document.getElementById('pdfIframe');
            
            title.textContent = `PDF de ${userName}`;
            iframe.src = '../' + pdfPath;
            modal.style.display = 'block';
        }

        // Cerrar modal de PDF
        function closePDFModal() {
            const modal = document.getElementById('pdfModal');
            const iframe = document.getElementById('pdfIframe');
            
            modal.style.display = 'none';
            iframe.src = '';
        }

        // Abrir modal de asignación
        function openAssignModal() {
            loadAdvisors();
            document.getElementById('assignModal').style.display = 'block';
        }

        // Cerrar modal de asignación
        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }

        // Cargar asesores para asignación
        async function loadAdvisors() {
            try {
                const response = await fetch('api/advisors.php');
                const data = await response.json();
                
                if (data.success === false) {
                    console.error('Error cargando asesores:', data.message);
                    return;
                }
                
                renderAdvisorsList(data);
            } catch (error) {
                console.error('Error cargando asesores:', error);
            }
        }

        // Renderizar lista de asesores
        function renderAdvisorsList(advisors) {
            const container = document.getElementById('advisorsList');
            container.innerHTML = '';

            if (!advisors || advisors.length === 0) {
                container.innerHTML = '<div class="message-no-data">No hay asesores disponibles</div>';
                return;
            }

            advisors.forEach(advisor => {
                const advisorDiv = document.createElement('div');
                advisorDiv.className = 'advisor-item';
                advisorDiv.innerHTML = `
                    <input type="checkbox" id="advisor_${advisor.cedula}" value="${advisor.cedula}">
                    <label for="advisor_${advisor.cedula}" class="advisor-label">
                        ${advisor.nombre} ${advisor.apellido} (${advisor.email})
                    </label>
                `;
                container.appendChild(advisorDiv);
            });
        }

        // Manejar cambio de rol
        document.getElementById('rol_id').addEventListener('change', function() {
            const coordinadorGroup = document.getElementById('coordinadorGroup');
            if (this.value && roles.find(r => r.id == this.value)?.nombre === 'asesor') {
                coordinadorGroup.style.display = 'block';
            } else {
                coordinadorGroup.style.display = 'none';
            }
            updateSipFieldsVisibility();
        });

        // Validar contraseñas
        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
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

        // Manejar envío del formulario de usuario
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar contraseñas si es creación o si se proporciona contraseña
            const userId = document.getElementById('userId').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!userId) {
                if (!validatePasswords()) {
                    return;
                }
            } else if (password !== '' || confirmPassword !== '') {
                if (!validatePasswords()) {
                    return;
                }
            }
            
            const formData = new FormData(this);
            const url = userId ? 'api/update_user.php' : 'api/create_user.php';
            
            try {
                showMessage('Procesando usuario...', 'info');
                
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    loadUsers();
                    showMessage(result.message, 'success');
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error del sistema', 'error');
            }
        });

        // Manejar envío del formulario de asignación
        document.getElementById('assignForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const coordinadorId = document.getElementById('assignCoordinador').value;
            const selectedAdvisors = Array.from(document.querySelectorAll('#advisorsList input[type="checkbox"]:checked'))
                .map(cb => cb.value);
            
            if (selectedAdvisors.length === 0) {
                showMessage('Seleccione al menos un asesor', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/assign_advisors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        coordinador_cedula: coordinadorId,
                        advisor_cedulas: selectedAdvisors
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeAssignModal();
                    loadUsers();
                    showMessage(result.message, 'success');
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('Error del sistema', 'error');
            }
        });

        // Toggle usuario (habilitar/deshabilitar)
        async function toggleUser(userCedula) {
            const user = users.find(u => String(u.cedula) === String(userCedula));
            const accion = user && user.activo ? 'inhabilitar' : 'habilitar';
            if (!confirm('¿Está seguro de ' + accion + ' este usuario?')) return;
            
            try {
                const response = await fetch('api/toggle_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ user_cedula: userCedula })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadUsers();
                    showMessage(result.message, 'success');
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('Error del sistema', 'error');
            }
        }

        // Actualizar usuarios
        function refreshUsers() {
            showMessage('Actualizando lista de usuarios...', 'info');
            loadUsers();
        }

        // Mostrar mensaje
        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')}"></i>
                ${message}
            `;
            
            const contentArea = document.querySelector('.content-area');
            contentArea.insertBefore(messageDiv, contentArea.firstChild);
            
            // Solo auto-remover mensajes de info después de 3 segundos
            if (type === 'info') {
                setTimeout(() => {
                    messageDiv.remove();
                }, 3000);
            } else {
                setTimeout(() => {
                    messageDiv.remove();
                }, 5000);
            }
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

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const userModal = document.getElementById('userModal');
            const assignModal = document.getElementById('assignModal');
            
            if (event.target === userModal) {
                closeModal();
            }
            if (event.target === assignModal) {
                closeAssignModal();
            }
        }
    </script>
</body>
</html>