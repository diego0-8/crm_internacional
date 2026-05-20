<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('admin');

// Obtener datos del usuario actual
$user = getCurrentUser();
$message = getMessage();
$db = getDB();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/partials/app_head.php'; ?>
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <div class="logo">
                    <img src="img/logo2.png" alt="Logo CRM">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Administration</div>
                    <a href="<?php echo app_nav_url('admin_dashboard'); ?>" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo app_nav_url('analytics'); ?>" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                    <a href="<?php echo app_nav_url('settings'); ?>" class="nav-item active">
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
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1>Configuración del Sistema</h1>
                        <p>Administra la configuración general del CRM</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
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

                <!-- Settings Sections -->
                <div class="settings-container">
                    <!-- General Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-cogs"></i>
                                Configuración General
                            </h2>
                        </div>
                        <div class="settings-content">
                            <div class="setting-group">
                                <label for="appName">Nombre de la Aplicación</label>
                                <input type="text" id="appName" class="form-control" value="<?php echo APP_NAME; ?>">
                                <small class="form-text">Nombre que se muestra en el sistema</small>
                            </div>

                            <div class="setting-group">
                                <label for="appUrl">URL de la Aplicación</label>
                                <input type="url" id="appUrl" class="form-control" value="<?php echo APP_URL; ?>">
                                <small class="form-text">URL base del sistema</small>
                            </div>

                            <div class="setting-group">
                                <label for="sessionLifetime">Tiempo de Sesión (minutos)</label>
                                <input type="number" id="sessionLifetime" class="form-control" value="<?php echo SESSION_LIFETIME / 60; ?>" min="5" max="480">
                                <small class="form-text">Tiempo máximo de inactividad antes de cerrar sesión automáticamente</small>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-shield-alt"></i>
                                Configuración de Seguridad
                            </h2>
                        </div>
                        <div class="settings-content">
                            <div class="setting-group">
                                <label for="jwtSecret">JWT Secret</label>
                                <div class="input-group">
                                    <input type="password" id="jwtSecret" class="form-control" value="<?php echo substr(JWT_SECRET, 0, 20) . '...'; ?>" readonly>
                                    <button type="button" class="btn btn-secondary" onclick="regenerateJWTSecret()">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <small class="form-text">Clave secreta para tokens JWT (solo lectura por seguridad)</small>
                            </div>

                            <div class="setting-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="enableLogs" checked>
                                    <span>Habilitar registro de actividad</span>
                                </label>
                                <small class="form-text">Registra todas las acciones de usuarios en el sistema</small>
                            </div>

                            <div class="setting-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="enableSessions" checked>
                                    <span>Control de sesiones múltiples</span>
                                </label>
                                <small class="form-text">Limita sesiones activas por usuario</small>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-upload"></i>
                                Configuración de Archivos
                            </h2>
                        </div>
                        <div class="settings-content">
                            <div class="setting-group">
                                <label for="maxFileSize">Tamaño Máximo de Archivo (MB)</label>
                                <input type="number" id="maxFileSize" class="form-control" value="<?php echo MAX_FILE_SIZE / (1024 * 1024); ?>" min="1" max="50">
                                <small class="form-text">Tamaño máximo para archivos CSV y PDFs</small>
                            </div>

                            <div class="setting-group">
                                <label for="uploadPath">Directorio de Subidas</label>
                                <input type="text" id="uploadPath" class="form-control" value="<?php echo UPLOAD_PATH; ?>" readonly>
                                <small class="form-text">Directorio donde se almacenan los archivos subidos</small>
                            </div>

                            <div class="setting-group">
                                <label>Tipos de Archivo Permitidos</label>
                                <div class="allowed-types">
                                    <span class="type-badge">CSV</span>
                                    <span class="type-badge">PDF</span>
                                    <span class="type-badge">XLSX</span>
                                </div>
                                <small class="form-text">Formatos de archivo aceptados en el sistema</small>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-envelope"></i>
                                Configuración de Email
                            </h2>
                        </div>
                        <div class="settings-content">
                            <div class="setting-group">
                                <label for="smtpHost">SMTP Host</label>
                                <input type="text" id="smtpHost" class="form-control" value="<?php echo SMTP_HOST; ?>" placeholder="smtp.gmail.com">
                                <small class="form-text">Servidor SMTP para envío de correos</small>
                            </div>

                            <div class="setting-group">
                                <label for="smtpPort">SMTP Port</label>
                                <input type="number" id="smtpPort" class="form-control" value="<?php echo SMTP_PORT; ?>" placeholder="587">
                                <small class="form-text">Puerto del servidor SMTP</small>
                            </div>

                            <div class="setting-group">
                                <label for="smtpUsername">SMTP Username</label>
                                <input type="email" id="smtpUsername" class="form-control" value="<?php echo SMTP_USERNAME; ?>" placeholder="usuario@dominio.com">
                                <small class="form-text">Usuario para autenticación SMTP</small>
                            </div>

                            <div class="setting-group">
                                <label for="smtpPassword">SMTP Password</label>
                                <input type="password" id="smtpPassword" class="form-control" value="<?php echo str_repeat('*', strlen(SMTP_PASSWORD)); ?>" placeholder="Contraseña">
                                <small class="form-text">Contraseña para autenticación SMTP</small>
                            </div>

                            <div class="setting-group">
                                <button type="button" class="btn btn-info" onclick="testEmailConfig()">
                                    <i class="fas fa-paper-plane"></i> Probar Configuración
                                </button>
                                <small class="form-text">Envía un email de prueba para verificar la configuración</small>
                            </div>
                        </div>
                    </div>

                    <!-- Database Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-database"></i>
                                Configuración de Base de Datos
                            </h2>
                        </div>
                        <div class="settings-content">
                            <div class="setting-group">
                                <label for="dbHost">Host</label>
                                <input type="text" id="dbHost" class="form-control" value="<?php echo DB_HOST; ?>" readonly>
                                <small class="form-text">Servidor de base de datos (solo lectura)</small>
                            </div>

                            <div class="setting-group">
                                <label for="dbName">Nombre de Base de Datos</label>
                                <input type="text" id="dbName" class="form-control" value="<?php echo DB_NAME; ?>" readonly>
                                <small class="form-text">Nombre de la base de datos (solo lectura)</small>
                            </div>

                            <div class="setting-group">
                                <label for="dbUser">Usuario</label>
                                <input type="text" id="dbUser" class="form-control" value="<?php echo DB_USER; ?>" readonly>
                                <small class="form-text">Usuario de la base de datos (solo lectura)</small>
                            </div>

                            <div class="setting-group">
                                <button type="button" class="btn btn-warning" onclick="testDatabaseConnection()">
                                    <i class="fas fa-plug"></i> Probar Conexión
                                </button>
                                <small class="form-text">Verifica la conexión con la base de datos</small>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información del Sistema
                            </h2>
                        </div>
                        <div class="settings-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Versión del Sistema</div>
                                    <div class="info-value"><?php echo APP_VERSION; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">PHP Version</div>
                                    <div class="info-value"><?php echo phpversion(); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Servidor Web</div>
                                    <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Base de Datos</div>
                                    <div class="info-value">MySQL <?php echo htmlspecialchars($db->getAttribute(PDO::ATTR_SERVER_VERSION)); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Espacio en Disco</div>
                                    <div class="info-value"><?php echo round(disk_free_space(__DIR__) / (1024 * 1024 * 1024), 2); ?> GB libre</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Última Actualización</div>
                                    <div class="info-value"><?php echo date('d/m/Y H:i', filemtime(__FILE__)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicializar configuración
        document.addEventListener('DOMContentLoaded', function() {
            initializeMobileSidebar();
            loadCurrentSettings();
        });

        // Cargar configuración actual
        async function loadCurrentSettings() {
            try {
                const response = await fetch('api/settings.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    console.warn('No se pudo cargar configuración actual');
                    return;
                }

                const result = await response.json();
                if (result.success) {
                    // Aplicar configuración cargada
                    applySettings(result.data);
                }
            } catch (error) {
                console.warn('Error cargando configuración:', error);
            }
        }

        // Aplicar configuración
        function applySettings(settings) {
            // Aplicar valores si existen
            if (settings.app_name) document.getElementById('appName').value = settings.app_name;
            if (settings.session_lifetime) document.getElementById('sessionLifetime').value = settings.session_lifetime / 60;
            if (settings.max_file_size) document.getElementById('maxFileSize').value = settings.max_file_size / (1024 * 1024);
            if (settings.smtp_host) document.getElementById('smtpHost').value = settings.smtp_host;
            if (settings.smtp_port) document.getElementById('smtpPort').value = settings.smtp_port;
            if (settings.smtp_username) document.getElementById('smtpUsername').value = settings.smtp_username;
            if (settings.enable_logs !== undefined) document.getElementById('enableLogs').checked = settings.enable_logs;
            if (settings.enable_sessions !== undefined) document.getElementById('enableSessions').checked = settings.enable_sessions;
        }

        // Guardar configuración
        async function saveSettings() {
            const settings = {
                app_name: document.getElementById('appName').value,
                app_url: document.getElementById('appUrl').value,
                session_lifetime: document.getElementById('sessionLifetime').value * 60,
                max_file_size: document.getElementById('maxFileSize').value * 1024 * 1024,
                smtp_host: document.getElementById('smtpHost').value,
                smtp_port: document.getElementById('smtpPort').value,
                smtp_username: document.getElementById('smtpUsername').value,
                smtp_password: document.getElementById('smtpPassword').value,
                enable_logs: document.getElementById('enableLogs').checked,
                enable_sessions: document.getElementById('enableSessions').checked
            };

            try {
                showMessage('Guardando configuración...', 'info');

                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify(settings)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Configuración guardada exitosamente', 'success');
                } else {
                    showMessage('Error guardando configuración: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error guardando configuración:', error);
                showMessage('Error guardando configuración', 'error');
            }
        }

        // Regenerar JWT Secret
        async function regenerateJWTSecret() {
            if (!confirm('¿Está seguro de regenerar el JWT Secret? Esto cerrará todas las sesiones activas.')) {
                return;
            }

            try {
                const response = await fetch('api/regenerate_jwt.php', {
                    method: 'POST',
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('JWT Secret regenerado exitosamente. Todas las sesiones han sido cerradas.', 'success');
                    setTimeout(() => {
                        window.appGoLogin();
                    }, 3000);
                } else {
                    showMessage('Error regenerando JWT Secret: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error regenerando JWT:', error);
                showMessage('Error regenerando JWT Secret', 'error');
            }
        }

        // Probar configuración de email
        async function testEmailConfig() {
            try {
                showMessage('Enviando email de prueba...', 'info');

                const response = await fetch('api/test_email.php', {
                    method: 'POST',
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Email de prueba enviado exitosamente', 'success');
                } else {
                    showMessage('Error enviando email: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error probando email:', error);
                showMessage('Error probando configuración de email', 'error');
            }
        }

        // Probar conexión a base de datos
        async function testDatabaseConnection() {
            try {
                showMessage('Probando conexión a base de datos...', 'info');

                const response = await fetch('api/test_db.php', {
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Conexión a base de datos exitosa', 'success');
                } else {
                    showMessage('Error de conexión: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error probando DB:', error);
                showMessage('Error probando conexión a base de datos', 'error');
            }
        }

        // Mostrar mensaje
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
                const response = await fetch('api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
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

        // Inicializar sidebar móvil
        function initializeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.querySelector('.menu-toggle');

            function toggleSidebar() {
                const isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    sidebar.classList.toggle('mobile-open');
                    overlay.classList.toggle('active');
                    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
                }
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }

            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }
    </script>

    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .settings-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .settings-section .section-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 20px;
            margin: 0;
        }

        .settings-section .section-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .setting-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .setting-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .setting-group .form-control {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .setting-group .form-control:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .setting-group .form-control[readonly] {
            background-color: #f9fafb;
            cursor: not-allowed;
        }

        .form-text {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .input-group {
            display: flex;
            gap: 8px;
        }

        .input-group .form-control {
            flex: 1;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: normal !important;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #8b5cf6;
        }

        .allowed-types {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .type-badge {
            background: #e0e7ff;
            color: #5b21b6;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #8b5cf6;
        }

        .info-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .info-value {
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 768px) {
            .settings-content {
                padding: 15px;
                gap: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .input-group {
                flex-direction: column;
            }

            .allowed-types {
                justify-content: center;
            }
        }
    </style>
</body>
</html>