/**
 * Modal "Ver detalle" — layout acotado al viewport y título Case/Parcel.
 * Usado en views/asesor_tickets.php
 */
(function (global) {
    'use strict';

    var MODAL_ID = 'ticketDetalleModal';
    var CLASS_OPEN = 'is-open';
    var BODY_LOCK = 'ticket-detalle-modal-open';

    function getModal() {
        return document.getElementById(MODAL_ID);
    }

    function getDialog() {
        var modal = getModal();
        return modal ? modal.querySelector('.ticket-detalle-modal-dialog') : null;
    }

    function getBody() {
        var modal = getModal();
        return modal ? modal.querySelector('.ticket-detalle-modal-body') : null;
    }

    function hasValor(v) {
        return v !== null && v !== undefined && String(v).trim() !== '';
    }

    /**
     * Título del modal: Case Number o Parcel Number (reparto, predio o listado).
     */
    function tituloFromTicket(ticket) {
        if (!ticket) return 'Detalle del ticket';

        var pr = ticket.propiedad_reparto || null;
        var pred = ticket.predio || null;

        var caso = (pr && pr.numero_caso) || (pred && pred.case_number) ||
            ticket.predio_case_number || ticket.reparto_numero_caso || ticket.numero_caso || '';
        var parcela = (pr && pr.numero_parcela) || (pred && pred.parcel_number) ||
            ticket.predio_parcel_number || ticket.reparto_numero_parcela || ticket.numero_parcela || '';

        if (!hasValor(caso) && hasValor(ticket.numero_ticket)) {
            caso = ticket.numero_ticket;
        }

        var casoStr = String(caso).trim();
        var parcelaStr = String(parcela).trim();

        if (casoStr && parcelaStr) {
            if (casoStr === parcelaStr) {
                return 'Case · Parcel ' + casoStr;
            }
            return 'Case ' + casoStr + ' · Parcel ' + parcelaStr;
        }
        if (casoStr) {
            return 'Case Number ' + casoStr;
        }
        if (parcelaStr) {
            return 'Parcel Number ' + parcelaStr;
        }
        return ticket.numero_ticket ? String(ticket.numero_ticket) : ('Ticket #' + (ticket.id || ''));
    }

    function setTitulo(ticket, escapeHtmlFn) {
        var titleEl = document.getElementById('ticketDetalleTitle');
        if (!titleEl) return;
        var esc = typeof escapeHtmlFn === 'function' ? escapeHtmlFn : function (s) { return String(s); };
        var texto = tituloFromTicket(ticket);
        titleEl.innerHTML = '<i class="fas fa-hashtag" aria-hidden="true"></i> ' + esc(texto);
    }

    function ajustarLayout() {
        var modal = getModal();
        var dialog = getDialog();
        var body = getBody();
        if (!modal || !dialog || !body || !modal.classList.contains(CLASS_OPEN)) {
            return;
        }

        var footer = dialog.querySelector('.ticket-detalle-modal-footer');
        var header = dialog.querySelector('.modal-header');
        var maxDialog = Math.floor(window.innerHeight * 0.88) - 20;

        dialog.style.maxHeight = maxDialog + 'px';
        dialog.style.height = 'auto';
        dialog.style.overflow = 'hidden';

        body.style.flex = '1 1 auto';
        body.style.minHeight = '0';
        body.style.maxHeight = '';
        body.style.height = 'auto';
        body.style.overflowY = 'visible';

        var natural = dialog.scrollHeight;

        if (natural > maxDialog) {
            dialog.style.height = maxDialog + 'px';

            var chrome = 0;
            if (header) chrome += header.offsetHeight;
            if (footer && footer.style.display !== 'none' && footer.offsetParent !== null) {
                chrome += footer.offsetHeight;
            }

            var bodyH = Math.max(120, maxDialog - chrome);
            body.style.maxHeight = bodyH + 'px';
            body.style.height = bodyH + 'px';
            body.style.overflowY = 'auto';
            body.style.overflowX = 'hidden';
        } else {
            dialog.style.height = 'auto';
            body.style.maxHeight = 'none';
            body.style.height = 'auto';
            body.style.overflowY = 'auto';
        }
    }

    function open() {
        var modal = getModal();
        if (!modal) return;
        modal.classList.add(CLASS_OPEN);
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add(BODY_LOCK);
        requestAnimationFrame(function () {
            ajustarLayout();
        });
    }

    function close() {
        var modal = getModal();
        if (!modal) return;
        modal.classList.remove(CLASS_OPEN);
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove(BODY_LOCK);

        var dialog = getDialog();
        var body = getBody();
        if (dialog) {
            dialog.style.height = '';
            dialog.style.maxHeight = '';
        }
        if (body) {
            body.style.height = '';
            body.style.maxHeight = '';
            body.style.overflowY = '';
        }
    }

    function isOpen() {
        var modal = getModal();
        return modal && modal.classList.contains(CLASS_OPEN);
    }

    var resizeBound = false;
    function bindResize() {
        if (resizeBound) return;
        resizeBound = true;
        window.addEventListener('resize', function () {
            if (isOpen()) ajustarLayout();
        });
    }

    bindResize();

    global.TicketDetalleModal = {
        tituloFromTicket: tituloFromTicket,
        setTitulo: setTitulo,
        ajustarLayout: ajustarLayout,
        open: open,
        close: close,
        isOpen: isOpen
    };
})(window);
