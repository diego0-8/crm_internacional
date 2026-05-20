/**
 * Renderizado de notas de ticket (texto libre y tipificación JSON v1).
 */
(function (global) {
    'use strict';

    function parseTipificacionNotaJson(text) {
        var s = String(text || '').trim();
        if (!s || s.charAt(0) !== '{') {
            return null;
        }
        try {
            var o = JSON.parse(s);
            if (o && o.tipo === 'tipificacion_actualizacion_v1') {
                return o;
            }
        } catch (eJ) { /* no es JSON válido */ }
        return null;
    }

    function buildTipificacionHistorialHtml(parsed, escapeHtml) {
        var esc = escapeHtml || function (x) { return String(x); };
        var ek = String(parsed.estado_key || '').trim();
        var el = String(parsed.estado_label || '').trim() || ek;
        var claseEst = ek.replace(/[^a-z0-9_]/gi, '') || 'sin_estado';
        var h = '<div class="nota-tipif-fields">';
        h += '<div class="nota-tipif-field">';
        h += '<span class="nota-tipif-field-label">Estado</span>';
        h += '<div class="nota-estado-line"><span class="ticket-estado-badge estado-' + claseEst + '">';
        h += '<i class="fas fa-flag-checkered" aria-hidden="true"></i> ';
        h += esc(el) + '</span></div></div>';
        h += '<div class="nota-tipif-field">';
        h += '<span class="nota-tipif-field-label">¿Aplica esta gestión?</span>';
        h += '<div class="nota-estado-line"><span class="ticket-estado-badge nota-tipif-valor nota-tipif-valor-aplica">';
        h += '<i class="fas fa-exchange-alt" aria-hidden="true"></i> ';
        h += esc(String(parsed.aplica_gestion || '—')) + '</span></div></div>';
        h += '<div class="nota-tipif-field">';
        h += '<span class="nota-tipif-field-label">Confirmar la siguiente acción</span>';
        h += '<div class="nota-estado-line"><span class="ticket-estado-badge nota-tipif-valor nota-tipif-valor-accion">';
        h += '<i class="fas fa-check-double" aria-hidden="true"></i> ';
        h += esc(String(parsed.accion_confirmada || '—')) + '</span></div></div>';
        h += '</div>';
        return h;
    }

    function renderTicketNotaItem(nota, escapeHtml) {
        var esc = escapeHtml || function (x) { return String(x); };
        var estKey = String(nota.estado_ticket || '').trim();
        var estLabel = String(nota.estado_label || estKey || '').trim();
        var tipoNota = String(nota.tipo_nota || 'asesor').trim() || 'asesor';
        var esTipifTipo = tipoNota === 'tipificacion_actualizacion';
        var parsedTip = parseTipificacionNotaJson(nota.contenido);
        var esVistaTipif = esTipifTipo || parsedTip !== null;

        var estadoHtml = '';
        if (!parsedTip && estKey && estLabel) {
            estadoHtml = '<div class="nota-estado-line"><span class="ticket-estado-badge estado-' +
                estKey.replace(/[^a-z0-9_]/gi, '') + '">' +
                '<i class="fas fa-route" aria-hidden="true"></i> ' + esc(estLabel) +
                '</span></div>';
        }

        var badgeTipo = '';
        if (esVistaTipif) {
            badgeTipo = '<div class="nota-tipo-line"><span class="nota-tipo-badge tipificacion">' +
                '<i class="fas fa-sitemap" aria-hidden="true"></i> Tipificación (actualización)</span></div>';
        }

        var cuerpo = '';
        if (parsedTip) {
            cuerpo = buildTipificacionHistorialHtml(parsedTip, esc);
        } else if (String(nota.contenido || '').trim() !== '') {
            cuerpo = '<div class="nota-contenido">' + esc(nota.contenido) + '</div>';
        }

        var prox = '';
        if (nota.proxima_accion && String(nota.proxima_accion).trim() !== '') {
            prox = '<div class="nota-accion"><strong>Próxima acción:</strong> ' + esc(nota.proxima_accion) + '</div>';
        }

        var fechaProx = '';
        if (nota.fecha_proxima_accion) {
            try {
                fechaProx = '<div class="nota-fecha-prox"><strong>Fecha próxima acción:</strong> ' +
                    esc(new Date(nota.fecha_proxima_accion).toLocaleString()) + '</div>';
            } catch (eFp) { /* ignore */ }
        }

        var fechaCreacion = '';
        try {
            fechaCreacion = new Date(nota.fecha_creacion).toLocaleString();
        } catch (eFc) {
            fechaCreacion = String(nota.fecha_creacion || '');
        }

        return '<div class="nota-item' + (esVistaTipif ? ' nota-item-tipificacion' : '') + '">' +
            '<div class="nota-header">' +
            '<span class="nota-fecha">' + esc(fechaCreacion) + '</span>' +
            '<span class="nota-asesor">' + esc(nota.asesor_nombre || '') + '</span>' +
            '</div>' +
            badgeTipo +
            estadoHtml +
            cuerpo +
            prox +
            fechaProx +
            '</div>';
    }

    global.TicketNotasRender = {
        parseTipificacionNotaJson: parseTipificacionNotaJson,
        buildTipificacionHistorialHtml: buildTipificacionHistorialHtml,
        renderTicketNotaItem: renderTicketNotaItem
    };
})(window);
