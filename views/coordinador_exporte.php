<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('coordinador');

$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/partials/app_head.php'; ?>
    <title>Exporte de reportes - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/asesor.css" rel="stylesheet">
    <link href="css/coordinador.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo logo-asesor">
                    <img src="img/logo2.png" alt="Logo CRM">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Coordinador</div>
                    <a href="<?php echo app_nav_url('coordinador_dashboard'); ?>" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_tareas'); ?>" class="nav-item">
                        <i class="fas fa-tasks"></i>
                        Tareas
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_gestion'); ?>" class="nav-item">
                        <i class="fas fa-upload"></i>
                        Gestión CSV
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_exporte'); ?>" class="nav-item active">
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
                        <h4 class="text-asesor"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h4>
                        <p class="text-asesor">Coordinador</p>
                    </div>
                </div>
                <button type="button" class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header header-asesor">
                <div class="header-left">
                    <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1 class="title-asesor">Exporte de reportes</h1>
                        <p class="subtitle-asesor">Descargue CSV de titulares (reparto), asignación a asesores y tickets de su equipo.</p>
                    </div>
                </div>
            </div>

            <div class="content-area coordinador-dashboard coordinador-exporte">
                <?php if ($message): ?>
                    <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="message info exporte-info-banner">
                    <i class="fas fa-info-circle"></i>
                    <span>Los reportes de <strong>titulares</strong> y <strong>asignación</strong> filtran por <strong>fecha de registro</strong> del titular en el sistema. Los <strong>tickets</strong> filtran por <strong>fecha de creación</strong> en la tiketera. El archivo CSV incluye codificación UTF-8 para Excel.</span>
                </div>

                <div class="card exporte-preview-card" id="exportePreviewCard" aria-live="polite">
                    <div class="exporte-preview-inner">
                        <i class="fas fa-table" aria-hidden="true"></i>
                        <div>
                            <strong id="exportePreviewLabel">Vista previa</strong>
                            <p id="exportePreviewText" class="exporte-preview-text">Seleccione tipo de reporte y fechas para ver cuántos registros se exportarán.</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-csv"></i> Generar reporte</h3>
                    </div>
                    <div class="card-content">
                        <form id="exportForm" novalidate>
                            <div class="form-row exporte-form-row">
                                <div class="form-group">
                                    <label for="fechaInicio">Fecha de inicio</label>
                                    <input type="date" id="fechaInicio" name="fecha_inicio" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="fechaFin">Fecha de fin</label>
                                    <input type="date" id="fechaFin" name="fecha_fin" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="tipoReporte">Tipo de reporte</label>
                                <select id="tipoReporte" name="tipo_reporte" class="form-control" required>
                                    <option value="titulares_reparto">Titulares reparto (detalle completo)</option>
                                    <option value="resumen_asignacion">Resumen asignación por asesor</option>
                                    <option value="tickets_equipo">Tickets CRM del equipo</option>
                                    <option value="metricas_asesores">Métricas diarias de asesores</option>
                                </select>
                                <small class="form-text text-muted" id="tipoReporteHelp">
                                    Incluye titular, propiedad, Reg_Int, BaseD, F_Correo, contactos y asesor asignado.
                                </small>
                            </div>

                            <div class="form-group" id="filtroAsignacionGroup">
                                <label for="filtroAsignacion">Filtrar titulares</label>
                                <select id="filtroAsignacion" name="filtro_asignacion" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="asignados">Solo con asesor asignado</option>
                                    <option value="sin_asesor">Solo sin asesor</option>
                                </select>
                            </div>

                            <div class="form-actions exporte-form-actions">
                                <button type="submit" class="btn btn-success" id="btnExportar">
                                    <i class="fas fa-download"></i> Descargar CSV
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Restablecer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Exportes rápidos</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-export-grid">
                            <button type="button" class="quick-export-item" onclick="exporteRapido('titulares_reparto', 'mes')">
                                <div class="quick-export-icon"><i class="fas fa-users"></i></div>
                                <div class="quick-export-content">
                                    <h4>Titulares del mes</h4>
                                    <p>Detalle reparto registrado este mes</p>
                                </div>
                            </button>
                            <button type="button" class="quick-export-item" onclick="exporteRapido('resumen_asignacion', 'mes')">
                                <div class="quick-export-icon"><i class="fas fa-user-friends"></i></div>
                                <div class="quick-export-content">
                                    <h4>Asignación del mes</h4>
                                    <p>Titulares por asesor (mes actual)</p>
                                </div>
                            </button>
                            <button type="button" class="quick-export-item" onclick="exporteRapido('tickets_equipo', 'mes')">
                                <div class="quick-export-icon"><i class="fas fa-ticket-alt"></i></div>
                                <div class="quick-export-content">
                                    <h4>Tickets del mes</h4>
                                    <p>Tiketera del equipo (mes actual)</p>
                                </div>
                            </button>
                            <button type="button" class="quick-export-item" onclick="exporteRapido('titulares_reparto', 'sin_asesor')">
                                <div class="quick-export-icon"><i class="fas fa-user-clock"></i></div>
                                <div class="quick-export-content">
                                    <h4>Sin asesor</h4>
                                    <p>Titulares pendientes de asignar (todo el histórico)</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const TIPO_REPORTE_AYUDA = {
            titulares_reparto: 'Incluye titular, propiedad, Reg_Int, BaseD, F_Correo, teléfonos, correos y asesor asignado.',
            resumen_asignacion: 'Cantidad de titulares por asesor (incluye fila «Sin asignar»).',
            tickets_equipo: 'Tickets de la tiketera de los asesores de su coordinación.',
            metricas_asesores: 'Requiere datos en metricas_asesores; filtra por fecha del reporte.'
        };

        let previewTimeout = null;

        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            document.getElementById('exportForm').addEventListener('submit', function(e) {
                e.preventDefault();
                generateExport();
            });
            ['fechaInicio', 'fechaFin', 'tipoReporte', 'filtroAsignacion'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', schedulePreview);
                }
            });
            toggleFiltroAsignacion();
            schedulePreview();
        });

        function initializeForm() {
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            document.getElementById('fechaInicio').value = formatDateYmd(firstDay);
            document.getElementById('fechaFin').value = formatDateYmd(today);
            updateTipoHelp();
        }

        function formatDateYmd(d) {
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        function updateTipoHelp() {
            var tipo = document.getElementById('tipoReporte').value;
            var help = document.getElementById('tipoReporteHelp');
            if (help) {
                help.textContent = TIPO_REPORTE_AYUDA[tipo] || '';
            }
            toggleFiltroAsignacion();
        }

        function toggleFiltroAsignacion() {
            var tipo = document.getElementById('tipoReporte').value;
            var grp = document.getElementById('filtroAsignacionGroup');
            if (grp) {
                grp.style.display = (tipo === 'titulares_reparto') ? '' : 'none';
            }
        }

        document.getElementById('tipoReporte').addEventListener('change', function() {
            updateTipoHelp();
            schedulePreview();
        });

        function schedulePreview() {
            clearTimeout(previewTimeout);
            previewTimeout = setTimeout(loadPreview, 350);
        }

        async function loadPreview() {
            var tipo = document.getElementById('tipoReporte').value;
            var params = new URLSearchParams({
                tipo_reporte: tipo,
                fecha_inicio: document.getElementById('fechaInicio').value,
                fecha_fin: document.getElementById('fechaFin').value,
                filtro_asignacion: document.getElementById('filtroAsignacion').value
            });
            var label = document.getElementById('exportePreviewLabel');
            var text = document.getElementById('exportePreviewText');
            if (text) {
                text.textContent = 'Calculando vista previa…';
            }
            try {
                var response = await fetch('api/coordinador_exporte_preview.php?' + params.toString(), {
                    credentials: 'include'
                });
                var result = await response.json();
                if (result.success) {
                    var n = result.total != null ? result.total : 0;
                    if (label) label.textContent = 'Listo para exportar';
                    if (text) {
                        text.textContent = n === 0
                            ? 'No hay registros con los filtros actuales. Ajuste fechas o tipo de reporte.'
                            : ('Se exportarán aproximadamente ' + n + ' registro(s).');
                    }
                } else {
                    if (label) label.textContent = 'Vista previa';
                    if (text) text.textContent = result.message || 'No se pudo calcular la vista previa.';
                }
            } catch (err) {
                if (label) label.textContent = 'Vista previa';
                if (text) text.textContent = 'Error al consultar la vista previa.';
            }
        }

        function buildExportParams() {
            return new URLSearchParams({
                fecha_inicio: document.getElementById('fechaInicio').value,
                fecha_fin: document.getElementById('fechaFin').value,
                tipo_reporte: document.getElementById('tipoReporte').value,
                filtro_asignacion: document.getElementById('filtroAsignacion').value
            });
        }

        async function generateExport() {
            var btn = document.getElementById('btnExportar');
            var tipo = document.getElementById('tipoReporte').value;
            var fi = document.getElementById('fechaInicio').value;
            var ff = document.getElementById('fechaFin').value;
            if (!fi || !ff) {
                showMessage('Indique fecha de inicio y fin.', 'error');
                return;
            }
            if (fi > ff) {
                showMessage('La fecha de inicio no puede ser posterior a la de fin.', 'error');
                return;
            }

            if (btn) {
                btn.disabled = true;
            }
            showMessage('Generando reporte…', 'info');

            try {
                var response = await fetch('api/export_metricas.php?' + buildExportParams().toString(), {
                    credentials: 'include'
                });

                var contentType = (response.headers.get('Content-Type') || '').toLowerCase();

                if (response.ok && contentType.indexOf('text/csv') !== -1) {
                    var blob = await response.blob();
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'reporte_' + tipo + '_' + fi + '_' + ff + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    showMessage('Reporte descargado correctamente.', 'success');
                    schedulePreview();
                } else {
                    var result = { message: 'Error al generar el reporte' };
                    try {
                        result = await response.json();
                    } catch (e) { /* respuesta no JSON */ }
                    showMessage(result.message || 'No hay datos para exportar o el servidor rechazó la solicitud.', 'error');
                }
            } catch (error) {
                console.error(error);
                showMessage('Error de conexión al generar el reporte.', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        function exporteRapido(tipo, modo) {
            var today = new Date();
            document.getElementById('tipoReporte').value = tipo;
            updateTipoHelp();

            if (modo === 'sin_asesor') {
                var inicioAmplio = new Date(2000, 0, 1);
                document.getElementById('fechaInicio').value = formatDateYmd(inicioAmplio);
                document.getElementById('fechaFin').value = formatDateYmd(today);
                document.getElementById('filtroAsignacion').value = 'sin_asesor';
            } else if (modo === 'mes') {
                document.getElementById('fechaInicio').value = formatDateYmd(new Date(today.getFullYear(), today.getMonth(), 1));
                document.getElementById('fechaFin').value = formatDateYmd(today);
                document.getElementById('filtroAsignacion').value = '';
            }

            schedulePreview();
            setTimeout(generateExport, 400);
        }

        function resetForm() {
            document.getElementById('exportForm').reset();
            initializeForm();
            document.getElementById('filtroAsignacion').value = '';
            schedulePreview();
        }

        function showMessage(message, type) {
            var contentArea = document.querySelector('.coordinador-exporte');
            if (!contentArea) return;
            var existing = contentArea.querySelector('.message.toast-exporte');
            if (existing) existing.remove();

            var messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + type + ' toast-exporte';
            var icon = 'info-circle';
            if (type === 'success') icon = 'check-circle';
            else if (type === 'error') icon = 'exclamation-triangle';
            messageDiv.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + escapeHtml(message) + '</span>';
            contentArea.insertBefore(messageDiv, contentArea.firstChild);
            setTimeout(function() { messageDiv.remove(); }, 6000);
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        async function cerrarSesion() {
            if (!confirm('¿Está seguro de cerrar sesión?')) return;
            try {
                var response = await fetch('api/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include'
                });
                var result = await response.json();
                if (result.success) {
                    window.appGoLogin();
                } else {
                    showMessage(result.message || 'Error al cerrar sesión', 'error');
                }
            } catch (error) {
                showMessage('Error cerrando sesión', 'error');
            }
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

        document.addEventListener('click', function(event) {
            var sidebar = document.querySelector('.sidebar');
            var menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768 && sidebar.classList.contains('open') &&
                !sidebar.contains(event.target) && menuToggle && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
