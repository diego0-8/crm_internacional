<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/RepartoImportModel.php';

requireAuthRole('coordinador');

// Obtener datos del usuario actual
$user = getCurrentUser();
$message = getMessage();

/** Cabeceras CSV obligatorias (inglés) — alineado con RepartoImportModel::CAMPOS_REQUERIDOS */
$columnasCsvRequeridas = array_merge(
    [
        'Case Number' => 'Número de caso (requiere Case Number o Parcel Number)',
        'Parcel Number' => 'Número de parcela',
    ],
    RepartoImportModel::CAMPOS_REQUERIDOS
);

// Repartir encabezados en 2–3 filas para que quepan en pantalla sin scroll horizontal
$totalColumnasCsv = count($columnasCsvRequeridas);
$filasColumnasObjetivo = 3;
$columnasPorFila = (int) max(1, (int) ceil($totalColumnasCsv / $filasColumnasObjetivo));
$filasColumnasRequeridas = array_chunk($columnasCsvRequeridas, $columnasPorFila, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/partials/app_head.php'; ?>
    <title>Gestión CSV - <?php echo APP_NAME; ?></title>
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
                    <a href="<?php echo app_nav_url('coordinador_tareas'); ?>" class="nav-item">
                        <i class="fas fa-tasks"></i>
                        Tareas
                    </a>
                    <a href="<?php echo app_nav_url('coordinador_gestion'); ?>" class="nav-item active">
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
                        <h1 class="title-asesor">Gestión de Archivos CSV</h1>
                        <p class="subtitle-asesor">Importa reparto (foreclosure) a titulares y propiedades. Solo se procesan filas completas según las columnas requeridas.</p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area coordinador-dashboard coordinador-gestion">
                <?php if ($message): ?>
                    <div class="message <?php echo $message['type']; ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <!-- Upload Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-upload"></i> Subir Archivo CSV</h3>
                    </div>
                    <div class="card-content">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-content" id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <h4>Arrastra y suelta tu archivo CSV aquí</h4>
                                    <p class="upload-hint">o haz clic en esta zona (fuera del botón) para seleccionar</p>
                                    <input type="file" id="csvFile" name="csv_file" accept=".csv" class="upload-file-input" tabindex="-1" aria-hidden="true">
                                    <button type="button" class="btn btn-primary" id="btnSelectCsv">
                                        <i class="fas fa-folder-open"></i> Seleccionar Archivo
                                    </button>
                                </div>
                            </div>
                            
                            <div class="file-info" id="fileInfo" style="display: none;">
                                <div class="file-details">
                                    <i class="fas fa-file-csv"></i>
                                    <div class="file-text">
                                        <h4 id="fileName"></h4>
                                        <p id="fileSize"></p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-success" id="uploadBtn" disabled>
                                    <i class="fas fa-upload"></i> Procesar Archivo
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Limpiar
                                </button>
                                <button type="button" class="btn btn-primary" onclick="openCreateClientModal()">
                                    <i class="fas fa-user-plus"></i> Crear Cliente Manualmente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Columnas requeridas -->
                <div class="card card-csv-requeridos">
                    <div class="card-header">
                        <h3><i class="fas fa-table"></i> Columnas requeridas (CSV en inglés)</h3>
                    </div>
                    <div class="card-content">
                        <p class="csv-requeridos-intro">
                            La primera fila del CSV debe incluir exactamente estos encabezados. Cada fila de datos debe tener
                            <strong>Case Number</strong> o <strong>Parcel Number</strong> (al menos uno) y el resto de columnas con valor.
                        </p>
                        <div class="csv-columnas-requeridas-wrap">
                            <table class="format-table columnas-requeridas-table">
                                <thead>
                                    <?php foreach ($filasColumnasRequeridas as $filaColumnas): ?>
                                        <tr>
                                            <?php foreach ($filaColumnas as $csvKey => $etiquetaEs): ?>
                                                <th title="<?php echo htmlspecialchars($etiquetaEs, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($csvKey, ENT_QUOTES, 'UTF-8'); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </thead>
                            </table>
                        </div>
                        <p class="csv-requeridos-nota">
                            <i class="fas fa-info-circle"></i>
                            Pase el cursor sobre cada encabezado para ver la descripción en español.
                            Columnas adicionales del archivo (teléfonos, correos, referencias, etc.) son opcionales si el modelo de reparto las trae.
                        </p>
                        <p class="csv-requeridos-nota csv-requeridos-contacto">
                            <i class="fas fa-address-book"></i>
                            <strong>Teléfonos y correos por fila (caso):</strong> si un número o correo se repite en varias columnas de la misma fila,
                            el caso <strong>sí se importa</strong> y solo se guardan los valores distintos (se omite el duplicado).
                            Aplica a <em>Phone 1–5</em>, <em>Email 1–5</em> y los de cada <em>RELATIVE n</em>.
                        </p>
                        <p class="csv-requeridos-nota csv-requeridos-duplicados">
                            <i class="fas fa-clone"></i>
                            <strong>Case Number y Parcel Number:</strong> el resto del archivo <strong>sí se importa</strong>.
                            Solo se omiten las filas cuyo Case o Parcel ya existan en la base de datos o se repitan dentro del mismo CSV.
                            Esas filas aparecen en el modal de resultado como casos no creados.
                        </p>
                    </div>
                </div>

                <!-- Resultado última importación: casos creados -->
                <div class="card card-filas-importadas" id="filasImportadasCard" style="display: none;">
                    <div class="card-header">
                        <h3><i class="fas fa-check-circle"></i> Casos importados (reparto)</h3>
                    </div>
                    <div class="card-content">
                        <p id="filasImportadasResumen" class="csv-requeridos-intro"></p>
                        <div class="csv-format-table">
                            <table class="format-table rechazos-table" id="filasImportadasTable">
                                <thead>
                                    <tr>
                                        <th>Fila CSV</th>
                                        <th>Referencia</th>
                                        <th>Tipo</th>
                                    </tr>
                                </thead>
                                <tbody id="filasImportadasBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resultado última importación: no creados -->
                <div class="card card-filas-rechazadas" id="filasRechazadasCard" style="display: none;">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-circle"></i> Casos no creados (información incompleta)</h3>
                    </div>
                    <div class="card-content">
                        <p id="filasRechazadasResumen" class="csv-requeridos-intro"></p>
                        <div class="csv-format-table">
                            <table class="format-table rechazos-table" id="filasRechazadasTable">
                                <thead>
                                    <tr>
                                        <th>Fila CSV</th>
                                        <th>Referencia</th>
                                        <th>Datos / columnas faltantes</th>
                                    </tr>
                                </thead>
                                <tbody id="filasRechazadasBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Archivos Subidos Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Archivos Subidos</h3>
                        <div class="header-actions">
                            <button class="btn btn-primary" onclick="loadArchivos()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <!-- Filters -->
                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="activoFilter">Disponibilidad</label>
                                <select id="activoFilter" class="form-control" onchange="applyFilters()">
                                    <option value="">Todos</option>
                                    <option value="1">Habilitados</option>
                                    <option value="0">Inhabilitados</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="estadoFilter">Estado</label>
                                <select id="estadoFilter" class="form-control" onchange="applyFilters()">
                                    <option value="">Todos los estados</option>
                                    <option value="completado">Completado</option>
                                    <option value="procesando">Procesando</option>
                                    <option value="error">Error</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="fechaFilter">Fecha</label>
                                <select id="fechaFilter" class="form-control" onchange="applyFilters()">
                                    <option value="">Todas las fechas</option>
                                    <option value="hoy">Hoy</option>
                                    <option value="semana">Esta semana</option>
                                    <option value="mes">Este mes</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button class="btn btn-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Limpiar Filtros
                                </button>
                            </div>
                        </div>

                        <!-- Files List -->
                        <div id="archivosList">
                            <!-- Los archivos se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal resultado importación CSV -->
    <div id="importResultModal" class="modal import-result-modal coordinador-modal">
        <div class="modal-content modal-scroll import-result-modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-file-csv"></i> Resultado de la importación</h3>
                <span class="close" onclick="closeImportResultModal()">&times;</span>
            </div>
            <div class="import-result-body">
                <p id="importResultResumen" class="import-result-resumen"></p>
                <section id="importResultOkSection" class="import-result-section" style="display: none;">
                    <h4><i class="fas fa-check-circle"></i> Casos importados</h4>
                    <p class="import-result-hint">Se muestra Case Number; si no existe, Parcel Number.</p>
                    <ul id="importResultOkList" class="import-result-list"></ul>
                </section>
                <section id="importResultFailSection" class="import-result-section import-result-section--fail" style="display: none;">
                    <h4><i class="fas fa-exclamation-triangle"></i> Casos no creados</h4>
                    <p class="import-result-hint">Incluye Case Number o Parcel Number duplicados (en BD o en el mismo archivo) y filas con datos incompletos.</p>
                    <ul id="importResultFailList" class="import-result-list import-result-list--fail"></ul>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeImportResultModal()">Entendido</button>
            </div>
        </div>
    </div>

    <!-- Modal detalles archivo CSV -->
    <div id="archivoDetalleModal" class="modal coordinador-modal">
        <div class="modal-content archivo-detalle-modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-file-csv"></i> Detalles del archivo</h3>
                <span class="close" onclick="closeArchivoDetalleModal()" aria-label="Cerrar">&times;</span>
            </div>
            <div class="modal-body" id="archivoDetalleBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeArchivoDetalleModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal para crear cliente manualmente -->
    <div id="createClientModal" class="modal coordinador-modal">
        <div class="modal-content modal-scroll">
            <div class="modal-header">
                <h3 class="modal-title">Crear Cliente Manualmente</h3>
                <span class="close" onclick="closeCreateClientModal()">&times;</span>
            </div>
            <form id="createClientForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientCedula">Cédula *</label>
                        <input type="text" id="clientCedula" name="cedula" class="form-control" required
                               placeholder="1234567890" pattern="[0-9]{7,20}">
                    </div>
                    <div class="form-group">
                        <label for="clientNombre">Nombre *</label>
                        <input type="text" id="clientNombre" name="nombre" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientApellido">Apellido *</label>
                        <input type="text" id="clientApellido" name="apellido" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="clientEmail">Email</label>
                        <input type="email" id="clientEmail" name="email" class="form-control"
                               placeholder="cliente@email.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientTelefono">Teléfono</label>
                        <input type="text" id="clientTelefono" name="telefono" class="form-control"
                               placeholder="+57 300 123 4567">
                    </div>
                    <div class="form-group">
                        <label for="clientEmpresa">Empresa</label>
                        <input type="text" id="clientEmpresa" name="empresa" class="form-control"
                               placeholder="Nombre de la empresa">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientCargo">Cargo</label>
                        <input type="text" id="clientCargo" name="cargo" class="form-control"
                               placeholder="Cargo en la empresa">
                    </div>
                    <div class="form-group">
                        <label for="clientAsesor">Asesor Asignado *</label>
                        <select id="clientAsesor" name="asesor_cedula" class="form-control" required>
                            <option value="">Seleccionar asesor</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="clientDireccion">Dirección</label>
                        <textarea id="clientDireccion" name="direccion" class="form-control" rows="2"
                                  placeholder="Dirección completa"></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientCiudad">Ciudad</label>
                        <input type="text" id="clientCiudad" name="ciudad" class="form-control"
                               placeholder="Ciudad">
                    </div>
                    <div class="form-group">
                        <label for="clientPais">País</label>
                        <input type="text" id="clientPais" name="pais" class="form-control"
                               placeholder="País" value="Colombia">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientCodigoPostal">Código Postal</label>
                        <input type="text" id="clientCodigoPostal" name="codigo_postal" class="form-control"
                               placeholder="Código postal">
                    </div>
                    <div class="form-group">
                        <label for="clientNotas">Notas</label>
                        <textarea id="clientNotas" name="notas" class="form-control" rows="2"
                                  placeholder="Notas adicionales sobre el cliente"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateClientModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="createClientSubmitBtn">Crear Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedFile = null;
        let archivos = [];
        let filteredArchivos = [];
        let uploadInitialized = false;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            initializeUpload();
            loadArchivos();
            loadAsesoresForClientCreation();
        });

        // Cargar archivos
        async function loadArchivos() {
            try {
                showMessage('Cargando archivos...', 'info');

                const response = await fetch('api/coordinador_archivos.php', {
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
                    archivos = result.data;
                    filteredArchivos = [...archivos];
                    renderArchivos();
                } else {
                    showMessage('Error cargando archivos: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando archivos:', error);
                showMessage('Error cargando archivos', 'error');
            }
        }

        function archivoEstaHabilitado(archivo) {
            return archivo.activo === undefined || archivo.activo === null || Number(archivo.activo) === 1;
        }

        // Renderizar archivos
        function renderArchivos() {
            const container = document.getElementById('archivosList');
            container.innerHTML = '';

            if (filteredArchivos.length === 0) {
                container.innerHTML = '<p style="color: #a0aec0; text-align: center; padding: 20px;">No hay archivos disponibles</p>';
                return;
            }

            filteredArchivos.forEach(archivo => {
                const habilitado = archivoEstaHabilitado(archivo);
                const archivoItem = document.createElement('div');
                archivoItem.className = 'archivo-item' + (habilitado ? '' : ' archivo-item-inactivo');
                const toggleBtn = habilitado
                    ? `<button class="btn btn-sm btn-warning" onclick="cambiarEstadoArchivo(${archivo.id}, 0)" title="Oculta el cargue para nuevas operaciones; conserva historial y tickets">
                            <i class="fas fa-ban"></i> Inhabilitar
                       </button>`
                    : `<button class="btn btn-sm btn-success" onclick="cambiarEstadoArchivo(${archivo.id}, 1)" title="Vuelve a habilitar el cargue">
                            <i class="fas fa-check-circle"></i> Habilitar
                       </button>`;

                archivoItem.innerHTML = `
                    <div class="archivo-info">
                        <div class="archivo-icon">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <div class="archivo-details">
                            <h4>${escapeHtmlCsv(archivo.nombre_archivo)}</h4>
                            <p>Subido: ${formatDate(archivo.created_at)}</p>
                            <p>Registros: ${archivo.registros_procesados || 0} de ${archivo.total_registros || 0}</p>
                        </div>
                        <div class="archivo-status">
                            <span class="status-badge status-${habilitado ? 'habilitado' : 'inhabilitado'}">${habilitado ? 'Habilitado' : 'Inhabilitado'}</span>
                            <span class="status-badge status-${archivo.estado}">${escapeHtmlCsv(archivo.estado)}</span>
                        </div>
                    </div>
                    <div class="archivo-actions">
                        <button class="btn btn-sm btn-info" onclick="verDetalles(${archivo.id})">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="descargarArchivo(${archivo.id})">
                            <i class="fas fa-download"></i> Descargar
                        </button>
                        ${toggleBtn}
                    </div>
                `;
                container.appendChild(archivoItem);
            });
        }

        // Aplicar filtros
        function applyFilters() {
            const activoFilter = document.getElementById('activoFilter').value;
            const estadoFilter = document.getElementById('estadoFilter').value;
            const fechaFilter = document.getElementById('fechaFilter').value;

            filteredArchivos = archivos.filter(archivo => {
                let matchesActivo = true;
                let matchesEstado = true;
                let matchesFecha = true;

                if (activoFilter !== '') {
                    const habilitado = archivoEstaHabilitado(archivo);
                    matchesActivo = activoFilter === '1' ? habilitado : !habilitado;
                }

                // Filtro por estado
                if (estadoFilter) {
                    matchesEstado = archivo.estado === estadoFilter;
                }

                // Filtro por fecha
                if (fechaFilter) {
                    const archivoFecha = new Date(archivo.created_at);
                    const today = new Date();
                    let fechaInicio;

                    switch (fechaFilter) {
                        case 'hoy':
                            fechaInicio = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                            break;
                        case 'semana':
                            fechaInicio = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                            break;
                        case 'mes':
                            fechaInicio = new Date(today.getFullYear(), today.getMonth(), 1);
                            break;
                    }

                    matchesFecha = archivoFecha >= fechaInicio;
                }

                return matchesActivo && matchesEstado && matchesFecha;
            });

            renderArchivos();
        }

        // Limpiar filtros
        function clearFilters() {
            document.getElementById('activoFilter').value = '';
            document.getElementById('estadoFilter').value = '';
            document.getElementById('fechaFilter').value = '';
            filteredArchivos = [...archivos];
            renderArchivos();
        }

        function closeArchivoDetalleModal() {
            const modal = document.getElementById('archivoDetalleModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Ver detalles del archivo (modal)
        function verDetalles(archivoId) {
            const archivo = archivos.find(a => a.id == archivoId);
            if (!archivo) return;

            const habilitado = archivoEstaHabilitado(archivo);
            const pct = archivo.total_registros > 0
                ? Math.round((Number(archivo.registros_procesados || 0) / Number(archivo.total_registros)) * 100)
                : 0;
            const body = document.getElementById('archivoDetalleBody');
            if (!body) return;

            body.innerHTML = `
                <dl class="archivo-detalle-dl">
                    <dt>Nombre del archivo</dt>
                    <dd>${escapeHtmlCsv(archivo.nombre_archivo)}</dd>
                    <dt>Fecha de subida</dt>
                    <dd>${escapeHtmlCsv(formatDate(archivo.created_at))}</dd>
                    <dt>Última actualización</dt>
                    <dd>${archivo.updated_at ? escapeHtmlCsv(formatDate(archivo.updated_at)) : '—'}</dd>
                    <dt>Disponibilidad</dt>
                    <dd><span class="status-badge status-${habilitado ? 'habilitado' : 'inhabilitado'}">${habilitado ? 'Habilitado' : 'Inhabilitado'}</span></dd>
                    <dt>Estado de procesamiento</dt>
                    <dd><span class="status-badge status-${escapeHtmlCsv(archivo.estado || '')}">${escapeHtmlCsv(archivo.estado || '—')}</span></dd>
                    <dt>Total de registros</dt>
                    <dd>${Number(archivo.total_registros || 0)}</dd>
                    <dt>Registros procesados</dt>
                    <dd>${Number(archivo.registros_procesados || 0)} (${pct}%)</dd>
                    <dt>ID en sistema</dt>
                    <dd>#${escapeHtmlCsv(String(archivo.id))}</dd>
                </dl>
            `;

            document.getElementById('archivoDetalleModal').style.display = 'block';
        }

        // Descargar archivo
        function descargarArchivo(archivoId) {
            window.open(`api/download_archivo.php?id=${archivoId}`, '_blank');
        }

        // Inhabilitar o habilitar cargue CSV (no elimina datos ni tickets)
        async function cambiarEstadoArchivo(archivoId, activo) {
            const habilitar = Number(activo) === 1;
            const mensaje = habilitar
                ? '¿Habilitar este cargue nuevamente?'
                : '¿Inhabilitar este cargue?\n\nNo se borrará nada: el historial en la aplicación y los tickets asociados se conservan. Solo dejará de estar activo para nuevas operaciones.';

            if (!confirm(mensaje)) {
                return;
            }

            try {
                const response = await fetch('api/toggle_archivo_csv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ archivo_id: archivoId, activo: habilitar ? 1 : 0 })
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
                    loadArchivos();
                } else {
                    showMessage('Error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cambiando estado del archivo:', error);
                showMessage('Error al cambiar el estado del archivo', 'error');
            }
        }

        // Función para formatear fecha
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function abrirSelectorCsv() {
            const fileInput = document.getElementById('csvFile');
            fileInput.value = '';
            fileInput.click();
        }

        // Inicializar funcionalidad de upload (una sola vez; evita doble diálogo de archivo)
        function initializeUpload() {
            if (uploadInitialized) {
                return;
            }
            uploadInitialized = true;

            const uploadArea = document.getElementById('uploadArea');
            const uploadContent = document.getElementById('uploadContent');
            const fileInput = document.getElementById('csvFile');
            const btnSelectCsv = document.getElementById('btnSelectCsv');
            const uploadForm = document.getElementById('uploadForm');

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0]);
                }
            });

            btnSelectCsv.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                abrirSelectorCsv();
            });

            uploadContent.addEventListener('click', function(e) {
                if (e.target.closest('button')) {
                    return;
                }
                abrirSelectorCsv();
            });

            fileInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                }
            });

            fileInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                uploadFile();
            });
        }

        // Manejar selección de archivo
        function handleFileSelect(file) {
            // Validar tipo de archivo
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showMessage('Por favor selecciona un archivo CSV válido', 'error');
                return;
            }

            // Validar tamaño (10MB máximo)
            if (file.size > 10 * 1024 * 1024) {
                showMessage('El archivo es demasiado grande. Máximo 10MB', 'error');
                return;
            }

            selectedFile = file;
            
            // Mostrar información del archivo
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('fileInfo').style.display = 'block';
            document.getElementById('uploadBtn').disabled = false;
        }

        // Remover archivo seleccionado
        function removeFile() {
            selectedFile = null;
            document.getElementById('fileInfo').style.display = 'none';
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('csvFile').value = '';
        }

        // Resetear formulario
        function resetForm() {
            removeFile();
            document.getElementById('uploadForm').reset();
        }

        function escapeHtmlCsv(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function referenciaDesdeFilaImport(fila) {
            if (fila.referencia) {
                return fila.referencia;
            }
            if (fila.identificador) {
                return fila.identificador;
            }
            if (fila.case_number) {
                return fila.case_number;
            }
            if (fila.parcel_number) {
                return fila.parcel_number;
            }
            return '—';
        }

        function etiquetaTipoReferencia(fila) {
            if (fila.tipo_referencia === 'case_number') {
                return 'Case Number';
            }
            if (fila.tipo_referencia === 'parcel_number') {
                return 'Parcel Number';
            }
            if (fila.case_number) {
                return 'Case Number';
            }
            if (fila.parcel_number) {
                return 'Parcel Number';
            }
            return '—';
        }

        function textoFaltantesFila(fila) {
            if (Array.isArray(fila.faltantes) && fila.faltantes.length > 0) {
                return fila.faltantes.join('; ');
            }
            return String(fila.motivo || fila.error || '');
        }

        function etiquetasCasoParcela(fila) {
            const partes = [];
            if (fila.case_number) {
                partes.push('Case: ' + fila.case_number);
            }
            if (fila.parcel_number) {
                partes.push('Parcel: ' + fila.parcel_number);
            }
            if (partes.length > 0) {
                return partes.join(' · ');
            }
            const ref = referenciaDesdeFilaImport(fila);
            return ref !== '—' ? ref : '';
        }

        function renderFilasImportadas(result) {
            const card = document.getElementById('filasImportadasCard');
            const body = document.getElementById('filasImportadasBody');
            const resumen = document.getElementById('filasImportadasResumen');
            if (!card || !body) {
                return;
            }

            const filas = Array.isArray(result.filas_importadas) ? result.filas_importadas : [];
            if (filas.length === 0) {
                card.style.display = 'none';
                body.innerHTML = '';
                if (resumen) {
                    resumen.textContent = '';
                }
                return;
            }

            card.style.display = 'block';
            if (resumen) {
                resumen.textContent = filas.length + ' caso(s) importado(s) correctamente (Case Number o Parcel Number según disponibilidad).';
            }

            body.innerHTML = '';
            filas.forEach(function(fila) {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + escapeHtmlCsv(String(fila.fila_csv ?? '—')) + '</td>' +
                    '<td><strong>' + escapeHtmlCsv(referenciaDesdeFilaImport(fila)) + '</strong></td>' +
                    '<td>' + escapeHtmlCsv(etiquetaTipoReferencia(fila)) + '</td>';
                body.appendChild(tr);
            });
        }

        function renderFilasRechazadas(result) {
            const card = document.getElementById('filasRechazadasCard');
            const body = document.getElementById('filasRechazadasBody');
            const resumen = document.getElementById('filasRechazadasResumen');
            if (!card || !body) {
                return;
            }

            let filas = Array.isArray(result.filas_rechazadas) ? result.filas_rechazadas : [];
            if (filas.length === 0 && Array.isArray(result.detalles_errores) && result.detalles_errores.length > 0) {
                filas = result.detalles_errores.map(function(msg) {
                    return { fila_csv: '—', identificador: '—', faltantes: [msg] };
                });
            }

            if (filas.length === 0) {
                card.style.display = 'none';
                body.innerHTML = '';
                if (resumen) {
                    resumen.textContent = '';
                }
                return;
            }

            card.style.display = 'block';
            const proc = result.registros_procesados ?? 0;
            const rech = result.registros_rechazados ?? filas.length;
            if (resumen) {
                resumen.textContent = 'Se importaron ' + proc + ' fila(s) y no se crearon ' + rech
                    + ' (duplicados Case/Parcel en BD o en el archivo, u otros errores). Detalle por fila:';
            }

            body.innerHTML = '';
            filas.forEach(function(fila) {
                const tr = document.createElement('tr');
                const refs = etiquetasCasoParcela(fila) || referenciaDesdeFilaImport(fila);
                const motivo = textoFaltantesFila(fila) || '—';
                tr.innerHTML =
                    '<td>' + escapeHtmlCsv(String(fila.fila_csv ?? '—')) + '</td>' +
                    '<td>' + escapeHtmlCsv(refs) + '</td>' +
                    '<td>' + escapeHtmlCsv(motivo) + '</td>';
                body.appendChild(tr);
            });
        }

        function openImportResultModal(result) {
            const modal = document.getElementById('importResultModal');
            if (!modal || result.formato !== 'reparto_foreclosure') {
                return;
            }

            const importadas = Array.isArray(result.filas_importadas) ? result.filas_importadas : [];
            let rechazadas = Array.isArray(result.filas_rechazadas) ? result.filas_rechazadas : [];
            if (rechazadas.length === 0 && importadas.length === 0 && Array.isArray(result.detalles_errores)) {
                rechazadas = result.detalles_errores.map(function(msg) {
                    return { fila_csv: '—', faltantes: [msg] };
                });
            }

            const proc = result.registros_procesados ?? importadas.length;
            const rech = result.registros_rechazados ?? rechazadas.length;

            if (importadas.length === 0 && rechazadas.length === 0) {
                return;
            }
            const resumenEl = document.getElementById('importResultResumen');
            if (resumenEl) {
                resumenEl.textContent = 'Importados: ' + proc + ' · No creados: ' + rech + '.';
            }

            const okSection = document.getElementById('importResultOkSection');
            const okList = document.getElementById('importResultOkList');
            if (okSection && okList) {
                if (importadas.length > 0) {
                    okSection.style.display = 'block';
                    okList.innerHTML = '';
                    importadas.forEach(function(fila) {
                        const li = document.createElement('li');
                        const ref = referenciaDesdeFilaImport(fila);
                        const tipo = etiquetaTipoReferencia(fila);
                        li.innerHTML = '<span class="import-result-fila">Fila ' + escapeHtmlCsv(String(fila.fila_csv)) + '</span> ' +
                            '<span class="import-result-ref">' + escapeHtmlCsv(ref) + '</span> ' +
                            '<span class="import-result-tipo">(' + escapeHtmlCsv(tipo) + ')</span>';
                        okList.appendChild(li);
                    });
                } else {
                    okSection.style.display = 'none';
                    okList.innerHTML = '';
                }
            }

            const failSection = document.getElementById('importResultFailSection');
            const failList = document.getElementById('importResultFailList');
            if (failSection && failList) {
                if (rechazadas.length > 0) {
                    failSection.style.display = 'block';
                    failList.innerHTML = '';
                    rechazadas.forEach(function(fila) {
                        const li = document.createElement('li');
                        const refs = etiquetasCasoParcela(fila);
                        const faltantes = textoFaltantesFila(fila) || 'Información insuficiente';
                        const filaNum = fila.fila_csv != null && fila.fila_csv !== '—' ? 'Fila ' + fila.fila_csv : 'Fila desconocida';
                        li.innerHTML = '<span class="import-result-fila">' + escapeHtmlCsv(filaNum) + '</span> ' +
                            (refs ? '<span class="import-result-ref">' + escapeHtmlCsv(refs) + '</span> — ' : '') +
                            '<span class="import-result-faltantes">' + escapeHtmlCsv(faltantes) + '</span>';
                        failList.appendChild(li);
                    });
                } else {
                    failSection.style.display = 'none';
                    failList.innerHTML = '';
                }
            }

            modal.style.display = 'block';
        }

        function closeImportResultModal() {
            const modal = document.getElementById('importResultModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function renderResultadoImportacion(result) {
            renderFilasImportadas(result);
            renderFilasRechazadas(result);
            openImportResultModal(result);
        }

        // Subir archivo
        async function uploadFile() {
            if (!selectedFile) {
                showMessage('Por favor selecciona un archivo', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', selectedFile);

            try {
                showMessage('Procesando archivo...', 'info');
                document.getElementById('uploadBtn').disabled = true;

                const response = await fetch('api/mass_upload_init.php', {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    let msg = result.message;
                    if (result.formato === 'reparto_foreclosure') {
                        msg += ' — titulares y propiedades (reparto)';
                    }
                    const tipo = (result.registros_rechazados || 0) > 0 ? 'info' : 'success';
                    showMessage(msg, tipo);
                    if (result.formato === 'reparto_foreclosure') {
                        renderResultadoImportacion(result);
                    } else {
                        renderFilasRechazadas(result);
                    }
                    resetForm();
                    if (typeof loadArchivos === 'function') {
                        loadArchivos();
                    }
                } else {
                    showMessage('Error procesando archivo: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error procesando archivo', 'error');
            } finally {
                document.getElementById('uploadBtn').disabled = false;
            }
        }

        // Funciones para crear cliente manualmente
        function openCreateClientModal() {
            document.getElementById('createClientForm').reset();
            loadAsesoresForClientCreation();
            document.getElementById('createClientModal').style.display = 'block';
        }

        function closeCreateClientModal() {
            document.getElementById('createClientModal').style.display = 'none';
            document.getElementById('createClientForm').reset();
        }

        // Cargar asesores para creación de cliente
        async function loadAsesoresForClientCreation() {
            try {
                const response = await fetch('api/coordinador_asesores.php', {
                    credentials: 'include'
                });

                if (!response.ok) {
                    console.error('Error cargando asesores');
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    const asesorSelect = document.getElementById('clientAsesor');
                    asesorSelect.innerHTML = '<option value="">Seleccionar asesor</option>';

                    result.data.forEach(asesor => {
                        asesorSelect.innerHTML += `<option value="${asesor.cedula}">${asesor.nombre} ${asesor.apellido}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error cargando asesores:', error);
            }
        }

        // Validar formulario de cliente
        function validateClientForm() {
            const cedula = document.getElementById('clientCedula').value.trim();
            const nombre = document.getElementById('clientNombre').value.trim();
            const apellido = document.getElementById('clientApellido').value.trim();
            const asesor = document.getElementById('clientAsesor').value;

            if (!cedula || !nombre || !apellido || !asesor) {
                showMessage('Por favor complete todos los campos obligatorios', 'error');
                return false;
            }

            if (!/^[0-9]{7,20}$/.test(cedula)) {
                showMessage('La cédula debe contener solo números (7-20 dígitos)', 'error');
                return false;
            }

            const email = document.getElementById('clientEmail').value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('El email no tiene un formato válido', 'error');
                return false;
            }

            return true;
        }

        // Manejar envío del formulario de creación de cliente
        document.getElementById('createClientForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validar formulario
            if (!validateClientForm()) {
                return;
            }

            const submitBtn = document.getElementById('createClientSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(this);

                const response = await fetch('api/crear_cliente.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    closeCreateClientModal();
                    showMessage(result.message, 'success');
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error creando cliente:', error);
                showMessage('Error creando cliente', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Funciones de utilidad
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

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

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const importModal = document.getElementById('importResultModal');
            if (event.target === importModal) {
                closeImportResultModal();
            }
            const clientModal = document.getElementById('createClientModal');
            if (event.target === clientModal) {
                closeCreateClientModal();
            }
            const archivoModal = document.getElementById('archivoDetalleModal');
            if (event.target === archivoModal) {
                closeArchivoDetalleModal();
            }
        }
    </script>

    <style>
        .upload-file-input {
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
            overflow: hidden;
            pointer-events: none;
        }

        /* Tema claro: anula .upload-area oscuro de dashboard.css */
        .coordinador-gestion .upload-area {
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            background: #ffffff;
            color: #1e293b;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.15);
        }

        .coordinador-gestion .upload-area:hover,
        .coordinador-gestion .upload-area.dragover {
            border-color: var(--secondary-blue, #1e88e5);
            background: #f0f9ff;
            transform: none;
        }

        .coordinador-gestion .upload-content h4 {
            margin: 0 0 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }

        .coordinador-gestion .upload-content p,
        .coordinador-gestion .upload-hint {
            margin: 0 0 1rem;
            font-size: 0.9375rem;
            color: #475569;
            cursor: pointer;
        }

        .coordinador-gestion .upload-content i.fa-cloud-upload-alt {
            font-size: 3rem;
            color: var(--secondary-blue, #1e88e5);
            margin-bottom: 1rem;
        }

        .coordinador-gestion .filters-row {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem 1.25rem;
        }

        .coordinador-gestion .filter-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #0f172a;
        }

        .coordinador-gestion .filter-group .form-control {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            font-size: 0.9375rem;
        }

        .coordinador-gestion .filter-group .form-control:focus {
            border-color: var(--secondary-blue, #1e88e5);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.15);
            background: #ffffff;
            color: #1e293b;
        }

        .coordinador-gestion .filter-group select.form-control option {
            background: #ffffff;
            color: #1e293b;
        }

        .archivo-detalle-modal-content {
            max-width: 520px;
        }

        .archivo-detalle-dl {
            display: grid;
            grid-template-columns: minmax(8.5rem, 38%) 1fr;
            gap: 0.65rem 1rem;
            margin: 0;
        }

        .archivo-detalle-dl dt {
            margin: 0;
            font-weight: 600;
            font-size: 0.875rem;
            color: #64748b;
        }

        .archivo-detalle-dl dd {
            margin: 0;
            font-size: 0.9375rem;
            color: #0f172a;
            word-break: break-word;
        }

        .coordinador-gestion .coordinador-modal .form-control {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }

        .coordinador-gestion .coordinador-modal .form-group label {
            color: #0f172a;
            font-weight: 600;
        }

        .upload-hint {
            cursor: pointer;
        }

        .csv-requeridos-intro {
            color: #4b5563;
            font-size: 0.9rem;
            margin: 0 0 1rem;
            line-height: 1.5;
        }

        .csv-requeridos-nota {
            color: #6b7280;
            font-size: 0.8125rem;
            margin: 0.75rem 0 0;
        }

        .csv-requeridos-contacto {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
        }

        .csv-columnas-requeridas-wrap {
            margin: 0;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .columnas-requeridas-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            margin-bottom: 0;
        }

        .columnas-requeridas-table thead tr th {
            white-space: normal;
            word-break: break-word;
            hyphens: auto;
            font-size: clamp(0.65rem, 1.1vw, 0.8rem);
            vertical-align: middle;
            text-align: center;
            padding: 0.45rem 0.35rem;
            line-height: 1.25;
        }

        .columnas-requeridas-table thead tr + tr th {
            border-top: 1px dashed #e5e7eb;
        }

        @media (max-width: 768px) {
            .columnas-requeridas-table thead tr th {
                font-size: 0.65rem;
                padding: 0.35rem 0.2rem;
            }
        }

        .coordinador-gestion .file-info {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .file-details {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-details i {
            font-size: 2rem;
            color: #059669;
        }

        .file-text h4 {
            margin: 0;
            color: #374151;
        }

        .file-text p {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .instructions {
            line-height: 1.6;
        }

        .instructions h4 {
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .instructions ol, .instructions ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        .instructions li {
            margin: 0.25rem 0;
        }

        .coordinador-gestion .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
            margin-bottom: 1.5rem;
        }

        .coordinador-gestion .filter-group {
            display: flex;
            flex-direction: column;
        }

        .archivo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background-color: #ffffff;
        }

        .archivo-item-inactivo {
            background-color: #f9fafb;
            border-color: #d1d5db;
            opacity: 0.92;
        }

        .archivo-item-inactivo .archivo-icon i {
            color: #9ca3af;
        }

        .archivo-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .archivo-icon i {
            font-size: 2rem;
            color: #059669;
        }

        .archivo-details h4 {
            margin: 0;
            color: #374151;
        }

        .archivo-details p {
            margin: 0.25rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .archivo-status {
            margin-left: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            align-items: flex-end;
        }

        .archivo-actions {
            display: flex;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completado {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-procesando {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-habilitado {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-inhabilitado {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        /* Modal scroll styles */
        .modal-scroll {
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-scroll .modal-content {
            max-height: none;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            flex: 1 1 100%;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        /* CSV Instructions Styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
        }

        .alert-danger {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }

        .alert i {
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }

        .csv-format-table {
            margin: 1.5rem 0;
            overflow-x: auto;
        }

        .format-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .format-table th,
        .format-table td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid #e5e7eb;
        }

        .format-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .format-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .required {
            color: #dc2626;
            font-weight: 600;
        }

        .optional {
            color: #059669;
            font-weight: 600;
        }

        .csv-example {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .csv-example pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .csv-validation-tips {
            background-color: #ecfdf5;
            border: 1px solid #10b981;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .csv-validation-tips h4 {
            color: #059669;
            margin-bottom: 0.5rem;
        }

        .csv-validation-tips ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        .csv-validation-tips li {
            margin: 0.25rem 0;
            color: #065f46;
        }

        .form-control {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #60643A;
            box-shadow: 0 0 0 3px rgba(96, 100, 58, 0.1);
        }

        .form-control:invalid {
            border-color: #ef4444;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 1.5rem;
        }
    </style>
</body>
</html>
