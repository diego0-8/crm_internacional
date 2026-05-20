<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('coordinador');

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
    <title>Gestión de Tareas - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/asesor.css" rel="stylesheet">
    <link href="css/coordinador.css" rel="stylesheet">
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
                    <div class="nav-section-title">Coordinador</div>
                    <a href="<?php echo app_nav_url('coordinador_dashboard'); ?>" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_tareas'); ?>" class="nav-item active">
                        <i class="fas fa-tasks"></i>
                        Tareas
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_gestion'); ?>" class="nav-item">
                        <i class="fas fa-upload"></i>
                        Gestión CSV
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_exporte'); ?>" class="nav-item">
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
                        <h1 class="title-asesor">Gestión de Tareas</h1>
                        <p class="subtitle-asesor">Asignación de titulares (reparto) a asesores.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="user-info">
                        <span>Bienvenido, <?php echo htmlspecialchars($user['nombre']); ?></span>
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area coordinador-dashboard">
                <?php if ($message): ?>
                    <div class="message <?php echo $message['type']; ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <!-- Estadísticas de Tareas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalClientes">0</h3>
                            <p>Total titulares</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="clientesAsignados">0</h3>
                            <p>Asignados a asesor</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="clientesDisponibles">0</h3>
                            <p>Sin asesor</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalAsesores">0</h3>
                            <p>Asesores Activos</p>
                        </div>
                    </div>
                </div>

                <!-- Asignación Individual -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Titulares (reparto)</h3>
                        <button class="btn btn-primary" onclick="refreshClientes()">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="search-bar">
                            <input type="text" id="searchClientes" placeholder="Buscar por nombre, Case Number, Parcel Number, teléfono, email…" onkeyup="debounceSearch()">
                            <i class="fas fa-search"></i>
                        </div>
                        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <button type="button" class="btn btn-primary btn-sm" onclick="abrirAsignacionMasivaSeleccion()">
                                <i class="fas fa-user-friends"></i> Asignar selección…
                            </button>
                        </div>
                        <div id="clientesContainer">
                            <div id="clientesList" class="clientes-grid">
                                <!-- Lista de clientes se carga aquí -->
                            </div>
                            <div id="paginationContainer" class="pagination-container" style="display: none;">
                                <div class="pagination-info">
                                    <span id="paginationInfo">Mostrando 1-6 de 0 titulares</span>
                                </div>
                                <div class="pagination-controls">
                                    <button id="prevPage" class="btn btn-sm btn-secondary" onclick="changePage(currentPage - 1)" disabled>
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </button>
                                    <div id="pageNumbers" class="page-numbers"></div>
                                    <button id="nextPage" class="btn btn-sm btn-secondary" onclick="changePage(currentPage + 1)" disabled>
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asignación Masiva -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users-cog"></i> Asignación Masiva</h3>
                    </div>
                    <div class="card-content">
                        <div class="asignacion-masiva">
                            <div class="form-group">
                                <label for="totalClientesAsignar">Cantidad de titulares sin asesor a repartir</label>
                                <input type="number" id="totalClientesAsignar" class="form-control form-control-narrow" min="1" placeholder="Ej. 10">
                            </div>
                            <div class="form-group">
                                <label for="notasAsignacionGlobal">Notas (opcional)</label>
                                <textarea id="notasAsignacionGlobal" class="form-control" rows="3" placeholder="Notas sobre la asignación..."></textarea>
                            </div>
                            <div class="asignacion-masiva-actions">
                                <button type="button" class="btn btn-success" onclick="asignarClientesGlobalmente()">
                                    <i class="fas fa-users"></i> Repartir titulares (automático)
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="limpiarAsignacionGlobal()">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Asesores -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-tie"></i> Asesores Disponibles</h3>
                        <button class="btn btn-primary" onclick="refreshAsesores()">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                    <div class="card-content">
                        <div id="asesoresList" class="asesores-list">
                            <!-- Lista de asesores se carga aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Asignación Individual -->
    <div id="assignModal" class="modal coordinador-modal modal-assign-titular" role="dialog" aria-modal="true" aria-labelledby="assignModalTitle">
        <div class="modal-content modal-assign-titular-dialog">
            <div class="modal-header">
                <h3 id="assignModalTitle">Asignar titular</h3>
                <span class="close" onclick="closeAssignModal()" aria-label="Cerrar">&times;</span>
            </div>
            <div class="modal-body modal-assign-titular-body">
                <div id="clienteInfo"></div>
                <div class="form-group">
                    <label for="asesorSelect">Asesor</label>
                    <select id="asesorSelect" class="form-control">
                        <option value="">Cargando asesores...</option>
                    </select>
                </div>
                <div class="form-group form-group--last">
                    <label for="notasAsignacion">Notas (opcional)</label>
                    <textarea id="notasAsignacion" class="form-control" rows="3" placeholder="Notas sobre la asignación..."></textarea>
                </div>
            </div>
            <div class="modal-footer modal-assign-titular-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarAsignacion()">Asignar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Asignación Masiva -->
    <div id="assignMasivoModal" class="modal coordinador-modal modal-assign-titular" role="dialog" aria-modal="true" aria-labelledby="assignMasivoModalTitle">
        <div class="modal-content modal-assign-titular-dialog">
            <div class="modal-header">
                <h3 id="assignMasivoModalTitle">Asignación masiva</h3>
                <span class="close" onclick="closeAssignMasivoModal()" aria-label="Cerrar">&times;</span>
            </div>
            <div class="modal-body modal-assign-titular-body">
                <div class="form-group">
                    <label for="asesorSelectMasivo">Asesor</label>
                    <select id="asesorSelectMasivo" class="form-control">
                        <option value="">Cargando asesores...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notasAsignacionMasiva">Notas (opcional)</label>
                    <textarea id="notasAsignacionMasiva" class="form-control" rows="3" placeholder="Notas sobre la asignación..."></textarea>
                </div>
                <div class="clientes-seleccionados">
                    <p><strong>Titulares seleccionados:</strong> <span id="cantidadAsignar">0</span></p>
                </div>
            </div>
            <div class="modal-footer modal-assign-titular-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAssignMasivoModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarAsignacionMasiva()">Asignar todos</button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let clientes = [];
        let asesores = [];
        let clientesSeleccionados = [];
        let clienteSeleccionado = null;
        let clientesFiltrados = [];
        let currentPage = 1;
        let itemsPerPage = 6;
        let searchTimeout = null;

        function escHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function fmtTitCampo(v) {
            if (v == null || String(v).trim() === '') return '—';
            return escHtml(String(v));
        }

        // Cargar datos al inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadClientes();
            loadAsesores();
        });

        // Cargar datos del dashboard
        async function loadDashboardData() {
            try {
                const response = await fetch('api/coordinador_dashboard.php', {
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
                    updateStats(result.data);
                } else {
                    showMessage('Error cargando datos del dashboard', 'error');
                }
            } catch (error) {
                console.error('Error cargando dashboard:', error);
                showMessage('Error cargando datos', 'error');
            }
        }

        // Actualizar estadísticas
        function updateStats(data) {
            if (data.clientes) {
                document.getElementById('totalClientes').textContent = data.clientes.total || 0;
                document.getElementById('clientesAsignados').textContent = data.clientes.asignados || 0;
                document.getElementById('clientesDisponibles').textContent = (data.clientes.total || 0) - (data.clientes.asignados || 0);
            }
            if (data.asesores) {
                document.getElementById('totalAsesores').textContent = data.asesores.length || 0;
            }
        }

        // Cargar clientes
        async function loadClientes() {
            const container = document.getElementById('clientesList');
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
            container.appendChild(loadingOverlay);

            try {
                const response = await fetch('api/coordinador_clientes.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success) {
                    clientes = result.data || [];
                    clientesFiltrados = [...clientes];
                    currentPage = 1;
                    renderClientes();
                } else {
                    console.error('Error en respuesta de API:', result.message);
                    showMessage(result.message || 'Error al cargar titulares', 'error');
                    clientes = [];
                    clientesFiltrados = [];
                    renderClientes();
                }
            } catch (error) {
                console.error('Error cargando clientes:', error);
                showMessage('Error de conexión al cargar titulares.', 'error');
                clientes = [];
                clientesFiltrados = [];
                renderClientes();
            } finally {
                // Remover overlay de carga
                if (loadingOverlay.parentNode) {
                    loadingOverlay.remove();
                }
            }
        }

        // Renderizar clientes con paginación (applyFilter=false cuando filtrarClientes ya aplicó el filtro)
        function renderClientes(applyFilter = true) {
            if (applyFilter) {
                const searchTerm = document.getElementById('searchClientes').value.trim();
                if (searchTerm) {
                    filtrarClientes(searchTerm);
                    return;
                }
                clientesFiltrados = [...clientes];
            }

            const searchTerm = document.getElementById('searchClientes').value.trim();

            // Calcular paginación
            const totalItems = clientesFiltrados.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            // Ajustar página actual si es necesario
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            } else if (currentPage < 1) {
                currentPage = 1;
            }

            // Calcular índices para la página actual
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
            const clientesPagina = clientesFiltrados.slice(startIndex, endIndex);

            const container = document.getElementById('clientesList');
            container.innerHTML = '';

            if (clientesFiltrados.length === 0) {
                if (searchTerm) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h4>No se encontraron resultados</h4>
                            <p>No hay titulares que coincidan con "${searchTerm}"</p>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h4>No hay titulares</h4>
                            <p>Importe un CSV de reparto en Gestión CSV o verifique el coordinador del registro.</p>
                        </div>
                    `;
                }
                document.getElementById('paginationContainer').style.display = 'none';
                return;
            }

            // Renderizar tarjetas de titulares
            clientesPagina.forEach(cliente => {
                const tid = cliente.titular_id;
                const nombreTit = escHtml(
                    [cliente.nombre, cliente.apellido].filter(x => x != null && String(x).trim() !== '').join(' ').trim()
                ) || '—';
                const emailDis = (cliente.email != null && String(cliente.email).trim() !== '')
                    ? escHtml(String(cliente.email))
                    : 'Sin email';
                const telDis = (cliente.telefono != null && String(cliente.telefono).trim() !== '')
                    ? escHtml(String(cliente.telefono))
                    : 'Sin teléfono';
                const locDis = escHtml(String(cliente.empresa || cliente.condado || '').trim()) || '—';
                const casoTxt = (cliente.numero_caso != null && String(cliente.numero_caso).trim() !== '')
                    ? String(cliente.numero_caso).trim()
                    : '';
                const parcelaTxt = (cliente.numero_parcela != null && String(cliente.numero_parcela).trim() !== '')
                    ? String(cliente.numero_parcela).trim()
                    : '';
                let casoExtra = '';
                if (casoTxt) {
                    casoExtra = ' · Caso: ' + escHtml(casoTxt);
                } else if (parcelaTxt) {
                    casoExtra = ' · Parcel: ' + escHtml(parcelaTxt);
                }
                const estadoTxt = escHtml(String(cliente.estado || 'reparto'));
                const asesorTxt = cliente.asesor_nombre ? escHtml(String(cliente.asesor_nombre)) : '';

                const clienteCard = document.createElement('div');
                clienteCard.className = `cliente-card ${cliente.asesor_nombre ? 'assigned' : ''}`;
                clienteCard.innerHTML = `
                    <div class="cliente-card-header">
                        <div class="cliente-card-header-top">
                            <div class="cliente-card-info">
                            <h4 class="cliente-card-titulo">${nombreTit}</h4>
                            <p class="cliente-card-linea cliente-card-linea--caso"><i class="fas fa-hashtag" aria-hidden="true"></i><span>ID ${tid}${casoExtra}</span></p>
                            <p class="cliente-card-linea"><i class="fas fa-envelope" aria-hidden="true"></i><span>${emailDis}</span></p>
                            <p class="cliente-card-linea"><i class="fas fa-phone" aria-hidden="true"></i><span>${telDis}</span></p>
                            <p class="cliente-card-linea"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span>${locDis}</span></p>
                            </div>
                            <span class="cliente-status status-reparto">${estadoTxt}</span>
                        </div>
                        <div class="titular-reparto-meta-cols" aria-label="Datos titular reparto">
                            <div class="trc-item"><span class="trc-lbl">Reg_Int</span><span class="trc-val">${fmtTitCampo(cliente.reg_int)}</span></div>
                            <div class="trc-item"><span class="trc-lbl">BaseD</span><span class="trc-val">${fmtTitCampo(cliente.base_d)}</span></div>
                            <div class="trc-item trc-item--fecha"><span class="trc-lbl">F_Correo</span><span class="trc-val">${fmtTitCampo(cliente.f_correo)}</span></div>
                        </div>
                    </div>
                    <div class="cliente-card-actions">
                        ${cliente.asesor_nombre ?
                            `<div class="cliente-assigned-info">
                                <i class="fas fa-user-check" aria-hidden="true"></i> Asignado a: ${asesorTxt}
                             </div>` :
                            `<div class="cliente-card-actions-row">
                                <input type="checkbox" id="titular_${tid}" value="${tid}"
                                       onchange="toggleClienteSeleccion(${tid})" aria-label="Seleccionar titular ${tid}">
                                <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalAsignacion(${tid})">
                                    <i class="fas fa-user-plus" aria-hidden="true"></i> Asignar
                                </button>
                            </div>`
                        }
                    </div>
                `;
                container.appendChild(clienteCard);
            });

            // Mostrar/ocultar paginación
            if (totalPages > 1) {
                renderPagination(totalItems, totalPages);
                document.getElementById('paginationContainer').style.display = 'flex';
            } else {
                document.getElementById('paginationContainer').style.display = 'none';
            }

            // Actualizar información de paginación
            updatePaginationInfo(startIndex + 1, endIndex, totalItems);
        }

        // Filtrar clientes con búsqueda en tiempo real
        function filtrarClientes(searchTerm = null) {
            if (searchTerm === null) {
                searchTerm = document.getElementById('searchClientes').value.trim();
            }

            if (!searchTerm) {
                clientesFiltrados = [...clientes];
            } else {
                const termLower = searchTerm.toLowerCase();
                const termNorm = normalizarTextoBusqueda(searchTerm);
                clientesFiltrados = clientes.filter(cliente => {
                    const nombre = (cliente.nombre || '').toLowerCase();
                    const apellido = (cliente.apellido || '').toLowerCase();
                    const nombreCompleto = `${nombre} ${apellido}`.trim();

                    return coincideBusqueda(cliente.titular_id, termLower, termNorm) ||
                           nombre.includes(termLower) ||
                           apellido.includes(termLower) ||
                           nombreCompleto.includes(termLower) ||
                           coincideBusqueda(cliente.numero_caso, termLower, termNorm) ||
                           coincideBusqueda(cliente.numero_parcela, termLower, termNorm) ||
                           coincideBusqueda(cliente.telefono, termLower, termNorm) ||
                           coincideBusqueda(cliente.email, termLower, termNorm) ||
                           coincideBusqueda(cliente.condado, termLower, termNorm) ||
                           coincideBusqueda(cliente.reg_int, termLower, termNorm) ||
                           coincideBusqueda(cliente.base_d, termLower, termNorm) ||
                           coincideBusqueda(cliente.f_correo, termLower, termNorm);
                });
            }

            // Resetear a página 1 cuando se busca
            currentPage = 1;

            renderClientes(false);
        }

        function normalizarTextoBusqueda(texto) {
            return String(texto || '').toLowerCase().replace(/[\s\-_./]/g, '');
        }

        function coincideBusqueda(valor, termLower, termNorm) {
            const v = String(valor || '').toLowerCase();
            if (!v) {
                return false;
            }
            return v.includes(termLower) || normalizarTextoBusqueda(v).includes(termNorm);
        }

        // Función de búsqueda con debounce para mejor rendimiento
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filtrarClientes();
            }, 300); // Esperar 300ms después de que el usuario deje de escribir
        }

        // Renderizar controles de paginación
        function renderPagination(totalItems, totalPages) {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';

            // Calcular rango de páginas a mostrar
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            // Ajustar el rango para mantener 5 páginas cuando sea posible
            if (endPage - startPage < 4) {
                if (startPage === 1) {
                    endPage = Math.min(totalPages, startPage + 4);
                } else if (endPage === totalPages) {
                    startPage = Math.max(1, endPage - 4);
                }
            }

            // Agregar botón "primera página" si es necesario
            if (startPage > 1) {
                const firstPageBtn = document.createElement('button');
                firstPageBtn.className = 'page-number';
                firstPageBtn.textContent = '1';
                firstPageBtn.onclick = () => changePage(1);
                pageNumbersContainer.appendChild(firstPageBtn);

                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'page-ellipsis';
                    ellipsis.textContent = '...';
                    ellipsis.style.margin = '0 0.5rem';
                    ellipsis.style.color = '#6b7280';
                    pageNumbersContainer.appendChild(ellipsis);
                }
            }

            // Agregar números de página
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-number ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.onclick = () => changePage(i);
                pageNumbersContainer.appendChild(pageBtn);
            }

            // Agregar botón "última página" si es necesario
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'page-ellipsis';
                    ellipsis.textContent = '...';
                    ellipsis.style.margin = '0 0.5rem';
                    ellipsis.style.color = '#6b7280';
                    pageNumbersContainer.appendChild(ellipsis);
                }

                const lastPageBtn = document.createElement('button');
                lastPageBtn.className = 'page-number';
                lastPageBtn.textContent = totalPages;
                lastPageBtn.onclick = () => changePage(totalPages);
                pageNumbersContainer.appendChild(lastPageBtn);
            }

            // Actualizar estado de botones anterior/siguiente
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');

            prevBtn.disabled = currentPage === 1;
            prevBtn.classList.toggle('disabled', currentPage === 1);

            nextBtn.disabled = currentPage === totalPages;
            nextBtn.classList.toggle('disabled', currentPage === totalPages);
        }

        // Cambiar página
        function changePage(page) {
            if (page < 1 || page > Math.ceil(clientesFiltrados.length / itemsPerPage)) {
                return;
            }

            currentPage = page;
            renderClientes();

            // Scroll suave hacia arriba de la lista
            document.getElementById('clientesList').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Actualizar información de paginación
        function updatePaginationInfo(start, end, total) {
            const infoElement = document.getElementById('paginationInfo');
            if (total === 0) {
                infoElement.textContent = 'No hay titulares';
            } else {
                infoElement.textContent = `Mostrando ${start}-${end} de ${total} titulares`;
            }
        }

        // Cargar asesores
        async function loadAsesores() {
            try {
                const response = await fetch('api/coordinador_asesores.php', {
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
                    asesores = result.data;
                    renderAsesores();
                } else {
                    showMessage('Error cargando asesores: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando asesores:', error);
                showMessage('Error cargando asesores', 'error');
            }
        }

        // Renderizar asesores
        function renderAsesores() {
            const container = document.getElementById('asesoresList');
            container.innerHTML = '';

            if (asesores.length === 0) {
                container.innerHTML = '<p class="coordinador-empty-inline">No hay asesores disponibles</p>';
                return;
            }

            asesores.forEach(asesor => {
                const asesorCard = document.createElement('div');
                asesorCard.className = 'asesor-card';
                asesorCard.innerHTML = `
                    <div class="asesor-info">
                        <div class="asesor-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="asesor-details">
                            <h4>${asesor.nombre} ${asesor.apellido}</h4>
                            <p>${asesor.email}</p>
                            <div class="asesor-stats">
                                <span class="stat-item">
                                    <i class="fas fa-users"></i> 
                                    <span id="clientesAsignados_${asesor.cedula}">0</span> titulares
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="asesor-actions">
                        <button class="btn btn-sm btn-info" onclick="verDetallesAsesor('${asesor.cedula}')">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </button>
                    </div>
                `;
                container.appendChild(asesorCard);
            });
        }

        // Abrir modal de asignación individual
        function abrirModalAsignacion(titularId) {
            const cliente = clientes.find(c => String(c.titular_id) === String(titularId));
            if (!cliente) return;
            
            clienteSeleccionado = cliente;
            mostrarInformacionCliente(cliente);
            cargarAsesores();
            openModalAsignacion('assignModal');
        }

        function openModalAsignacion(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            modal.classList.add('modal-open');
            document.body.classList.add('modal-assign-titular-open');
        }

        function closeModalAsignacion(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('modal-open');
            }
            if (!document.querySelector('.modal-assign-titular.modal-open')) {
                document.body.classList.remove('modal-assign-titular-open');
            }
        }

        // Mostrar información del cliente en el modal
        function mostrarInformacionCliente(cliente) {
            const clienteInfo = document.getElementById('clienteInfo');
            const nom = escHtml([cliente.nombre, cliente.apellido].filter(x => x != null && String(x).trim() !== '').join(' ').trim()) || '—';
            const est = escHtml(String(cliente.estado || 'reparto'));
            const fmtO = (v) => (v != null && String(v).trim() !== '') ? escHtml(String(v)) : 'No disponible';
            clienteInfo.innerHTML = `
                <div class="cliente-info-card">
                    <div class="cliente-header">
                        <h4>${nom}</h4>
                        <span class="status-badge status-reparto">${est}</span>
                    </div>
                    <div class="cliente-details">
                        <p><strong>Caso:</strong> ${fmtO(cliente.numero_caso)}</p>
                        <p><strong>Condado:</strong> ${fmtO(cliente.condado)}</p>
                        <p><strong>Reg_Int:</strong> ${fmtTitCampo(cliente.reg_int)}</p>
                        <p><strong>BaseD:</strong> ${fmtTitCampo(cliente.base_d)}</p>
                        <p><strong>F_Correo:</strong> ${fmtTitCampo(cliente.f_correo)}</p>
                        <p><strong>Email:</strong> ${fmtO(cliente.email)}</p>
                        <p><strong>Teléfono:</strong> ${fmtO(cliente.telefono)}</p>
                        <p><strong>Ciudad (mailing):</strong> ${fmtO(cliente.ciudad)}</p>
                    </div>
                </div>
            `;
        }

        // Cargar asesores para el modal
        async function cargarAsesores() {
            try {
                const response = await fetch('api/coordinador_asesores.php', {
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
                    renderAsesoresSelect(result.data);
                } else {
                    showMessage('Error cargando asesores: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando asesores:', error);
                showMessage('Error cargando asesores', 'error');
            }
        }

        // Renderizar select de asesores
        function renderAsesoresSelect(asesoresData, selectId = 'asesorSelect') {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Seleccionar asesor...</option>';
            
            if (asesoresData.length === 0) {
                select.innerHTML = '<option value="">No hay asesores disponibles</option>';
                return;
            }
            
            asesoresData.forEach(asesor => {
                const option = document.createElement('option');
                option.value = asesor.cedula;
                option.textContent = `${asesor.nombre} ${asesor.apellido} (${asesor.email})`;
                select.appendChild(option);
            });
        }

        async function cargarAsesoresParaMasivo() {
            try {
                const response = await fetch('api/coordinador_asesores.php', { credentials: 'include' });
                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }
                const result = await response.json();
                if (result.success) {
                    renderAsesoresSelect(result.data, 'asesorSelectMasivo');
                } else {
                    showMessage('Error cargando asesores: ' + result.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showMessage('Error cargando asesores', 'error');
            }
        }

        async function abrirAsignacionMasivaSeleccion() {
            if (clientesSeleccionados.length === 0) {
                showMessage('Seleccione al menos un titular con la casilla', 'error');
                return;
            }
            document.getElementById('cantidadAsignar').textContent = clientesSeleccionados.length;
            await cargarAsesoresParaMasivo();
            openModalAsignacion('assignMasivoModal');
        }

        function closeAssignMasivoModal() {
            closeModalAsignacion('assignMasivoModal');
            document.getElementById('notasAsignacionMasiva').value = '';
        }

        async function confirmarAsignacionMasiva() {
            const asesorCedula = document.getElementById('asesorSelectMasivo').value;
            if (!asesorCedula) {
                showMessage('Seleccione un asesor', 'error');
                return;
            }
            const ids = [...clientesSeleccionados];
            let ok = 0;
            const notas = document.getElementById('notasAsignacionMasiva').value;
            try {
                for (const tid of ids) {
                    const response = await fetch('api/assign_cliente.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({
                            titular_id: parseInt(tid, 10),
                            asesor_cedula: asesorCedula,
                            notas: notas
                        })
                    });
                    const result = await response.json();
                    if (result.success) ok++;
                }
                showMessage(`Asignados ${ok} de ${ids.length} titular(es)`, ok === ids.length ? 'success' : 'info');
                closeAssignMasivoModal();
                limpiarSeleccion();
                loadClientes();
                loadDashboardData();
            } catch (e) {
                console.error(e);
                showMessage('Error en asignación masiva', 'error');
            }
        }

        // Confirmar asignación individual
        async function confirmarAsignacion() {
            const asesorCedula = document.getElementById('asesorSelect').value;
            const notas = document.getElementById('notasAsignacion').value;
            
            if (!asesorCedula) {
                showMessage('Seleccione un asesor', 'error');
                return;
            }
            
            if (!clienteSeleccionado) {
                showMessage('No hay titular seleccionado', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/assign_cliente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        titular_id: clienteSeleccionado.titular_id,
                        asesor_cedula: asesorCedula,
                        notas: notas
                    })
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
                    showMessage('Titular asignado correctamente', 'success');
                    closeAssignModal();
                    loadClientes();
                    loadDashboardData();
                } else {
                    showMessage('Error al asignar titular: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error asignando cliente:', error);
                showMessage('Error al asignar titular', 'error');
            }
        }

        // Cerrar modal de asignación individual
        function closeAssignModal() {
            closeModalAsignacion('assignModal');
            clienteSeleccionado = null;
            document.getElementById('asesorSelect').innerHTML = '<option value="">Cargando asesores...</option>';
            document.getElementById('notasAsignacion').value = '';
        }

        // Toggle selección de cliente
        function toggleClienteSeleccion(titularId) {
            const tid = String(titularId);
            const checkbox = document.getElementById(`titular_${tid}`);
            if (!checkbox) return;
            
            if (checkbox.checked) {
                if (!clientesSeleccionados.includes(tid)) {
                    clientesSeleccionados.push(tid);
                }
            } else {
                clientesSeleccionados = clientesSeleccionados.filter(x => x !== tid);
            }
            
            actualizarPanelAsignacion();
        }

        // Actualizar panel de asignación
        function actualizarPanelAsignacion() {
            const contador = document.getElementById('cantidadAsignar');
            if (contador) {
                contador.textContent = clientesSeleccionados.length;
            }
        }

        // Limpiar selección
        function limpiarSeleccion() {
            clientesSeleccionados = [];
            
            // Desmarcar todos los checkboxes
            document.querySelectorAll('#clientesList input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            actualizarPanelAsignacion();
        }

        async function asignarClientesGlobalmente() {
            const totalClientes = parseInt(document.getElementById('totalClientesAsignar').value);
            const notas = document.getElementById('notasAsignacionGlobal').value;
            
            if (!totalClientes || totalClientes <= 0) {
                showMessage('Ingrese una cantidad válida de titulares', 'error');
                return;
            }
            
            const clientesDisponibles = parseInt(document.getElementById('clientesDisponibles').textContent);
            if (totalClientes > clientesDisponibles) {
                showMessage(`No puede asignar más de ${clientesDisponibles} titulares sin asesor`, 'error');
                return;
            }
            
            if (!confirm(`¿Repartir ${totalClientes} titular(es) entre sus asesores en forma automática?`)) {
                return;
            }
            
            try {
                showMessage('Repartiendo titulares…', 'info');

                const response = await fetch('api/assign_clientes_automatico.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        total_clientes: totalClientes,
                        notas: notas
                    })
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
                    showMessage(result.message, 'success');
                    limpiarAsignacionGlobal();
                    loadClientes();
                    loadDashboardData();
                } else {
                    showMessage('Error al repartir titulares: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error asignando clientes:', error);
                showMessage('Error al repartir titulares', 'error');
            }
        }

        // Limpiar asignación global
        function limpiarAsignacionGlobal() {
            document.getElementById('totalClientesAsignar').value = '';
            document.getElementById('notasAsignacionGlobal').value = '';
        }

        // Ver detalles del asesor
        function verDetallesAsesor(asesorCedula) {
            showMessage('Función de detalles en desarrollo', 'info');
        }

        // Funciones de utilidad
        function refreshClientes() {
            // Limpiar búsqueda y resetear paginación
            document.getElementById('searchClientes').value = '';
            currentPage = 1;
            loadClientes();
        }

        function refreshAsesores() {
            loadAsesores();
        }

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            const contentArea = document.querySelector('.content-area');
            if (contentArea) {
                contentArea.insertBefore(messageDiv, contentArea.firstChild);
            } else {
                document.body.appendChild(messageDiv);
            }

            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Función para cerrar sesión
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

                if (!response.ok) {
                    if (response.status === 401) {
                        window.appGoLogin();
                        return;
                    }
                    throw new Error('Error HTTP: ' + response.status);
                }

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

        // Función para toggle del sidebar
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

        // Cerrar sidebar en desktop
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });

        document.querySelectorAll('.modal-assign-titular').forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                if (e.target !== modal) {
                    return;
                }
                if (modal.id === 'assignModal') {
                    closeAssignModal();
                } else if (modal.id === 'assignMasivoModal') {
                    closeAssignMasivoModal();
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') {
                return;
            }
            if (document.getElementById('assignModal').classList.contains('modal-open')) {
                closeAssignModal();
            }
            if (document.getElementById('assignMasivoModal').classList.contains('modal-open')) {
                closeAssignMasivoModal();
            }
        });
    </script>
</body>
</html>