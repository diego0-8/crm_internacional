<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasRole('coordinador')) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar tickets CSV - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/role-specific.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/asesor.css" rel="stylesheet">
    <link href="../css/coordinador.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
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
                    <a href="coordinador_tickets_import.php" class="nav-item active">
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
                        <h4 class="text-asesor"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h4>
                        <p class="text-asesor">Coordinador</p>
                    </div>
                </div>

                <button class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header header-asesor">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1 class="title-asesor">Importar tickets (CSV)</h1>
                        <p class="subtitle-asesor">Carga masiva de tickets para clientes de su coordinación. Descargue la plantilla para ver el formato.</p>
                    </div>
                </div>
            </div>

            <div class="content-area coordinador-dashboard">
                <?php if ($message): ?>
                    <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Subir archivo</h2>
                        <div class="header-actions" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="../api/plantilla_tickets_csv.php" class="btn btn-secondary" download>
                                <i class="fas fa-file-download"></i> Plantilla simple
                            </a>
                            <a href="../api/plantilla_tickets_csv.php?tipo=foreclosure" class="btn btn-secondary" download>
                                <i class="fas fa-file-download"></i> Plantilla foreclosure
                            </a>
                        </div>
                    </div>
                    <div class="ticket-form" style="padding: 0;">
                        <p style="color: #666; margin-bottom: 10px;">
                            Se detecta automáticamente el tipo de archivo según las columnas y se admiten delimitadores <code>,</code> o <code>;</code>.
                        </p>
                        <details style="margin-bottom: 12px;">
                            <summary style="cursor:pointer; font-weight:600; color:#333;">Plantilla simple (delimitador <code>,</code>)</summary>
                            <p style="color:#666; margin-top:8px;">
                                Obligatorias: <code>cliente_cedula</code>, <code>titulo</code>.
                                Opcionales: <code>descripcion</code>,
                                <code>estado</code> (comunicacion|validacion|proceso_judicial|remate|recuperacion|cierre, default <em>comunicacion</em>),
                                <code>asesor_cedula</code>, <code>categoria_codigo</code> (GEN|SOP|FAC|COM),
                                y si crea clientes: <code>nombre_completo</code>, <code>email</code>, <code>telefono</code>.
                            </p>
                        </details>
                        <details style="margin-bottom: 14px;">
                            <summary style="cursor:pointer; font-weight:600; color:#333;">Plantilla foreclosure (delimitador <code>;</code>)</summary>
                            <p style="color:#666; margin-top:8px;">
                                Cada fila representa <strong>1 cliente + 1 predio + hasta 5 referencias</strong> y genera 1 ticket en estado <em>comunicación</em>.
                                Columnas clave: <code>Case Number</code> (preferido) o <code>Parcel Number</code> como identificador,
                                <code>First Name</code>, <code>Last Name</code>, <code>Property Street/City/State/ZIP Code</code>, <code>County</code>,
                                <code>Phone 1..5</code> + <code>Phone N: Type</code> + <code>Phone N: DNC/Litigator</code>,
                                <code>Email 1..5</code>, y bloques <code>RELATIVE 1..5</code> (con sus propios teléfonos y emails).
                                Si la fila no trae asesor, se usa el <em>Asesor por defecto</em> seleccionado abajo.
                            </p>
                        </details>
                        <form id="formImportTickets" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="archivo_csv">Archivo CSV</label>
                                <input type="file" id="archivo_csv" name="archivo_csv" class="form-control" accept=".csv" required>
                            </div>
                            <div class="form-group">
                                <label for="asesor_default">Asesor por defecto (opcional)</label>
                                <select id="asesor_default" name="asesor_default" class="form-control">
                                    <option value="">— Sin asesor por defecto —</option>
                                </select>
                                <small style="color:#888;">Se usará cuando una fila/cliente no tenga asesor asignado.</small>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="crear_clientes" name="crear_clientes" value="1">
                                    Crear cliente si no existe (solo plantilla simple). En foreclosure los clientes se crean siempre.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary" id="btnImport">
                                <i class="fas fa-upload"></i> Importar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Últimas importaciones</h2>
                        <button type="button" class="btn btn-secondary" onclick="cargarLotes()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Archivo</th>
                                    <th>Total</th>
                                    <th>OK</th>
                                    <th>Error</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Errores</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyLotes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            cargarLotes();
            cargarAsesores();
            document.getElementById('formImportTickets').addEventListener('submit', enviarImportacion);
        });

        async function cargarAsesores() {
            const sel = document.getElementById('asesor_default');
            try {
                const response = await fetch('../api/coordinador_asesores.php', { credentials: 'same-origin' });
                const result = await response.json();
                if (!result.success || !Array.isArray(result.data)) return;
                result.data.forEach(function (a) {
                    const opt = document.createElement('option');
                    opt.value = a.cedula;
                    const nombre = ((a.nombre || '') + ' ' + (a.apellido || '')).trim();
                    opt.textContent = nombre + ' (' + a.cedula + ')';
                    sel.appendChild(opt);
                });
            } catch (e) {
                console.warn('No se pudieron cargar los asesores', e);
            }
        }

        async function enviarImportacion(e) {
            e.preventDefault();
            const btn = document.getElementById('btnImport');
            const fd = new FormData();
            const fileInput = document.getElementById('archivo_csv');
            if (!fileInput.files.length) {
                alert('Seleccione un CSV');
                return;
            }
            fd.append('archivo_csv', fileInput.files[0]);
            if (document.getElementById('crear_clientes').checked) {
                fd.append('crear_clientes_si_faltan', '1');
            }
            const asesorDefault = document.getElementById('asesor_default').value;
            if (asesorDefault) {
                fd.append('asesor_default_cedula', asesorDefault);
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';
            try {
                const response = await fetch('../api/coordinador_import_tickets_csv.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message || 'Importación completada');
                    fileInput.value = '';
                    cargarLotes();
                } else {
                    alert(result.message || 'Error');
                }
            } catch (err) {
                console.error(err);
                alert('Error de red');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload"></i> Importar';
            }
        }

        async function cargarLotes() {
            const tbody = document.getElementById('tbodyLotes');
            tbody.innerHTML = '<tr><td colspan="8">Cargando...</td></tr>';
            try {
                const response = await fetch('../api/coordinador_ticket_imports.php?limite=30', { credentials: 'same-origin' });
                const result = await response.json();
                if (!result.success) {
                    tbody.innerHTML = '<tr><td colspan="8">Error</td></tr>';
                    return;
                }
                if (!result.data.length) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center">Sin importaciones</td></tr>';
                    return;
                }
                tbody.innerHTML = result.data.map(function(r) {
                    return '<tr>' +
                        '<td>' + r.id + '</td>' +
                        '<td>' + escapeHtml(r.nombre_archivo) + '</td>' +
                        '<td>' + r.total_filas + '</td>' +
                        '<td>' + r.filas_ok + '</td>' +
                        '<td>' + r.filas_error + '</td>' +
                        '<td>' + escapeHtml(r.estado) + '</td>' +
                        '<td>' + escapeHtml(r.created_at || '') + '</td>' +
                        '<td>' + (parseInt(r.filas_error, 10) > 0
                            ? '<button type="button" class="btn btn-sm btn-info" onclick="verErrores(' + r.id + ')">Ver</button>'
                            : '-') +
                        '</td></tr>';
                }).join('');
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="8">Error de red</td></tr>';
            }
        }

        async function verErrores(batchId) {
            try {
                const response = await fetch('../api/coordinador_ticket_import_errors.php?batch_id=' + batchId, { credentials: 'same-origin' });
                const result = await response.json();
                if (!result.success) {
                    alert(result.message);
                    return;
                }
                const lines = result.data.map(function(x) {
                    return 'Línea ' + x.numero_linea + ': ' + (x.mensaje_error || '');
                }).join('\n');
                alert(lines || 'Sin detalle');
            } catch (e) {
                alert('Error');
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        async function cerrarSesion() {
            if (!confirm('¿Cerrar sesión?')) return;
            try {
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = 'login.php';
                }
            } catch (e) {
                window.location.href = 'login.php';
            }
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>
