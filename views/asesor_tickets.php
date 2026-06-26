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
    <title>Mis tickets CRM - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/asesor.css" rel="stylesheet">
    <link href="css/tickets.css" rel="stylesheet">
    <link href="css/asesor-bell.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                        <h4><?php echo $user['nombre'] . ' ' . $user['apellido']; ?></h4>
                        <p>Asesor</p>
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
            <div class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="welcome-section">
                        <h1>Mis tickets CRM</h1>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar tickets..." id="searchInput">
                    </div>
                    <?php require __DIR__ . '/partials/asesor_navbar_bell.php'; ?>
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
                    <button class="btn btn-secondary" onclick="loadTickets()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total</div>
                            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalTickets">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Contactabilidad</div>
                            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;"><i class="fas fa-phone-volume"></i></div>
                        </div>
                        <div class="stat-value" id="statComunicacion">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Acuerdo comercial</div>
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;"><i class="fas fa-handshake"></i></div>
                        </div>
                        <div class="stat-value" id="statValidacion">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Docs. a la corte</div>
                            <div class="stat-icon" style="background: rgba(168, 85, 247, 0.2); color: #a855f7;"><i class="fas fa-landmark"></i></div>
                        </div>
                        <div class="stat-value" id="statProcesoJudicial">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Docs. adicionales</div>
                            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-file-alt"></i></div>
                        </div>
                        <div class="stat-value" id="statRemate">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Giro · Swift · etc.</div>
                            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;"><i class="fas fa-coins"></i></div>
                        </div>
                        <div class="stat-value" id="statRecuperacion">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Desembolso</div>
                            <div class="stat-icon" style="background: rgba(107, 114, 128, 0.2); color: #6b7280;"><i class="fas fa-flag-checkered"></i></div>
                        </div>
                        <div class="stat-value" id="statCierre">0</div>
                    </div>
                </div>

                <div class="filters-section">
                    <div class="filter-group">
                        <label>Filtrar por estado:</label>
                        <select id="filtroEstado" onchange="filtrarTickets()">
                            <option value="">Todos</option>
                            <option value="contactabilidad_cliente">Contactabilidad con el cliente</option>
                            <option value="acuerdo_comercial">Acuerdo comercial</option>
                            <option value="documentos_corte">Documentos a la corte</option>
                            <option value="documentos_adicionales">Documentos adicionales</option>
                            <option value="corte_giro_saldo">Corte giró saldo</option>
                            <option value="cliente_swift">Cliente generó Swift</option>
                            <option value="desembolso">Desembolso</option>
                            <option disabled>────────── Histórico ──────────</option>
                            <option value="comunicacion">Comunicación (histórico)</option>
                            <option value="validacion">Validación (histórico)</option>
                            <option value="proceso_judicial">Proceso judicial (histórico)</option>
                            <option value="remate">Remate (histórico)</option>
                            <option value="recuperacion">Recuperación (histórico)</option>
                            <option value="cierre">Cierre (histórico)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Ordenar por:</label>
                        <select id="ordenarPor" onchange="filtrarTickets()">
                            <option value="fecha_creacion">Fecha de creación</option>
                            <option value="estado">Estado</option>
                            <option value="numero_ticket">Número de ticket</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lista de tickets -->
                <div class="tickets-section">
                    <div class="section-header">
                        <h2 class="section-title">Lista de Tickets</h2>
                        <div class="tickets-count">
                            <span id="ticketsCount">0 tickets encontrados</span>
                        </div>
                    </div>
                    <div id="ticketsList" class="tickets-container">
                        <!-- Los tickets se cargarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal detalle ticket (tema CRM / reparto) -->
    <div id="ticketDetalleModal" class="modal ticket-detalle-modal" role="dialog" aria-modal="true" aria-labelledby="ticketDetalleTitle" aria-hidden="true">
        <div class="ticket-detalle-modal-dialog" role="document">
            <div class="modal-header">
                <h3 class="modal-title" id="ticketDetalleTitle"><i class="fas fa-hashtag"></i> Detalle del ticket</h3>
                <span class="close" onclick="cerrarModalDetalleTicket()" aria-label="Cerrar">&times;</span>
            </div>
            <div id="ticketDetalleContent" class="modal-body ticket-detalle-modal-body">
                <p class="ticket-detalle-loading"><i class="fas fa-spinner fa-spin"></i> Cargando detalle…</p>
            </div>
            <div class="modal-footer ticket-detalle-modal-footer" id="ticketDetalleFooter" style="display: none;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalDetalleTicket()">Cerrar</button>
                <a id="ticketDetalleBtnGestionar" href="#" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Gestionar ticket
                </a>
            </div>
        </div>
    </div>

    <script src="assets/js/asesor-llamadas-hoy.js"></script>
    <script src="assets/js/ticket-detalle-modal.js"></script>
    <script src="assets/js/ticket-notas-render.js"></script>
    <script>
        let tickets = [];
        let ticketsFiltrados = [];
        let clientes = [];

        const ESTADO_LABELS = {
            contactabilidad_cliente: 'Contactabilidad con el cliente',
            acuerdo_comercial: 'Acuerdo comercial',
            documentos_corte: 'Documentos a la corte',
            documentos_adicionales: 'Documentos adicionales',
            corte_giro_saldo: 'Corte giró saldo',
            cliente_swift: 'Cliente Swift',
            desembolso: 'Desembolso',
            comunicacion: 'Comunicación',
            validacion: 'Validación',
            proceso_judicial: 'Proceso judicial',
            remate: 'Remate',
            recuperacion: 'Recuperación',
            cierre: 'Cierre'
        };
        const ESTADO_ICONS = {
            contactabilidad_cliente: 'fas fa-phone-volume',
            acuerdo_comercial: 'fas fa-handshake',
            documentos_corte: 'fas fa-landmark',
            documentos_adicionales: 'fas fa-file-alt',
            corte_giro_saldo: 'fas fa-money-bill-wave',
            cliente_swift: 'fas fa-exchange-alt',
            desembolso: 'fas fa-flag-checkered',
            comunicacion: 'fas fa-comments',
            validacion: 'fas fa-clipboard-check',
            proceso_judicial: 'fas fa-gavel',
            remate: 'fas fa-hammer',
            recuperacion: 'fas fa-coins',
            cierre: 'fas fa-flag-checkered'
        };

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        const INITIAL_CLIENTE_FILTER = <?php echo json_encode(app_route_param('cliente', ''), JSON_UNESCAPED_UNICODE); ?>;

        // Inicializar página
        document.addEventListener('DOMContentLoaded', async function() {
            const clienteCedula = INITIAL_CLIENTE_FILTER || null;
            await loadClientes();
            if (clienteCedula) {
                mostrarFiltroCliente(clienteCedula);
                loadTickets(clienteCedula);
            } else {
                loadTickets();
            }
        });

        // Cargar tickets
        async function loadTickets(clienteCedula = null) {
            try {
                let url = 'api/tickets_asesor.php';
                if (clienteCedula) {
                    url += `?cliente=${clienteCedula}`;
                }

                const response = await fetch(url);

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    tickets = result.data;
                    ticketsFiltrados = [...tickets];
                    renderTickets();
                    updateStats();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando tickets:', error);
                showMessage('Error cargando tickets', 'error');
            }
        }

        // Cargar clientes
        async function loadClientes() {
            try {
                const response = await fetch('api/asesor_clientes.php');

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

                const result = await response.json();

                if (result.success) {
                    clientes = result.data;
                } else {
                    console.error('Error cargando clientes:', result.message);
                    showMessage('Error cargando clientes: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando clientes:', error);
                showMessage('Error cargando clientes', 'error');
            }
        }

        async function mostrarFiltroCliente(clienteCedula) {
            try {
                const cliente = clientes.find(c => c.cedula === clienteCedula);
                const clienteNombre = cliente ? (cliente.nombre_completo || `${cliente.nombre || ''} ${cliente.apellido || ''}`.trim()) : `Cliente ${clienteCedula}`;
                
                // Crear indicador de filtro
                const filtroDiv = document.createElement('div');
                filtroDiv.className = 'filtro-cliente-indicator';
                filtroDiv.innerHTML = `
                    <div class="filtro-cliente-content">
                        <div class="filtro-cliente-info">
                            <i class="fas fa-filter"></i>
                            <span>Mostrando tickets de: <strong>${clienteNombre}</strong></span>
                        </div>
                        <button class="btn btn-sm btn-secondary" onclick="limpiarFiltroCliente()">
                            <i class="fas fa-times"></i> Ver Todos los Tickets
                        </button>
                    </div>
                `;
                
                // Insertar después del header
                const header = document.querySelector('.page-header');
                header.insertAdjacentElement('afterend', filtroDiv);
                
            } catch (error) {
                console.error('Error mostrando filtro de cliente:', error);
            }
        }

        // Limpiar filtro de cliente
        function limpiarFiltroCliente() {
            window.appGo('asesor_tickets');
        }

        // Renderizar tickets
        function renderTickets() {
            const container = document.getElementById('ticketsList');
            container.innerHTML = '';

            if (ticketsFiltrados.length === 0) {
                container.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 40px;">No hay tickets</p>';
                return;
            }

            ticketsFiltrados.forEach(ticket => {
                const ticketCard = document.createElement('div');
                ticketCard.className = 'ticket-card estado-' + String(ticket.estado || '').replace(/[^a-z0-9_]/gi, '_');

                const refTicket   = ticket.numero_ticket ? escapeHtml(ticket.numero_ticket) : ('#' + ticket.id);
                const catLine     = ticket.categoria_nombre ? `<p><strong>Categoría:</strong> ${escapeHtml(ticket.categoria_nombre)}</p>` : '';
                const estadoLabel = ESTADO_LABELS[ticket.estado] || ticket.estado || '';
                const estadoIcon  = ESTADO_ICONS[ticket.estado] || 'fas fa-circle';

                ticketCard.innerHTML = `
                    <div class="ticket-header">
                        <div class="ticket-info">
                            <h3>${refTicket}</h3>
                            ${ticket.titulo ? `<p class="ticket-asunto">${escapeHtml(ticket.titulo)}</p>` : ''}
                            ${catLine}
                            <p><strong>Cliente:</strong> ${escapeHtml(ticket.cliente_nombre || '')}</p>
                            ${ticket.cliente_telefono ? `<p><strong>Teléfono:</strong> ${escapeHtml(ticket.cliente_telefono)}</p>` : ''}
                        </div>
                        <div class="ticket-status">
                            <span class="ticket-estado-badge estado-${String(ticket.estado || '').replace(/[^a-z0-9_]/gi, '_')}">
                                <i class="${estadoIcon}"></i> ${escapeHtml(estadoLabel)}
                            </span>
                        </div>
                    </div>
                    <div class="ticket-body">
                        <p><strong>Descripción:</strong> ${escapeHtml(ticket.descripcion || 'Sin descripción')}</p>
                        <p><strong>Fecha creación:</strong> ${new Date(ticket.fecha_creacion).toLocaleString()}</p>
                        ${ticket.fecha_cierre ? `<p><strong>Fecha cierre:</strong> ${new Date(ticket.fecha_cierre).toLocaleString()}</p>` : ''}
                        ${ticket.pdf_archivo ? `
                            <div class="ticket-pdf">
                                <i class="fas fa-file-pdf"></i>
                                <a href="${archivoTicketUrl(ticket.pdf_archivo, { ticketId: ticket.id })}" target="_blank" rel="noopener" class="pdf-link">Ver PDF adjunto</a>
                            </div>
                        ` : ''}
                    </div>
                    <div class="ticket-actions">
                        <button class="btn btn-sm btn-info" onclick="verDetalleTicket(${ticket.id})">
                            <i class="fas fa-eye"></i> Ver detalle
                        </button>
                        ${(ticket.estado !== 'desembolso' && ticket.estado !== 'cierre') ? `
                            <a href="${appNav('asesor_gestionar_ticket', {id: ticket.id})}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Gestionar
                            </a>
                        ` : `
                            <span class="btn btn-sm btn-secondary disabled">
                                <i class="fas fa-lock"></i> Caso cerrado
                            </span>
                        `}
                    </div>
                `;

                container.appendChild(ticketCard);
            });

            // Actualizar contador
            document.getElementById('ticketsCount').textContent = `${ticketsFiltrados.length} tickets encontrados`;
        }

        function incrementEstadoBucket(counts, estado) {
            const e = estado || '';
            if (e === 'contactabilidad_cliente' || e === 'comunicacion') counts.comunicacion++;
            else if (e === 'acuerdo_comercial' || e === 'validacion') counts.validacion++;
            else if (e === 'documentos_corte' || e === 'proceso_judicial') counts.proceso_judicial++;
            else if (e === 'documentos_adicionales' || e === 'remate') counts.remate++;
            else if (e === 'corte_giro_saldo' || e === 'cliente_swift' || e === 'recuperacion') counts.recuperacion++;
            else if (e === 'desembolso' || e === 'cierre') counts.cierre++;
        }

        function updateStats() {
            const total = tickets.length;
            const counts = {
                comunicacion: 0, validacion: 0, proceso_judicial: 0,
                remate: 0, recuperacion: 0, cierre: 0
            };
            tickets.forEach(t => incrementEstadoBucket(counts, t.estado));
            document.getElementById('totalTickets').textContent = total;
            document.getElementById('statComunicacion').textContent     = counts.comunicacion;
            document.getElementById('statValidacion').textContent       = counts.validacion;
            document.getElementById('statProcesoJudicial').textContent  = counts.proceso_judicial;
            document.getElementById('statRemate').textContent           = counts.remate;
            document.getElementById('statRecuperacion').textContent     = counts.recuperacion;
            document.getElementById('statCierre').textContent           = counts.cierre;
        }

        function filtrarTickets() {
            const estado  = document.getElementById('filtroEstado').value;
            const ordenar = document.getElementById('ordenarPor').value;

            ticketsFiltrados = [...tickets];

            if (estado) {
                ticketsFiltrados = ticketsFiltrados.filter(t => t.estado === estado);
            }

            const estadoOrder = {
                contactabilidad_cliente: 1,
                comunicacion: 1,
                acuerdo_comercial: 2,
                validacion: 2,
                documentos_corte: 3,
                proceso_judicial: 3,
                documentos_adicionales: 4,
                remate: 4,
                corte_giro_saldo: 5,
                cliente_swift: 5,
                recuperacion: 5,
                desembolso: 6,
                cierre: 6
            };
            ticketsFiltrados.sort((a, b) => {
                switch (ordenar) {
                    case 'fecha_creacion':
                        return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
                    case 'estado':
                        return (estadoOrder[a.estado] || 99) - (estadoOrder[b.estado] || 99);
                    case 'numero_ticket':
                        return String(a.numero_ticket || '').localeCompare(String(b.numero_ticket || ''));
                    default:
                        return 0;
                }
            });

            renderTickets();
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const termino = e.target.value.toLowerCase();
            if (termino) {
                ticketsFiltrados = tickets.filter(t =>
                    String(t.titulo || '').toLowerCase().includes(termino) ||
                    String(t.descripcion || '').toLowerCase().includes(termino) ||
                    String(t.cliente_nombre || '').toLowerCase().includes(termino) ||
                    String(t.numero_ticket || '').toLowerCase().includes(termino)
                );
            } else {
                ticketsFiltrados = [...tickets];
            }
            renderTickets();
        });

        // Actualizar estado del ticket
        async function actualizarTicketEstado(ticketId, nuevoEstado) {
            const observaciones = prompt('Observaciones (opcional):');
            
            try {
                const response = await fetch('api/actualizar_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        estado: nuevoEstado,
                        observaciones: observaciones
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Estado del ticket actualizado', 'success');
                    loadTickets();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error actualizando ticket:', error);
                showMessage('Error actualizando ticket', 'error');
            }
        }

        function hasDetalleValor(value) {
            return value !== null && value !== undefined && String(value).trim() !== '' && String(value).trim() !== '—';
        }

        function archivoTicketUrl(ruta, opts) {
            opts = opts || {};
            var base = (typeof APP_HOME !== 'undefined' && APP_HOME) ? APP_HOME : '';
            if (opts.archivoId) {
                return base + 'api/ver_ticket_pdf.php?archivo_id=' + encodeURIComponent(opts.archivoId);
            }
            if (opts.ticketId) {
                return base + 'api/ver_ticket_pdf.php?ticket_id=' + encodeURIComponent(opts.ticketId);
            }
            if (!ruta) return '#';
            var rel = String(ruta).replace(/^\.\.\//, '').replace(/^\//, '');
            return base + rel;
        }

        function puedeGestionarTicket(estado) {
            return estado !== 'desembolso' && estado !== 'cierre';
        }

        function labelEstadoTicket(estado) {
            return ESTADO_LABELS[estado] || estado || '—';
        }

        function badgeEstadoHtml(estado, labelOverride) {
            const est = String(estado || '');
            const cls = 'estado-' + est.replace(/[^a-z0-9_]/gi, '_');
            const icon = ESTADO_ICONS[est] || 'fas fa-circle';
            const lbl = escapeHtml(labelOverride || labelEstadoTicket(est));
            return '<span class="ticket-estado-badge ' + cls + '"><i class="' + icon + '"></i> ' + lbl + '</span>';
        }

        function renderInfoGrid(items) {
            const rows = items.filter(function(it) { return it && (it.html || hasDetalleValor(it.value)); });
            if (!rows.length) return '';
            return '<div class="info-grid">' + rows.map(function(it) {
                const val = it.html != null ? it.html : escapeHtml(String(it.value));
                return '<div class="info-item"><span class="info-label">' + escapeHtml(it.label) + '</span><span class="info-value">' + val + '</span></div>';
            }).join('') + '</div>';
        }

        function renderListaContacto(items, campo) {
            if (!items || !items.length) return '<p class="ticket-detalle-empty">Sin registros</p>';
            return '<ul class="ticket-detalle-lista">' + items.map(function(it) {
                const txt = escapeHtml(String(it[campo] || '').trim());
                const extra = it.tipo ? ' <span class="ticket-detalle-meta">(' + escapeHtml(String(it.tipo)) + ')</span>' : '';
                return '<li>' + txt + extra + '</li>';
            }).join('') + '</ul>';
        }

        function renderHistorialEstadoHtml(historial) {
            if (!historial || !historial.length) {
                return '<p class="ticket-detalle-empty">Sin cambios de estado registrados</p>';
            }
            return '<div class="ticket-detalle-timeline">' + historial.map(function(h) {
                const est = h.estado_nuevo || h.estado || '';
                const asesor = [h.asesor_nombre, h.asesor_apellido].filter(Boolean).join(' ').trim();
                const obs = hasDetalleValor(h.observacion) ? '<p class="ticket-detalle-obs">' + escapeHtml(h.observacion) + '</p>' : '';
                const ant = hasDetalleValor(h.estado_anterior)
                    ? '<span class="ticket-detalle-meta">Desde ' + escapeHtml(labelEstadoTicket(h.estado_anterior)) + '</span>' : '';
                return '<div class="ticket-detalle-timeline-item">' +
                    '<div class="ticket-detalle-timeline-head">' +
                    '<span class="ticket-detalle-timeline-fecha">' + escapeHtml(new Date(h.fecha_cambio).toLocaleString()) + '</span>' +
                    badgeEstadoHtml(est) +
                    '</div>' + ant +
                    (asesor ? '<span class="ticket-detalle-meta">Por ' + escapeHtml(asesor) + '</span>' : '') +
                    obs +
                    '</div>';
            }).join('') + '</div>';
        }

        function actualizarFooterDetalleModal(ticket) {
            const footer = document.getElementById('ticketDetalleFooter');
            const btnG = document.getElementById('ticketDetalleBtnGestionar');
            if (!footer || !btnG) return;
            footer.style.display = 'flex';
            if (puedeGestionarTicket(ticket.estado)) {
                btnG.href = appNav('asesor_gestionar_ticket', { id: ticket.id });
                btnG.style.display = 'inline-flex';
            } else {
                btnG.style.display = 'none';
            }
        }

        // Ver detalle del ticket
        async function verDetalleTicket(ticketId) {
            const ticket = tickets.find(function(t) { return Number(t.id) === Number(ticketId); });
            if (!ticket) return;

            const modal = document.getElementById('ticketDetalleModal');
            const title = document.getElementById('ticketDetalleTitle');
            const content = document.getElementById('ticketDetalleContent');
            const footer = document.getElementById('ticketDetalleFooter');

            if (footer) footer.style.display = 'none';
            content.innerHTML = '<p class="ticket-detalle-loading"><i class="fas fa-spinner fa-spin"></i> Cargando detalle…</p>';
            if (window.TicketDetalleModal) {
                TicketDetalleModal.setTitulo(ticket, escapeHtml);
                TicketDetalleModal.open();
            } else {
                title.innerHTML = '<i class="fas fa-hashtag"></i> ' + escapeHtml(ticket.numero_ticket || ('Ticket #' + ticket.id));
                modal.classList.add('is-open');
            }

            try {
                const response = await fetch('api/ticket_detalle_completo.php?ticket_id=' + encodeURIComponent(ticketId), {
                    credentials: 'same-origin'
                });
                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('HTTP ' + response.status);
                }
                const result = await response.json();

                if (result.success && result.data) {
                    mostrarDetalleCompleto(result.data);
                } else {
                    mostrarDetalleBasico(ticket);
                }
            } catch (error) {
                console.error('Error cargando detalle completo:', error);
                mostrarDetalleBasico(ticket);
            }
        }

        function mostrarDetalleCompleto(ticket) {
            const content = document.getElementById('ticketDetalleContent');
            const pr = ticket.propiedad_reparto || null;
            const tit = ticket.titular_reparto || null;
            const pred = ticket.predio || null;
            const tels = ticket.cliente_telefonos && ticket.cliente_telefonos.length
                ? ticket.cliente_telefonos
                : (hasDetalleValor(ticket.cliente_telefono) ? [{ numero: ticket.cliente_telefono, tipo: 'principal' }] : []);
            const emails = ticket.cliente_emails_list && ticket.cliente_emails_list.length
                ? ticket.cliente_emails_list
                : (hasDetalleValor(ticket.cliente_email) ? [{ email: ticket.cliente_email }] : []);

            let html = '<div class="ticket-detalle-completo" data-ticket-id="' + escapeHtml(String(ticket.id)) + '">';

            html += '<div class="info-section ticket-detalle-resumen">';
            html += '<h4><i class="fas fa-ticket-alt"></i> Ticket CRM</h4>';
            html += renderInfoGrid([
                { label: 'Referencia', value: ticket.numero_ticket || ('#' + ticket.id) },
                { label: 'ID interno', value: '#' + ticket.id },
                { label: 'Estado', html: badgeEstadoHtml(ticket.estado, ticket.estado_label) },
                { label: 'Categoría', value: ticket.categoria_nombre },
                { label: 'Creado', value: ticket.fecha_creacion ? new Date(ticket.fecha_creacion).toLocaleString() : '' },
                { label: 'Estado desde', value: ticket.estado_actual_desde ? new Date(ticket.estado_actual_desde).toLocaleString() : '' },
                { label: 'Tiempo en gestión', value: ticket.tiempo_total_legible },
                { label: 'Cierre', value: ticket.fecha_cierre ? new Date(ticket.fecha_cierre).toLocaleString() : '' }
            ]);
            html += '</div>';

            if (pr || tit) {
                html += '<div class="info-section">';
                html += '<h4><i class="fas fa-home"></i> Caso reparto (CSV)</h4>';
                if (tit) {
                    const nomTit = [tit.primer_nombre, tit.apellido].filter(Boolean).join(' ').trim();
                    html += renderInfoGrid([
                        { label: 'Titular', value: nomTit },
                        { label: 'Prioridad', value: tit.prioridad },
                        { label: 'Mailing', value: [tit.mailing_calle, tit.mailing_ciudad, tit.mailing_estado, tit.mailing_codigo_postal].filter(Boolean).join(', ') }
                    ]);
                }
                if (pr) {
                    html += renderInfoGrid([
                        { label: 'Case Number', value: pr.numero_caso },
                        { label: 'Parcel Number', value: pr.numero_parcela },
                        { label: 'Tipo foreclosure', value: pr.tipo_foreclosure },
                        { label: 'Propiedad', value: [pr.propiedad_calle, pr.propiedad_ciudad, pr.propiedad_estado].filter(Boolean).join(', ') },
                        { label: 'Condado', value: pr.condado },
                        { label: 'Fuente', value: pr.fuente },
                        { label: 'Días mora (activos)', value: pr.dias_mora_activos != null ? String(pr.dias_mora_activos) : '' }
                    ]);
                }
                html += '</div>';
            }

            if (pred) {
                html += '<div class="info-section">';
                html += '<h4><i class="fas fa-building"></i> Predio (ticket)</h4>';
                html += renderInfoGrid([
                    { label: 'Case Number', value: pred.case_number },
                    { label: 'Parcel Number', value: pred.parcel_number },
                    { label: 'Foreclosure', value: pred.type_of_foreclosure },
                    { label: 'Dirección', value: [pred.property_street, pred.property_city, pred.property_state, pred.property_zip].filter(Boolean).join(', ') },
                    { label: 'Condado', value: pred.county },
                    { label: 'Valor a devolver', value: pred.valor_a_devolver },
                    { label: 'Fecha venta', value: pred.date_sold }
                ]);
                html += '</div>';
            }

            html += '<div class="info-section">';
            html += '<h4><i class="fas fa-user"></i> Cliente / contacto</h4>';
            html += renderInfoGrid([
                { label: 'Identificador', value: ticket.cliente_cedula },
                { label: 'Nombre', value: ticket.cliente_nombre },
                { label: 'Dirección', value: ticket.cliente_direccion },
                { label: 'Ciudad', value: ticket.cliente_ciudad }
            ]);
            html += '<p class="ticket-detalle-sub"><strong>Teléfonos</strong></p>';
            html += renderListaContacto(tels, 'numero');
            html += '<p class="ticket-detalle-sub"><strong>Correos</strong></p>';
            html += renderListaContacto(emails, 'email');
            html += '</div>';

            if (hasDetalleValor(ticket.descripcion)) {
                html += '<div class="info-section"><h4><i class="fas fa-align-left"></i> Descripción</h4>';
                html += '<div class="ticket-description">' + escapeHtml(ticket.descripcion) + '</div></div>';
            }
            if (hasDetalleValor(ticket.observaciones)) {
                html += '<div class="info-section"><h4><i class="fas fa-sticky-note"></i> Observaciones</h4>';
                html += '<div class="ticket-observations">' + escapeHtml(ticket.observaciones) + '</div></div>';
            }

            html += '<div class="info-section"><h4><i class="fas fa-route"></i> Historial de estados</h4>';
            html += renderHistorialEstadoHtml(ticket.historial_estado);
            html += '</div>';

            html += '<div class="info-section"><h4><i class="fas fa-comments"></i> Notas del ticket</h4>';
            html += '<div id="historialDetalle" class="historial-detalle"><p class="ticket-detalle-loading"><i class="fas fa-spinner fa-spin"></i> Cargando notas…</p></div></div>';

            const archivos = ticket.archivos_pdf && ticket.archivos_pdf.length ? ticket.archivos_pdf : [];
            if (hasDetalleValor(ticket.pdf_archivo)) {
                archivos.unshift({ nombre_archivo: 'PDF principal', ruta_archivo: ticket.pdf_archivo, fecha_subida: ticket.fecha_creacion });
            }
            if (archivos.length) {
                html += '<div class="info-section"><h4><i class="fas fa-paperclip"></i> Archivos (' + archivos.length + ')</h4><div class="ticket-archivos">';
                archivos.forEach(function(archivo) {
                    const url = archivoTicketUrl(archivo.ruta_archivo, {
                        archivoId: archivo.id,
                        ticketId: ticket.id
                    });
                    html += '<div class="archivo-item"><div class="archivo-info"><i class="fas fa-file-pdf"></i><div class="archivo-details">';
                    html += '<span class="archivo-nombre">' + escapeHtml(archivo.nombre_archivo || 'Documento') + '</span>';
                    if (archivo.fecha_subida) {
                        html += '<span class="archivo-fecha">' + escapeHtml(new Date(archivo.fecha_subida).toLocaleString()) + '</span>';
                    }
                    html += '</div></div><div class="archivo-actions">';
                    html += '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Ver</a>';
                    html += '</div></div>';
                });
                html += '</div></div>';
            }

            if (ticket.historial_cliente && ticket.historial_cliente.length) {
                html += '<div class="info-section"><h4><i class="fas fa-phone-volume"></i> Llamadas del cliente (CRM)</h4><div class="historial-interacciones">';
                ticket.historial_cliente.forEach(function(inter) {
                    html += '<div class="interaccion-item"><div class="interaccion-header">';
                    html += '<span class="interaccion-fecha">' + escapeHtml(new Date(inter.fecha_llamada).toLocaleString()) + '</span>';
                    if (hasDetalleValor(inter.tipificacion_categoria)) {
                        html += '<span class="interaccion-tipo">' + escapeHtml(inter.tipificacion_categoria) + '</span>';
                    }
                    html += '</div>';
                    if (hasDetalleValor(inter.observacion)) {
                        html += '<div class="interaccion-observacion">' + escapeHtml(inter.observacion) + '</div>';
                    }
                    html += '<div class="interaccion-details">';
                    if (inter.duracion_minutos != null && inter.duracion_minutos !== '') {
                        html += '<span>Duración: ' + escapeHtml(String(inter.duracion_minutos)) + ' min</span>';
                    }
                    if (hasDetalleValor(inter.tipificacion_codigo)) {
                        html += '<span>Código: ' + escapeHtml(inter.tipificacion_codigo) + '</span>';
                    }
                    html += '</div></div>';
                });
                html += '</div></div>';
            }

            if (ticket.referencias_personales && ticket.referencias_personales.length) {
                html += '<div class="info-section"><h4><i class="fas fa-users"></i> Referencias (' + ticket.referencias_personales.length + ')</h4>';
                html += '<p class="ticket-detalle-hint">Gestione el detalle completo en <strong>Gestionar ticket</strong>.</p></div>';
            }

            html += '</div>';
            content.innerHTML = html;
            actualizarFooterDetalleModal(ticket);
            if (window.TicketDetalleModal) {
                TicketDetalleModal.setTitulo(ticket, escapeHtml);
                TicketDetalleModal.ajustarLayout();
            }
            cargarHistorialDetalle(ticket.id);
        }

        function mostrarDetalleBasico(ticket) {
            const content = document.getElementById('ticketDetalleContent');
            content.innerHTML =
                '<div class="ticket-detalle-completo" data-ticket-id="' + escapeHtml(String(ticket.id)) + '">' +
                '<div class="info-section ticket-detalle-resumen">' +
                '<h4><i class="fas fa-ticket-alt"></i> Ticket CRM</h4>' +
                renderInfoGrid([
                    { label: 'Referencia', value: ticket.numero_ticket || ('#' + ticket.id) },
                    { label: 'Título', value: ticket.titulo },
                    { label: 'Estado', html: badgeEstadoHtml(ticket.estado) },
                    { label: 'Cliente', value: ticket.cliente_nombre },
                    { label: 'Teléfono', value: ticket.cliente_telefono },
                    { label: 'Creado', value: ticket.fecha_creacion ? new Date(ticket.fecha_creacion).toLocaleString() : '' }
                ]) +
                (hasDetalleValor(ticket.descripcion)
                    ? '<div class="info-section"><h4><i class="fas fa-align-left"></i> Descripción</h4><div class="ticket-description">' + escapeHtml(ticket.descripcion) + '</div></div>'
                    : '') +
                (hasDetalleValor(ticket.pdf_archivo)
                    ? '<div class="info-section"><h4><i class="fas fa-paperclip"></i> Archivo</h4><a href="' + escapeHtml(archivoTicketUrl(ticket.pdf_archivo, { ticketId: ticket.id })) + '" target="_blank" rel="noopener" class="pdf-link"><i class="fas fa-file-pdf"></i> Ver PDF</a></div>'
                    : '') +
                '<p class="ticket-detalle-hint">No se pudo cargar el detalle completo. Use <strong>Gestionar ticket</strong> para la ficha ampliada.</p>' +
                '</div>';
            actualizarFooterDetalleModal(ticket);
            if (window.TicketDetalleModal) {
                TicketDetalleModal.setTitulo(ticket, escapeHtml);
                TicketDetalleModal.ajustarLayout();
            }
        }

        // Cargar historial de detalle
        async function cargarHistorialDetalle(ticketId) {
            const historialContainer = document.getElementById('historialDetalle');
            if (!historialContainer) return;
            try {
                const response = await fetch('api/ticket_notas.php?ticket_id=' + encodeURIComponent(ticketId), {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    historialContainer.innerHTML = result.data.map(function(nota) {
                        if (window.TicketNotasRender && typeof TicketNotasRender.renderTicketNotaItem === 'function') {
                            return TicketNotasRender.renderTicketNotaItem(nota, escapeHtml);
                        }
                        const prox = hasDetalleValor(nota.proxima_accion)
                            ? '<div class="nota-accion"><strong>Próxima acción:</strong> ' + escapeHtml(nota.proxima_accion) + '</div>'
                            : '';
                        return '<div class="nota-item">' +
                            '<div class="nota-header">' +
                            '<span class="nota-fecha">' + escapeHtml(new Date(nota.fecha_creacion).toLocaleString()) + '</span>' +
                            '<span class="nota-asesor">' + escapeHtml(nota.asesor_nombre || '') + '</span>' +
                            '</div>' +
                            '<div class="nota-contenido">' + escapeHtml(nota.contenido || '') + '</div>' +
                            prox +
                            '</div>';
                    }).join('');
                } else {
                    historialContainer.innerHTML = '<p class="ticket-detalle-empty">No hay notas registradas</p>';
                }
            } catch (error) {
                console.error('Error cargando historial de detalle:', error);
                historialContainer.innerHTML = '<p class="ticket-detalle-empty ticket-detalle-empty--error">Error cargando notas</p>';
            }
            if (window.TicketDetalleModal) {
                TicketDetalleModal.ajustarLayout();
            }
        }

        function cerrarModalDetalleTicket() {
            if (window.TicketDetalleModal) {
                TicketDetalleModal.close();
                return;
            }
            const modal = document.getElementById('ticketDetalleModal');
            if (modal) modal.classList.remove('is-open');
        }

        // Funciones de utilidad
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

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const detalleModal = document.getElementById('ticketDetalleModal');

            if (event.target === detalleModal) {
                cerrarModalDetalleTicket();
            }
        }
    </script>
</body>
</html>
