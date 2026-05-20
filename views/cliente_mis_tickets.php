<?php
require_once __DIR__ . '/../config.php';

requireAuthRole('cliente');

$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/partials/app_head.php'; ?>
    <title>Mis Tickets - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/role-specific.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/tickets.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo logo-cliente">
                    <i class="fas fa-user"></i>
                    CRM
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Cliente</div>
                    <a href="<?php echo app_nav_url('cliente_dashboard'); ?>" class="nav-item">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo app_nav_url('cliente_mis_tickets'); ?>" class="nav-item active">
                        <i class="fas fa-ticket-alt"></i>
                        Mis Tickets
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="profile-card">
                    <div class="profile-avatar avatar-cliente">
                        <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h4 class="text-asesor"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h4>
                        <p class="text-asesor">Cliente</p>
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
                        <h1 class="title-asesor">Mis Tickets</h1>
                        <p class="subtitle-asesor">Listado de tus solicitudes y su estado.</p>
                    </div>
                </div>
            </div>

            <div class="content-area">
                <?php if ($message): ?>
                    <div class="message <?php echo htmlspecialchars($message['type']); ?>">
                        <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="users-section">
                    <div class="section-header">
                        <h2 class="section-title">Tickets</h2>
                        <button type="button" class="btn btn-secondary" onclick="cargarTickets()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Asunto</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Asesor</th>
                                    <th>Creado</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyTickets">
                                <tr><td colspan="6" style="text-align:center">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', cargarTickets);

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        const ESTADO_LABELS = {
            'comunicacion':'Comunicación','validacion':'Validación','proceso_judicial':'Proceso judicial',
            'remate':'Remate','recuperacion':'Recuperación','cierre':'Cierre'
        };

        async function cargarTickets() {
            const tbody = document.getElementById('tbodyTickets');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Cargando...</td></tr>';
            try {
                const response = await fetch('api/cliente_tickets.php', { credentials: 'same-origin' });
                const result = await response.json();
                if (!result.success) {
                    tbody.innerHTML = '<tr><td colspan="6">Error: ' + escapeHtml(result.message || '') + '</td></tr>';
                    return;
                }
                if (!result.data || !result.data.length) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">No tienes tickets registrados</td></tr>';
                    return;
                }
                tbody.innerHTML = result.data.map(function(t) {
                    var ref = t.numero_ticket ? t.numero_ticket : ('#' + t.id);
                    var estadoLabel = ESTADO_LABELS[t.estado] || (t.estado || '');
                    return '<tr>' +
                        '<td><strong>' + escapeHtml(ref) + '</strong></td>' +
                        '<td>' + escapeHtml(t.titulo || '') + '</td>' +
                        '<td>' + escapeHtml(t.categoria_nombre || '—') + '</td>' +
                        '<td><span class="ticket-estado-badge estado-' + escapeHtml(t.estado || '') + '">' + escapeHtml(estadoLabel) + '</span></td>' +
                        '<td>' + escapeHtml(t.asesor_nombre || '—') + '</td>' +
                        '<td>' + escapeHtml(t.fecha_creacion ? new Date(t.fecha_creacion).toLocaleString() : '') + '</td>' +
                        '</tr>';
                }).join('');
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6">Error de red</td></tr>';
            }
        }

        async function cerrarSesion() {
            if (!confirm('¿Cerrar sesión?')) return;
            try {
                await fetch('api/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin'
                });
            } catch (e) {}
            window.appGoLogin();
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>
