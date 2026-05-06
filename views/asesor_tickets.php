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
    <title>Mis Tickets - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/role-specific.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/asesor.css" rel="stylesheet">
    <link href="../css/tickets.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                        <i class="fas fa-users"></i>
                        Mis Clientes
                    </a>
                    <a href="asesor_tickets.php" class="nav-item active">
                        <i class="fas fa-ticket-alt"></i>
                        Mis Tickets
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
                        <h1>Mis Tickets</h1>
                        <p>Gestiona y da seguimiento a todos tus tickets de soporte.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar tickets..." id="searchInput">
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

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="abrirModalCrearTicket()">
                        <i class="fas fa-plus"></i> Nuevo Ticket
                    </button>
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
                            <div class="stat-title">Comunicación</div>
                            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;"><i class="fas fa-comments"></i></div>
                        </div>
                        <div class="stat-value" id="statComunicacion">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Validación</div>
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;"><i class="fas fa-clipboard-check"></i></div>
                        </div>
                        <div class="stat-value" id="statValidacion">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Proceso judicial</div>
                            <div class="stat-icon" style="background: rgba(168, 85, 247, 0.2); color: #a855f7;"><i class="fas fa-gavel"></i></div>
                        </div>
                        <div class="stat-value" id="statProcesoJudicial">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Remate</div>
                            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-hammer"></i></div>
                        </div>
                        <div class="stat-value" id="statRemate">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Recuperación</div>
                            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;"><i class="fas fa-coins"></i></div>
                        </div>
                        <div class="stat-value" id="statRecuperacion">0</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Cierre</div>
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
                            <option value="comunicacion">Comunicación</option>
                            <option value="validacion">Validación</option>
                            <option value="proceso_judicial">Proceso judicial</option>
                            <option value="remate">Remate</option>
                            <option value="recuperacion">Recuperación</option>
                            <option value="cierre">Cierre</option>
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

    <!-- Modal para crear ticket -->
    <div id="crearTicketModal" class="modal-cliente">
        <div class="modal-cliente-content">
            <div class="modal-cliente-header">
                <h3 class="modal-cliente-title">Crear Nuevo Ticket</h3>
                <span class="close-modal" onclick="cerrarModalCrearTicket()">&times;</span>
            </div>
            
            <div class="ticket-form">
                <form id="crearTicketForm" onsubmit="crearTicket(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ticketCliente">Cliente *</label>
                            <select id="ticketCliente" class="form-control" required>
                                <option value="">Seleccionar cliente</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ticketCategoria">Categoría</label>
                            <select id="ticketCategoria" class="form-control">
                                <option value="">Sin categoría</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ticketTitulo">Asunto (opcional)</label>
                        <input type="text" id="ticketTitulo" class="form-control"
                               placeholder="El número de ticket se asignará automáticamente. Use este campo para un asunto interno opcional.">
                        <small class="form-text"><i class="fas fa-info-circle"></i> El número del ticket (TK-AAAA-NNNNNN) se genera de forma automática al crear el caso.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticketDescripcion">Descripción del Problema</label>
                        <textarea id="ticketDescripcion" class="form-control" rows="4" 
                                  placeholder="Describa detalladamente el problema o solicitud del cliente..."></textarea>
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="ticketPdfArchivo">Archivo PDF (Opcional)</label>
                        <input type="file" id="ticketPdfArchivo" name="pdf_archivo" class="form-control" 
                               accept=".pdf" onchange="previewTicketPDF(this)">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> 
                            Sube un documento PDF relacionado con el ticket. Máximo 50MB
                        </small>
                        <div id="ticket-pdf-preview" class="pdf-preview" style="display: none;">
                            <div class="pdf-preview-content">
                                <i class="fas fa-file-pdf"></i>
                                <span id="ticket-pdf-filename"></span>
                                <button type="button" class="btn-remove-pdf" onclick="removeTicketPDF()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearTicket()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-ticket-alt"></i> Crear Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del ticket -->
    <div id="ticketDetalleModal" class="modal-cliente">
        <div class="modal-cliente-content">
            <div class="modal-cliente-header">
                <h3 class="modal-cliente-title" id="ticketDetalleTitle">Detalles del Ticket</h3>
                <span class="close-modal" onclick="cerrarModalDetalleTicket()">&times;</span>
            </div>
            
            <div id="ticketDetalleContent">
                <!-- El contenido del ticket se cargará aquí -->
            </div>
        </div>
    </div>

    <script>
        let tickets = [];
        let ticketsFiltrados = [];
        let clientes = [];

        const ESTADO_LABELS = {
            'comunicacion':     'Comunicación',
            'validacion':       'Validación',
            'proceso_judicial': 'Proceso judicial',
            'remate':           'Remate',
            'recuperacion':     'Recuperación',
            'cierre':           'Cierre'
        };
        const ESTADO_ICONS = {
            'comunicacion':     'fas fa-comments',
            'validacion':       'fas fa-clipboard-check',
            'proceso_judicial': 'fas fa-gavel',
            'remate':           'fas fa-hammer',
            'recuperacion':     'fas fa-coins',
            'cierre':           'fas fa-flag-checkered'
        };

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        // Inicializar página
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si hay filtro por cliente
            const urlParams = new URLSearchParams(window.location.search);
            const clienteCedula = urlParams.get('cliente');
            
            if (clienteCedula) {
                // Mostrar indicador de filtro por cliente
                mostrarFiltroCliente(clienteCedula);
                loadTickets(clienteCedula);
            } else {
                loadTickets();
            }
            loadClientes();
        });

        // Cargar tickets
        async function loadTickets(clienteCedula = null) {
            try {
                let url = '../api/tickets_asesor.php';
                if (clienteCedula) {
                    url += `?cliente=${clienteCedula}`;
                }

                const response = await fetch(url);

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
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
                const response = await fetch('../api/asesor_clientes.php');

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
            // Remover parámetro de URL y recargar
            const url = new URL(window.location);
            url.searchParams.delete('cliente');
            window.location.href = url.toString();
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
                ticketCard.className = `ticket-card estado-${ticket.estado}`;

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
                            <span class="ticket-estado-badge estado-${ticket.estado}">
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
                                <a href="../${ticket.pdf_archivo}" target="_blank" class="pdf-link">Ver PDF adjunto</a>
                            </div>
                        ` : ''}
                    </div>
                    <div class="ticket-actions">
                        <button class="btn btn-sm btn-info" onclick="verDetalleTicket(${ticket.id})">
                            <i class="fas fa-eye"></i> Ver detalle
                        </button>
                        ${ticket.estado !== 'cierre' ? `
                            <a href="asesor_gestionar_ticket.php?id=${ticket.id}" class="btn btn-sm btn-warning">
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

        function updateStats() {
            const total = tickets.length;
            const counts = {
                comunicacion: 0, validacion: 0, proceso_judicial: 0,
                remate: 0, recuperacion: 0, cierre: 0
            };
            tickets.forEach(t => { if (counts.hasOwnProperty(t.estado)) counts[t.estado]++; });
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

            const estadoOrder = { comunicacion:1, validacion:2, proceso_judicial:3, remate:4, recuperacion:5, cierre:6 };
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
                const response = await fetch('../api/actualizar_ticket.php', {
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

        // Ver detalle del ticket
        async function verDetalleTicket(ticketId) {
            const ticket = tickets.find(t => t.id === ticketId);
            if (!ticket) return;

            const modal = document.getElementById('ticketDetalleModal');
            const title = document.getElementById('ticketDetalleTitle');
            const content = document.getElementById('ticketDetalleContent');

            title.textContent = `Ticket #${ticket.id} - ${ticket.titulo}`;
            
            // Cargar información adicional del ticket
            try {
                const response = await fetch(`../api/ticket_detalle_completo.php?ticket_id=${ticketId}`);
                const result = await response.json();
                
                if (result.success) {
                    const ticketCompleto = result.data;
                    mostrarDetalleCompleto(ticketCompleto);
                } else {
                    mostrarDetalleBasico(ticket);
                }
            } catch (error) {
                console.error('Error cargando detalle completo:', error);
                mostrarDetalleBasico(ticket);
            }

            modal.style.display = 'block';
        }

        function mostrarDetalleCompleto(ticket) {
            const content = document.getElementById('ticketDetalleContent');

            const hasData = (value) => value && value !== null && value !== '' && value !== 'No disponible';

            content.innerHTML = `
                <div class="ticket-detalle-completo" data-ticket-id="${ticket.id}">
                    <!-- Información del Ticket -->
                    <div class="info-section">
                        <h4><i class="fas fa-ticket-alt"></i> Información del Ticket</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ID:</span>
                                <span class="info-value">#${ticket.id}</span>
                            </div>
                            ${hasData(ticket.titulo) ? `
                            <div class="info-item">
                                <span class="info-label">Título:</span>
                                <span class="info-value">${ticket.titulo}</span>
                            </div>
                            ` : ''}
                            <div class="info-item">
                                <span class="info-label">Estado:</span>
                                <span class="info-value">
                                    <span class="ticket-estado-badge estado-${ticket.estado}">
                                        ${escapeHtml(ticket.estado_label || ESTADO_LABELS[ticket.estado] || ticket.estado || '')}
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Fecha Creación:</span>
                                <span class="info-value">${new Date(ticket.fecha_creacion).toLocaleString()}</span>
                            </div>
                            ${hasData(ticket.fecha_cierre) ? `
                            <div class="info-item">
                                <span class="info-label">Fecha Cierre:</span>
                                <span class="info-value">${new Date(ticket.fecha_cierre).toLocaleString()}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-user"></i> Información del Cliente</h4>
                        <div class="info-grid">
                            ${hasData(ticket.cliente_cedula) ? `
                            <div class="info-item">
                                <span class="info-label">Cédula:</span>
                                <span class="info-value">${ticket.cliente_cedula}</span>
                            </div>
                            ` : ''}
                            ${hasData(ticket.cliente_nombre) ? `
                            <div class="info-item">
                                <span class="info-label">Nombre Completo:</span>
                                <span class="info-value">${ticket.cliente_nombre}</span>
                            </div>
                            ` : ''}
                            ${hasData(ticket.cliente_telefono) ? `
                            <div class="info-item">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value">${ticket.cliente_telefono}</span>
                            </div>
                            ` : ''}
                            ${hasData(ticket.cliente_email) ? `
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">${ticket.cliente_email}</span>
                            </div>
                            ` : ''}
                            ${hasData(ticket.cliente_direccion) ? `
                            <div class="info-item">
                                <span class="info-label">Dirección:</span>
                                <span class="info-value">${ticket.cliente_direccion}</span>
                            </div>
                            ` : ''}
                            ${hasData(ticket.cliente_ciudad) ? `
                            <div class="info-item">
                                <span class="info-label">Ciudad:</span>
                                <span class="info-value">${ticket.cliente_ciudad}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Descripción del Problema -->
                    ${hasData(ticket.descripcion) ? `
                    <div class="info-section">
                        <h4><i class="fas fa-file-alt"></i> Descripción del Problema</h4>
                        <div class="ticket-description">
                            ${ticket.descripcion}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Observaciones -->
                    ${hasData(ticket.observaciones) ? `
                    <div class="info-section">
                        <h4><i class="fas fa-sticky-note"></i> Observaciones</h4>
                        <div class="ticket-observations">
                            ${ticket.observaciones}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Archivos Adjuntos -->
                    ${ticket.archivos_pdf && ticket.archivos_pdf.length > 0 ? `
                    <div class="info-section">
                        <h4><i class="fas fa-paperclip"></i> Archivos Adjuntos (${ticket.archivos_pdf.length})</h4>
                        <div class="ticket-archivos">
                            ${ticket.archivos_pdf.map(archivo => `
                                <div class="archivo-item">
                                    <div class="archivo-info">
                                        <i class="fas fa-file-pdf"></i>
                                        <div class="archivo-details">
                                            <span class="archivo-nombre">${archivo.nombre_archivo}</span>
                                            <span class="archivo-fecha">${new Date(archivo.fecha_subida).toLocaleString()}</span>
                                        </div>
                                    </div>
                                    <div class="archivo-actions">
                                        <a href="../${archivo.ruta_archivo}" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarArchivo(${archivo.id})">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Historial de Notas -->
                    <div class="info-section">
                        <h4><i class="fas fa-history"></i> Historial de Notas y Acciones</h4>
                        <div id="historialDetalle" class="historial-detalle">
                            <!-- Se cargará dinámicamente -->
                        </div>
                    </div>
                    
                    <!-- Historial de Interacciones del Cliente -->
                    ${ticket.historial_cliente && ticket.historial_cliente.length > 0 ? `
                    <div class="info-section">
                        <h4><i class="fas fa-phone"></i> Historial de Interacciones del Cliente</h4>
                        <div class="historial-interacciones">
                            ${ticket.historial_cliente.map(interaccion => `
                                <div class="interaccion-item">
                                    <div class="interaccion-header">
                                        <span class="interaccion-fecha">${new Date(interaccion.fecha_llamada).toLocaleString()}</span>
                                        ${hasData(interaccion.tipificacion_categoria) ? `
                                        <span class="interaccion-tipo">${interaccion.tipificacion_categoria}</span>
                                        ` : ''}
                                    </div>
                                    ${hasData(interaccion.observacion) ? `
                                    <div class="interaccion-observacion">${interaccion.observacion}</div>
                                    ` : ''}
                                    <div class="interaccion-details">
                                        ${hasData(interaccion.duracion_minutos) ? `
                                        <span>Duración: ${interaccion.duracion_minutos} min</span>
                                        ` : ''}
                                        ${hasData(interaccion.tipificacion_codigo) ? `
                                        <span>Tipo: ${interaccion.tipificacion_codigo}</span>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;

            // Cargar historial de notas
            cargarHistorialDetalle(ticket.id);
        }

        function mostrarDetalleBasico(ticket) {
            const content = document.getElementById('ticketDetalleContent');

            content.innerHTML = `
                <div class="ticket-detalle-info">
                    <div class="info-section">
                        <h4>Información del Ticket</h4>
                        <div class="info-item">
                            <span class="info-label">ID:</span>
                            <span class="info-value">#${ticket.id}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Título:</span>
                            <span class="info-value">${ticket.titulo}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span class="info-value">
                                <span class="ticket-estado-badge estado-${ticket.estado}">
                                    ${escapeHtml(ESTADO_LABELS[ticket.estado] || ticket.estado || '')}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha Creación:</span>
                            <span class="info-value">${new Date(ticket.fecha_creacion).toLocaleString()}</span>
                        </div>
                        ${ticket.fecha_cierre ? `
                        <div class="info-item">
                            <span class="info-label">Fecha Cierre:</span>
                            <span class="info-value">${new Date(ticket.fecha_cierre).toLocaleString()}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="info-section">
                        <h4>Información del Cliente</h4>
                        <div class="info-item">
                            <span class="info-label">Cliente:</span>
                            <span class="info-value">${escapeHtml(ticket.cliente_nombre || '')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value">${escapeHtml(ticket.cliente_telefono || 'No disponible')}</span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>Descripción del Problema</h4>
                        <div class="ticket-description">
                            ${ticket.descripcion || 'Sin descripción'}
                        </div>
                    </div>
                    
                    ${ticket.observaciones ? `
                    <div class="info-section">
                        <h4>Observaciones</h4>
                        <div class="ticket-observations">
                            ${ticket.observaciones}
                        </div>
                    </div>
                    ` : ''}
                    
                    ${ticket.pdf_archivo ? `
                    <div class="info-section">
                        <h4>Archivo Adjunto</h4>
                        <div class="ticket-pdf">
                            <i class="fas fa-file-pdf"></i>
                            <a href="../${ticket.pdf_archivo}" target="_blank" class="pdf-link">
                                Ver PDF Adjunto
                            </a>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        }

        // Cargar historial de detalle
        async function cargarHistorialDetalle(ticketId) {
            try {
                const response = await fetch(`../api/ticket_notas.php?ticket_id=${ticketId}`);
                const result = await response.json();
                
                const historialContainer = document.getElementById('historialDetalle');
                
                if (result.success && result.data.length > 0) {
                    historialContainer.innerHTML = result.data.map(nota => `
                        <div class="nota-item">
                            <div class="nota-header">
                                <span class="nota-fecha">${new Date(nota.fecha_creacion).toLocaleString()}</span>
                                <span class="nota-asesor">${nota.asesor_nombre}</span>
                            </div>
                            <div class="nota-contenido">${nota.contenido}</div>
                            ${nota.proxima_accion ? `<div class="nota-accion"><strong>Próxima acción:</strong> ${nota.proxima_accion}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    historialContainer.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 20px;">No hay notas registradas</p>';
                }
            } catch (error) {
                console.error('Error cargando historial de detalle:', error);
                document.getElementById('historialDetalle').innerHTML = '<p style="color: #ef4444; text-align: center; padding: 20px;">Error cargando historial</p>';
            }
        }

        // Cerrar modal de detalle
        function cerrarModalDetalleTicket() {
            document.getElementById('ticketDetalleModal').style.display = 'none';
        }

        // Eliminar archivo PDF
        async function eliminarArchivo(archivoId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('archivo_id', archivoId);

                const response = await fetch('../api/eliminar_archivo_ticket.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Archivo eliminado exitosamente', 'success');
                    // Recargar el modal de detalle
                    const ticketId = document.querySelector('.ticket-detalle-completo').dataset.ticketId;
                    if (ticketId) {
                        verDetalleTicket(ticketId);
                    }
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error eliminando archivo:', error);
                showMessage('Error eliminando archivo', 'error');
            }
        }

        // Abrir modal crear ticket
        function abrirModalCrearTicket() {
            cargarClientesParaTicket();
            cargarCategoriasTicket();
            document.getElementById('crearTicketModal').style.display = 'block';
        }

        async function cargarCategoriasTicket() {
            const sel = document.getElementById('ticketCategoria');
            sel.innerHTML = '<option value="">Sin categoría</option>';
            try {
                const response = await fetch('../api/ticket_categorias.php', { credentials: 'same-origin' });
                const result = await response.json();
                if (result.success && result.data) {
                    result.data.forEach(function(c) {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.codigo + ' — ' + c.nombre;
                        sel.appendChild(opt);
                    });
                }
            } catch (e) {
                console.warn('Categorías no disponibles', e);
            }
        }

        // Cerrar modal crear ticket
        function cerrarModalCrearTicket() {
            document.getElementById('crearTicketModal').style.display = 'none';
            document.getElementById('crearTicketForm').reset();
            removeTicketPDF();
        }

        function cargarClientesParaTicket() {
            const select = document.getElementById('ticketCliente');
            select.innerHTML = '<option value="">Seleccionar cliente</option>';

            clientes.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.cedula;
                const nombre = cliente.nombre_completo || `${cliente.nombre || ''} ${cliente.apellido || ''}`.trim();
                option.textContent = `${nombre} (${cliente.cedula})`;
                select.appendChild(option);
            });
        }

        // Preview de PDF para ticket
        function previewTicketPDF(input) {
            const file = input.files[0];
            const preview = document.getElementById('ticket-pdf-preview');
            const filename = document.getElementById('ticket-pdf-filename');

            if (file) {
                if (file.type !== 'application/pdf') {
                    showMessage('Por favor seleccione un archivo PDF válido', 'error');
                    input.value = '';
                    return;
                }

                const maxSize = 50 * 1024 * 1024; // 50MB
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

        // Remover PDF del ticket
        function removeTicketPDF() {
            document.getElementById('ticketPdfArchivo').value = '';
            document.getElementById('ticket-pdf-preview').style.display = 'none';
        }

        async function crearTicket(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('cliente_cedula', document.getElementById('ticketCliente').value);
            const titulo = document.getElementById('ticketTitulo').value;
            if (titulo && titulo.trim()) {
                formData.append('titulo', titulo.trim());
            }
            formData.append('descripcion', document.getElementById('ticketDescripcion').value);
            const catId = document.getElementById('ticketCategoria').value;
            if (catId) {
                formData.append('categoria_id', catId);
            }
            
            const pdfFile = document.getElementById('ticketPdfArchivo').files[0];
            if (pdfFile) {
                formData.append('pdf_archivo', pdfFile);
            }
            
            try {
                const response = await fetch('../api/crear_ticket.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Ticket creado exitosamente', 'success');
                    cerrarModalCrearTicket();
                    loadTickets();
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error creando ticket:', error);
                showMessage('Error creando ticket', 'error');
            }
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
                const response = await fetch('../api/logout.php', {
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

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const crearModal = document.getElementById('crearTicketModal');
            const detalleModal = document.getElementById('ticketDetalleModal');
            
            if (event.target === crearModal) {
                cerrarModalCrearTicket();
            }
            if (event.target === detalleModal) {
                cerrarModalDetalleTicket();
            }
        }
    </script>
</body>
</html>
