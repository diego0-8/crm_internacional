/**
 * Campana del asesor: llamadas/seguimientos hoy y vencidos (ticket_notas).
 */
(function (global) {
    'use strict';

    var API_URL = 'api/llamadas_pendientes_asesor.php';
    var refreshTimer = null;
    var REFRESH_MS = 5 * 60 * 1000;
    var cachedItems = [];

    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function tituloItem(item) {
        var caso = String(item.case_number || '').trim();
        var parcela = String(item.parcel_number || '').trim();
        if (caso && parcela && caso !== parcela) {
            return 'Case ' + caso + ' · Parcel ' + parcela;
        }
        if (caso) return 'Case ' + caso;
        if (parcela) return 'Parcel ' + parcela;
        if (item.numero_ticket) return String(item.numero_ticket);
        if (item.titulo) return String(item.titulo);
        return 'Ticket #' + item.ticket_id;
    }

    function formatHora(fechaStr) {
        if (!fechaStr) return '—';
        try {
            var d = new Date(fechaStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return fechaStr;
            return d.toLocaleString(undefined, {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return fechaStr;
        }
    }

    function updateBadge(total) {
        var badge = $('notificationCount');
        if (!badge) return;
        var n = parseInt(total, 10) || 0;
        badge.textContent = n > 99 ? '99+' : String(n);
        if (n > 0) {
            badge.removeAttribute('hidden');
        } else {
            badge.setAttribute('hidden', 'hidden');
        }
    }

    function renderList(items) {
        var list = $('asesorBellList');
        if (!list) return;

        if (!items || items.length === 0) {
            list.innerHTML = '<p class="asesor-bell-empty">No tienes llamadas programadas para hoy ni vencidas.</p>';
            return;
        }

        var vencidas = items.filter(function (i) { return i.urgencia === 'vencida'; });
        var hoy = items.filter(function (i) { return i.urgencia !== 'vencida'; });

        var html = '';

        function renderGrupo(titulo, grupo, claseUrg) {
            if (!grupo.length) return '';
            var h = '<div class="asesor-bell-group">';
            h += '<div class="asesor-bell-group-title">' + escapeHtml(titulo) + '</div>';
            grupo.forEach(function (item) {
                var href = typeof global.appNav === 'function'
                    ? global.appNav('asesor_gestionar_ticket', { id: item.ticket_id })
                    : ('?_route=asesor_gestionar_ticket&id=' + encodeURIComponent(item.ticket_id));
                var accion = String(item.proxima_accion || '').trim();
                var tel = String(item.cliente_telefono || '').trim();
                var cliente = String(item.cliente_nombre || '').trim();
                var urgLabel = claseUrg === 'vencida' ? 'Vencida' : 'Hoy';
                h += '<a class="asesor-bell-item asesor-bell-item--' + claseUrg + '" href="' + escapeHtml(href) + '">';
                h += '<div class="asesor-bell-item-head">';
                h += '<span class="asesor-bell-item-title">' + escapeHtml(tituloItem(item)) + '</span>';
                h += '<span class="asesor-bell-urgencia asesor-bell-urgencia--' + claseUrg + '">' + urgLabel + '</span>';
                h += '</div>';
                h += '<div class="asesor-bell-item-meta"><i class="fas fa-clock" aria-hidden="true"></i> ' + escapeHtml(formatHora(item.fecha_proxima_accion)) + '</div>';
                if (accion) {
                    h += '<div class="asesor-bell-item-accion">' + escapeHtml(accion) + '</div>';
                }
                if (cliente || tel) {
                    h += '<div class="asesor-bell-item-cliente">';
                    if (cliente) h += '<span>' + escapeHtml(cliente) + '</span>';
                    if (tel) h += ' <span class="asesor-bell-tel">' + escapeHtml(tel) + '</span>';
                    h += '</div>';
                }
                h += '</a>';
            });
            h += '</div>';
            return h;
        }

        html += renderGrupo('Vencidas', vencidas, 'vencida');
        html += renderGrupo('Hoy', hoy, 'hoy');
        list.innerHTML = html;
    }

    function setListStatus(msg, isError) {
        var list = $('asesorBellList');
        if (!list) return;
        var cls = isError ? 'asesor-bell-status asesor-bell-status--error' : 'asesor-bell-status';
        list.innerHTML = '<p class="' + cls + '">' + msg + '</p>';
    }

    function fetchPendientes() {
        return fetch(API_URL, { credentials: 'same-origin' })
            .then(function (res) {
                if (res.status === 401) {
                    if (typeof global.appGoLogin === 'function') {
                        global.appGoLogin();
                    }
                    throw new Error('No autorizado');
                }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (result) {
                if (!result.success) {
                    throw new Error(result.message || 'Error al cargar');
                }
                cachedItems = result.data || [];
                updateBadge(result.total != null ? result.total : cachedItems.length);
                return cachedItems;
            });
    }

    function loadAndRender(showLoadingInPanel) {
        var list = $('asesorBellList');
        if (showLoadingInPanel && list) {
            list.innerHTML = '<p class="asesor-bell-status"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando…</p>';
        }
        return fetchPendientes()
            .then(function (items) {
                var panel = $('asesorBellPanel');
                if (panel && !panel.hidden) {
                    renderList(items);
                }
                return items;
            })
            .catch(function (err) {
                console.error('asesor bell:', err);
                updateBadge(0);
                var panel = $('asesorBellPanel');
                if (panel && !panel.hidden) {
                    setListStatus('No se pudieron cargar las llamadas pendientes.', true);
                }
            });
    }

    function setPanelOpen(open) {
        var btn = $('asesorBellBtn');
        var panel = $('asesorBellPanel');
        if (!btn || !panel) return;

        if (open) {
            panel.removeAttribute('hidden');
            btn.setAttribute('aria-expanded', 'true');
            renderList(cachedItems);
            if (!cachedItems.length) {
                loadAndRender(true);
            }
        } else {
            panel.setAttribute('hidden', 'hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    }

    function isPanelOpen() {
        var panel = $('asesorBellPanel');
        return panel && !panel.hasAttribute('hidden');
    }

    function bindEvents() {
        var btn = $('asesorBellBtn');
        var panel = $('asesorBellPanel');
        var closeBtn = $('asesorBellPanelClose');
        var wrap = document.querySelector('.asesor-bell-wrap');

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                setPanelOpen(!isPanelOpen());
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                setPanelOpen(false);
            });
        }

        document.addEventListener('click', function (e) {
            if (!isPanelOpen()) return;
            if (wrap && wrap.contains(e.target)) return;
            setPanelOpen(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isPanelOpen()) {
                setPanelOpen(false);
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                loadAndRender(false);
            }
        });

        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(function () {
            if (document.visibilityState === 'visible') {
                loadAndRender(false);
            }
        }, REFRESH_MS);
    }

    function initAsesorBell() {
        if (!$('asesorBellBtn')) return;
        bindEvents();
        loadAndRender(false);
    }

    global.initAsesorBell = initAsesorBell;
    global.refreshAsesorBell = function () { return loadAndRender(false); };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAsesorBell);
    } else {
        initAsesorBell();
    }
})(window);
