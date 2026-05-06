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
    <title>Analytics - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../css/dashboard.css" rel="stylesheet">
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
                    <img src="../img/logo2.png" alt="Logo CRM">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="admin_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="analytics.php" class="nav-item active">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                    <a href="settings.php" class="nav-item">
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
                        <h1>Analytics & Reportes</h1>
                        <p>Información detallada del rendimiento del sistema CRM</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar métricas..." id="searchInput">
                    </div>
                    <div class="header-icon">
                        <i class="fas fa-download"></i>
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

                <!-- Overview Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Usuarios</div>
                            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalUsers">0</div>
                        <div class="stat-change" id="usersChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Clientes</div>
                            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
                                <i class="fas fa-user-friends"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalClients">0</div>
                        <div class="stat-change" id="clientsChange">
                            <i class="fas fa-info-circle"></i>
                            <span>Cargando...</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Tickets</div>
                            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalTickets">0</div>
                        <div class="stat-change">
                            <i class="fas fa-info-circle"></i>
                            <span>Sistema activo</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Archivos Procesados</div>
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">
                                <i class="fas fa-file-csv"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalFiles">0</div>
                        <div class="stat-change">
                            <i class="fas fa-info-circle"></i>
                            <span>CSV uploads</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="analytics-section">
                    <div class="section-header">
                        <h2 class="section-title">Distribución de Datos</h2>
                    </div>
                    <div class="charts-grid">
                        <div class="chart-card">
                            <h3>Usuarios por Rol</h3>
                            <canvas id="usersByRoleChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Estado de Clientes</h3>
                            <canvas id="clientsByStatusChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Tickets por Estado</h3>
                            <canvas id="ticketsByStatusChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Actividad por Hora</h3>
                            <canvas id="activityByHourChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Trends Section -->
                <div class="analytics-section">
                    <div class="section-header">
                        <h2 class="section-title">Tendencias (Últimos 30 días)</h2>
                    </div>
                    <div class="charts-grid">
                        <div class="chart-card full-width">
                            <h3>Crecimiento de Usuarios</h3>
                            <canvas id="usersTrendChart"></canvas>
                        </div>
                        <div class="chart-card full-width">
                            <h3>Crecimiento de Clientes</h3>
                            <canvas id="clientsTrendChart"></canvas>
                        </div>
                        <div class="chart-card full-width">
                            <h3>Tickets Creados</h3>
                            <canvas id="ticketsTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Performance Section -->
                <div class="analytics-section">
                    <div class="section-header">
                        <h2 class="section-title">Rendimiento del Sistema</h2>
                    </div>
                    <div class="performance-grid">
                        <div class="performance-card">
                            <h3>Top Coordinadores</h3>
                            <div id="topCoordinatorsList" class="performance-list">
                                <!-- Lista se cargará aquí -->
                            </div>
                        </div>
                        <div class="performance-card">
                            <h3>Top Asesores</h3>
                            <div id="topAdvisorsList" class="performance-list">
                                <!-- Lista se cargará aquí -->
                            </div>
                        </div>
                        <div class="performance-card">
                            <h3>Estadísticas de Archivos</h3>
                            <div id="fileStats" class="file-stats">
                                <!-- Estadísticas se cargarán aquí -->
                            </div>
                        </div>
                        <div class="performance-card">
                            <h3>Actividad Reciente</h3>
                            <div id="recentActivity" class="activity-list">
                                <!-- Actividad se cargará aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let analyticsData = {};
        let charts = {};

        // Inicializar analytics
        document.addEventListener('DOMContentLoaded', function() {
            loadAnalyticsData();
            initializeMobileSidebar();
        });

        // Cargar datos de analytics
        async function loadAnalyticsData() {
            try {
                showMessage('Cargando datos analíticos...', 'info');

                const response = await fetch('../api/analytics.php', {
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
                    analyticsData = result.data;
                    updateOverview();
                    renderCharts();
                    renderTrends();
                    renderPerformance();
                } else {
                    showMessage('Error cargando datos: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error cargando analytics:', error);
                showMessage('Error cargando datos analíticos', 'error');
            }
        }

        // Actualizar métricas generales
        function updateOverview() {
            document.getElementById('totalUsers').textContent = analyticsData.overview.total_users || 0;
            document.getElementById('totalClients').textContent = analyticsData.overview.total_clients || 0;
            document.getElementById('totalTickets').textContent = analyticsData.overview.total_tickets || 0;
            document.getElementById('totalFiles').textContent = analyticsData.overview.total_files || 0;

            // Actualizar cambios
            if (analyticsData.changes) {
                document.getElementById('usersChange').innerHTML = `
                    <i class="fas fa-arrow-${analyticsData.changes.users_change.includes('+') ? 'up' : 'down'}"></i>
                    <span>${analyticsData.changes.users_change}</span>
                `;
                document.getElementById('clientsChange').innerHTML = `
                    <i class="fas fa-arrow-${analyticsData.changes.clients_change.includes('+') ? 'up' : 'down'}"></i>
                    <span>${analyticsData.changes.clients_change}</span>
                `;
            }
        }

        // Renderizar gráficos
        function renderCharts() {
            // Usuarios por rol
            const usersByRoleCtx = document.getElementById('usersByRoleChart').getContext('2d');
            charts.usersByRole = new Chart(usersByRoleCtx, {
                type: 'doughnut',
                data: {
                    labels: analyticsData.charts.users_by_role.map(item => item.role),
                    datasets: [{
                        data: analyticsData.charts.users_by_role.map(item => item.count),
                        backgroundColor: ['#8b5cf6', '#10b981', '#3b82f6', '#f59e0b'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Clientes por estado
            const clientsByStatusCtx = document.getElementById('clientsByStatusChart').getContext('2d');
            charts.clientsByStatus = new Chart(clientsByStatusCtx, {
                type: 'bar',
                data: {
                    labels: analyticsData.charts.clients_by_status.map(item => item.estado),
                    datasets: [{
                        label: 'Clientes',
                        data: analyticsData.charts.clients_by_status.map(item => item.count),
                        backgroundColor: '#10b981',
                        borderColor: '#059669',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Tickets por estado
            const ticketsByStatusCtx = document.getElementById('ticketsByStatusChart').getContext('2d');
            charts.ticketsByStatus = new Chart(ticketsByStatusCtx, {
                type: 'pie',
                data: {
                    labels: analyticsData.charts.tickets_by_status.map(item => item.estado),
                    datasets: [{
                        data: analyticsData.charts.tickets_by_status.map(item => item.count),
                        backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ef4444'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Actividad por hora
            const activityByHourCtx = document.getElementById('activityByHourChart').getContext('2d');
            charts.activityByHour = new Chart(activityByHourCtx, {
                type: 'line',
                data: {
                    labels: analyticsData.performance.activity_by_hour.map(item => `${item.hour}:00`),
                    datasets: [{
                        label: 'Actividad',
                        data: analyticsData.performance.activity_by_hour.map(item => item.count),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Renderizar tendencias
        function renderTrends() {
            // Usuarios creados
            const usersTrendCtx = document.getElementById('usersTrendChart').getContext('2d');
            charts.usersTrend = new Chart(usersTrendCtx, {
                type: 'line',
                data: {
                    labels: analyticsData.trends.users_created.map(item => formatDate(item.date)),
                    datasets: [{
                        label: 'Usuarios creados',
                        data: analyticsData.trends.users_created.map(item => item.count),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Clientes creados
            const clientsTrendCtx = document.getElementById('clientsTrendChart').getContext('2d');
            charts.clientsTrend = new Chart(clientsTrendCtx, {
                type: 'line',
                data: {
                    labels: analyticsData.trends.clients_created.map(item => formatDate(item.date)),
                    datasets: [{
                        label: 'Clientes creados',
                        data: analyticsData.trends.clients_created.map(item => item.count),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Tickets creados
            const ticketsTrendCtx = document.getElementById('ticketsTrendChart').getContext('2d');
            charts.ticketsTrend = new Chart(ticketsTrendCtx, {
                type: 'line',
                data: {
                    labels: analyticsData.trends.tickets_created.map(item => formatDate(item.date)),
                    datasets: [{
                        label: 'Tickets creados',
                        data: analyticsData.trends.tickets_created.map(item => item.count),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Renderizar métricas de rendimiento
        function renderPerformance() {
            // Top coordinadores
            const topCoordinatorsList = document.getElementById('topCoordinatorsList');
            topCoordinatorsList.innerHTML = '';

            if (analyticsData.performance.top_coordinators.length === 0) {
                topCoordinatorsList.innerHTML = '<p>No hay datos disponibles</p>';
                return;
            }

            analyticsData.performance.top_coordinators.forEach((coord, index) => {
                const coordItem = document.createElement('div');
                coordItem.className = 'performance-item';
                coordItem.innerHTML = `
                    <div class="performance-rank">#${index + 1}</div>
                    <div class="performance-info">
                        <div class="performance-name">${coord.nombre} ${coord.apellido}</div>
                        <div class="performance-metric">${coord.clients_count} clientes, ${coord.files_count} archivos</div>
                    </div>
                `;
                topCoordinatorsList.appendChild(coordItem);
            });

            // Top asesores
            const topAdvisorsList = document.getElementById('topAdvisorsList');
            topAdvisorsList.innerHTML = '';

            if (analyticsData.performance.top_advisors.length === 0) {
                topAdvisorsList.innerHTML = '<p>No hay datos disponibles</p>';
                return;
            }

            analyticsData.performance.top_advisors.forEach((advisor, index) => {
                const advisorItem = document.createElement('div');
                advisorItem.className = 'performance-item';
                advisorItem.innerHTML = `
                    <div class="performance-rank">#${index + 1}</div>
                    <div class="performance-info">
                        <div class="performance-name">${advisor.nombre} ${advisor.apellido}</div>
                        <div class="performance-metric">${advisor.tickets_resolved} tickets resueltos</div>
                        <div class="performance-metric">${advisor.avg_resolution_time ? advisor.avg_resolution_time + 'h promedio' : 'Sin datos'}</div>
                    </div>
                `;
                topAdvisorsList.appendChild(advisorItem);
            });

            // Estadísticas de archivos
            const fileStats = document.getElementById('fileStats');
            fileStats.innerHTML = '';

            analyticsData.performance.file_statistics.forEach(stat => {
                const statItem = document.createElement('div');
                statItem.className = 'file-stat-item';
                statItem.innerHTML = `
                    <div class="file-stat-status">${stat.estado}</div>
                    <div class="file-stat-count">${stat.count} archivos</div>
                    <div class="file-stat-records">${stat.total_records} registros</div>
                    <div class="file-stat-rate">${stat.avg_completion_rate ? stat.avg_completion_rate.toFixed(1) + '%' : 'N/A'} completado</div>
                `;
                fileStats.appendChild(statItem);
            });

            // Actividad reciente
            const recentActivity = document.getElementById('recentActivity');
            recentActivity.innerHTML = '';

            if (analyticsData.system.recent_activity.length === 0) {
                recentActivity.innerHTML = '<p>No hay actividad reciente</p>';
                return;
            }

            // Agrupar por fecha
            const groupedActivity = {};
            analyticsData.system.recent_activity.forEach(activity => {
                if (!groupedActivity[activity.date]) {
                    groupedActivity[activity.date] = [];
                }
                groupedActivity[activity.date].push(activity);
            });

            Object.keys(groupedActivity).sort().reverse().forEach(date => {
                const dateItem = document.createElement('div');
                dateItem.className = 'activity-date-group';
                dateItem.innerHTML = `<h4>${formatDate(date)}</h4>`;

                groupedActivity[date].forEach(activity => {
                    const activityItem = document.createElement('div');
                    activityItem.className = 'activity-item';
                    activityItem.innerHTML = `
                        <div class="activity-action">${activity.accion}</div>
                        <div class="activity-count">${activity.count} veces</div>
                    `;
                    dateItem.appendChild(activityItem);
                });

                recentActivity.appendChild(dateItem);
            });
        }

        // Funciones de utilidad
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                month: 'short',
                day: 'numeric'
            });
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
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
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
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-card h3 {
            margin: 0 0 20px 0;
            color: #374151;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chart-card.full-width {
            grid-column: 1 / -1;
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .performance-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .performance-card h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .performance-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .performance-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
        }

        .performance-rank {
            font-weight: 600;
            color: #8b5cf6;
            min-width: 30px;
        }

        .performance-info {
            flex: 1;
        }

        .performance-name {
            font-weight: 500;
            color: #374151;
        }

        .performance-metric {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .file-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .file-stat-item {
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 4px solid #8b5cf6;
        }

        .file-stat-status {
            font-weight: 600;
            color: #374151;
            text-transform: capitalize;
        }

        .file-stat-count,
        .file-stat-records,
        .file-stat-rate {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-date-group {
            margin-bottom: 15px;
        }

        .activity-date-group h4 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .activity-action {
            font-weight: 500;
            color: #374151;
            text-transform: capitalize;
        }

        .activity-count {
            color: #6b7280;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }

            .performance-grid {
                grid-template-columns: 1fr;
            }

            .chart-card {
                padding: 15px;
            }

            .performance-card {
                padding: 15px;
            }
        }
    </style>
</body>
</html>