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
    <title>Gestión de Tareas - <?php echo APP_NAME; ?></title>
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
                    <a href="coordinador_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="coordinador_tareas.php" class="nav-item active">
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
                        <h1 class="title-asesor">Gestión de Tareas</h1>
                        <p class="subtitle-asesor">Asignación de clientes a asesores de manera eficiente.</p>
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
                        <?php echo $message['message']; ?>
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
                            <p>Total Clientes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="clientesAsignados">0</h3>
                            <p>Clientes Asignados</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="clientesDisponibles">0</h3>
                            <p>Clientes Disponibles</p>
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
                        <h3><i class="fas fa-user-plus"></i> Asignación Individual</h3>
                        <button class="btn btn-primary" onclick="refreshClientes()">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="search-bar">
                            <input type="text" id="searchClientes" placeholder="Buscar por cédula, nombre o teléfono..." onkeyup="debounceSearch()">
                            <i class="fas fa-search"></i>
                        </div>
                        <div id="clientesContainer">
                            <div id="clientesList" class="clientes-grid">
                                <!-- Lista de clientes se carga aquí -->
                            </div>
                            <div id="paginationContainer" class="pagination-container" style="display: none;">
                                <div class="pagination-info">
                                    <span id="paginationInfo">Mostrando 1-5 de 0 clientes</span>
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
                                <label for="totalClientesAsignar">Total de clientes a asignar</label>
                                <input type="number" id="totalClientesAsignar" class="form-control form-control-narrow" min="1" placeholder="Ej. 10">
                            </div>
                            <div class="form-group">
                                <label for="notasAsignacionGlobal">Notas (opcional)</label>
                                <textarea id="notasAsignacionGlobal" class="form-control" rows="3" placeholder="Notas sobre la asignación..."></textarea>
                            </div>
                            <div class="asignacion-masiva-actions">
                                <button type="button" class="btn btn-success" onclick="asignarClientesGlobalmente()">
                                    <i class="fas fa-users"></i> Asignar Clientes Globalmente
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
    <div id="assignModal" class="modal coordinador-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Asignar Cliente</h3>
                <span class="close" onclick="closeAssignModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="clienteInfo"></div>
                <div class="form-group">
                    <label for="asesorSelect">Asesor</label>
                    <select id="asesorSelect" class="form-control">
                        <option value="">Cargando asesores...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notasAsignacion">Notas (opcional)</label>
                    <textarea id="notasAsignacion" class="form-control" rows="3" placeholder="Notas sobre la asignación..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAssignModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarAsignacion()">Asignar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Asignación Masiva -->
    <div id="assignMasivoModal" class="modal coordinador-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Asignación Masiva</h3>
                <span class="close" onclick="closeAssignMasivoModal()">&times;</span>
            </div>
            <div class="modal-body">
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
                    <p><strong>Clientes seleccionados:</strong> <span id="cantidadAsignar">0</span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAssignMasivoModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarAsignacionMasiva()">Asignar Todos</button>
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
        let itemsPerPage = 5;
        let searchTimeout = null;

        // Cargar datos al inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadClientes();
            loadAsesores();
        });

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
                const response = await fetch('../api/coordinador_clientes.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.href = '../views/login.php';
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
                    showMessage(result.message || 'Error al cargar los datos de clientes', 'error');
                    clientes = [];
                    clientesFiltrados = [];
                    renderClientes();
                }
            } catch (error) {
                console.error('Error cargando clientes:', error);
                showMessage('Error de conexión al cargar clientes. Verifica tu conexión a internet.', 'error');
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

        // Renderizar clientes con paginación
        function renderClientes() {
            // Aplicar filtros de búsqueda si existe término de búsqueda
            const searchTerm = document.getElementById('searchClientes').value.trim();
            if (searchTerm) {
                filtrarClientes(searchTerm);
            } else {
                clientesFiltrados = [...clientes];
            }

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
                            <p>No hay clientes que coincidan con "${searchTerm}"</p>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h4>No hay clientes disponibles</h4>
                            <p>Todavía no se han cargado clientes en el sistema</p>
                        </div>
                    `;
                }
                document.getElementById('paginationContainer').style.display = 'none';
                return;
            }

            // Renderizar tarjetas de clientes
            clientesPagina.forEach(cliente => {
                const clienteCard = document.createElement('div');
                clienteCard.className = `cliente-card ${cliente.asesor_nombre ? 'assigned' : ''}`;

                clienteCard.innerHTML = `
                    <div class="cliente-card-header">
                        <div class="cliente-card-info">
                            <h4>${cliente.nombre} ${cliente.apellido}</h4>
                            <p><i class="fas fa-id-card"></i> ${cliente.cedula || 'Sin cédula'}</p>
                            <p><i class="fas fa-envelope"></i> ${cliente.email || 'Sin email'}</p>
                            <p><i class="fas fa-phone"></i> ${cliente.telefono || 'Sin teléfono'}</p>
                            <p><i class="fas fa-building"></i> ${cliente.empresa || 'Sin empresa'}</p>
                        </div>
                        <span class="cliente-status status-${cliente.estado || 'nuevo'}">${cliente.estado || 'nuevo'}</span>
                    </div>
                    <div class="cliente-card-actions">
                        ${cliente.asesor_nombre ?
                            `<div class="cliente-assigned-info">
                                <i class="fas fa-user-check"></i> Asignado a: ${cliente.asesor_nombre}
                             </div>` :
                            `<div style="display: flex; gap: 0.5rem;">
                                <input type="checkbox" id="cliente_${cliente.cedula}" value="${cliente.cedula}"
                                       onchange="toggleClienteSeleccion('${cliente.cedula}')">
                                <button class="btn btn-sm btn-primary" onclick="abrirModalAsignacion('${cliente.cedula}')">
                                    <i class="fas fa-user-plus"></i> Asignar
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
                // Búsqueda insensible a mayúsculas y con coincidencias parciales
                const termLower = searchTerm.toLowerCase();
                clientesFiltrados = clientes.filter(cliente => {
                    const cedula = (cliente.cedula || '').toLowerCase();
                    const nombre = (cliente.nombre || '').toLowerCase();
                    const apellido = (cliente.apellido || '').toLowerCase();
                    const nombreCompleto = `${nombre} ${apellido}`.trim();
                    const telefono = (cliente.telefono || '').toLowerCase();

                    return cedula.includes(termLower) ||
                           nombre.includes(termLower) ||
                           apellido.includes(termLower) ||
                           nombreCompleto.includes(termLower) ||
                           telefono.includes(termLower);
                });
            }

            // Resetear a página 1 cuando se busca
            currentPage = 1;

            // Re-renderizar con los resultados filtrados
            renderClientes();
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
                infoElement.textContent = 'No hay clientes';
            } else {
                infoElement.textContent = `Mostrando ${start}-${end} de ${total} clientes`;
            }
        }

        // Cargar asesores
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
                                    <span id="clientesAsignados_${asesor.cedula}">0</span> clientes
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
        function abrirModalAsignacion(clienteCedula) {
            const cliente = clientes.find(c => c.cedula == clienteCedula);
            if (!cliente) return;
            
            clienteSeleccionado = cliente;
            mostrarInformacionCliente(cliente);
            cargarAsesores();
            document.getElementById('assignModal').style.display = 'block';
        }

        // Mostrar información del cliente en el modal
        function mostrarInformacionCliente(cliente) {
            const clienteInfo = document.getElementById('clienteInfo');
            clienteInfo.innerHTML = `
                <div class="cliente-info-card">
                    <div class="cliente-header">
                        <h4>${cliente.nombre} ${cliente.apellido}</h4>
                        <span class="status-badge status-${cliente.estado}">${cliente.estado}</span>
                    </div>
                    <div class="cliente-details">
                        <p><strong>Email:</strong> ${cliente.email || 'No disponible'}</p>
                        <p><strong>Empresa:</strong> ${cliente.empresa || 'No disponible'}</p>
                        <p><strong>Teléfono:</strong> ${cliente.telefono || 'No disponible'}</p>
                        <p><strong>Ciudad:</strong> ${cliente.ciudad || 'No disponible'}</p>
                    </div>
                </div>
            `;
        }

        // Cargar asesores para el modal
        async function cargarAsesores() {
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
        function renderAsesoresSelect(asesoresData) {
            const select = document.getElementById('asesorSelect');
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

        // Confirmar asignación individual
        async function confirmarAsignacion() {
            const asesorCedula = document.getElementById('asesorSelect').value;
            const notas = document.getElementById('notasAsignacion').value;
            
            if (!asesorCedula) {
                showMessage('Seleccione un asesor', 'error');
                return;
            }
            
            if (!clienteSeleccionado) {
                showMessage('No hay cliente seleccionado', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/assign_cliente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        cliente_id: clienteSeleccionado.cedula,
                        asesor_cedula: asesorCedula,
                        notas: notas
                    })
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
                    showMessage('Cliente asignado exitosamente', 'success');
                    closeAssignModal();
                    loadClientes();
                    loadDashboardData();
                } else {
                    showMessage('Error asignando cliente: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error asignando cliente:', error);
                showMessage('Error asignando cliente', 'error');
            }
        }

        // Cerrar modal de asignación individual
        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
            clienteSeleccionado = null;
            document.getElementById('asesorSelect').innerHTML = '<option value="">Cargando asesores...</option>';
            document.getElementById('notasAsignacion').value = '';
        }

        // Toggle selección de cliente
        function toggleClienteSeleccion(clienteCedula) {
            const checkbox = document.getElementById(`cliente_${clienteCedula}`);
            const cliente = clientes.find(c => c.cedula == clienteCedula);
            
            if (checkbox.checked) {
                if (!clientesSeleccionados.includes(clienteCedula)) {
                    clientesSeleccionados.push(clienteCedula);
                }
            } else {
                clientesSeleccionados = clientesSeleccionados.filter(cedula => cedula !== clienteCedula);
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

        // Asignar clientes globalmente
        async function asignarClientesGlobalmente() {
            const totalClientes = parseInt(document.getElementById('totalClientesAsignar').value);
            const notas = document.getElementById('notasAsignacionGlobal').value;
            
            if (!totalClientes || totalClientes <= 0) {
                showMessage('Ingrese una cantidad válida de clientes', 'error');
                return;
            }
            
            const clientesDisponibles = parseInt(document.getElementById('clientesDisponibles').textContent);
            if (totalClientes > clientesDisponibles) {
                showMessage(`No puede asignar más de ${clientesDisponibles} clientes`, 'error');
                return;
            }
            
            if (!confirm(`¿Está seguro de asignar ${totalClientes} clientes a los asesores?`)) {
                return;
            }
            
            try {
                showMessage('Asignando clientes globalmente...', 'info');

                const response = await fetch('../api/assign_clientes_automatico.php', {
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
                        window.location.href = '../views/login.php';
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
                    showMessage('Error asignando clientes: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error asignando clientes:', error);
                showMessage('Error asignando clientes', 'error');
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
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
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
                    window.location.href = '../views/login.php';
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
    </script>
</body>
</html>