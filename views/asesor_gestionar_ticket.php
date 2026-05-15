<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!hasRole('asesor')) {
    header('Location: login.php');
    exit;
}

$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($ticketId <= 0) {
    header('Location: asesor_tickets.php');
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
    <title>Gestionar ticket #<?php echo $ticketId; ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/role-specific.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/asesor.css" rel="stylesheet">
    <link href="../css/tickets.css" rel="stylesheet">
    <link href="../css/softphone-web.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../img/logo2.png" alt="CRM Logo">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Asesor</div>
                    <a href="asesor_dashboard.php" class="nav-item">
                        <i class="fas fa-folder-open"></i>
                        Mis casos (reparto)
                    </a>
                    <a href="asesor_tickets.php" class="nav-item active">
                        <i class="fas fa-ticket-alt"></i>
                        Mis tickets CRM
                    </a>
                    <a href="asesor_estadisticas.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        Estadísticas
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h4>
                        <p>Asesor</p>
                    </div>
                </div>

                <button class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1>Gestionar ticket</h1>
                        <p id="ticketSubtitle">Cargando…</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="asesor_tickets.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                    </a>
                </div>
            </div>

            <div class="content-area">
                <?php if ($message): ?>
                    <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="ticket-gestion-layout">
                    <aside class="ticket-detalles-aside" aria-label="Detalles del caso">
                        <section class="detalle-grupo">
                            <h4><i class="fas fa-user"></i> Titular (tiketera)</h4>
                            <dl class="detalle-lista">
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Nombre</dt>
                                    <dd class="detalle-valor" id="detClienteNombre">—</dd>
                                </div>
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Edad</dt>
                                    <dd class="detalle-valor" id="detClienteEdad">—</dd>
                                </div>
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Estado vital</dt>
                                    <dd class="detalle-valor" id="detClienteVital">—</dd>
                                </div>
                                <div class="detalle-fila detalle-fila-multilinea">
                                    <dt class="detalle-label">Teléfonos</dt>
                                    <dd class="detalle-valor detalle-valor-flush">
                                        <ul class="detalle-lista-inline" id="detClienteTelefonosLista"></ul>
                                    </dd>
                                </div>
                                <div class="detalle-fila detalle-fila-multilinea">
                                    <dt class="detalle-label">Emails</dt>
                                    <dd class="detalle-valor detalle-valor-flush">
                                        <ul class="detalle-lista-emails" id="detClienteEmailsLista"></ul>
                                    </dd>
                                </div>
                                <div class="detalle-fila detalle-fila-multilinea">
                                    <dt class="detalle-label">Dirección postal (CRM)</dt>
                                    <dd class="detalle-valor" id="detClienteMailing">—</dd>
                                </div>
                            </dl>
                            <div id="titularRepartoMailingBlock" class="titular-reparto-mailing" style="display: none;" aria-label="Mailing tabla titulares">
                                <h5 class="detalle-subtitulo-reparto"><i class="fas fa-envelope"></i> Mailing (tabla titulares)</h5>
                                <dl class="detalle-lista">
                                    <div class="detalle-fila">
                                        <dt class="detalle-label">mailing_calle</dt>
                                        <dd class="detalle-valor" id="detTitMailingCalle">—</dd>
                                    </div>
                                    <div class="detalle-fila">
                                        <dt class="detalle-label">mailing_ciudad</dt>
                                        <dd class="detalle-valor" id="detTitMailingCiudad">—</dd>
                                    </div>
                                    <div class="detalle-fila">
                                        <dt class="detalle-label">mailing_estado</dt>
                                        <dd class="detalle-valor" id="detTitMailingEstado">—</dd>
                                    </div>
                                    <div class="detalle-fila">
                                        <dt class="detalle-label">mailing_codigo_postal</dt>
                                        <dd class="detalle-valor" id="detTitMailingCp">—</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="detalle-acciones-cliente">
                                <button type="button" class="btn btn-secondary btn-compact" id="btnAbrirReferencias">
                                    <i class="fas fa-address-book"></i> Referencias
                                </button>
                            </div>
                        </section>

                        <section class="detalle-grupo">
                            <h4><i class="fas fa-map-marked-alt"></i> Caso (predio)</h4>
                            <div id="detPredioVacio" class="detalle-vacio" style="display: none;">
                                <i class="fas fa-info-circle"></i> Sin predio asociado a este ticket.
                            </div>
                            <dl class="detalle-lista" id="detPredioLista">
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Case Number</dt>
                                    <dd class="detalle-valor" id="detPredioCase">—</dd>
                                </div>
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Parcel Number</dt>
                                    <dd class="detalle-valor" id="detPredioParcel">—</dd>
                                </div>
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Tipo de foreclosure</dt>
                                    <dd class="detalle-valor" id="detPredioTipo">—</dd>
                                </div>
                                <div class="detalle-fila detalle-fila-multilinea">
                                    <dt class="detalle-label">Dirección de la propiedad</dt>
                                    <dd class="detalle-valor" id="detPredioDireccion">—</dd>
                                </div>
                                <div class="detalle-fila">
                                    <dt class="detalle-label">County</dt>
                                    <dd class="detalle-valor" id="detPredioCounty">—</dd>
                                </div>
                                <div class="detalle-fila">
                                    <dt class="detalle-label">Source</dt>
                                    <dd class="detalle-valor" id="detPredioSource">—</dd>
                                </div>
                                <div class="detalle-fila" id="detDiasMoraFila" style="display: none;">
                                    <dt class="detalle-label">Días en mora</dt>
                                    <dd class="detalle-valor">
                                        <strong id="detDiasMoraTexto">—</strong>
                                        <span class="detalle-mora-meta" id="detDiasMoraMeta"></span>
                                        <div class="mora-meter-bar" role="progressbar" aria-valuemin="0" aria-valuemax="300" aria-valuenow="0" aria-labelledby="detDiasMoraTexto">
                                            <span id="detDiasMoraFill" class="mora-meter-fill"></span>
                                        </div>
                                    </dd>
                                </div>
                                <div class="detalle-fila" id="detImportanciaFila" style="display: none;">
                                    <dt class="detalle-label">Importancia del ticket</dt>
                                    <dd class="detalle-valor">
                                        <div class="importancia-ticket">
                                            <div class="importancia-ticket-cabecera">
                                                <span class="importancia-num" id="detImportanciaNum" aria-label="Nivel de importancia">—</span>
                                                <span class="importancia-leyenda" id="detImportanciaLeyenda"></span>
                                            </div>
                                            <p class="importancia-hint">1 = máxima prioridad · 10 = mínima prioridad</p>
                                            <div class="importancia-escala" id="detImportanciaEscala" role="img" aria-label="Escala de importancia del 1 al 10"></div>
                                        </div>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="detalle-grupo" id="detValoresGrupo">
                            <h4><i class="fas fa-dollar-sign"></i> Valores y subasta</h4>
                            <ul class="detalle-montos">
                                <li class="detalle-monto subasta">
                                    <span class="detalle-monto-label">Valor de puja apertura</span>
                                    <span class="detalle-monto-valor" id="detValorInicial">—</span>
                                    <small class="detalle-monto-hint">Valor inicial de la subasta</small>
                                </li>
                                <li class="detalle-monto vendido">
                                    <span class="detalle-monto-label">Valor puja de cierre</span>
                                    <span class="detalle-monto-valor" id="detValorVendido">—</span>
                                    <small class="detalle-monto-hint">Valor vendido del predio</small>
                                </li>
                                <li class="detalle-monto devolver">
                                    <span class="detalle-monto-label">Excedente</span>
                                    <span class="detalle-monto-valor" id="detValorDevolver">—</span>
                                    <small class="detalle-monto-hint">Valor a devolver al titular</small>
                                </li>
                                <li class="detalle-monto fecha">
                                    <span class="detalle-monto-label">Fecha de venta</span>
                                    <span class="detalle-monto-valor" id="detDateSold">—</span>
                                    <small class="detalle-monto-hint">Fecha en que se vendió/subastó</small>
                                </li>
                            </ul>
                        </section>
                    </aside>

                    <div class="ticket-gestion-main">
                        <div class="ticket-gestion-content">
                    <header class="ticket-pro-header" id="ticketProHeader">
                        <div class="ticket-pro-header-left">
                            <div class="ticket-pro-numero" id="ticketProNumero">TK-----</div>
                            <div class="ticket-pro-meta" id="ticketProMeta">Cargando…</div>
                        </div>
                        <div class="ticket-pro-header-right">
                            <span class="ticket-estado-badge" id="ticketEstadoBadge">—</span>
                            <span class="ticket-estado-desde" id="ticketEstadoDesde"></span>
                            <span class="ticket-tiempo-total" id="ticketTiempoTotal"></span>
                        </div>
                    </header>

                    <form id="gestionTicketForm" onsubmit="guardarGestionTicket(event)">
                        <input type="hidden" id="gestionTicketId" name="ticket_id" value="<?php echo $ticketId; ?>">

                        <div class="form-section">
                            <h4><i class="fas fa-route"></i> Estado del caso</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="gestionEstado">Cambiar estado</label>
                                    <select id="gestionEstado" name="estado" class="form-control">
                                        <option value="comunicacion">Comunicación</option>
                                        <option value="validacion">Validación</option>
                                        <option value="proceso_judicial">Proceso judicial</option>
                                        <option value="remate">Remate</option>
                                        <option value="recuperacion">Recuperación</option>
                                        <option value="cierre">Cierre</option>
                                    </select>
                                    <small class="form-text" id="gestionEstadoHelp">
                                        <i class="fas fa-info-circle"></i>
                                        Solo se muestran activos los estados a los que puede avanzar desde la etapa actual.
                                    </small>
                                </div>
                            </div>


                        </div>

                        <div class="form-section">
                            <h4><i class="fas fa-stream"></i> Línea de tiempo del caso</h4>
                            <div class="ticket-timeline-estado-actual" id="ticketTimelineEstadoActual" aria-live="polite">
                                <div class="ticket-timeline-estado-actual-label">Estado actual del ticket</div>
                                <span class="ticket-estado-badge" id="ticketTimelineEstadoBadge" role="status">—</span>
                                <p class="ticket-timeline-estado-actual-hint">Coincide con el estado en <strong>tiketera</strong> y con el selector «Cambiar estado» cuando no hay cambios pendientes.</p>
                            </div>
                            <ol class="ticket-timeline" id="ticketTimeline" aria-live="polite">
                                <li class="timeline-empty">Cargando línea de tiempo…</li>
                            </ol>
                        </div>

                        <div class="form-section">
                            <h4><i class="fas fa-file-pdf"></i> Gestión de Archivos PDF</h4>
                            <div id="pdf-management">
                                <div id="archivosExistentes" class="archivos-existentes"></div>

                                <div class="new-pdf-upload">
                                    <label for="nuevoPdfArchivo">Agregar Nuevo PDF</label>
                                    <input type="file" id="nuevoPdfArchivo" name="nuevo_pdf" class="form-control"
                                           accept=".pdf" onchange="previewNuevoPDF(this)">
                                    <small class="form-text">
                                        <i class="fas fa-info-circle"></i>
                                        Se agregará a los archivos existentes. Máximo 50MB
                                    </small>
                                    <div id="nuevo-pdf-preview" class="pdf-preview" style="display: none;">
                                        <div class="pdf-preview-content">
                                            <i class="fas fa-file-pdf"></i>
                                            <span id="nuevo-pdf-filename"></span>
                                            <button type="button" class="btn-remove-pdf" onclick="removeNuevoPDF()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4><i class="fas fa-sticky-note"></i> Notas del Asesor</h4>
                            <div class="form-group">
                                <label for="nuevaNota">Agregar Nueva Nota</label>
                                <textarea id="nuevaNota" name="nueva_nota" class="form-control" rows="3"
                                          placeholder="Agregue una nota sobre las acciones tomadas..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="proximaAccion">Próxima Acción</label>
                                <input type="text" id="proximaAccion" name="proxima_accion" class="form-control"
                                       placeholder="¿Qué hará a continuación?">
                            </div>

                            <div class="form-group">
                                <label for="fechaProximaAccion">Fecha de Próxima Acción</label>
                                <input type="datetime-local" id="fechaProximaAccion" name="fecha_proxima_accion" class="form-control">
                            </div>
                        </div>

                        <div class="form-section">
                            <h4><i class="fas fa-history"></i> Historial de Notas</h4>
                            <div id="historialNotas" class="historial-notas"></div>
                        </div>

                        <div class="ticket-form-actions">
                            <a href="asesor_tickets.php" class="btn btn-secondary btn-compact">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary btn-compact">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                        </div>
                    </div>
                    <aside class="columna-softphone ticket-softphone-aside" aria-label="Softphone WebRTC">
                        <div class="softphone-container">
                            <div id="softphoneInactiveBanner" class="softphone-inactive-banner" style="display: none;" role="status"></div>
                            <div id="webrtc-softphone"></div>
                        </div>
                        <p class="softphone-client-line" id="softphoneClienteLine"></p>
                        
                        <div class="monetizacion-panel" style="margin-top: 20px; background: #fff; border-radius: 8px; padding: 16px; border: 1px solid var(--border-light); text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <h4 style="color: var(--primary-color); font-size: 14px; margin-bottom: 8px; font-weight: 600;"><i class="fas fa-money-bill-wave"></i> Monetización Esperada</h4>
                            <div style="font-size: 24px; font-weight: 700; color: #10b981;" id="detMonetizacion">—</div>
                            <p style="font-size: 12px; color: #64748b; margin-top: 4px;">Incentivo por cerrar el ticket</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <div id="modalReferencias" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalReferenciasTitulo" aria-hidden="true" style="display: none;">
        <div class="modal-content modal-referencias-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalReferenciasTitulo">Referencias personales</h3>
                <button type="button" class="close" onclick="cerrarModalReferencias()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body" id="referenciasModalBody">
                <p class="text-muted">Cargando…</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalReferencias()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const TICKET_ID = <?php echo $ticketId; ?>;

        var __gestionarSoftphoneInitDone = false;

        async function initSoftphoneSidebar(ticket) {
            if (__gestionarSoftphoneInitDone) return;
            __gestionarSoftphoneInitDone = true;

            var line = document.getElementById('softphoneClienteLine');
            var banner = document.getElementById('softphoneInactiveBanner');
            var mount = document.getElementById('webrtc-softphone');
            if (!line || !banner || !mount) return;

            var tel = '';
            if (ticket && ticket.cliente_telefono) {
                tel = String(ticket.cliente_telefono);
            } else if (ticket && ticket.cliente_telefonos && ticket.cliente_telefonos.length > 0 &&
                ticket.cliente_telefonos[0].numero) {
                tel = String(ticket.cliente_telefonos[0].numero);
            }
            var nombre = (ticket && ticket.cliente_nombre) ? String(ticket.cliente_nombre) : '';
            line.textContent = tel ? (nombre + ' · Tel. ' + tel) : (nombre ? nombre + ' · Sin teléfono en ficha' : 'Cliente sin datos');

            try {
                var res = await fetch('../api/softphone_config.php', { credentials: 'same-origin' }).then(function(r) {
                    return r.json();
                });
                if (!res.success || !res.enabled) {
                    banner.style.display = 'block';
                    banner.textContent = res.message || 'Softphone no configurado para su usuario.';
                    mount.style.display = 'none';
                    return;
                }

                banner.style.display = 'none';
                mount.style.display = 'block';

                if (!window.SIP || !window.SIP.UserAgent || !window.SIP.Web) {
                    var mod = await import('https://esm.sh/sip.js@0.21.2');
                    // Exponer un subset completo requerido por softphone-web.js
                    // (Registerer/RegistererState/Inviter, etc.)
                    window.SIP = {
                        ...(window.SIP || {}),
                        UserAgent: mod.UserAgent,
                        Web: mod.Web,
                        Registerer: mod.Registerer,
                        RegistererState: mod.RegistererState,
                        Inviter: mod.Inviter,
                        Invitation: mod.Invitation,
                        SessionState: mod.SessionState
                    };
                }

                if (!window.WebRTCSoftphone) {
                    await new Promise(function(resolve, reject) {
                        var s = document.createElement('script');
                        s.src = '../assets/js/softphone-web.js';
                        s.async = true;
                        s.onload = function() { resolve(); };
                        s.onerror = function() { reject(new Error('No se pudo cargar softphone-web.js')); };
                        document.body.appendChild(s);
                    });
                }

                if (typeof window.WebRTCSoftphone !== 'function') {
                    throw new Error('WebRTCSoftphone no está disponible');
                }

                if (window.webrtcSoftphone) {
                    var digitsOnly = tel.replace(/\D/g, '');
                    var nd0 = document.getElementById('number-display');
                    if (nd0 && digitsOnly) nd0.value = digitsOnly;
                    return;
                }

                new WebRTCSoftphone(res.config);

                var digits = tel.replace(/\D/g, '');
                var nd = document.getElementById('number-display');
                if (nd && digits) nd.value = digits;

                window.__callLogContext = { cliente_id: 0, telefono_contacto: digits.slice(-12) };
                if (window.webrtcSoftphone && typeof window.webrtcSoftphone.setCallContext === 'function') {
                    window.webrtcSoftphone.setCallContext(window.__callLogContext);
                }
            } catch (e) {
                console.error(e);
                banner.style.display = 'block';
                banner.textContent = 'Softphone: ' + (e.message || String(e));
                mount.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const response = await fetch('../api/ticket_detalle_completo.php?ticket_id=' + TICKET_ID, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (!result.success || !result.data) {
                    showMessage(result.message || 'No se pudo cargar el ticket', 'error');
                    document.getElementById('ticketSubtitle').textContent = 'Error al cargar el ticket';
                    setTimeout(function() {
                        window.location.href = 'asesor_tickets.php';
                    }, 2000);
                    return;
                }

                const ticket = result.data;
                renderHeaderPro(ticket);
                renderDetallesCaso(ticket);
                renderEstadoControls(ticket);
                renderTimeline(ticket.historial_estado || [], ticket.estado || '');

                var refLabel = ticket.numero_ticket ? ticket.numero_ticket : ('#' + ticket.id);
                var subParts = [refLabel, ticket.cliente_nombre || ''].filter(Boolean);
                if (ticket.categoria_nombre) {
                    subParts.push(ticket.categoria_nombre);
                }
                document.getElementById('ticketSubtitle').textContent = subParts.join(' · ');



                await cargarHistorialNotas(TICKET_ID);
                await cargarArchivosExistentes(TICKET_ID);

                window.__ticketActual = ticket;
                var btnRef = document.getElementById('btnAbrirReferencias');
                if (btnRef) {
                    btnRef.addEventListener('click', function() {
                        abrirModalReferencias();
                    });
                }
                var modalRef = document.getElementById('modalReferencias');
                if (modalRef) {
                    modalRef.addEventListener('click', function(ev) {
                        if (ev.target === modalRef) cerrarModalReferencias();
                    });
                }
                document.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Escape') cerrarModalReferencias();
                });

                await initSoftphoneSidebar(ticket);
            } catch (e) {
                console.error(e);
                showMessage('Error de red al cargar el ticket', 'error');
                setTimeout(function() {
                    window.location.href = 'asesor_tickets.php';
                }, 2000);
            }
        });

        async function cargarHistorialNotas(ticketId) {
            try {
                const response = await fetch('../api/ticket_notas.php?ticket_id=' + ticketId, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                const historialContainer = document.getElementById('historialNotas');

                if (result.success && result.data.length > 0) {
                    historialContainer.innerHTML = result.data.map(function(nota) {
                        var estKey = (nota.estado_ticket || '').trim();
                        var estLabel = (nota.estado_label || estKey || '').trim();
                        var estadoHtml = '';
                        if (estKey && estLabel) {
                            estadoHtml = '<div class="nota-estado-line"><span class="ticket-estado-badge estado-' +
                                estKey.replace(/[^a-z0-9_]/gi, '') + '">' +
                                '<i class="fas fa-route" aria-hidden="true"></i> ' + escapeHtml(estLabel) +
                                '</span></div>';
                        }
                        return '<div class="nota-item">' +
                            '<div class="nota-header">' +
                            '<span class="nota-fecha">' + new Date(nota.fecha_creacion).toLocaleString() + '</span>' +
                            '<span class="nota-asesor">' + escapeHtml(nota.asesor_nombre || '') + '</span>' +
                            '</div>' +
                            estadoHtml +
                            '<div class="nota-contenido">' + escapeHtml(nota.contenido || '') + '</div>' +
                            (nota.proxima_accion ? '<div class="nota-accion"><strong>Próxima acción:</strong> ' +
                                escapeHtml(nota.proxima_accion) + '</div>' : '') +
                            '</div>';
                    }).join('');
                } else {
                    historialContainer.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 20px;">No hay notas registradas</p>';
                }
            } catch (error) {
                console.error('Error cargando historial de notas:', error);
                document.getElementById('historialNotas').innerHTML =
                    '<p style="color: #ef4444; text-align: center; padding: 20px;">Error cargando historial</p>';
            }
        }

        async function cargarArchivosExistentes(ticketId) {
            try {
                const response = await fetch('../api/ticket_archivos.php?ticket_id=' + ticketId, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                const archivosContainer = document.getElementById('archivosExistentes');

                if (result.success && result.data.length > 0) {
                    archivosContainer.innerHTML =
                        '<h5>Archivos Existentes (' + result.data.length + ')</h5>' +
                        '<div class="archivos-lista">' +
                        result.data.map(function(archivo) {
                            return '<div class="archivo-item-gestion">' +
                                '<div class="archivo-info">' +
                                '<i class="fas fa-file-pdf"></i>' +
                                '<div class="archivo-details">' +
                                '<span class="archivo-nombre">' + escapeHtml(archivo.nombre_archivo) + '</span>' +
                                '<span class="archivo-fecha">' + new Date(archivo.fecha_subida).toLocaleString() + '</span>' +
                                '</div></div>' +
                                '<div class="archivo-actions">' +
                                '<a href="../' + archivo.ruta_archivo + '" target="_blank" class="btn btn-sm btn-primary">' +
                                '<i class="fas fa-eye"></i> Ver</a>' +
                                '<button type="button" class="btn btn-sm btn-danger" onclick="eliminarArchivoGestion(' +
                                archivo.id + ')"><i class="fas fa-trash"></i> Eliminar</button>' +
                                '</div></div>';
                        }).join('') +
                        '</div>';
                } else {
                    archivosContainer.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 20px;">No hay archivos PDF</p>';
                }
            } catch (error) {
                console.error('Error cargando archivos existentes:', error);
                document.getElementById('archivosExistentes').innerHTML =
                    '<p style="color: #ef4444; text-align: center; padding: 20px;">Error cargando archivos</p>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function eliminarArchivoGestion(archivoId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('archivo_id', archivoId);

                const response = await fetch('../api/eliminar_archivo_ticket.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Archivo eliminado exitosamente', 'success');
                    cargarArchivosExistentes(TICKET_ID);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error eliminando archivo:', error);
                showMessage('Error eliminando archivo', 'error');
            }
        }

        const ESTADO_LABELS = {
            'comunicacion':     'Comunicación',
            'validacion':       'Validación',
            'proceso_judicial': 'Proceso judicial',
            'remate':           'Remate',
            'recuperacion':     'Recuperación',
            'cierre':           'Cierre'
        };

        function renderHeaderPro(ticket) {
            var num = ticket.numero_ticket || ('#' + ticket.id);
            document.getElementById('ticketProNumero').textContent = num;

            var meta = [];
            if (ticket.cliente_nombre)   meta.push(escapeHtml(ticket.cliente_nombre));
            var telMeta = ticket.cliente_telefono;
            if (!telMeta && ticket.cliente_telefonos && ticket.cliente_telefonos.length > 0) {
                telMeta = ticket.cliente_telefonos[0].numero;
            }
            if (telMeta) meta.push('Tel. ' + escapeHtml(String(telMeta)));
            if (ticket.categoria_nombre) meta.push(escapeHtml(ticket.categoria_nombre));
            if (ticket.fecha_creacion)   meta.push('Abierto: ' + new Date(ticket.fecha_creacion).toLocaleString());
            document.getElementById('ticketProMeta').innerHTML = meta.join(' · ');

            var estado = ticket.estado || '';
            var badge = document.getElementById('ticketEstadoBadge');
            badge.className = 'ticket-estado-badge estado-' + estado;
            badge.innerHTML = '<i class="fas fa-route"></i> ' + escapeHtml(ticket.estado_label || ESTADO_LABELS[estado] || estado);

            var desde = document.getElementById('ticketEstadoDesde');
            if (ticket.estado_actual_desde) {
                desde.textContent = 'Desde ' + new Date(ticket.estado_actual_desde).toLocaleString();
            } else {
                desde.textContent = '';
            }

            var total = document.getElementById('ticketTiempoTotal');
            if (ticket.tiempo_total_legible) {
                total.textContent = 'Tiempo total: ' + ticket.tiempo_total_legible;
            } else {
                total.textContent = '';
            }
        }

        function formatUsd(n) {
            if (n === null || n === undefined || n === '' || isNaN(Number(n))) return '—';
            try {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(Number(n));
            } catch (_) {
                return '$' + Number(n).toFixed(2);
            }
        }

        function formatDateOnly(s) {
            if (!s) return '—';
            // Soporta 'YYYY-MM-DD' y 'YYYY-MM-DD HH:MM:SS'.
            var d = new Date(String(s).replace(' ', 'T'));
            if (isNaN(d.getTime())) return '—';
            return d.toLocaleDateString();
        }

        function setText(id, value) {
            var el = document.getElementById(id);
            if (!el) return;
            var hasValue = value !== null && value !== undefined && String(value).trim() !== '' && value !== '—';
            el.textContent = hasValue ? String(value) : '—';
            el.classList.toggle('vacio', !hasValue);
        }

        function tipoTelefonoLabel(tipo) {
            var map = { landline: 'Landline', wireless: 'Wireless', voip: 'VoIP', other: 'Otro' };
            return map[String(tipo || '').toLowerCase()] || (tipo ? String(tipo) : '—');
        }

        function renderClienteTelefonosYEmails(ticket) {
            var ulTel = document.getElementById('detClienteTelefonosLista');
            var ulMail = document.getElementById('detClienteEmailsLista');
            if (ulTel) ulTel.innerHTML = '';
            if (ulMail) ulMail.innerHTML = '';

            var phones = ticket.cliente_telefonos || [];
            if ((!phones || phones.length === 0) && ticket.cliente_telefono) {
                phones = [{ numero: ticket.cliente_telefono, tipo: 'other', dnc_litigator: null, orden: 1 }];
            }

            if (ulTel) {
                if (!phones || phones.length === 0) {
                    ulTel.innerHTML = '<li class="detalle-sublinha vacio">Sin teléfonos registrados</li>';
                } else {
                    phones.forEach(function(p) {
                        var orden = parseInt(p.orden, 10) || 1;
                        var tipoLbl = tipoTelefonoLabel(p.tipo);
                        var dnc = p.dnc_litigator === 'Y' ? 'Y' : (p.dnc_litigator === 'N' ? 'N' : '—');
                        var li = document.createElement('li');
                        li.className = 'detalle-phone-row';
                        li.innerHTML =
                            '<span class="det-phone-slot">Phone ' + orden + '</span>' +
                            '<span class="det-phone-num">' + escapeHtml(String(p.numero || '').trim()) + '</span>' +
                            '<span class="det-phone-meta">' +
                            '<span class="det-phone-meta-label">Phone ' + orden + ': Type</span> · ' + escapeHtml(tipoLbl) +
                            ' · <span class="det-phone-meta-label">Phone ' + orden + ': DNC/Litigator</span> · ' + escapeHtml(dnc) +
                            '</span>';
                        ulTel.appendChild(li);
                    });
                }
            }

            var mails = ticket.cliente_emails_list || [];
            if (ulMail) {
                if ((!mails || mails.length === 0) && ticket.cliente_email) {
                    mails = [{ email: ticket.cliente_email, orden: 1 }];
                }
                if (!mails || mails.length === 0) {
                    ulMail.innerHTML = '<li class="detalle-sublinha vacio">Sin emails registrados</li>';
                } else {
                    mails.forEach(function(m) {
                        var orden = parseInt(m.orden, 10) || 1;
                        var li = document.createElement('li');
                        li.className = 'detalle-email-row';
                        li.innerHTML =
                            '<span class="det-email-slot">Email ' + orden + '</span>' +
                            '<span class="det-email-val">' + escapeHtml(String(m.email || '').trim()) + '</span>';
                        ulMail.appendChild(li);
                    });
                }
            }
        }

        function buildReferenciasHtml(ticket) {
            var refs = ticket.referencias_personales || [];
            if (!refs.length) {
                return '<p class="modal-ref-empty">No hay referencias registradas para este cliente.</p>';
            }
            var html = '';
            refs.forEach(function(ref, idx) {
                var orden = parseInt(ref.orden, 10) || (idx + 1);
                var nombre = [ref.nombre, ref.apellido].filter(Boolean).join(' ').trim() || 'Sin nombre';
                html += '<article class="modal-ref-card">';
                html += '<h4 class="modal-ref-card-title">RELATIVE ' + orden + ' · ' + escapeHtml(nombre) + '</h4>';
                var tieneTipo = ref.possible_type && String(ref.possible_type).trim() !== '';
                var tieneEdad = ref.age !== null && ref.age !== undefined && String(ref.age).trim() !== '';
                if (tieneTipo || tieneEdad) {
                    html += '<p class="modal-ref-meta">';
                    if (tieneTipo) html += '<strong>Tipo:</strong> ' + escapeHtml(String(ref.possible_type));
                    if (tieneTipo && tieneEdad) html += ' · ';
                    if (tieneEdad) html += '<strong>Edad:</strong> ' + escapeHtml(String(ref.age));
                    html += '</p>';
                }

                var tels = ref.telefonos || [];
                if (!tels.length) {
                    html += '<p class="modal-ref-subtle">Sin teléfonos</p>';
                } else {
                    tels.forEach(function(p) {
                        var k = parseInt(p.orden, 10) || 1;
                        var tipoLbl = tipoTelefonoLabel(p.tipo);
                        var dnc = p.dnc_litigator === 'Y' ? 'Y' : (p.dnc_litigator === 'N' ? 'N' : '—');
                        html += '<div class="modal-ref-phone-group">';
                        html += '<div class="ref-csv-row"><span class="ref-csv-k">Phone ' + k + '</span><span class="ref-csv-v">' +
                            escapeHtml(String(p.numero || '').trim()) + '</span></div>';
                        html += '<div class="ref-csv-row"><span class="ref-csv-k">Phone ' + k + ': Type</span><span class="ref-csv-v">' +
                            escapeHtml(tipoLbl) + '</span></div>';
                        html += '<div class="ref-csv-row"><span class="ref-csv-k">Phone ' + k + ': DNC/Litigator</span><span class="ref-csv-v">' +
                            escapeHtml(dnc) + '</span></div>';
                        html += '</div>';
                    });
                }

                var mails = ref.emails || [];
                if (mails.length) {
                    html += '<div class="modal-ref-emails"><strong>Correos</strong>';
                    mails.forEach(function(em) {
                        var ek = parseInt(em.orden, 10) || 1;
                        html += '<div class="ref-csv-row"><span class="ref-csv-k">Email ' + ek + '</span><span class="ref-csv-v">' +
                            escapeHtml(String(em.email || '').trim()) + '</span></div>';
                    });
                    html += '</div>';
                }

                html += '</article>';
            });
            return html;
        }

        function abrirModalReferencias() {
            var modal = document.getElementById('modalReferencias');
            var body = document.getElementById('referenciasModalBody');
            if (!modal || !body) return;
            body.innerHTML = buildReferenciasHtml(window.__ticketActual || {});
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
        }

        function cerrarModalReferencias() {
            var modal = document.getElementById('modalReferencias');
            if (!modal) return;
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }

        function renderDetallesCaso(ticket) {
            // Cliente
            setText('detClienteNombre', ticket.cliente_nombre);

            var edad = ticket.cliente_age;
            setText('detClienteEdad', (edad !== null && edad !== undefined && edad !== '') ? (edad + ' años') : null);

            var deceased = ticket.cliente_deceased;
            var vital = '';
            if (deceased === 'Y') vital = 'Fallecido';
            else if (deceased === 'N') vital = 'Vivo';
            setText('detClienteVital', vital);

            renderClienteTelefonosYEmails(ticket);

            var mail = [
                ticket.cliente_mailing_street,
                ticket.cliente_mailing_city,
                ticket.cliente_mailing_state,
                ticket.cliente_mailing_zip
            ].filter(function(x) { return x && String(x).trim() !== ''; }).join(', ');
            setText('detClienteMailing', mail);

            var titRep = ticket.titular_reparto || null;
            var blkTit = document.getElementById('titularRepartoMailingBlock');
            if (blkTit) {
                if (titRep) {
                    blkTit.style.display = '';
                    setText('detTitMailingCalle', titRep.mailing_calle);
                    setText('detTitMailingCiudad', titRep.mailing_ciudad);
                    setText('detTitMailingEstado', titRep.mailing_estado);
                    setText('detTitMailingCp', titRep.mailing_codigo_postal);
                } else {
                    blkTit.style.display = 'none';
                }
            }

            // Predio (tabla predios) y/o reparto (propiedades + mora)
            var predio = ticket.predio || null;
            var pr = ticket.propiedad_reparto || null;
            var tieneCasoPredio = !!(predio) || !!(pr);
            var vacio = document.getElementById('detPredioVacio');
            var lista = document.getElementById('detPredioLista');
            var grupoValores = document.getElementById('detValoresGrupo');

            if (!tieneCasoPredio) {
                if (vacio) vacio.style.display = 'block';
                if (lista) lista.style.display = 'none';
                if (grupoValores) grupoValores.style.display = 'none';
                renderImportanciaTicket(null);
            } else {
                if (vacio) vacio.style.display = 'none';
                if (lista) lista.style.display = '';
                if (grupoValores) grupoValores.style.display = '';

                if (predio) {
                    setText('detPredioCase', predio.case_number);
                    setText('detPredioParcel', predio.parcel_number);
                    setText('detPredioTipo', predio.type_of_foreclosure);

                    var dirPredio = [
                        predio.property_street,
                        predio.property_city,
                        predio.property_state,
                        predio.property_zip
                    ].filter(function(x) { return x && String(x).trim() !== ''; }).join(', ');
                    setText('detPredioDireccion', dirPredio);

                    setText('detPredioCounty', predio.county);
                    setText('detPredioSource', predio.predio_source);

                    setText('detValorInicial',  formatUsd(predio.valor_inicial_subasta));
                    setText('detValorVendido',  formatUsd(predio.valor_vendido));
                    setText('detValorDevolver', formatUsd(predio.valor_a_devolver));
                    setText('detDateSold',      formatDateOnly(predio.date_sold));

                    var formatCop = function(val) {
                        if (val === null || val === undefined || val === '') return '—';
                        var num = parseFloat(val);
                        if (isNaN(num)) return val;
                        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(num);
                    };

                    setText('detMonetizacion',  predio.monetizacion ? formatCop(predio.monetizacion) : '—');
                } else if (pr) {
                    setText('detPredioCase', pr.numero_caso);
                    setText('detPredioParcel', pr.numero_parcela);
                    setText('detPredioTipo', pr.tipo_foreclosure);
                    var dirPr = [
                        pr.propiedad_calle,
                        pr.propiedad_ciudad,
                        pr.propiedad_estado,
                        pr.propiedad_codigo_postal
                    ].filter(function(x) { return x && String(x).trim() !== ''; }).join(', ');
                    setText('detPredioDireccion', dirPr);
                    setText('detPredioCounty', pr.condado);
                    setText('detPredioSource', pr.fuente);
                    if (grupoValores) grupoValores.style.display = 'none';
                }

                var moraFila = document.getElementById('detDiasMoraFila');
                if (pr && pr.dias_mora_activos != null && pr.dias_mora_activos !== '') {
                    if (moraFila) moraFila.style.display = '';
                    var lim = pr.dias_mora_limite || 300;
                    var act = Math.min(lim, Math.max(0, parseInt(String(pr.dias_mora_activos), 10) || 0));
                    setText('detDiasMoraTexto', act + ' / ' + lim + ' días');
                    var meta = document.getElementById('detDiasMoraMeta');
                    if (meta) {
                        meta.textContent = pr.en_limite ? ' (límite alcanzado)' : '';
                    }
                    var fill = document.getElementById('detDiasMoraFill');
                    if (fill) {
                        fill.style.width = Math.min(100, (act / lim) * 100) + '%';
                    }
                    var bar = moraFila ? moraFila.querySelector('.mora-meter-bar') : null;
                    if (bar) {
                        bar.setAttribute('aria-valuenow', String(act));
                        bar.setAttribute('aria-valuemax', String(lim));
                    }
                } else {
                    if (moraFila) moraFila.style.display = 'none';
                }

                renderImportanciaTicket(titRep ? titRep.prioridad : null);
            }
        }

        function parsePrioridadImportancia(valor) {
            if (valor === null || valor === undefined || String(valor).trim() === '') {
                return null;
            }
            var m = String(valor).trim().match(/\d+/);
            if (!m) return null;
            var n = parseInt(m[0], 10);
            if (isNaN(n)) return null;
            if (n < 1) return 1;
            if (n > 10) return 10;
            return n;
        }

        function renderImportanciaTicket(prioridadRaw) {
            var fila = document.getElementById('detImportanciaFila');
            if (!fila) return;
            var nivel = parsePrioridadImportancia(prioridadRaw);
            if (nivel === null) {
                fila.style.display = 'none';
                return;
            }
            fila.style.display = '';
            var numEl = document.getElementById('detImportanciaNum');
            if (numEl) {
                numEl.textContent = String(nivel);
                numEl.setAttribute('aria-label', 'Importancia ' + nivel + ' de 10');
            }
            var leyenda = document.getElementById('detImportanciaLeyenda');
            if (leyenda) {
                if (nivel === 1) {
                    leyenda.textContent = 'Prioridad máxima';
                } else if (nivel >= 10) {
                    leyenda.textContent = 'Prioridad mínima';
                } else {
                    leyenda.textContent = 'Prioridad intermedia';
                }
            }
            var escala = document.getElementById('detImportanciaEscala');
            if (escala) {
                var html = '';
                for (var i = 1; i <= 10; i++) {
                    var cls = 'importancia-paso';
                    if (i === nivel) cls += ' activo';
                    else if (i < nivel) cls += ' mas-alta';
                    html += '<span class="' + cls + '" title="Nivel ' + i + '">' + i + '</span>';
                }
                escala.innerHTML = html;
                escala.setAttribute('aria-label', 'Importancia ' + nivel + ' en escala de 1 (máxima) a 10 (mínima)');
            }
        }

        function renderEstadoControls(ticket) {
            var sel = document.getElementById('gestionEstado');
            var actual = ticket.estado || '';
            var permitidas = ticket.transiciones_permitidas || [];
            var disponibles = ticket.estados_disponibles || [];

            // Limpiar y reconstruir opciones según permisos del backend.
            sel.innerHTML = '';
            disponibles.forEach(function(opt) {
                var o = document.createElement('option');
                o.value = opt.key;
                o.textContent = opt.label;
                if (!opt.enabled) o.disabled = true;
                if (opt.key === actual) o.selected = true;
                sel.appendChild(o);
            });

            // Si el estado es terminal (cierre) deshabilitar el select y el botón.
            var help = document.getElementById('gestionEstadoHelp');
            if (actual === 'cierre' || permitidas.length === 0) {
                sel.disabled = true;
                if (help) {
                    help.innerHTML = '<i class="fas fa-lock"></i> El caso está en estado terminal (Cierre): no admite más cambios de estado.';
                }
            } else {
                sel.disabled = false;
            }
        }

        function updateTimelineEstadoActual(estadoActual) {
            var badge = document.getElementById('ticketTimelineEstadoBadge');
            if (!badge) return;
            var key = (estadoActual || '').trim();
            var lab = ESTADO_LABELS[key] || (key || '—');
            badge.className = 'ticket-estado-badge' + (key ? (' estado-' + key) : '');
            badge.innerHTML = '<i class="fas fa-flag-checkered" aria-hidden="true"></i> ' + escapeHtml(lab);
        }

        function renderTimeline(historial, estadoActual) {
            updateTimelineEstadoActual(estadoActual);

            var tl = document.getElementById('ticketTimeline');
            if (!tl) return;

            if (!historial || historial.length === 0) {
                tl.innerHTML = '<li class="timeline-empty">Sin registros de cambio de estado en el historial. El <strong>estado actual</strong> del ticket se muestra arriba; cada nueva gestión que cambie el estado aparecerá aquí.</li>';
                return;
            }

            var html = historial.map(function(h, idx) {
                var esUltimo = (idx === historial.length - 1);
                var esActual = esUltimo;
                var labelNuevo = h.estado_guardado_label || h.estado_label || ESTADO_LABELS[h.estado_nuevo] || h.estado_nuevo;
                var labelAnt = h.estado_anterior_label || (h.estado_anterior ? (ESTADO_LABELS[h.estado_anterior] || h.estado_anterior) : null);
                var fecha = h.fecha_cambio ? new Date(h.fecha_cambio).toLocaleString() : '';
                var asesor = h.asesor_nombre_completo ? escapeHtml(h.asesor_nombre_completo) : 'Sistema';
                var dur = h.duracion_legible || '';
                var prevText = labelAnt
                    ? ('Transición registrada: de «' + escapeHtml(labelAnt) + '» a «' + escapeHtml(labelNuevo) + '»')
                    : ('Alta del ticket · estado inicial: «' + escapeHtml(labelNuevo) + '»');
                var obs = h.observacion
                    ? ('<div class="timeline-obs"><span class="timeline-obs-label">Observación en esta gestión</span>' +
                        '<div class="timeline-obs-body">' + escapeHtml(h.observacion) + '</div></div>')
                    : '';

                var estadoKey = String(h.estado_nuevo || '').trim();
                var estadoClassKey = estadoKey.replace(/[^a-z0-9_]/gi, '') || 'sin_estado';
                var badgeGuardado = '<span class="ticket-estado-badge estado-' + estadoClassKey + '">' +
                    '<i class="fas fa-bookmark" aria-hidden="true"></i> ' + escapeHtml(labelNuevo) + '</span>';

                return '' +
                    '<li class="timeline-step estado-' + estadoClassKey + (esActual ? ' active' : '') + '">' +
                        '<div class="timeline-marker"></div>' +
                        '<div class="timeline-body">' +
                            '<div class="timeline-estado-gestion">' +
                                '<span class="timeline-estado-gestion-label">Estado en que quedó guardado el ticket en esta gestión</span>' +
                                badgeGuardado +
                            '</div>' +
                            '<div class="timeline-title">' + prevText + '</div>' +
                            '<div class="timeline-meta">' +
                                '<span><i class="far fa-clock"></i> ' + escapeHtml(fecha) + '</span>' +
                                '<span><i class="far fa-user"></i> ' + asesor + '</span>' +
                                (dur ? '<span><i class="fas fa-hourglass-half"></i> ' + escapeHtml(dur) + (esActual ? ' (en curso)' : '') + '</span>' : '') +
                            '</div>' +
                            obs +
                        '</div>' +
                    '</li>';
            }).join('');

            tl.innerHTML = html;
        }

        async function guardarGestionTicket(e) {
            e.preventDefault();

            const sel = document.getElementById('gestionEstado');
            const estado = sel.value;
            const ticketId = document.getElementById('gestionTicketId').value;

            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            formData.append('estado', estado);


            const nuevaNota = document.getElementById('nuevaNota').value;
            if (nuevaNota.trim()) {
                formData.append('nueva_nota', nuevaNota);
            }

            const proximaAccion = document.getElementById('proximaAccion').value;
            const fechaProximaAccion = document.getElementById('fechaProximaAccion').value;
            if (proximaAccion.trim()) {
                formData.append('proxima_accion', proximaAccion);
            }
            if (fechaProximaAccion) {
                formData.append('fecha_proxima_accion', fechaProximaAccion);
            }

            const nuevoPdf = document.getElementById('nuevoPdfArchivo').files[0];
            if (nuevoPdf) {
                formData.append('nuevo_pdf', nuevoPdf);
            }

            try {
                const response = await fetch('../api/gestionar_ticket.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Cambios guardados', 'success');
                    var tid = String(typeof TICKET_ID !== 'undefined' ? TICKET_ID : (document.getElementById('gestionTicketId') && document.getElementById('gestionTicketId').value) || '');
                    var key = 'crm_ticket_primer_guardado_' + tid;
                    if (tid && !localStorage.getItem(key)) {
                        localStorage.setItem(key, '1');
                        setTimeout(function() {
                            window.location.href = 'asesor_tickets.php';
                        }, 600);
                    } else {
                        setTimeout(function() {
                            window.location.reload();
                        }, 600);
                    }
                } else {
                    showMessage(result.message || 'No se pudo guardar', 'error');
                }
            } catch (error) {
                console.error('Error guardando gestión del ticket:', error);
                showMessage('Error guardando cambios', 'error');
            }
        }

        function previewNuevoPDF(input) {
            const file = input.files[0];
            const preview = document.getElementById('nuevo-pdf-preview');
            const filename = document.getElementById('nuevo-pdf-filename');

            if (file) {
                if (file.type !== 'application/pdf') {
                    showMessage('Por favor seleccione un archivo PDF válido', 'error');
                    input.value = '';
                    return;
                }

                const maxSize = 50 * 1024 * 1024;
                if (file.size > maxSize) {
                    showMessage('El archivo PDF no puede ser mayor a 50MB', 'error');
                    input.value = '';
                    return;
                }

                filename.textContent = file.name;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        function removeNuevoPDF() {
            document.getElementById('nuevoPdfArchivo').value = '';
            document.getElementById('nuevo-pdf-preview').style.display = 'none';
        }

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + type;
            messageDiv.innerHTML =
                '<i class="fas fa-' + (type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')) + '"></i>' +
                escapeHtml(message);

            const contentArea = document.querySelector('.content-area');
            contentArea.insertBefore(messageDiv, contentArea.firstChild);

            setTimeout(function() {
                messageDiv.remove();
            }, 5000);
        }

        async function cerrarSesion() {
            if (!confirm('¿Está seguro de cerrar sesión?')) return;

            try {
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
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

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

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

        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
