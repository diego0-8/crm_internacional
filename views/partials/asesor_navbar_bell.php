<?php
/** Campana de llamadas pendientes (hoy + vencidas) — navbar asesor */
?>
<div class="asesor-bell-wrap header-icon">
    <button type="button"
            class="asesor-bell-btn"
            id="asesorBellBtn"
            aria-label="Llamadas pendientes"
            aria-expanded="false"
            aria-controls="asesorBellPanel">
        <i class="fas fa-bell" aria-hidden="true"></i>
        <span class="notification-badge" id="notificationCount" hidden>0</span>
    </button>
    <div id="asesorBellPanel" class="asesor-bell-panel" role="region" aria-labelledby="asesorBellPanelTitle" hidden>
        <div class="asesor-bell-panel-header">
            <h4 id="asesorBellPanelTitle"><i class="fas fa-phone-volume" aria-hidden="true"></i> Llamadas pendientes</h4>
            <button type="button" class="asesor-bell-panel-close" id="asesorBellPanelClose" aria-label="Cerrar panel">&times;</button>
        </div>
        <div id="asesorBellList" class="asesor-bell-list">
            <p class="asesor-bell-status"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando…</p>
        </div>
    </div>
</div>
