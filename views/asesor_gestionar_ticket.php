<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('asesor');

$ticketId = (int) app_route_param('id', 0);
if ($ticketId <= 0) {
    app_redirect_route('asesor_tickets');
}

$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/partials/app_head.php'; ?>
    <title>Gestionar ticket #<?php echo $ticketId; ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/asesor.css" rel="stylesheet">
    <link href="css/tickets.css" rel="stylesheet">
    <link href="css/softphone-web.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="img/logo2.png" alt="CRM Logo">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Asesor</div>
                    <a href="<?php echo app_nav_url('asesor_dashboard'); ?>" class="nav-item">
                        <i class="fas fa-folder-open"></i>
                        Mis casos (reparto)
                    </a>
                    <a href="<?php echo app_nav_url('asesor_tickets'); ?>" class="nav-item active">
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
                    <a href="<?php echo app_nav_url('asesor_tickets'); ?>" class="btn btn-secondary">
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
                                        <div id="detClienteTelefonosEmpty" class="detalle-sublinha vacio">Sin teléfonos registrados</div>
                                        <div id="detClienteTelefonosToolbar" class="detalle-telefonos-toolbar" hidden>
                                            <div class="det-tel-col-select">
                                                <span class="det-tel-mini-label" id="detClienteTelefonoSelectLbl">Todos los números (CRM / CSV)</span>
                                                <select id="detClienteTelefonoSelect" class="form-control det-tel-select" aria-labelledby="detClienteTelefonoSelectLbl"></select>
                                            </div>
                                            <div class="det-tel-col-vista">
                                                <span class="det-tel-mini-label">Número seleccionado</span>
                                                <button type="button" class="det-tel-numero-btn" id="detClienteTelefonoAccion"
                                                        title="Copiar número e iniciar llamada WebRTC">
                                                    <span class="det-tel-numero-text" id="detClienteTelefonoSeleccionadoVisual">—</span>
                                                    <span class="det-tel-accion-icons" aria-hidden="true">
                                                        <i class="fas fa-copy"></i><i class="fas fa-phone-alt"></i>
                                                    </span>
                                                </button>
                                                <p class="det-tel-hint">Clic para copiar al portapapeles y llamar</p>
                                            </div>
                                        </div>
                                    </dd>
                                </div>
                                <div class="detalle-fila detalle-fila-multilinea">
                                    <dt class="detalle-label">Emails</dt>
                                    <dd class="detalle-valor detalle-valor-flush">
                                        <div id="detClienteEmailsEmpty" class="detalle-sublinha vacio">Sin emails registrados</div>
                                        <div id="detClienteEmailsToolbar" class="detalle-telefonos-toolbar det-toolbar-contacto-mail" hidden>
                                            <div class="det-tel-col-select">
                                                <span class="det-tel-mini-label" id="detClienteEmailSelectLbl">Todos los correos (CRM / CSV)</span>
                                                <select id="detClienteEmailSelect" class="form-control det-tel-select" aria-labelledby="detClienteEmailSelectLbl"></select>
                                            </div>
                                            <div class="det-tel-col-vista">
                                                <span class="det-tel-mini-label">Correo seleccionado</span>
                                                <button type="button" class="det-tel-numero-btn det-mail-copy-btn" id="detClienteEmailAccion"
                                                        title="Copiar correo seleccionado al portapapeles">
                                                    <span class="det-tel-numero-text" id="detClienteEmailSeleccionadoVisual">—</span>
                                                    <span class="det-tel-accion-icons" aria-hidden="true">
                                                        <i class="fas fa-copy"></i><i class="fas fa-envelope"></i>
                                                    </span>
                                                </button>
                                                <p class="det-tel-hint">Clic para copiar al portapapeles</p>
                                            </div>
                                        </div>
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
                                        <option value="">Cargando estados…</option>
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

                        <div class="form-section perfilacion-contactabilidad-section" id="perfilacionContactabilidadSection" style="display: none;">
                            <h4><i class="fas fa-sitemap"></i> Tipificación: contactabilidad con el cliente</h4>
                            <p class="form-text perfilacion-intro">Visible solo cuando el estado del caso es <strong>«Contactabilidad con el cliente»</strong>.
                            Los desplegables están en columna; el siguiente solo se habilita cuando cierra bien el nivel anterior.</p>
                            <div id="perfilacionNiveles" class="perfilacion-niveles" aria-label="Niveles de tipificación"></div>
                            <p id="perfilacionEstadoAyuda" class="form-text perfilacion-ayuda" style="display: none;"></p>
                        </div>

                        <div class="form-section perfilacion-actualizacion-section" id="perfilacionActualizacionSection" style="display: none;">
                            <h4><i class="fas fa-exchange-alt"></i> Tipificación: actualización vs escalamiento</h4>
                            <p id="perfilActualizacionIntro" class="form-text perfilacion-intro">Según las reglas definidas para el estado seleccionado, indique Si o No.</p>
                            <div class="form-group">
                                <label for="perfilActualizacionRespuesta">¿Aplica esta gestión?</label>
                                <select id="perfilActualizacionRespuesta" class="form-control" name="perfil_actualizacion_respuesta" autocomplete="off">
                                    <option value="">Seleccione…</option>
                                    <option value="si">Sí</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="form-group" id="wrapPerfilActualizacionConfirm" style="display: none;">
                                <label for="perfilActualizacionConfirmacion">Confirmar siguiente acción</label>
                                <select id="perfilActualizacionConfirmacion" class="form-control perfilacion-select" autocomplete="off"></select>
                                <small class="form-text">Se habilita después de elegir Sí o No. Debe coincidir con una de las acciones descritas abajo.</small>
                            </div>
                            <div class="perfil-actualizacion-leyenda" id="perfilActualizacionLeyenda">
                                <p class="leyenda-row"><strong>Si elige «Sí»:</strong> <span id="perfilActualizacionTxtSi"></span></p>
                                <p class="leyenda-row"><strong>Si elige «No»:</strong> <span id="perfilActualizacionTxtNo"></span></p>
                            </div>
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
                            <p class="form-text" style="margin-top: -6px;"><strong>Tipificación:</strong> en estados distintos de «contactabilidad», al guardar, la sección anterior y estos campos de <em>Próxima acción</em> y <em>Fecha</em> generan también un ítem aparte en el historial solo para tipificación.</p>
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
                            <a href="<?php echo app_nav_url('asesor_tickets'); ?>" class="btn btn-secondary btn-compact">
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
        var __detalleTelToolbarHandlersBound = false;
        var __detalleEmailToolbarHandlersBound = false;

        async function copiarTextoPortapapeles(texto) {
            var s = String(texto || '').trim();
            if (!s) return false;
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                await navigator.clipboard.writeText(s);
                return true;
            }
            return new Promise(function(resolve) {
                try {
                    var ta = document.createElement('textarea');
                    ta.value = s;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    ta.setSelectionRange(0, 99999);
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    resolve(true);
                } catch (eTa) {
                    resolve(false);
                }
            });
        }

        function actualizarDetalleTelefonoSeleccionVisual() {
            actualizarSeleccionVisualDesdeSelect(
                document.getElementById('detClienteTelefonoSelect'),
                document.getElementById('detClienteTelefonoSeleccionadoVisual')
            );
        }

        function syncSoftphoneNumberFromTicketSelect() {
            var sel = document.getElementById('detClienteTelefonoSelect');
            if (!sel || sel.selectedIndex < 0) return;
            var raw = String(sel.options[sel.selectedIndex].value || '').trim();
            var digits = raw.replace(/\D/g, '');
            var nombre = '';
            try {
                if (window.__ticketActual && window.__ticketActual.cliente_nombre) {
                    nombre = String(window.__ticketActual.cliente_nombre);
                }
            } catch (_) {}
            var line = document.getElementById('softphoneClienteLine');
            if (line) {
                line.textContent = digits
                    ? (nombre ? nombre + ' · Tel. ' + raw : 'Tel. ' + raw)
                    : (nombre ? nombre + ' · Sin teléfono en ficha' : 'Cliente sin datos');
            }
            if (digits && window.webrtcSoftphone && typeof window.webrtcSoftphone.setNumber === 'function') {
                window.webrtcSoftphone.setNumber(digits);
            } else if (digits) {
                var nd = document.getElementById('number-display');
                if (nd) nd.value = digits;
            }
        }

        function bindDetalleTelefonosToolbarOnce() {
            if (__detalleTelToolbarHandlersBound) return;
            var sel = document.getElementById('detClienteTelefonoSelect');
            var btn = document.getElementById('detClienteTelefonoAccion');
            if (!sel || !btn) return;
            __detalleTelToolbarHandlersBound = true;
            sel.addEventListener('change', function() {
                actualizarDetalleTelefonoSeleccionVisual();
                syncSoftphoneNumberFromTicketSelect();
            });
            btn.addEventListener('click', function(ev) {
                ev.preventDefault();
                ejecutarCopiarYllamarDetalleTel();
            });
        }

        function actualizarDetalleEmailSeleccionVisual() {
            var sel = document.getElementById('detClienteEmailSelect');
            var viz = document.getElementById('detClienteEmailSeleccionadoVisual');
            actualizarSeleccionVisualDesdeSelect(sel, viz);
        }

        function bindDetalleEmailsToolbarOnce() {
            if (__detalleEmailToolbarHandlersBound) return;
            var sel = document.getElementById('detClienteEmailSelect');
            var btn = document.getElementById('detClienteEmailAccion');
            if (!sel || !btn) return;
            __detalleEmailToolbarHandlersBound = true;
            sel.addEventListener('change', function() {
                actualizarDetalleEmailSeleccionVisual();
            });
            btn.addEventListener('click', function(ev) {
                ev.preventDefault();
                ejecutarCopiarDetalleEmail();
            });
        }

        function actualizarSeleccionVisualDesdeSelect(sel, vizEl) {
            if (!vizEl) return;
            var raw = '';
            if (sel && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]) {
                raw = String(sel.options[sel.selectedIndex].value || '').trim();
            }
            vizEl.textContent = raw || '—';
        }

        function obtenerTelefonoPrincipalParaSoftphone(ticket) {
            var sel = document.getElementById('detClienteTelefonoSelect');
            if (sel && sel.options.length > 0 && sel.selectedIndex >= 0) {
                var vv = String(sel.options[sel.selectedIndex].value || '').trim();
                if (vv) return vv;
            }
            if (ticket && ticket.cliente_telefono) return String(ticket.cliente_telefono);
            if (ticket && ticket.cliente_telefonos && ticket.cliente_telefonos.length > 0 && ticket.cliente_telefonos[0].numero) {
                return String(ticket.cliente_telefonos[0].numero);
            }
            return '';
        }

        async function ejecutarCopiarYLlamarTelDesdeSelect(sel) {
            if (!sel || sel.selectedIndex < 0) return;
            var raw = String(sel.options[sel.selectedIndex].value || '').trim();
            if (!raw) {
                showMessage('Seleccione un número en la lista.', 'error');
                return;
            }
            var digits = raw.replace(/\D/g, '');
            try {
                await copiarTextoPortapapeles(raw);
            } catch (eCopy) {
                showMessage('No se pudo copiar al portapapeles.', 'error');
                return;
            }
            if (!digits) {
                showMessage('Número copiado (no hay dígitos para marcar por WebRTC).', 'info');
                return;
            }
            if (!window.webrtcSoftphone || typeof window.webrtcSoftphone.callNumber !== 'function') {
                showMessage('Número copiado. Conecte el softphone lateral para llamar.', 'warning');
                return;
            }
            try {
                await window.webrtcSoftphone.callNumber(digits);
                showMessage('Llamando a ' + raw + '…', 'success');
            } catch (errCall) {
                showMessage('Número copiado; error al iniciar llamada WebRTC.', 'error');
                console.error(errCall);
            }
        }

        async function ejecutarCopiarYllamarDetalleTel() {
            await ejecutarCopiarYLlamarTelDesdeSelect(document.getElementById('detClienteTelefonoSelect'));
        }

        async function ejecutarCopiarEmailDesdeSelect(sel) {
            if (!sel || sel.selectedIndex < 0) return;
            var raw = String(sel.options[sel.selectedIndex].value || '').trim();
            if (!raw) {
                showMessage('Seleccione un correo en la lista.', 'error');
                return;
            }
            try {
                await copiarTextoPortapapeles(raw);
                showMessage('Correo copiado.', 'success');
            } catch (eCopy) {
                showMessage('No se pudo copiar al portapapeles.', 'error');
            }
        }

        async function ejecutarCopiarDetalleEmail() {
            await ejecutarCopiarEmailDesdeSelect(document.getElementById('detClienteEmailSelect'));
        }

        async function initSoftphoneSidebar(ticket) {
            if (__gestionarSoftphoneInitDone) return;
            __gestionarSoftphoneInitDone = true;

            var line = document.getElementById('softphoneClienteLine');
            var banner = document.getElementById('softphoneInactiveBanner');
            var mount = document.getElementById('webrtc-softphone');
            if (!line || !banner || !mount) return;

            var tel = obtenerTelefonoPrincipalParaSoftphone(ticket);
            var nombre = (ticket && ticket.cliente_nombre) ? String(ticket.cliente_nombre) : '';
            line.textContent = tel ? (nombre + ' · Tel. ' + tel) : (nombre ? nombre + ' · Sin teléfono en ficha' : 'Cliente sin datos');

            try {
                var res = await fetch('api/softphone_config.php', { credentials: 'same-origin' }).then(function(r) {
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
                    syncSoftphoneNumberFromTicketSelect();
                    return;
                }

                new WebRTCSoftphone(res.config);

                syncSoftphoneNumberFromTicketSelect();

                var digits = obtenerTelefonoPrincipalParaSoftphone(ticket).replace(/\D/g, '');
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
                const response = await fetch('api/ticket_detalle_completo.php?ticket_id=' + TICKET_ID, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (!result.success || !result.data) {
                    showMessage(result.message || 'No se pudo cargar el ticket', 'error');
                    document.getElementById('ticketSubtitle').textContent = 'Error al cargar el ticket';
                    setTimeout(function() {
                        window.appGo('asesor_tickets');
                    }, 2000);
                    return;
                }

                const ticket = result.data;
                renderHeaderPro(ticket);
                renderDetallesCaso(ticket);
                renderEstadoControls(ticket);
                renderTimeline(ticket.historial_estado || [], ticket.estado || '');
                initPerfilacionDesdeTicket(ticket);
                syncTipificacionesTicket();
                document.getElementById('gestionEstado').addEventListener('change', syncTipificacionesTicket);

                var perfActPx = document.getElementById('perfilActualizacionRespuesta');
                if (perfActPx && !perfActPx.dataset.perfilListen) {
                    perfActPx.dataset.perfilListen = '1';
                    perfActPx.addEventListener('change', function() {
                        var ge = document.getElementById('gestionEstado');
                        actualizarDesplegableConfirmacionActualizacion();
                        var g = window.__perfilActualizacionGuardada;
                        var geo = ge ? ge.value : '';
                        if (perfActPx.value && g && geo === g.estado_al_guardar && g.accion_confirmada &&
                            g.respuesta !== perfActPx.value) {
                            var cf = document.getElementById('perfilActualizacionConfirmacion');
                            if (cf) cf.value = '';
                        }
                    });
                }

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
                    window.appGo('asesor_tickets');
                }, 2000);
            }
        });

        function parseTipificacionNotaJson(text) {
            var s = String(text || '').trim();
            if (!s || s.charAt(0) !== '{') return null;
            try {
                var o = JSON.parse(s);
                if (o && o.tipo === 'tipificacion_actualizacion_v1') return o;
            } catch (eJ) {}
            return null;
        }

        function buildTipificacionHistorialHtml(parsed) {
            var ek = String(parsed.estado_key || '').trim();
            var el = String(parsed.estado_label || '').trim() || ek;
            var claseEst = ek.replace(/[^a-z0-9_]/gi, '') || 'sin_estado';
            var h = '<div class="nota-tipif-fields">';
            h += '<div class="nota-tipif-field">';
            h += '<span class="nota-tipif-field-label">Estado</span>';
            h += '<div class="nota-estado-line"><span class="ticket-estado-badge estado-' + claseEst + '">';
            h += '<i class="fas fa-flag-checkered" aria-hidden="true"></i> ';
            h += escapeHtml(el) + '</span></div></div>';
            h += '<div class="nota-tipif-field">';
            h += '<span class="nota-tipif-field-label">¿Aplica esta gestión?</span>';
            h += '<div class="nota-estado-line"><span class="ticket-estado-badge nota-tipif-valor nota-tipif-valor-aplica">';
            h += '<i class="fas fa-exchange-alt" aria-hidden="true"></i> ';
            h += escapeHtml(String(parsed.aplica_gestion || '—')) + '</span></div></div>';
            h += '<div class="nota-tipif-field">';
            h += '<span class="nota-tipif-field-label">Confirmar la siguiente acción</span>';
            h += '<div class="nota-estado-line"><span class="ticket-estado-badge nota-tipif-valor nota-tipif-valor-accion">';
            h += '<i class="fas fa-check-double" aria-hidden="true"></i> ';
            h += escapeHtml(String(parsed.accion_confirmada || '—')) + '</span></div></div>';
            h += '</div>';
            return h;
        }

        async function cargarHistorialNotas(ticketId) {
            try {
                const response = await fetch('api/ticket_notas.php?ticket_id=' + ticketId, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                const historialContainer = document.getElementById('historialNotas');

                if (result.success && result.data.length > 0) {
                    historialContainer.innerHTML = result.data.map(function(nota) {
                        var estKey = (nota.estado_ticket || '').trim();
                        var estLabel = (nota.estado_label || estKey || '').trim();
                        var tipoNota = ((nota.tipo_nota || 'asesor').trim() !== '') ? String(nota.tipo_nota).trim() : 'asesor';
                        var esTipifTipo = tipoNota === 'tipificacion_actualizacion';
                        var parsedTip = parseTipificacionNotaJson(nota.contenido);
                        var esVistaTipif = esTipifTipo || parsedTip !== null;
                        var estadoHtml = '';
                        if (!parsedTip && estKey && estLabel) {
                            estadoHtml = '<div class="nota-estado-line"><span class="ticket-estado-badge estado-' +
                                estKey.replace(/[^a-z0-9_]/gi, '') + '">' +
                                '<i class="fas fa-route" aria-hidden="true"></i> ' + escapeHtml(estLabel) +
                                '</span></div>';
                        }
                        var badgeTipo = '';
                        if (esVistaTipif) {
                            badgeTipo = '<div class="nota-tipo-line"><span class="nota-tipo-badge tipificacion">' +
                                '<i class="fas fa-sitemap" aria-hidden="true"></i> Tipificación (actualización)</span></div>';
                        }
                        var cuerpoTipif = '';
                        if (parsedTip) {
                            cuerpoTipif = buildTipificacionHistorialHtml(parsedTip);
                        } else if (String(nota.contenido || '').trim() !== '') {
                            cuerpoTipif = '<div class="nota-contenido">' + escapeHtml(nota.contenido) + '</div>';
                        }
                        var fechaProx = '';
                        if (nota.fecha_proxima_accion) {
                            try {
                                fechaProx = '<div class="nota-fecha-prox"><strong>Fecha próxima acción:</strong> ' +
                                    escapeHtml(String(new Date(nota.fecha_proxima_accion).toLocaleString())) + '</div>';
                            } catch (eFp) {
                                fechaProx = '';
                            }
                        }
                        return '<div class="nota-item' + (esVistaTipif ? ' nota-item-tipificacion' : '') + '">' +
                            '<div class="nota-header">' +
                            '<span class="nota-fecha">' + new Date(nota.fecha_creacion).toLocaleString() + '</span>' +
                            '<span class="nota-asesor">' + escapeHtml(nota.asesor_nombre || '') + '</span>' +
                            '</div>' +
                            badgeTipo +
                            estadoHtml +
                            cuerpoTipif +
                            (nota.proxima_accion ? '<div class="nota-accion"><strong>Próxima acción:</strong> ' +
                                escapeHtml(nota.proxima_accion) + '</div>' : '') +
                            fechaProx +
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
                const response = await fetch('api/ticket_archivos.php?ticket_id=' + ticketId, {
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

                const response = await fetch('api/eliminar_archivo_ticket.php', {
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

        const ESTADO_CONTACTABILIDAD = 'contactabilidad_cliente';
        const ESTADO_DESEMBOLSO = 'desembolso';

        function getTextosActualizacion(esDesembolso) {
            if (esDesembolso) {
                return {
                    si: 'Actualizamos sistema',
                    no: 'Lanzamos alerta Clean Trust'
                };
            }
            return {
                si: 'Actualización en el sistema',
                no: 'Escalamos con director de la operación'
            };
        }

        const ESTADO_LABELS = {
            contactabilidad_cliente: 'Contactabilidad con el cliente',
            acuerdo_comercial: 'Acuerdo comercial',
            documentos_corte: 'Documentos a la corte',
            documentos_adicionales: 'Documentos adicionales',
            corte_giro_saldo: 'Corte giró saldo',
            cliente_swift: 'Cliente Swift',
            desembolso: 'Desembolso'
        };
        var ESTADO_LEGACY_LABELS = {
            comunicacion: 'Comunicación',
            validacion: 'Validación',
            proceso_judicial: 'Proceso judicial',
            remate: 'Remate',
            recuperacion: 'Recuperación',
            cierre: 'Cierre'
        };

        function labelEstadoTicket(k) {
            if (!k) return '—';
            return ESTADO_LABELS[k] || ESTADO_LEGACY_LABELS[k] || k;
        }

        function perfilSlug(text, idx) {
            var s = String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            return 'n_' + idx + '_' + (s || 'x');
        }

        var PERFIL_RAMAS_SI = [
            { key: 'cuelga_llamada', label: 'Cuelga la llamada', pasos: ['Se agenda nueva llamada'] },
            { key: 'estafa', label: 'Estafa', pasos: ['Solicitamos correo electrónico', 'Remitimos información'] },
            { key: 'recibe_informacion', label: 'Recibe información', pasos: ['Solicitamos correo electrónico', 'Remitimos información'] },
            { key: 'indica_averiguar', label: 'Indica va a averiguar', pasos: ['Le indicamos nuestros datos de contacto', 'Se agenda nueva llamada'] },
            { key: 'mismo_tramite', label: 'El mismo hace el trámite', pasos: ['Le indicamos nuestros datos de contacto', 'Se agenda nueva llamada'] },
            { key: 'dan_vobo', label: 'Dan el VoBo', pasos: ['Solicitamos correo electrónico', 'Remitimos información'] }
        ];

        var PERFIL_NO_CANALES = [
            { key: 'google', label: 'Google' },
            { key: 'linkedin', label: 'LinkedIn' }
        ];

        window.__perfilPath = [];

        function trimPerfilRowsAfter(box, el) {
            if (!box || !el) return;
            var n = el.nextSibling;
            while (n) {
                var nx = n.nextSibling;
                n.parentNode.removeChild(n);
                n = nx;
            }
        }

        function pasoKeySi(ramaKey, texto, stepIdx) {
            return String(ramaKey) + '__' + perfilSlug(texto, stepIdx);
        }

        function isPerfilContactabilidadCompleta() {
            var p = window.__perfilPath;
            if (!p || p.length < 2) return false;
            if (p[0].key !== 'si' && p[0].key !== 'no') return false;
            if (p[0].key === 'si') {
                var rama = PERFIL_RAMAS_SI.find(function(r) { return r.key === p[1].key; });
                if (!rama) return false;
                /* Acepta perfiles históricos con más pasos (cuando el árbol tenía niveles extra). */
                return p.length >= 2 + rama.pasos.length;
            }
            if (p[1].key !== 'localizacion') return false;
            var canalOk = PERFIL_NO_CANALES.some(function(c) { return c.key === p[2].key; });
            if (!canalOk) return false;
            return p.length >= 4 && p[3].key === 'agenda_interaccion';
        }

        function renderPerfilacionNiveles() {
            var box = document.getElementById('perfilacionNiveles');
            var help = document.getElementById('perfilacionEstadoAyuda');
            if (!box) return;
            box.innerHTML = '';
            window.__perfilPath = [];

            var wrap0 = document.createElement('div');
            wrap0.className = 'perfilacion-col';
            wrap0.innerHTML = '<label class="perfilacion-lbl">Nivel 1 · Contactabilidad con el cliente</label>';
            var sel0 = document.createElement('select');
            sel0.className = 'form-control perfilacion-select';
            sel0.innerHTML = '<option value="">Seleccione…</option>' +
                '<option value="si">Sí — hubo contactabilidad</option><option value="no">No — sin contactabilidad</option>';
            sel0.addEventListener('change', function() {
                onPerfilNivelCambio(0, sel0.value);
            });
            wrap0.appendChild(sel0);
            box.appendChild(wrap0);
            if (help) {
                help.style.display = 'none';
                help.textContent = '';
            }
        }

        function onPerfilNivelCambio(nivelDepth, valorKey) {
            var box = document.getElementById('perfilacionNiveles');
            var cols = box.querySelectorAll('.perfilacion-col');
            for (var i = cols.length - 1; i > nivelDepth; i--) {
                cols[i].remove();
            }
            window.__perfilPath = window.__perfilPath.slice(0, nivelDepth);

            function pushPaso(key, label) {
                window.__perfilPath.push({ key: key, label: label });
            }

            if (valorKey === '') return;

            if (nivelDepth === 0) {
                if (valorKey === 'si') {
                    pushPaso('si', 'Sí');
                    crearSelectRamaSi();
                } else if (valorKey === 'no') {
                    pushPaso('no', 'No');
                    crearBloqueNoLocalizacion();
                }
                return;
            }

            var pSlice = window.__perfilPath;
            if (pSlice[0] && pSlice[0].key === 'si' && nivelDepth === 1) {
                var rama = PERFIL_RAMAS_SI.find(function(r) { return r.key === valorKey; });
                if (!rama) return;
                pushPaso(rama.key, rama.label);
                iniciarPasosRamaSi(rama);
            }
        }

        function crearSelectRamaSi() {
            var box = document.getElementById('perfilacionNiveles');
            var wrap = document.createElement('div');
            wrap.className = 'perfilacion-col';
            wrap.innerHTML = '<label class="perfilacion-lbl">Nivel 2 · Desenlace del contacto</label>';
            var sel = document.createElement('select');
            sel.className = 'form-control perfilacion-select';
            sel.innerHTML = '<option value="">Seleccione…</option>' +
                PERFIL_RAMAS_SI.map(function(r) {
                    return '<option value="' + r.key + '">' + escapeHtml(r.label) + '</option>';
                }).join('');
            sel.addEventListener('change', function() {
                onPerfilNivelCambio(1, sel.value);
            });
            wrap.appendChild(sel);
            box.appendChild(wrap);
        }

        function iniciarPasosRamaSi(rama) {
            var box = document.getElementById('perfilacionNiveles');
            function addPaso(stepIdx) {
                if (stepIdx >= rama.pasos.length) return;
                var texto = rama.pasos[stepIdx];
                var k = pasoKeySi(rama.key, texto, stepIdx);
                var nivelNum = 3 + stepIdx;
                var wrap = document.createElement('div');
                wrap.className = 'perfilacion-col perfilacion-paso-seq';
                wrap.innerHTML = '<label class="perfilacion-lbl">Nivel ' + nivelNum + ' · ' + escapeHtml(texto) + '</label>';
                var sel = document.createElement('select');
                sel.className = 'form-control perfilacion-select';
                sel.innerHTML = '<option value="">Seleccione…</option>' +
                    '<option value="' + escapeHtml(k) + '">' + escapeHtml(texto) + '</option>';
                sel.addEventListener('change', function() {
                    trimPerfilRowsAfter(box, wrap);
                    window.__perfilPath = window.__perfilPath.slice(0, 2 + stepIdx);
                    if (!sel.value) return;
                    window.__perfilPath.push({ key: k, label: texto });
                    addPaso(stepIdx + 1);
                });
                wrap.appendChild(sel);
                box.appendChild(wrap);
            }
            addPaso(0);
        }

        function crearBloqueNoLocalizacion() {
            var box = document.getElementById('perfilacionNiveles');

            var wrapLoc = document.createElement('div');
            wrapLoc.className = 'perfilacion-col';
            wrapLoc.innerHTML = '<label class="perfilacion-lbl">Nivel 2 · Localización</label>';
            var selLoc = document.createElement('select');
            selLoc.className = 'form-control perfilacion-select';
            selLoc.innerHTML = '<option value="">Seleccione…</option><option value="localizacion">Localización</option>';

            var wrapCanal = document.createElement('div');
            wrapCanal.className = 'perfilacion-col perfilacion-no-canal';
            wrapCanal.innerHTML = '<label class="perfilacion-lbl">Nivel 3 · Canal</label>';
            var selCanal = document.createElement('select');
            selCanal.className = 'form-control perfilacion-select';
            selCanal.disabled = true;
            selCanal.innerHTML = '<option value="">Seleccione…</option>' +
                PERFIL_NO_CANALES.map(function(c) {
                    return '<option value="' + c.key + '">' + escapeHtml(c.label) + '</option>';
                }).join('');

            selLoc.addEventListener('change', function() {
                trimPerfilRowsAfter(box, wrapCanal);
                selCanal.value = '';
                selCanal.disabled = true;
                window.__perfilPath = window.__perfilPath.slice(0, 1);
                if (!selLoc.value) return;
                window.__perfilPath.push({ key: 'localizacion', label: 'Localización' });
                selCanal.disabled = false;
            });

            selCanal.addEventListener('change', function() {
                trimPerfilRowsAfter(box, wrapCanal);
                window.__perfilPath = window.__perfilPath.slice(0, 2);
                if (!selCanal.value) return;
                var canal = PERFIL_NO_CANALES.find(function(c) { return c.key === selCanal.value; });
                if (!canal) return;
                window.__perfilPath.push({ key: canal.key, label: canal.label });
                appendNoAgendaInteraccionRow(box, wrapCanal);
            });

            wrapLoc.appendChild(selLoc);
            wrapCanal.appendChild(selCanal);
            box.appendChild(wrapLoc);
            box.appendChild(wrapCanal);
        }

        function appendNoAgendaInteraccionRow(box, insertAfter) {
            trimPerfilRowsAfter(box, insertAfter);
            var wrap = document.createElement('div');
            wrap.className = 'perfilacion-col perfilacion-paso-final';
            wrap.innerHTML = '<label class="perfilacion-lbl">Nivel 4 · Se agenda nuevamente interacción</label>';
            var sel = document.createElement('select');
            sel.className = 'form-control perfilacion-select';
            sel.innerHTML = '<option value="">Seleccione…</option>' +
                '<option value="agenda_interaccion">Confirmar · Se agenda nuevamente interacción</option>';
            sel.addEventListener('change', function() {
                window.__perfilPath = window.__perfilPath.slice(0, 3);
                if (!sel.value) return;
                window.__perfilPath.push({ key: 'agenda_interaccion', label: 'Se agenda nuevamente interacción' });
            });
            wrap.appendChild(sel);
            if (insertAfter.nextSibling) {
                box.insertBefore(wrap, insertAfter.nextSibling);
            } else {
                box.appendChild(wrap);
            }
        }

        function resetSegundoNivelActualizacion() {
            var wrap = document.getElementById('wrapPerfilActualizacionConfirm');
            var c = document.getElementById('perfilActualizacionConfirmacion');
            if (!c) return;
            c.innerHTML = '';
            var o0 = document.createElement('option');
            o0.value = '';
            o0.textContent = 'Seleccione…';
            c.appendChild(o0);
            c.value = '';
            if (wrap) wrap.style.display = 'none';
        }

        function actualizarDesplegableConfirmacionActualizacion() {
            var selAct = document.getElementById('perfilActualizacionRespuesta');
            var estSel = document.getElementById('gestionEstado');
            var wrap = document.getElementById('wrapPerfilActualizacionConfirm');
            var c = document.getElementById('perfilActualizacionConfirmacion');
            if (!selAct || !estSel || !wrap || !c) return;

            var v = String(selAct.value || '').trim();
            if (v !== 'si' && v !== 'no') {
                resetSegundoNivelActualizacion();
                return;
            }
            var des = estSel.value === ESTADO_DESEMBOLSO;
            var t = getTextosActualizacion(des);
            var label = v === 'si' ? t.si : t.no;

            c.innerHTML = '';
            var oEmpty = document.createElement('option');
            oEmpty.value = '';
            oEmpty.textContent = 'Seleccione…';
            c.appendChild(oEmpty);
            var o1 = document.createElement('option');
            o1.value = label;
            o1.textContent = label;
            c.appendChild(o1);

            wrap.style.display = 'block';
        }

        function syncTipificacionesTicket() {
            var sec = document.getElementById('perfilacionContactabilidadSection');
            var ayuda = document.getElementById('perfilacionEstadoAyuda');
            var sel = document.getElementById('gestionEstado');
            var secAct = document.getElementById('perfilacionActualizacionSection');
            var selAct = document.getElementById('perfilActualizacionRespuesta');
            if (!sel) return;

            var est = sel.value || '';

            if (sec) {
                if (est === ESTADO_CONTACTABILIDAD) {
                    sec.style.display = 'block';
                    if (!window.__perfilPath || window.__perfilPath.length === 0) {
                        renderPerfilacionNiveles();
                    }
                    if (ayuda) {
                        ayuda.style.display = 'block';
                        ayuda.innerHTML = '<i class="fas fa-info-circle"></i> Complete todos los niveles de la tipificación antes de guardar.';
                    }
                } else {
                    sec.style.display = 'none';
                    if (ayuda) ayuda.style.display = 'none';
                    window.__perfilPath = [];
                }
            }

            if (secAct && selAct) {
                if (est && est !== ESTADO_CONTACTABILIDAD) {
                    secAct.style.display = 'block';
                    var txt = getTextosActualizacion(est === ESTADO_DESEMBOLSO);
                    var elSi = document.getElementById('perfilActualizacionTxtSi');
                    var elNo = document.getElementById('perfilActualizacionTxtNo');
                    if (elSi) elSi.textContent = txt.si;
                    if (elNo) elNo.textContent = txt.no;
                    var intro = document.getElementById('perfilActualizacionIntro');
                    if (intro) {
                        intro.innerHTML = est === ESTADO_DESEMBOLSO
                            ? 'Estado <strong>Desembolso</strong>: las acciones asociadas a Sí / No son las indicadas abajo.'
                            : 'Para los demás estados del pipeline, las acciones asociadas a Sí / No son las indicadas abajo.';
                    }
                    var g = window.__perfilActualizacionGuardada;
                    if (g && g.estado_al_guardar === est && (g.respuesta === 'si' || g.respuesta === 'no')) {
                        selAct.value = g.respuesta;
                    } else {
                        selAct.value = '';
                    }
                    if (selAct.value) {
                        actualizarDesplegableConfirmacionActualizacion();
                        var selConf = document.getElementById('perfilActualizacionConfirmacion');
                        if (selConf && g && g.estado_al_guardar === est && g.accion_confirmada) {
                            for (var oi = 0; oi < selConf.options.length; oi++) {
                                if (selConf.options[oi].value === g.accion_confirmada) {
                                    selConf.value = g.accion_confirmada;
                                    break;
                                }
                            }
                        }
                    } else {
                        resetSegundoNivelActualizacion();
                    }
                } else {
                    secAct.style.display = 'none';
                    selAct.value = '';
                    resetSegundoNivelActualizacion();
                }
            }
        }

        function reconstruirPerfilDesdePasos(pasos) {
            if (!pasos || !pasos.length) {
                renderPerfilacionNiveles();
                return;
            }
            window.__perfilPath = [];
            var box = document.getElementById('perfilacionNiveles');
            if (!box) return;
            box.innerHTML = '';

            /* Nivel 1 */
            var wrap0 = document.createElement('div');
            wrap0.className = 'perfilacion-col';
            wrap0.innerHTML = '<label class="perfilacion-lbl">Nivel 1 · Contactabilidad con el cliente</label>';
            var sel0 = document.createElement('select');
            sel0.className = 'form-control perfilacion-select';
            sel0.innerHTML = '<option value="">Seleccione…</option>' +
                '<option value="si">Sí — hubo contactabilidad</option><option value="no">No — sin contactabilidad</option>';
            sel0.value = pasos[0].key === 'si' || pasos[0].key === 'no' ? pasos[0].key : '';
            sel0.disabled = true;
            wrap0.appendChild(sel0);
            box.appendChild(wrap0);

            window.__perfilPath.push(pasos[0]);

            if (pasos[0].key === 'si' && pasos[1]) {
                var wrap1 = document.createElement('div');
                wrap1.className = 'perfilacion-col';
                wrap1.innerHTML = '<label class="perfilacion-lbl">Nivel 2 · Desenlace del contacto</label>';
                var sel1 = document.createElement('select');
                sel1.className = 'form-control perfilacion-select';
                sel1.innerHTML = '<option value="">Seleccione…</option>' +
                    PERFIL_RAMAS_SI.map(function(r) {
                        var selAttr = pasos[1] && pasos[1].key === r.key ? ' selected' : '';
                        return '<option value="' + r.key + '"' + selAttr + '>' + escapeHtml(r.label) + '</option>';
                    }).join('');
                sel1.disabled = true;
                wrap1.appendChild(sel1);
                box.appendChild(wrap1);

                window.__perfilPath.push(pasos[1]);
                var rama = PERFIL_RAMAS_SI.find(function(r) { return r.key === pasos[1].key; });
                if (!rama) {
                    window.__perfilPath = pasos.slice(0);
                    return;
                }
                var offset = 2;
                for (var i = 0; i < rama.pasos.length; i++) {
                    var texto = rama.pasos[i];
                    var esperado = pasos[offset + i];
                    var keyEsperado = esperado ? esperado.key : pasoKeySi(rama.key, texto, i);
                    crearColumnaUnicaPasoSoloVisual(
                        keyEsperado,
                        texto,
                        2 + i,
                        !!(esperado && (esperado.label === texto || esperado.key === keyEsperado))
                    );
                }
                window.__perfilPath = pasos.slice(0);
                return;
            }

            if (pasos[0].key === 'no') {
                var wrapLoc = document.createElement('div');
                wrapLoc.className = 'perfilacion-col';
                wrapLoc.innerHTML = '<label class="perfilacion-lbl">Nivel 2 · Localización</label>';
                var selLoc = document.createElement('select');
                selLoc.className = 'form-control perfilacion-select';
                selLoc.innerHTML = '<option value="">Seleccione…</option><option value="localizacion">Localización</option>';
                selLoc.value = pasos[1] && pasos[1].key === 'localizacion' ? 'localizacion' : '';
                selLoc.disabled = true;
                wrapLoc.appendChild(selLoc);
                box.appendChild(wrapLoc);
                if (pasos[1]) window.__perfilPath.push(pasos[1]);

                var wrap2 = document.createElement('div');
                wrap2.className = 'perfilacion-col';
                wrap2.innerHTML = '<label class="perfilacion-lbl">Nivel 3 · Canal</label>';
                var sel2 = document.createElement('select');
                sel2.className = 'form-control perfilacion-select';
                sel2.innerHTML = '<option value="">Seleccione…</option>' +
                    PERFIL_NO_CANALES.map(function(c) {
                        var selAttr = pasos[2] && pasos[2].key === c.key ? ' selected' : '';
                        return '<option value="' + c.key + '"' + selAttr + '>' + escapeHtml(c.label) + '</option>';
                    }).join('');
                sel2.disabled = true;
                wrap2.appendChild(sel2);
                box.appendChild(wrap2);
                if (pasos[2]) window.__perfilPath.push(pasos[2]);
                if (pasos[3]) {
                    crearColumnaUnicaPasoSoloVisual(pasos[3].key, pasos[3].label || 'Se agenda nuevamente interacción', 3, true);
                }
                window.__perfilPath = pasos.slice(0);
            }
        }

        function crearColumnaUnicaPasoSoloVisual(keyFinal, texto, nivelLabelIdx, seleccionado) {
            var box = document.getElementById('perfilacionNiveles');
            var wrap = document.createElement('div');
            wrap.className = 'perfilacion-col perfilacion-paso-seq';
            wrap.innerHTML = '<label class="perfilacion-lbl">Nivel ' + (nivelLabelIdx + 1) + ' · ' + escapeHtml(texto) + '</label>';
            var sel = document.createElement('select');
            sel.className = 'form-control perfilacion-select';
            sel.disabled = true;
            if (seleccionado) {
                sel.innerHTML = '<option value="' + escapeHtml(keyFinal) + '" selected>' + escapeHtml(texto) + '</option>';
            } else {
                sel.innerHTML = '<option value="">' + escapeHtml(texto) + '</option>';
            }
            wrap.appendChild(sel);
            box.appendChild(wrap);
        }

        function initPerfilacionDesdeTicket(ticket) {
            window.__perfilGuardadaParse = null;
            if (ticket.perfilacion_contactabilidad) {
                try {
                    var raw = ticket.perfilacion_contactabilidad;
                    window.__perfilGuardadaParse = typeof raw === 'string' ? JSON.parse(raw) : raw;
                } catch (e) {
                    window.__perfilGuardadaParse = null;
                }
            }
            window.__perfilActualizacionGuardada = null;
            if (ticket.perfilacion_actualizacion) {
                try {
                    var ra = ticket.perfilacion_actualizacion;
                    window.__perfilActualizacionGuardada = typeof ra === 'string' ? JSON.parse(ra) : ra;
                } catch (e2) {
                    window.__perfilActualizacionGuardada = null;
                }
            }
            window.__perfilPath = [];
            if (ticket.estado === ESTADO_CONTACTABILIDAD) {
                if (window.__perfilGuardadaParse && window.__perfilGuardadaParse.pasos) {
                    reconstruirPerfilDesdePasos(window.__perfilGuardadaParse.pasos);
                } else {
                    renderPerfilacionNiveles();
                }
            }
        }

        function renderHeaderPro(ticket) {
            var num = ticket.numero_ticket || ('#' + ticket.id);
            document.getElementById('ticketProNumero').textContent = num;

            var meta = [];
            if (ticket.cliente_nombre)   meta.push(escapeHtml(ticket.cliente_nombre));
            var phonesHdr = ticket.cliente_telefonos ? ticket.cliente_telefonos.slice() : [];
            if ((!phonesHdr.length) && ticket.cliente_telefono) {
                phonesHdr.push({ numero: ticket.cliente_telefono });
            }
            var numsHdr = phonesHdr.filter(function(p) {
                return p && String(p.numero || '').trim();
            }).map(function(p) { return String(p.numero).trim(); });
            var telMeta = numsHdr.length ? numsHdr[0] : '';
            if (telMeta) {
                if (numsHdr.length > 1) {
                    meta.push('Tel. ' + escapeHtml(telMeta) + ' · ' + numsHdr.length + ' números en ficha');
                } else {
                    meta.push('Tel. ' + escapeHtml(telMeta));
                }
            }
            if (ticket.categoria_nombre) meta.push(escapeHtml(ticket.categoria_nombre));
            if (ticket.fecha_creacion)   meta.push('Abierto: ' + new Date(ticket.fecha_creacion).toLocaleString());
            document.getElementById('ticketProMeta').innerHTML = meta.join(' · ');

            var estado = ticket.estado || '';
            var estadoClass = String(estado).replace(/[^a-z0-9_]/gi, '_');
            var badge = document.getElementById('ticketEstadoBadge');
            badge.className = 'ticket-estado-badge estado-' + estadoClass;
            badge.innerHTML = '<i class="fas fa-route"></i> ' + escapeHtml(ticket.estado_label || labelEstadoTicket(estado));

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
            var emptyEl = document.getElementById('detClienteTelefonosEmpty');
            var toolbar = document.getElementById('detClienteTelefonosToolbar');
            var sel = document.getElementById('detClienteTelefonoSelect');

            var phonesRaw = ticket.cliente_telefonos || [];
            if ((!phonesRaw || phonesRaw.length === 0) && ticket.cliente_telefono) {
                phonesRaw = [{ numero: ticket.cliente_telefono, tipo: 'other', dnc_litigator: null, orden: 1 }];
            }
            var phones = phonesRaw.slice().sort(function(a, b) {
                return (parseInt(a.orden, 10) || 999) - (parseInt(b.orden, 10) || 999);
            }).filter(function(p) {
                return p && String(p.numero || '').trim();
            });

            if (emptyEl && toolbar && sel) {
                sel.innerHTML = '';
                if (!phones.length) {
                    emptyEl.hidden = false;
                    toolbar.hidden = true;
                } else {
                    emptyEl.hidden = true;
                    toolbar.hidden = false;
                    phones.forEach(function(p) {
                        var orden = parseInt(p.orden, 10) || 1;
                        var tipoLbl = tipoTelefonoLabel(p.tipo);
                        var raw = String(p.numero || '').trim();
                        var opt = document.createElement('option');
                        opt.value = raw;
                        opt.textContent = 'Phone ' + orden + ' · ' + tipoLbl + ' · ' + raw;
                        sel.appendChild(opt);
                    });
                    bindDetalleTelefonosToolbarOnce();
                    actualizarDetalleTelefonoSeleccionVisual();
                    syncSoftphoneNumberFromTicketSelect();
                }
            }

            var mailsRaw = ticket.cliente_emails_list || [];
            if ((!mailsRaw || mailsRaw.length === 0) && ticket.cliente_email) {
                mailsRaw = [{ email: ticket.cliente_email, orden: 1 }];
            }
            var mailList = mailsRaw.slice().sort(function(a, b) {
                return (parseInt(a.orden, 10) || 999) - (parseInt(b.orden, 10) || 999);
            }).filter(function(em) {
                return em && String(em.email || '').trim();
            });

            var emptyMail = document.getElementById('detClienteEmailsEmpty');
            var toolbarMail = document.getElementById('detClienteEmailsToolbar');
            var selMail = document.getElementById('detClienteEmailSelect');

            if (emptyMail && toolbarMail && selMail) {
                selMail.innerHTML = '';
                if (!mailList.length) {
                    emptyMail.hidden = false;
                    toolbarMail.hidden = true;
                } else {
                    emptyMail.hidden = true;
                    toolbarMail.hidden = false;
                    mailList.forEach(function(m) {
                        var orden = parseInt(m.orden, 10) || 1;
                        var raw = String(m.email || '').trim();
                        var opt = document.createElement('option');
                        opt.value = raw;
                        opt.textContent = 'Email ' + orden + ' · ' + raw;
                        selMail.appendChild(opt);
                    });
                    bindDetalleEmailsToolbarOnce();
                    actualizarDetalleEmailSeleccionVisual();
                }
            }
        }

        function crearToolbarRefTelefonos(telsSorted) {
            var block = document.createElement('div');
            block.className = 'modal-ref-tel-block';
            block.appendChild(crearSubtituloModalRef('Teléfonos'));
            var toolbar = document.createElement('div');
            toolbar.className = 'detalle-telefonos-toolbar modal-ref-toolbar';

            var colSel = document.createElement('div');
            colSel.className = 'det-tel-col-select';
            var lblTel = document.createElement('span');
            lblTel.className = 'det-tel-mini-label';
            lblTel.textContent = 'Números del referido';
            var sel = document.createElement('select');
            sel.className = 'form-control det-tel-select modal-ref-tel-select';
            sel.setAttribute('aria-label', 'Teléfonos del referido');
            telsSorted.forEach(function(p) {
                var orden = parseInt(p.orden, 10) || 1;
                var tipoLbl = tipoTelefonoLabel(p.tipo);
                var raw = String(p.numero || '').trim();
                var opt = document.createElement('option');
                opt.value = raw;
                opt.textContent = 'Phone ' + orden + ' · ' + tipoLbl + ' · ' + raw;
                sel.appendChild(opt);
            });
            colSel.appendChild(lblTel);
            colSel.appendChild(sel);

            var colViz = document.createElement('div');
            colViz.className = 'det-tel-col-vista';
            var lblSel = document.createElement('span');
            lblSel.className = 'det-tel-mini-label';
            lblSel.textContent = 'Número seleccionado';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'det-tel-numero-btn modal-ref-tel-accion';
            btn.title = 'Copiar número e iniciar llamada WebRTC';
            var spanViz = document.createElement('span');
            spanViz.className = 'det-tel-numero-text modal-ref-tel-viz';
            spanViz.textContent = '—';
            var icons = document.createElement('span');
            icons.className = 'det-tel-accion-icons';
            icons.setAttribute('aria-hidden', 'true');
            icons.innerHTML = '<i class="fas fa-copy"></i><i class="fas fa-phone-alt"></i>';
            btn.appendChild(spanViz);
            btn.appendChild(icons);
            var hint = document.createElement('p');
            hint.className = 'det-tel-hint';
            hint.textContent = 'Clic para copiar al portapapeles y llamar';
            colViz.appendChild(lblSel);
            colViz.appendChild(btn);
            colViz.appendChild(hint);

            toolbar.appendChild(colSel);
            toolbar.appendChild(colViz);
            block.appendChild(toolbar);

            actualizarSeleccionVisualDesdeSelect(sel, spanViz);
            return block;
        }

        function crearToolbarRefEmails(emailsSorted) {
            var block = document.createElement('div');
            block.className = 'modal-ref-email-block';
            block.appendChild(crearSubtituloModalRef('Correos'));

            var toolbar = document.createElement('div');
            toolbar.className = 'detalle-telefonos-toolbar det-toolbar-contacto-mail modal-ref-toolbar';

            var colSel = document.createElement('div');
            colSel.className = 'det-tel-col-select';
            var lbl = document.createElement('span');
            lbl.className = 'det-tel-mini-label';
            lbl.textContent = 'Correos del referido';
            var sel = document.createElement('select');
            sel.className = 'form-control det-tel-select modal-ref-email-select';
            sel.setAttribute('aria-label', 'Correos del referido');
            emailsSorted.forEach(function(em) {
                var orden = parseInt(em.orden, 10) || 1;
                var raw = String(em.email || '').trim();
                var opt = document.createElement('option');
                opt.value = raw;
                opt.textContent = 'Email ' + orden + ' · ' + raw;
                sel.appendChild(opt);
            });
            colSel.appendChild(lbl);
            colSel.appendChild(sel);

            var colViz = document.createElement('div');
            colViz.className = 'det-tel-col-vista';
            var lblSel = document.createElement('span');
            lblSel.className = 'det-tel-mini-label';
            lblSel.textContent = 'Correo seleccionado';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'det-tel-numero-btn det-mail-copy-btn modal-ref-email-accion';
            btn.title = 'Copiar correo seleccionado al portapapeles';
            var spanViz = document.createElement('span');
            spanViz.className = 'det-tel-numero-text modal-ref-email-viz';
            spanViz.textContent = '—';
            var icons = document.createElement('span');
            icons.className = 'det-tel-accion-icons';
            icons.setAttribute('aria-hidden', 'true');
            icons.innerHTML = '<i class="fas fa-copy"></i><i class="fas fa-envelope"></i>';
            btn.appendChild(spanViz);
            btn.appendChild(icons);
            var hint = document.createElement('p');
            hint.className = 'det-tel-hint';
            hint.textContent = 'Clic para copiar al portapapeles';
            colViz.appendChild(lblSel);
            colViz.appendChild(btn);
            colViz.appendChild(hint);

            toolbar.appendChild(colSel);
            toolbar.appendChild(colViz);
            block.appendChild(toolbar);

            actualizarSeleccionVisualDesdeSelect(sel, spanViz);
            return block;
        }

        function crearSubtituloModalRef(texto) {
            var h = document.createElement('h5');
            h.className = 'modal-ref-section-title';
            h.textContent = texto;
            return h;
        }

        function ordenarTelefonosRef(list) {
            return (list || []).slice().sort(function(a, b) {
                return (parseInt(a.orden, 10) || 999) - (parseInt(b.orden, 10) || 999);
            }).filter(function(p) {
                return p && String(p.numero || '').trim();
            });
        }

        function ordenarEmailsRef(list) {
            return (list || []).slice().sort(function(a, b) {
                return (parseInt(a.orden, 10) || 999) - (parseInt(b.orden, 10) || 999);
            }).filter(function(em) {
                return em && String(em.email || '').trim();
            });
        }

        function bindReferenciasModalDelegatedOnce() {
            var host = document.getElementById('referenciasModalBody');
            if (!host || host.dataset.refToolbarDelBound === '1') return;
            host.dataset.refToolbarDelBound = '1';
            host.addEventListener('change', function(ev) {
                var t = ev.target;
                if (!(t instanceof HTMLElement)) return;
                var telBlk = t.closest('.modal-ref-tel-block');
                if (telBlk && t.classList.contains('modal-ref-tel-select')) {
                    actualizarSeleccionVisualDesdeSelect(t, telBlk.querySelector('.modal-ref-tel-viz'));
                }
                var mailBlk = t.closest('.modal-ref-email-block');
                if (mailBlk && t.classList.contains('modal-ref-email-select')) {
                    actualizarSeleccionVisualDesdeSelect(t, mailBlk.querySelector('.modal-ref-email-viz'));
                }
            });
            host.addEventListener('click', function(ev) {
                var telBtn = ev.target.closest ? ev.target.closest('.modal-ref-tel-accion') : null;
                if (telBtn) {
                    ev.preventDefault();
                    var blk = telBtn.closest('.modal-ref-tel-block');
                    var s = blk ? blk.querySelector('.modal-ref-tel-select') : null;
                    ejecutarCopiarYLlamarTelDesdeSelect(s);
                    return;
                }
                var mailBtn = ev.target.closest ? ev.target.closest('.modal-ref-email-accion') : null;
                if (mailBtn) {
                    ev.preventDefault();
                    var mb = mailBtn.closest('.modal-ref-email-block');
                    var ms = mb ? mb.querySelector('.modal-ref-email-select') : null;
                    ejecutarCopiarEmailDesdeSelect(ms);
                }
            });
        }

        function renderReferenciasModalBody(ticket) {
            var body = document.getElementById('referenciasModalBody');
            if (!body) return;
            bindReferenciasModalDelegatedOnce();

            body.innerHTML = '';

            var refs = (ticket && ticket.referencias_personales) ? ticket.referencias_personales : [];
            if (!refs.length) {
                var p = document.createElement('p');
                p.className = 'modal-ref-empty';
                p.textContent = 'No hay referencias registradas para este cliente.';
                body.appendChild(p);
                return;
            }

            refs.forEach(function(ref, idx) {
                var ordenRel = parseInt(ref.orden, 10) || (idx + 1);
                var nombre = [ref.nombre, ref.apellido].filter(Boolean).join(' ').trim() || 'Sin nombre';

                var card = document.createElement('article');
                card.className = 'modal-ref-card';

                var hTitle = document.createElement('h4');
                hTitle.className = 'modal-ref-card-title';
                hTitle.textContent = 'RELATIVE ' + ordenRel + ' · ' + nombre.replace(/\s+/g, ' ');
                card.appendChild(hTitle);

                var tieneTipo = ref.possible_type && String(ref.possible_type).trim() !== '';
                var tieneEdad = ref.age !== null && ref.age !== undefined && String(ref.age).trim() !== '';
                if (tieneTipo || tieneEdad) {
                    var meta = document.createElement('p');
                    meta.className = 'modal-ref-meta';
                    meta.textContent = (tieneTipo ? 'Tipo: ' + ref.possible_type : '') +
                        (tieneTipo && tieneEdad ? ' · ' : '') +
                        (tieneEdad ? 'Edad: ' + ref.age : '');
                    card.appendChild(meta);
                }

                var telsSorted = ordenarTelefonosRef(ref.telefonos || []);
                if (!telsSorted.length) {
                    card.appendChild(crearSubtituloModalRef('Teléfonos'));
                    var pSin = document.createElement('p');
                    pSin.className = 'modal-ref-subtle';
                    pSin.textContent = 'Sin teléfonos';
                    card.appendChild(pSin);
                } else {
                    card.appendChild(crearToolbarRefTelefonos(telsSorted));
                }

                var mailsSorted = ordenarEmailsRef(ref.emails || []);
                if (!mailsSorted.length) {
                    card.appendChild(crearSubtituloModalRef('Correos'));
                    var pSinE = document.createElement('p');
                    pSinE.className = 'modal-ref-subtle';
                    pSinE.textContent = 'Sin correos';
                    card.appendChild(pSinE);
                } else {
                    card.appendChild(crearToolbarRefEmails(mailsSorted));
                }

                body.appendChild(card);
            });
        }

        function abrirModalReferencias() {
            var modal = document.getElementById('modalReferencias');
            var body = document.getElementById('referenciasModalBody');
            if (!modal || !body) return;
            renderReferenciasModalBody(window.__ticketActual || {});
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
            if (actual === 'desembolso' || permitidas.length === 0) {
                sel.disabled = true;
                if (help) {
                    help.innerHTML = '<i class="fas fa-lock"></i> El caso está en estado terminal (desembolso): no admite más cambios de estado.';
                }
            } else {
                sel.disabled = false;
            }
        }

        function updateTimelineEstadoActual(estadoActual) {
            var badge = document.getElementById('ticketTimelineEstadoBadge');
            if (!badge) return;
            var key = (estadoActual || '').trim();
            var lab = labelEstadoTicket(key) || (key || '—');
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
                var labelNuevo = h.estado_guardado_label || h.estado_label || labelEstadoTicket(h.estado_nuevo) || h.estado_nuevo;
                var labelAnt = h.estado_anterior_label || (h.estado_anterior ? (labelEstadoTicket(h.estado_anterior) || h.estado_anterior) : null);
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

            if (estado === ESTADO_CONTACTABILIDAD) {
                if (!isPerfilContactabilidadCompleta()) {
                    showMessage('Complete la tipificación de contactabilidad (todos los niveles).', 'error');
                    return;
                }
            } else {
                var selActP = document.getElementById('perfilActualizacionRespuesta');
                var vr = selActP ? String(selActP.value || '').trim() : '';
                if (vr !== 'si' && vr !== 'no') {
                    showMessage('Seleccione Sí o No en la tipificación de actualización o escalamiento para este estado.', 'error');
                    return;
                }
                var selConfP = document.getElementById('perfilActualizacionConfirmacion');
                var vc = selConfP ? String(selConfP.value || '').trim() : '';
                var desG = estado === ESTADO_DESEMBOLSO;
                var tG = getTextosActualizacion(desG);
                var esperadoSeg = vr === 'si' ? tG.si : tG.no;
                if (!vc || vc !== esperadoSeg) {
                    showMessage('Complete el segundo desplegable con la acción que sigue a su elección (Sí / No).', 'error');
                    return;
                }
                var px = String(document.getElementById('proximaAccion').value || '').trim();
                var fx = String(document.getElementById('fechaProximaAccion').value || '').trim();
                if (!px || !fx) {
                    showMessage('Indique «Próxima acción» y «Fecha de próxima acción» (obligatorios para dejar constancia de la tipificación en el historial).', 'error');
                    return;
                }
            }

            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            formData.append('estado', estado);

            if (estado === ESTADO_CONTACTABILIDAD && isPerfilContactabilidadCompleta()) {
                formData.append('perfilacion_contactabilidad', JSON.stringify({
                    tipo: 'contactabilidad',
                    version: 1,
                    pasos: window.__perfilPath
                }));
            }

            if (estado !== ESTADO_CONTACTABILIDAD) {
                var selAct2 = document.getElementById('perfilActualizacionRespuesta');
                var selConf2 = document.getElementById('perfilActualizacionConfirmacion');
                var rAct = selAct2 ? String(selAct2.value || '').trim() : '';
                var cAct = selConf2 ? String(selConf2.value || '').trim() : '';
                if (rAct === 'si' || rAct === 'no') {
                    formData.append('perfilacion_actualizacion', JSON.stringify({
                        version: 2,
                        respuesta: rAct,
                        accion_confirmada: cAct
                    }));
                }
            }


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
                const response = await fetch('api/gestionar_ticket.php', {
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
                            window.appGo('asesor_tickets');
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
                const response = await fetch('api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
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
